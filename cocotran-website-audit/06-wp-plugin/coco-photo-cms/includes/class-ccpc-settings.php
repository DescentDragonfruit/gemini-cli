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

	/**
	 * Keys owned by each settings tab. A tab's form posts only its own fields, so the other tab's
	 * values are carried over from the saved option (unchecked checkboxes are otherwise lost).
	 */
	const TAB_KEYS = array(
		'exports' => array( 'export_jpeg_quality', 'wm_text', 'wm_position', 'wm_opacity', 'wm_size', 'wm_font', 'wm_default', 'delete_guard', 'speed_decoding', 'speed_lazy', 'speed_lazy_skip', 'speed_fetchpriority', 'speed_preload', 'speed_sizes', 'auto_crops_on_publish', 'auto_crops_refresh', 'auto_crops_max' ),
	);

	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? $in : array();
		$tab = isset( $in['_tab'] ) ? sanitize_key( $in['_tab'] ) : 'general';
		$cur = ccpc_get_settings();
		if ( 'exports' === $tab ) {
			$out = self::sanitize_exports( $in, $cur );
			return array_merge( $cur, $out );
		}
		$out = self::sanitize_general( $in );
		foreach ( self::TAB_KEYS['exports'] as $k ) {
			$out[ $k ] = $cur[ $k ];
		}
		return $out;
	}

	protected static function sanitize_exports( $in, $cur ) {
		$d                          = ccpc_default_settings();
		$out                        = array();
		$out['export_jpeg_quality'] = max( 60, min( 100, (int) ( $in['export_jpeg_quality'] ?? $cur['export_jpeg_quality'] ) ) );
		$out['wm_text']             = sanitize_text_field( $in['wm_text'] ?? $cur['wm_text'] );
		$out['wm_position']         = ( isset( $in['wm_position'] ) && 'bl' === $in['wm_position'] ) ? 'bl' : 'br';
		$out['wm_opacity']          = max( 0, min( 100, (int) ( $in['wm_opacity'] ?? $cur['wm_opacity'] ) ) );
		$out['wm_size']             = max( 1, min( 8, round( (float) ( $in['wm_size'] ?? $cur['wm_size'] ), 1 ) ) );
		$font                       = isset( $in['wm_font'] ) ? trim( sanitize_text_field( $in['wm_font'] ) ) : (string) $cur['wm_font'];
		if ( '' !== $font && ! ( preg_match( '/\.(ttf|otf)$/i', $font ) && is_readable( $font ) ) ) {
			add_settings_error( 'ccpc', 'ccpc_font', __( 'Watermark font path is not a readable .ttf/.otf; cleared (GD bitmap font will be used).', 'coco-photo-cms' ) );
			$font = '';
		}
		$out['wm_font']         = $font;
		$out['speed_lazy_skip'] = max( 0, min( 10, (int) ( $in['speed_lazy_skip'] ?? $d['speed_lazy_skip'] ) ) );
		$out['auto_crops_max']  = max( 0, min( 30, (int) ( $in['auto_crops_max'] ?? $d['auto_crops_max'] ) ) );
		foreach ( array( 'wm_default', 'delete_guard', 'speed_decoding', 'speed_lazy', 'speed_fetchpriority', 'speed_preload', 'speed_sizes', 'auto_crops_on_publish', 'auto_crops_refresh' ) as $b ) {
			$out[ $b ] = empty( $in[ $b ] ) ? 0 : 1;
		}
		return $out;
	}

	protected static function sanitize_general( $in ) {
		$d   = ccpc_default_settings();
		$out = array();
		$out['max_long_edge']     = max( 400, min( 6000, (int) ( $in['max_long_edge'] ?? $d['max_long_edge'] ) ) );
		$out['webp_quality']      = max( 40, min( 100, (int) ( $in['webp_quality'] ?? $d['webp_quality'] ) ) );
		$presets                  = self::webp_presets();
		$out['webp_preset']       = isset( $in['webp_preset'], $presets[ $in['webp_preset'] ] ) ? $in['webp_preset'] : 'custom';
		if ( 'custom' !== $out['webp_preset'] ) {
			$out['webp_quality'] = (int) $presets[ $out['webp_preset'] ][0];
		}
		$out['avif_quality']      = max( 30, min( 100, (int) ( $in['avif_quality'] ?? $d['avif_quality'] ) ) );
		$out['schema_max_images'] = max( 1, min( 30, (int) ( $in['schema_max_images'] ?? $d['schema_max_images'] ) ) );
		$out['front_cap']         = max( 0, min( 4000, (int) ( $in['front_cap'] ?? $d['front_cap'] ) ) );
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

	/**
	 * WebP quality presets: key => [ quality, label ].
	 */
	public static function webp_presets() {
		return array(
			'small'    => array( 72, __( 'Smallest file (quality 72)', 'coco-photo-cms' ) ),
			'balanced' => array( 82, __( 'Balanced (quality 82)', 'coco-photo-cms' ) ),
			'quality'  => array( 88, __( 'Quality (quality 88)', 'coco-photo-cms' ) ),
			'custom'   => array( 0, __( 'Custom (use the number)', 'coco-photo-cms' ) ),
		);
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
				<a class="nav-tab <?php echo 'exports' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=exports"><?php esc_html_e( 'Exports & speed', 'coco-photo-cms' ); ?></a>
				<a class="nav-tab <?php echo 'bulk' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=bulk"><?php esc_html_e( 'Bulk', 'coco-photo-cms' ); ?></a>
				<a class="nav-tab <?php echo 'redirects' === $tab ? 'nav-tab-active' : ''; ?>" href="?page=coco-photo-cms&tab=redirects"><?php esc_html_e( 'Redirect rules', 'coco-photo-cms' ); ?></a>
			</nav>

			<?php if ( 'general' === $tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'ccpc' ); ?>
				<input type="hidden" name="<?php echo esc_attr( $f( '_tab' ) ); ?>" value="general">
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Max long edge (px)', 'coco-photo-cms' ); ?></th><td><input type="number" name="<?php echo esc_attr( $f( 'max_long_edge' ) ); ?>" value="<?php echo (int) $s['max_long_edge']; ?>" min="400" max="6000"> <p class="description"><?php esc_html_e( 'Originals larger than this are resized on upload. 2000 is plenty for a 1,200px content column at 2× density.', 'coco-photo-cms' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'WebP quality', 'coco-photo-cms' ); ?></th><td>
						<select name="<?php echo esc_attr( $f( 'webp_preset' ) ); ?>" id="ccpc-webp-preset">
							<?php foreach ( self::webp_presets() as $k => $pr ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" data-q="<?php echo (int) $pr[0]; ?>" <?php selected( $s['webp_preset'], $k ); ?>><?php echo esc_html( $pr[1] ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="number" id="ccpc-webp-quality" name="<?php echo esc_attr( $f( 'webp_quality' ) ); ?>" value="<?php echo (int) $s['webp_quality']; ?>" min="40" max="100" class="small-text">
						<p class="description"><?php esc_html_e( 'Target for every WebP the plugin writes (uploads, bulk optimise, social exports). Pick a preset or choose Custom and type a number. Re-run "Optimise all images" to apply a new quality to existing files.', 'coco-photo-cms' ); ?></p>
					</td></tr>
					<tr><th><?php esc_html_e( 'AVIF', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'make_avif' ) ); ?>" value="1" <?php checked( $s['make_avif'] ); ?> <?php disabled( ! function_exists( 'imageavif' ) ); ?>> <?php esc_html_e( 'Also generate AVIF', 'coco-photo-cms' ); ?></label> <?php echo function_exists( 'imageavif' ) ? '' : '<em>(' . esc_html__( 'not available on this server', 'coco-photo-cms' ) . ')</em>'; ?> &nbsp; <?php esc_html_e( 'quality', 'coco-photo-cms' ); ?> <input type="number" name="<?php echo esc_attr( $f( 'avif_quality' ) ); ?>" value="<?php echo (int) $s['avif_quality']; ?>" min="30" max="100" class="small-text"></td></tr>
					<tr><th><?php esc_html_e( 'Keep originals', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'keep_originals' ) ); ?>" value="1" <?php checked( $s['keep_originals'] ); ?>> <?php esc_html_e( 'Copy the untouched upload to an /originals/ folder beside it', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Front-end size cap (px)', 'coco-photo-cms' ); ?></th><td><input type="number" name="<?php echo esc_attr( $f( 'front_cap' ) ); ?>" value="<?php echo (int) $s['front_cap']; ?>" min="0" max="4000"> <p class="description"><?php esc_html_e( 'When a template asks for the full-size image (sliders, galleries, hero blocks), serve the largest generated size at or under this width instead of the original. 0 turns it off. Fixes the multi-megabyte homepage without touching the theme; originals stay for downloads and the media library.', 'coco-photo-cms' ); ?></p></td></tr>
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

			<?php elseif ( 'exports' === $tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'ccpc' ); ?>
				<input type="hidden" name="<?php echo esc_attr( $f( '_tab' ) ); ?>" value="exports">
				<h2><?php esc_html_e( 'Social exports', 'coco-photo-cms' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Crops are cut around each image\'s focal point (attachment screen → click the preview). Sizes: Social 4:5 1080×1350, Story 9:16 1080×1920, Pin 2:3 1000×1500, Open Graph 1200×630. WebP uses the WebP quality above.', 'coco-photo-cms' ); ?>
					<?php echo '' === CCPC_Image::engine() ? '<strong>' . esc_html__( 'No image library (GD/Imagick) detected: exports are disabled.', 'coco-photo-cms' ) . '</strong>' : esc_html( sprintf( __( 'Engine: %s.', 'coco-photo-cms' ), strtoupper( CCPC_Image::engine() ) ) ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Export JPEG quality', 'coco-photo-cms' ); ?></th><td><input type="number" name="<?php echo esc_attr( $f( 'export_jpeg_quality' ) ); ?>" value="<?php echo (int) $s['export_jpeg_quality']; ?>" min="60" max="100" class="small-text"></td></tr>
					<tr><th><?php esc_html_e( 'Credit watermark', 'coco-photo-cms' ); ?></th><td>
						<p class="description"><?php esc_html_e( 'Burned into social/Pin/OG exports only, and only when the export is requested with the watermark option. Never applied to images served on the site.', 'coco-photo-cms' ); ?></p>
						<p><label><?php esc_html_e( 'Text', 'coco-photo-cms' ); ?> <input type="text" class="regular-text" name="<?php echo esc_attr( $f( 'wm_text' ) ); ?>" value="<?php echo esc_attr( $s['wm_text'] ); ?>"></label></p>
						<p><label><?php esc_html_e( 'Position', 'coco-photo-cms' ); ?> <select name="<?php echo esc_attr( $f( 'wm_position' ) ); ?>"><option value="br" <?php selected( $s['wm_position'], 'br' ); ?>><?php esc_html_e( 'Bottom right', 'coco-photo-cms' ); ?></option><option value="bl" <?php selected( $s['wm_position'], 'bl' ); ?>><?php esc_html_e( 'Bottom left', 'coco-photo-cms' ); ?></option></select></label>
						&nbsp; <label><?php esc_html_e( 'Opacity %', 'coco-photo-cms' ); ?> <input type="number" class="small-text" name="<?php echo esc_attr( $f( 'wm_opacity' ) ); ?>" value="<?php echo (int) $s['wm_opacity']; ?>" min="0" max="100"></label>
						&nbsp; <label><?php esc_html_e( 'Font size (% of width)', 'coco-photo-cms' ); ?> <input type="number" class="small-text" step="0.1" name="<?php echo esc_attr( $f( 'wm_size' ) ); ?>" value="<?php echo esc_attr( $s['wm_size'] ); ?>" min="1" max="8"></label></p>
						<p><label><?php esc_html_e( 'TTF font path', 'coco-photo-cms' ); ?> <input type="text" class="large-text code" name="<?php echo esc_attr( $f( 'wm_font' ) ); ?>" value="<?php echo esc_attr( $s['wm_font'] ); ?>" placeholder="<?php echo esc_attr( get_stylesheet_directory() . '/fonts/your-font.ttf' ); ?>"></label>
						<span class="description"><?php esc_html_e( 'Absolute path to a .ttf/.otf on the server. Default: the first font found in the theme\'s /fonts/ folder. Blank = GD\'s built-in bitmap font (plain but always works).', 'coco-photo-cms' ); ?> <?php echo function_exists( 'imagettftext' ) ? '' : '<strong>' . esc_html__( 'FreeType is not available: the bitmap font will be used.', 'coco-photo-cms' ) . '</strong>'; ?></span></p>
						<p><label><input type="checkbox" name="<?php echo esc_attr( $f( 'wm_default' ) ); ?>" value="1" <?php checked( $s['wm_default'] ); ?>> <?php esc_html_e( 'Tick the watermark box by default on the attachment screen', 'coco-photo-cms' ); ?></label></p>
					</td></tr>
				</table>

				<h2><?php esc_html_e( 'Auto crops on publish', 'coco-photo-cms' ); ?></h2>
				<p class="description"><?php esc_html_e( 'When a post, page, property, guide, brief, dispatch or shortlist is published, the four social crops are generated for its featured image and the images in its fields (ACF image/gallery fields, Kadence galleries), around each image\'s focal point (centre when none is set). Runs as a single WP-Cron event 10 seconds after publish (at the end of the request when DISABLE_WP_CRON is set). Crops that already exist are never regenerated. The Studio Desk reads them from /wp-json/coco-photo-cms/v1/post-crops/{id} or /crops-for-url?url=… and hands the URLs to Canva.', 'coco-photo-cms' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'On publish', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'auto_crops_on_publish' ) ); ?>" value="1" <?php checked( $s['auto_crops_on_publish'] ); ?>> <?php esc_html_e( 'Generate the social crops automatically when a post is published', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Images per post', 'coco-photo-cms' ); ?></th><td><?php esc_html_e( 'The featured image plus the first', 'coco-photo-cms' ); ?> <input type="number" class="small-text" name="<?php echo esc_attr( $f( 'auto_crops_max' ) ); ?>" value="<?php echo (int) $s['auto_crops_max']; ?>" min="0" max="30"> <?php esc_html_e( 'other images (fields first, then content)', 'coco-photo-cms' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'On update', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'auto_crops_refresh' ) ); ?>" value="1" <?php checked( $s['auto_crops_refresh'] ); ?>> <?php esc_html_e( 'Also run when an already-published post is saved (fills crops for images added later; nothing is regenerated)', 'coco-photo-cms' ); ?></label></td></tr>
					<?php $log = CCPC_AutoCrops::get_log(); if ( $log ) : ?>
					<tr><th><?php esc_html_e( 'Last runs', 'coco-photo-cms' ); ?></th><td><textarea readonly rows="6" class="large-text code"><?php echo esc_textarea( implode( "\n", array_slice( $log, 0, 20 ) ) ); ?></textarea></td></tr>
					<?php endif; ?>
				</table>

				<h2><?php esc_html_e( 'Delete guard', 'coco-photo-cms' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'In-use guard', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'delete_guard' ) ); ?>" value="1" <?php checked( $s['delete_guard'] ); ?>> <?php esc_html_e( 'Block "Delete permanently" for images still used in posts, fields or featured images (shows where, with a "Delete anyway" link). REST deletes return 409 with the usages.', 'coco-photo-cms' ); ?></label></td></tr>
				</table>

				<h2><?php esc_html_e( 'Speed set (front end)', 'coco-photo-cms' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'decoding="async"', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'speed_decoding' ) ); ?>" value="1" <?php checked( $s['speed_decoding'] ); ?>> <?php esc_html_e( 'On every image', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'loading="lazy"', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'speed_lazy' ) ); ?>" value="1" <?php checked( $s['speed_lazy'] ); ?>> <?php esc_html_e( 'On every image except the first', 'coco-photo-cms' ); ?></label> <input type="number" class="small-text" name="<?php echo esc_attr( $f( 'speed_lazy_skip' ) ); ?>" value="<?php echo (int) $s['speed_lazy_skip']; ?>" min="0" max="10"> <?php esc_html_e( 'in the main content, any image with class ct-hero, and the first slide of the first Kadence slider', 'coco-photo-cms' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'fetchpriority="high"', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'speed_fetchpriority' ) ); ?>" value="1" <?php checked( $s['speed_fetchpriority'] ); ?>> <?php esc_html_e( 'On the featured image (or the first content image) of singular pages; removed from everything else', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Preload LCP image', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'speed_preload' ) ); ?>" value="1" <?php checked( $s['speed_preload'] ); ?>> <?php esc_html_e( '<link rel="preload" as="image"> in <head> for that image, at the capped size (WebP when accepted)', 'coco-photo-cms' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'sizes sanity', 'coco-photo-cms' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $f( 'speed_sizes' ) ); ?>" value="1" <?php checked( $s['speed_sizes'] ); ?>> <?php esc_html_e( 'Rewrite sizes="(max-width: Npx) 100vw, Npx" to the front-end cap when N is larger', 'coco-photo-cms' ); ?></label></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<?php elseif ( 'bulk' === $tab ) : ?>
			<?php $job = CCPC_Optimise::get_job(); ?>
			<h2><?php esc_html_e( 'Optimise all images', 'coco-photo-cms' ); ?></h2>
			<p class="description"><?php echo esc_html( sprintf( __( 'For every JPEG/PNG: resize if the long edge is over %1$d px, regenerate WebP for every size at quality %2$d, optionally re-encode the full-size JPEG (quality 82, progressive, kept only when smaller). Runs 5 images per request; close the tab and come back — it resumes.', 'coco-photo-cms' ), (int) $s['max_long_edge'], (int) $s['webp_quality'] ) ); ?></p>
			<p>
				<button class="button" id="ccpc-opt-estimate"><?php esc_html_e( 'Dry run (estimate)', 'coco-photo-cms' ); ?></button>
				<span id="ccpc-opt-estimate-out"></span>
			</p>
			<p>
				<label><input type="checkbox" id="ccpc-opt-reencode" <?php checked( ! empty( $job['opts']['reencode_jpeg'] ) ); ?>> <?php esc_html_e( 'Also re-encode full-size JPEGs (q82 progressive)', 'coco-photo-cms' ); ?></label>
			</p>
			<p>
				<button class="button button-primary" id="ccpc-opt-start" data-resume="<?php echo ( $job['started'] && ! $job['finished'] ) ? '1' : '0'; ?>"><?php echo ( $job['started'] && ! $job['finished'] ) ? esc_html__( 'Resume', 'coco-photo-cms' ) : esc_html__( 'Start', 'coco-photo-cms' ); ?></button>
				<button class="button" id="ccpc-opt-pause" disabled><?php esc_html_e( 'Pause', 'coco-photo-cms' ); ?></button>
				<button class="button-link" id="ccpc-opt-reset"><?php esc_html_e( 'Reset job', 'coco-photo-cms' ); ?></button>
			</p>
			<div class="ccpc-progress"><div class="ccpc-progress-bar" id="ccpc-opt-bar" style="width:<?php echo $job['total'] ? (int) round( 100 * $job['processed'] / max( 1, $job['total'] ) ) : 0; ?>%"></div></div>
			<p id="ccpc-opt-status"><?php
			if ( $job['started'] ) {
				echo esc_html( sprintf( __( '%1$d / %2$d processed · %3$s → %4$s · %5$d resized · %6$d JPEGs re-encoded%7$s', 'coco-photo-cms' ), (int) $job['processed'], (int) $job['total'], size_format( $job['before'] ), size_format( $job['after'] ), (int) $job['resized'], (int) $job['reencoded'], $job['finished'] ? ' · ' . __( 'finished', 'coco-photo-cms' ) : '' ) );
			}
			?></p>
			<div id="ccpc-opt-log"></div>

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
