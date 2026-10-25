<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.  v2.5 · 25 Oct 2026
 * The two footer blocks from the Site Snippets plugin, "Pair it with" and the author line, in the facts-strip style:
 * sans light labels in capitals, more air around everything, and a small greyscale photo of each paired piece on
 * the left of its text (the piece's featured image; items without one simply show text).
 * Remove the snippet and the blocks fall back to the plugin's default style.
 * After saving: SpeedyCache → Purge all, then Cloudflare → Purge everything, or pages keep the old look.
 */

/* ---------- 1. The look (one stylesheet, served through both plugin filters) ---------- */
function ct_footer_blocks_css() {
	return <<<'CSS'
/* Coco Tran — footer blocks in the facts-strip style (v2.5) */
.ct-tail{max-width:none!important;padding:0!important;margin:4rem 0 3rem!important}
.ct-pair,.ct-author{--ct-ink:#111;--ct-hair:var(--global-palette7,#e3e0da);max-width:none;margin:0;font-family:sans,"Inter",system-ui,sans-serif;color:var(--ct-ink);}
body .ct-pair{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:3.6rem 32px 3rem!important;border-top:3px solid #000!important;background:#fff;}
body .ct-pair .ct-pair__kicker{margin:0!important;font-family:sans,"Inter",system-ui,sans-serif!important;font-weight:400!important;font-size:17px!important;line-height:24px!important;letter-spacing:1.5px!important;text-transform:uppercase!important;font-variant-caps:normal!important;color:#000!important;}
.ct-pair .ct-pair__list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:72px;row-gap:30px;}
.ct-pair .ct-pair__item{margin:0;padding:0;}
.ct-pair .ct-pair__link{display:grid!important;grid-template-columns:1fr;column-gap:24px;align-items:start;text-decoration:none;color:inherit;border-top:1px solid var(--ct-hair);padding:20px 0 14px;}
.ct-pair .ct-pair__link.has-thumb{grid-template-columns:96px minmax(0,1fr)!important;}
.ct-pair .ct-pair__thumb{grid-column:1;grid-row:1/span 3;display:block;width:96px!important;height:96px!important;overflow:hidden;background:#f1efea;}
.ct-pair .ct-pair__thumb img{display:block;width:100%!important;height:100%!important;max-width:none;object-fit:cover;filter:grayscale(1)!important;transition:filter .25s;}
.ct-pair .ct-pair__link:hover .ct-pair__thumb img{filter:grayscale(0)!important;}
.ct-pair .has-thumb > .ct-pair__format,.ct-pair .has-thumb > .ct-pair__title,.ct-pair .has-thumb > .ct-pair__excerpt{grid-column:2;}
body .ct-pair .ct-pair__format{display:block;font-family:sans,"Inter",system-ui,sans-serif!important;font-weight:300!important;font-size:13px!important;line-height:20px!important;letter-spacing:.6px!important;text-transform:uppercase!important;font-variant-caps:normal!important;color:#000!important;margin:0 0 6px!important;}
.ct-pair .ct-pair__title{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-weight:400;font-size:17px;line-height:26px;color:var(--ct-ink);}
.ct-pair .ct-pair__link:hover .ct-pair__title{text-decoration:underline;text-underline-offset:3px;color:var(--ct-ink);}
.ct-pair .ct-pair__excerpt{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-size:14px;line-height:22px;color:var(--ct-ink);opacity:.72;margin-top:6px;}
.ct-pair .ct-pair__item--hub .ct-pair__link{border-top-color:var(--ct-ink);}
body .ct-author{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:2.8rem 32px 3.6rem!important;border-top:1px solid var(--ct-hair);border-bottom:3px solid #000!important;background:#fff;font-size:16px;line-height:30px;}
body .ct-author::before{content:"Photos & words";font-family:sans,"Inter",system-ui,sans-serif!important;font-weight:400!important;font-size:17px!important;line-height:24px!important;letter-spacing:1.5px!important;text-transform:uppercase!important;color:#000!important;}
.ct-author > *{grid-column:2;}
.ct-author p{margin:0;}
.ct-author .ct-author__line{font-family:var(--global-heading-font-family,Georgia,serif);font-size:17px;line-height:30px;}
.ct-author .ct-author__role{display:none;}
.ct-author .ct-author__name{color:inherit;text-decoration:none;border-bottom:1px solid var(--ct-ink);}
.ct-author .ct-author__job,.ct-author .ct-author__meta{font-family:sans,"Inter",system-ui,sans-serif;font-weight:300;font-size:17px;line-height:26px;letter-spacing:.15px;text-transform:none;font-variant-caps:normal;color:#0e0e0e;margin-top:10px;}
.ct-author .ct-author__meta{font-size:13px;line-height:20px;letter-spacing:.39px;font-weight:400;color:#000;margin-top:10px;}
.ct-author .ct-author__bio{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:26px;margin:10px 0 0;}
.ct-author .ct-author__sep{color:var(--ct-hair);}
.ct-author .ct-author__links{margin-top:14px;font-family:sans,"Inter",system-ui,sans-serif;font-weight:300;font-size:15px;}
.ct-author .ct-author__links a{color:var(--ct-ink);text-decoration:none;border-bottom:1px solid var(--ct-hair);}
@media (max-width:767px){
 body .ct-pair,body .ct-author{grid-template-columns:1fr;column-gap:0;padding:2.4rem 8px 2rem!important;}
 .ct-tail{margin:3rem 0 2rem!important}
 body .ct-pair .ct-pair__kicker,body .ct-author::before{font-size:17px!important;line-height:24px!important;letter-spacing:1.5px!important;margin-bottom:10px!important;}
 .ct-pair .ct-pair__list{grid-template-columns:1fr;row-gap:14px;}
 .ct-pair .ct-pair__link{padding:14px 0 10px;column-gap:16px;}
 .ct-pair .ct-pair__link.has-thumb{grid-template-columns:72px minmax(0,1fr)!important;}
 .ct-pair .ct-pair__thumb{width:72px!important;height:72px!important;}
 .ct-author > *{grid-column:1;}
}
CSS;
}
/* Priority 99 so this wins over any older copy of the footer snippet that may still be active; the same stylesheet
   is also printed in the head, so the photos never render full-size even if the plugin's filters change. */
add_filter( 'ct_pair_css', 'ct_footer_blocks_css', 99 );
add_filter( 'ct_author_css', 'ct_footer_blocks_css', 99 );
add_action( 'wp_head', function () { if ( ! is_admin() ) { echo '<style id="ct-footer-blocks-v2">' . ct_footer_blocks_css() . '</style>' . "\n"; } }, 99 );
add_action( 'wp_footer', function () { if ( ! is_admin() ) { echo '<style id="ct-footer-blocks-v2-last">' . ct_footer_blocks_css() . '</style>' . "\n"; } }, 9999 );

/* ---------- 2. The small greyscale photo on each paired piece ---------- */
/** Post ID for a cocotran.com URL. url_to_postid() misses the magazine post types, so fall back to the slug. */
function ct_pair_post_id_from_url( $url ) {
	$pid = (int) url_to_postid( $url );
	if ( $pid ) { return $pid; }
	$path = trim( (string) parse_url( $url, PHP_URL_PATH ), '/' );
	if ( '' === $path ) { return 0; }
	$parts = explode( '/', $path );
	$slug  = sanitize_title( end( $parts ) );
	if ( '' === $slug ) { return 0; }
	$found = get_posts( array( 'name' => $slug, 'post_type' => array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true ) );
	if ( $found ) { return (int) $found[0]; }
	foreach ( array( 'property', 'guide', 'brief', 'dispatch', 'shortlist', 'post', 'page' ) as $pt ) { $o = get_page_by_path( $slug, OBJECT, $pt ); if ( $o instanceof WP_Post ) { return (int) $o->ID; } }
	return 0;
}
/** The photo for a piece: featured image, else the first image attached to it. */
function ct_pair_thumb_src( $pid ) {
	$src = get_the_post_thumbnail_url( $pid, 'medium' );
	if ( $src ) { return $src; }
	$media = get_attached_media( 'image', $pid );
	if ( $media ) { $first = reset( $media ); $src = wp_get_attachment_image_url( $first->ID, 'medium' ); if ( $src ) { return $src; } }
	return '';
}
function ct_pair_add_thumbs( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'ct-pair__link' ) || false !== strpos( $html, 'ct-pair__thumb' ) ) { return $html; }
	return preg_replace_callback( '/<a class="ct-pair__link" href="([^"]+)">/', function ( $m ) {
		$pid = ct_pair_post_id_from_url( html_entity_decode( $m[1] ) );
		$src = $pid ? ct_pair_thumb_src( $pid ) : '';
		if ( ! $src ) { return $m[0]; }
		return '<a class="ct-pair__link has-thumb" href="' . $m[1] . '"><span class="ct-pair__thumb"><img src="' . esc_url( $src ) . '" alt="" loading="lazy" decoding="async"></span>';
	}, $html );
}
/* Classic posts: the plugin appends the blocks to the_content at priority 25. */
add_filter( 'the_content', 'ct_pair_add_thumbs', 26 );
/* Magazine post types: the plugin echoes the blocks on these Kadence hooks (priority 20 and 5); buffer around them. */
add_action( 'kadence_single_after_content', function () { ob_start(); }, 19 );
add_action( 'kadence_single_after_content', function () { echo ct_pair_add_thumbs( (string) ob_get_clean() ); }, 21 );
add_action( 'kadence_after_content', function () { ob_start(); }, 4 );
add_action( 'kadence_after_content', function () { echo ct_pair_add_thumbs( (string) ob_get_clean() ); }, 6 );
