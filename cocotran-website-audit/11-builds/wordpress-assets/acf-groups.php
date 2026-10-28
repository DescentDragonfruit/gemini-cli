<?php
/**
 * Coco Tran — ACF field groups shipped with the plugin.
 *
 * On every load after an upgrade, once ACF is ready, any field group in
 * ./acf/*.json that is not yet registered on the site is imported exactly as
 * ACF → Tools → Import would do it (persisted as a field-group post, editable
 * in the ACF UI). Groups that already exist are never touched, so this is
 * safe to run on every request; it short-circuits on an option flag so it
 * costs nothing after the first pass for a given plugin version.
 *
 * Shipped: Answerable opening (short answer, glance rows, visited, visit
 * basis, updated on, disclosure), Content system, Booking box, Comparison.
 * Diagnostic: GET /wp-json/coco-tran/v1/types → acf_groups.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'acf/init', function () {
	$ver = '1.2.5';
	if ( get_option( 'ct_acf_groups_ver' ) === $ver ) { return; }
	if ( ! function_exists( 'acf_get_field_group' ) || ! function_exists( 'acf_import_field_group' ) ) { return; }
	$done = array();
	foreach ( glob( __DIR__ . '/acf/*.json' ) as $file ) {
		$data = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $data ) ) { continue; }
		$groups = isset( $data['key'] ) ? array( $data ) : $data;
		foreach ( $groups as $group ) {
			if ( empty( $group['key'] ) || empty( $group['fields'] ) ) { continue; }
			if ( acf_get_field_group( $group['key'] ) ) { $done[] = $group['key'] . ':found'; continue; }
			if ( function_exists( 'acf_get_field_group_post' ) ) {
				$post = acf_get_field_group_post( $group['key'] );
				if ( $post ) { $group['ID'] = $post->ID; }
			}
			$imported = acf_import_field_group( $group );
			$done[] = $group['key'] . ( is_array( $imported ) && ! empty( $imported['ID'] ) ? ':imported#' . $imported['ID'] : ':failed' );
		}
	}
	update_option( 'ct_acf_groups_ver', $ver, false );
	update_option( 'ct_acf_groups_log', array( 'at' => current_time( 'mysql' ), 'result' => $done ), false );
}, 20 );

/* Expose the import log on the diagnostic route. */
add_filter( 'ct_types_diagnostic', function ( $out ) {
	$out['acf_import_log'] = get_option( 'ct_acf_groups_log' );
	return $out;
} );
