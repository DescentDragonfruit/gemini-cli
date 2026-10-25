<?php
/**
 * Coco Tran — expose the magazine post types, their taxonomies and their ACF
 * field groups in the REST API so the Studio Desk worker can read and build
 * drafts through /wp-json. Read permissions are unchanged: anonymous visitors
 * see only what the front end already shows; writes still need an
 * Application Password with edit rights. Nothing here changes templates,
 * slugs or permalinks.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'ct_rest_magazine_types' ) ) {
	function ct_rest_magazine_types() {
		return apply_filters( 'ct_rest_magazine_types', array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'roundup', 'route', 'dossier', 'primer', 'postcard' ) );
	}
}

add_filter( 'register_post_type_args', function ( $args, $post_type ) {
	if ( in_array( $post_type, ct_rest_magazine_types(), true ) ) {
		$args['show_in_rest'] = true;
		if ( empty( $args['rest_base'] ) ) { $args['rest_base'] = $post_type; }
		if ( empty( $args['rest_controller_class'] ) ) { $args['rest_controller_class'] = 'WP_REST_Posts_Controller'; }
		if ( empty( $args['supports'] ) || ! in_array( 'custom-fields', (array) $args['supports'], true ) ) {
			$args['supports'] = array_values( array_unique( array_merge( (array) ( $args['supports'] ?? array() ), array( 'custom-fields' ) ) ) );
		}
	}
	return $args;
}, 20, 2 );

add_filter( 'register_taxonomy_args', function ( $args, $taxonomy, $object_type ) {
	$object_type = (array) $object_type;
	if ( array_intersect( $object_type, ct_rest_magazine_types() ) || in_array( $taxonomy, array( 'destinations', 'destination', 'brief_format', 'dispatch_format', 'collection', 'collections', 'region' ), true ) ) {
		$args['show_in_rest'] = true;
		if ( empty( $args['rest_base'] ) ) { $args['rest_base'] = $taxonomy; }
	}
	return $args;
}, 20, 3 );

/* ACF: field groups whose location rules target a magazine type are served
 * under the post's "acf" key, and ACF's REST support is on. Groups that do
 * not target those types are left exactly as configured. */
add_filter( 'acf/settings/rest_api_enabled', '__return_true' );
add_filter( 'acf/settings/rest_api_format', function ( $f ) { return $f ?: 'light'; } );
add_filter( 'acf/load_field_group', function ( $group ) {
	if ( empty( $group['location'] ) || ! empty( $group['show_in_rest'] ) ) { return $group; }
	foreach ( (array) $group['location'] as $and_rules ) {
		foreach ( (array) $and_rules as $rule ) {
			if ( ( $rule['param'] ?? '' ) === 'post_type' && ( $rule['operator'] ?? '' ) === '==' && in_array( $rule['value'] ?? '', ct_rest_magazine_types(), true ) ) {
				$group['show_in_rest'] = 1;
				return $group;
			}
		}
	}
	return $group;
}, 20 );
