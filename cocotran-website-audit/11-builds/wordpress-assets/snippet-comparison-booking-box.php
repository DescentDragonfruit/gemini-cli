<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.
 * On a Comparison brief the shared [ctt_book] box has no hotel of its own, so name the two
 * places from the piece's fields and print the booking note under it. Nothing else changes.
 */
add_filter( 'shortcode_atts_ctt_book', function ( $out ) {
	if ( ( $out['hotel'] ?? '' ) === '' && get_post_type() === 'brief' ) {
		$a = trim( (string) get_post_meta( get_the_ID(), 'side_a_name', true ) );
		$b = trim( (string) get_post_meta( get_the_ID(), 'side_b_name', true ) );
		if ( $a && $b ) { $out['hotel'] = $a . ' or ' . $b; }
		if ( ( $out['perks'] ?? '' ) === '' ) { $out['perks'] = (string) get_post_meta( get_the_ID(), 'comparison_booking_note', true ); }
	}
	return $out;
} );
