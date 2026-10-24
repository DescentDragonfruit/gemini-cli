<?php
/**
 * Replace an image everywhere (same attachment ID, new file) or in one place (swap the usage
 * to a different attachment).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Replace {

	public static function init() {
		add_action( 'add_meta_boxes_attachment', array( __CLASS__, 'meta_box' ) );
		add_action( 'admin_post_ccpc_replace_everywhere', array( __CLASS__, 'handle_everywhere' ) );
		add_action( 'wp_ajax_ccpc_swap_usage', array( __CLASS__, 'ajax_swap' ) );
	}

	public static function meta_box() {
		add_meta_box( 'ccpc-replace', __( 'Coco Photo CMS — Replace & usages', 'coco-photo-cms' ), array( __CLASS__, 'render_box' ), 'attachment', 'normal', 'high' );
	}

	public static function render_box( $post ) {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			echo '<p>' . esc_html__( 'Images only.', 'coco-photo-cms' ) . '</p>';
			return;
		}
		$usages = CCPC_Usages::find( $post->ID );
		?>
		<div id="ccpc-usages" class="ccpc-box">
			<h4><?php esc_html_e( 'Replace everywhere', 'coco-photo-cms' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Upload a new file. The attachment keeps its ID and filename, every size and WebP is regenerated, and a cache-buster is added so Cloudflare and browsers refetch it.', 'coco-photo-cms' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="ccpc-inline-form">
				<input type="hidden" name="action" value="ccpc_replace_everywhere">
				<input type="hidden" name="attachment_id" value="<?php echo (int) $post->ID; ?>">
				<?php wp_nonce_field( 'ccpc_replace_' . $post->ID ); ?>
				<input type="file" name="ccpc_file" accept="image/jpeg,image/png,image/webp" required>
				<button type="submit" class="button"><?php esc_html_e( 'Replace everywhere', 'coco-photo-cms' ); ?></button>
			</form>

			<h4><?php esc_html_e( 'Used in', 'coco-photo-cms' ); ?> (<?php echo count( $usages ); ?>)</h4>
			<?php if ( ! $usages ) : ?>
				<p class="description"><?php esc_html_e( 'Not used in any post yet.', 'coco-photo-cms' ); ?></p>
			<?php else : ?>
				<table class="widefat striped ccpc-usages-table">
					<thead><tr><th><?php esc_html_e( 'Post', 'coco-photo-cms' ); ?></th><th><?php esc_html_e( 'Type', 'coco-photo-cms' ); ?></th><th><?php esc_html_e( 'Where', 'coco-photo-cms' ); ?></th><th><?php esc_html_e( 'Replace only here', 'coco-photo-cms' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $usages as $u ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $u['edit_link'] ); ?>"><?php echo esc_html( $u['title'] ); ?></a></td>
							<td><?php echo esc_html( $u['post_type'] ); ?></td>
							<td><code><?php echo esc_html( $u['location'] ); ?></code></td>
							<td>
								<input type="number" min="1" class="small-text ccpc-swap-id" placeholder="<?php esc_attr_e( 'New image ID', 'coco-photo-cms' ); ?>">
								<button type="button" class="button button-small ccpc-swap-btn" data-old="<?php echo (int) $post->ID; ?>" data-post="<?php echo (int) $u['post_id']; ?>" data-location="<?php echo esc_attr( $u['location'] ); ?>"><?php esc_html_e( 'Swap', 'coco-photo-cms' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( '"Swap" changes that one post/field to the new image ID. Other posts are untouched. Within a single post content, every occurrence of the old image in that post is swapped.', 'coco-photo-cms' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Overwrite the attachment's file in place and regenerate everything.
	 *
	 * @param int    $attachment_id ID.
	 * @param string $tmp_path      Path to the new file (already validated image).
	 * @return true|WP_Error
	 */
	public static function replace_everywhere( $attachment_id, $tmp_path ) {
		$attachment_id = (int) $attachment_id;
		$current       = get_attached_file( $attachment_id );
		if ( ! $current || ! file_exists( $tmp_path ) ) {
			return new WP_Error( 'ccpc_missing', 'File not found.' );
		}
		$check = wp_check_filetype_and_ext( $tmp_path, basename( $tmp_path ) );
		if ( empty( $check['type'] ) || 0 !== strpos( $check['type'], 'image/' ) ) {
			return new WP_Error( 'ccpc_type', 'Not an image.' );
		}
		$cur_ext = strtolower( pathinfo( $current, PATHINFO_EXTENSION ) );
		$new_ext = strtolower( $check['ext'] );
		if ( $cur_ext !== $new_ext && ! ( in_array( $cur_ext, array( 'jpg', 'jpeg' ), true ) && in_array( $new_ext, array( 'jpg', 'jpeg' ), true ) ) ) {
			return new WP_Error( 'ccpc_ext', sprintf( 'The replacement must be a .%s like the original (so the URL stays the same).', $cur_ext ) );
		}

		// Remove old sizes/variants so no orphans remain.
		$meta = wp_get_attachment_metadata( $attachment_id );
		$dir  = dirname( $current );
		if ( ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $s ) {
				if ( ! empty( $s['file'] ) ) {
					$p = $dir . '/' . $s['file'];
					foreach ( array( $p, CCPC_Processor::variant_path( $p, 'webp' ), CCPC_Processor::variant_path( $p, 'avif' ) ) as $f ) {
						if ( file_exists( $f ) ) {
							wp_delete_file( $f );
						}
					}
				}
			}
		}
		foreach ( array( 'webp', 'avif' ) as $v ) {
			$f = CCPC_Processor::variant_path( $current, $v );
			if ( file_exists( $f ) ) {
				wp_delete_file( $f );
			}
		}
		$orig = $dir . '/originals/' . basename( $current );
		if ( file_exists( $orig ) ) {
			wp_delete_file( $orig );
		}

		if ( ! copy( $tmp_path, $current ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error( 'ccpc_copy', 'Could not write the new file.' );
		}
		wp_delete_file( $tmp_path );

		CCPC_Processor::process_file( $current );
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$new_meta = wp_generate_attachment_metadata( $attachment_id, $current );
		if ( is_array( $new_meta ) ) {
			wp_update_attachment_metadata( $attachment_id, $new_meta );
		}
		update_post_meta( $attachment_id, '_ccpc_version', time() );
		CCPC_Rename::purge_caches();
		return true;
	}

	/**
	 * Swap one usage of $old_id to $new_id.
	 *
	 * @return true|WP_Error
	 */
	public static function swap_usage( $post_id, $location, $old_id, $new_id ) {
		global $wpdb;
		$post_id = (int) $post_id;
		$old_id  = (int) $old_id;
		$new_id  = (int) $new_id;
		if ( ! get_post( $post_id ) || ! wp_attachment_is_image( $new_id ) || $old_id === $new_id ) {
			return new WP_Error( 'ccpc_args', 'Invalid post or image.' );
		}

		if ( 'thumbnail' === $location ) {
			set_post_thumbnail( $post_id, $new_id );
			clean_post_cache( $post_id );
			return true;
		}

		list( $kind, $key ) = array_pad( explode( ':', $location, 2 ), 2, '' );

		if ( 'content' === $kind ) {
			$post   = get_post( $post_id );
			$pairs  = self::url_pairs( $old_id, $new_id );
			$search = array( 'wp-image-' . $old_id, '"id":' . $old_id . ',', '"id":' . $old_id . '}' );
			$repl   = array( 'wp-image-' . $new_id, '"id":' . $new_id . ',', '"id":' . $new_id . '}' );
			foreach ( $pairs as $o => $n ) {
				$search[] = $o;
				$repl[]   = $n;
				$search[] = str_replace( '/', '\/', $o );
				$repl[]   = str_replace( '/', '\/', $n );
			}
			$new_content = str_replace( $search, $repl, $post->post_content );
			if ( $new_content !== $post->post_content ) {
				$wpdb->update( $wpdb->posts, array( 'post_content' => $new_content ), array( 'ID' => $post_id ) );
				clean_post_cache( $post_id );
			}
			return true;
		}

		if ( in_array( $kind, array( 'acf', 'meta', 'kadence' ), true ) && $key ) {
			$val = get_post_meta( $post_id, $key, true );
			if ( is_numeric( $val ) && (int) $val === $old_id ) {
				$new_val = $new_id;
			} elseif ( is_array( $val ) ) {
				$new_val = self::deep_swap( $val, $old_id, $new_id );
			} elseif ( is_string( $val ) ) {
				$pairs   = self::url_pairs( $old_id, $new_id );
				$new_val = str_replace( array_keys( $pairs ), array_values( $pairs ), $val );
			} else {
				return new WP_Error( 'ccpc_meta', 'Unrecognised field value.' );
			}
			if ( 'acf' === $kind && function_exists( 'update_field' ) ) {
				update_field( $key, $new_val, $post_id );
			} else {
				update_post_meta( $post_id, $key, $new_val );
			}
			clean_post_cache( $post_id );
			return true;
		}
		return new WP_Error( 'ccpc_location', 'Unknown location.' );
	}

	protected static function deep_swap( $arr, $old, $new ) {
		foreach ( $arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$arr[ $k ] = self::deep_swap( $v, $old, $new );
			} elseif ( is_numeric( $v ) && (int) $v === (int) $old ) {
				$arr[ $k ] = $new;
			}
		}
		return $arr;
	}

	/**
	 * Map old attachment URLs (full + sizes) to the new attachment's nearest equivalents.
	 */
	protected static function url_pairs( $old_id, $new_id ) {
		$pairs = array();
		$ofull = wp_get_attachment_url( $old_id );
		$nfull = wp_get_attachment_url( $new_id );
		if ( $ofull && $nfull ) {
			$pairs[ strtok( $ofull, '?' ) ] = strtok( $nfull, '?' );
		}
		$om = wp_get_attachment_metadata( $old_id );
		$nm = wp_get_attachment_metadata( $new_id );
		if ( ! empty( $om['sizes'] ) ) {
			$obase = dirname( $ofull );
			$nbase = dirname( $nfull );
			foreach ( $om['sizes'] as $size => $s ) {
				if ( empty( $s['file'] ) ) {
					continue;
				}
				$target = ! empty( $nm['sizes'][ $size ]['file'] ) ? $nbase . '/' . $nm['sizes'][ $size ]['file'] : strtok( $nfull, '?' );
				$pairs[ $obase . '/' . $s['file'] ] = $target;
			}
		}
		return $pairs;
	}

	/* ---------- Admin handlers ---------- */

	public static function handle_everywhere() {
		$id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'coco-photo-cms' ) );
		}
		check_admin_referer( 'ccpc_replace_' . $id );
		if ( empty( $_FILES['ccpc_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'No file.', 'coco-photo-cms' ) );
		}
		$tmp = $_FILES['ccpc_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$r   = self::replace_everywhere( $id, $tmp );
		if ( is_wp_error( $r ) ) {
			wp_die( esc_html( $r->get_error_message() ) );
		}
		wp_safe_redirect( add_query_arg( 'ccpc_replaced', 1, get_edit_post_link( $id, 'raw' ) ) );
		exit;
	}

	public static function ajax_swap() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$r = self::swap_usage(
			$post_id,
			isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '',
			isset( $_POST['old_id'] ) ? (int) $_POST['old_id'] : 0,
			isset( $_POST['new_id'] ) ? (int) $_POST['new_id'] : 0
		);
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		CCPC_Rename::purge_caches();
		wp_send_json_success();
	}
}
