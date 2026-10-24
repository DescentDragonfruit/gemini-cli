<?php
/**
 * Rename an attachment's files (all sizes + variants), rewrite every reference, register 301s.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Rename {

	public static function init() {
		add_action( 'wp_ajax_ccpc_rename', array( __CLASS__, 'ajax_rename' ) );
		add_filter( 'bulk_actions-upload', array( __CLASS__, 'bulk_action' ) );
		add_filter( 'handle_bulk_actions-upload', array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'serve_fallback_redirects' ), 1 );
	}

	/**
	 * Rename an attachment to a new stem.
	 *
	 * @param int    $attachment_id    ID.
	 * @param string $new_stem         Desired filename without extension.
	 * @param bool   $register_redirect Add 301s (skipped automatically when nothing references it yet).
	 * @return array|WP_Error { old => new URL map }.
	 */
	public static function rename( $attachment_id, $new_stem, $register_redirect = true ) {
		global $wpdb;
		$attachment_id = (int) $attachment_id;
		$old_rel       = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
		$old_abs       = get_attached_file( $attachment_id );
		if ( ! $old_rel || ! $old_abs || ! file_exists( $old_abs ) ) {
			return new WP_Error( 'ccpc_nofile', 'Attachment file not found.' );
		}
		$new_stem = sanitize_title( $new_stem );
		if ( '' === $new_stem ) {
			return new WP_Error( 'ccpc_badname', 'Empty filename.' );
		}
		$dir      = dirname( $old_abs );
		$ext      = pathinfo( $old_abs, PATHINFO_EXTENSION );
		$old_stem = pathinfo( $old_abs, PATHINFO_FILENAME );
		if ( $old_stem === $new_stem ) {
			return array();
		}
		$new_name = wp_unique_filename( $dir, $new_stem . '.' . $ext );
		$new_stem = pathinfo( $new_name, PATHINFO_FILENAME );

		$meta  = wp_get_attachment_metadata( $attachment_id );
		$files = array( basename( $old_abs ) => $new_name ); // old basename => new basename
		if ( ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $k => $s ) {
				if ( empty( $s['file'] ) ) {
					continue;
				}
				$suffix = substr( $s['file'], strlen( $old_stem ) ); // e.g. "-300x200.jpg"
				if ( 0 === strpos( $s['file'], $old_stem ) ) {
					$files[ $s['file'] ] = $new_stem . $suffix;
				}
			}
		}
		if ( ! empty( $meta['original_image'] ) && 0 === strpos( $meta['original_image'], $old_stem ) ) {
			$files[ $meta['original_image'] ] = $new_stem . substr( $meta['original_image'], strlen( $old_stem ) );
		}
		// Variants (.webp/.avif siblings) and the backed-up original.
		$variants = array();
		foreach ( $files as $o => $n ) {
			foreach ( array( 'webp', 'avif' ) as $v ) {
				$ov = CCPC_Processor::variant_path( $dir . '/' . $o, $v );
				if ( file_exists( $ov ) ) {
					$variants[ basename( $ov ) ] = basename( CCPC_Processor::variant_path( $dir . '/' . $n, $v ) );
				}
			}
		}
		$all = $files + $variants;

		// Physical renames (abort cleanly if any fails).
		$done = array();
		foreach ( $all as $o => $n ) {
			if ( ! file_exists( $dir . '/' . $o ) ) {
				continue;
			}
			if ( ! rename( $dir . '/' . $o, $dir . '/' . $n ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				foreach ( $done as $dn => $do ) {
					rename( $dir . '/' . $dn, $dir . '/' . $do ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				}
				return new WP_Error( 'ccpc_rename_failed', 'Could not rename ' . $o );
			}
			$done[ $n ] = $o;
		}
		$orig = $dir . '/originals/' . basename( $old_abs );
		if ( file_exists( $orig ) ) {
			rename( $orig, $dir . '/originals/' . $new_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		// Attachment records.
		$subdir  = dirname( $old_rel );
		$new_rel = ( '.' === $subdir ? '' : $subdir . '/' ) . $new_name;
		update_post_meta( $attachment_id, '_wp_attached_file', $new_rel );
		if ( is_array( $meta ) ) {
			$meta['file'] = $new_rel;
			if ( ! empty( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $k => $s ) {
					if ( ! empty( $s['file'] ) && isset( $files[ $s['file'] ] ) ) {
						$meta['sizes'][ $k ]['file'] = $files[ $s['file'] ];
					}
				}
			}
			if ( ! empty( $meta['original_image'] ) && isset( $files[ $meta['original_image'] ] ) ) {
				$meta['original_image'] = $files[ $meta['original_image'] ];
			}
			if ( ! empty( $meta['ccpc_variants'] ) ) {
				$meta['ccpc_variants'] = array_map( function ( $p ) use ( $variants ) {
					$b = basename( $p );
					return isset( $variants[ $b ] ) ? dirname( $p ) . '/' . $variants[ $b ] : $p;
				}, $meta['ccpc_variants'] );
			}
			wp_update_attachment_metadata( $attachment_id, $meta );
		}
		$uploads  = wp_get_upload_dir();
		$base_url = trailingslashit( $uploads['baseurl'] ) . ( '.' === $subdir ? '' : $subdir . '/' );
		$wpdb->update( $wpdb->posts, array( 'guid' => $base_url . $new_name ), array( 'ID' => $attachment_id ) );
		clean_post_cache( $attachment_id );

		// URL map (old => new) for content/meta rewrites and redirects.
		$map = array();
		foreach ( $all as $o => $n ) {
			$map[ $base_url . $o ] = $base_url . $n;
		}

		$usages = CCPC_Usages::find( $attachment_id );
		self::rewrite_references( $map, $usages );

		if ( $register_redirect && $usages ) {
			self::register_redirects( $map );
		}
		self::purge_caches();
		return $map;
	}

	/**
	 * Replace old URLs with new in post_content and postmeta of the given usages (plain string
	 * replace, plus the JSON-escaped form Kadence/Gutenberg store).
	 */
	public static function rewrite_references( $map, $usages ) {
		global $wpdb;
		$search  = array();
		$replace = array();
		foreach ( $map as $o => $n ) {
			$search[]  = $o;
			$replace[] = $n;
			$search[]  = str_replace( '/', '\/', $o );
			$replace[] = str_replace( '/', '\/', $n );
			// Relative (path-only) forms.
			$op = wp_parse_url( $o, PHP_URL_PATH );
			$np = wp_parse_url( $n, PHP_URL_PATH );
			if ( $op && $np ) {
				$search[]  = $op;
				$replace[] = $np;
				$search[]  = str_replace( '/', '\/', $op );
				$replace[] = str_replace( '/', '\/', $np );
			}
		}
		$post_ids = array_unique( wp_list_pluck( $usages, 'post_id' ) );
		foreach ( $post_ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				continue;
			}
			$new_content = str_replace( $search, $replace, $post->post_content );
			if ( $new_content !== $post->post_content ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $new_content ), array( 'ID' => $pid ) );
			}
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $pid ) );
			foreach ( $rows as $row ) {
				if ( '_' === substr( $row->meta_key, 0, 1 ) ) {
					continue;
				}
				$val = maybe_unserialize( $row->meta_value );
				$new = self::deep_replace( $val, $search, $replace );
				if ( $new !== $val ) {
					update_metadata_by_mid( 'post', $row->meta_id, $new );
				}
			}
			clean_post_cache( $pid );
		}
	}

	protected static function deep_replace( $val, $search, $replace ) {
		if ( is_string( $val ) ) {
			return str_replace( $search, $replace, $val );
		}
		if ( is_array( $val ) ) {
			foreach ( $val as $k => $v ) {
				$val[ $k ] = self::deep_replace( $v, $search, $replace );
			}
		}
		return $val;
	}

	/**
	 * Register 301s: Rank Math Redirections when present, else the plugin's own option.
	 */
	public static function register_redirects( $map ) {
		$stored = get_option( CCPC_REDIRECTS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		foreach ( $map as $o => $n ) {
			$path = wp_parse_url( $o, PHP_URL_PATH );
			if ( ! $path ) {
				continue;
			}
			$stored[ $path ] = $n;
			if ( class_exists( '\RankMath\Redirections\DB' ) && method_exists( '\RankMath\Redirections\DB', 'add' ) ) {
				try {
					\RankMath\Redirections\DB::add( array(
						'sources'     => array( array( 'pattern' => ltrim( $path, '/' ), 'comparison' => 'exact', 'ignore' => '' ) ),
						'url_to'      => $n,
						'header_code' => 301,
						'status'      => 'active',
					) );
				} catch ( \Throwable $e ) {
					// Fall through to our own option-based redirect.
				}
			}
		}
		update_option( CCPC_REDIRECTS_OPTION, $stored, false );
	}

	/**
	 * Option-based fallback. Only reaches PHP when the old file no longer exists and the
	 * server hands 404s to WordPress; see README for the .htaccess / LiteSpeed rule.
	 */
	public static function serve_fallback_redirects() {
		if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		if ( ! $path || false === strpos( $path, '/wp-content/uploads/' ) ) {
			return;
		}
		$stored = get_option( CCPC_REDIRECTS_OPTION, array() );
		if ( isset( $stored[ $path ] ) ) {
			wp_redirect( $stored[ $path ], 301 ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		}
	}

	/**
	 * Redirect rules text for copy-paste (Apache/LiteSpeed .htaccess + Cloudflare bulk).
	 */
	public static function rules_text() {
		$stored = get_option( CCPC_REDIRECTS_OPTION, array() );
		if ( ! is_array( $stored ) || ! $stored ) {
			return "# No redirects recorded yet.\n";
		}
		$out = "# Coco Photo CMS — paste into .htaccess above '# BEGIN WordPress' (Apache / LiteSpeed)\n";
		foreach ( $stored as $path => $url ) {
			$out .= 'Redirect 301 ' . $path . ' ' . $url . "\n";
		}
		$out .= "\n# Cloudflare Bulk Redirects CSV (source_url,target_url,status_code)\n";
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		foreach ( $stored as $path => $url ) {
			$out .= 'https://' . $host . $path . ',' . $url . ",301\n";
		}
		return $out;
	}

	public static function purge_caches() {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'litespeed_purge_all' );
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}

	/* ---------- Admin entry points ---------- */

	public static function ajax_rename() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$stem = isset( $_POST['stem'] ) ? sanitize_title( wp_unslash( $_POST['stem'] ) ) : '';
		$r    = self::rename( $id, $stem );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		wp_send_json_success( array(
			'file' => basename( (string) get_post_meta( $id, '_wp_attached_file', true ) ),
			'map'  => $r,
		) );
	}

	public static function bulk_action( $actions ) {
		$actions['ccpc_rename_template'] = __( 'Rename from Coco Photo fields', 'coco-photo-cms' );
		return $actions;
	}

	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( 'ccpc_rename_template' !== $action ) {
			return $redirect;
		}
		$n = 0;
		foreach ( $ids as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) || ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$stem = CCPC_Naming::build_stem( array(
				'property' => get_post_meta( $id, '_ccpc_property', true ),
				'place'    => get_post_meta( $id, '_ccpc_place', true ),
				'subject'  => get_post_meta( $id, '_ccpc_subject', true ),
			) );
			if ( $stem && ! is_wp_error( self::rename( $id, $stem ) ) ) {
				$n++;
			}
		}
		return add_query_arg( 'ccpc_renamed', $n, $redirect );
	}
}
