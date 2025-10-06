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
- **Smart Caching**: Optional server-side caching for improved performance
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
- **Cache Management**: Clear cache when needed

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
   - **Market Variant**: Choose language (English, Chinese Simplified, Chinese Traditional)
   - **Output Format**: JSON (recommended) or XML

### Cache Settings

- **Enable Cache**: Toggle server-side caching on/off
- **Cache TTL**: Set cache lifetime in seconds (default: 3600)

## Usage

### Shortcodes

#### Display Products Grid
```
[atdw_live_products]
```

**Attributes:**
- `state` - Filter by state code (e.g., NSW, VIC, QLD)
- `category` - Filter by category ID or name
- `region` - Filter by region/area
- `location` - Filter by location
- `type` - Filter by product type
- `limit` - Number of products to display (default: 10)
- `layout` - Layout type: grid or list (default: grid)
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `show_image` - Show images: yes or no (default: yes)
- `show_excerpt` - Show excerpt: yes or no (default: yes)

**Examples:**
```
[atdw_live_products state="NSW" limit="6"]
[atdw_live_products state="QLD" region="Gold Coast" columns="4"]
[atdw_live_products type="ACCOMM" layout="list"]
[atdw_live_products state="VIC" category="Wine" limit="12"]
```

**ATDW API Parameters Used:**
- `st` - State code (from `state` attribute)
- `cats` - Categories (from `category` attribute)
- `area` - Area/region (from `region` attribute)
- `loc` - Location (from `location` attribute)
- `ptype` - Product type (from `type` attribute)
- `size` - Result limit (from `limit` attribute)

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
- `show_categories` - Show category filter: yes or no (default: yes)
- `show_regions` - Show region filter: yes or no (default: yes)
- `show_types` - Show type filter: yes or no (default: yes)
- `columns` - Number of columns for results: 2, 3, or 4 (default: 3)

## How It Works

### Architecture

1. **Shortcode Rendering**: When a shortcode is added to a page, the plugin renders a container with data attributes
2. **JavaScript Initialization**: On page load, JavaScript detects containers and prepares requests
3. **AJAX Proxy Request**: JavaScript sends AJAX request to WordPress with API parameters (e.g., st=NSW, size=10)
4. **Server-side Proxy**: PHP proxy validates request and forwards to ATDW API with proper query parameters
5. **Cache Check**: If caching is enabled, checks for cached response based on full URL
6. **API Request**: Makes HTTP request to ATDW API: `https://atlas.atdw-online.com.au/api/atlas/products?key=YOUR_KEY&st=NSW&out=json&mv=ENGLISH`
7. **Response Handling**: Returns JSON data to JavaScript
8. **DOM Rendering**: JavaScript renders products dynamically in the DOM

### Data Flow

```
User Browser → WordPress AJAX → PHP Proxy → ATDW API
                    ↓                ↓
                Cache Check    API Response
                    ↓                ↓
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

### Caching
- Enable server-side caching in settings
- Adjust cache TTL based on data update frequency
- Clear cache when needed from dashboard

### Best Practices
- Use reasonable `limit` values in shortcodes
- Enable caching for production sites
- Use specific filters to reduce API response size
- Consider using a CDN for plugin assets

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
4. Clear cache and try again

### Performance Issues
1. Enable caching in settings
2. Reduce number of products displayed
3. Use specific filters to narrow results
4. Check for JavaScript conflicts with other plugins

## Security

### Data Sanitization
- All inputs are sanitized before processing
- AJAX requests require nonce verification
- API parameters validated before forwarding

### API Key Protection
- API key stored securely in database
- Never exposed to frontend JavaScript
- Only accessible through server-side proxy

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Limitations

### Compared to Sync-Based Plugins
- Requires JavaScript enabled in browser
- Initial page load may be slightly slower
- Cannot query products using WordPress queries
- Not suitable for very large product catalogs (1000+)

### Best Use Cases
- Small to medium product catalogs
- Sites requiring real-time data
- Low-traffic websites
- Sites without cron access

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
- GitHub: [Your Repository URL]
- Documentation: [Your Documentation URL]
- Support Email: [Your Support Email]

## Changelog

### Version 1.0.0
- Initial release
- Real-time JavaScript fetching
- Server-side AJAX proxy
- Shortcodes for display
- Admin dashboard
- Cache management
- Error logging

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by [Your Name]
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
Yes, you can override the styles with custom CSS in your theme.

### How often is the data updated?
Data is fetched in real-time from the ATDW API. Optional server-side caching can cache responses based on your TTL setting.

---

**Note**: This plugin requires an active ATDW API subscription. Contact ATDW for pricing and access details.
