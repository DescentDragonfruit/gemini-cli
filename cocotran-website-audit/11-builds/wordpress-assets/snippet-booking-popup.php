<?php
/**
 * Code Snippets (WPCode) · PHP · Run everywhere.  v2.2 · 25 Oct 2026 (no PHP tags inside: WPCode strips them)
 * "Request this booking" opens a form in a window on the page, in the article's own style, and sends it
 * through Splitforms (the same service the Proposal Studio uses) to the inbox the access key is registered to.
 * No Tally, no plugin. The hotel and the page travel with the request. Without JavaScript the button still
 * works: it goes to the contact page as before.
 *
 * To change the inbox: register a key for that address in Splitforms → Forms and paste it below.
 * Works on every [ctt_book] and [ctt_book_button] already on the site, including inside Kadence Elements.
 */
if ( ! defined( 'CT_BOOK_SF_KEY' ) ) { define( 'CT_BOOK_SF_KEY', 'df6287210a42464dbb117310fee7369a' ); }

add_filter( 'do_shortcode_tag', function ( $out, $tag ) {
	if ( ! in_array( $tag, array( 'ctt_book', 'ctt_book_button' ), true ) || ! is_string( $out ) || false === strpos( $out, 'ctt-book__btn' ) ) { return $out; }
	return preg_replace_callback( '/<a class="button ctt-book__btn" href="([^"]*)"/', function ( $m ) {
		$q = array();
		parse_str( (string) parse_url( html_entity_decode( $m[1] ), PHP_URL_QUERY ), $q );
		$hotel  = isset( $q['hotel'] ) ? (string) $q['hotel'] : '';
		$source = isset( $q['source'] ) ? (string) $q['source'] : '';
		return '<a class="button ctt-book__btn ct-book-pop" href="' . $m[1] . '" data-ct-hotel="' . esc_attr( $hotel ) . '" data-ct-source="' . esc_attr( $source ) . '"';
	}, $out );
}, 10, 2 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) { return; }
	// WPCode strips PHP tags from snippets, so the markup is printed from a string rather than by leaving PHP mode.
	$html = <<<'HTML'
<style id="ct-book-pop-css">
.ct-bk-overlay{position:fixed;inset:0;z-index:2147483000;background:rgba(0,0,0,.42);display:block;overflow-y:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;padding:24px 16px;box-sizing:border-box;opacity:0;transition:opacity .2s}
.ct-bk-overlay.is-open{opacity:1}
.ct-bk{position:relative;width:min(560px,100%);margin:0 auto 40px;background:#fff;color:#000;padding:34px 36px 32px;box-sizing:border-box;font-family:sans,"Inter",system-ui,sans-serif;border-top:3px solid #000}
.ct-bk__close{position:absolute;top:14px;right:14px;border:0;background:none;font:inherit;font-size:13px;letter-spacing:.39px;text-transform:uppercase;cursor:pointer;color:#000;padding:6px}
.ct-bk__kicker{font-weight:300;font-size:13px;letter-spacing:.39px;text-transform:uppercase;margin:0 0 6px}
.ct-bk__title{font-weight:400;font-size:17px;line-height:24px;letter-spacing:2px;text-transform:uppercase;margin:0 0 10px}
.ct-bk__lead{font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:26px;margin:0 0 22px}
.ct-bk__grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 20px}
.ct-bk__grid .full{grid-column:1/-1}
.ct-bk label{display:block;font-weight:300;font-size:12px;letter-spacing:1px;text-transform:uppercase;margin:0 0 6px}
.ct-bk input,.ct-bk textarea{width:100%;box-sizing:border-box;border:0;border-bottom:1px solid #000;border-radius:0;background:transparent;padding:6px 0 8px;font-family:var(--global-heading-font-family,Georgia,serif);font-size:16px;line-height:24px;color:#000;outline:0}
.ct-bk input:focus,.ct-bk textarea:focus{border-bottom-width:2px}
.ct-bk textarea{min-height:72px;resize:vertical}
.ct-bk__foot{display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center;margin-top:26px}
.ct-bk__send{border:0;background:#000;color:#fff;font:inherit;font-weight:300;font-size:13px;letter-spacing:.39px;text-transform:uppercase;padding:14px 26px;cursor:pointer}
.ct-bk__send[disabled]{opacity:.5;cursor:default}
.ct-bk__note{font-family:var(--global-heading-font-family,Georgia,serif);font-size:14px;color:#000;opacity:.78}
.ct-bk__done{font-family:var(--global-heading-font-family,Georgia,serif);font-size:17px;line-height:28px;margin:0}
.ct-bk .hp{position:absolute;left:-9999px;top:-9999px;height:0;width:0;opacity:0}
@media (max-width:600px){.ct-bk-overlay{padding:12px 10px}.ct-bk{padding:26px 20px 28px;margin-bottom:24px}.ct-bk__grid{grid-template-columns:1fr;gap:16px}}
</style>
<script id="ct-book-pop">
(function(){
  var KEY="__CT_BOOK_SF_KEY__";
  function esc(s){return String(s||"").replace(/[&<>"]/g,function(c){return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]})}
  function build(hotel,source){
    var h=hotel||"this hotel";
    return '<div class="ct-bk" role="dialog" aria-modal="true" aria-label="Request this booking">'+
      '<button type="button" class="ct-bk__close" data-close>Close ✕</button>'+
      '<p class="ct-bk__kicker">Book through Coco Tran Travel</p>'+
      '<h3 class="ct-bk__title">Request this booking</h3>'+
      '<p class="ct-bk__lead">Same rate as booking direct, plus the perks my partner programmes carry. Tell me who is travelling and when, and I reply personally, usually the same day.</p>'+
      '<form class="ct-bk__form" novalidate>'+
      '<div class="ct-bk__grid">'+
      '<div class="full"><label for="ctbk-hotel">Hotel</label><input id="ctbk-hotel" name="hotel" type="text" value="'+esc(hotel)+'" required></div>'+
      '<div><label for="ctbk-in">Check-in</label><input id="ctbk-in" name="check_in" type="date" required></div>'+
      '<div><label for="ctbk-out">Check-out</label><input id="ctbk-out" name="check_out" type="date" required></div>'+
      '<div class="full"><label for="ctbk-who">Who is travelling?</label><input id="ctbk-who" name="travellers" type="text" placeholder="2 adults, or 2 adults and a 6-year-old" required></div>'+
      '<div><label for="ctbk-name">Your name</label><input id="ctbk-name" name="name" type="text" autocomplete="name" required></div>'+
      '<div><label for="ctbk-email">Your email</label><input id="ctbk-email" name="email" type="email" autocomplete="email" required></div>'+
      '<div class="full"><label for="ctbk-phone">Phone or WhatsApp (optional)</label><input id="ctbk-phone" name="phone" type="tel" autocomplete="tel"></div>'+
      '<div class="full"><label for="ctbk-notes">Anything I should know? (optional)</label><textarea id="ctbk-notes" name="notes" placeholder="Room type you have in mind, an occasion, flexibility on dates, a budget per night"></textarea></div>'+
      '</div>'+
      '<input class="hp" type="text" name="botcheck" tabindex="-1" autocomplete="off">'+
      '<div class="ct-bk__foot"><button type="submit" class="ct-bk__send">Send the request</button><span class="ct-bk__note">Name, dates, hotel — I reply personally.</span></div>'+
      '</form></div>';
  }
  function open(a){
    var hotel=a.getAttribute("data-ct-hotel")||"", source=a.getAttribute("data-ct-source")||location.href.split("#")[0];
    var ov=document.createElement("div");ov.className="ct-bk-overlay";ov.setAttribute("data-lenis-prevent","");ov.setAttribute("data-scroll-lock-scrollable","");ov.innerHTML=build(hotel,source);document.body.appendChild(ov);["wheel","touchmove"].forEach(function(ev){ov.addEventListener(ev,function(e){e.stopPropagation()},{passive:true})});
    var prev=document.body.style.overflow;document.body.style.overflow="hidden";
    requestAnimationFrame(function(){ov.classList.add("is-open")});
    function close(){ov.classList.remove("is-open");document.body.style.overflow=prev;setTimeout(function(){ov.remove()},200);document.removeEventListener("keydown",onKey)}
    function onKey(e){if(e.key==="Escape")close()}
    document.addEventListener("keydown",onKey);
    ov.addEventListener("click",function(e){if(e.target===ov||e.target.closest("[data-close]"))close()});
    var form=ov.querySelector("form");var first=ov.querySelector("#ctbk-in");first&&first.focus();
    form.addEventListener("submit",function(e){
      e.preventDefault();
      if(!form.checkValidity()){form.reportValidity();return}
      var btn=form.querySelector(".ct-bk__send");btn.disabled=true;btn.textContent="Sending…";
      var f=new FormData(form);var fields={};f.forEach(function(v,k){fields[k]=String(v)});
      var body=Object.assign({access_key:KEY,subject:"Booking request · "+(fields.hotel||hotel)+" · "+fields.name,from_name:fields.name||"Reader",replyto:fields.email,source_page:source,site:"cocotran.com"},fields);
      fetch("https://splitforms.com/api/submit",{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json"},body:JSON.stringify(body)})
        .then(function(r){return r.json().catch(function(){return {}}).then(function(j){return r.ok&&j.success!==false})})
        .catch(function(){return false})
        .then(function(ok){
          if(ok){form.innerHTML='<p class="ct-bk__done">Received. I will check availability and rates and come back to you personally, usually the same day.<br><br>Coco</p>';}
          else{btn.disabled=false;btn.textContent="Send the request";var n=form.querySelector(".ct-bk__note");if(n)n.textContent="That did not go through. Email traveladvisor@cocotran.com and I will see it on my phone.";}
        });
    });
  }
  document.addEventListener("click",function(e){
    var a=e.target&&e.target.closest?e.target.closest("a.ct-book-pop"):null;
    if(!a||e.metaKey||e.ctrlKey||e.shiftKey||e.button===1||!KEY||KEY.indexOf("PASTE")===0){return;}
    e.preventDefault();open(a);
  });
})();
</script>
HTML;
	echo str_replace( '__CT_BOOK_SF_KEY__', esc_js( CT_BOOK_SF_KEY ), $html );
}, 50 );
