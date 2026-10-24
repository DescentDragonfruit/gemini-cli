<?php
/**
 * Find where an attachment is used: featured image, post content, ACF fields, other meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Usages {

	public static function init() {
		add_filter( 'manage_media_columns', array( __CLASS__, 'column' ) );
		add_action( 'manage_media_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'wp_ajax_ccpc_usages', array( __CLASS__, 'ajax_usages' ) );
	}

	/**
	 * All file basenames (without extension) that belong to an attachment: the main file and
	 * every size. Used for LIKE searches in content/meta.
	 *
	 * @param int $attachment_id ID.
	 * @return string[] Basenames without extension, unique.
	 */
	public static function file_stems( $attachment_id ) {
		$stems = array();
		$file  = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( $file ) {
			$stems[] = pathinfo( $file, PATHINFO_FILENAME );
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					$stems[] = pathinfo( $size['file'], PATHINFO_FILENAME );
				}
			}
		}
		if ( ! empty( $meta['original_image'] ) ) {
			$stems[] = pathinfo( $meta['original_image'], PATHINFO_FILENAME );
		}
		return array_values( array_unique( array_filter( $stems ) ) );
	}

	/**
	 * Find usages.
	 *
	 * @param int $attachment_id ID.
	 * @return array[]
	 */
	public static function find( $attachment_id ) {
		global $wpdb;
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return array();
		}

		$found = array();
		$add   = function ( $post_id, $location ) use ( &$found, $attachment_id ) {
			$post_id = (int) $post_id;
			if ( $post_id === $attachment_id ) {
				return;
			}
			$post = get_post( $post_id );
			if ( ! $post || 'revision' === $post->post_type || 'attachment' === $post->post_type ) {
				return;
			}
			$key = $post_id . '|' . $location;
			if ( isset( $found[ $key ] ) ) {
				return;
			}
			$found[ $key ] = array(
				'post_id'   => $post_id,
				'post_type' => $post->post_type,
				'location'  => $location,
				'title'     => get_the_title( $post ) ? get_the_title( $post ) : '(no title)',
				'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			);
		};

		// 1. Featured image.
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s",
			(string) $attachment_id
		) );
		foreach ( $ids as $pid ) {
			$add( $pid, 'thumbnail' );
		}

		// 2. Post content: wp-image-<id>, "id":<id>, and the file stem.
		$stem_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$main_stem = $stem_file ? pathinfo( $stem_file, PATHINFO_FILENAME ) : '';
		$where     = array(
			$wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( 'wp-image-' . $attachment_id ) . '%' ),
			$wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( '"id":' . $attachment_id . ',' ) . '%' ),
			$wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( '"id":' . $attachment_id . '}' ) . '%' ),
		);
		if ( $main_stem ) {
			$where[] = $wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( $main_stem ) . '%' );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- each clause prepared above.
		$rows = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment') AND post_status <> 'auto-draft' AND (" . implode( ' OR ', $where ) . ')' );
		foreach ( $rows as $pid ) {
			$add( $pid, 'content' );
		}

		// 3. Post meta: exact numeric ID, serialized ID, or file stem in value.
		$meta_where = array(
			$wpdb->prepare( 'meta_value = %s', (string) $attachment_id ),
			$wpdb->prepare( 'meta_value LIKE %s', '%' . $wpdb->esc_like( 'i:' . $attachment_id . ';' ) . '%' ),
			$wpdb->prepare( 'meta_value LIKE %s', '%' . $wpdb->esc_like( '"' . $attachment_id . '"' ) . '%' ),
		);
		if ( $main_stem ) {
			$meta_where[] = $wpdb->prepare( 'meta_value LIKE %s', '%' . $wpdb->esc_like( $main_stem ) . '%' );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key NOT IN ('_thumbnail_id','_wp_attached_file','_wp_attachment_metadata') AND meta_key NOT LIKE '\\_ccpc\\_%' AND (" . implode( ' OR ', $meta_where ) . ')' );
		foreach ( $meta_rows as $row ) {
			if ( '_' === substr( $row->meta_key, 0, 1 ) ) {
				continue; // hidden/system meta (ACF field refs, edit locks …).
			}
			// Guard against a serialized match on a different ID, e.g. "123" inside "1234".
			if ( ! self::meta_value_references( $row->meta_value, $attachment_id, $main_stem ) ) {
				continue;
			}
			$loc = 'meta:' . $row->meta_key;
			if ( self::is_acf_field( $row->post_id, $row->meta_key ) ) {
				$loc = 'acf:' . $row->meta_key;
			} elseif ( 0 === strpos( $row->meta_key, '_kad' ) || 0 === strpos( $row->meta_key, 'kadence' ) ) {
				$loc = 'kadence:' . $row->meta_key;
			}
			$add( $row->post_id, $loc );
		}

		return array_values( $found );
	}

	/**
	 * Does an ACF field reference sit beside this meta key?
	 */
	protected static function is_acf_field( $post_id, $meta_key ) {
		$ref = get_post_meta( $post_id, '_' . $meta_key, true );
		return is_string( $ref ) && 0 === strpos( $ref, 'field_' );
	}

	/**
	 * True if a meta value genuinely references the attachment.
	 */
	protected static function meta_value_references( $value, $attachment_id, $stem ) {
		if ( (string) $attachment_id === (string) $value ) {
			return true;
		}
		if ( $stem && false !== strpos( (string) $value, $stem ) ) {
			return true;
		}
		$data = maybe_unserialize( $value );
		if ( is_array( $data ) ) {
			return self::array_has_id( $data, $attachment_id );
		}
		return false;
	}

	protected static function array_has_id( $arr, $id ) {
		foreach ( $arr as $v ) {
			if ( is_array( $v ) ) {
				if ( self::array_has_id( $v, $id ) ) {
					return true;
				}
			} elseif ( is_numeric( $v ) && (int) $v === (int) $id ) {
				return true;
			}
		}
		return false;
	}

	/* ---------- Media list column ---------- */

	public static function column( $cols ) {
		$cols['ccpc_usages'] = __( 'Used in', 'coco-photo-cms' );
		return $cols;
	}

	public static function column_content( $col, $id ) {
		if ( 'ccpc_usages' !== $col ) {
			return;
		}
		if ( ! wp_attachment_is_image( $id ) ) {
			echo '—';
			return;
		}
		$count = count( self::find( $id ) );
		printf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $id ) . '#ccpc-usages' ),
			esc_html( sprintf( _n( '%d place', '%d places', $count, 'coco-photo-cms' ), $count ) )
		);
	}

	public static function ajax_usages() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		wp_send_json_success( self::find( $id ) );
	}
}
