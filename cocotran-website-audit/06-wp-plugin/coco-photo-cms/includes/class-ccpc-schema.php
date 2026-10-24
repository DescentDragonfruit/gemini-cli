<?php
/**
 * Alt enforcement on output, credit lines on captions, ImageObject JSON-LD.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCPC_Schema {

	public static function init() {
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'attributes' ), 10, 2 );
		if ( ccpc_setting( 'show_credit' ) ) {
			add_filter( 'render_block_core/image', array( __CLASS__, 'credit_block' ), 10, 2 );
			add_filter( 'img_caption_shortcode', array( __CLASS__, 'credit_shortcode' ), 10, 3 );
		}
		if ( ccpc_setting( 'schema_enabled' ) ) {
			add_action( 'wp_head', array( __CLASS__, 'jsonld' ), 30 );
		}
	}

	public static function attributes( $attr, $attachment ) {
		if ( empty( $attr['alt'] ) ) {
			$alt = (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
			if ( '' === trim( $alt ) ) {
				$alt = CCPC_Naming::auto_alt( $attachment->ID ); // Falls back to fields; '' if none (decorative is acceptable).
			}
			$attr['alt'] = $alt;
		}
		return $attr;
	}

	/**
	 * Credit text for an attachment ('' if none set).
	 */
	public static function credit_for( $attachment_id ) {
		$c = (string) get_post_meta( $attachment_id, '_ccpc_credit', true );
		return trim( $c );
	}

	public static function credit_block( $html, $block ) {
		$id = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;
		if ( ! $id ) {
			return $html;
		}
		$credit = self::credit_for( $id );
		if ( ! $credit || false !== strpos( $html, 'ccpc-credit' ) ) {
			return $html;
		}
		$span = ' <span class="ccpc-credit">' . esc_html( $credit ) . '</span>';
		if ( false !== strpos( $html, '</figcaption>' ) ) {
			return str_replace( '</figcaption>', $span . '</figcaption>', $html );
		}
		return str_replace( '</figure>', '<figcaption class="wp-element-caption">' . trim( $span ) . '</figcaption></figure>', $html );
	}

	public static function credit_shortcode( $output, $attr, $content ) {
		if ( empty( $attr['id'] ) ) {
			return $output;
		}
		$id     = (int) str_replace( 'attachment_', '', $attr['id'] );
		$credit = self::credit_for( $id );
		if ( ! $credit ) {
			return $output;
		}
		$attr['caption'] = ( isset( $attr['caption'] ) ? $attr['caption'] . ' ' : '' ) . '<span class="ccpc-credit">' . esc_html( $credit ) . '</span>';
		remove_filter( 'img_caption_shortcode', array( __CLASS__, 'credit_shortcode' ), 10 );
		$out = img_caption_shortcode( $attr, $content );
		add_filter( 'img_caption_shortcode', array( __CLASS__, 'credit_shortcode' ), 10, 3 );
		return $out;
	}

	/**
	 * Attachment IDs used on the current singular post: featured first, then content, then ACF.
	 */
	public static function post_image_ids( $post_id, $max ) {
		$ids = array();
		$t   = get_post_thumbnail_id( $post_id );
		if ( $t ) {
			$ids[] = (int) $t;
		}
		$content = (string) get_post_field( 'post_content', $post_id );
		if ( preg_match_all( '/wp-image-(\d+)/', $content, $m ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
		}
		if ( preg_match_all( '/"id":(\d+)[,}]/', $content, $m ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
		}
		if ( function_exists( 'get_fields' ) ) {
			$fields = get_fields( $post_id );
			if ( is_array( $fields ) ) {
				foreach ( $fields as $k => $v ) {
					if ( 0 === strpos( $k, 'image' ) || 0 === strpos( $k, 'iamge' ) ) {
						if ( is_numeric( $v ) ) {
							$ids[] = (int) $v;
						} elseif ( is_array( $v ) && ! empty( $v['ID'] ) ) {
							$ids[] = (int) $v['ID'];
						}
					}
				}
			}
		}
		$ids = array_values( array_unique( array_filter( $ids, function ( $i ) {
			return $i > 0 && wp_attachment_is_image( $i );
		} ) ) );
		return array_slice( $ids, 0, max( 1, (int) $max ) );
	}

	public static function image_object( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			return null;
		}
		$s   = ccpc_get_settings();
		$obj = array(
			'@type'      => 'ImageObject',
			'@id'        => strtok( $src[0], '?' ) . '#image',
			'contentUrl' => $src[0],
			'url'        => $src[0],
		);
		if ( ! empty( $src[1] ) ) {
			$obj['width']  = (int) $src[1];
			$obj['height'] = (int) $src[2];
		}
		$add = function ( $k, $v ) use ( &$obj ) {
			$v = is_string( $v ) ? trim( $v ) : $v;
			if ( '' !== $v && null !== $v ) {
				$obj[ $k ] = $v;
			}
		};
		$add( 'name', get_the_title( $id ) );
		$add( 'description', get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$add( 'caption', wp_get_attachment_caption( $id ) );
		$add( 'creditText', self::credit_for( $id ) );
		$add( 'copyrightNotice', $s['copyright_notice'] );
		$add( 'license', $s['license_url'] );
		$add( 'acquireLicensePage', $s['license_url'] );
		$add( 'datePublished', get_the_date( 'c', $id ) );
		$obj['creator'] = array( '@type' => 'Person', 'name' => 'Coco Tran', '@id' => home_url( '/#coco-tran' ) );
		$place          = (string) get_post_meta( $id, '_ccpc_place', true );
		if ( '' !== trim( $place ) ) {
			$obj['contentLocation'] = array( '@type' => 'Place', 'name' => $place );
		}
		$visited = (string) get_post_meta( $id, '_ccpc_visited', true );
		if ( '' !== trim( $visited ) ) {
			$obj['dateCreated'] = $visited;
		}
		return $obj;
	}

	public static function jsonld() {
		if ( ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		$ids     = self::post_image_ids( $post_id, ccpc_setting( 'schema_max_images' ) );
		if ( ! $ids ) {
			return;
		}
		$graph = array();
		foreach ( $ids as $id ) {
			$o = self::image_object( $id );
			if ( $o ) {
				$graph[] = $o;
			}
		}
		if ( ! $graph ) {
			return;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
