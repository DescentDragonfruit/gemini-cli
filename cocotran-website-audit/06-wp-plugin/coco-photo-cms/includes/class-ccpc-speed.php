<?php
/**
 * Front-end speed set: decoding=async, lazy-loading with an eager budget, fetchpriority=high and a
 * <link rel=preload> for the LCP image, and a sanity pass on oversized `sizes` attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Speed {

	protected static $count    = 0;      // content images seen in the main loop (per request)
	protected static $lcp_done = false;  // fetchpriority=high already assigned
	protected static $eager    = array(); // normalised src values that must never be lazy (ct-hero, first slide)
	protected static $lcp_id   = null;
	protected static $scanned  = false;  // first-slider lookup done (first main-content post only)

	public static function init() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_cron() ) {
			return;
		}
		$s = ccpc_get_settings();
		if ( empty( $s['speed_decoding'] ) && empty( $s['speed_lazy'] ) && empty( $s['speed_fetchpriority'] ) && empty( $s['speed_preload'] ) && empty( $s['speed_sizes'] ) ) {
			return;
		}
		add_filter( 'wp_omit_loading_attr_threshold', array( __CLASS__, 'threshold' ), 20 );
		add_filter( 'the_content', array( __CLASS__, 'scan_content' ), 9 );
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'content_img' ), 30, 3 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'attributes' ), 30, 3 );
		if ( ! empty( $s['speed_preload'] ) ) {
			add_action( 'wp_head', array( __CLASS__, 'preload' ), 2 );
		}
	}

	public static function threshold() {
		return max( 0, (int) ccpc_setting( 'speed_lazy_skip' ) );
	}

	/**
	 * The LCP candidate on a singular page: featured image, else the first content image.
	 */
	public static function lcp_id() {
		if ( null !== self::$lcp_id ) {
			return self::$lcp_id;
		}
		self::$lcp_id = 0;
		if ( ! is_singular() ) {
			return 0;
		}
		$post_id = get_queried_object_id();
		$t       = get_post_thumbnail_id( $post_id );
		if ( $t ) {
			self::$lcp_id = (int) $t;
			return self::$lcp_id;
		}
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( preg_match( '/wp-image-(\d+)/', $content, $m ) || preg_match( '/"id":(\d+)[,}]/', $content, $m ) ) {
			if ( wp_attachment_is_image( (int) $m[1] ) ) {
				self::$lcp_id = (int) $m[1];
			}
		}
		return self::$lcp_id;
	}

	/**
	 * Before core's image pass: reset the per-post counter and collect images that must stay eager
	 * (class ct-hero, and everything inside the first slide of the first Kadence slider).
	 */
	public static function scan_content( $content ) {
		if ( ! is_string( $content ) || ! self::is_main_content( 'the_content' ) ) {
			return $content;
		}
		if ( preg_match_all( '/<img\b[^>]*\bclass="[^"]*\bct-hero\b[^"]*"[^>]*>/i', $content, $m ) ) {
			foreach ( $m[0] as $tag ) {
				if ( preg_match( '/\ssrc="([^"]+)"/i', $tag, $sm ) ) {
					self::$eager[ self::norm_src( $sm[1] ) ] = 1;
				}
			}
		}
		if ( self::$scanned ) {
			return $content; // Archives: the eager budget and "first slider" apply to the page, not to every post.
		}
		self::$scanned = true;
		// First Kadence slider/carousel wrapper → its first slide → its <img> tags.
		if ( preg_match( '/class="[^"]*\b(kb-gallery-type-(?:slider|carousel|fluidcarousel|thumbslider)|kt-blocks-carousel|kb-splide|kb-slider|splide)\b[^"]*"/i', $content, $wm, PREG_OFFSET_CAPTURE ) ) {
			$from  = (int) $wm[0][1];
			$slide = '/class="[^"]*\b(kb-slide-item|kb-gallery-slide-item|splide__slide|kt-blocks-carousel-item|kadence-blocks-gallery-item)\b[^"]*"/i';
			if ( preg_match( $slide, $content, $sm1, PREG_OFFSET_CAPTURE, $from ) ) {
				$start = (int) $sm1[0][1];
				$end   = strlen( $content );
				if ( preg_match( $slide, $content, $sm2, PREG_OFFSET_CAPTURE, $start + strlen( $sm1[0][0] ) ) ) {
					$end = (int) $sm2[0][1];
				}
				$chunk = substr( $content, $start, min( $end - $start, 20000 ) );
				if ( preg_match_all( '/<img\b[^>]*\ssrc="([^"]+)"/i', $chunk, $im ) ) {
					foreach ( $im[1] as $src ) {
						self::$eager[ self::norm_src( $src ) ] = 1;
					}
				}
			}
		}
		return $content;
	}

	/**
	 * Is this the main post content (classic loop, or a block theme's core/post-content render)?
	 */
	protected static function is_main_content( $context ) {
		if ( 'the_content' !== $context || ! is_main_query() ) {
			return false;
		}
		return in_the_loop() || ( is_singular() && (int) get_the_ID() === (int) get_queried_object_id() );
	}

	/**
	 * Content <img> tags (after core's own loading/srcset pass).
	 *
	 * Block themes run wp_filter_content_tags() a second time over the whole rendered template
	 * (context 'template'). Images already decided in the first pass carry a `loading` or
	 * `fetchpriority` attribute and are left alone; only untouched template images (logo, related
	 * posts…) get the default treatment here.
	 */
	public static function content_img( $img, $context, $attachment_id ) {
		if ( ! is_string( $img ) || '' === $img ) {
			return $img;
		}
		$s    = ccpc_get_settings();
		$main = self::is_main_content( $context );
		if ( ! $main && ! in_array( $context, array( 'the_excerpt', 'widget_text_content', 'widget_block_content' ), true )
			&& preg_match( '/\s(loading|fetchpriority)="/i', $img ) ) {
			if ( ! empty( $s['speed_decoding'] ) ) {
				$img = self::set_attr( $img, 'decoding', 'async' );
			}
			if ( ! empty( $s['speed_sizes'] ) ) {
				$img = self::fix_sizes_tag( $img );
			}
			return $img;
		}
		$eager = false;
		if ( $main ) {
			self::$count++;
			if ( self::$count <= (int) $s['speed_lazy_skip'] ) {
				$eager = true;
			}
		}
		if ( preg_match( '/\bclass="[^"]*\bct-hero\b[^"]*"/i', $img ) ) {
			$eager = true;
		}
		if ( preg_match( '/\ssrc="([^"]+)"/i', $img, $sm ) && isset( self::$eager[ self::norm_src( $sm[1] ) ] ) ) {
			$eager = true;
		}
		$is_lcp = false;
		if ( $main && ! empty( $s['speed_fetchpriority'] ) && ! self::$lcp_done && is_singular() && $attachment_id && (int) $attachment_id === self::lcp_id() ) {
			$is_lcp = true;
		} elseif ( $main && ! empty( $s['speed_fetchpriority'] ) && ! self::$lcp_done && is_singular() && 1 === self::$count && ! get_post_thumbnail_id( get_queried_object_id() ) ) {
			$is_lcp = true; // No featured image and the first content image is not an attachment we can match: still the LCP.
		}

		if ( ! empty( $s['speed_decoding'] ) ) {
			$img = self::set_attr( $img, 'decoding', 'async' );
		}
		if ( ! empty( $s['speed_sizes'] ) ) {
			$img = self::fix_sizes_tag( $img );
		}
		if ( $is_lcp ) {
			$img            = self::set_attr( $img, 'fetchpriority', 'high' );
			$img            = self::set_attr( $img, 'loading', 'eager' );
			self::$lcp_done = true;
			if ( function_exists( 'wp_high_priority_element_flag' ) ) {
				wp_high_priority_element_flag( false );
			}
			return $img;
		}
		if ( ! empty( $s['speed_fetchpriority'] ) && self::$lcp_done ) {
			$img = self::remove_attr( $img, 'fetchpriority' ); // only one high-priority image per page
		}
		if ( ! empty( $s['speed_lazy'] ) ) {
			$img = self::set_attr( $img, 'loading', $eager ? 'eager' : 'lazy' );
		}
		return $img;
	}

	/**
	 * Images built by wp_get_attachment_image() (featured image, galleries, blocks).
	 */
	public static function attributes( $attr, $attachment, $size ) {
		if ( ! is_array( $attr ) || ! $attachment instanceof WP_Post ) {
			return $attr;
		}
		$s = ccpc_get_settings();
		if ( ! empty( $s['speed_decoding'] ) ) {
			$attr['decoding'] = 'async';
		}
		if ( ! empty( $s['speed_sizes'] ) && ! empty( $attr['sizes'] ) && is_string( $attr['sizes'] ) ) {
			$attr['sizes'] = self::fix_sizes_value( $attr['sizes'] );
		}
		$eager = ! empty( $attr['class'] ) && preg_match( '/\bct-hero\b/', (string) $attr['class'] );
		if ( ! empty( $s['speed_fetchpriority'] ) && ! self::$lcp_done && is_singular() && (int) $attachment->ID === self::lcp_id() && ! is_admin() ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading']       = 'eager';
			self::$lcp_done = true;
			if ( function_exists( 'wp_high_priority_element_flag' ) ) {
				wp_high_priority_element_flag( false );
			}
			return $attr;
		}
		if ( ! empty( $s['speed_fetchpriority'] ) && self::$lcp_done && isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
			unset( $attr['fetchpriority'] );
		}
		if ( ! empty( $s['speed_lazy'] ) ) {
			if ( $eager ) {
				$attr['loading'] = 'eager';
			} elseif ( ! isset( $attr['loading'] ) || false === $attr['loading'] ) {
				// Core leaves the first N (threshold) images eager; everything after is lazy.
				if ( function_exists( 'wp_increase_content_media_count' ) && wp_increase_content_media_count( 0 ) > self::threshold() ) {
					$attr['loading'] = 'lazy';
				}
			}
		}
		return $attr;
	}

	/**
	 * <link rel="preload" as="image"> for the LCP image, using the capped size and the WebP sibling
	 * when the request accepts it.
	 */
	public static function preload() {
		if ( ! is_singular() || is_feed() ) {
			return;
		}
		$id = self::lcp_id();
		if ( ! $id ) {
			return;
		}
		$src = wp_get_attachment_image_src( $id, 'full' ); // image_downsize is capped by CCPC_Serve.
		if ( ! $src ) {
			return;
		}
		$url = $src[0];
		if ( class_exists( 'CCPC_Serve' ) && ccpc_setting( 'serve_webp' ) ) {
			$url = CCPC_Serve::swap_url( $url );
		}
		$srcset = wp_get_attachment_image_srcset( $id, array( (int) $src[1], (int) $src[2] ) );
		$sizes  = wp_get_attachment_image_sizes( $id, array( (int) $src[1], (int) $src[2] ) );
		if ( $sizes && ccpc_setting( 'speed_sizes' ) ) {
			$sizes = self::fix_sizes_value( $sizes );
		}
		echo '<link rel="preload" as="image" href="' . esc_url( $url ) . '"'
			. ( $srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '' )
			. ( $srcset && $sizes ? ' imagesizes="' . esc_attr( $sizes ) . '"' : '' )
			. ' fetchpriority="high">' . "\n";
	}

	/* ---------- helpers ---------- */

	/**
	 * Compare image URLs regardless of ?v= and of the WebP/AVIF swap done by CCPC_Serve.
	 */
	protected static function norm_src( $url ) {
		$u = strtok( html_entity_decode( (string) $url ), '?' );
		return preg_replace( '/\.(jpe?g|png|webp|avif)$/i', '', $u );
	}

	protected static function set_attr( $tag, $name, $value ) {
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '="[^"]*"/i', $tag ) ) {
			return preg_replace( '/\s' . preg_quote( $name, '/' ) . '="[^"]*"/i', ' ' . $name . '="' . esc_attr( $value ) . '"', $tag, 1 );
		}
		return preg_replace( '/<img\b/i', '<img ' . $name . '="' . esc_attr( $value ) . '"', $tag, 1 );
	}

	protected static function remove_attr( $tag, $name ) {
		return preg_replace( '/\s' . preg_quote( $name, '/' ) . '="[^"]*"/i', '', $tag );
	}

	/**
	 * sizes="(max-width: NNNpx) 100vw, NNNpx" with NNN above the front-end cap → cap.
	 */
	public static function fix_sizes_value( $sizes ) {
		$cap = (int) ccpc_setting( 'front_cap' );
		if ( $cap <= 0 || ! is_string( $sizes ) ) {
			return $sizes;
		}
		return preg_replace_callback( '/^(auto,\s*)?\(max-width:\s*(\d+)px\)\s*100vw,\s*(\d+)px$/', function ( $m ) use ( $cap ) {
			$a = (int) $m[2];
			$b = (int) $m[3];
			if ( $a <= $cap && $b <= $cap ) {
				return $m[0];
			}
			return $m[1] . '(max-width: ' . min( $a, $cap ) . 'px) 100vw, ' . min( $b, $cap ) . 'px';
		}, trim( $sizes ) );
	}

	protected static function fix_sizes_tag( $tag ) {
		return preg_replace_callback( '/\ssizes="([^"]*)"/i', function ( $m ) {
			return ' sizes="' . esc_attr( self::fix_sizes_value( $m[1] ) ) . '"';
		}, $tag, 1 );
	}
}
