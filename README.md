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

### Google Maps Settings

To enable interactive Google Maps embeds in the `atdw_live_meta` shortcode (when using `key="map"`):

1. Navigate to **ATDW Live > Settings**
2. Scroll to the **Google Maps Settings** section
3. Enter your **Google Maps API Key**:
   - Obtain an API key from the [Google Cloud Console](https://console.cloud.google.com/)
   - Enable the **Maps Embed API** for your project
   - Copy the API key to the plugin settings
4. (Optional) Configure the **Google Maps Base URL** if needed (default: `https://www.google.com/maps/embed/v1/place`)

**Without Google Maps API Key:**
If no API key is configured, the map shortcode will display coordinates with a text link to Google Maps as a fallback.

**With Google Maps API Key:**
When configured, the map shortcode will display an interactive embedded Google Map with customizable dimensions and zoom level.

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
- `ct` - Filter by city/town (comma-separated values, e.g., "Sydney,Melbourne,Brisbane")
- `fl` - Field List - specify which data fields to return from API (comma-separated, reduces response size)
- `size` - Number of products to display (default: 12)
- `pge` - Page number for pagination (default: 1)
- `sort` - Sort order: 'date' (sorts by next_occurrence asc), 'product_name asc', 'product_name desc', 'price_asc' (sorts by rate_from asc), 'price_desc' (sorts by rate_from desc), 'drt', 'rnd' (default: none)
- `layout` - Layout type: grid or list (default: grid)
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `show_image` - Show images: yes or no (default: yes)
- `show_excerpt` - Show excerpt: yes or no (default: yes)
- `description_length` - Number of words to display in description excerpt (default: 60)
- `price_before` - Text to prepend to price display (e.g., 'From ', 'Starting at ') (default: empty)
- `price_after` - Text to append to price display (e.g., ' per night', ' pp') (default: empty)
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
[atdw_live_products st="NSW" ct="Sydney,Newcastle,Wollongong" size="10"]
[atdw_live_products ct="Melbourne,Geelong" type="ACCOMM" columns="4"]
[atdw_live_products st="QLD" show_excerpt="yes" description_length="30" columns="3"]
[atdw_live_products type="EVENTS" description_length="100" layout="list"]
[atdw_live_products type="ACCOMM" price_before="From " price_after=" per night" columns="3"]
[atdw_live_products st="NSW" price_before="Starting at " layout="grid"]
[atdw_live_products type="TOUR" price_after=" pp" show_image="yes"]
```

**ATDW API Parameters Used:**
- `st` - State code (from `st` attribute)
- `cats` - Categories (from `cats` attribute)
- `area` - Area/region (from `region` attribute)
- `loc` - Location (from `location` attribute)
- `ptype` - Product type (from `type` attribute)
- `ct` - City/town filter (from `ct` attribute, comma-separated)
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

#### Display Product Meta Field
```
[atdw_live_meta key="FIELD_NAME"]
```

Display a specific meta field from a product. This is useful for creating custom product detail pages where you want to display individual product fields.

**Attributes:**
- `id` - ATDW Product ID (optional - automatically uses URL query parameter `?id=PRODUCT_ID` if not provided)
- `key` - Meta key to display (required) - see supported keys below
- `format` - Output format: 'html' (default), 'text', or 'raw'
- `class` - Custom CSS class for styling
- `before` - Content to display before the value
- `after` - Content to display after the value
- `size` - Image size for gallery: 'thumbnail', 'medium' (default), 'large', 'full'
- `columns` - Number of columns for gallery display: 1-5 (default: 3)
- `link` - Whether to link gallery images to full size: 'yes' (default) or 'no'

**Smart ID Detection:**
The shortcode automatically detects the product ID from the URL query parameter. For example, if your page URL is `example.com/detail?id=56b2386bdae9a4b66d8b4c6f`, you can simply use:
```
[atdw_live_meta key="name"]
[atdw_live_meta key="gallery" columns="4"]
```

This makes it easy to create a single product detail page template that dynamically displays different products based on the URL parameter. You can still override this by explicitly providing the `id` attribute in the shortcode.

**Performance Optimization:**
When multiple `atdw_live_meta` shortcodes are used on the same page with the same product ID, the plugin automatically:
1. Groups all shortcodes by product ID
2. Makes a single API request per unique product ID
3. Populates all meta fields from that one API response
4. Caches the product data for instant reuse

This means you can use as many meta shortcodes as needed on a detail page without worrying about making multiple API calls. For example, using 10 different meta shortcodes for the same product will only trigger 1 API request instead of 10.

**Automatic Data Fetching:**
If your page URL contains an `?id=PRODUCT_ID` query parameter, the plugin will automatically fetch that product's data from the API even if there are no meta shortcodes on the page. This makes the product data available:
- In the JavaScript console: `window.atdwProduct['PRODUCT_ID']`
- For custom JavaScript code to access and use
- For dynamically added shortcodes

The fetched data is cached, so subsequent requests for the same product (even on page navigation back) will use the cached version instead of making another API call.

**Supported Meta Keys:**
- `name` or `title` - Product name
- `description` - Product description
- `phone` - **ENHANCED:** Phone number (automatically linked with tel:) - **Now prioritizes `communication` array with attributeIdCommunication='CAPHENQUIR', falls back to direct phone field**
- `email` - **ENHANCED:** Email address (automatically linked with mailto:) - **Now prioritizes `communication` array with attributeIdCommunication='CAEMENQUIR', falls back to direct email field**
- `website` - **ENHANCED:** Website URL (automatically linked) - **Now prioritizes `communication` array with attributeIdCommunication='CAURENQUIR', falls back to websiteUrl field**
- `address` - **NEW:** Formatted physical address (automatically combines addressLine1, cityName, stateName, and addressPostalCode from addresses array)
- `map` - **NEW & ENHANCED:** Interactive Google Maps embed (extracts geocodeGdaLatitude and geocodeGdaLongitude from addresses array, automatically embeds Google Maps if API key is configured, otherwise shows coordinates with link to Google Maps)
  - **Parameters:** `map_width` (default: 100%), `map_height` (default: 400px), `map_zoom` (default: 15)
  - **Requires:** Google Maps API key (set in plugin settings)
- `locality` - Suburb/locality
- `area` - Area/region
- `state` - State
- `postcode` - Postcode
- `country` - Country
- `gallery` or `images` - Image gallery (special rendering with grid layout)
- `image` or `featured_image` - Featured/single image
- `price` or `rate` - Price (formatted with $ symbol)

You can also use any API field name directly (e.g., `productName`, `productDescription`, `websiteUrl`, etc.)

**Communication Array Priority:**
The plugin now intelligently extracts phone, email, and website information from the `communication` array in the API response, using specific attribute IDs:
- **Phone**: Looks for `attributeIdCommunication='CAPHENQUIR'` (Primary Phone)
- **Email**: Looks for `attributeIdCommunication='CAEMENQUIR'` (Email Enquiries)
- **Website**: Looks for `attributeIdCommunication='CAURENQUIR'` (URL Enquiries)

If these are not found in the communication array, the plugin will fall back to the traditional direct field approach.

**Examples:**

Using URL query parameter (recommended for detail pages):
```
<!-- On a page with URL: example.com/detail?id=56b2386bdae9a4b66d8b4c6f -->

<!-- Display product name -->
[atdw_live_meta key="name"]

<!-- Display product description -->
[atdw_live_meta key="description" format="html"]

<!-- Display phone with custom label -->
[atdw_live_meta key="phone" before="Call us: "]

<!-- Display email with custom styling -->
[atdw_live_meta key="email" class="contact-email"]

<!-- Display image gallery in 4 columns -->
[atdw_live_meta key="gallery" columns="4"]

<!-- Display gallery without links -->
[atdw_live_meta key="gallery" link="no"]

<!-- Display featured image -->
[atdw_live_meta key="image" size="large"]

<!-- Display formatted address (combines all address fields) -->
[atdw_live_meta key="address" before="<strong>Location:</strong> "]

<!-- Display map with default settings (100% width, 400px height, zoom 15) -->
[atdw_live_meta key="map"]

<!-- Display map with custom dimensions -->
[atdw_live_meta key="map" map_width="600px" map_height="450px"]

<!-- Display map with custom zoom level -->
[atdw_live_meta key="map" map_zoom="12"]

<!-- Display map with all custom parameters -->
[atdw_live_meta key="map" map_width="100%" map_height="500px" map_zoom="17"]

<!-- Display website -->
[atdw_live_meta key="website"]

<!-- Display price -->
[atdw_live_meta key="price" before="From "]
```

Using explicit ID (useful for fixed content):
```
<!-- Display specific product name -->
[atdw_live_meta id="56b2386bdae9a4b66d8b4c6f" key="name"]

<!-- Display specific product gallery -->
[atdw_live_meta id="56b2386bdae9a4b66d8b4c6f" key="gallery" columns="3"]
```

**Use Case Example:**
Create a product detail page template (e.g., `/detail`) and add these shortcodes:
```
<h1>[atdw_live_meta key="name"]</h1>
<div class="product-info">
  [atdw_live_meta key="gallery" columns="4"]
  [atdw_live_meta key="description"]
  <div class="contact-details">
    [atdw_live_meta key="phone" before="<strong>Phone:</strong> "]
    [atdw_live_meta key="email" before="<strong>Email:</strong> "]
    [atdw_live_meta key="website" before="<strong>Website:</strong> "]
  </div>
  <div class="location-details">
    [atdw_live_meta key="address" before="<strong>Address:</strong> "]
    [atdw_live_meta key="map" before="<strong>Coordinates:</strong> "]
  </div>
</div>
```
Then link to products using: `/detail?id=PRODUCT_ID`

**Note:** The `map` shortcode outputs coordinates with data attributes (`data-lat` and `data-lng`) that can be easily integrated with mapping libraries like Google Maps, Leaflet, or Mapbox.

**Special Considerations for Gallery:**
The gallery key renders images in a responsive grid layout with the following features:
- Configurable column count (1-5 columns)
- Responsive design (automatically adjusts for mobile)
- Optional linking to full-size images
- Lazy loading for performance
- Hover effects for better UX

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

### Version 1.2.0
- Added new `[atdw_live_meta]` shortcode for displaying individual product meta fields
- Smart ID detection: automatically uses URL query parameter `?id=PRODUCT_ID` if not explicitly provided
- Automatic data fetching: fetches product data when `?id` is in URL, even without shortcodes
- Product data caching: stores fetched products in memory for instant reuse
- Global data access: product data available via `window.atdwProduct` for custom JavaScript
- Performance optimization: groups meta shortcodes by product ID and makes single API call per product
- UTF-16 encoding support: handles ATDW API responses in UTF-16LE, UTF-16BE, and UTF-8
- Support for gallery/images display with configurable columns and responsive layout
- Special handling for phone, email, and website fields with automatic linking
- Support for custom formatting options (html, text, raw)
- Added before/after content wrapping for meta fields
- Image size controls for gallery and single image display
- Enhanced CSS styling for meta field display
- Simplified product detail page creation with automatic ID detection
- Efficient rendering: multiple meta shortcodes for same product only trigger one API request
- **NEW:** Added `address` meta key - automatically extracts and formats address from API addresses array (combines addressLine1, cityName, stateName, addressPostalCode)
- **NEW:** Added `map` meta key - extracts geocode coordinates from addresses array and displays interactive Google Maps embed
- **NEW:** Google Maps integration with customizable parameters (map_width, map_height, map_zoom)
- **NEW:** Communication array priority - phone, email, and website meta keys now prioritize data from communication array with specific attribute IDs (CAPHENQUIR, CAEMENQUIR, CAURENQUIR)
- **NEW:** Graceful fallback for maps - displays coordinates with Google Maps link if no API key is configured
- Enhanced data extraction from nested API structures (addresses array, communication array)

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
