=== Jezpress ATDW Products ===
Contributors: jezweb
Tags: atdw, tourism, products, api, caching
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 4.0.0
License: Proprietary

Display Australian tourism products from ATDW API with server-side caching and stale-while-revalidate fallback.

== Description ==

Jezpress ATDW Products connects your WordPress site to the Australian Tourism Data Warehouse (ATDW) API, displaying tourism listings with automatic server-side caching.

Features:

* Server-side transient caching with configurable TTL (default 4 hours)
* Stale-while-revalidate fallback — serves cached data when the API is unavailable
* Shortcodes for product listings, single products, search, and metadata
* Google Maps integration for product locations
* Customisable image sizes and CSS styling
* Purge Cache button in admin dashboard
* JezPress license management
* Automatic updates via JezPress

== Changelog ==

= 4.0.0 =
* Version bump marking the plugin rename to "Jezpress ATDW Products" and the merged tabbed admin UI (Settings/Logs/Shortcodes/License)

= 3.4.2 =
* Hardened the nonce endpoints (atdw_live_get_nonce, atdw_live_fetch) with explicit nocache_headers() — closes a loophole where an aggressive page/CDN cache (the same class of cache already known to bake stale nonces into page HTML) could cache these AJAX responses too, handing guests a stale/expired nonce or stale product data

= 3.4.1 =
* Fixed Purge ATDW Cache button icon alignment — replaced the inline style with a shared `.button .dashicons` rule in admin.css

= 3.4.0 =
* Added a new Shortcodes tab and moved the shortcode reference card there from the Settings tab

= 3.3.0 =
* Merged the API Status card into API Settings — status, Test Connection and Purge Cache now live in that one card
* Removed the System Information card from the Settings tab

= 3.2.0 =
* Renamed plugin to "Jezpress ATDW Products"
* Renamed the WP admin sidebar menu from "ATDW Live" to "ATDW"

= 3.1.0 =
* Admin UI restructured into a single "ATDW" page with Settings, Logs and License tabs (matches the JezPress Events admin layout)
* The separate Dashboard page is gone — its API status summary, shortcode reference and system info now sit at the top of the Settings tab
* The License page is now a tab on the same page rather than a separate submenu

= 3.0.0 =
* Fixed frontend AJAX nonce going stale for anonymous visitors behind page/CDN caching
* Added atdw_live_get_nonce endpoint that issues a fresh nonce at request time (uncached), instead of relying on the nonce baked into cached page HTML
* Frontend now fetches a fresh nonce on load for both logged-in and logged-out visitors before making product requests

= 2.1.1 =
* Added JezPress license management with admin gating
* Admin pages (Dashboard, Settings, Logs) show license gate when inactive
* Frontend shortcodes disabled when license is inactive
* License page accessible regardless of license status

= 2.0.3 =
* Removed license code for stable live deployment
* Caching and updater only

= 2.0.0 =
* Added server-side transient caching (configurable TTL, default 4h)
* Added stale-while-revalidate fallback (6x TTL = 24h)
* Added Purge ATDW Cache button on Dashboard
* Added JezPress auto-updater
* Updated branding and proprietary license

= 1.3.1 =
* Original release by Jezweb (Cenemil)
* Client-side AJAX rendering with ATDW API proxy
