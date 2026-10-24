<?php
/**
 * Coco Tran — "Pair it with": related pieces under every piece.
 *
 * Appended after the content on the five CPTs + posts (before the booking box when the box is in the
 * content, else at the end): up to 4 items that share the piece's destination term — a different
 * format first (a Verdict pairs with the Guide and the Route, not with another Verdict), then the same
 * format, then the same pillar (post type) when the destination has too little — and, when the
 * destination has a hub (`ct_hub_url` meta, the ACF `hub` post object, or a Page whose slug matches the
 * destination), the hub takes the last slot. Each item: title, kicker (the format term), 12-word
 * excerpt. Cached per post in a transient keyed on the link-map version. Post meta `ct_pair_off` = 1
 * hides it on a piece. Markup: <nav class="ct-pair">, CSS inline once per page.
 *
 * Requires autolink.php (shared helpers). Filters: `ct_pair_max` (4), `ct_pair_items`, `ct_pair_css`,
 * `ct_format_taxonomies`, `ct_pair_heading`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Format taxonomies per post type (kicker source). Unknown types: the first registered taxonomy with "format" in its name. */
function ct_format_taxonomy( $post_type ) {
	$known = apply_filters(
		'ct_format_taxonomies',
		array(
			'guide'    => 'the-guide-format',
			'brief'    => 'brief_format',
			'dispatch' => 'dispatches-formats',
		)
	);
	if ( isset( $known[ $post_type ] ) && taxonomy_exists( $known[ $post_type ] ) ) {
		return $known[ $post_type ];
	}
	foreach ( get_object_taxonomies( $post_type, 'names' ) as $tax ) {
		if ( false !== strpos( $tax, 'format' ) ) {
			return $tax;
		}
	}
	return '';
}

/** The kicker for a piece: its format term name, else the post type's singular label ("Property" → "The Verdict"). */
function ct_piece_kicker( $post ) {
	$tax = ct_format_taxonomy( $post->post_type );
	if ( '' !== $tax ) {
		$terms = get_the_terms( $post, $tax );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			return $terms[0]->name;
		}
	}
	$labels = apply_filters(
		'ct_pillar_kickers',
		array(
			'property'  => 'The Verdict',
			'guide'     => 'The Guide',
			'brief'     => 'The Brief',
			'dispatch'  => 'The Dispatch',
			'shortlist' => 'The Shortlist',
			'post'      => 'From the archive',
			'page'      => 'Destination guide',
		)
	);
	if ( isset( $labels[ $post->post_type ] ) ) {
		return $labels[ $post->post_type ];
	}
	$obj = get_post_type_object( $post->post_type );
	return $obj ? $obj->labels->singular_name : '';
}

/** 12-word excerpt: ACF `short_answer`, else the excerpt, else the content. */
function ct_piece_excerpt( $post, $words = 12 ) {
	$text = trim( (string) get_post_meta( $post->ID, 'short_answer', true ) );
	if ( '' === $text && '' !== trim( (string) $post->post_excerpt ) ) {
		$text = $post->post_excerpt;
	}
	if ( '' === $text ) {
		$text = excerpt_remove_blocks( $post->post_content );
		$text = strip_shortcodes( $text );
	}
	$text = wp_strip_all_tags( $text, true );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return wp_trim_words( $text, (int) $words, '…' );
}

/** The destination hub for a piece, as [url, title] or null. */
function ct_piece_hub( $post, $dest_terms ) {
	$url = trim( (string) get_post_meta( $post->ID, 'ct_hub_url', true ) );
	if ( '' !== $url ) {
		$title = '';
		$id    = url_to_postid( $url );
		if ( $id ) {
			$title = get_the_title( $id );
		}
		return array( esc_url_raw( $url ), $title );
	}
	$hub = get_post_meta( $post->ID, 'hub', true ); // ACF post_object (Content system group)
	if ( is_array( $hub ) ) {
		$hub = reset( $hub );
	}
	if ( is_numeric( $hub ) && (int) $hub > 0 ) {
		$page = get_post( (int) $hub );
		if ( $page && 'publish' === $page->post_status ) {
			return array( get_permalink( $page ), get_the_title( $page ) );
		}
	}
	foreach ( $dest_terms as $term ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'name'           => $term->slug,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		if ( ! empty( $pages ) ) {
			return array( get_permalink( $pages[0] ), get_the_title( $pages[0] ) );
		}
	}
	return null;
}

/** Transient key for a post's Pair block (link-map version + Pair salt). */
function ct_pair_cache_key( $post_id ) {
	return 'ct_pair_' . (int) $post_id . '_' . substr( md5( ct_link_map_version() . '|' . (int) get_option( 'ct_pair_salt', 0 ) ), 0, 10 );
}

/** Drop every Pair transient (map update) — SQL on the options table, same as core's delete_expired_transients(). */
function ct_pair_flush_cache() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_ct\_pair\_%' OR option_name LIKE '\_transient\_timeout\_ct\_pair\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	wp_cache_flush_group( 'transient' );
	update_option( 'ct_pair_salt', time(), false );
}

/**
 * Compute the Pair items for a post (uncached). Each: ['url','title','kicker','excerpt','kind' => piece|hub].
 */
function ct_pair_compute( $post ) {
	$max   = max( 1, (int) apply_filters( 'ct_pair_max', 4 ) );
	$types = array_diff( ct_site_post_types(), array() );
	$tax   = ct_destination_taxonomy();
	$dests = ( '' !== $tax ) ? get_the_terms( $post, $tax ) : array();
	$dests = is_array( $dests ) ? $dests : array();

	$hub    = ! empty( $dests ) ? ct_piece_hub( $post, $dests ) : null;
	$budget = $max - ( $hub ? 1 : 0 );
	$picked = array();
	$ids    = array( $post->ID );

	$pull = function ( $args, $limit ) use ( &$picked, &$ids, $types ) {
		if ( $limit < 1 ) {
			return;
		}
		$q = new WP_Query(
			array_merge(
				array(
					'post_type'           => $types,
					'post_status'         => 'publish',
					'posts_per_page'      => $limit + 8,
					'post__not_in'        => $ids,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
				),
				$args
			)
		);
		$added = 0;
		foreach ( $q->posts as $p ) {
			if ( $added >= $limit ) {
				break;
			}
			if ( in_array( $p->ID, $ids, true ) ) {
				continue;
			}
			$ids[]    = $p->ID;
			$picked[] = $p;
			$added++;
		}
	};

	if ( ! empty( $dests ) ) {
		$term_ids = wp_list_pluck( $dests, 'term_id' );
		$tax_q    = array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $term_ids ) );
		// 1. same destination, a different format
		$pull( array( 'tax_query' => $tax_q, 'post_type' => array_values( array_diff( $types, array( $post->post_type ) ) ) ), $budget );
		// 2. same destination, same format
		$pull( array( 'tax_query' => $tax_q, 'post_type' => array( $post->post_type ) ), $budget - count( $picked ) );
	}
	// 3. same pillar (post type), newest
	if ( count( $picked ) < $budget && 'post' !== $post->post_type ) {
		$pull( array( 'post_type' => array( $post->post_type ) ), $budget - count( $picked ) );
	}

	$items = array();
	foreach ( array_slice( $picked, 0, $budget ) as $p ) {
		$items[] = array(
			'url'     => get_permalink( $p ),
			'title'   => get_the_title( $p ),
			'kicker'  => ct_piece_kicker( $p ),
			'excerpt' => ct_piece_excerpt( $p ),
			'kind'    => 'piece',
			'post_id' => $p->ID,
		);
	}
	if ( $hub && ! empty( $items ) ) {
		$items[] = array(
			'url'     => $hub[0],
			'title'   => '' !== $hub[1] ? $hub[1] : ( $dests[0]->name . ' travel guide' ),
			'kicker'  => 'Destination guide',
			'excerpt' => 'Every piece on ' . $dests[0]->name . ', in one place.',
			'kind'    => 'hub',
			'post_id' => 0,
		);
	}
	return (array) apply_filters( 'ct_pair_items', $items, $post );
}

/** Items for a post, cached 12 h in a transient keyed on the link-map version. */
function ct_pair_items( $post ) {
	$key   = ct_pair_cache_key( $post->ID );
	$items = get_transient( $key );
	if ( ! is_array( $items ) ) {
		$items = ct_pair_compute( $post );
		set_transient( $key, $items, 12 * HOUR_IN_SECONDS );
	}
	return $items;
}

function ct_pair_css_once() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="ct-pair-css">' . apply_filters( 'ct_pair_css', CT_PAIR_CSS ) . '</style>';
}

/** Render the block for a post; '' when there is nothing to pair. */
function ct_pair_render( $post ) {
	$items = ct_pair_items( $post );
	if ( empty( $items ) ) {
		return '';
	}
	$heading = apply_filters( 'ct_pair_heading', 'Pair it with', $post );
	$out     = ct_pair_css_once();
	$out    .= '<nav class="ct-pair" aria-label="' . esc_attr( $heading ) . '" data-ct-pair="' . count( $items ) . '">';
	$out    .= '<p class="ct-pair__kicker">' . esc_html( $heading ) . '</p><ul class="ct-pair__list">';
	foreach ( $items as $item ) {
		$out .= '<li class="ct-pair__item ct-pair__item--' . esc_attr( $item['kind'] ) . '">';
		$out .= '<a class="ct-pair__link" href="' . esc_url( $item['url'] ) . '">';
		if ( '' !== $item['kicker'] ) {
			$out .= '<span class="ct-pair__format">' . esc_html( $item['kicker'] ) . '</span>';
		}
		$out .= '<span class="ct-pair__title">' . esc_html( $item['title'] ) . '</span>';
		if ( '' !== $item['excerpt'] ) {
			$out .= '<span class="ct-pair__excerpt">' . esc_html( $item['excerpt'] ) . '</span>';
		}
		$out .= '</a></li>';
	}
	$out .= '</ul></nav>';
	return $out;
}

/** Is the Pair block off for this post (meta `ct_pair_off`)? */
function ct_pair_is_off( $post_id ) {
	$meta = get_post_meta( $post_id, 'ct_pair_off', true );
	return in_array( strtolower( trim( (string) $meta ) ), array( '1', 'true', 'yes', 'on' ), true );
}

/** the_content, priority 25 (after autolink at 20). */
function ct_pair_the_content( $content ) {
	static $done = array();
	if ( ! ct_is_main_singular_content() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, ct_site_post_types(), true ) || ct_pair_is_off( $post->ID ) || isset( $done[ $post->ID ] ) ) {
		return $content;
	}
	$done[ $post->ID ] = true;
	return ct_insert_before_booking_box( $content, ct_pair_render( $post ) );
}
add_filter( 'the_content', 'ct_pair_the_content', 25 );

/** A piece published, updated or trashed: its own Pair cache goes, and the salt invalidates the others. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array_merge( ct_site_post_types(), array( 'page' ) ), true ) ) {
			return;
		}
		if ( 'publish' !== $new && 'publish' !== $old ) {
			return;
		}
		update_option( 'ct_pair_salt', time(), false );
		if ( function_exists( 'ct_author_counts_flush' ) ) {
			ct_author_counts_flush();
		}
	},
	10,
	3
);

define( 'CT_PAIR_CSS', <<<'CSS'
.ct-pair{--ct-accent:var(--global-palette1,#8a6d3b);--ct-ink:var(--global-palette3,#1d1d1b);--ct-grey:var(--global-palette5,#6b6b66);--ct-hair:var(--global-palette7,#e3e0da);
  max-width:var(--ct-ao-max,720px);margin:2.5rem auto 0;padding:1.4rem 0 1.6rem;border-top:1px solid var(--ct-ink);font-family:var(--global-body-font-family,Georgia,serif);color:var(--ct-ink);}
.ct-pair .ct-pair__kicker{margin:0 0 .9rem;font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-variant-caps:all-small-caps;letter-spacing:.08em;font-size:.78rem;color:var(--ct-grey);}
.ct-pair .ct-pair__list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem 1.5rem;}
.ct-pair .ct-pair__item{margin:0;padding:0;}
.ct-pair .ct-pair__link{display:block;text-decoration:none;color:inherit;border-top:1px solid var(--ct-hair);padding-top:.7rem;}
.ct-pair .ct-pair__link:hover .ct-pair__title{color:var(--ct-accent);}
.ct-pair .ct-pair__format{display:block;font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-variant-caps:all-small-caps;letter-spacing:.08em;font-size:.72rem;color:var(--ct-accent);margin-bottom:.2rem;}
.ct-pair .ct-pair__title{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-size:1.05rem;line-height:1.3;transition:color .15s;}
.ct-pair .ct-pair__excerpt{display:block;margin-top:.3rem;font-size:.88rem;line-height:1.45;color:var(--ct-grey);}
.ct-pair .ct-pair__item--hub .ct-pair__link{border-top-color:var(--ct-ink);}
CSS
);
