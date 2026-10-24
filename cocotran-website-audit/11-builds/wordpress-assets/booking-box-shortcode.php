<?php
/**
 * Plugin Name: Coco Tran — Booking box shortcode
 * Description: [ctt_book hotel="…" perks="…"] renders the "Book [hotel] through Coco Tran Travel — same rate, plus perks" box, linking to cocotrantravel.com/contact with the hotel and the source URL. Also provides [ctt_book_perks] and [ctt_book_button] for the Kadence Element on Properties.
 * Version:     1.0.0
 * Author:      Coco Tran
 * License:     GPL-2.0-or-later
 *
 * Install: copy to wp-content/mu-plugins/booking-box-shortcode.php (mu-plugins load automatically, nothing to activate).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTT_BOOK_CONTACT_URL', 'https://cocotrantravel.com/contact' );

/**
 * Default perks sentence per ACF `booking_access` value.
 * Keys match the select choices in acf-booking-access.json. Override with the `ctt_book_perks_map` filter.
 *
 * Wording follows Coco's standing rule: "typically", "when available", "wherever the programme applies" —
 * never promise a perk a hotel can opt out of.
 *
 * @return array<string, array{0:string,1:string}> value => [ programme name, sentence ]
 */
function ctt_book_perks_map() {
	$map = array(
		'virtuoso' => array(
			'Virtuoso',
			'Through Virtuoso, my clients typically enjoy a room upgrade when one’s available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you.',
		),
		'fspp'     => array(
			'Four Seasons Preferred Partner',
			'Through Four Seasons Preferred Partner, my clients typically enjoy an upgrade when one’s available, daily breakfast for two, a hotel credit set by the property (usually US$100), and early check-in or late check-out when available. The hotel pays me, not you.',
		),
		'bellini'  => array(
			'Belmond Bellini Club',
			'Through the Belmond Bellini Club, my clients typically enjoy an upgrade when one’s available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you.',
		),
		'stars'    => array(
			'Marriott STARS',
			'Through Marriott STARS, my clients typically enjoy an upgrade when one’s available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out — wherever the hotel participates. The hotel pays me, not you.',
		),
		'prive'    => array(
			'Hyatt Privé',
			'Through Hyatt Privé, my clients typically enjoy an upgrade when one’s available, daily breakfast for two, a US$100 hotel credit, and early check-in or late check-out when available. The hotel pays me, not you.',
		),
		'direct'   => array(
			'Direct',
			'This hotel isn’t in a preferred-partner programme, so I book it direct at the hotel’s own rate and ask for the room, the view and the small courtesies myself. You pay the same; I handle the arrival.',
		),
		''         => array(
			'',
			'Same rate as booking direct. Through my preferred-partner programmes, my clients typically enjoy a room upgrade when one’s available, daily breakfast, a hotel credit, and early check-in or late check-out, wherever the programme applies. The hotel pays me, not you.',
		),
	);
	return apply_filters( 'ctt_book_perks_map', $map );
}

/**
 * Resolve hotel / perks / access / source for the current context.
 *
 * @param array $atts Shortcode attributes.
 * @return array{hotel:string,perks:string,access:string,source:string,url:string}
 */
function ctt_book_context( $atts ) {
	$atts = shortcode_atts(
		array(
			'hotel'  => '',
			'perks'  => '',   // free text: "upgrade, breakfast, $100 credit" → becomes a sentence
			'access' => '',   // virtuoso | fspp | bellini | stars | prive | direct (overrides ACF)
			'source' => '',   // defaults to the current permalink
			'label'  => 'Request this booking',
		),
		$atts,
		'ctt_book'
	);

	$post_id = get_the_ID();
	$hotel   = trim( (string) $atts['hotel'] );
	if ( '' === $hotel && $post_id && 'property' === get_post_type( $post_id ) ) {
		$hotel = get_the_title( $post_id );
	}
	if ( '' === $hotel ) {
		$hotel = 'this hotel';
	}

	$access = strtolower( trim( (string) $atts['access'] ) );
	if ( '' === $access && $post_id ) {
		$access = strtolower( (string) get_post_meta( $post_id, 'booking_access', true ) );
	}

	$map   = ctt_book_perks_map();
	$perks = trim( (string) $atts['perks'] );
	if ( '' === $perks && $post_id ) {
		$perks = trim( (string) get_post_meta( $post_id, 'booking_perks_override', true ) );
	}
	if ( '' !== $perks ) {
		// A short list → one sentence in her voice.
		$programme = isset( $map[ $access ] ) && '' !== $map[ $access ][0] ? 'Through ' . $map[ $access ][0] . ', my' : 'My';
		$perks     = rtrim( $perks, '. ' );
		$perks     = $programme . ' clients typically enjoy ' . $perks . ', wherever the programme applies. Same rate as booking direct; the hotel pays me, not you.';
	} else {
		$perks = isset( $map[ $access ] ) ? $map[ $access ][1] : $map[''][1];
	}

	$source = trim( (string) $atts['source'] );
	if ( '' === $source ) {
		$source = $post_id ? get_permalink( $post_id ) : home_url( add_query_arg( array() ) );
	}

	$url = CTT_BOOK_CONTACT_URL . '?hotel=' . rawurlencode( $hotel ) . '&source=' . rawurlencode( $source );

	return array(
		'hotel'  => $hotel,
		'perks'  => $perks,
		'access' => $access,
		'source' => $source,
		'url'    => $url,
		'label'  => (string) $atts['label'],
	);
}

/** Print the box CSS once per page (same rules as kadence-element-booking-box.html). */
function ctt_book_css_once() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="ctt-book-css">' . CTT_BOOK_CSS . '</style>';
}

/** [ctt_book_button] — the CTA link alone (used inside the Kadence Element). */
function ctt_book_button_shortcode( $atts ) {
	$c = ctt_book_context( (array) $atts );
	return sprintf(
		'<p class="ctt-book__cta"><a class="button ctt-book__btn" href="%s" target="_blank" rel="noopener" data-ctt-source="booking-box">%s</a><span class="ctt-book__note">Name, dates, hotel — I reply personally.</span></p>',
		esc_url( $c['url'] ),
		esc_html( $c['label'] )
	);
}

/** [ctt_book_perks] — the perks sentence alone (used inside the Kadence Element). */
function ctt_book_perks_shortcode( $atts ) {
	$c = ctt_book_context( (array) $atts );
	return '<p class="ctt-book__perks">' . esc_html( $c['perks'] ) . '</p>';
}

/** [ctt_book hotel="…" perks="…" access="…"] — the whole box. */
function ctt_book_shortcode( $atts ) {
	$c = ctt_book_context( (array) $atts );
	$out  = ctt_book_css_once();
	$out .= '<aside class="ctt-book" data-ctt-book="' . esc_attr( $c['access'] ) . '">';
	$out .= '<p class="ctt-book__kicker">Book through Coco Tran Travel</p>';
	$out .= '<h3 class="ctt-book__title">Book <em>' . esc_html( $c['hotel'] ) . '</em> through Coco Tran Travel — same rate, plus perks</h3>';
	$out .= ctt_book_perks_shortcode( $atts );
	$out .= ctt_book_button_shortcode( $atts );
	$out .= '</aside>';
	return $out;
}

add_action(
	'init',
	function () {
		add_shortcode( 'ctt_book', 'ctt_book_shortcode' );
		add_shortcode( 'ctt_book_perks', 'ctt_book_perks_shortcode' );
		add_shortcode( 'ctt_book_button', 'ctt_book_button_shortcode' );
	}
);

define( 'CTT_BOOK_CSS', <<<'CSS'
.ctt-book{--ct-accent:var(--global-palette1,#8a6d3b);--ct-ink:var(--global-palette3,#1d1d1b);--ct-grey:var(--global-palette5,#6b6b66);--ct-hair:var(--global-palette7,#e3e0da);
  max-width:var(--ct-ao-max,720px);margin:2.5rem auto;padding:1.5rem 0 1.6rem;border-top:1px solid var(--ct-ink);border-bottom:1px solid var(--ct-hair);
  font-family:var(--global-body-font-family,Georgia,serif);color:var(--ct-ink);}
.ctt-book .ctt-book__kicker{margin:0 0 .5rem;font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-variant-caps:all-small-caps;letter-spacing:.08em;font-size:.78rem;color:var(--ct-grey);}
.ctt-book .ctt-book__title{margin:0 0 .75rem;font-family:var(--global-heading-font-family,Georgia,serif);font-weight:400;font-size:1.5rem;line-height:1.25;}
.ctt-book .ctt-book__title em{font-style:normal;color:var(--ct-accent);}
.ctt-book .ctt-book__perks{margin:0 0 1.1rem;font-size:1rem;line-height:1.55;}
.ctt-book .ctt-book__cta{margin:0;display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;align-items:center;}
.ctt-book .ctt-book__btn,.ctt-book a.button.ctt-book__btn{display:inline-block;padding:.7rem 1.4rem;border:1px solid var(--ct-ink);border-radius:0;background:transparent;color:var(--ct-ink);
  font-family:ui-monospace,"SF Mono",Menlo,Consolas,monospace;font-variant-caps:all-small-caps;letter-spacing:.1em;font-size:.85rem;text-decoration:none;transition:background .15s,color .15s;}
.ctt-book .ctt-book__btn:hover,.ctt-book a.button.ctt-book__btn:hover{background:var(--ct-ink);color:#fff;}
.ctt-book .ctt-book__note{font-size:.82rem;color:var(--ct-grey);}
CSS
);
