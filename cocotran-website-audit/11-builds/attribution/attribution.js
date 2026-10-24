/* Coco Tran Travel — inquiry source attribution.
   Reads ?hotel, ?source, ?src (and UTM) on arrival, keeps a first-touch record for 90 days,
   fills hidden form fields named source_url / hotel / src / landing / first_seen, and fires GA4 events. */
(function () {
  var KEY = "ctt_attr";
  var DAYS = 90;
  function read() { try { var m = document.cookie.match(new RegExp("(?:^|; )" + KEY + "=([^;]*)")); return m ? JSON.parse(decodeURIComponent(m[1])) : null; } catch (e) { return null; } }
  function write(o) { try { var d = new Date(); d.setTime(d.getTime() + DAYS * 864e5); document.cookie = KEY + "=" + encodeURIComponent(JSON.stringify(o)) + "; expires=" + d.toUTCString() + "; path=/; SameSite=Lax; Secure"; } catch (e) {} }
  var q = new URLSearchParams(location.search);
  var incoming = { hotel: q.get("hotel") || "", source_url: q.get("source") || "", src: q.get("src") || q.get("utm_source") || "", campaign: q.get("utm_campaign") || "" };
  var ref = document.referrer || "";
  var hasSignal = incoming.hotel || incoming.source_url || incoming.src || /cocotran\.com/.test(ref);
  var rec = read();
  if (!rec) { rec = { first_seen: new Date().toISOString().slice(0, 10), landing: location.pathname, referrer: ref }; }
  if (hasSignal) { // a new signal overwrites hotel/source (last hotel wins), first_seen/landing stay first-touch
    if (incoming.hotel) rec.hotel = incoming.hotel;
    if (incoming.source_url) rec.source_url = incoming.source_url; else if (!rec.source_url && /cocotran\.com/.test(ref)) rec.source_url = ref;
    if (incoming.src) rec.src = incoming.src;
    if (incoming.campaign) rec.campaign = incoming.campaign;
  }
  write(rec);
  function fill() {
    var map = { source_url: rec.source_url || "", hotel: rec.hotel || "", src: rec.src || "", landing: rec.landing || "", first_seen: rec.first_seen || "" };
    Object.keys(map).forEach(function (name) {
      var els = document.querySelectorAll('input[name="' + name + '"], input[name$="[' + name + ']"], input[data-attr="' + name + '"]');
      for (var i = 0; i < els.length; i++) { if (!els[i].value) els[i].value = map[name]; }
      // Kadence forms name fields kb_field_N; allow a data-label match as a fallback
      var byLabel = document.querySelectorAll('input[type="hidden"][aria-label="' + name + '"]');
      for (var j = 0; j < byLabel.length; j++) { if (!byLabel[j].value) byLabel[j].value = map[name]; }
    });
    // prefill a visible "hotel" text field if the form has one and it's empty
    var h = document.querySelector('input[name="hotel"]:not([type="hidden"]), input[placeholder*="hotel" i]');
    if (h && !h.value && rec.hotel) h.value = rec.hotel;
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fill); else fill();
  document.addEventListener("submit", function (e) {
    var f = e.target; if (!f || f.tagName !== "FORM") return;
    fill();
    var isBooking = !!(rec.hotel || rec.src === "booking-box");
    if (typeof gtag === "function") {
      gtag("event", isBooking ? "booking_request" : "inquiry", { source_url: rec.source_url || "", hotel: rec.hotel || "", src: rec.src || "", landing: rec.landing || "" });
    }
  }, true);
  window.cttAttribution = rec;
})();
