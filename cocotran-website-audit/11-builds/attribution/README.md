# Inquiry source attribution — know which pieces produce clients

Goal: every booking request and inquiry on cocotrantravel.com records where it came from (the cocotran.com piece, the hotel, the campaign), so in 60 days the Library can show "pieces → inquiries", not just clicks.

## How it fits together

1. **cocotran.com** already sends readers to `https://cocotrantravel.com/contact?hotel=<name>&source=<piece URL>` from the booking box (`[ctt_book]`). Add `&src=booking-box` there; the Inquire line in articles should link to `…/contact?source=<piece URL>&src=inquire-line`.
2. **cocotrantravel.com** (`attribution.js`, below) reads those parameters on arrival, stores them as a first-touch record in a cookie (90 days) so they survive browsing, and on any contact form fills hidden fields `source_url`, `hotel`, `src`, `landing`, `first_seen`.
3. The form's notification email / CRM entry therefore carries the source. The GA4 event `inquiry` is fired with the same parameters (`attribution.js` does it if gtag is present).
4. Weekly, the rankings run reads the inquiry source field (export from the form plugin or CRM; or the `inquiries` sheet) and writes `inquiries28` to each Library record.

## Install on cocotrantravel.com (WordPress + Kadence forms assumed; works with any form that allows hidden fields)

1. Add hidden fields to the contact form named exactly: `source_url`, `hotel`, `src`, `landing`, `first_seen` (Kadence Form → Add field → Hidden; Gravity/WPForms: Hidden field with those labels/names).
2. Add `attribution.js` site-wide: Kadence → Elements → new Element, hook `wp_footer`, display entire site, paste the `<script>` from `attribution-element.html` (the file wraps the JS in the `new Function(atob(...))` form so WordPress doesn't mangle `&&`).
3. GA4: Admin → Events → mark `inquiry` and `booking_request` as key events (conversions). Enable cross-domain measurement between cocotran.com and cocotrantravel.com (Admin → Data streams → Configure tag settings → Configure your domains) so one journey is one session.
4. Test: open `https://cocotrantravel.com/contact?hotel=Test%20Hotel&source=https://cocotran.com/property/caruso-ravello/&src=booking-box`, submit the form, confirm the hidden values arrive in the email/CRM entry.

## Reading it

Export inquiries monthly with the five fields. Group by `source_url` → that's the pieces-to-inquiries table. The Library's `goal` for Commercial pieces can then be measured: a Verdict with 400 clicks and 0 inquiries has a booking-box problem; one with 80 clicks and 3 inquiries is the model to copy.
