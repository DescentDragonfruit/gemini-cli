<?php
/**
 * Small GD / Imagick wrapper used for focal crops, watermarks and JPEG re-encoding.
 *
 * WP_Image_Editor refuses to upscale, which social crops routinely need (a 2000×1333 source
 * cropped to 9:16 is only 750 px wide), so these paths talk to GD/Imagick directly. Every entry
 * point feature-detects and returns WP_Error when neither library can do the job.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Image {

	/** @var string 'gd' | 'imagick' | '' */
	protected $engine = '';

	/** @var resource|GdImage|Imagick|null */
	protected $img = null;

	protected $width  = 0;
	protected $height = 0;

	/**
	 * Which engine would be used. '' when none can read + write JPEG and WebP.
	 *
	 * @return string
	 */
	public static function engine() {
		if ( extension_loaded( 'gd' ) && function_exists( 'imagecreatefromjpeg' ) && function_exists( 'imagejpeg' ) ) {
			return 'gd';
		}
		if ( class_exists( 'Imagick' ) ) {
			return 'imagick';
		}
		return '';
	}

	public static function supports_webp() {
		$e = self::engine();
		if ( 'gd' === $e ) {
			return function_exists( 'imagewebp' );
		}
		if ( 'imagick' === $e ) {
			try {
				return in_array( 'WEBP', Imagick::queryFormats( 'WEBP' ), true );
			} catch ( Exception $e ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Load a JPEG/PNG/WebP from disk.
	 *
	 * @param string $path Absolute path.
	 * @return CCPC_Image|WP_Error
	 */
	public static function load( $path ) {
		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'ccpc_missing', 'Source file missing.' );
		}
		$engine = self::engine();
		if ( '' === $engine ) {
			return new WP_Error( 'ccpc_noengine', 'Neither GD nor Imagick is available.' );
		}
		$self         = new self();
		$self->engine = $engine;
		$info         = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $info ) {
			return new WP_Error( 'ccpc_unreadable', 'Not a readable image.' );
		}
		if ( 'gd' === $engine ) {
			$img = null;
			switch ( $info['mime'] ) {
				case 'image/jpeg':
					$img = @imagecreatefromjpeg( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					break;
				case 'image/png':
					$img = @imagecreatefrompng( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					break;
				case 'image/webp':
					$img = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : null; // phpcs:ignore WordPress.PHP.NoSilencedErrors
					break;
			}
			if ( ! $img ) {
				return new WP_Error( 'ccpc_unreadable', 'GD could not read the image.' );
			}
			if ( 'image/jpeg' === $info['mime'] && function_exists( 'exif_read_data' ) ) {
				$img = self::gd_fix_orientation( $img, $path );
			}
			$self->img    = $img;
			$self->width  = imagesx( $img );
			$self->height = imagesy( $img );
			return $self;
		}
		try {
			$im = new Imagick( $path );
			if ( method_exists( $im, 'autoOrient' ) ) {
				$im->autoOrient();
			}
			$im->setImageColorspace( Imagick::COLORSPACE_SRGB );
			$self->img    = $im;
			$self->width  = $im->getImageWidth();
			$self->height = $im->getImageHeight();
			return $self;
		} catch ( Exception $e ) {
			return new WP_Error( 'ccpc_unreadable', 'Imagick could not read the image: ' . $e->getMessage() );
		}
	}

	protected static function gd_fix_orientation( $img, $path ) {
		$exif = @exif_read_data( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( empty( $exif['Orientation'] ) ) {
			return $img;
		}
		switch ( (int) $exif['Orientation'] ) {
			case 3:
				return imagerotate( $img, 180, 0 );
			case 6:
				return imagerotate( $img, -90, 0 );
			case 8:
				return imagerotate( $img, 90, 0 );
		}
		return $img;
	}

	public function get_width() {
		return $this->width;
	}

	public function get_height() {
		return $this->height;
	}

	/**
	 * Crop the largest rectangle of the target aspect around the focal point, then scale to exactly
	 * $tw × $th (upscaling when the source is smaller).
	 *
	 * @param float $fx Focal x 0–1.
	 * @param float $fy Focal y 0–1.
	 * @param int   $tw Target width.
	 * @param int   $th Target height.
	 * @return true|WP_Error
	 */
	public function crop_focal( $fx, $fy, $tw, $th ) {
		$tw = max( 1, (int) $tw );
		$th = max( 1, (int) $th );
		$fx = min( 1, max( 0, (float) $fx ) );
		$fy = min( 1, max( 0, (float) $fy ) );
		$sw = $this->width;
		$sh = $this->height;
		if ( $sw < 2 || $sh < 2 ) {
			return new WP_Error( 'ccpc_tiny', 'Source too small.' );
		}
		$target_ratio = $tw / $th;
		if ( $sw / $sh > $target_ratio ) {
			$ch = $sh;
			$cw = (int) round( $sh * $target_ratio );
		} else {
			$cw = $sw;
			$ch = (int) round( $sw / $target_ratio );
		}
		$cw = max( 1, min( $sw, $cw ) );
		$ch = max( 1, min( $sh, $ch ) );
		// Place the crop so the focal point keeps its relative position, clamped to the image.
		$cx = (int) round( $fx * $sw - $cw / 2 );
		$cy = (int) round( $fy * $sh - $ch / 2 );
		$cx = max( 0, min( $sw - $cw, $cx ) );
		$cy = max( 0, min( $sh - $ch, $cy ) );

		if ( 'gd' === $this->engine ) {
			$dst = imagecreatetruecolor( $tw, $th );
			if ( ! $dst ) {
				return new WP_Error( 'ccpc_gd', 'Could not allocate image.' );
			}
			imagealphablending( $dst, false );
			imagesavealpha( $dst, true );
			imagecopyresampled( $dst, $this->img, 0, 0, $cx, $cy, $tw, $th, $cw, $ch );
			imagedestroy( $this->img );
			imagealphablending( $dst, true );
			$this->img = $dst;
		} else {
			try {
				$this->img->cropImage( $cw, $ch, $cx, $cy );
				$this->img->setImagePage( 0, 0, 0, 0 );
				$this->img->resizeImage( $tw, $th, Imagick::FILTER_LANCZOS, 1 );
			} catch ( Exception $e ) {
				return new WP_Error( 'ccpc_imagick', $e->getMessage() );
			}
		}
		$this->width  = $tw;
		$this->height = $th;
		return true;
	}

	/**
	 * Scale so the long edge is $long (never upscales).
	 *
	 * @return true|WP_Error
	 */
	public function fit_long_edge( $long ) {
		$long = (int) $long;
		$cur  = max( $this->width, $this->height );
		if ( $long < 1 || $cur <= $long ) {
			return true;
		}
		$scale = $long / $cur;
		$nw    = max( 1, (int) round( $this->width * $scale ) );
		$nh    = max( 1, (int) round( $this->height * $scale ) );
		if ( 'gd' === $this->engine ) {
			$dst = imagecreatetruecolor( $nw, $nh );
			imagealphablending( $dst, false );
			imagesavealpha( $dst, true );
			imagecopyresampled( $dst, $this->img, 0, 0, 0, 0, $nw, $nh, $this->width, $this->height );
			imagedestroy( $this->img );
			imagealphablending( $dst, true );
			$this->img = $dst;
		} else {
			try {
				$this->img->resizeImage( $nw, $nh, Imagick::FILTER_LANCZOS, 1 );
			} catch ( Exception $e ) {
				return new WP_Error( 'ccpc_imagick', $e->getMessage() );
			}
		}
		$this->width  = $nw;
		$this->height = $nh;
		return true;
	}

	/**
	 * Draw a credit line in a corner.
	 *
	 * @param array $opts { text, position (br|bl), opacity 0–100, size (% of width), font (TTF path or '') }.
	 * @return true|WP_Error
	 */
	public function watermark( $opts ) {
		$opts = wp_parse_args( $opts, array(
			'text'     => '',
			'position' => 'br',
			'opacity'  => 70,
			'size'     => 2.5,
			'font'     => '',
		) );
		$text = trim( (string) $opts['text'] );
		if ( '' === $text ) {
			return true;
		}
		$opacity = min( 100, max( 0, (float) $opts['opacity'] ) ) / 100;
		$px      = max( 10, (int) round( $this->width * ( (float) $opts['size'] / 100 ) ) );
		$margin  = max( 8, (int) round( $px * 0.9 ) );
		$font    = (string) $opts['font'];
		$ttf_ok  = '' !== $font && is_readable( $font ) && function_exists( 'imagettftext' ) && function_exists( 'imagettfbbox' );

		if ( 'gd' === $this->engine ) {
			imagealphablending( $this->img, true );
			$alpha  = (int) round( 127 - 127 * $opacity ); // 0 opaque … 127 transparent.
			$white  = imagecolorallocatealpha( $this->img, 255, 255, 255, $alpha );
			$shadow = imagecolorallocatealpha( $this->img, 0, 0, 0, min( 127, $alpha + 40 ) );
			if ( $ttf_ok ) {
				$box = @imagettfbbox( $px, 0, $font, $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				if ( is_array( $box ) ) {
					$tw = abs( $box[4] - $box[0] );
					$th = abs( $box[5] - $box[1] );
					$x  = 'bl' === $opts['position'] ? $margin : $this->width - $tw - $margin;
					$y  = $this->height - $margin; // baseline.
					imagettftext( $this->img, $px, 0, $x + 2, $y + 2, $shadow, $font, $text );
					imagettftext( $this->img, $px, 0, $x, $y, $white, $font, $text );
					return true;
				}
			}
			// Built-in bitmap font (ASCII only), rendered small then scaled up so it is legible on a
			// 1080 px image. Typographic characters are transliterated first.
			$latin = str_replace( array( '©', '·', '•', '–', '—', '’', '‘', '“', '”', '…' ), array( '(c)', '-', '-', '-', '-', "'", "'", '"', '"', '...' ), $text );
			if ( function_exists( 'iconv' ) ) {
				$t = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $latin ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				if ( is_string( $t ) && '' !== $t ) {
					$latin = $t;
				}
			}
			$latin = preg_replace( '/[^\x20-\x7E]/', '', $latin );
			if ( '' === trim( $latin ) ) {
				return true;
			}
			$f   = 5;
			$cw  = imagefontwidth( $f );
			$chh = imagefontheight( $f );
			$sw  = $cw * strlen( $latin ) + 4;
			$sh  = $chh + 4;
			$tmp = imagecreatetruecolor( $sw, $sh );
			imagealphablending( $tmp, false );
			imagesavealpha( $tmp, true );
			$transparent = imagecolorallocatealpha( $tmp, 0, 0, 0, 127 );
			imagefill( $tmp, 0, 0, $transparent );
			$tmp_shadow = imagecolorallocatealpha( $tmp, 0, 0, 0, min( 127, $alpha + 40 ) );
			$tmp_white  = imagecolorallocatealpha( $tmp, 255, 255, 255, $alpha );
			imagestring( $tmp, $f, 3, 3, $latin, $tmp_shadow );
			imagestring( $tmp, $f, 2, 2, $latin, $tmp_white );
			$scale = $px / $chh;
			$dw    = max( 1, (int) round( $sw * $scale ) );
			$dh    = max( 1, (int) round( $sh * $scale ) );
			$x     = 'bl' === $opts['position'] ? $margin : $this->width - $dw - $margin;
			$y     = $this->height - $dh - $margin;
			imagecopyresampled( $this->img, $tmp, $x, $y, 0, 0, $dw, $dh, $sw, $sh );
			imagedestroy( $tmp );
			return true;
		}

		try {
			$draw = new ImagickDraw();
			if ( '' !== $font && is_readable( $font ) ) {
				$draw->setFont( $font );
			}
			$draw->setFontSize( $px );
			$draw->setFillColor( new ImagickPixel( 'white' ) );
			$draw->setFillOpacity( $opacity );
			$draw->setGravity( 'bl' === $opts['position'] ? Imagick::GRAVITY_SOUTHWEST : Imagick::GRAVITY_SOUTHEAST );
			$shadow = clone $draw;
			$shadow->setFillColor( new ImagickPixel( 'black' ) );
			$shadow->setFillOpacity( max( 0, $opacity - 0.3 ) );
			$this->img->annotateImage( $shadow, $margin - 2, $margin - 2, 0, $text );
			$this->img->annotateImage( $draw, $margin, $margin, 0, $text );
		} catch ( Exception $e ) {
			return new WP_Error( 'ccpc_imagick', $e->getMessage() );
		}
		return true;
	}

	/**
	 * Save as JPEG or WebP.
	 *
	 * @param string $path        Destination.
	 * @param string $mime        image/jpeg | image/webp.
	 * @param int    $quality     1–100.
	 * @param bool   $progressive Progressive JPEG.
	 * @return true|WP_Error
	 */
	public function save( $path, $mime, $quality, $progressive = true ) {
		$quality = min( 100, max( 1, (int) $quality ) );
		wp_mkdir_p( dirname( $path ) );
		if ( 'gd' === $this->engine ) {
			if ( 'image/webp' === $mime ) {
				if ( ! function_exists( 'imagewebp' ) ) {
					return new WP_Error( 'ccpc_nowebp', 'GD has no WebP support.' );
				}
				$ok = @imagewebp( $this->img, $path, $quality ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			} else {
				imageinterlace( $this->img, $progressive ? 1 : 0 );
				$ok = @imagejpeg( $this->img, $path, $quality ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
			return $ok ? true : new WP_Error( 'ccpc_write', 'Could not write ' . basename( $path ) );
		}
		try {
			$im = clone $this->img;
			$im->setImageFormat( 'image/webp' === $mime ? 'webp' : 'jpeg' );
			$im->setImageCompressionQuality( $quality );
			if ( 'image/jpeg' === $mime ) {
				$im->setInterlaceScheme( $progressive ? Imagick::INTERLACE_PLANE : Imagick::INTERLACE_NO );
				if ( method_exists( $im, 'setSamplingFactors' ) ) {
					$im->setSamplingFactors( array( '2x2', '1x1', '1x1' ) );
				}
			}
			$im->stripImage();
			$ok = $im->writeImage( $path );
			$im->clear();
			return $ok ? true : new WP_Error( 'ccpc_write', 'Could not write ' . basename( $path ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'ccpc_imagick', $e->getMessage() );
		}
	}

	public function destroy() {
		if ( 'gd' === $this->engine && $this->img ) {
			imagedestroy( $this->img );
		} elseif ( 'imagick' === $this->engine && $this->img ) {
			$this->img->clear();
		}
		$this->img = null;
	}

	/**
	 * Re-encode a JPEG in place as progressive at $quality. Keeps the old file when the new one
	 * is not smaller. Returns [before, after] bytes or WP_Error.
	 */
	public static function reencode_jpeg( $path, $quality = 82 ) {
		if ( ! file_exists( $path ) || 'image/jpeg' !== (string) wp_check_filetype( $path )['type'] ) {
			return new WP_Error( 'ccpc_notjpeg', 'Not a JPEG.' );
		}
		$before = (int) filesize( $path );
		$img    = self::load( $path );
		if ( is_wp_error( $img ) ) {
			return $img;
		}
		$tmp = $path . '.ccpc-tmp';
		$r   = $img->save( $tmp, 'image/jpeg', $quality, true );
		$img->destroy();
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		$after = (int) filesize( $tmp );
		if ( $after > 0 && $after < $before ) {
			if ( ! rename( $tmp, $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				wp_delete_file( $tmp );
				return new WP_Error( 'ccpc_write', 'Could not replace the JPEG.' );
			}
			return array( $before, $after );
		}
		wp_delete_file( $tmp );
		return array( $before, $before );
	}
}
