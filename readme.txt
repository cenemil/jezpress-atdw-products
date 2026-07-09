=== ATDW Live Products ===
Contributors: jezweb
Tags: atdw, tourism, products, api, caching
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 3.0.0
License: Proprietary

Display Australian tourism products from ATDW API with server-side caching and stale-while-revalidate fallback.

== Description ==

ATDW Live Products connects your WordPress site to the Australian Tourism Data Warehouse (ATDW) API, displaying tourism listings with automatic server-side caching.

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
