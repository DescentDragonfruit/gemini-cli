<?php
/**
 * Focal point per attachment and on-demand social / Pinterest / Open Graph crops around it,
 * with an optional credit watermark on the exports only (never on site images).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Crops {

	const META_FOCAL = '_ccpc_focal';
	const META_CROPS = '_ccpc_crops';
	const SUBDIR     = 'ccpc-crops';

	/**
	 * Export formats: key => [ width, height, label ].
	 */
	const FORMATS = array(
		'social_45'  => array( 1080, 1350, 'Social 4:5' ),
		'social_916' => array( 1080, 1920, 'Story 9:16' ),
		'pin_23'     => array( 1000, 1500, 'Pin 2:3' ),
		'og_191'     => array( 1200, 630, 'Open Graph 1.91:1' ),
	);

	public static function init() {
		add_action( 'add_meta_boxes_attachment', array( __CLASS__, 'meta_box' ) );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'save_fields' ), 20, 2 );
		add_action( 'wp_ajax_ccpc_set_focal', array( __CLASS__, 'ajax_set_focal' ) );
		add_action( 'wp_ajax_ccpc_make_crops', array( __CLASS__, 'ajax_make_crops' ) );
		add_action( 'admin_post_ccpc_export', array( __CLASS__, 'handle_export' ) );
		add_filter( 'bulk_actions-upload', array( __CLASS__, 'bulk_action' ) );
		add_filter( 'handle_bulk_actions-upload', array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_filter( 'manage_media_columns', array( __CLASS__, 'column' ) );
		add_action( 'manage_media_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'delete_attachment', array( __CLASS__, 'on_delete' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/* ---------- Focal point ---------- */

	/**
	 * @return array { x, y, set } floats 0–1; set=false when defaulting to centre.
	 */
	public static function get_focal( $attachment_id ) {
		$f = get_post_meta( (int) $attachment_id, self::META_FOCAL, true );
		if ( is_array( $f ) && isset( $f['x'], $f['y'] ) && is_numeric( $f['x'] ) && is_numeric( $f['y'] ) ) {
			return array( 'x' => self::clamp( $f['x'] ), 'y' => self::clamp( $f['y'] ), 'set' => true );
		}
		return array( 'x' => 0.5, 'y' => 0.5, 'set' => false );
	}

	public static function set_focal( $attachment_id, $x, $y ) {
		$attachment_id = (int) $attachment_id;
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'ccpc_notimage', 'Not an image.' );
		}
		update_post_meta( $attachment_id, self::META_FOCAL, array( 'x' => self::clamp( $x ), 'y' => self::clamp( $y ) ) );
		return self::get_focal( $attachment_id );
	}

	public static function clear_focal( $attachment_id ) {
		delete_post_meta( (int) $attachment_id, self::META_FOCAL );
	}

	protected static function clamp( $v ) {
		return round( min( 1, max( 0, (float) $v ) ), 4 );
	}

	/* ---------- Generation ---------- */

	/**
	 * Best source: the untouched upload in /originals/ when it is larger, else the attached file.
	 */
	public static function source_path( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return '';
		}
		$orig = dirname( $file ) . '/originals/' . basename( $file );
		if ( file_exists( $orig ) ) {
			$a = @getimagesize( $orig ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$b = @getimagesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( $a && $b && (int) $a[0] >= (int) $b[0] ) {
				return $orig;
			}
		}
		return $file;
	}

	public static function cache_dir( $attachment_id ) {
		$up = wp_get_upload_dir();
		return trailingslashit( $up['basedir'] ) . self::SUBDIR . '/' . (int) $attachment_id;
	}

	public static function cache_url( $attachment_id ) {
		$up = wp_get_upload_dir();
		return trailingslashit( $up['baseurl'] ) . self::SUBDIR . '/' . (int) $attachment_id;
	}

	/**
	 * Watermark options from settings.
	 */
	public static function watermark_opts() {
		$s = ccpc_get_settings();
		return array(
			'text'     => (string) $s['wm_text'],
			'position' => 'bl' === $s['wm_position'] ? 'bl' : 'br',
			'opacity'  => (int) $s['wm_opacity'],
			'size'     => (float) $s['wm_size'],
			'font'     => (string) $s['wm_font'],
		);
	}

	/**
	 * Key that changes whenever the output would: focal, source file, qualities, watermark settings.
	 */
	protected static function cache_key( $attachment_id, $focal, $watermark, $source ) {
		$s    = ccpc_get_settings();
		$bits = array(
			$focal['x'],
			$focal['y'],
			file_exists( $source ) ? filemtime( $source ) . ':' . filesize( $source ) : '0',
			(int) $s['webp_quality'],
			(int) $s['export_jpeg_quality'],
			(int) get_post_meta( $attachment_id, '_ccpc_version', true ),
			$watermark ? wp_json_encode( self::watermark_opts() ) : '0',
		);
		return substr( md5( implode( '|', $bits ) ), 0, 10 );
	}

	/**
	 * Generate (or reuse cached) crops.
	 *
	 * @param int        $attachment_id ID.
	 * @param string[]   $formats       Subset of FORMATS keys; empty = all.
	 * @param array|null $focal         { x, y } to use (and persist) instead of the stored point.
	 * @param bool       $watermark     Burn the credit line into these exports.
	 * @param bool       $force         Regenerate even when cached.
	 * @return array|WP_Error format => { jpg, webp, width, height, watermark, bytes_jpg, bytes_webp }.
	 */
	public static function generate( $attachment_id, $formats = array(), $focal = null, $watermark = false, $force = false ) {
		$attachment_id = (int) $attachment_id;
		if ( ! wp_attachment_is_image( $attachment_id ) || ! CCPC_Processor::is_processable( (string) get_post_mime_type( $attachment_id ) ) ) {
			return new WP_Error( 'ccpc_notimage', 'Only JPEG/PNG attachments can be exported.', array( 'status' => 400 ) );
		}
		if ( '' === CCPC_Image::engine() ) {
			return new WP_Error( 'ccpc_noengine', 'Neither GD nor Imagick is available on this server.', array( 'status' => 500 ) );
		}
		$formats = array_values( array_intersect( (array) $formats, array_keys( self::FORMATS ) ) );
		if ( ! $formats ) {
			$formats = array_keys( self::FORMATS );
		}
		if ( is_array( $focal ) && isset( $focal['x'], $focal['y'] ) ) {
			$focal = self::set_focal( $attachment_id, $focal['x'], $focal['y'] );
			if ( is_wp_error( $focal ) ) {
				return $focal;
			}
		} else {
			$focal = self::get_focal( $attachment_id );
		}
		$source = self::source_path( $attachment_id );
		if ( '' === $source ) {
			return new WP_Error( 'ccpc_missing', 'Attachment file not found.', array( 'status' => 404 ) );
		}
		$key     = self::cache_key( $attachment_id, $focal, (bool) $watermark, $source );
		$dir     = self::cache_dir( $attachment_id );
		$stored  = get_post_meta( $attachment_id, self::META_CROPS, true );
		$stored  = is_array( $stored ) ? $stored : array();
		$s       = ccpc_get_settings();
		$webp_ok = CCPC_Image::supports_webp();
		$out     = array();
		$img     = null;

		foreach ( $formats as $fmt ) {
			list( $w, $h ) = self::FORMATS[ $fmt ];
			$slot = $fmt . ( $watermark ? ':wm' : '' );
			$base = $fmt . '-' . $key;
			$jpg  = $dir . '/' . $base . '.jpg';
			$webp = $dir . '/' . $base . '.webp';
			if ( ! $force && file_exists( $jpg ) && ( ! $webp_ok || file_exists( $webp ) ) ) {
				$out[ $slot ] = self::describe_slot( $attachment_id, $fmt, $base, (bool) $watermark, $webp_ok );
				continue;
			}
			// Load once per call; crop from a fresh copy each time (cropping is destructive).
			$img = CCPC_Image::load( $source );
			if ( is_wp_error( $img ) ) {
				return $img;
			}
			$r = $img->crop_focal( $focal['x'], $focal['y'], $w, $h );
			if ( is_wp_error( $r ) ) {
				$img->destroy();
				return $r;
			}
			if ( $watermark ) {
				$img->watermark( self::watermark_opts() );
			}
			$r = $img->save( $jpg, 'image/jpeg', (int) $s['export_jpeg_quality'], true );
			if ( is_wp_error( $r ) ) {
				$img->destroy();
				return $r;
			}
			if ( $webp_ok ) {
				$img->save( $webp, 'image/webp', (int) $s['webp_quality'] );
			}
			$img->destroy();
			$out[ $slot ] = self::describe_slot( $attachment_id, $fmt, $base, (bool) $watermark, $webp_ok );
		}

		// Persist, then sweep files no slot references any more.
		foreach ( $out as $slot => $d ) {
			$stored[ $slot ] = array(
				'format'    => $d['format'],
				'base'      => $d['base'],
				'watermark' => $d['watermark'],
				'generated' => time(),
			);
		}
		update_post_meta( $attachment_id, self::META_CROPS, $stored );
		self::sweep( $attachment_id, $stored );
		return $out;
	}

	protected static function describe_slot( $attachment_id, $fmt, $base, $watermark, $webp_ok ) {
		$dir  = self::cache_dir( $attachment_id );
		$url  = self::cache_url( $attachment_id );
		$jpg  = $dir . '/' . $base . '.jpg';
		$webp = $dir . '/' . $base . '.webp';
		return array(
			'format'     => $fmt,
			'label'      => self::FORMATS[ $fmt ][2],
			'width'      => self::FORMATS[ $fmt ][0],
			'height'     => self::FORMATS[ $fmt ][1],
			'watermark'  => (bool) $watermark,
			'base'       => $base,
			'jpg'        => $url . '/' . $base . '.jpg',
			'webp'       => ( $webp_ok && file_exists( $webp ) ) ? $url . '/' . $base . '.webp' : null,
			'bytes_jpg'  => file_exists( $jpg ) ? (int) filesize( $jpg ) : 0,
			'bytes_webp' => file_exists( $webp ) ? (int) filesize( $webp ) : 0,
		);
	}

	/**
	 * Cached crops that still exist on disk and still match the current key (stale ones are dropped).
	 *
	 * @return array slot => description.
	 */
	public static function cached( $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		$stored        = get_post_meta( $attachment_id, self::META_CROPS, true );
		if ( ! is_array( $stored ) || ! $stored ) {
			return array();
		}
		$source = self::source_path( $attachment_id );
		$focal  = self::get_focal( $attachment_id );
		$keys   = array(
			0 => $source ? self::cache_key( $attachment_id, $focal, false, $source ) : '',
			1 => $source ? self::cache_key( $attachment_id, $focal, true, $source ) : '',
		);
		$dir     = self::cache_dir( $attachment_id );
		$webp_ok = CCPC_Image::supports_webp();
		$out     = array();
		$changed = false;
		foreach ( $stored as $slot => $d ) {
			$fmt = isset( $d['format'] ) ? $d['format'] : '';
			$wm  = ! empty( $d['watermark'] ) ? 1 : 0;
			if ( ! isset( self::FORMATS[ $fmt ] ) || empty( $d['base'] ) || $d['base'] !== $fmt . '-' . $keys[ $wm ] || ! file_exists( $dir . '/' . $d['base'] . '.jpg' ) ) {
				unset( $stored[ $slot ] );
				$changed = true;
				continue;
			}
			$out[ $slot ] = self::describe_slot( $attachment_id, $fmt, $d['base'], (bool) $wm, $webp_ok );
		}
		if ( $changed ) {
			update_post_meta( $attachment_id, self::META_CROPS, $stored );
			self::sweep( $attachment_id, $stored );
		}
		return $out;
	}

	/**
	 * Count of current (non-watermark) formats cached, out of the total.
	 */
	public static function status( $attachment_id ) {
		$n = 0;
		foreach ( self::cached( $attachment_id ) as $slot => $d ) {
			if ( empty( $d['watermark'] ) ) {
				$n++;
			}
		}
		return array( $n, count( self::FORMATS ) );
	}

	protected static function sweep( $attachment_id, $stored ) {
		$dir = self::cache_dir( $attachment_id );
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$keep = array();
		foreach ( $stored as $d ) {
			if ( ! empty( $d['base'] ) ) {
				$keep[ $d['base'] . '.jpg' ]  = 1;
				$keep[ $d['base'] . '.webp' ] = 1;
			}
		}
		foreach ( (array) glob( $dir . '/*.{jpg,webp}', GLOB_BRACE ) as $f ) {
			if ( ! isset( $keep[ basename( $f ) ] ) ) {
				wp_delete_file( $f );
			}
		}
	}

	public static function on_delete( $attachment_id ) {
		$dir = self::cache_dir( $attachment_id );
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( (array) glob( $dir . '/*' ) as $f ) {
			if ( is_file( $f ) ) {
				wp_delete_file( $f );
			}
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	/* ---------- Attachment edit screen ---------- */

	public static function meta_box() {
		add_meta_box( 'ccpc-social', __( 'Coco Photo CMS — Focal point, social exports & resize', 'coco-photo-cms' ), array( __CLASS__, 'render_box' ), 'attachment', 'normal', 'high' );
	}

	public static function render_box( $post ) {
		$id = (int) $post->ID;
		if ( ! wp_attachment_is_image( $id ) || ! CCPC_Processor::is_processable( (string) get_post_mime_type( $id ) ) ) {
			echo '<p>' . esc_html__( 'JPEG/PNG images only.', 'coco-photo-cms' ) . '</p>';
			return;
		}
		$focal   = self::get_focal( $id );
		$preview = wp_get_attachment_image_src( $id, 'medium_large' );
		if ( ! $preview ) {
			$preview = wp_get_attachment_image_src( $id, 'large' );
		}
		$cached = self::cached( $id );
		$meta   = wp_get_attachment_metadata( $id );
		$engine = CCPC_Image::engine();
		$export = wp_nonce_url( admin_url( 'admin-post.php?action=ccpc_export&id=' . $id ), 'ccpc_export_' . $id );
		$wm_on  = ! empty( ccpc_setting( 'wm_default' ) );
		?>
		<div class="ccpc-box ccpc-social-box">
			<?php if ( '' === $engine ) : ?>
				<p class="ccpc-alt-warning"><?php esc_html_e( 'Neither GD nor Imagick is available: exports and resizing are disabled.', 'coco-photo-cms' ); ?></p>
			<?php endif; ?>
			<div class="ccpc-social-cols">
				<div class="ccpc-social-left">
					<h4><?php esc_html_e( 'Focal point', 'coco-photo-cms' ); ?> <span class="ccpc-focal-readout"><?php echo esc_html( $focal['set'] ? round( $focal['x'] * 100 ) . '% / ' . round( $focal['y'] * 100 ) . '%' : __( 'centre (default)', 'coco-photo-cms' ) ); ?></span></h4>
					<p class="description"><?php esc_html_e( 'Click the picture where the subject is. Every social crop is cut around that point.', 'coco-photo-cms' ); ?></p>
					<?php if ( $preview ) : ?>
					<div class="ccpc-focal" data-id="<?php echo (int) $id; ?>" style="aspect-ratio:<?php echo (int) $preview[1]; ?>/<?php echo (int) $preview[2]; ?>">
						<img src="<?php echo esc_url( $preview[0] ); ?>" alt="" draggable="false">
						<span class="ccpc-focal-dot" style="left:<?php echo esc_attr( $focal['x'] * 100 ); ?>%;top:<?php echo esc_attr( $focal['y'] * 100 ); ?>%"></span>
					</div>
					<?php endif; ?>
					<input type="hidden" name="attachments[<?php echo (int) $id; ?>][ccpc_focal_x]" class="ccpc-focal-x" value="<?php echo esc_attr( $focal['x'] ); ?>">
					<input type="hidden" name="attachments[<?php echo (int) $id; ?>][ccpc_focal_y]" class="ccpc-focal-y" value="<?php echo esc_attr( $focal['y'] ); ?>">
					<p><button type="button" class="button button-small ccpc-focal-reset" data-id="<?php echo (int) $id; ?>"><?php esc_html_e( 'Reset to centre', 'coco-photo-cms' ); ?></button> <span class="ccpc-msg ccpc-focal-msg"></span></p>
				</div>
				<div class="ccpc-social-right">
					<h4><?php esc_html_e( 'Export for social', 'coco-photo-cms' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Downloads a crop around the focal point. Files are cached until the focal point or the image changes.', 'coco-photo-cms' ); ?></p>
					<p class="ccpc-export-opts">
						<label><input type="checkbox" class="ccpc-export-wm" <?php checked( $wm_on ); ?>> <?php esc_html_e( 'Add credit watermark', 'coco-photo-cms' ); ?></label>
						&nbsp; <label><?php esc_html_e( 'Format', 'coco-photo-cms' ); ?> <select class="ccpc-export-type"><option value="jpg">JPEG</option><?php if ( CCPC_Image::supports_webp() ) : ?><option value="webp">WebP</option><?php endif; ?></select></label>
					</p>
					<p class="ccpc-export-buttons" data-base="<?php echo esc_url( $export ); ?>">
						<?php foreach ( self::FORMATS as $key => $f ) : ?>
							<a class="button <?php echo 'pin_23' === $key ? 'ccpc-pin' : ''; ?> ccpc-export-btn" data-format="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( add_query_arg( 'format', $key, $export ) ); ?>">
								<?php echo esc_html( $f[2] ); ?> <small><?php echo (int) $f[0]; ?>×<?php echo (int) $f[1]; ?></small>
							</a>
						<?php endforeach; ?>
					</p>
					<p>
						<button type="button" class="button button-small ccpc-make-crops" data-id="<?php echo (int) $id; ?>"><?php esc_html_e( 'Generate all now (cache)', 'coco-photo-cms' ); ?></button>
						<span class="ccpc-msg ccpc-crops-msg"></span>
					</p>
					<ul class="ccpc-crops-status">
						<?php foreach ( self::FORMATS as $key => $f ) : ?>
							<li data-format="<?php echo esc_attr( $key ); ?>">
								<span class="ccpc-dot <?php echo isset( $cached[ $key ] ) ? 'is-on' : ''; ?>"></span>
								<?php echo esc_html( $f[2] ); ?>:
								<?php if ( isset( $cached[ $key ] ) ) : ?>
									<a href="<?php echo esc_url( $cached[ $key ]['jpg'] ); ?>" target="_blank" rel="noopener">JPG <?php echo esc_html( size_format( $cached[ $key ]['bytes_jpg'] ) ); ?></a>
									<?php if ( $cached[ $key ]['webp'] ) : ?> · <a href="<?php echo esc_url( $cached[ $key ]['webp'] ); ?>" target="_blank" rel="noopener">WebP <?php echo esc_html( size_format( $cached[ $key ]['bytes_webp'] ) ); ?></a><?php endif; ?>
								<?php else : ?>
									<em><?php esc_html_e( 'not generated', 'coco-photo-cms' ); ?></em>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>

					<h4><?php esc_html_e( 'Resize this photo', 'coco-photo-cms' ); ?></h4>
					<p class="description">
						<?php
						printf(
							/* translators: 1: width 2: height */
							esc_html__( 'Currently %1$d × %2$d. Re-encodes the full-size file in place (from the backed-up original when there is one), regenerates every size and WebP, and cache-busts the URL.', 'coco-photo-cms' ),
							(int) ( $meta['width'] ?? 0 ),
							(int) ( $meta['height'] ?? 0 )
						);
						?>
					</p>
					<p class="ccpc-inline-form">
						<label><?php esc_html_e( 'Long edge (px)', 'coco-photo-cms' ); ?> <input type="number" class="small-text ccpc-resize-edge" min="400" max="6000" step="1" value="<?php echo (int) ccpc_setting( 'max_long_edge' ); ?>"></label>
						<button type="button" class="button ccpc-resize-btn" data-id="<?php echo (int) $id; ?>" <?php disabled( '' === $engine ); ?>><?php esc_html_e( 'Resize now', 'coco-photo-cms' ); ?></button>
						<span class="ccpc-msg ccpc-resize-msg"></span>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Focal point posted with the attachment edit form.
	 */
	public static function save_fields( $post, $attachment ) {
		$id = (int) $post['ID'];
		if ( isset( $attachment['ccpc_focal_x'], $attachment['ccpc_focal_y'] ) && is_numeric( $attachment['ccpc_focal_x'] ) && is_numeric( $attachment['ccpc_focal_y'] ) ) {
			$x   = self::clamp( $attachment['ccpc_focal_x'] );
			$y   = self::clamp( $attachment['ccpc_focal_y'] );
			$cur = self::get_focal( $id );
			// Do not create a stored point just because the form echoed the centre default back.
			if ( $cur['set'] || abs( $x - 0.5 ) > 0.0001 || abs( $y - 0.5 ) > 0.0001 ) {
				self::set_focal( $id, $x, $y );
			}
		}
		return $post;
	}

	public static function ajax_set_focal() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		if ( ! empty( $_POST['reset'] ) ) {
			self::clear_focal( $id );
			wp_send_json_success( self::get_focal( $id ) );
		}
		if ( ! isset( $_POST['x'], $_POST['y'] ) ) {
			wp_send_json_error( 'x and y required' );
		}
		$r = self::set_focal( $id, (float) $_POST['x'], (float) $_POST['y'] );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		wp_send_json_success( $r );
	}

	public static function ajax_make_crops() {
		check_ajax_referer( 'ccpc_admin', 'nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$wm = ! empty( $_POST['watermark'] );
		$r  = self::generate( $id, array(), null, $wm, ! empty( $_POST['force'] ) );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		wp_send_json_success( $r );
	}

	/**
	 * admin-post.php?action=ccpc_export&id=&format=&type=jpg|webp&wm=0|1 → file download.
	 */
	public static function handle_export() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'coco-photo-cms' ) );
		}
		check_admin_referer( 'ccpc_export_' . $id );
		$format = isset( $_GET['format'] ) ? sanitize_key( $_GET['format'] ) : '';
		$type   = isset( $_GET['type'] ) && 'webp' === $_GET['type'] ? 'webp' : 'jpg';
		$wm     = ! empty( $_GET['wm'] );
		if ( ! isset( self::FORMATS[ $format ] ) ) {
			wp_die( esc_html__( 'Unknown format.', 'coco-photo-cms' ) );
		}
		$r = self::generate( $id, array( $format ), null, $wm );
		if ( is_wp_error( $r ) ) {
			wp_die( esc_html( $r->get_error_message() ) );
		}
		$slot = $format . ( $wm ? ':wm' : '' );
		$d    = $r[ $slot ];
		$path = self::cache_dir( $id ) . '/' . $d['base'] . '.' . $type;
		if ( 'webp' === $type && ! file_exists( $path ) ) {
			$type = 'jpg';
			$path = self::cache_dir( $id ) . '/' . $d['base'] . '.jpg';
		}
		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Export file missing.', 'coco-photo-cms' ) );
		}
		$stem = pathinfo( (string) get_post_meta( $id, '_wp_attached_file', true ), PATHINFO_FILENAME );
		$name = sanitize_file_name( $stem . '-' . str_replace( '_', '-', $format ) . ( $wm ? '-credit' : '' ) . '.' . $type );
		nocache_headers();
		header( 'Content-Type: ' . ( 'webp' === $type ? 'image/webp' : 'image/jpeg' ) );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/* ---------- Media list ---------- */

	public static function bulk_action( $actions ) {
		$actions['ccpc_social_crops'] = __( 'Generate social crops', 'coco-photo-cms' );
		return $actions;
	}

	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( 'ccpc_social_crops' !== $action ) {
			return $redirect;
		}
		$n = 0;
		foreach ( (array) $ids as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) || ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$r = self::generate( (int) $id, array(), null, false );
			if ( ! is_wp_error( $r ) ) {
				$n++;
			}
		}
		return add_query_arg( 'ccpc_crops_done', $n, remove_query_arg( 'ccpc_crops_done', $redirect ) );
	}

	public static function column( $cols ) {
		$cols['ccpc_social'] = __( 'Social', 'coco-photo-cms' );
		return $cols;
	}

	public static function column_content( $col, $id ) {
		if ( 'ccpc_social' !== $col ) {
			return;
		}
		if ( ! wp_attachment_is_image( $id ) ) {
			echo '—';
			return;
		}
		$focal = self::get_focal( $id );
		list( $n, $total ) = self::status( $id );
		printf(
			'<span class="ccpc-dot %1$s" title="%2$s"></span> <a href="%3$s">%4$s</a>',
			$focal['set'] ? 'is-on' : '',
			esc_attr( $focal['set'] ? sprintf( __( 'Focal point set (%1$d%% / %2$d%%)', 'coco-photo-cms' ), round( $focal['x'] * 100 ), round( $focal['y'] * 100 ) ) : __( 'Focal point: centre (not set)', 'coco-photo-cms' ) ),
			esc_url( get_edit_post_link( $id ) . '#ccpc-social' ),
			esc_html( sprintf( __( '%1$d/%2$d crops', 'coco-photo-cms' ), $n, $total ) )
		);
	}

	public static function notices() {
		if ( isset( $_GET['ccpc_crops_done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$n = (int) $_GET['ccpc_crops_done']; // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( 'Social crops generated for %d image.', 'Social crops generated for %d images.', $n, 'coco-photo-cms' ), $n ) ) . '</p></div>';
		}
	}
}
