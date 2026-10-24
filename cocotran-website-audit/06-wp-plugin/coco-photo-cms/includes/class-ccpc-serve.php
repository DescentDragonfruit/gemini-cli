<?php
/**
 * Serve WebP/AVIF siblings when the browser accepts them; cache-bust replaced images.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Serve {

	protected static $exists_cache = array();

	public static function init() {
		add_filter( 'wp_get_attachment_url', array( __CLASS__, 'cache_bust' ), 10, 2 );
		if ( is_admin() ) {
			return;
		}
		if ( (int) ccpc_setting( 'front_cap' ) > 0 ) {
			add_filter( 'image_downsize', array( __CLASS__, 'cap_full' ), 5, 3 );
			add_filter( 'wp_content_img_tag', array( __CLASS__, 'cap_content_img' ), 5, 3 );
		}
		if ( ! ccpc_setting( 'serve_webp' ) ) {
			return;
		}
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'image_attributes' ), 20, 3 );
		add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'srcset' ), 20, 5 );
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'content_img' ), 20, 3 );
		add_action( 'send_headers', array( __CLASS__, 'vary_header' ) );
	}

	/**
	 * Which format does this request accept? 'avif' | 'webp' | ''.
	 */
	public static function accepted() {
		static $acc = null;
		if ( null !== $acc ) {
			return $acc;
		}
		$h   = isset( $_SERVER['HTTP_ACCEPT'] ) ? (string) $_SERVER['HTTP_ACCEPT'] : ''; // phpcs:ignore WordPress.Security
		$acc = '';
		if ( ccpc_setting( 'make_avif' ) && false !== strpos( $h, 'image/avif' ) ) {
			$acc = 'avif';
		} elseif ( false !== strpos( $h, 'image/webp' ) ) {
			$acc = 'webp';
		}
		return $acc;
	}

	public static function vary_header() {
		if ( ! headers_sent() ) {
			header( 'Vary: Accept', false );
		}
	}

	/**
	 * Swap a single upload URL for its variant if the variant file exists.
	 */
	public static function swap_url( $url ) {
		$fmt = self::accepted();
		if ( ! $fmt || ! is_string( $url ) ) {
			return $url;
		}
		$clean = strtok( $url, '?' );
		$query = substr( $url, strlen( $clean ) );
		if ( ! preg_match( '/\.(jpe?g|png)$/i', $clean ) ) {
			return $url;
		}
		$uploads = wp_get_upload_dir();
		if ( 0 !== strpos( $clean, $uploads['baseurl'] ) ) {
			return $url;
		}
		$rel  = substr( $clean, strlen( $uploads['baseurl'] ) );
		$path = $uploads['basedir'] . $rel;
		$vpat = CCPC_Processor::variant_path( $path, $fmt );
		if ( ! isset( self::$exists_cache[ $vpat ] ) ) {
			self::$exists_cache[ $vpat ] = file_exists( $vpat );
		}
		if ( ! self::$exists_cache[ $vpat ] ) {
			return $url;
		}
		return $uploads['baseurl'] . substr( $vpat, strlen( $uploads['basedir'] ) ) . $query;
	}

	/**
	 * Swap the src of images built by wp_get_attachment_image() (featured images, galleries,
	 * image blocks). This runs after core has built srcset/sizes from the original .jpg URL;
	 * swapping earlier (on wp_get_attachment_image_src) made wp_calculate_image_srcset() fail
	 * its src match and drop srcset entirely. The srcset URLs themselves are swapped in srcset().
	 */
	public static function image_attributes( $attr, $attachment, $size ) {
		if ( is_array( $attr ) && ! empty( $attr['src'] ) ) {
			$attr['src'] = self::swap_url( $attr['src'] );
		}
		return $attr;
	}

	public static function srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! is_array( $sources ) ) {
			return $sources;
		}
		$v = (int) get_post_meta( $attachment_id, '_ccpc_version', true );
		foreach ( $sources as $w => $s ) {
			$url = self::swap_url( $s['url'] );
			if ( $v && false === strpos( $url, '?v=' ) ) {
				$url = add_query_arg( 'v', $v, $url );
			}
			$sources[ $w ]['url'] = $url;
		}
		return $sources;
	}

	/**
	 * Images in post content that bypass wp_get_attachment_image (classic <img> / Kadence).
	 */
	public static function content_img( $filtered_image, $context, $attachment_id ) {
		if ( ! self::accepted() || false === strpos( $filtered_image, 'src="' ) ) {
			return $filtered_image;
		}
		return preg_replace_callback( '/\s(src|data-src)="([^"]+)"/', function ( $m ) {
			return ' ' . $m[1] . '="' . esc_url( self::swap_url( $m[2] ) ) . '"';
		}, $filtered_image );
	}

	/**
	 * The largest generated size whose width is <= the cap. Returns [url, w, h] or null.
	 */
	public static function capped_size( $attachment_id ) {
		$cap  = (int) ccpc_setting( 'front_cap' );
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( $cap <= 0 || empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
			return null;
		}
		if ( ! empty( $meta['width'] ) && (int) $meta['width'] <= $cap ) {
			return null; // original is already small enough
		}
		$best = null;
		foreach ( $meta['sizes'] as $name => $sz ) {
			if ( empty( $sz['width'] ) || empty( $sz['file'] ) || (int) $sz['width'] > $cap ) {
				continue;
			}
			if ( ! $best || (int) $sz['width'] > (int) $best['width'] ) {
				$best = $sz;
			}
		}
		if ( ! $best ) {
			return null;
		}
		$full = wp_get_attachment_url( $attachment_id );
		if ( ! $full ) {
			return null;
		}
		$full = strtok( $full, '?' );
		$url  = trailingslashit( dirname( $full ) ) . $best['file'];
		return array( $url, (int) $best['width'], (int) $best['height'] );
	}

	/**
	 * image_downsize: when a template asks for 'full' on the front end, hand back the capped size.
	 * Runs before core, which then skips its own lookup; srcset still matches because the capped
	 * file is one of the registered sizes.
	 */
	public static function cap_full( $out, $attachment_id, $size ) {
		// An unregistered size name (e.g. 'post-thumbnail' in a theme without add_image_size) falls
		// back to the original in core, so treat it like 'full'.
		if ( is_array( $size ) || ( 'full' !== $size && in_array( $size, get_intermediate_image_sizes(), true ) ) || ! wp_attachment_is_image( $attachment_id ) ) {
			return $out;
		}
		$c = self::capped_size( $attachment_id );
		if ( ! $c ) {
			return $out;
		}
		$v = (int) get_post_meta( $attachment_id, '_ccpc_version', true );
		if ( $v ) {
			$c[0] = add_query_arg( 'v', $v, $c[0] );
		}
		return array( $c[0], $c[1], $c[2], true );
	}

	/**
	 * Content <img> tags (classic blocks, Kadence galleries/sliders) that hard-code the original URL.
	 */
	public static function cap_content_img( $filtered_image, $context, $attachment_id ) {
		if ( ! $attachment_id || false === strpos( $filtered_image, 'src="' ) ) {
			return $filtered_image;
		}
		$c = self::capped_size( $attachment_id );
		if ( ! $c ) {
			return $filtered_image;
		}
		$full = strtok( (string) wp_get_attachment_url( $attachment_id ), '?' );
		if ( ! $full ) {
			return $filtered_image;
		}
		// Only replace the original/scaled URL (not an already-sized one).
		$scaled = preg_replace( '/(\.[a-z]{3,4})$/i', '-scaled$1', $full );
		foreach ( array( $full, $scaled ) as $orig ) {
			if ( false !== strpos( $filtered_image, $orig ) ) {
				$filtered_image = str_replace( $orig, $c[0], $filtered_image );
				$filtered_image = preg_replace( '/\swidth="\d+"/', ' width="' . $c[1] . '"', $filtered_image, 1 );
				$filtered_image = preg_replace( '/\sheight="\d+"/', ' height="' . $c[2] . '"', $filtered_image, 1 );
			}
		}
		return $filtered_image;
	}

	/**
	 * ?v=<timestamp> on attachments that were replaced in place.
	 */
	public static function cache_bust( $url, $attachment_id ) {
		$v = (int) get_post_meta( $attachment_id, '_ccpc_version', true );
		if ( $v && false === strpos( $url, '?v=' ) ) {
			$url = add_query_arg( 'v', $v, $url );
		}
		return $url;
	}
}
