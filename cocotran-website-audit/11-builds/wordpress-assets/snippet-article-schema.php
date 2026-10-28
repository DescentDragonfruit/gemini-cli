<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.  v1.0 · 25 Oct 2026
 * Article schema for the magazine post types (property, guide, brief, dispatch, shortlist).
 * Rank Math emits no Article node on these (schema type "None"), so Google saw no author and no
 * modified date on them even though classic posts carry both. This adds one Article node per piece:
 * headline, author → the Person node the Site Snippets plugin already prints (@id /about/#person),
 * datePublished, dateModified (ACF `updated_on` when set, else the WordPress modified date), image,
 * description (Rank Math description, else the short answer). Classic posts are untouched.
 */
add_filter( 'rank_math/json_ld', function ( $data, $jsonld ) {
	if ( ! is_singular() || ! is_array( $data ) ) { return $data; }
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'property', 'guide', 'brief', 'dispatch', 'shortlist' ), true ) ) { return $data; }
	$article_types = array( 'Article', 'NewsArticle', 'BlogPosting', 'TechArticle', 'Review', 'CreativeWork' );
	foreach ( $data as $entity ) {
		if ( is_array( $entity ) && ! empty( $entity['@type'] ) && array_intersect( (array) $entity['@type'], $article_types ) ) { return $data; } // Rank Math already has one
	}
	$url      = get_permalink( $post );
	$modified = function_exists( 'ct_updated_on_iso' ) ? ct_updated_on_iso( $post->ID ) : '';
	if ( '' === $modified ) { $modified = get_the_modified_date( 'c', $post ); }
	$desc = '';
	if ( class_exists( 'RankMath\\Helper' ) ) { $desc = (string) RankMath\Helper::get_post_meta( 'description', $post->ID ); }
	if ( '' === $desc ) { $desc = trim( wp_strip_all_tags( (string) get_post_meta( $post->ID, 'short_answer', true ) ) ); }
	if ( '' === $desc ) { $desc = trim( wp_strip_all_tags( (string) get_post_meta( $post->ID, 'comparison_short_answer', true ) ) ); }
	$person_id = function_exists( 'ct_person_id' ) ? ct_person_id() : home_url( '/about/#person' );
	$article = array(
		'@type'            => 'Article',
		'@id'              => $url . '#article',
		'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
		'url'              => $url,
		'mainEntityOfPage' => $url,
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => $modified,
		'author'           => array( '@id' => $person_id ),
		'publisher'        => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
		'inLanguage'       => get_bloginfo( 'language' ),
	);
	if ( '' !== $desc ) { $article['description'] = $desc; }
	$img = get_the_post_thumbnail_url( $post, 'full' );
	if ( $img ) { $article['image'] = $img; }
	$data['ctArticle'] = $article;
	return $data;
}, 50, 2 ); // before the plugin's paywall/dateModified pass at 99, which then also stamps this node
