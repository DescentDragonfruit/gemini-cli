<?php
/**
 * Plugin Name: Coco Photo CMS
 * Plugin URI:  https://cocotran.com
 * Description: The photo layer WordPress doesn't have: compress and convert on upload, descriptive filenames, required alt text and credits, rename with redirects, replace an image everywhere or in one place, focal-point social crops (generated automatically on publish) with an export-only credit watermark, in-use delete guard, one-click resize, bulk optimise, front-end speed attributes, ImageObject schema, and a REST intake + post-crops endpoints for Claude artifacts and the Canva hand-off.
 * Version:     1.2.0
 * Author:      Coco Tran
 * License:     GPL-2.0-or-later
 * Text Domain: coco-photo-cms
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCPC_VERSION', '1.2.0' );
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
		'front_cap'         => 1600,
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
		// 1.1
		'webp_preset'         => 'balanced',
		'export_jpeg_quality' => 88,
		'wm_text'             => '© Coco Tran · cocotranstudio.com',
		'wm_position'         => 'br',
		'wm_opacity'          => 70,
		'wm_size'             => 2.2,
		'wm_font'             => ccpc_detect_theme_font(),
		'wm_default'          => 0,
		'delete_guard'        => 1,
		'speed_decoding'      => 1,
		'speed_lazy'          => 1,
		'speed_lazy_skip'     => 2,
		'speed_fetchpriority' => 1,
		'speed_preload'       => 1,
		'speed_sizes'         => 1,
		// 1.2
		'auto_crops_on_publish' => 1,
		'auto_crops_refresh'    => 0,
		'auto_crops_max'        => 6,
	);
}

/**
 * First .ttf/.otf found in the child or parent theme's /fonts/ folder, else ''. Used as the
 * default watermark font (nothing is bundled; GD's bitmap font is the fallback).
 *
 * @return string Absolute path or ''.
 */
function ccpc_detect_theme_font() {
	static $found = null;
	if ( null !== $found ) {
		return $found;
	}
	$found = '';
	if ( ! function_exists( 'get_stylesheet_directory' ) ) {
		return $found;
	}
	$dirs = array_unique( array( get_stylesheet_directory(), get_template_directory() ) );
	foreach ( $dirs as $dir ) {
		foreach ( array( '/fonts/*.ttf', '/fonts/*.otf', '/assets/fonts/*.ttf', '/assets/fonts/*.otf' ) as $pat ) {
			$hits = glob( $dir . $pat );
			if ( $hits ) {
				sort( $hits );
				$found = $hits[0];
				return $found;
			}
		}
	}
	return $found;
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
require_once CCPC_DIR . 'includes/class-ccpc-image.php';
require_once CCPC_DIR . 'includes/class-ccpc-crops.php';
require_once CCPC_DIR . 'includes/class-ccpc-guard.php';
require_once CCPC_DIR . 'includes/class-ccpc-optimise.php';
require_once CCPC_DIR . 'includes/class-ccpc-speed.php';
require_once CCPC_DIR . 'includes/class-ccpc-autocrops.php';

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
	CCPC_Crops::init();
	CCPC_Guard::init();
	CCPC_Optimise::init();
	CCPC_Speed::init();
	CCPC_AutoCrops::init();
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

register_deactivation_hook( __FILE__, function () {
	wp_unschedule_hook( 'ccpc_auto_crops' );
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
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ccpc_admin' ),
		'formats' => class_exists( 'CCPC_Crops' ) ? CCPC_Crops::FORMATS : array(),
		'job'     => class_exists( 'CCPC_Optimise' ) ? CCPC_Optimise::get_job() : array(),
		'i18n'    => array(
			'altRequired' => __( 'Alt text is required before this image can be saved.', 'coco-photo-cms' ),
			'working'     => __( 'Working…', 'coco-photo-cms' ),
			'done'        => __( 'Done.', 'coco-photo-cms' ),
			'confirmRegen' => __( 'Regenerate all four crops for this image (the cached files are replaced)?', 'coco-photo-cms' ),
			'confirmResize' => __( 'Re-encode the full-size file and regenerate every size? The backed-up original (if any) is used as the source.', 'coco-photo-cms' ),
		),
	) );
} );
