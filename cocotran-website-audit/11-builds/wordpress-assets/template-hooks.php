<?php
/**
 * Coco Tran — Kadence template hooks.
 *
 * The magazine post types (property, guide, brief, dispatch, shortlist) render
 * their ACF fields through Kadence Elements and never print `the_content`, so
 * the autolink / Pair it with / author filters in this plugin never ran on
 * them (seen 25 Oct 2026 on every published piece). This file:
 *
 *   1. autolinks the ACF WYSIWYG fields of the piece being viewed, with one
 *      link budget per piece across all its fields;
 *   2. prints "Pair it with" and the author block after the content area via
 *      Kadence's single-content hooks, once per request, unless `the_content`
 *      already did (classic posts, or a piece with real post_content).
 *
 * Nothing changes for classic posts. Post meta `ct_pair_off`, `ct_author_off`
 * and `ct_autolink_off` are honoured exactly as before.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 1. Autolink inside ACF WYSIWYG fields of the main singular piece. */
add_filter( 'acf/format_value/type=wysiwyg', function ( $value, $post_id, $field ) {
	if ( ! is_string( $value ) || '' === trim( $value ) || ! function_exists( 'ct_autolink_plan' ) ) { return $value; }
	if ( ! ct_is_main_singular_content() ) { return $value; }
	$pid = (int) $post_id;
	if ( ! $pid || $pid !== (int) get_queried_object_id() ) { return $value; }
	if ( ! in_array( get_post_type( $pid ), ct_site_post_types(), true ) || ct_autolink_is_off( $pid ) ) { return $value; }
	if ( false !== strpos( $value, 'data-ct-autolink=' ) ) { return $value; }
	static $budget = array();
	if ( ! isset( $budget[ $pid ] ) ) { $budget[ $pid ] = ct_autolink_max(); }
	if ( $budget[ $pid ] < 1 ) { return $value; }
	$html = false === stripos( $value, '<p' ) ? wpautop( $value ) : $value;
	$plan = ct_autolink_plan( $html, $pid );
	if ( empty( $plan['links'] ) ) { return $value; }
	$out = ct_autolink_apply( $plan );
	if ( '' === $out ) { return $value; }
	$budget[ $pid ] -= count( $plan['links'] );
	return $out;
}, 20, 3 );

/* 2. Pair it with + author block on Kadence single templates. */
function ct_kadence_tail() {
	static $printed = false;
	if ( $printed ) { return; }
	if ( ! function_exists( 'ct_is_main_singular_content' ) || ! ct_is_main_singular_content() ) { return; }
	$post = get_queried_object();
	if ( ! ( $post instanceof WP_Post ) || 'post' === $post->post_type || ! in_array( $post->post_type, ct_site_post_types(), true ) ) { return; }
	$printed = true;
	$out = '';
	if ( function_exists( 'ct_pair_render' ) && ! ct_pair_is_off( $post->ID ) && empty( $GLOBALS['ct_tail_done'][ $post->ID ]['pair'] ) ) {
		$GLOBALS['ct_tail_done'][ $post->ID ]['pair'] = true;
		$out .= ct_pair_render( $post );
	}
	if ( function_exists( 'ct_author_render' ) && function_exists( 'ct_author_is_off' ) && ! ct_author_is_off( $post->ID ) && empty( $GLOBALS['ct_tail_done'][ $post->ID ]['author'] ) ) {
		$GLOBALS['ct_tail_done'][ $post->ID ]['author'] = true;
		$out .= ct_author_render( $post );
	}
	if ( '' !== $out ) {
		echo '<div class="ct-tail content-area" style="max-width:var(--kb-container-max-width,1200px);margin:2.5rem auto 0;padding:0 var(--global-content-edge-padding,1.5rem);box-sizing:border-box">' . $out . '</div>';
	}
}
add_action( 'kadence_single_after_content', 'ct_kadence_tail', 20 );
add_action( 'kadence_after_content', 'ct_kadence_tail', 5 );
