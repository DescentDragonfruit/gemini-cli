<?php
/**
 * Coco Tran — images in ACF fields go into the Rank Math image sitemap.
 *
 * Rank Math's image sitemap only sees images inside post_content. On cocotran.com almost every
 * photograph lives in an ACF image field (image_1_left, image2, iamge7 …) on the property / guide /
 * brief / dispatch / shortlist CPTs, so the sitemap was blind to the photography. This adds every
 * attachment referenced by the post's meta (single IDs, arrays of IDs, Kadence gallery JSON) with
 * its title and alt, so Google Images can index them against the piece.
 *
 * Ships inside the Coco Tran Site Snippets plugin; harmless when Rank Math is absent.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rank_math/sitemap/urlimages', 'ctt_sitemap_acf_images', 10, 2 );

/**
 * @param array $images  [ [ 'src' => url, 'title' => '', 'alt' => '' ], … ]
 * @param int   $post_id Post ID.
 * @return array
 */
function ctt_sitemap_acf_images( $images, $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return $images;
	}
	$type = get_post_type( $post_id );
	$cpts = apply_filters( 'ctt_sitemap_image_post_types', array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'post', 'page' ) );
	if ( ! in_array( $type, $cpts, true ) ) {
		return $images;
	}

	$ids  = ctt_sitemap_collect_attachment_ids( $post_id );
	$seen = array();
	foreach ( (array) $images as $img ) {
		if ( ! empty( $img['src'] ) ) {
			$seen[ strtok( $img['src'], '?' ) ] = true;
		}
	}
	$max = (int) apply_filters( 'ctt_sitemap_max_images', 40 );
	foreach ( $ids as $id ) {
		if ( count( $images ) >= $max ) {
			break;
		}
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$src = wp_get_attachment_url( $id );
		if ( ! $src ) {
			continue;
		}
		$key = strtok( $src, '?' );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$alt      = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		$images[] = array(
			'src'   => $key,
			'title' => wp_strip_all_tags( get_the_title( $id ) ),
			'alt'   => wp_strip_all_tags( $alt ),
		);
	}
	return $images;
}

/**
 * Every attachment ID referenced by a post's meta: ACF image fields (int or array), ACF galleries
 * (array of ints), Kadence gallery / slider JSON ("id":123 or "ids":[…]), plus the featured image.
 *
 * @return int[]
 */
function ctt_sitemap_collect_attachment_ids( $post_id ) {
	$ids   = array();
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$ids[] = $thumb;
	}
	$meta = get_post_meta( $post_id );
	if ( ! is_array( $meta ) ) {
		return $ids;
	}
	foreach ( $meta as $key => $values ) {
		if ( '' === $key || '_' === $key[0] ) {
			continue; // ACF stores field keys under _name; skip internals and WP's own.
		}
		foreach ( (array) $values as $raw ) {
			$v = maybe_unserialize( $raw );
			if ( is_numeric( $v ) ) {
				$n = (int) $v;
				if ( $n > 0 && ctt_sitemap_key_looks_like_image( $key ) ) {
					$ids[] = $n;
				}
				continue;
			}
			if ( is_array( $v ) ) {
				foreach ( $v as $item ) {
					if ( is_numeric( $item ) && (int) $item > 0 && ctt_sitemap_key_looks_like_image( $key ) ) {
						$ids[] = (int) $item;
					} elseif ( is_array( $item ) && ! empty( $item['ID'] ) ) {
						$ids[] = (int) $item['ID'];
					}
				}
				continue;
			}
			if ( is_string( $v ) && false !== strpos( $v, '"id"' ) ) {
				if ( preg_match_all( '/"id"\s*:\s*(\d+)/', $v, $m ) ) {
					foreach ( $m[1] as $n ) {
						$ids[] = (int) $n;
					}
				}
			}
		}
	}
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	// Keep only real attachments (an int in a "room_count" field would otherwise sneak in).
	return array_values( array_filter( $ids, function ( $id ) {
		return 'attachment' === get_post_type( $id );
	} ) );
}

function ctt_sitemap_key_looks_like_image( $key ) {
	return (bool) preg_match( '/(image|img|photo|gallery|hero|iamge|thumbnail|cover)/i', $key );
}
