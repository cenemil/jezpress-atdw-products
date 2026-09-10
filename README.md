# Jezpress ATDW Products

A lightweight WordPress plugin to display Australian tourism products from the ATDW (Australian Tourism Data Warehouse) ATLAS API using real-time JavaScript fetching - **no database sync required**.

## Description

Jezpress ATDW Products provides a modern, client-side approach to displaying tourism products from the ATDW API. Unlike traditional sync-based plugins, this plugin fetches data in real-time using JavaScript, eliminating the need for database storage, cron jobs, and manual syncing.

## Key Differences from Sync-Based Plugins

### Jezpress ATDW Products (This Plugin)
- No database storage - Products fetched on-demand
- No sync required - Always shows latest data
- No cron jobs - No scheduled tasks needed
- Lightweight - Minimal server resources
- Real-time data - Always up-to-date
- Fast setup - Just add API key and go

### Traditional Sync-Based Plugins
- Requires database storage
- Manual or scheduled syncing
- Cron job management
- Database maintenance
- Potential data staleness
- Complex setup process

## Features

### Core Functionality
- **Real-time Fetching**: Data fetched directly from ATDW API via JavaScript
- **Server-side Proxy**: AJAX proxy to avoid CORS issues
- **API Error Logging**: Track and monitor API errors
- **Easy Configuration**: Simple settings interface

### Display Options
- **Shortcodes**: Easy-to-use shortcodes for displaying products
- **Responsive Design**: Mobile-friendly templates
- **Search Functionality**: Advanced product search and filtering
- **Customizable Layout**: Grid, list, and custom column layouts

### Admin Features
- **Single tabbed admin page** (ATDW > Settings/Logs/Shortcodes/License) — API status is folded into the API Settings card, alongside the rest of the settings form
- **Settings Panel**: Configure API credentials and options
- **Error Logging**: View and troubleshoot API errors on the Logs tab

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- ATDW API Key (obtain from ATDW)
- JavaScript enabled in browser

## Installation

1. Upload the `jezpress-atdw-products` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to ATDW > Settings to configure your API key
4. Test your API connection
5. Add shortcodes to your pages

## Configuration

### API Settings

1. Navigate to **ATDW > Settings**
2. Enter your ATDW API Key
3. Configure the following options:
   - **API Base URL**: Default is `https://atlas.atdw-online.com.au/api/atlas`
   - **Organisation Name**: Your organisation name (optional)
   - **Organisation Number**: Filter products by organisation number (optional)
   - **Market Variant**: Choose language (English, Chinese Simplified, Chinese Traditional)
   - **Output Format**: JSON (recommended) or XML

## Usage

### Shortcodes

#### Display Products Grid
```
[atdw_live_products]
[atdw_live_products st="NSW" size="6"]
[atdw_live_products st="QLD" region="Gold Coast" columns="4"]
```

#### Display Single Product
```
[atdw_live_product id="PRODUCT_ID"]
```

#### Display Search Form
```
[atdw_live_search]
[atdw_live_search ct="Dungog,Clarence Town,Stroud"]
```

#### Display Product Meta Field
```
[atdw_live_meta key="name"]
[atdw_live_meta key="gallery" columns="4"]
[atdw_live_meta key="map" map_width="100%" map_height="500px"]
```

## Version History

### 4.0.1
- Published as a new JezPress catalogue entry under the `jezpress-atdw-products` slug (was `atdw-live-products`)
- Renamed the main plugin file and all internal `atdw-live-*`/`class-jezweb-*` class files to a consistent `jatdwp` naming (e.g. `class-jatdwp-admin.php`, `class-jatdwp-updater.php`, `class-jatdwp-license.php`, `public/assets/js/jatdwp.js`) — no functional changes
- Removed a stale, unused `update-info.json` manifest left over from an earlier release process

### 4.0.0
- Version bump marking the plugin rename to "Jezpress ATDW Products" and the merged tabbed admin UI (Settings/Logs/Shortcodes/License)

### 3.4.2
- **Hardened nonce endpoints** — `atdw_live_get_nonce` and `atdw_live_fetch` now send explicit `nocache_headers()`, closing a gap where an aggressive page/CDN cache could cache the nonce/fetch AJAX responses themselves and hand guests a stale nonce or stale data

### 3.4.1
- **Fixed** Purge ATDW Cache button icon alignment — replaced an inline style with a shared `.button .dashicons` rule

### 3.4.0
- **New Shortcodes tab** — the shortcode reference card moved out of Settings into its own tab

### 3.3.0
- **Merged cards** — API Status folded into API Settings (status, Test Connection, Purge Cache all in one card)
- **Removed** the System Information card from the Settings tab

### 3.2.0
- **Renamed plugin** to "Jezpress ATDW Products"
- **Renamed admin menu** — WP sidebar entry changed from "ATDW Live" to "ATDW"

### 3.1.0
- **Merged admin UI** — Dashboard and Settings pages combined into one Settings tab
- **Tabbed panel structure** — ATDW > Settings / Logs / License, matching the JezPress Events admin layout
- **License moved to a tab** — no longer a separate submenu page

### 2.1.1 (2026-03-05)
- **JezPress license management** — license key required for activation with admin gating
- **Admin gating** — Dashboard, Settings, Logs pages show "License Required" when inactive
- **Frontend gating** — shortcodes disabled when license is inactive
- **License page** always accessible regardless of license status

### 2.0.0 (2026-03-05)
- **Server-side transient caching** — API responses cached in WordPress transients (configurable TTL, default 4 hours)
- **Stale-while-revalidate fallback** — when ATDW API returns 502/500 errors, plugin serves last-known-good cached data (fallback TTL = 6x primary)
- **Purge ATDW Cache button** — one-click cache purge from Dashboard page
- **JezPress auto-updates** — plugin receives updates from JezPress update server

### 1.3.1
- Original release (client-side only, no server caching)

## License

Proprietary — Jezweb. Distributed via JezPress. License key required.

## Credits

- Developed by Jezweb (https://www.jezweb.com.au)
- ATDW API by Australian Tourism Data Warehouse
