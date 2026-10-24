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
		if ( ! ccpc_setting( 'serve_webp' ) || is_admin() ) {
			return;
		}
		add_filter( 'wp_get_attachment_image_src', array( __CLASS__, 'image_src' ), 20, 2 );
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

	public static function image_src( $image, $attachment_id ) {
		if ( is_array( $image ) && ! empty( $image[0] ) ) {
			$image[0] = self::swap_url( $image[0] );
		}
		return $image;
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
