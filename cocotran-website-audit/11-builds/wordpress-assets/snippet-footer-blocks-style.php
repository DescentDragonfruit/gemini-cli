<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.
 * Restyles the two footer blocks from the Site Snippets plugin — "Pair it with" and the author line —
 * to match the facts strip at the top of every piece (sans light labels, Kin serif values, hairlines,
 * label column + value columns, full width). Pure CSS through the plugin's own filters; no markup change.
 * Remove the snippet and the blocks fall back to the plugin's default style.
 * After saving: SpeedyCache → Purge all, then Cloudflare → Purge everything, or pages keep the old look.
 */
add_filter( 'ct_pair_css', function () {
	return <<<'CSS'
/* Coco Tran — footer blocks in the facts-strip style */
.ct-tail{max-width:none!important;padding:0!important;margin:2.5rem 0 0!important}
.ct-pair,.ct-author{--ct-ink:#111;--ct-hair:var(--global-palette7,#e3e0da);max-width:none;margin:0;font-family:sans-serif;color:var(--ct-ink);}
.ct-pair{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:1.4rem 32px 1.2rem;border-top:1px solid var(--ct-ink);background:#fff;}
.ct-pair .ct-pair__kicker{margin:0;font-family:sans-serif;font-weight:300;font-size:17px;line-height:30px;letter-spacing:0;text-transform:uppercase;font-variant-caps:normal;color:var(--ct-ink);}
.ct-pair .ct-pair__list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:64px;row-gap:14px;}
.ct-pair .ct-pair__item{margin:0;padding:0;}
.ct-pair .ct-pair__link{display:block;text-decoration:none;color:inherit;border-top:1px solid var(--ct-hair);padding:10px 0 6px;}
.ct-pair .ct-pair__format{display:block;font-family:sans-serif;font-weight:300;font-size:17px;line-height:20px;letter-spacing:.15px;text-transform:none;font-variant-caps:normal;color:#0e0e0e;margin:0 0 2px;}
.ct-pair .ct-pair__title{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-weight:400;font-size:16px;line-height:30px;color:var(--ct-ink);}
.ct-pair .ct-pair__link:hover .ct-pair__title{text-decoration:underline;text-underline-offset:3px;color:var(--ct-ink);}
.ct-pair .ct-pair__excerpt{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-size:14px;line-height:20px;color:var(--ct-ink);opacity:.72;margin-top:2px;}
.ct-pair .ct-pair__item--hub .ct-pair__link{border-top-color:var(--ct-ink);}
.ct-author{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:1.2rem 32px 2.4rem;border-top:1px solid var(--ct-hair);background:#fff;font-size:16px;line-height:30px;}
.ct-author::before{content:"Photos & words";font-family:sans-serif;font-weight:300;font-size:17px;line-height:30px;text-transform:uppercase;color:var(--ct-ink);}
.ct-author > *{grid-column:2;}
.ct-author p{margin:0;}
.ct-author .ct-author__line{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:30px;}
.ct-author .ct-author__role{display:none;}
.ct-author .ct-author__name{color:inherit;text-decoration:none;border-bottom:1px solid var(--ct-ink);}
.ct-author .ct-author__job,.ct-author .ct-author__meta{font-family:sans-serif;font-weight:300;font-size:17px;line-height:24px;letter-spacing:.15px;text-transform:none;font-variant-caps:normal;color:#0e0e0e;}
.ct-author .ct-author__meta{font-size:13px;line-height:17px;letter-spacing:.39px;font-weight:400;color:#000;margin-top:6px;}
.ct-author .ct-author__bio{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:26px;margin:4px 0 0;}
.ct-author .ct-author__sep{color:var(--ct-hair);}
.ct-author .ct-author__links{margin-top:6px;font-family:sans-serif;font-weight:300;font-size:15px;}
.ct-author .ct-author__links a{color:var(--ct-ink);text-decoration:none;border-bottom:1px solid var(--ct-hair);}
@media (max-width:767px){
 .ct-pair,.ct-author{grid-template-columns:1fr;column-gap:0;padding-left:8px;padding-right:8px;}
 .ct-pair .ct-pair__list{grid-template-columns:1fr;row-gap:6px;}
 .ct-author > *{grid-column:1;}
}

CSS;
} );
add_filter( 'ct_author_css', function () {
	return <<<'CSS'
/* Coco Tran — footer blocks in the facts-strip style */
.ct-tail{max-width:none!important;padding:0!important;margin:2.5rem 0 0!important}
.ct-pair,.ct-author{--ct-ink:#111;--ct-hair:var(--global-palette7,#e3e0da);max-width:none;margin:0;font-family:sans-serif;color:var(--ct-ink);}
.ct-pair{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:1.4rem 32px 1.2rem;border-top:1px solid var(--ct-ink);background:#fff;}
.ct-pair .ct-pair__kicker{margin:0;font-family:sans-serif;font-weight:300;font-size:17px;line-height:30px;letter-spacing:0;text-transform:uppercase;font-variant-caps:normal;color:var(--ct-ink);}
.ct-pair .ct-pair__list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:64px;row-gap:14px;}
.ct-pair .ct-pair__item{margin:0;padding:0;}
.ct-pair .ct-pair__link{display:block;text-decoration:none;color:inherit;border-top:1px solid var(--ct-hair);padding:10px 0 6px;}
.ct-pair .ct-pair__format{display:block;font-family:sans-serif;font-weight:300;font-size:17px;line-height:20px;letter-spacing:.15px;text-transform:none;font-variant-caps:normal;color:#0e0e0e;margin:0 0 2px;}
.ct-pair .ct-pair__title{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-weight:400;font-size:16px;line-height:30px;color:var(--ct-ink);}
.ct-pair .ct-pair__link:hover .ct-pair__title{text-decoration:underline;text-underline-offset:3px;color:var(--ct-ink);}
.ct-pair .ct-pair__excerpt{display:block;font-family:var(--global-heading-font-family,Georgia,serif);font-size:14px;line-height:20px;color:var(--ct-ink);opacity:.72;margin-top:2px;}
.ct-pair .ct-pair__item--hub .ct-pair__link{border-top-color:var(--ct-ink);}
.ct-author{display:grid;grid-template-columns:174px 1fr;column-gap:64px;padding:1.2rem 32px 2.4rem;border-top:1px solid var(--ct-hair);background:#fff;font-size:16px;line-height:30px;}
.ct-author::before{content:"Photos & words";font-family:sans-serif;font-weight:300;font-size:17px;line-height:30px;text-transform:uppercase;color:var(--ct-ink);}
.ct-author > *{grid-column:2;}
.ct-author p{margin:0;}
.ct-author .ct-author__line{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:30px;}
.ct-author .ct-author__role{display:none;}
.ct-author .ct-author__name{color:inherit;text-decoration:none;border-bottom:1px solid var(--ct-ink);}
.ct-author .ct-author__job,.ct-author .ct-author__meta{font-family:sans-serif;font-weight:300;font-size:17px;line-height:24px;letter-spacing:.15px;text-transform:none;font-variant-caps:normal;color:#0e0e0e;}
.ct-author .ct-author__meta{font-size:13px;line-height:17px;letter-spacing:.39px;font-weight:400;color:#000;margin-top:6px;}
.ct-author .ct-author__bio{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:26px;margin:4px 0 0;}
.ct-author .ct-author__sep{color:var(--ct-hair);}
.ct-author .ct-author__links{margin-top:6px;font-family:sans-serif;font-weight:300;font-size:15px;}
.ct-author .ct-author__links a{color:var(--ct-ink);text-decoration:none;border-bottom:1px solid var(--ct-hair);}
@media (max-width:767px){
 .ct-pair,.ct-author{grid-template-columns:1fr;column-gap:0;padding-left:8px;padding-right:8px;}
 .ct-pair .ct-pair__list{grid-template-columns:1fr;row-gap:6px;}
 .ct-author > *{grid-column:1;}
}

CSS;
} );
