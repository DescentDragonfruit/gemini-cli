<?php
/**
 * Coco Tran · Redirects (v1.0, 25 Oct 2026) · WPCode PHP snippet · Run everywhere.
 * 301 redirects the Studio worker manages over REST, kept in one option — used ONLY when a URL changes
 * (a respin replacing old posts, a slug change Coco asked for). Rank Math's redirect API is never called.
 *   GET    /wp-json/coco-tran/v1/redirects                      → the list (edit_others_posts)
 *   POST   /wp-json/coco-tran/v1/redirects {from, to, note}      → add or replace one; `from` is a path, `to` a full URL
 *   DELETE /wp-json/coco-tran/v1/redirects {from}                → remove one
 * Runs at template_redirect priority 1; exact path match (case, trailing slash, query string ignored); never in admin,
 * REST, AJAX or previews; refuses loops; flattens chains (anything that pointed at `from` now points at `to`).
 * Paste into WPCode WITHOUT the opening <?php line.
 */
if (!function_exists('ct_rd_norm')) {
  function ct_rd_norm($p) { $p = parse_url((string) $p, PHP_URL_PATH); $p = strtolower(trim((string) $p)); $p = preg_replace('#/+#', '/', $p); $p = rtrim($p, '/'); return $p === '' ? '/' : $p; }
  function ct_rd_list() { $l = get_option('ct_redirects', []); return is_array($l) ? $l : []; }
  add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || !empty($_GET['preview'])) return;
    $list = ct_rd_list(); if (!$list) return;
    $path = ct_rd_norm($_SERVER['REQUEST_URI'] ?? '/');
    if (empty($list[$path]['to'])) return;
    $to = $list[$path]['to'];
    $host = strtolower((string) parse_url($to, PHP_URL_HOST)); $here = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (ct_rd_norm($to) === $path && ($host === '' || $host === $here)) return; // never loop onto itself
    $list[$path]['hits'] = (int) ($list[$path]['hits'] ?? 0) + 1; $list[$path]['last'] = gmdate('c');
    update_option('ct_redirects', $list, false);
    nocache_headers(); wp_redirect($to, 301, 'Coco Tran'); exit;
  }, 1);
  add_action('rest_api_init', function () {
    $perm = function () { return current_user_can('edit_others_posts'); };
    register_rest_route('coco-tran/v1', '/redirects', [
      ['methods' => 'GET', 'permission_callback' => $perm, 'callback' => function () { return ct_rd_list(); }],
      ['methods' => 'POST', 'permission_callback' => $perm, 'callback' => function ($r) {
        $from = ct_rd_norm((string) $r->get_param('from')); $to = esc_url_raw((string) $r->get_param('to'));
        if ($from === '/' || !$to) return new WP_Error('ct_bad', 'from (a path) and to (a full URL) are required', ['status' => 400]);
        if (ct_rd_norm($to) === $from) return new WP_Error('ct_loop', 'from and to are the same path', ['status' => 400]);
        $list = ct_rd_list();
        $list[$from] = ['to' => $to, 'added' => gmdate('c'), 'by' => get_current_user_id(), 'note' => sanitize_text_field((string) $r->get_param('note')), 'hits' => (int) ($list[$from]['hits'] ?? 0)];
        foreach ($list as $k => $v) { if ($k !== $from && ct_rd_norm($v['to'] ?? '') === $from) $list[$k]['to'] = $to; }
        update_option('ct_redirects', $list, false);
        if (function_exists('rank_math')) { /* nothing: Rank Math's own 404 monitor simply stops seeing the old path */ }
        return ['ok' => true, 'from' => $from, 'to' => $to, 'count' => count($list)];
      }],
      ['methods' => 'DELETE', 'permission_callback' => $perm, 'callback' => function ($r) {
        $from = ct_rd_norm((string) $r->get_param('from')); $list = ct_rd_list(); unset($list[$from]);
        update_option('ct_redirects', $list, false); return ['ok' => true, 'count' => count($list)];
      }],
    ]);
  });
}
