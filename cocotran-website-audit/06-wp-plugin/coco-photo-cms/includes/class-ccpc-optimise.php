<?php
/**
 * Resize one photo in place, and the batched "Optimise all images" job with a dry-run estimate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Optimise {

	const JOB_OPTION = 'ccpc_optimise_job';
	const BATCH      = 5;
	const EST_BATCH  = 50;
	const JPEG_Q     = 82;

	public static function init() {
		add_action( 'wp_ajax_ccpc_resize', array( __CLASS__, 'ajax_resize' ) );
		add_action( 'wp_ajax_ccpc_optimise_estimate', array( __CLASS__, 'ajax_estimate' ) );
		add_action( 'wp_ajax_ccpc_optimise_batch', array( __CLASS__, 'ajax_batch' ) );
		add_action( 'wp_ajax_ccpc_optimise_reset', array( __CLASS__, 'ajax_reset' ) );
	}

	/* ---------- Helpers ---------- */

	/**
	 * Bytes on disk for the full file, every size and their WebP/AVIF siblings (not /originals/).
	 */
	public static function bytes_for( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return 0;
		}
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$paths = array( $file );
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $s ) {
				if ( ! empty( $s['file'] ) ) {
					$paths[] = dirname( $file ) . '/' . $s['file'];
				}
			}
		}
		$total = 0;
		foreach ( $paths as $p ) {
			foreach ( array( $p, CCPC_Processor::variant_path( $p, 'webp' ), CCPC_Processor::variant_path( $p, 'avif' ) ) as $f ) {
				if ( file_exists( $f ) ) {
					$total += (int) filesize( $f );
				}
			}
		}
		return $total;
	}

	protected static function image_ids( $offset, $limit ) {
		return get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => (int) $limit,
			'offset'         => (int) $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		) );
	}

	public static function count_images() {
		$q = new WP_Query( array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 1,
		) );
		return (int) $q->found_posts;
	}

	/* ---------- Resize one ---------- */

	/**
	 * Re-encode the full-size file at a new long edge, regenerate sizes + WebP, bump the version.
	 *
	 * @param int $attachment_id ID.
	 * @param int $long_edge     Target long edge (400–6000). Never upscales beyond the best source.
	 * @return array|WP_Error { width, height, bytes_before, bytes_after, source }.
	 */
	public static function resize_to( $attachment_id, $long_edge ) {
		$attachment_id = (int) $attachment_id;
		$file          = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return new WP_Error( 'ccpc_missing', 'Attachment file not found.', array( 'status' => 404 ) );
		}
		if ( ! CCPC_Processor::is_processable( (string) get_post_mime_type( $attachment_id ) ) ) {
			return new WP_Error( 'ccpc_notimage', 'Only JPEG/PNG attachments can be resized.', array( 'status' => 400 ) );
		}
		$long_edge = max( 400, min( 6000, (int) $long_edge ) );
		$before    = self::bytes_for( $attachment_id );
		$dir       = dirname( $file );
		$orig      = $dir . '/originals/' . basename( $file );
		$source    = $file;
		if ( ! empty( ccpc_setting( 'keep_originals' ) ) ) {
			if ( ! file_exists( $orig ) && wp_mkdir_p( $dir . '/originals' ) ) {
				copy( $file, $orig ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
		if ( file_exists( $orig ) ) {
			$source = $orig; // Best quality: resize from the untouched upload, not from a prior re-encode.
		}

		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$keep = CCPC_Processor::read_credit_exif( $source );
		$size = $editor->get_size();
		if ( max( (int) $size['width'], (int) $size['height'] ) > $long_edge ) {
			$r = $editor->resize( $long_edge, $long_edge, false );
			if ( is_wp_error( $r ) ) {
				return $r;
			}
		}
		$editor->set_quality( 86 );

		// Old sizes/variants go first so nothing is orphaned by changed dimensions.
		CCPC_Processor::delete_generated( $attachment_id, false );

		$saved = $editor->save( $file );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		if ( $keep ) {
			CCPC_Processor::write_credit_exif( $file, $keep );
		}
		clearstatcache( true, $file );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		// We manage the size ourselves; do not let core add a -scaled copy and move the attached file.
		add_filter( 'big_image_size_threshold', '__return_false', 99 );
		$meta = wp_generate_attachment_metadata( $attachment_id, $file );
		remove_filter( 'big_image_size_threshold', '__return_false', 99 );
		if ( is_array( $meta ) ) {
			wp_update_attachment_metadata( $attachment_id, $meta );
		}
		update_post_meta( $attachment_id, '_ccpc_version', time() );
		CCPC_Rename::purge_caches();
		$meta = wp_get_attachment_metadata( $attachment_id );
		return array(
			'id'           => $attachment_id,
			'width'        => (int) ( $meta['width'] ?? 0 ),
			'height'       => (int) ( $meta['height'] ?? 0 ),
			'bytes_before' => $before,
			'bytes_after'  => self::bytes_for( $attachment_id ),
			'source'       => $source === $orig ? 'original' : 'current',
		);
	}

	public static function ajax_resize() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$r = self::resize_to( $id, isset( $_POST['long_edge'] ) ? (int) $_POST['long_edge'] : 0 );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		wp_send_json_success( $r );
	}

	/* ---------- Optimise one ---------- */

	/**
	 * @param int   $attachment_id ID.
	 * @param array $opts          { reencode_jpeg: bool }.
	 * @return array|WP_Error report.
	 */
	public static function optimise_one( $attachment_id, $opts = array() ) {
		$attachment_id = (int) $attachment_id;
		$opts          = wp_parse_args( $opts, array( 'reencode_jpeg' => false ) );
		$file          = get_attached_file( $attachment_id );
		$report        = array(
			'id'      => $attachment_id,
			'file'    => $file ? basename( $file ) : '',
			'before'  => 0,
			'after'   => 0,
			'actions' => array(),
		);
		if ( ! $file || ! file_exists( $file ) ) {
			$report['actions'][] = 'missing file';
			return $report;
		}
		if ( ! CCPC_Processor::is_processable( (string) get_post_mime_type( $attachment_id ) ) ) {
			$report['actions'][] = 'skipped (not JPEG/PNG)';
			return $report;
		}
		$report['before'] = self::bytes_for( $attachment_id );
		$meta             = wp_get_attachment_metadata( $attachment_id );
		$max              = max( 400, (int) ccpc_setting( 'max_long_edge' ) );
		$long             = max( (int) ( $meta['width'] ?? 0 ), (int) ( $meta['height'] ?? 0 ) );
		$resized          = false;

		if ( $long > $max ) {
			$r = self::resize_to( $attachment_id, $max );
			if ( is_wp_error( $r ) ) {
				$report['actions'][] = 'resize failed: ' . $r->get_error_message();
			} else {
				$resized             = true;
				$report['actions'][] = sprintf( 'resized %d→%d px', $long, max( $r['width'], $r['height'] ) );
				$meta                = wp_get_attachment_metadata( $attachment_id );
			}
		}

		if ( ! empty( $opts['reencode_jpeg'] ) && 'image/jpeg' === get_post_mime_type( $attachment_id ) ) {
			$r = CCPC_Image::reencode_jpeg( $file, self::JPEG_Q );
			if ( is_wp_error( $r ) ) {
				$report['actions'][] = 'jpeg re-encode failed: ' . $r->get_error_message();
			} elseif ( $r[1] < $r[0] ) {
				$report['actions'][] = sprintf( 'jpeg re-encoded q%d progressive (%s→%s)', self::JPEG_Q, size_format( $r[0] ), size_format( $r[1] ) );
				if ( is_array( $meta ) ) {
					$meta['filesize'] = $r[1];
				}
				if ( ! $resized ) {
					update_post_meta( $attachment_id, '_ccpc_version', time() );
				}
			} else {
				$report['actions'][] = 'jpeg already smaller than q' . self::JPEG_Q;
			}
		}

		// WebP for all sizes at the configured quality. After a resize only stale ones are redone
		// (mtime check); otherwise force so a quality change is applied everywhere.
		$variants = CCPC_Processor::generate_variants( $attachment_id, is_array( $meta ) ? $meta : null, ! $resized );
		if ( is_array( $meta ) ) {
			$meta['ccpc_variants'] = $variants;
			wp_update_attachment_metadata( $attachment_id, $meta );
		}
		$report['actions'][] = sprintf( 'webp ×%d @ q%d', count( $variants ), (int) ccpc_setting( 'webp_quality' ) );
		$report['after']     = self::bytes_for( $attachment_id );
		return $report;
	}

	/* ---------- Job state ---------- */

	public static function default_job() {
		return array(
			'offset'    => 0,
			'total'     => 0,
			'processed' => 0,
			'before'    => 0,
			'after'     => 0,
			'resized'   => 0,
			'reencoded' => 0,
			'started'   => 0,
			'finished'  => 0,
			'opts'      => array( 'reencode_jpeg' => 0 ),
			'last'      => array(),
		);
	}

	public static function get_job() {
		$j = get_option( self::JOB_OPTION, array() );
		return wp_parse_args( is_array( $j ) ? $j : array(), self::default_job() );
	}

	public static function save_job( $job ) {
		update_option( self::JOB_OPTION, $job, false );
		return $job;
	}

	/**
	 * Run one batch of the job. Resumable: state lives in an option.
	 *
	 * @param array $opts { reencode_jpeg } used when (re)starting.
	 * @param bool  $restart Reset state first.
	 * @return array job state.
	 */
	public static function run_batch( $opts = array(), $restart = false ) {
		$job = self::get_job();
		if ( $restart || $job['finished'] || ! $job['started'] ) {
			$job                          = self::default_job();
			$job['started']               = time();
			$job['total']                 = self::count_images();
			$job['opts']['reencode_jpeg'] = empty( $opts['reencode_jpeg'] ) ? 0 : 1;
		}
		$ids  = self::image_ids( $job['offset'], self::BATCH );
		$last = array();
		foreach ( $ids as $id ) {
			$r = self::optimise_one( $id, array( 'reencode_jpeg' => ! empty( $job['opts']['reencode_jpeg'] ) ) );
			if ( is_wp_error( $r ) ) {
				continue;
			}
			$job['before'] += $r['before'];
			$job['after']  += $r['after'];
			$job['processed']++;
			foreach ( $r['actions'] as $a ) {
				if ( 0 === strpos( $a, 'resized' ) ) {
					$job['resized']++;
				}
				if ( 0 === strpos( $a, 'jpeg re-encoded' ) ) {
					$job['reencoded']++;
				}
			}
			$last[] = $r;
		}
		$job['offset'] += count( $ids );
		$job['last']    = $last;
		if ( count( $ids ) < self::BATCH ) {
			$job['finished'] = time();
		}
		return self::save_job( $job );
	}

	/**
	 * Dry-run estimate, one page at a time (the caller accumulates).
	 */
	public static function estimate_batch( $offset ) {
		$ids = self::image_ids( $offset, self::EST_BATCH );
		$out = array(
			'processed' => count( $ids ),
			'bytes'     => 0,
			'no_webp'   => 0,
			'over_cap'  => 0,
			'next'      => count( $ids ) < self::EST_BATCH ? null : (int) $offset + self::EST_BATCH,
		);
		$max = max( 400, (int) ccpc_setting( 'max_long_edge' ) );
		foreach ( $ids as $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}
			$out['bytes'] += self::bytes_for( $id );
			if ( ! file_exists( CCPC_Processor::variant_path( $file, 'webp' ) ) ) {
				$out['no_webp']++;
			}
			$meta = wp_get_attachment_metadata( $id );
			if ( max( (int) ( $meta['width'] ?? 0 ), (int) ( $meta['height'] ?? 0 ) ) > $max ) {
				$out['over_cap']++;
			}
		}
		return $out;
	}

	/* ---------- AJAX ---------- */

	protected static function check_admin() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
	}

	public static function ajax_estimate() {
		self::check_admin();
		$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$r      = self::estimate_batch( $offset );
		if ( 0 === $offset ) {
			$r['count'] = self::count_images();
		}
		wp_send_json_success( $r );
	}

	public static function ajax_batch() {
		self::check_admin();
		if ( '' === CCPC_Image::engine() && is_wp_error( wp_get_image_editor( '' ) ) ) {
			wp_send_json_error( 'No image library available.' );
		}
		$restart = ! empty( $_POST['restart'] );
		$opts    = array( 'reencode_jpeg' => ! empty( $_POST['reencode_jpeg'] ) );
		wp_send_json_success( self::run_batch( $opts, $restart ) );
	}

	public static function ajax_reset() {
		self::check_admin();
		delete_option( self::JOB_OPTION );
		wp_send_json_success( self::default_job() );
	}
}
