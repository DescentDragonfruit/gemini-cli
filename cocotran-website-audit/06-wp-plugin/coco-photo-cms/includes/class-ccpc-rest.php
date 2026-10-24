<?php
/**
 * REST intake: /wp-json/coco-photo-cms/v1/* — used by Claude artifacts to push images.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Rest {

	const NS = 'coco-photo-cms/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'cors' ), 10, 4 );
	}

	public static function routes() {
		register_rest_route( self::NS, '/upload', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'upload' ),
			'permission_callback' => array( __CLASS__, 'can_upload' ),
		) );
		register_rest_route( self::NS, '/usages/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => function ( $req ) {
				return rest_ensure_response( CCPC_Usages::find( (int) $req['id'] ) );
			},
			'permission_callback' => array( __CLASS__, 'can_upload' ),
		) );
		register_rest_route( self::NS, '/rename/(?P<id>\d+)', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rename' ),
			'permission_callback' => array( __CLASS__, 'can_edit' ),
		) );
		register_rest_route( self::NS, '/replace/(?P<id>\d+)', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'replace' ),
			'permission_callback' => array( __CLASS__, 'can_edit' ),
		) );
		register_rest_route( self::NS, '/settings', array(
			'methods'             => 'GET',
			'callback'            => function () {
				$s = ccpc_get_settings();
				unset( $s['cors_origins'] );
				return rest_ensure_response( $s );
			},
			'permission_callback' => array( __CLASS__, 'can_upload' ),
		) );
	}

	public static function can_upload() {
		return current_user_can( 'upload_files' );
	}

	public static function can_edit( $req ) {
		return current_user_can( 'edit_post', (int) $req['id'] );
	}

	/**
	 * CORS: only for our namespace, only for allowlisted origins (supports *.domain).
	 */
	public static function cors( $served, $result, $request, $server ) {
		if ( 0 !== strpos( $request->get_route(), '/' . self::NS ) ) {
			return $served;
		}
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		if ( $origin && self::origin_allowed( $origin ) ) {
			header( 'Access-Control-Allow-Origin: ' . $origin );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
			header( 'Vary: Origin', false );
		}
		return $served;
	}

	public static function origin_allowed( $origin ) {
		$list = preg_split( '/\s+/', trim( (string) ccpc_setting( 'cors_origins' ) ) );
		$o    = wp_parse_url( $origin );
		if ( empty( $o['host'] ) ) {
			return false;
		}
		$scheme = isset( $o['scheme'] ) ? $o['scheme'] : 'https';
		foreach ( $list as $allowed ) {
			$allowed = trim( $allowed );
			if ( '' === $allowed ) {
				continue;
			}
			$a = wp_parse_url( $allowed );
			if ( empty( $a['host'] ) ) {
				continue;
			}
			if ( ( isset( $a['scheme'] ) ? $a['scheme'] : 'https' ) !== $scheme ) {
				continue;
			}
			if ( 0 === strpos( $a['host'], '*.' ) ) {
				$suffix = substr( $a['host'], 1 ); // ".claude.ai"
				if ( substr( $o['host'], -strlen( $suffix ) ) === $suffix ) {
					return true;
				}
			} elseif ( $a['host'] === $o['host'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * POST /upload — multipart `file` or JSON `data_url`; fields: property, place, subject, n,
	 * caption, alt (required), credit, visited, license_note, title, post_id, acf_field,
	 * insert_into_content (bool), set_featured (bool).
	 */
	public static function upload( WP_REST_Request $req ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$p        = $req->get_params();
		$warnings = array();
		$alt      = isset( $p['alt'] ) ? sanitize_text_field( $p['alt'] ) : '';
		if ( '' === trim( $alt ) ) {
			if ( ccpc_setting( 'auto_alt' ) ) {
				$alt        = sanitize_text_field( trim( implode( ', ', array_filter( array( $p['subject'] ?? '', $p['property'] ?? '', $p['place'] ?? '' ) ) ) ) );
				$warnings[] = 'alt generated from fields; review it.';
			} else {
				return new WP_Error( 'ccpc_alt_required', 'alt is required (describe what is actually in the photo).', array( 'status' => 400 ) );
			}
		}

		// Expose naming fields to the upload prefilter and add_attachment hook.
		foreach ( array( 'property', 'place', 'subject', 'n', 'credit', 'visited', 'license_note' ) as $k ) {
			if ( isset( $p[ $k ] ) ) {
				$_POST[ 'ccpc_' . $k ] = sanitize_text_field( $p[ $k ] );
			}
		}
		if ( empty( $_POST['ccpc_credit'] ) ) {
			$_POST['ccpc_credit'] = (string) ccpc_setting( 'default_credit' );
		}

		$files = $req->get_file_params();
		if ( ! empty( $files['file']['tmp_name'] ) ) {
			$attachment_id = media_handle_upload( 'file', 0, array(), array( 'test_form' => false ) );
		} elseif ( ! empty( $p['data_url'] ) ) {
			$decoded = self::decode_data_url( (string) $p['data_url'] );
			if ( is_wp_error( $decoded ) ) {
				return $decoded;
			}
			$tmp = wp_tempnam( 'ccpc' );
			file_put_contents( $tmp, $decoded['bytes'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$name = ! empty( $p['filename'] ) ? sanitize_file_name( $p['filename'] ) : 'upload.' . $decoded['ext'];
			if ( ! preg_match( '/\.' . $decoded['ext'] . '$/i', $name ) ) {
				$name .= '.' . $decoded['ext'];
			}
			$attachment_id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp, 'type' => $decoded['type'], 'size' => strlen( $decoded['bytes'] ) ), 0 );
		} else {
			return new WP_Error( 'ccpc_nofile', 'Send multipart "file" or JSON "data_url".', array( 'status' => 400 ) );
		}
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		delete_post_meta( $attachment_id, '_ccpc_alt_missing' );
		$update = array( 'ID' => $attachment_id );
		if ( ! empty( $p['caption'] ) ) {
			$update['post_excerpt'] = sanitize_text_field( $p['caption'] );
		}
		if ( ! empty( $p['title'] ) ) {
			$update['post_title'] = sanitize_text_field( $p['title'] );
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}

		// Optional placement.
		$post_id = ! empty( $p['post_id'] ) ? (int) $p['post_id'] : 0;
		if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
			if ( ! empty( $p['acf_field'] ) ) {
				$field = sanitize_key( $p['acf_field'] );
				if ( function_exists( 'update_field' ) ) {
					update_field( $field, $attachment_id, $post_id );
				} else {
					update_post_meta( $post_id, $field, $attachment_id );
					$warnings[] = 'ACF not active: wrote raw post meta ' . $field;
				}
			}
			if ( ! empty( $p['set_featured'] ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
			if ( ! empty( $p['insert_into_content'] ) ) {
				$post  = get_post( $post_id );
				$src   = wp_get_attachment_image_src( $attachment_id, 'large' );
				$block = "\n<!-- wp:image {\"id\":{$attachment_id},\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $src ? $src[0] : wp_get_attachment_url( $attachment_id ) ) . "\" alt=\"" . esc_attr( $alt ) . "\" class=\"wp-image-{$attachment_id}\"/>" . ( ! empty( $p['caption'] ) ? '<figcaption class="wp-element-caption">' . esc_html( sanitize_text_field( $p['caption'] ) ) . '</figcaption>' : '' ) . "</figure>\n<!-- /wp:image -->\n";
				wp_update_post( array( 'ID' => $post_id, 'post_content' => $post->post_content . $block ) );
			}
			wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post_id ) );
		} elseif ( $post_id ) {
			$warnings[] = 'post_id ignored: no permission.';
		}

		return rest_ensure_response( self::describe( $attachment_id, $warnings ) );
	}

	public static function rename( WP_REST_Request $req ) {
		$stem = sanitize_title( (string) $req->get_param( 'stem' ) );
		$r    = CCPC_Rename::rename( (int) $req['id'], $stem );
		if ( is_wp_error( $r ) ) {
			$r->add_data( array( 'status' => 400 ) );
			return $r;
		}
		return rest_ensure_response( self::describe( (int) $req['id'], array(), array( 'redirects' => $r ) ) );
	}

	/**
	 * POST /replace/{id}: mode=everywhere (file|data_url) or mode=here (post_id, location, new_id).
	 */
	public static function replace( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$mode = (string) $req->get_param( 'mode' );
		if ( 'here' === $mode ) {
			$r = CCPC_Replace::swap_usage( (int) $req->get_param( 'post_id' ), (string) $req->get_param( 'location' ), $id, (int) $req->get_param( 'new_id' ) );
			if ( is_wp_error( $r ) ) {
				$r->add_data( array( 'status' => 400 ) );
				return $r;
			}
			return rest_ensure_response( array( 'ok' => true ) );
		}
		$files = $req->get_file_params();
		$name  = '';
		if ( ! empty( $files['file']['tmp_name'] ) ) {
			$tmp  = $files['file']['tmp_name'];
			$name = (string) ( $files['file']['name'] ?? '' );
		} elseif ( $req->get_param( 'data_url' ) ) {
			$decoded = self::decode_data_url( (string) $req->get_param( 'data_url' ) );
			if ( is_wp_error( $decoded ) ) {
				return $decoded;
			}
			$base = wp_tempnam( 'ccpc' );
			$tmp  = $base . '.' . $decoded['ext'];
			file_put_contents( $tmp, $decoded['bytes'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			wp_delete_file( $base ); // wp_tempnam() created an empty placeholder we do not use.
		} else {
			return new WP_Error( 'ccpc_nofile', 'file or data_url required.', array( 'status' => 400 ) );
		}
		$r = CCPC_Replace::replace_everywhere( $id, $tmp, $name );
		if ( is_wp_error( $r ) ) {
			$r->add_data( array( 'status' => 400 ) );
			return $r;
		}
		return rest_ensure_response( self::describe( $id ) );
	}

	protected static function decode_data_url( $data_url ) {
		if ( ! preg_match( '#^data:(image/(jpeg|png|webp));base64,(.+)$#s', $data_url, $m ) ) {
			return new WP_Error( 'ccpc_dataurl', 'data_url must be a base64 image/jpeg, image/png or image/webp.', array( 'status' => 400 ) );
		}
		$bytes = base64_decode( $m[3], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( false === $bytes || strlen( $bytes ) < 100 ) {
			return new WP_Error( 'ccpc_dataurl', 'Invalid base64 payload.', array( 'status' => 400 ) );
		}
		$ext = 'jpeg' === $m[2] ? 'jpg' : $m[2];
		return array( 'bytes' => $bytes, 'type' => $m[1], 'ext' => $ext );
	}

	public static function describe( $id, $warnings = array(), $extra = array() ) {
		$url  = wp_get_attachment_url( $id );
		$webp = CCPC_Processor::variant_path( strtok( (string) $url, '?' ), 'webp' );
		$up   = wp_get_upload_dir();
		$webp_exists = file_exists( str_replace( $up['baseurl'], $up['basedir'], $webp ) );
		return array_merge( array(
			'id'       => (int) $id,
			'filename' => basename( (string) get_post_meta( $id, '_wp_attached_file', true ) ),
			'url'      => $url,
			'webp'     => $webp_exists ? $webp : null,
			'alt'      => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'credit'   => (string) get_post_meta( $id, '_ccpc_credit', true ),
			'usages'   => count( CCPC_Usages::find( $id ) ),
			'warnings' => $warnings,
		), $extra );
	}
}
