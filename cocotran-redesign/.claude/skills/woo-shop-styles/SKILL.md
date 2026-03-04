# Skill: /woo-shop-styles

Generate WooCommerce animation CSS that matches your site's fx-* animation system.

## How to invoke

```
/woo-shop-styles shop-grid
  "Card hover lift + slide-up add-to-cart button"

/woo-shop-styles product-page
  "Gallery zoom, pulsing add-to-cart, tab fade transitions"

/woo-shop-styles cart
  "Stagger line items, smooth quantity updates"

/woo-shop-styles all
  "Full WooCommerce animation stylesheet"
```

## Output

CSS scoped to `.woocommerce` body class (only loads on shop pages).
Paste into `plugin/cocotran-scroll-fx/assets/css/scroll-fx.css`
or into a new `woo.css` file (enqueue separately in the plugin PHP file).

Includes:
- Shop grid card hover effects
- Product image zoom on hover
- Add-to-cart button states (idle, hover, loading, success)
- Cart line item entrance animations
- Checkout field focus transitions
- All reduced-motion and touch-device safe variants

## Skill notes
- Scope all rules under `.woocommerce` to avoid bleeding into other pages
- WooCommerce uses AJAX for cart updates — animate `.cart_totals` on DOM mutation
- Test with Kadence WooCommerce integration plugin active
