# Skill: woo-shop-styles

## Purpose
Generate WooCommerce-compatible CSS animations and interaction styles that work
with your Kadence theme + Kadence WooCommerce extension. Brings the same modern,
dynamic feel to your shop pages as the rest of the site.

## When to Use
- You're adding WooCommerce to cocotran.com or cocotrantravel.com
- You want product cards to have hover effects in the shop grid
- You want the product page (single product) to feel polished and animated
- You want the cart and checkout to have smooth micro-interactions

## How to Invoke

**Shop grid hover effects:**
> "Add hover animations to my WooCommerce shop grid — lift effect on cards,
>  slide-up quick-add button on hover"

**Single product page:**
> "Animate the product page — gallery zoom on hover, pulsing add-to-cart button,
>  smooth tab content transitions"

**Cart animations:**
> "Add smooth entry animations to cart line items"

**Full WooCommerce stylesheet:**
> "Generate a complete WooCommerce animation stylesheet for my theme"

---

## Component Styles

### Shop Grid — Product Cards
```css
/* ── WooCommerce Shop Grid ─────────────────── */

/* Card container */
.woocommerce ul.products li.product {
  transition: transform var(--duration-base) var(--ease-smooth),
              box-shadow var(--duration-base) var(--ease-smooth);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.woocommerce ul.products li.product:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-lg);
}

/* Product image zoom on hover */
.woocommerce ul.products li.product .woocommerce-LoopProduct-link img {
  transition: transform var(--duration-slow) var(--ease-smooth);
}
.woocommerce ul.products li.product:hover .woocommerce-LoopProduct-link img {
  transform: scale(1.05);
}

/* Add to cart button — hidden by default, slides up on hover */
.woocommerce ul.products li.product .button {
  opacity: 0;
  transform: translateY(10px);
  transition: opacity var(--duration-fast) var(--ease-smooth),
              transform var(--duration-fast) var(--ease-smooth);
}
.woocommerce ul.products li.product:hover .button {
  opacity: 1;
  transform: translateY(0);
}

/* Price styling */
.woocommerce ul.products li.product .price {
  color: var(--color-primary);
  font-weight: var(--font-weight-semibold);
}

/* Product title */
.woocommerce ul.products li.product .woocommerce-loop-product__title {
  font-family: var(--font-heading);
  transition: color var(--duration-fast) var(--ease-smooth);
}
.woocommerce ul.products li.product:hover .woocommerce-loop-product__title {
  color: var(--color-primary);
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .woocommerce ul.products li.product,
  .woocommerce ul.products li.product .woocommerce-LoopProduct-link img,
  .woocommerce ul.products li.product .button { transition: none; }
}

/* Mobile — disable lift on touch devices */
@media (max-width: 767px) {
  .woocommerce ul.products li.product:hover { transform: none; box-shadow: none; }
  .woocommerce ul.products li.product .button { opacity: 1; transform: none; }
}
```

### Single Product Page
```css
/* ── Single Product Page ───────────────────── */

/* Product gallery — zoom on hover */
.woocommerce div.product .woocommerce-product-gallery__image img {
  transition: transform var(--duration-slow) var(--ease-smooth);
}
.woocommerce div.product .woocommerce-product-gallery__image:hover img {
  transform: scale(1.03);
}

/* Add to cart button — pulse animation */
.woocommerce div.product form.cart .single_add_to_cart_button {
  background: var(--color-primary);
  border-radius: var(--radius-md);
  transition: background var(--duration-fast) var(--ease-smooth),
              transform var(--duration-fast) var(--ease-spring),
              box-shadow var(--duration-fast) var(--ease-smooth);
}
.woocommerce div.product form.cart .single_add_to_cart_button:hover {
  background: var(--color-primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-color);
}
.woocommerce div.product form.cart .single_add_to_cart_button:active {
  transform: scale(0.97) translateY(0);
}

/* Tab content fade transition */
.woocommerce div.product .woocommerce-tabs .panel {
  animation: fadeIn var(--duration-base) var(--ease-out);
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
```

### Cart & Checkout
```css
/* ── Cart ──────────────────────────────────── */

/* Line items — stagger reveal */
.woocommerce-cart table.cart tbody tr {
  opacity: 0;
  animation: slideInRow var(--duration-base) var(--ease-out) forwards;
}
.woocommerce-cart table.cart tbody tr:nth-child(1) { animation-delay: 0ms; }
.woocommerce-cart table.cart tbody tr:nth-child(2) { animation-delay: 80ms; }
.woocommerce-cart table.cart tbody tr:nth-child(3) { animation-delay: 160ms; }
.woocommerce-cart table.cart tbody tr:nth-child(4) { animation-delay: 240ms; }

@keyframes slideInRow {
  from { opacity: 0; transform: translateX(-12px); }
  to   { opacity: 1; transform: translateX(0); }
}

/* Update cart button */
.woocommerce-cart .actions .button {
  transition: background var(--duration-fast) var(--ease-smooth),
              transform var(--duration-fast) var(--ease-spring);
}
.woocommerce-cart .actions .button:hover {
  transform: translateY(-2px);
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .woocommerce-cart table.cart tbody tr { animation: none; opacity: 1; }
}
```

---

## Skill Notes
- Always test WooCommerce styles with the Kadence WooCommerce extension active
- WooCommerce adds `.woocommerce` class to `body` on shop pages — scope all rules to this
- Some WooCommerce elements use inline styles — may need `!important` for overrides
- Cart total updates use AJAX — add transition on `.cart_totals` to smooth the refresh
- Always check mobile: WooCommerce product grid goes to 2 columns at 767px, 1 at 480px
