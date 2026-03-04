<?php
/**
 * Plugin Name:       Cocotran Scroll FX
 * Plugin URI:        https://cocotran.com
 * Description:       Wix-quality scroll animations and interactions for Kadence Blocks.
 *                    Add fx-* classes to any block to animate it — no page builder needed.
 * Version:           1.0.0
 * Author:            Cocotran
 * License:           GPL-2.0-or-later
 * Text Domain:       cocotran-scroll-fx
 *
 * HOW TO UPDATE:
 *   1. Edit files in assets/css/ or assets/js/
 *   2. Bump the $ver constant below
 *   3. Re-upload the changed files
 */

defined( 'ABSPATH' ) || exit;

define( 'CSFX_VER',  '1.0.0' );
define( 'CSFX_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CSFX_URL',  plugin_dir_url( __FILE__ ) );

// ── Enqueue plugin assets ─────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'csfx_enqueue' );

function csfx_enqueue() {
    $v = CSFX_VER;
    $c = CSFX_URL . 'assets/css/';
    $j = CSFX_URL . 'assets/js/';

    // CSS — load in order (each builds on the previous)
    wp_enqueue_style( 'csfx-tokens',   $c . 'tokens.css',    [],              $v );
    wp_enqueue_style( 'csfx-fx',       $c . 'scroll-fx.css', ['csfx-tokens'], $v );
    wp_enqueue_style( 'csfx-micro',    $c . 'micro.css',     ['csfx-tokens'], $v );

    // JS — load in footer
    wp_enqueue_script( 'csfx-engine',      $j . 'engine.js',      [], $v, true );
    wp_enqueue_script( 'csfx-parallax',    $j . 'parallax.js',    [], $v, true );
    wp_enqueue_script( 'csfx-transitions', $j . 'transitions.js', [], $v, true );
}

// ── Per-site body class ────────────────────────────────────────────────────
// Lets you style cocotran.com vs cocotrantravel.com differently.

add_filter( 'body_class', 'csfx_body_class' );

function csfx_body_class( $classes ) {
    if ( strpos( home_url(), 'cocotrantravel' ) !== false ) {
        $classes[] = 'site-travel';
    } else {
        $classes[] = 'site-translation';
    }
    return $classes;
}
