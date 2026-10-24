<?php
/**
 * Coco Tran — the author block and the Person schema.
 *
 * `[ct_author]` shortcode, and an automatic append under the "Pair it with" block on the five CPTs:
 * "Coco Tran · Photos & words", the role, the one-sentence bio (option `ct_author_bio`), the counts
 * ("{n} properties stayed, {m} guides" from published CPT counts, cached), links to /about/,
 * /how-we-review/, Instagram and YouTube (options `ct_social_instagram`, `ct_social_youtube`), and the
 * destinations the piece is filed under. Post meta `ct_author_off` = 1 hides it on a piece.
 *
 * Schema: a Person node (@id /about/#person) with name, url, jobTitle, description, sameAs and
 * knowsAbout — every value of which is visible in the block — and `author` on Rank Math's Article /
 * Review node through the same `rank_math/json_ld` filter paywall-schema-snippet.php uses. Without
 * Rank Math (or when Rank Math emitted no graph on the page) the Person goes out as its own
 * application/ld+json in wp_footer. Nothing is emitted on a page where the block is not rendered.
 *
 * Requires autolink.php (shared helpers). Filters: `ct_author_name`, `ct_author_job_title`,
 * `ct_author_links`, `ct_person_schema`, `ct_author_css`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ct_author_name() {
	return (string) apply_filters( 'ct_author_name', 'Coco Tran' );
}

function ct_author_job_title() {
	return (string) apply_filters( 'ct_author_job_title', 'Luxury travel advisor & photographer' );
}

function ct_author_about_url() {
	return home_url( '/about/' );
}

function ct_person_id() {
	return ct_author_about_url() . '#person';
}

/** Social URLs from options, only the ones set. */
function ct_author_social() {
	$out = array();
	foreach ( array( 'instagram' => 'ct_social_instagram', 'youtube' => 'ct_social_youtube' ) as $k => $opt ) {
		$url = esc_url_raw( trim( (string) get_option( $opt, '' ) ) );
		if ( '' !== $url ) {
			$out[ $k ] = $url;
		}
	}
	return $out;
}

/** Published counts for the line, cached 12 h. */
function ct_author_counts() {
	$counts = get_transient( 'ct_author_counts' );
	if ( ! is_array( $counts ) ) {
		$counts = array( 'properties' => 0, 'guides' => 0 );
		if ( post_type_exists( 'property' ) ) {
			$c                    = wp_count_posts( 'property' );
			$counts['properties'] = isset( $c->publish ) ? (int) $c->publish : 0;
		}
		if ( post_type_exists( 'guide' ) ) {
			$c                = wp_count_posts( 'guide' );
			$counts['guides'] = isset( $c->publish ) ? (int) $c->publish : 0;
		}
		set_transient( 'ct_author_counts', $counts, 12 * HOUR_IN_SECONDS );
	}
	return $counts;
}

function ct_author_counts_flush() {
	delete_transient( 'ct_author_counts' );
}

/** The "{n} properties stayed, {m} guides" phrase, or '' when both are zero. */
function ct_author_counts_line() {
	$c     = ct_author_counts();
	$parts = array();
	if ( $c['properties'] > 0 ) {
		$parts[] = number_format_i18n( $c['properties'] ) . ' ' . _n( 'property stayed', 'properties stayed', $c['properties'], 'coco-tran' );
	}
	if ( $c['guides'] > 0 ) {
		$parts[] = number_format_i18n( $c['guides'] ) . ' ' . _n( 'guide', 'guides', $c['guides'], 'coco-tran' );
	}
	return implode( ', ', $parts );
}

/** Destination term names of a piece (visible in the block, and knowsAbout in the schema). */
function ct_piece_destinations( $post ) {
	$tax = function_exists( 'ct_destination_taxonomy' ) ? ct_destination_taxonomy() : '';
	if ( '' === $tax || ! $post ) {
		return array();
	}
	$terms = get_the_terms( $post, $tax );
	return is_array( $terms ) ? array_values( array_unique( wp_list_pluck( $terms, 'name' ) ) ) : array();
}

/** Everything the block shows, so the schema can mirror exactly that. */
function ct_author_data( $post = null ) {
	$social = ct_author_social();
	$links  = array(
		array( 'About', ct_author_about_url() ),
		array( 'How we review', home_url( '/how-we-review/' ) ),
	);
	if ( isset( $social['instagram'] ) ) {
		$links[] = array( 'Instagram', $social['instagram'] );
	}
	if ( isset( $social['youtube'] ) ) {
		$links[] = array( 'YouTube', $social['youtube'] );
	}
	return array(
		'name'         => ct_author_name(),
		'job_title'    => ct_author_job_title(),
		'bio'          => trim( wp_strip_all_tags( (string) get_option( 'ct_author_bio', '' ) ) ),
		'counts'       => ct_author_counts_line(),
		'about_url'    => ct_author_about_url(),
		'links'        => (array) apply_filters( 'ct_author_links', $links, $post ),
		'social'       => $social,
		'destinations' => $post ? ct_piece_destinations( $post ) : array(),
	);
}

function ct_author_css_once() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="ct-author-css">' . apply_filters( 'ct_author_css', CT_AUTHOR_CSS ) . '</style>';
}

/** Remember that the block went out on this page (the footer schema checks it). */
function ct_author_rendered( $set = null ) {
	static $rendered = false;
	if ( null !== $set ) {
		$rendered = (bool) $set;
	}
	return $rendered;
}

/** Render the block. */
function ct_author_render( $post = null ) {
	$d = ct_author_data( $post );
	ct_author_rendered( true );
	$out  = ct_author_css_once();
	$out .= '<aside class="ct-author" data-ct-author="1">';
	$out .= '<p class="ct-author__line"><a class="ct-author__name" href="' . esc_url( $d['about_url'] ) . '" rel="author">' . esc_html( $d['name'] ) . '</a> <span class="ct-author__sep">·</span> <span class="ct-author__role">Photos &amp; words</span></p>';
	$out .= '<p class="ct-author__job">' . esc_html( $d['job_title'] ) . '</p>';
	if ( '' !== $d['bio'] ) {
		$out .= '<p class="ct-author__bio">' . esc_html( $d['bio'] ) . '</p>';
	}
	$meta = array();
	if ( '' !== $d['counts'] ) {
		$meta[] = '<span class="ct-author__counts">' . esc_html( $d['counts'] ) . '</span>';
	}
	if ( ! empty( $d['destinations'] ) ) {
		$meta[] = '<span class="ct-author__knows">Filed under ' . esc_html( implode( ', ', $d['destinations'] ) ) . '</span>';
	}
	if ( $meta ) {
		$out .= '<p class="ct-author__meta">' . implode( ' <span class="ct-author__sep">·</span> ', $meta ) . '</p>';
	}
	$out .= '<p class="ct-author__links">';
	$n    = 0;
	foreach ( $d['links'] as $link ) {
		$external = 0 !== strpos( $link[1], home_url() );
		$out     .= ( $n++ ? ' <span class="ct-author__sep">·</span> ' : '' )
			. '<a href="' . esc_url( $link[1] ) . '"' . ( $external ? ' rel="me noopener" target="_blank"' : '' ) . '>' . esc_html( $link[0] ) . '</a>';
	}
	$out .= '</p></aside>';
	return $out;
}

/** [ct_author] */
add_action(
	'init',
	function () {
		add_shortcode(
			'ct_author',
			function () {
				return ct_author_render( get_post() );
			}
		);
	}
);

/** Post types that get the block automatically (the five CPTs, not posts). */
function ct_author_post_types() {
	return (array) apply_filters( 'ct_author_post_types', array( 'property', 'guide', 'brief', 'dispatch', 'shortlist' ) );
}

function ct_author_is_off( $post_id ) {
	$meta = get_post_meta( $post_id, 'ct_author_off', true );
	return in_array( strtolower( trim( (string) $meta ) ), array( '1', 'true', 'yes', 'on' ), true );
}

/** Will the block be on the page for this post (auto append, or the shortcode in its content)? */
function ct_author_will_render( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	if ( has_shortcode( (string) $post->post_content, 'ct_author' ) ) {
		return true;
	}
	return in_array( $post->post_type, ct_author_post_types(), true ) && ! ct_author_is_off( $post->ID );
}

/** the_content, priority 26: under the Pair block, before the booking box. */
function ct_author_the_content( $content ) {
	static $done = array();
	if ( ! function_exists( 'ct_is_main_singular_content' ) || ! ct_is_main_singular_content() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, ct_author_post_types(), true ) || ct_author_is_off( $post->ID ) || isset( $done[ $post->ID ] ) ) {
		return $content;
	}
	if ( has_shortcode( (string) $post->post_content, 'ct_author' ) || false !== strpos( $content, 'data-ct-author="1"' ) ) {
		return $content; // placed by hand
	}
	$done[ $post->ID ] = true;
	return ct_insert_before_booking_box( $content, ct_author_render( $post ) );
}
add_filter( 'the_content', 'ct_author_the_content', 26 );

/* -------------------------------------------------------------------------
 * Schema
 * ---------------------------------------------------------------------- */

/** The Person node, mirroring the block exactly. */
function ct_person_schema( $post = null ) {
	$d      = ct_author_data( $post );
	$person = array(
		'@type'    => 'Person',
		'@id'      => ct_person_id(),
		'name'     => $d['name'],
		'url'      => $d['about_url'],
		'jobTitle' => $d['job_title'],
	);
	if ( '' !== $d['bio'] ) {
		$person['description'] = $d['bio'];
	}
	if ( ! empty( $d['social'] ) ) {
		$person['sameAs'] = array_values( $d['social'] );
	}
	if ( ! empty( $d['destinations'] ) ) {
		$person['knowsAbout'] = $d['destinations'];
	}
	return (array) apply_filters( 'ct_person_schema', $person, $post );
}

/** Did our Rank Math filter put the Person into Rank Math's graph on this page? */
function ct_person_in_rank_math( $set = null ) {
	static $in = false;
	if ( null !== $set ) {
		$in = (bool) $set;
	}
	return $in;
}

/**
 * rank_math/json_ld (priority 100, after the paywall filter at 99): add the Person and point the
 * Article / Review `author` at it.
 */
add_filter(
	'rank_math/json_ld',
	function ( $data, $jsonld = null ) {
		if ( ! is_singular() || ! is_array( $data ) ) {
			return $data;
		}
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! ct_author_will_render( $post ) ) {
			return $data;
		}
		$person = ct_person_schema( $post );
		$ref    = array( '@type' => 'Person', '@id' => $person['@id'], 'name' => $person['name'] );
		$types  = array( 'Article', 'NewsArticle', 'BlogPosting', 'TechArticle', 'Review', 'CreativeWork' );
		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
				continue;
			}
			if ( array_intersect( (array) $entity['@type'], $types ) ) {
				$data[ $key ]['author'] = $ref;
			}
		}
		$data['ct_person'] = $person;
		ct_person_in_rank_math( true );
		return $data;
	},
	100,
	2
);

/** Standalone Person graph when the block is on the page and Rank Math did not carry it. */
add_action(
	'wp_footer',
	function () {
		if ( ! ct_author_rendered() || ct_person_in_rank_math() ) {
			return;
		}
		$post = is_singular() ? get_queried_object() : null;
		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array( ct_person_schema( $post instanceof WP_Post ? $post : null ) ),
		);
		if ( $post instanceof WP_Post && ! defined( 'RANK_MATH_VERSION' ) ) {
			// No Rank Math at all: a minimal Article (title, URL, dates — all visible) so the author linkage still exists.
			$graph['@graph'][] = array(
				'@type'    => 'Article',
				'@id'      => get_permalink( $post ) . '#ct-article',
				'headline' => wp_strip_all_tags( get_the_title( $post ) ),
				'url'      => get_permalink( $post ),
				'datePublished' => get_post_time( DATE_ATOM, false, $post ),
				'dateModified'  => get_post_modified_time( DATE_ATOM, false, $post ),
				'author'   => array( '@id' => ct_person_id() ),
			);
		}
		echo "\n" . '<script type="application/ld+json" id="ct-person-schema">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	},
	20
);

define( 'CT_AUTHOR_CSS', <<<'CSS'
.ct-author{--ct-accent:var(--global-palette1,#8a6d3b);--ct-ink:var(--global-palette3,#1d1d1b);--ct-grey:var(--global-palette5,#6b6b66);--ct-hair:var(--global-palette7,#e3e0da);
  max-width:var(--ct-ao-max,720px);margin:1.5rem auto 2.5rem;padding:1.2rem 0 0;border-top:1px solid var(--ct-hair);font-family:var(--global-body-font-family,Georgia,serif);color:var(--ct-ink);font-size:.95rem;line-height:1.5;}
.ct-author p{margin:0 0 .35rem;}
.ct-author .ct-author__line{font-family:var(--global-heading-font-family,Georgia,serif);font-size:1.1rem;}
.ct-author .ct-author__name{color:inherit;text-decoration:none;border-bottom:1px solid var(--ct-accent);}
.ct-author .ct-author__role,.ct-author .ct-author__job,.ct-author .ct-author__meta{font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-variant-caps:all-small-caps;letter-spacing:.06em;font-size:.78rem;color:var(--ct-grey);}
.ct-author .ct-author__bio{margin:.5rem 0;}
.ct-author .ct-author__sep{color:var(--ct-hair);}
.ct-author .ct-author__links{margin-top:.5rem;font-size:.88rem;}
.ct-author .ct-author__links a{color:var(--ct-ink);text-decoration:none;border-bottom:1px solid var(--ct-hair);}
.ct-author .ct-author__links a:hover{border-bottom-color:var(--ct-accent);color:var(--ct-accent);}
CSS
);
