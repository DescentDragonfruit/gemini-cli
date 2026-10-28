<?php
/**
 * Upload processing: resize, strip EXIF, keep original, generate WebP/AVIF for every size.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Processor {

	public static function init() {
		add_filter( 'wp_handle_upload', array( __CLASS__, 'after_upload' ), 10, 2 );
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'on_metadata' ), 20, 2 );
		add_action( 'delete_attachment', array( __CLASS__, 'on_delete' ) );
	}

	/**
	 * Is this a raster image we process?
	 */
	public static function is_processable( $file_or_type ) {
		$type = $file_or_type;
		if ( false !== strpos( $file_or_type, '/' ) && file_exists( $file_or_type ) ) {
			$type = (string) wp_check_filetype( $file_or_type )['type'];
		}
		return in_array( $type, array( 'image/jpeg', 'image/png' ), true );
	}

	/**
	 * Runs once the uploaded file is in place: back up the original, resize, strip EXIF.
	 *
	 * @param array  $upload  { file, url, type }.
	 * @param string $context 'upload' | 'sideload'.
	 * @return array
	 */
	public static function after_upload( $upload, $context = 'upload' ) {
		if ( empty( $upload['file'] ) || ! empty( $upload['error'] ) || ! self::is_processable( (string) $upload['type'] ) ) {
			return $upload;
		}
		self::process_file( $upload['file'] );
		return $upload;
	}

	/**
	 * Back up + resize + strip a file in place. Reusable for replace().
	 *
	 * @param string $path Absolute path.
	 * @return true|WP_Error
	 */
	public static function process_file( $path ) {
		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'ccpc_missing', 'File missing.' );
		}
		$settings = ccpc_get_settings();

		if ( ! empty( $settings['keep_originals'] ) ) {
			$orig_dir = dirname( $path ) . '/originals';
			if ( wp_mkdir_p( $orig_dir ) ) {
				$dest = $orig_dir . '/' . basename( $path );
				if ( ! file_exists( $dest ) ) {
					copy( $path, $dest );
				}
			}
		}

		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$size = $editor->get_size();
		$max  = max( 400, (int) $settings['max_long_edge'] );
		$long = max( (int) $size['width'], (int) $size['height'] );

		// Preserve copyright/artist before the editor strips EXIF.
		$keep = self::read_credit_exif( $path );

		if ( $long > $max ) {
			$editor->resize( $max, $max, false );
		}
		$editor->set_quality( 86 );
		// Saving through the editor re-encodes and drops EXIF (GD) / strips profiles (Imagick).
		$saved = $editor->save( $path );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		if ( $keep ) {
			self::write_credit_exif( $path, $keep );
		}
		return true;
	}

	public static function read_credit_exif( $path ) {
		if ( ! function_exists( 'exif_read_data' ) || 'image/jpeg' !== wp_check_filetype( $path )['type'] ) {
			return array();
		}
		$exif = @exif_read_data( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! is_array( $exif ) ) {
			return array();
		}
		$out = array();
		foreach ( array( 'Copyright', 'Artist' ) as $k ) {
			if ( ! empty( $exif[ $k ] ) ) {
				$out[ $k ] = sanitize_text_field( $exif[ $k ] );
			}
		}
		return $out;
	}

	/**
	 * Re-apply copyright/artist with Imagick when available (GD cannot write EXIF).
	 */
	public static function write_credit_exif( $path, $fields ) {
		if ( ! class_exists( 'Imagick' ) ) {
			return;
		}
		try {
			$im = new Imagick( $path );
			if ( isset( $fields['Copyright'] ) ) {
				$im->setImageProperty( 'exif:Copyright', $fields['Copyright'] );
			}
			if ( isset( $fields['Artist'] ) ) {
				$im->setImageProperty( 'exif:Artist', $fields['Artist'] );
			}
			$im->writeImage( $path );
			$im->clear();
		} catch ( Exception $e ) {
			// Non-fatal.
		}
	}

	/**
	 * After WP builds the sizes, generate WebP/AVIF for each.
	 */
	public static function on_metadata( $metadata, $attachment_id ) {
		if ( ! is_array( $metadata ) || ! wp_attachment_is_image( $attachment_id ) ) {
			return $metadata;
		}
		$type = get_post_mime_type( $attachment_id );
		if ( ! self::is_processable( (string) $type ) ) {
			return $metadata;
		}
		$metadata['ccpc_variants'] = self::generate_variants( $attachment_id, $metadata );
		return $metadata;
	}

	/**
	 * Generate .webp (and .avif) siblings for the full file and every size.
	 *
	 * @param int        $attachment_id ID.
	 * @param array|null $metadata      Optional metadata to use.
	 * @param bool       $force         Regenerate even when an up-to-date variant exists (quality change).
	 * @return array List of generated relative paths.
	 */
	public static function generate_variants( $attachment_id, $metadata = null, $force = false ) {
		$settings = ccpc_get_settings();
		$metadata = $metadata ? $metadata : wp_get_attachment_metadata( $attachment_id );
		$file     = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) || ! is_array( $metadata ) ) {
			return array();
		}
		$dir   = dirname( $file );
		$paths = array( $file );
		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $s ) {
				if ( ! empty( $s['file'] ) ) {
					$paths[] = $dir . '/' . $s['file'];
				}
			}
		}
		$formats = array( 'webp' => (int) $settings['webp_quality'] );
		if ( ! empty( $settings['make_avif'] ) && function_exists( 'imageavif' ) ) {
			$formats['avif'] = (int) $settings['avif_quality'];
		}
		$made    = array();
		$uploads = wp_get_upload_dir();
		foreach ( $paths as $p ) {
			if ( ! file_exists( $p ) ) {
				continue;
			}
			foreach ( $formats as $ext => $q ) {
				$target = self::variant_path( $p, $ext );
				if ( ! $force && file_exists( $target ) && filemtime( $target ) >= filemtime( $p ) ) {
					$made[] = str_replace( trailingslashit( $uploads['basedir'] ), '', $target );
					continue;
				}
				$ed = wp_get_image_editor( $p );
				if ( is_wp_error( $ed ) ) {
					continue;
				}
				$ed->set_quality( $q );
				$r = $ed->save( $target, 'image/' . $ext );
				if ( ! is_wp_error( $r ) ) {
					$made[] = str_replace( trailingslashit( $uploads['basedir'] ), '', $target );
				}
			}
		}
		return $made;
	}

	/**
	 * photo.jpg -> photo.webp (sibling, extension swapped).
	 */
	public static function variant_path( $path, $ext ) {
		$info = pathinfo( $path );
		return $info['dirname'] . '/' . $info['filename'] . '.' . $ext;
	}

	/**
	 * Delete every generated size and its WebP/AVIF siblings (and the full-size siblings), so a
	 * resize/replace leaves no orphans. The full-size file itself is kept.
	 *
	 * @param int  $attachment_id    ID.
	 * @param bool $include_original Also remove the /originals/ backup.
	 */
	public static function delete_generated( $attachment_id, $include_original = false ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return;
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		$dir  = dirname( $file );
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $s ) {
				if ( empty( $s['file'] ) ) {
					continue;
				}
				$p = $dir . '/' . $s['file'];
				foreach ( array( $p, self::variant_path( $p, 'webp' ), self::variant_path( $p, 'avif' ) ) as $f ) {
					if ( file_exists( $f ) ) {
						wp_delete_file( $f );
					}
				}
			}
		}
		foreach ( array( 'webp', 'avif' ) as $v ) {
			$f = self::variant_path( $file, $v );
			if ( file_exists( $f ) ) {
				wp_delete_file( $f );
			}
		}
		if ( $include_original ) {
			$orig = $dir . '/originals/' . basename( $file );
			if ( file_exists( $orig ) ) {
				wp_delete_file( $orig );
			}
		}
	}

	/**
	 * Remove variants and the backed-up original when the attachment is deleted.
	 */
	public static function on_delete( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return;
		}
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$paths = array( $file );
		if ( ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $s ) {
				if ( ! empty( $s['file'] ) ) {
					$paths[] = dirname( $file ) . '/' . $s['file'];
				}
			}
		}
		foreach ( $paths as $p ) {
			foreach ( array( 'webp', 'avif' ) as $ext ) {
				$v = self::variant_path( $p, $ext );
				if ( file_exists( $v ) ) {
					wp_delete_file( $v );
				}
			}
		}
		$orig = dirname( $file ) . '/originals/' . basename( $file );
		if ( file_exists( $orig ) ) {
			wp_delete_file( $orig );
		}
	}
}
