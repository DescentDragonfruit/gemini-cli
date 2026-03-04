<?php
/**
 * Cocotran Child Theme — functions.php
 *
 * Enqueues child theme stylesheets and scripts.
 * All animation and styling files live in assets/css/ and assets/js/.
 *
 * HOW TO UPDATE FILES:
 * 1. Make changes to files in assets/css/ or assets/js/
 * 2. Bump the $ver string below (e.g. '1.0.0' → '1.0.1')
 *    This forces browsers to reload the new version
 * 3. Upload changed files via FTP or your host's file manager
 */

// ── Enqueue child theme styles + scripts ───────────────────────────────────

function cocotran_enqueue_assets() {

    $ver  = '1.0.0'; // <-- bump this after every upload
    $path = get_stylesheet_directory_uri() . '/assets/';

    // ── Stylesheets (loaded in order — each depends on the previous) ──────

    // 1. Design tokens — colors, fonts, spacing, animation easing
    wp_enqueue_style(
        'cocotran-tokens',
        $path . 'css/01-custom-properties.css',
        array(),
        $ver
    );

    // 2. Base — global resets, body, links, headings
    wp_enqueue_style(
        'cocotran-base',
        $path . 'css/02-base.css',
        array( 'cocotran-tokens' ),
        $ver
    );

    // 3. Animations — keyframes, .reveal, .parallax-*, .hover-* classes
    wp_enqueue_style(
        'cocotran-animations',
        $path . 'css/03-animations.css',
        array( 'cocotran-base' ),
        $ver
    );

    // 4. Components — hero, nav, cards, CTA, footer overrides
    wp_enqueue_style(
        'cocotran-components',
        $path . 'css/04-components.css',
        array( 'cocotran-animations' ),
        $ver
    );

    // 5. Mobile — responsive breakpoints and mobile-specific adjustments
    wp_enqueue_style(
        'cocotran-mobile',
        $path . 'css/05-mobile.css',
        array( 'cocotran-components' ),
        $ver
    );

    // ── Scripts (loaded in footer — true as last argument) ────────────────

    // Scroll-triggered reveal animations (IntersectionObserver)
    wp_enqueue_script(
        'cocotran-scroll-observer',
        $path . 'js/scroll-observer.js',
        array(),
        $ver,
        true // load in footer
    );

    // Parallax depth-layer scroll effect
    wp_enqueue_script(
        'cocotran-parallax',
        $path . 'js/parallax.js',
        array(),
        $ver,
        true
    );

    // Smooth page-to-page transitions
    wp_enqueue_script(
        'cocotran-page-transitions',
        $path . 'js/page-transitions.js',
        array(),
        $ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'cocotran_enqueue_assets' );


// ── Google Fonts ───────────────────────────────────────────────────────────
// Replace HEADING_FONT and BODY_FONT with your Google Fonts names
// Get the URL from https://fonts.google.com → "Get embed code"

function cocotran_enqueue_google_fonts() {
    wp_enqueue_style(
        'cocotran-google-fonts',
        // Example: 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap'
        'REPLACE_WITH_YOUR_GOOGLE_FONTS_URL',
        array(),
        null // no version — Google manages cache
    );
}
add_action( 'wp_enqueue_scripts', 'cocotran_enqueue_google_fonts' );


// ── Per-site body class ────────────────────────────────────────────────────
// Adds .site-travel on cocotrantravel.com and .site-translation on cocotran.com
// Lets you style both sites differently from one child theme

function cocotran_body_class( $classes ) {
    if ( strpos( home_url(), 'cocotrantravel' ) !== false ) {
        $classes[] = 'site-travel';
    } else {
        $classes[] = 'site-translation';
    }
    return $classes;
}
add_filter( 'body_class', 'cocotran_body_class' );


// ── Sticky nav scroll class ────────────────────────────────────────────────
// The scroll-observer.js script adds .header-scrolled to body on scroll.
// CSS in 04-components.css styles the nav differently when this class is present.
// No PHP needed — handled purely in JS + CSS.


// ── Remove Kadence parent theme Google Fonts (if duplicating) ─────────────
// Uncomment this if Kadence is loading fonts you've replaced with your own.
// function cocotran_remove_kadence_fonts() {
//     wp_dequeue_style( 'kadence-googlefonts' );
// }
// add_action( 'wp_enqueue_scripts', 'cocotran_remove_kadence_fonts', 20 );
