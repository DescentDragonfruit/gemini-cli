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

/* Fallback: if a type or taxonomy was registered before this plugin loaded
 * (mu-plugin or theme code calling register_post_type at file load), the
 * args filters above never saw it. Re-register it late with the same
 * arguments plus REST support. register_post_type() on an existing name
 * replaces the registration in place; slugs, rewrites and templates stay. */
add_action( 'init', function () {
	foreach ( ct_rest_magazine_types() as $type ) {
		$obj = get_post_type_object( $type );
		if ( ! $obj || ! empty( $obj->show_in_rest ) ) { continue; }
		$args = get_object_vars( $obj );
		unset( $args['name'], $args['cap'], $args['label'], $args['labels'], $args['rest_controller'], $args['revisions_rest_controller'], $args['autosave_rest_controller'], $args['template'], $args['template_lock'] );
		$args['labels']       = (array) $obj->labels;
		$args['label']        = $obj->label;
		$args['show_in_rest'] = true;
		$args['rest_base']    = $type;
		$args['rest_controller_class'] = 'WP_REST_Posts_Controller';
		$args['supports']     = array_values( array_unique( array_merge( array_keys( get_all_post_type_supports( $type ) ), array( 'custom-fields' ) ) ) );
		$args['taxonomies']   = get_object_taxonomies( $type );
		if ( isset( $args['rewrite'] ) && is_object( $args['rewrite'] ) ) { $args['rewrite'] = (array) $args['rewrite']; }
		register_post_type( $type, $args );
	}
	foreach ( get_taxonomies( array(), 'objects' ) as $tax ) {
		if ( ! empty( $tax->show_in_rest ) || $tax->_builtin ) { continue; }
		$types = (array) $tax->object_type;
		if ( ! array_intersect( $types, ct_rest_magazine_types() ) && ! in_array( $tax->name, array( 'destinations', 'destination', 'brief_format', 'dispatch_format', 'collection', 'collections', 'region' ), true ) ) { continue; }
		$args = get_object_vars( $tax );
		unset( $args['name'], $args['object_type'], $args['cap'], $args['label'], $args['labels'], $args['rest_controller'] );
		$args['labels']       = (array) $tax->labels;
		$args['label']        = $tax->label;
		$args['show_in_rest'] = true;
		$args['rest_base']    = $tax->name;
		if ( isset( $args['rewrite'] ) && is_object( $args['rewrite'] ) ) { $args['rewrite'] = (array) $args['rewrite']; }
		register_taxonomy( $tax->name, $types, $args );
	}
}, 999 );

/* Read-only diagnostic for the Studio Desk worker: which types and
 * taxonomies exist and whether REST sees them. Admins only. */
add_action( 'rest_api_init', function () {
	register_rest_route( 'coco-tran/v1', '/types', array(
		'methods'             => 'GET',
		'permission_callback' => function () { return current_user_can( 'edit_others_posts' ); },
		'callback'            => function () {
			$out = array( 'post_types' => array(), 'taxonomies' => array(), 'acf' => function_exists( 'acf_get_field_groups' ) );
			foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $t ) {
				$out['post_types'][ $t->name ] = array( 'show_in_rest' => (bool) $t->show_in_rest, 'rest_base' => $t->rest_base ?: null, 'public' => (bool) $t->public, 'taxonomies' => get_object_taxonomies( $t->name ) );
			}
			foreach ( get_taxonomies( array( '_builtin' => false ), 'objects' ) as $x ) {
				$out['taxonomies'][ $x->name ] = array( 'show_in_rest' => (bool) $x->show_in_rest, 'rest_base' => $x->rest_base ?: null, 'types' => (array) $x->object_type, 'hierarchical' => (bool) $x->hierarchical );
			}
			if ( function_exists( 'acf_get_field_groups' ) ) {
				$out['acf_groups'] = array();
				foreach ( acf_get_field_groups() as $g ) { $out['acf_groups'][] = array( 'key' => $g['key'], 'title' => $g['title'], 'show_in_rest' => ! empty( $g['show_in_rest'] ), 'location' => $g['location'] ); }
			}
			return apply_filters( 'ct_types_diagnostic', $out );
		},
	) );
} );
