<?php
/**
 * Settings → Coco Photo CMS (General + Bulk tabs) and the AJAX bulk handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_ajax_ccpc_bulk_webp', array( __CLASS__, 'ajax_bulk_webp' ) );
		add_action( 'wp_ajax_ccpc_missing_alt', array( __CLASS__, 'ajax_missing_alt' ) );
	}

	public static function menu() {
		add_options_page( 'Coco Photo CMS', 'Coco Photo CMS', 'manage_options', 'coco-photo-cms', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting( 'ccpc', CCPC_OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function sanitize( $in ) {
		$d   = ccpc_default_settings();
		$out = array();
		$in  = is_array( $in ) ? $in : array();
		$out['max_long_edge']     = max( 400, min( 6000, (int) ( $in['max_long_edge'] ?? $d['max_long_edge'] ) ) );
		$out['webp_quality']      = max( 40, min( 100, (int) ( $in['webp_quality'] ?? $d['webp_quality'] ) ) );
		$out['avif_quality']      = max( 30, min( 100, (int) ( $in['avif_quality'] ?? $d['avif_quality'] ) ) );
		$out['schema_max_images'] = max( 1, min( 30, (int) ( $in['schema_max_images'] ?? $d['schema_max_images'] ) ) );
		foreach ( array( 'make_avif', 'keep_originals', 'serve_webp', 'auto_rename', 'auto_alt', 'show_credit', 'schema_enabled' ) as $b ) {
			$out[ $b ] = empty( $in[ $b ] ) ? 0 : 1;
		}
		$tpl = sanitize_text_field( $in['filename_template'] ?? $d['filename_template'] );
		$out['filename_template'] = '' === trim( $tpl ) ? $d['filename_template'] : $tpl;
		$out['default_credit']    = sanitize_text_field( $in['default_credit'] ?? $d['default_credit'] );
		$out['copyright_notice']  = sanitize_text_field( $in['copyright_notice'] ?? $d['copyright_notice'] );
		$out['license_url']       = esc_url_raw( $in['license_url'] ?? $d['license_url'] );
		$origins = array();
		foreach ( preg_split( '/\s+/', (string) ( $in['cors_origins'] ?? '' ) ) as $o ) {
			$o = trim( $o );
			if ( $o && preg_match( '#^https?://[A-Za-z0-9*.\-]+(:\d+)?$#', $o ) ) {
				$origins[] = $o;
			}
		}
		$out['cors_origins'] = implode( "\n", $origins );
		return $out;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s   = ccpc_get_settings();
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		$f   = function ( $k ) { return CCPC_OPTION . '[' . $k . ']'; };
		?>
		<div class="wrap ccpc-settings">
			<h1>Coco Photo CMS</h1>
			<nav class="nav-tab-wrapper">
				<a class="nav-tab <?php echo 'general' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=general"><?php esc_html_e( 'General', 'coco-photo-cms' ); ?></a>
				<a class="nav-tab <?php echo 'bulk' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=bulk"><?php esc_html_e( 'Bulk', 'coco-photo-cms' ); ?></a>
				<a class="nav-tab <?php echo 'redirects' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=redirects"><?php esc_html_e( 'Redirect rules', 'coco-photo-cms' ); ?></a>
			</nav>

			<?php if ( 'general' === $tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'ccpc' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Max long edge (px)', 'coco-photo-cms' ); ?></th><td><input type="number" name="<?php echo esc_attr( $f( 'max_long_edge' ) ); ?>" value="<?php echo (int) $s['max_long_edge']; ?>" min="400" max="6000"> <p class="description"><?php esc_html_e( 'Originals larger than this are resized on upload. 2000 is plenty for a 1,200px content column at 2× density.', 'coco-photo-cms' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'WebP quality', 'coco-photo-cms' ); ?></th><td><input type="number" name="<?php echo esc_attr( $f( 'webp_quality' ) ); ?>" value="<?php echo (int) $s['webp_quality']; ?>" min="40" max="100"></td></tr>
					<tr><th><?php esc_html_e( 'AVIF', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'make_avif' ) ); ?>" value="1" <?php checked( $s['make_avif'] ); ?> <?php disabled( ! function_exists( 'imageavif' ) ); ?>> <?php esc_html_e( 'Also generate AVIF', 'coco-photo-cms' ); ?></label> <?php echo function_exists( 'imageavif' ) ? '' : '<em>(' . esc_html__( 'not available on this server', 'coco-photo-cms' ) . ')</em>'; ?> &nbsp; <?php esc_html_e( 'quality', 'coco-photo-cms' ); ?> <input type="number" name="<?php echo esc_attr( $f( 'avif_quality' ) ); ?>" value="<?php echo (int) $s['avif_quality']; ?>" min="30" max="100" class="small-text"></td></tr>
					<tr><th><?php esc_html_e( 'Keep originals', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'keep_originals' ) ); ?>" value="1" <?php checked( $s['keep_originals'] ); ?>> <?php esc_html_e( 'Copy the untouched upload to an /originals/ folder beside it', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Serve WebP/AVIF', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'serve_webp' ) ); ?>" value="1" <?php checked( $s['serve_webp'] ); ?>> <?php esc_html_e( 'Swap image URLs for the WebP/AVIF sibling when the browser accepts it', 'coco-photo-cms' ); ?></label><p class="description"><?php esc_html_e( 'Turn OFF LiteSpeed Cache → Image Optimization → "Image WebP Replacement" if this is on, or they fight. Full-page caches must vary on Accept (LiteSpeed does when its own WebP option is on; otherwise serve WebP via LiteSpeed instead and leave this off).', 'coco-photo-cms' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Filename template', 'coco-photo-cms' ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $f( 'filename_template' ) ); ?>" value="<?php echo esc_attr( $s['filename_template'] ); ?>"><p class="description"><?php esc_html_e( 'Tokens: {property} {place} {subject} {n}. Built from the Coco Photo fields on each image.', 'coco-photo-cms' ); ?></p>
						<label><input type="checkbox" name="<?php echo esc_attr( $f( 'auto_rename' ) ); ?>" value="1" <?php checked( $s['auto_rename'] ); ?>> <?php esc_html_e( 'Rename the file automatically when the fields are saved', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Default credit', 'coco-photo-cms' ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $f( 'default_credit' ) ); ?>" value="<?php echo esc_attr( $s['default_credit'] ); ?>"> <label><input type="checkbox" name="<?php echo esc_attr( $f( 'show_credit' ) ); ?>" value="1" <?php checked( $s['show_credit'] ); ?>> <?php esc_html_e( 'Append the credit to captions', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Copyright notice', 'coco-photo-cms' ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $f( 'copyright_notice' ) ); ?>" value="<?php echo esc_attr( $s['copyright_notice'] ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'License / usage page URL', 'coco-photo-cms' ); ?></th><td><input type="url" class="regular-text" name="<?php echo esc_attr( $f( 'license_url' ) ); ?>" value="<?php echo esc_attr( $s['license_url'] ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Alt text', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'auto_alt' ) ); ?>" value="1" <?php checked( $s['auto_alt'] ); ?>> <?php esc_html_e( 'If alt is left empty, build it from Detail, Property, Place (off by default: write real alt text)', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'ImageObject schema', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'schema_enabled' ) ); ?>" value="1" <?php checked( $s['schema_enabled'] ); ?>> <?php esc_html_e( 'Emit JSON-LD for the featured image and the first', 'coco-photo-cms' ); ?></label> <input type="number" class="small-text" name="<?php echo esc_attr( $f( 'schema_max_images' ) ); ?>" value="<?php echo (int) $s['schema_max_images']; ?>" min="1" max="30"> <?php esc_html_e( 'images on each post', 'coco-photo-cms' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'REST intake — allowed origins', 'coco-photo-cms' ); ?></th><td><textarea name="<?php echo esc_attr( $f( 'cors_origins' ) ); ?>" rows="3" class="regular-text"><?php echo esc_textarea( $s['cors_origins'] ); ?></textarea><p class="description"><?php esc_html_e( 'One per line. *.domain allows subdomains. Endpoint: /wp-json/coco-photo-cms/v1/upload with an Application Password.', 'coco-photo-cms' ); ?></p></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<?php elseif ( 'bulk' === $tab ) : ?>
			<h2><?php esc_html_e( 'Generate WebP for images that don\'t have one', 'coco-photo-cms' ); ?></h2>
			<p><button class="button button-primary" id="ccpc-bulk-webp"><?php esc_html_e( 'Start', 'coco-photo-cms' ); ?></button> <span id="ccpc-bulk-status"></span></p>
			<p class="description"><?php esc_html_e( 'Runs 10 images per request until done. Safe to re-run.', 'coco-photo-cms' ); ?></p>
			<h2><?php esc_html_e( 'Images with empty alt text', 'coco-photo-cms' ); ?></h2>
			<p><button class="button" id="ccpc-missing-alt"><?php esc_html_e( 'Find', 'coco-photo-cms' ); ?></button></p>
			<div id="ccpc-missing-alt-list"></div>

			<?php else : ?>
			<h2><?php esc_html_e( 'Redirect rules', 'coco-photo-cms' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Static files are served before WordPress runs, so an old image URL only reaches the plugin\'s redirect if the file is gone and the server passes the 404 to PHP. For a guaranteed 301, paste these into .htaccess (Apache/LiteSpeed) or Cloudflare → Bulk Redirects.', 'coco-photo-cms' ); ?></p>
			<textarea readonly rows="16" class="large-text code"><?php echo esc_textarea( CCPC_Rename::rules_text() ); ?></textarea>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------- Bulk AJAX ---------- */

	public static function ajax_bulk_webp() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$ids    = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 10,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );
		$made = 0;
		foreach ( $ids as $id ) {
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}
			if ( file_exists( CCPC_Processor::variant_path( $file, 'webp' ) ) ) {
				continue;
			}
			$v = CCPC_Processor::generate_variants( $id );
			if ( $v ) {
				$meta = wp_get_attachment_metadata( $id );
				if ( is_array( $meta ) ) {
					$meta['ccpc_variants'] = $v;
					wp_update_attachment_metadata( $id, $meta );
				}
				$made++;
			}
		}
		wp_send_json_success( array( 'processed' => count( $ids ), 'made' => $made, 'next' => count( $ids ) < 10 ? null : $offset + 10 ) );
	}

	public static function ajax_missing_alt() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attachment_image_alt'
			 WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%'
			   AND (m.meta_value IS NULL OR TRIM(m.meta_value) = '')
			 ORDER BY p.ID DESC LIMIT 500"
		);
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array( 'id' => (int) $r->ID, 'title' => $r->post_title, 'edit' => get_edit_post_link( $r->ID, 'raw' ), 'usages' => count( CCPC_Usages::find( $r->ID ) ) );
		}
		wp_send_json_success( $out );
	}
}
