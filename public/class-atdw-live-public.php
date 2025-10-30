<?php
/**
 * Public Class
 *
 * Handles all frontend functionality
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATDW_Live_Public class
 */
class ATDW_Live_Public {

	/**
	 * Settings instance
	 *
	 * @var ATDW_Live_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param ATDW_Live_Settings $settings Settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Enqueue public assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		// Register shortcodes
		add_shortcode( 'atdw_live_products', array( $this, 'products_shortcode' ) );
		add_shortcode( 'atdw_live_product', array( $this, 'single_product_shortcode' ) );
		add_shortcode( 'atdw_live_search', array( $this, 'search_shortcode' ) );
		add_shortcode( 'atdw_live_meta', array( $this, 'meta_shortcode' ) );
	}

	/**
	 * Enqueue public assets
	 */
	public function enqueue_public_assets() {
		global $post;

		// Check if shortcode is present
		if ( ! is_a( $post, 'WP_Post' ) || ! $this->has_shortcode( $post->post_content ) ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'atdw-live-public-css',
			ATDW_LIVE_PLUGIN_URL . 'public/assets/css/public.css',
			array(),
			ATDW_LIVE_VERSION
		);

		// Add custom CSS inline
		$custom_css = $this->settings->get( 'atdw_live_custom_css', '' );
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'atdw-live-public-css', $custom_css );
		}

		// Enqueue GLightbox CSS for gallery lightbox
		wp_enqueue_style(
			'glightbox-css',
			ATDW_LIVE_PLUGIN_URL . 'public/assets/lib/glightbox/glightbox.min.css',
			array(),
			'3.3.0'
		);

		// Enqueue GLightbox JS for gallery lightbox
		wp_enqueue_script(
			'glightbox-js',
			ATDW_LIVE_PLUGIN_URL . 'public/assets/lib/glightbox/glightbox.min.js',
			array(),
			'3.3.0',
			true
		);

		// Enqueue JS
		wp_enqueue_script(
			'atdw-live-public-js',
			ATDW_LIVE_PLUGIN_URL . 'public/assets/js/atdw-live.js',
			array( 'jquery', 'glightbox-js' ),
			ATDW_LIVE_VERSION,
			true
		);

		// Localize script with settings
		wp_localize_script(
			'atdw-live-public-js',
			'atdwLive',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'atdw_live_nonce' ),
				'cacheEnabled'    => $this->settings->get( 'atdw_live_cache_enabled' ),
				'apiBaseUrl'      => $this->settings->get( 'atdw_live_api_base_url' ),
				'apiKey'          => $this->settings->get( 'atdw_live_api_key' ),
				'marketVariant'   => $this->settings->get( 'atdw_live_market_variant' ),
				'outputFormat'    => $this->settings->get( 'atdw_live_output_format' ),
				'gmapsApiKey'     => $this->settings->get( 'atdw_live_gmaps_api_key' ),
				'gmapsBaseUrl'    => $this->settings->get( 'atdw_live_gmaps_base_url' ),
				'listingWidth'    => $this->settings->get( 'atdw_live_listing_width', 400 ),
				'listingHeight'   => $this->settings->get( 'atdw_live_listing_height', 300 ),
				'galleryWidth'    => $this->settings->get( 'atdw_live_gallery_width', 600 ),
				'galleryHeight'   => $this->settings->get( 'atdw_live_gallery_height', 400 ),
				'thumbnailWidth'  => $this->settings->get( 'atdw_live_thumbnail_width', 150 ),
				'thumbnailHeight' => $this->settings->get( 'atdw_live_thumbnail_height', 150 ),
				'strings'         => array(
					'loading'    => __( 'Loading listings...', 'atdw-live-products' ),
					'noProducts' => __( 'No listings found.', 'atdw-live-products' ),
					'error'      => __( 'Error loading listings. Please try again.', 'atdw-live-products' ),
					'viewDetails'=> __( 'View Details', 'atdw-live-products' ),
				),
			)
		);
	}

	/**
	 * Products shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function products_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'st'            => '',  // Changed from state to st (ATDW API parameter)
				'cats'          => '',
				'region'        => '',
				'location'      => '',
				'type'          => '',
				'ct'            => '',  // City/town filter (comma-separated values)
				'fl'            => '',  // Field List - subset of data fields to return (comma-separated)
				'size'          => 12,  // Changed from limit to size, default 12
				'pge'           => 1,   // Page number for pagination
				'sort'          => '',  // Sort order (e.g., 'date', 'product_name asc', 'product_name desc', 'rnd', 'drt')
				'layout'        => 'grid',
				'columns'       => 3,
				'show_image'    => 'yes',
				'show_excerpt'  => 'yes',
				'show_pagination' => 'yes',
				'pagination_type' => 'number',  // Pagination type: 'number' (inline numbers) or 'prevnext' (prev/next buttons)
				'view_label'    => 'View Details',  // Button label
				'view_slug'     => 'detail',  // Single product page slug
			),
			$atts,
			'atdw_live_products'
		);

		// Generate unique ID for this instance
		$instance_id = 'atdw-live-' . uniqid();

		// Prepare data attributes for JavaScript (using ATDW API parameter names)
		$data_attrs = array(
			'data-endpoint'     => '/products',
			'data-st'           => esc_attr( $atts['st'] ),  // Changed to st
			'data-cats'         => esc_attr( $atts['cats'] ),
			'data-region'       => esc_attr( $atts['region'] ),
			'data-location'     => esc_attr( $atts['location'] ),
			'data-type'         => esc_attr( $atts['type'] ),
			'data-ct'           => esc_attr( $atts['ct'] ),  // City/town filter
			'data-fl'           => esc_attr( $atts['fl'] ),
			'data-size'         => absint( $atts['size'] ),
			'data-pge'          => absint( $atts['pge'] ),
			'data-order'        => esc_attr( $atts['sort'] ),  // Sort order
			'data-layout'       => esc_attr( $atts['layout'] ),
			'data-columns'      => absint( $atts['columns'] ),
			'data-show-image'   => esc_attr( $atts['show_image'] ),
			'data-show-excerpt' => esc_attr( $atts['show_excerpt'] ),
			'data-show-pagination' => esc_attr( $atts['show_pagination'] ),
			'data-pagination-type' => esc_attr( $atts['pagination_type'] ),
			'data-view-label'   => esc_attr( $atts['view_label'] ),
			'data-view-slug'    => esc_attr( $atts['view_slug'] ),
		);

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="atdw-live-container atdw-layout-<?php echo esc_attr( $atts['layout'] ); ?> atdw-columns-<?php echo esc_attr( $atts['columns'] ); ?>" <?php echo implode( ' ', array_map( function( $k, $v ) { return "$k=\"$v\""; }, array_keys( $data_attrs ), $data_attrs ) ); ?>>
			<div class="atdw-live-loading">
				<span class="atdw-spinner"></span>
				<p><?php esc_html_e( 'Loading listings...', 'atdw-live-products' ); ?></p>
			</div>
			<div class="atdw-live-products"></div>
			<?php if ( 'yes' === $atts['show_pagination'] ) : ?>
				<div class="atdw-pagination" style="display: none;">
					<button class="atdw-prev-page" disabled><?php esc_html_e( '← Previous', 'atdw-live-products' ); ?></button>
					<span class="atdw-page-info">
						<?php esc_html_e( 'Page', 'atdw-live-products' ); ?> <span class="atdw-current-page">1</span> <span class="atdw-page-separator"><?php esc_html_e( 'of', 'atdw-live-products' ); ?></span> <span class="atdw-total-pages">1</span>
					</span>
					<button class="atdw-next-page"><?php esc_html_e( 'Next →', 'atdw-live-products' ); ?></button>
				</div>
			<?php endif; ?>
			<div class="atdw-live-error" style="display: none;">
				<p><?php esc_html_e( 'Error loading listings. Please try again.', 'atdw-live-products' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Single product shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function single_product_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts,
			'atdw_live_product'
		);

		if ( empty( $atts['id'] ) ) {
			return '<p>' . esc_html__( 'Listing ID is required.', 'atdw-live-products' ) . '</p>';
		}

		// Generate unique ID for this instance
		$instance_id = 'atdw-live-single-' . uniqid();

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="atdw-live-single-container" data-endpoint="/product" data-product-id="<?php echo esc_attr( $atts['id'] ); ?>">
			<div class="atdw-live-loading">
				<span class="atdw-spinner"></span>
				<p><?php esc_html_e( 'Loading listing...', 'atdw-live-products' ); ?></p>
			</div>
			<div class="atdw-live-single-product"></div>
			<div class="atdw-live-error" style="display: none;">
				<p><?php esc_html_e( 'Error loading listing. Please try again.', 'atdw-live-products' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Search shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function search_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'ct'              => '',  // City parameter for pre-filtering
				'sort'            => '',  // Sort options (comma-separated)
				'type'            => '',  // Product types (comma-separated)
				'placeholder'     => __( 'Search keyword', 'atdw-live-products' ),  // Search input placeholder
				'show_suburb'     => 'yes',
				'show_sort'       => 'yes',
				'show_types'      => 'yes',
				'columns'         => 3,
			),
			$atts,
			'atdw_live_search'
		);

		// Generate unique ID for this instance
		$instance_id = 'atdw-live-search-' . uniqid();

		// Parse cities from ct parameter - expects comma-separated values
		$cities = array();
		if ( ! empty( $atts['ct'] ) ) {
			// Split comma-separated values
			$city_values = array_map( 'trim', explode( ',', $atts['ct'] ) );
			foreach ( $city_values as $city ) {
				if ( ! empty( $city ) ) {
					// Preserve the original case as entered in the shortcode
					$cities[ $city ] = $city;
				}
			}
		}
		// If no ct parameter provided, dropdown will be empty (no default cities)

		// Parse sort options from sort parameter - expects comma-separated values
		$sort_options = array();
		if ( ! empty( $atts['sort'] ) ) {
			// Split comma-separated values
			$sort_values = array_map( 'trim', explode( ',', $atts['sort'] ) );
			foreach ( $sort_values as $sort_value ) {
				$sort_value = strtolower( $sort_value );
				switch ( $sort_value ) {
					case 'date':
						$sort_options['date'] = __( 'Date', 'atdw-live-products' );
						break;
					case 'asc':
						$sort_options['product_name asc'] = __( 'Alphabetical (A-Z)', 'atdw-live-products' );
						break;
					case 'desc':
						$sort_options['product_name desc'] = __( 'Alphabetical (Z-A)', 'atdw-live-products' );
						break;
					case 'price_asc':
						$sort_options['price_asc'] = __( 'Price (lowest to highest)', 'atdw-live-products' );
						break;
					case 'price_desc':
						$sort_options['price_desc'] = __( 'Price (highest to lowest)', 'atdw-live-products' );
						break;
					case 'drt':
						$sort_options['drt'] = __( 'DRT Members', 'atdw-live-products' );
						break;
					case 'rnd':
					case 'random':
						$sort_options['rnd'] = __( 'Randomize', 'atdw-live-products' );
						break;
				}
			}
		} else {
			// Default sort options if none specified (price options NOT included by default)
			$sort_options = array(
				'date'              => __( 'Date', 'atdw-live-products' ),
				'product_name asc'  => __( 'Alphabetical (A-Z)', 'atdw-live-products' ),
				'product_name desc' => __( 'Alphabetical (Z-A)', 'atdw-live-products' ),
				'drt'               => __( 'DRT Members', 'atdw-live-products' ),
				'rnd'               => __( 'Randomize', 'atdw-live-products' ),
			);
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="atdw-live-search-container" data-show-suburb="<?php echo esc_attr( $atts['show_suburb'] ); ?>" data-show-sort="<?php echo esc_attr( $atts['show_sort'] ); ?>" data-show-types="<?php echo esc_attr( $atts['show_types'] ); ?>" data-columns="<?php echo esc_attr( $atts['columns'] ); ?>" data-types="<?php echo esc_attr( $atts['type'] ); ?>">
			<div class="atdw-live-search-form">
				<div class="atdw-search-row">
					<div class="atdw-search-field">
						<input type="text" class="atdw-search-input" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" />
					</div>

					<?php if ( 'yes' === $atts['show_suburb'] && ! empty( $cities ) ) : ?>
						<div class="atdw-search-field">
							<select class="atdw-filter-suburb">
								<option value=""><?php esc_html_e( 'Select a suburb/city', 'atdw-live-products' ); ?></option>
								<?php foreach ( $cities as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $atts['show_sort'] && ! empty( $sort_options ) ) : ?>
						<div class="atdw-search-field">
							<select class="atdw-filter-sort">
								<?php foreach ( $sort_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<div class="atdw-search-field">
						<button type="button" class="atdw-search-button"><?php esc_html_e( 'Search', 'atdw-live-products' ); ?></button>
					</div>
				</div>

				<?php if ( 'yes' === $atts['show_types'] && ! empty( $atts['type'] ) ) : ?>
					<div class="atdw-types-row" data-cats="<?php echo esc_attr( $atts['type'] ); ?>">
						<button type="button" class="atdw-types-toggle">
							<span class="atdw-toggle-text"><?php esc_html_e( 'Select types', 'atdw-live-products' ); ?></span>
						</button>
						<div class="atdw-types-checkboxes" style="display: none;">
							<div class="atdw-types-loading">
								<span class="atdw-spinner"></span>
								<p><?php esc_html_e( 'Loading types...', 'atdw-live-products' ); ?></p>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Meta shortcode - displays a specific meta field from a product
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function meta_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => '',      // Product ID (optional - will use URL query param if not set)
				'key'        => '',      // Meta key to display (required)
				'format'     => 'html',  // Output format: 'html' (default), 'text', or 'raw'
				'class'      => '',      // Custom CSS class
				'before'     => '',      // Content before the value
				'after'      => '',      // Content after the value
				'size'       => 'medium', // Image size for gallery: 'thumbnail', 'medium', 'large', 'full'
				'columns'    => 3,       // Number of columns for gallery display
				'link'       => 'yes',   // Whether to link gallery images to full size
				'label'      => '',      // Label text for links (phone, email, website)
				'map_width'  => '100%',  // Map width (for map key)
				'map_height' => '400px', // Map height (for map key)
				'map_zoom'   => '15',    // Map zoom level (for map key)
				'attribute_type' => '',  // Attribute Type ID to filter attributes (for key='attributes')
			),
			$atts,
			'atdw_live_meta'
		);

		// If no ID provided in shortcode, try to get it from URL query parameter
		if ( empty( $atts['id'] ) ) {
			$atts['id'] = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
		}

		// Validate required attributes - if no ID, return empty string (don't display anything)
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		if ( empty( $atts['key'] ) ) {
			return '<p class="atdw-error">' . esc_html__( 'Meta key is required.', 'atdw-live-products' ) . '</p>';
		}

		// Generate unique ID for this instance
		$instance_id = 'atdw-live-meta-' . uniqid();

		// Prepare CSS classes
		$css_classes = array( 'atdw-live-meta-container', 'atdw-meta-key-' . sanitize_html_class( $atts['key'] ) );
		if ( ! empty( $atts['class'] ) ) {
			$css_classes[] = sanitize_html_class( $atts['class'] );
		}

		// Prepare data attributes - store all config for JS to use
		$data_attrs = array(
			'data-product-id'  => esc_attr( $atts['id'] ),
			'data-meta-key'    => esc_attr( $atts['key'] ),
			'data-format'      => esc_attr( $atts['format'] ),
			'data-before'      => esc_attr( $atts['before'] ),
			'data-after'       => esc_attr( $atts['after'] ),
			'data-size'        => esc_attr( $atts['size'] ),
			'data-columns'     => absint( $atts['columns'] ),
			'data-link'        => esc_attr( $atts['link'] ),
			'data-label'       => esc_attr( $atts['label'] ),
			'data-map-width'   => esc_attr( $atts['map_width'] ),
			'data-map-height'  => esc_attr( $atts['map_height'] ),
			'data-map-zoom'    => absint( $atts['map_zoom'] ),
			'data-attribute-type' => esc_attr( $atts['attribute_type'] ),
		);

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>" <?php echo implode( ' ', array_map( function( $k, $v ) { return "$k=\"$v\""; }, array_keys( $data_attrs ), $data_attrs ) ); ?>>
			<div class="atdw-live-loading">
				<span class="atdw-spinner"></span>
			</div>
			<div class="atdw-live-meta-content"></div>
			<div class="atdw-live-error" style="display: none;">
				<p><?php esc_html_e( 'Error loading meta field.', 'atdw-live-products' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if content has ATDW shortcode
	 *
	 * @param string $content Content to check.
	 * @return bool True if has shortcode, false otherwise.
	 */
	private function has_shortcode( $content ) {
		$shortcodes = array( 'atdw_live_products', 'atdw_live_product', 'atdw_live_search', 'atdw_live_meta' );

		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}
}
