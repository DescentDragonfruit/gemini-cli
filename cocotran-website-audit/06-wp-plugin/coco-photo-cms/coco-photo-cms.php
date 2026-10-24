<?php
/**
 * Plugin Name: Coco Photo CMS
 * Plugin URI:  https://cocotran.com
 * Description: The photo layer WordPress doesn't have: compress and convert on upload, descriptive filenames, required alt text and credits, rename with redirects, replace an image everywhere or in one place, ImageObject schema, and a REST intake for images pushed from Claude artifacts.
 * Version:     1.0.0
 * Author:      Coco Tran
 * License:     GPL-2.0-or-later
 * Text Domain: coco-photo-cms
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCPC_VERSION', '1.0.0' );
define( 'CCPC_FILE', __FILE__ );
define( 'CCPC_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCPC_URL', plugin_dir_url( __FILE__ ) );
define( 'CCPC_OPTION', 'ccpc_settings' );
define( 'CCPC_REDIRECTS_OPTION', 'ccpc_redirects' );

/**
 * Default settings.
 *
 * @return array
 */
function ccpc_default_settings() {
	return array(
		'max_long_edge'     => 2000,
		'webp_quality'      => 82,
		'avif_quality'      => 60,
		'make_avif'         => 0,
		'keep_originals'    => 1,
		'serve_webp'        => 1,
		'filename_template' => '{property}-{place}-{subject}-{n}-coco-tran',
		'auto_rename'       => 1,
		'default_credit'    => 'Photo: Coco Tran',
		'license_url'       => 'https://cocotran.com/disclosures/',
		'copyright_notice'  => '© Coco Tran',
		'auto_alt'          => 0,
		'show_credit'       => 1,
		'schema_enabled'    => 1,
		'schema_max_images' => 6,
		'cors_origins'      => "https://claude.ai\nhttps://*.claude.ai",
	);
}

/**
 * Get merged settings.
 *
 * @return array
 */
function ccpc_get_settings() {
	$saved = get_option( CCPC_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, ccpc_default_settings() );
}

/**
 * Get one setting.
 *
 * @param string $key Key.
 * @return mixed
 */
function ccpc_setting( $key ) {
	$s = ccpc_get_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

require_once CCPC_DIR . 'includes/class-ccpc-usages.php';
require_once CCPC_DIR . 'includes/class-ccpc-processor.php';
require_once CCPC_DIR . 'includes/class-ccpc-naming.php';
require_once CCPC_DIR . 'includes/class-ccpc-rename.php';
require_once CCPC_DIR . 'includes/class-ccpc-replace.php';
require_once CCPC_DIR . 'includes/class-ccpc-serve.php';
require_once CCPC_DIR . 'includes/class-ccpc-schema.php';
require_once CCPC_DIR . 'includes/class-ccpc-rest.php';
require_once CCPC_DIR . 'includes/class-ccpc-settings.php';

/**
 * Find every place an attachment is used.
 *
 * @param int $attachment_id Attachment ID.
 * @return array[] Each: [ 'post_id', 'post_type', 'location', 'title' ].
 */
function ccpc_find_usages( $attachment_id ) {
	return CCPC_Usages::find( (int) $attachment_id );
}

/**
 * Boot.
 */
function ccpc_init() {
	CCPC_Processor::init();
	CCPC_Naming::init();
	CCPC_Rename::init();
	CCPC_Replace::init();
	CCPC_Serve::init();
	CCPC_Schema::init();
	CCPC_Rest::init();
	CCPC_Settings::init();
	CCPC_Usages::init();
}
add_action( 'plugins_loaded', 'ccpc_init' );

register_activation_hook( __FILE__, function () {
	if ( false === get_option( CCPC_OPTION, false ) ) {
		add_option( CCPC_OPTION, ccpc_default_settings() );
	}
	if ( false === get_option( CCPC_REDIRECTS_OPTION, false ) ) {
		add_option( CCPC_REDIRECTS_OPTION, array() );
	}
} );

/**
 * Admin assets.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screens = array( 'post.php', 'upload.php', 'settings_page_coco-photo-cms', 'media.php' );
	if ( ! in_array( $hook, $screens, true ) ) {
		return;
	}
	wp_enqueue_style( 'ccpc-admin', CCPC_URL . 'assets/admin.css', array(), CCPC_VERSION );
	wp_enqueue_script( 'ccpc-admin', CCPC_URL . 'assets/admin.js', array( 'jquery' ), CCPC_VERSION, true );
	wp_localize_script( 'ccpc-admin', 'CCPC', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'ccpc_admin' ),
		'i18n'  => array(
			'altRequired' => __( 'Alt text is required before this image can be saved.', 'coco-photo-cms' ),
			'working'     => __( 'Working…', 'coco-photo-cms' ),
			'done'        => __( 'Done.', 'coco-photo-cms' ),
		),
	) );
} );
