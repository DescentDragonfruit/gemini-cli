<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.
 * Article chrome, site-wide:
 *  1. Facts rows (THE STAY / THE TRIP / THE VISIT): values at body size and line height on phones too.
 *  2. Destination line: label and places on one line; places reduced to city and country
 *     (the continent is dropped whenever a more specific place is filed) — on the strip and in "Filed under".
 *  3. Footer blocks: "Pair it with" and "Photos & words" headings in the article's heading style, black.
 *  4. Booking box: title in the article's heading style, strip-style labels, full column width.
 * Remove the snippet to revert everything at once.
 */
add_filter( 'get_the_terms', function ( $terms, $post_id, $taxonomy ) {
	if ( is_admin() || 'destination' !== $taxonomy || ! is_array( $terms ) || count( $terms ) < 2 ) { return $terms; }
	$by_id = array(); foreach ( $terms as $t ) { if ( $t instanceof WP_Term ) { $by_id[ $t->term_id ] = $t; } }
	if ( count( $by_id ) < 2 ) { return $terms; }
	$leaves = array();
	foreach ( $by_id as $t ) { $has_child = false; foreach ( $by_id as $o ) { if ( (int) $o->parent === (int) $t->term_id ) { $has_child = true; break; } } if ( ! $has_child ) { $leaves[] = $t; } }
	$keep = array();
	foreach ( $leaves as $leaf ) {
		$keep[ $leaf->term_id ] = $leaf;
		if ( $leaf->parent && isset( $by_id[ $leaf->parent ] ) && (int) $by_id[ $leaf->parent ]->parent !== 0 ) { $keep[ $leaf->parent ] = $by_id[ $leaf->parent ]; } // the country, not the continent
		elseif ( $leaf->parent && isset( $by_id[ $leaf->parent ] ) && (int) $leaf->parent !== 0 && (int) $by_id[ $leaf->parent ]->parent === 0 && count( $leaves ) === 1 ) { $keep[ $leaf->parent ] = $by_id[ $leaf->parent ]; } // place filed straight under a continent: keep both
	}
	return empty( $keep ) ? $terms : array_values( $keep );
}, 20, 3 );

add_action( 'wp_head', function () {
	if ( is_admin() ) { return; }
	echo "<style id=\"ct-article-chrome\">" . <<<'CSS'
/* Coco Tran — article chrome (installed as a snippet) */
/* 1. Facts rows: values at body size on every screen */
.kb-row-layout-id27262_093f0b-f3 .kb-dynamic-html, .kb-row-layout-id29436_c794f5-cd .kb-dynamic-html, .kb-row-layout-id27738_6679df-d4 .kb-dynamic-html, .kb-row-layout-id27517_dc0cc8-28 .kb-dynamic-html, .kb-row-layout-id27762_b719d0-c9 .kb-dynamic-html, .kb-row-layout-id27733_b300b5-c2 .kb-dynamic-html, .kb-row-layout-id27556_04c183-2d .kb-dynamic-html, .kb-row-layout-id27818_fd71b8-95 .kb-dynamic-html, .kb-row-layout-id27776_d8eb7f-23 .kb-dynamic-html, .kb-row-layout-id27810_c821f6-d9 .kb-dynamic-html, .kb-row-layout-id30381_620279-15 .kb-dynamic-html, .kb-row-layout-id27262_093f0b-f3 .kb-dynamic-list, .kb-row-layout-id29436_c794f5-cd .kb-dynamic-list, .kb-row-layout-id27738_6679df-d4 .kb-dynamic-list, .kb-row-layout-id27517_dc0cc8-28 .kb-dynamic-list, .kb-row-layout-id27762_b719d0-c9 .kb-dynamic-list, .kb-row-layout-id27733_b300b5-c2 .kb-dynamic-list, .kb-row-layout-id27556_04c183-2d .kb-dynamic-list, .kb-row-layout-id27818_fd71b8-95 .kb-dynamic-list, .kb-row-layout-id27776_d8eb7f-23 .kb-dynamic-list, .kb-row-layout-id27810_c821f6-d9 .kb-dynamic-list, .kb-row-layout-id30381_620279-15 .kb-dynamic-list{font-size:16px!important;line-height:30px!important}
@media (max-width:767px){
.kb-row-layout-id27262_093f0b-f3 .kb-dynamic-html, .kb-row-layout-id29436_c794f5-cd .kb-dynamic-html, .kb-row-layout-id27738_6679df-d4 .kb-dynamic-html, .kb-row-layout-id27517_dc0cc8-28 .kb-dynamic-html, .kb-row-layout-id27762_b719d0-c9 .kb-dynamic-html, .kb-row-layout-id27733_b300b5-c2 .kb-dynamic-html, .kb-row-layout-id27556_04c183-2d .kb-dynamic-html, .kb-row-layout-id27818_fd71b8-95 .kb-dynamic-html, .kb-row-layout-id27776_d8eb7f-23 .kb-dynamic-html, .kb-row-layout-id27810_c821f6-d9 .kb-dynamic-html, .kb-row-layout-id30381_620279-15 .kb-dynamic-html, .kb-row-layout-id27262_093f0b-f3 .kb-dynamic-list, .kb-row-layout-id29436_c794f5-cd .kb-dynamic-list, .kb-row-layout-id27738_6679df-d4 .kb-dynamic-list, .kb-row-layout-id27517_dc0cc8-28 .kb-dynamic-list, .kb-row-layout-id27762_b719d0-c9 .kb-dynamic-list, .kb-row-layout-id27733_b300b5-c2 .kb-dynamic-list, .kb-row-layout-id27556_04c183-2d .kb-dynamic-list, .kb-row-layout-id27818_fd71b8-95 .kb-dynamic-list, .kb-row-layout-id27776_d8eb7f-23 .kb-dynamic-list, .kb-row-layout-id27810_c821f6-d9 .kb-dynamic-list, .kb-row-layout-id30381_620279-15 .kb-dynamic-list{font-size:16px!important;line-height:24px!important}
.kb-row-layout-id27262_093f0b-f3 .kb-dynamic-html, .kb-row-layout-id29436_c794f5-cd .kb-dynamic-html, .kb-row-layout-id27738_6679df-d4 .kb-dynamic-html, .kb-row-layout-id27517_dc0cc8-28 .kb-dynamic-html, .kb-row-layout-id27762_b719d0-c9 .kb-dynamic-html, .kb-row-layout-id27733_b300b5-c2 .kb-dynamic-html, .kb-row-layout-id27556_04c183-2d .kb-dynamic-html, .kb-row-layout-id27818_fd71b8-95 .kb-dynamic-html, .kb-row-layout-id27776_d8eb7f-23 .kb-dynamic-html, .kb-row-layout-id27810_c821f6-d9 .kb-dynamic-html, .kb-row-layout-id30381_620279-15 .kb-dynamic-html{margin:0 0 2px!important}
}
/* 2. Destination: label and places on one line */
.kt-inside-inner-col:has(> .wp-block-post-terms){align-items:baseline!important}
.kt-inside-inner-col > .wp-block-post-terms{margin:0!important;line-height:20px!important}
/* 3. Footer blocks: headings in the article's heading style, black */
body .ct-pair .ct-pair__kicker, body .ct-author::before{font-family:sans,"Inter",system-ui,sans-serif;font-weight:400;font-size:22px;line-height:30px;letter-spacing:0;text-transform:uppercase;color:#000}
body .ct-pair .ct-pair__format{color:#000}
body .ct-pair,body .ct-author{--ct-ink:#000;color:#000}
body .ct-pair .ct-pair__excerpt{opacity:.78}
/* 4. Booking box: title in the article's heading style, labels in the strip's sans, full column width, nothing gold */
body .ctt-book{max-width:none;margin:2rem 0;--ct-accent:#000;--ct-grey:#0e0e0e}
body .ctt-book .ctt-book__kicker{font-family:sans,"Inter",system-ui,sans-serif;font-variant-caps:normal;font-weight:300;font-size:13px;letter-spacing:.39px;text-transform:uppercase;color:#0e0e0e;margin:0 0 .4rem}
body .ctt-book .ctt-book__title{font-family:sans,"Inter",system-ui,sans-serif;font-weight:400;font-size:22px;line-height:30px;letter-spacing:0;text-transform:uppercase;color:#000;margin:0 0 .6rem;overflow-wrap:normal;word-break:normal}
body .ctt-book .ctt-book__title em{font:inherit;font-style:normal;color:#000;text-transform:inherit}
body .ctt-book .ctt-book__perks{font-size:16px;line-height:30px}
body .ctt-book .ctt-book__btn, body .ctt-book a.button.ctt-book__btn{font-family:sans,"Inter",system-ui,sans-serif;font-variant-caps:normal;font-weight:300;font-size:13px;letter-spacing:.39px;text-transform:uppercase;border-color:#000;color:#000}
body .ctt-book .ctt-book__note{font-family:var(--global-heading-font-family,Georgia,serif);font-size:14px;color:#000;opacity:.78}
@media (max-width:767px){
body .ct-pair .ct-pair__kicker, body .ct-author::before, body .ctt-book .ctt-book__title{font-size:16px;line-height:22px}
}

CSS . "</style>\n";
}, 99 );
