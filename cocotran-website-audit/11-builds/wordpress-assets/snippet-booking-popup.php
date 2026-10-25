<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.  v1.0 · 25 Oct 2026
 * Booking box → "Request this booking" opens as a window on the page (Tally form 2jEqDb)
 * instead of leaving for cocotrantravel.com/contact. The hotel and the page the reader
 * was on travel with the request as hidden fields; Tally emails every request to
 * traveladvisor@cocotran.com (set in the form's settings, not here).
 *
 * Works on every [ctt_book] and [ctt_book_button] already on the site, including the ones
 * inside Kadence Elements. No JavaScript → the button still works: it opens the form page.
 * Remove the snippet and the button goes back to the contact page.
 */
if ( ! defined( 'CT_BOOK_TALLY_FORM' ) ) { define( 'CT_BOOK_TALLY_FORM', '2jEqDb' ); }

add_filter( 'do_shortcode_tag', function ( $out, $tag ) {
	if ( ! in_array( $tag, array( 'ctt_book', 'ctt_book_button' ), true ) || ! is_string( $out ) || false === strpos( $out, 'ctt-book__btn' ) ) { return $out; }
	return preg_replace_callback( '/<a class="button ctt-book__btn" href="([^"]*)"/', function ( $m ) {
		$q = array();
		parse_str( (string) parse_url( html_entity_decode( $m[1] ), PHP_URL_QUERY ), $q );
		$hotel  = isset( $q['hotel'] ) ? (string) $q['hotel'] : '';
		$source = isset( $q['source'] ) ? (string) $q['source'] : '';
		$url    = 'https://tally.so/r/' . CT_BOOK_TALLY_FORM . '?hotel=' . rawurlencode( $hotel ) . '&source=' . rawurlencode( $source );
		return '<a class="button ctt-book__btn ct-book-pop" href="' . esc_url( $url ) . '" data-ct-form="' . esc_attr( CT_BOOK_TALLY_FORM ) . '" data-ct-hotel="' . esc_attr( $hotel ) . '" data-ct-source="' . esc_attr( $source ) . '"';
	}, $out );
}, 10, 2 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) { return; }
	?>
<script id="ct-book-pop">
(function(){
  var loading=null;
  function lib(){
    if(window.Tally&&window.Tally.openPopup){return Promise.resolve();}
    if(loading){return loading;}
    loading=new Promise(function(res,rej){var s=document.createElement('script');s.src='https://tally.so/widgets/embed.js';s.async=true;s.onload=function(){window.Tally&&window.Tally.openPopup?res():rej(new Error('no Tally'))};s.onerror=rej;document.head.appendChild(s);});
    return loading;
  }
  document.addEventListener('click',function(e){
    var a=e.target&&e.target.closest?e.target.closest('a.ct-book-pop'):null;
    if(!a||e.metaKey||e.ctrlKey||e.shiftKey||e.button===1){return;}
    e.preventDefault();
    var opts={layout:'modal',width:560,hiddenFields:{hotel:a.getAttribute('data-ct-hotel')||'',source:a.getAttribute('data-ct-source')||location.href}};
    lib().then(function(){window.Tally.openPopup(a.getAttribute('data-ct-form'),opts);}).catch(function(){window.open(a.href,'_blank','noopener');});
  });
})();
</script>
	<?php
}, 50 );
