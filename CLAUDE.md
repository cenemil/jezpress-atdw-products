# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

"Jezpress ATDW Products" (plugin slug/text domain `atdw-live-products`, unchanged from an earlier name) is a
proprietary WordPress plugin (distributed via JezPress) that displays Australian
tourism listings from the ATDW (Australian Tourism Data Warehouse) ATLAS API. There is no PHP build step,
package manager, or test suite — this is plain WordPress-plugin PHP + jQuery, edited and deployed directly.

## Commands

There is no build/lint/test tooling in this repo (no `package.json`, `composer.json`, or PHPUnit setup).
To verify changes:
- Lint PHP with WordPress conventions by eye (tabs for indentation, `array()` not `[]`, Yoda-ish conditions
  match the existing style — follow the surrounding file).
- Manually test by activating the plugin in a local WordPress install and exercising the shortcodes on a
  page, or via the `run` skill if driving a real WP instance.
- Bump `Version:` in `atdw-live-products.php`'s header AND the `ATDW_LIVE_VERSION` constant together — they
  must match, and `readme.txt`/`README.md` version history should be updated too.

## Architecture

### Request flow (why there's no database)

Unlike typical ATDW sync plugins, this one never stores product data in the database. The flow is:

1. A shortcode (`public/class-atdw-live-public.php`) renders an empty container `<div>` with `data-*`
   attributes encoding the shortcode's parameters, plus a loading spinner.
2. `public/assets/js/atdw-live.js` (jQuery, `ATDWLive` object) reads those `data-*` attributes on
   `DOMContentLoaded` and calls `admin-ajax.php` (`action=atdw_live_fetch`) to fetch real data.
3. `includes/class-atdw-live-api-proxy.php` (`ajax_fetch_products`) validates the nonce, sanitizes params
   against an explicit allow-list, and proxies the request server-side to the real ATDW ATLAS API — this
   avoids CORS and keeps the API key server-side.
4. The proxy applies **transient caching** (`atdw_live_cache_*` transients, default TTL 4h) and a
   **stale-while-revalidate fallback** transient (6x the primary TTL) that's served if the live ATDW API
   returns an error (500/502) or a request fails — see `get_fallback_or_error()`.
5. JS renders the JSON into HTML client-side (grid/list layouts, pagination, search/filter, meta fields).

Because the nonce can be baked into cached page HTML (full-page caching / CDN), there's a
`wp_ajax_atdw_live_get_nonce` endpoint the JS calls to fetch a fresh nonce when a request unexpectedly
fails — see `initWithFreshNonce()` at the bottom of `atdw-live.js`. Both the nonce and fetch AJAX endpoints
call `nocache_headers()` so a CDN/full-page cache never caches these AJAX responses themselves either.

### Key files

- `atdw-live-products.php` — plugin bootstrap. Defines constants, loads the updater and license classes
  *before* the main singleton (they must initialize independent of `is_admin()` so WP-Cron auto-updates
  still fire), then instantiates `ATDW_Live_Products` which wires up settings/API proxy/admin/public classes.
- `includes/class-atdw-live-settings.php` — thin wrapper around `get_option()`/`update_option()` for all
  `atdw_live_*` options (API key, base URL, market variant, cache TTL, gallery/thumbnail dimensions, custom
  CSS, Google Maps key, etc). `ATDW_Live_Settings::get()` is the single read path other classes use.
- `includes/class-atdw-live-api-proxy.php` — all outbound HTTP to ATDW lives here. Handles ATDW's flaky
  encoding quirks (UTF-16LE/BE responses, trailing garbage characters, BOMs) before `json_decode`, logs
  errors to the `atdw_live_api_errors` option (last 50, viewable in the Logs admin page), and owns the
  cache-key/fallback-key scheme (cache key excludes `key`/`out` params since they don't affect the data).
- `public/class-atdw-live-public.php` — registers all four shortcodes (`atdw_live_products`,
  `atdw_live_product`, `atdw_live_search`, `atdw_live_meta`) and enqueues assets only when a shortcode is
  present on the current post (`has_shortcode()` check avoids loading JS/CSS site-wide). Localizes plugin
  settings into `window.atdwLive` for the JS to consume.
- `public/assets/js/atdw-live.js` — the `ATDWLive` object is the entire frontend runtime: pagination,
  search/filter AJAX calls, and a meta-field renderer (`atdw_live_meta` shortcode) that special-cases
  `map`, `gallery`/`images`, `attributes`, and `nextOccurrence` fields against the raw ATDW product JSON
  (see the `metaKey.toLowerCase() === '...'` branches). Gallery images use GLightbox for the lightbox.
- `admin/class-atdw-live-admin.php` — single top-level "ATDW" admin page (`admin.php?page=atdw-live`; the
  menu label is "ATDW" but the page slug and URL are still `atdw-live`, left unchanged)
  with a tab nav (`?tab=settings|logs|shortcodes|license`), structured the same way as JezPress Events'
  `class-jpem-admin.php`. There is no `admin/views/` directory — each tab is rendered by a private method
  directly in this class (`render_settings_tab()`, `render_logs_tab()`, `render_shortcodes_tab()`); the
  License tab instead delegates to `Jezweb_License::render_tab_content()`. The old separate Dashboard page
  is gone — its API-status summary is now folded into the top of the API Settings card, and its shortcode
  reference card lives in its own Shortcodes tab; the old System Information card was dropped entirely, not
  moved. Settings, Logs, and Shortcodes tabs are hidden from the nav (and unreachable) when the license is
  inactive; License is always reachable.
  Shared card styling (`.admin-page-wrap` / `.admin-page-card` / `.admin-page-card-title`) in
  `admin/assets/css/admin.css` is kept in sync with `jezpress-events/assets/css/admin.css` so the two
  plugins' admin pages look like one product — change both if you touch this system.
- `includes/class-jezweb-license.php` — JezPress license key gating, local to this plugin (not a shared
  file across Jezweb plugins, despite the generic-sounding name). Exposes `should_disable_plugin()`; when
  the license is inactive, the frontend (`ATDW_Live_Public`) is never instantiated (shortcodes render
  nothing) but the admin UI still loads so users can activate a key. `render_tab_content()` renders just
  the License tab's markup (no `<div class="wrap">`/`<h1>` — that's provided by
  `ATDW_Live_Admin::render_page()`); it does **not** register its own admin menu — `menu_slug` is
  hardcoded to `'atdw-live'` purely so its settings-errors group / redirect URLs land on the merged page.
  Includes tamper-resistance checks (`verify_file_integrity()`, source/data signatures) — don't strip these
  when refactoring, they're intentional anti-tamper measures tied to the licensing model.
- `includes/class-jezweb-jezpress-updater.php` — pulls plugin updates from `https://updates.jezpress.com`
  using the license key for auth; initialized unconditionally (not gated by `is_admin()`) so cron-based
  auto-updates keep working outside admin requests.

### Conventions to preserve

- All ATDW API query params are validated against an explicit allow-list in
  `ATDW_Live_API_Proxy::sanitize_params()` — add new params there before wiring them through shortcodes/JS.
- Cache keys and fallback keys are computed the same way (`md5(endpoint . serialize(params))` minus
  `key`/`out`) — keep them in sync if you change caching logic, since the fallback depends on this.
- Debug logging is gated behind `defined('WP_DEBUG') && WP_DEBUG` throughout — follow this pattern rather
  than logging unconditionally.
- Shortcode attributes use ATDW's own API parameter names where practical (`st`, `ct`, `pge`, `fl`, `mv`)
  even though they're not always intuitive — this keeps shortcode params and API params 1:1 traceable.
