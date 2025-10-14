# ATDW Live Products

A lightweight WordPress plugin to display Australian tourism products from the ATDW (Australian Tourism Data Warehouse) ATLAS API using real-time JavaScript fetching - **no database sync required**.

## Description

ATDW Live Products provides a modern, client-side approach to displaying tourism products from the ATDW API. Unlike traditional sync-based plugins, this plugin fetches data in real-time using JavaScript, eliminating the need for database storage, cron jobs, and manual syncing.

## Key Differences from Sync-Based Plugins

### ATDW Live Products (This Plugin)
- ✅ **No database storage** - Products fetched on-demand
- ✅ **No sync required** - Always shows latest data
- ✅ **No cron jobs** - No scheduled tasks needed
- ✅ **Lightweight** - Minimal server resources
- ✅ **Real-time data** - Always up-to-date
- ✅ **Fast setup** - Just add API key and go

### Traditional Sync-Based Plugins
- ❌ Requires database storage
- ❌ Manual or scheduled syncing
- ❌ Cron job management
- ❌ Database maintenance
- ❌ Potential data staleness
- ❌ Complex setup process

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
- **Dashboard**: Quick overview of plugin status
- **Settings Panel**: Configure API credentials and options
- **Error Logging**: View and troubleshoot API errors

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- ATDW API Key (obtain from ATDW)
- JavaScript enabled in browser

## Installation

1. Upload the `atdw-live-products` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to ATDW Live > Settings to configure your API key
4. Test your API connection
5. Add shortcodes to your pages

## Configuration

### API Settings

1. Navigate to **ATDW Live > Settings**
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
```

**Attributes:**
- `st` - Filter by state code (e.g., NSW, VIC, QLD)
- `cats` - Filter by category ID or name
- `region` - Filter by region/area
- `location` - Filter by location
- `type` - Filter by product type
- `fl` - Field List - specify which data fields to return from API (comma-separated, reduces response size)
- `size` - Number of products to display (default: 12)
- `pge` - Page number for pagination (default: 1)
- `sort` - Sort order: 'date', 'product_name asc', 'product_name desc', 'price_asc', 'price_desc', 'drt', 'rnd' (default: none)
- `layout` - Layout type: grid or list (default: grid)
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `show_image` - Show images: yes or no (default: yes)
- `show_excerpt` - Show excerpt: yes or no (default: yes)
- `show_pagination` - Show pagination controls: yes or no (default: yes)
- `view_label` - Button label (default: "View Details")
- `view_slug` - Single product page slug (default: "detail")

**Examples:**
```
[atdw_live_products st="NSW" size="6"]
[atdw_live_products st="QLD" region="Gold Coast" columns="4"]
[atdw_live_products type="ACCOMM" layout="list" show_pagination="no"]
[atdw_live_products st="VIC" cats="Wine" size="12" view_slug="listing"]
[atdw_live_products fl="productName,productImage,productDescription" st="NSW" size="10"]
[atdw_live_products st="NSW" sort="date" size="12"]
[atdw_live_products st="QLD" sort="product_name asc" columns="3"]
[atdw_live_products type="EVENTS" sort="rnd" size="6"]
```

**ATDW API Parameters Used:**
- `st` - State code (from `st` attribute)
- `cats` - Categories (from `cats` attribute)
- `area` - Area/region (from `region` attribute)
- `loc` - Location (from `location` attribute)
- `ptype` - Product type (from `type` attribute)
- `fl` - Field List - subset of response fields (from `fl` attribute)
- `size` - Result limit (from `size` attribute)
- `pge` - Page number (from `pge` attribute)
- `order` - Sort order (from `sort` attribute)

#### Display Single Product
```
[atdw_live_product id="PRODUCT_ID"]
```

**Attributes:**
- `id` - ATDW Product ID (required)

#### Display Search Form
```
[atdw_live_search]
```

**Attributes:**
- `ct` - Pre-filter cities/suburbs (comma-separated)
- `sort` - Sort options to display (comma-separated: date, asc, desc, price_asc, price_desc, drt, random)
- `type` - Product types/categories to filter by (comma-separated)
- `placeholder` - Search input placeholder text
- `show_suburb` - Show suburb/city filter: yes or no (default: yes)
- `show_sort` - Show sort filter: yes or no (default: yes)
- `show_types` - Show type checkboxes: yes or no (default: yes)
- `columns` - Number of columns for results: 2, 3, or 4 (default: 3)

## How It Works

### Architecture

1. **Shortcode Rendering**: When a shortcode is added to a page, the plugin renders a container with data attributes
2. **JavaScript Initialization**: On page load, JavaScript detects containers and prepares requests
3. **AJAX Proxy Request**: JavaScript sends AJAX request to WordPress with API parameters (e.g., st=NSW, size=12)
4. **Server-side Proxy**: PHP proxy validates request and forwards to ATDW API with proper query parameters
5. **API Request**: Makes HTTP request to ATDW API: `https://atlas.atdw-online.com.au/api/atlas/products?key=YOUR_KEY&st=NSW&out=json&mv=ENGLISH`
6. **Response Handling**: Returns JSON data to JavaScript
7. **DOM Rendering**: JavaScript renders products dynamically in the DOM

### Data Flow

```
User Browser → WordPress AJAX → PHP Proxy → ATDW API
                                                ↓
                                          API Response
                                                ↓
                      JavaScript ← JSON Data ←┘
                            ↓
                    Render Products
```

## API Endpoints Used

The plugin can access the following ATDW API endpoints through the proxy:

- `/products` - Search and retrieve products
- `/product` - Get single product details
- `/productservice` - Get product service information
- `/categories` - Get available categories
- `/producttypes` - Get product types
- `/regions` - Get regions
- `/states` - Get states

## Performance Optimization

### Best Practices
- Use reasonable `size` values in shortcodes to avoid overloading the API
- Use specific filters (st, cats, region, etc.) to reduce API response size
- Consider using a CDN for plugin assets
- Enable page caching on your WordPress site (doesn't affect real-time data)

## Troubleshooting

### API Connection Issues
1. Verify your API key is correct
2. Check API base URL is correct
3. Ensure your server can make outbound HTTPS requests
4. Check error logs in **ATDW Live > Logs**

### Products Not Displaying
1. Check browser console for JavaScript errors
2. Verify shortcode syntax is correct
3. Test API connection from dashboard
4. Check error logs in **ATDW Live > Logs**

### Performance Issues
1. Reduce number of products displayed (use `size` attribute)
2. Use specific filters to narrow results
3. Check for JavaScript conflicts with other plugins
4. Ensure your API key has proper access permissions

## Security

### Data Sanitization
- All inputs are sanitized before processing
- AJAX requests require nonce verification
- API parameters validated before forwarding

### API Key Protection
- API key stored securely in database
- Accessible only to authenticated admin users
- All API requests proxied through WordPress backend

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Limitations

### Compared to Sync-Based Plugins
- Requires JavaScript enabled in browser
- API requests made on each page load (no local caching)
- Cannot query products using WordPress queries
- Not suitable for very large product catalogs without filtering

### Best Use Cases
- Organisations displaying their own products (using Organisation Number filter)
- Sites requiring always up-to-date data
- Simple tourism listing pages
- Sites with specific filtering needs (by region, category, etc.)

## Developer Information

### Hooks and Filters

**Actions:**
- `atdw_live_loaded` - Fired when plugin is fully loaded

**Filters:**
- None currently available

### Custom Styling

Override plugin styles by adding CSS to your theme:

```css
/* Custom product card styling */
.atdw-product-card {
    /* Your custom styles */
}
```

### JavaScript API

Access the ATDW Live JavaScript object:

```javascript
jQuery(document).ready(function($) {
    // Access ATDW Live API
    if (typeof ATDWLive !== 'undefined') {
        // Custom functionality
    }
});
```

## Comparison with ATDW Tourism Products Plugin

| Feature | ATDW Live Products | ATDW Tourism Products |
|---------|-------------------|----------------------|
| Database Storage | No | Yes (Custom Post Types) |
| Real-time Data | Yes | No (Sync-based) |
| Sync Required | No | Yes (Manual/Automatic) |
| Cron Jobs | No | Yes |
| Server Resources | Low | Medium-High |
| Setup Complexity | Simple | Complex |
| Search Integration | JavaScript-based | WordPress native |
| SEO Friendliness | Lower | Higher |
| Best For | Small catalogs | Large catalogs |

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/cenemil/atdw-live-products
- Website: https://www.jezweb.com.au

## Changelog

### Version 1.1.0
- Added `fl` (Field List) parameter to control API response fields
- Added price overlay display on product images (from `rate_from` field)
- Improved search result pagination - separate from regular pagination
- Search pagination now preserves all search filters across pages
- Enhanced pagination controls for search vs regular product listing
- Bug fixes and performance improvements

### Version 1.0.0
- Initial release
- Real-time JavaScript fetching via AJAX proxy
- Three shortcodes: products grid, single product, search form
- Organisation number filtering support
- Pagination with ATDW API `pge` parameter
- Advanced search with keyword, suburb, sort, and type filters
- Admin dashboard with settings and error logging
- Responsive design with mobile support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by Jezweb (https://www.jezweb.com.au)
- ATDW API by Australian Tourism Data Warehouse

## Frequently Asked Questions

### How is this different from the sync-based plugin?
This plugin fetches data in real-time using JavaScript, while the sync-based plugin stores products in your WordPress database. This approach is lighter and always shows current data, but requires JavaScript and may be slower for large catalogs.

### Do I need a different API key?
No, you can use the same ATDW API key for both plugins.

### Can I use both plugins at the same time?
Yes, but it's not recommended. Choose one based on your needs.

### Which plugin should I use?
Use **ATDW Live Products** if:
- You have a small to medium catalog
- You want always-current data
- You prefer simple setup
- Server resources are limited

Use **ATDW Tourism Products** if:
- You have a large catalog (500+ products)
- You need WordPress search integration
- SEO is critical
- You want offline product browsing

### Does this work with page caching plugins?
Yes, since the data is fetched client-side, page caching will not affect the product display.

### Can I customize the product display?
Yes, you can override the styles with custom CSS in your theme, or use the Custom CSS field in the plugin settings.

### How often is the data updated?
Data is fetched in real-time from the ATDW API on every page load, ensuring you always display the most current information.

### Can I filter by my organisation only?
Yes, enter your Organisation Number in the plugin settings to automatically filter all products to show only those owned by your organisation.

---

**Note**: This plugin requires an active ATDW API subscription. Contact ATDW for pricing and access details.
