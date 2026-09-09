<?php
/**
 * Admin Class
 *
 * Single top-level "ATDW" admin page with Settings, Logs and License tabs —
 * structured the same way as JezPress Events' admin page (see
 * jezpress-events/includes/class-jpem-admin.php). There used to be a separate
 * Dashboard page; its API-status summary and shortcode reference now live at
 * the top of the Settings tab instead.
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATDW_Live_Admin class
 */
class ATDW_Live_Admin {

	/**
	 * Settings instance
	 *
	 * @var ATDW_Live_Settings
	 */
	private $settings;

	/**
	 * API Proxy instance
	 *
	 * @var ATDW_Live_API_Proxy
	 */
	private $api_proxy;

	/**
	 * Constructor
	 *
	 * @param ATDW_Live_Settings   $settings Settings instance.
	 * @param ATDW_Live_API_Proxy $api_proxy API proxy instance.
	 */
	public function __construct( $settings, $api_proxy ) {
		$this->settings  = $settings;
		$this->api_proxy = $api_proxy;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Add admin menu
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Handle AJAX requests
		add_action( 'wp_ajax_atdw_live_clear_errors', array( $this, 'ajax_clear_errors' ) );
		add_action( 'wp_ajax_atdw_live_purge_cache', array( $this, 'ajax_purge_cache' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . ATDW_LIVE_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// Show activation notice
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
	}

	/**
	 * Register the single top-level admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Jezpress ATDW Products', 'atdw-live-products' ),
			__( 'ATDW', 'atdw-live-products' ),
			'manage_options',
			'atdw-live',
			array( $this, 'render_page' ),
			'dashicons-location-alt',
			30
		);

		// Relabel the auto-added first submenu item from "ATDW" to "Settings",
		// matching the Settings tab that lands here by default.
		add_submenu_page(
			'atdw-live',
			__( 'ATDW Settings', 'atdw-live-products' ),
			__( 'Settings', 'atdw-live-products' ),
			'manage_options',
			'atdw-live',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin page
		if ( 'toplevel_page_atdw-live' !== $hook ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'atdw-live-admin-css',
			ATDW_LIVE_PLUGIN_URL . 'admin/assets/css/admin.css',
			array(),
			ATDW_LIVE_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'atdw-live-admin-js',
			ATDW_LIVE_PLUGIN_URL . 'admin/assets/js/admin.js',
			array( 'jquery' ),
			ATDW_LIVE_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'atdw-live-admin-js',
			'atdwLiveAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'atdw_live_admin_nonce' ),
				'strings' => array(
					'testingConnection' => __( 'Testing connection...', 'atdw-live-products' ),
					'connectionSuccess' => __( 'Connection successful!', 'atdw-live-products' ),
					'connectionFailed'  => __( 'Connection failed!', 'atdw-live-products' ),
				),
			)
		);
	}

	/**
	 * Render the merged admin page: header, tab nav, and the active tab's content.
	 */
	public function render_page() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$license     = Jezweb_License::get_instance();
		$is_licensed = $license && $license->is_valid();

		// Only a licensed install may change settings — mirrors the old per-page
		// license gate, which never rendered the settings form when inactive.
		if ( $is_licensed && isset( $_POST['atdw_live_settings_submit'] ) && check_admin_referer( 'atdw_live_settings_save', 'atdw_live_settings_nonce' ) ) {
			$this->save_settings();
		}

		if ( ! $is_licensed && 'license' !== $tab ) {
			$tab = 'license';
		}
		?>
		<div class="wrap atdw-live-wrap">
			<h1><?php esc_html_e( 'Jezpress ATDW Products', 'atdw-live-products' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php if ( $is_licensed ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live&tab=settings' ) ); ?>"
					   class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Settings', 'atdw-live-products' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live&tab=logs' ) ); ?>"
					   class="nav-tab <?php echo 'logs' === $tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Logs', 'atdw-live-products' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live&tab=shortcodes' ) ); ?>"
					   class="nav-tab <?php echo 'shortcodes' === $tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'Shortcodes', 'atdw-live-products' ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=atdw-live&tab=license' ) ); ?>"
				   class="nav-tab <?php echo 'license' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'License', 'atdw-live-products' ); ?>
				</a>
			</nav>

			<?php
			if ( 'license' === $tab ) {
				if ( $license ) {
					$license->render_tab_content();
				}
			} elseif ( 'logs' === $tab ) {
				$this->render_logs_tab();
			} elseif ( 'shortcodes' === $tab ) {
				$this->render_shortcodes_tab();
			} else {
				// Settings is the default landing tab.
				$this->render_settings_tab();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Settings tab: the settings form (API status folded into the API Settings
	 * card) plus reference material that used to live on the separate Dashboard page.
	 */
	private function render_settings_tab() {
		settings_errors( 'atdw_live_settings' );

		$api_key_set = ! empty( $this->settings->get( 'atdw_live_api_key' ) );
		$org_number  = $this->settings->get( 'atdw_live_org_number' );
		$market_variants = $this->settings->get_market_variants();
		$output_formats  = $this->settings->get_output_formats();
		?>
		<div class="admin-page-wrap">

		<form method="post" action="">
			<?php wp_nonce_field( 'atdw_live_settings_save', 'atdw_live_settings_nonce' ); ?>

			<div class="admin-page-card">
				<h2 class="admin-page-card-title"><?php esc_html_e( 'API Settings', 'atdw-live-products' ); ?></h2>

				<?php if ( $api_key_set ) : ?>
					<p class="atdw-status atdw-status-success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'API Key Configured', 'atdw-live-products' ); ?>
					</p>
					<?php if ( ! empty( $org_number ) ) : ?>
						<p style="margin-top:10px;color:#2271b1;">
							<span class="dashicons dashicons-filter"></span>
							<?php
							/* translators: %s: Organisation number */
							printf( esc_html__( 'Filtering products by organisation: %s', 'atdw-live-products' ), '<strong>' . esc_html( $org_number ) . '</strong>' );
							?>
						</p>
					<?php endif; ?>
					<p style="margin-top:15px;">
						<button type="button" class="button button-secondary" id="atdw-live-test-connection">
							<?php esc_html_e( 'Test Connection', 'atdw-live-products' ); ?>
						</button>
						<button type="button" class="button" id="atdw-live-purge-cache" style="color:#d63638;">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Purge ATDW Cache', 'atdw-live-products' ); ?>
						</button>
					</p>
				<?php else : ?>
					<p class="atdw-status atdw-status-error">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'API Key Not Configured', 'atdw-live-products' ); ?>
					</p>
					<p class="description" style="margin:10px 0;">
						<?php esc_html_e( 'Configure your ATDW API key below to start displaying tourism products.', 'atdw-live-products' ); ?>
					</p>
				<?php endif; ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="atdw_live_api_key"><?php esc_html_e( 'API Key', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_api_key" name="atdw_live_api_key" value="<?php echo esc_attr( $this->settings->get( 'atdw_live_api_key' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Enter your ATDW API key.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_api_base_url"><?php esc_html_e( 'API Base URL', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="url" id="atdw_live_api_base_url" name="atdw_live_api_base_url" value="<?php echo esc_url( $this->settings->get( 'atdw_live_api_base_url' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Default: https://atlas.atdw-online.com.au/api/atlas', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_org_name"><?php esc_html_e( 'Organisation Name', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_org_name" name="atdw_live_org_name" value="<?php echo esc_attr( $this->settings->get( 'atdw_live_org_name' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Enter your organisation name as registered with ATDW.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_org_number"><?php esc_html_e( 'Organisation Number', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_org_number" name="atdw_live_org_number" value="<?php echo esc_attr( $this->settings->get( 'atdw_live_org_number' ) ); ?>" class="regular-text" placeholder="e.g., NSW2420" />
							<p class="description">
								<?php esc_html_e( 'Enter your ATDW organisation number (e.g., NSW2420).', 'atdw-live-products' ); ?><br>
								<strong><?php esc_html_e( 'Note:', 'atdw-live-products' ); ?></strong> <?php esc_html_e( 'When set, all product queries will be filtered to show only products from your organisation.', 'atdw-live-products' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_market_variant"><?php esc_html_e( 'Market Variant', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<select id="atdw_live_market_variant" name="atdw_live_market_variant">
								<?php foreach ( $market_variants as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $this->settings->get( 'atdw_live_market_variant' ), $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the language/market variant for product data.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_output_format"><?php esc_html_e( 'Output Format', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<select id="atdw_live_output_format" name="atdw_live_output_format">
								<?php foreach ( $output_formats as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $this->settings->get( 'atdw_live_output_format' ), $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the API response format.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="admin-page-card">
				<h2 class="admin-page-card-title"><?php esc_html_e( 'Google Maps Settings', 'atdw-live-products' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="atdw_live_gmaps_api_key"><?php esc_html_e( 'Google Maps API Key', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_gmaps_api_key" name="atdw_live_gmaps_api_key" value="<?php echo esc_attr( $this->settings->get( 'atdw_live_gmaps_api_key' ) ); ?>" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Enter your Google Maps API key. Required for displaying maps.', 'atdw-live-products' ); ?><br>
								<?php
								printf(
									/* translators: %s: Google Cloud Console URL */
									esc_html__( 'Get your API key from the %s.', 'atdw-live-products' ),
									'<a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google Cloud Console', 'atdw-live-products' ) . '</a>'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_gmaps_base_url"><?php esc_html_e( 'Google Maps API Base URL', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="url" id="atdw_live_gmaps_base_url" name="atdw_live_gmaps_base_url" value="<?php echo esc_url( $this->settings->get( 'atdw_live_gmaps_base_url' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Default: https://maps.googleapis.com/maps/api/js', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="admin-page-card">
				<h2 class="admin-page-card-title"><?php esc_html_e( 'Image Settings', 'atdw-live-products' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="atdw_live_listing_width"><?php esc_html_e( 'Listing Image Width (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $listing_width = $this->settings->get( 'atdw_live_listing_width' ); ?>
							<input type="number" id="atdw_live_listing_width" name="atdw_live_listing_width" <?php echo ( '' !== $listing_width && null !== $listing_width ) ? 'value="' . esc_attr( $listing_width ) . '"' : ''; ?> class="small-text" step="1" placeholder="400" />
							<p class="description"><?php esc_html_e( 'Width in pixels for listing images. Default: 400', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_listing_height"><?php esc_html_e( 'Listing Image Height (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $listing_height = $this->settings->get( 'atdw_live_listing_height' ); ?>
							<input type="number" id="atdw_live_listing_height" name="atdw_live_listing_height" <?php echo ( '' !== $listing_height && null !== $listing_height ) ? 'value="' . esc_attr( $listing_height ) . '"' : ''; ?> class="small-text" step="1" placeholder="300" />
							<p class="description"><?php esc_html_e( 'Height in pixels for listing images. Default: 300', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_gallery_width"><?php esc_html_e( 'Gallery Image Width (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $gallery_width = $this->settings->get( 'atdw_live_gallery_width' ); ?>
							<input type="number" id="atdw_live_gallery_width" name="atdw_live_gallery_width" <?php echo ( '' !== $gallery_width && null !== $gallery_width ) ? 'value="' . esc_attr( $gallery_width ) . '"' : ''; ?> class="small-text" step="1" placeholder="600" />
							<p class="description"><?php esc_html_e( 'Width in pixels for lightbox gallery images. Default: 600', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_gallery_height"><?php esc_html_e( 'Gallery Image Height (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $gallery_height = $this->settings->get( 'atdw_live_gallery_height' ); ?>
							<input type="number" id="atdw_live_gallery_height" name="atdw_live_gallery_height" <?php echo ( '' !== $gallery_height && null !== $gallery_height ) ? 'value="' . esc_attr( $gallery_height ) . '"' : ''; ?> class="small-text" step="1" placeholder="400" />
							<p class="description"><?php esc_html_e( 'Height in pixels for lightbox gallery images. Default: 400', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_thumbnail_width"><?php esc_html_e( 'Thumbnail Width (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $thumbnail_width = $this->settings->get( 'atdw_live_thumbnail_width' ); ?>
							<input type="number" id="atdw_live_thumbnail_width" name="atdw_live_thumbnail_width" <?php echo ( '' !== $thumbnail_width && null !== $thumbnail_width ) ? 'value="' . esc_attr( $thumbnail_width ) . '"' : ''; ?> class="small-text" step="1" />
							<p class="description"><?php esc_html_e( 'Width in pixels for gallery thumbnails. Leave empty for auto sizing.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_thumbnail_height"><?php esc_html_e( 'Thumbnail Height (px)', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<?php $thumbnail_height = $this->settings->get( 'atdw_live_thumbnail_height' ); ?>
							<input type="number" id="atdw_live_thumbnail_height" name="atdw_live_thumbnail_height" <?php echo ( '' !== $thumbnail_height && null !== $thumbnail_height ) ? 'value="' . esc_attr( $thumbnail_height ) . '"' : ''; ?> class="small-text" step="1" />
							<p class="description"><?php esc_html_e( 'Height in pixels for gallery thumbnails. Leave empty for auto sizing.', 'atdw-live-products' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="admin-page-card">
				<h2 class="admin-page-card-title"><?php esc_html_e( 'Badge Settings', 'atdw-live-products' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="atdw_live_badge_image_url"><?php esc_html_e( 'Badge Image URL', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_badge_image_url" name="atdw_live_badge_image_url" value="<?php echo esc_url( $this->settings->get( 'atdw_live_badge_image_url' ) ); ?>" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Enter the full URL or relative path of the badge image to display on products with specific attributes.', 'atdw-live-products' ); ?><br>
								<strong><?php esc_html_e( 'Note:', 'atdw-live-products' ); ?></strong> <?php esc_html_e( 'Use the badge parameter in the shortcode to specify which attributeTypeId should trigger the badge display.', 'atdw-live-products' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="atdw_live_badge_attribute_type"><?php esc_html_e( 'Badge Attribute Type ID', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<input type="text" id="atdw_live_badge_attribute_type" name="atdw_live_badge_attribute_type" value="<?php echo esc_attr( $this->settings->get( 'atdw_live_badge_attribute_type' ) ); ?>" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Enter the Attribute Type ID that should trigger the badge display (e.g., "ENTITY FAC", "TOURISMORG").', 'atdw-live-products' ); ?><br>
								<strong><?php esc_html_e( 'Note:', 'atdw-live-products' ); ?></strong> <?php esc_html_e( 'This will be used as the default value if no badge parameter is specified in the shortcode.', 'atdw-live-products' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="admin-page-card">
				<h2 class="admin-page-card-title"><?php esc_html_e( 'Custom CSS', 'atdw-live-products' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="atdw_live_custom_css"><?php esc_html_e( 'Custom CSS', 'atdw-live-products' ); ?></label>
						</th>
						<td>
							<textarea id="atdw_live_custom_css" name="atdw_live_custom_css" rows="15" class="large-text code" style="font-family:monospace;"><?php echo esc_textarea( $this->settings->get( 'atdw_live_custom_css' ) ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Add custom CSS to override the default plugin styles. This CSS will be loaded after the default stylesheet and will take priority.', 'atdw-live-products' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<p class="admin-page-card-submit"><?php submit_button( __( 'Save Settings', 'atdw-live-products' ), 'primary', 'atdw_live_settings_submit' ); ?></p>
		</form>
		</div>
		<?php
	}

	/**
	 * Shortcodes tab: reference material that used to live on the separate
	 * Dashboard page, then at the bottom of the Settings tab.
	 */
	private function render_shortcodes_tab() {
		?>
		<div class="admin-page-wrap">
		<div class="admin-page-card admin-page-card-full">
			<h2 class="admin-page-card-title"><?php esc_html_e( 'Shortcode Reference', 'atdw-live-products' ); ?></h2>

			<h3><?php esc_html_e( 'Display Products Grid', 'atdw-live-products' ); ?></h3>
			<p><?php esc_html_e( 'Basic usage:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'Filtered by state:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="NSW" size="6"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With region and columns:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="QLD" region="Gold Coast" columns="4"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'List layout without pagination:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products type="ACCOMM" layout="list" show_pagination="no"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With custom view link:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products st="VIC" cats="Wine" size="12" view_slug="listing"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With specific fields only (reduced response):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_products fl="productName,productImage,productDescription" st="NSW" size="10"]</code>

			<h3 style="margin-top:20px;"><?php esc_html_e( 'Display Single Product', 'atdw-live-products' ); ?></h3>
			<code>[atdw_live_product id="56b23f7e57010b977310e2ba"]</code>
			<p class="description"><?php esc_html_e( 'Replace with actual ATDW product ID', 'atdw-live-products' ); ?></p>

			<h3 style="margin-top:20px;"><?php esc_html_e( 'Search Form', 'atdw-live-products' ); ?></h3>
			<p><?php esc_html_e( 'Basic search (default filters):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With specific cities:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search ct="Dungog,Clarence Town,Stroud"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With custom placeholder:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search placeholder="Search tourism attractions" ct="Dungog"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'Custom sort options:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search sort="date,asc,desc,random"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'With product type filtering:', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search type="ACCOMM" show_types="yes"]</code>

			<p style="margin-top:10px;"><?php esc_html_e( 'Minimal (keyword only):', 'atdw-live-products' ); ?></p>
			<code>[atdw_live_search show_suburb="no" show_sort="no" show_types="no"]</code>

			<h3 style="margin-top:20px;"><?php esc_html_e( 'Available Parameters', 'atdw-live-products' ); ?></h3>
			<h4><?php esc_html_e( 'Product Grid ([atdw_live_products]):', 'atdw-live-products' ); ?></h4>
			<ul class="admin-page-card-section-ul">
				<li><strong>st</strong> - State code (NSW, VIC, QLD, SA, WA, TAS, NT, ACT)</li>
				<li><strong>cats</strong> - Category filter (comma-separated)</li>
				<li><strong>region</strong> - Region/area name</li>
				<li><strong>location</strong> - Location filter</li>
				<li><strong>type</strong> - Product type filter</li>
				<li><strong>fl</strong> - Field List - specify which fields to return from API response (comma-separated, e.g., "productName,productImage,productDescription"). Reduces response size for better performance</li>
				<li><strong>size</strong> - Number of products per page (default: 12)</li>
				<li><strong>pge</strong> - Page number for pagination (default: 1)</li>
				<li><strong>layout</strong> - grid or list (default: grid)</li>
				<li><strong>columns</strong> - 2, 3, or 4 (default: 3)</li>
				<li><strong>show_image</strong> - yes or no (default: yes)</li>
				<li><strong>show_excerpt</strong> - yes or no (default: yes)</li>
				<li><strong>show_pagination</strong> - yes or no (default: yes)</li>
				<li><strong>view_label</strong> - Button text (default: "View Details")</li>
				<li><strong>view_slug</strong> - Single product page slug (default: "detail")</li>
			</ul>
			<h4 style="margin-top:15px;"><?php esc_html_e( 'Search Form ([atdw_live_search]):', 'atdw-live-products' ); ?></h4>
			<ul class="admin-page-card-section-ul">
				<li><strong>ct</strong> - Comma-separated city/suburb options (e.g., "Dungog,Stroud"). If empty, no dropdown shown</li>
				<li><strong>sort</strong> - Comma-separated sort options: date, asc, desc, price_asc, price_desc, drt, random. If empty, shows default options</li>
				<li><strong>type</strong> - Product type/category to filter (used for loading type checkboxes)</li>
				<li><strong>placeholder</strong> - Search input placeholder (default: "Search keyword")</li>
				<li><strong>show_suburb</strong> - Show suburb/city dropdown: yes or no (default: yes)</li>
				<li><strong>show_sort</strong> - Show sort dropdown: yes or no (default: yes)</li>
				<li><strong>show_types</strong> - Show type checkboxes: yes or no (default: yes)</li>
				<li><strong>columns</strong> - Results grid columns: 2, 3, or 4 (default: 3)</li>
			</ul>
		</div>
		</div>
		<?php
	}

	/**
	 * Logs tab: recent API errors.
	 */
	private function render_logs_tab() {
		$errors = $this->api_proxy->get_errors( 50 );
		?>
		<div class="admin-page-wrap">
		<div class="admin-page-card admin-page-card-full">
			<h2 class="admin-page-card-title"><?php esc_html_e( 'Recent API Errors', 'atdw-live-products' ); ?></h2>
			<?php if ( ! empty( $errors ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Type', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'Message', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'URL', 'atdw-live-products' ); ?></th>
							<th><?php esc_html_e( 'Timestamp', 'atdw-live-products' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $errors as $error ) : ?>
							<tr>
								<td><?php echo esc_html( $error['type'] ); ?></td>
								<td><?php echo esc_html( $error['message'] ); ?></td>
								<td><code><?php echo esc_html( wp_trim_words( $error['url'], 10, '...' ) ); ?></code></td>
								<td><?php echo esc_html( $error['timestamp'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p style="margin-top:15px;">
					<button type="button" class="button" id="atdw-live-clear-errors">
						<?php esc_html_e( 'Clear Errors', 'atdw-live-products' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'No errors logged.', 'atdw-live-products' ); ?></p>
			<?php endif; ?>
		</div>
		</div>
		<?php
	}

	/**
	 * Save settings
	 */
	private function save_settings() {
		// API Settings
		if ( isset( $_POST['atdw_live_api_key'] ) ) {
			$this->settings->update( 'atdw_live_api_key', sanitize_text_field( $_POST['atdw_live_api_key'] ) );
		}

		if ( isset( $_POST['atdw_live_api_base_url'] ) ) {
			$this->settings->update( 'atdw_live_api_base_url', esc_url_raw( $_POST['atdw_live_api_base_url'] ) );
		}

		// Organisation Settings
		if ( isset( $_POST['atdw_live_org_name'] ) ) {
			$this->settings->update( 'atdw_live_org_name', sanitize_text_field( $_POST['atdw_live_org_name'] ) );
		}

		if ( isset( $_POST['atdw_live_org_number'] ) ) {
			$this->settings->update( 'atdw_live_org_number', sanitize_text_field( $_POST['atdw_live_org_number'] ) );
		}

		if ( isset( $_POST['atdw_live_market_variant'] ) ) {
			$this->settings->update( 'atdw_live_market_variant', sanitize_text_field( $_POST['atdw_live_market_variant'] ) );
		}

		if ( isset( $_POST['atdw_live_output_format'] ) ) {
			$this->settings->update( 'atdw_live_output_format', sanitize_text_field( $_POST['atdw_live_output_format'] ) );
		}

		// Custom CSS
		if ( isset( $_POST['atdw_live_custom_css'] ) ) {
			$custom_css = $this->settings->sanitize_css( $_POST['atdw_live_custom_css'] );
			$this->settings->update( 'atdw_live_custom_css', $custom_css );
		}

		// Google Maps Settings
		if ( isset( $_POST['atdw_live_gmaps_api_key'] ) ) {
			$this->settings->update( 'atdw_live_gmaps_api_key', sanitize_text_field( $_POST['atdw_live_gmaps_api_key'] ) );
		}

		if ( isset( $_POST['atdw_live_gmaps_base_url'] ) ) {
			$this->settings->update( 'atdw_live_gmaps_base_url', esc_url_raw( $_POST['atdw_live_gmaps_base_url'] ) );
		}

		// Image Settings - Listing Images
		if ( isset( $_POST['atdw_live_listing_width'] ) ) {
			$value = $_POST['atdw_live_listing_width'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_listing_width', '' );
			} else {
				$this->settings->update( 'atdw_live_listing_width', absint( $value ) );
			}
		}

		if ( isset( $_POST['atdw_live_listing_height'] ) ) {
			$value = $_POST['atdw_live_listing_height'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_listing_height', '' );
			} else {
				$this->settings->update( 'atdw_live_listing_height', absint( $value ) );
			}
		}

		// Gallery Settings
		if ( isset( $_POST['atdw_live_gallery_width'] ) ) {
			$value = $_POST['atdw_live_gallery_width'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_gallery_width', '' );
			} else {
				$this->settings->update( 'atdw_live_gallery_width', absint( $value ) );
			}
		}

		if ( isset( $_POST['atdw_live_gallery_height'] ) ) {
			$value = $_POST['atdw_live_gallery_height'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_gallery_height', '' );
			} else {
				$this->settings->update( 'atdw_live_gallery_height', absint( $value ) );
			}
		}

		if ( isset( $_POST['atdw_live_thumbnail_width'] ) ) {
			$value = $_POST['atdw_live_thumbnail_width'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_thumbnail_width', '' );
			} else {
				$this->settings->update( 'atdw_live_thumbnail_width', absint( $value ) );
			}
		}

		if ( isset( $_POST['atdw_live_thumbnail_height'] ) ) {
			$value = $_POST['atdw_live_thumbnail_height'];
			// Preserve empty values, otherwise convert to integer
			if ( $value === '' || $value === null ) {
				$this->settings->update( 'atdw_live_thumbnail_height', '' );
			} else {
				$this->settings->update( 'atdw_live_thumbnail_height', absint( $value ) );
			}
		}

		// Badge Settings
		if ( isset( $_POST['atdw_live_badge_image_url'] ) ) {
			$this->settings->update( 'atdw_live_badge_image_url', sanitize_text_field( $_POST['atdw_live_badge_image_url'] ) );
		}

		if ( isset( $_POST['atdw_live_badge_attribute_type'] ) ) {
			$this->settings->update( 'atdw_live_badge_attribute_type', sanitize_text_field( $_POST['atdw_live_badge_attribute_type'] ) );
		}

		// Show success message
		add_settings_error(
			'atdw_live_settings',
			'settings_updated',
			__( 'Settings saved successfully!', 'atdw-live-products' ),
			'success'
		);
	}

	/**
	 * AJAX: Purge all ATDW transient caches.
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( 'atdw_live_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'atdw-live-products' ) ) );
		}

		global $wpdb;

		// Delete all primary cache transients.
		$deleted_cache = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_atdw_cache_%'
			    OR option_name LIKE '_transient_timeout_atdw_cache_%'"
		);

		// Delete all fallback transients.
		$deleted_fallback = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_atdw_fallback_%'
			    OR option_name LIKE '_transient_timeout_atdw_fallback_%'"
		);

		$total = intval( $deleted_cache ) + intval( $deleted_fallback );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of transient rows deleted */
				__( 'Cache purged! %d transient rows cleared.', 'atdw-live-products' ),
				$total
			),
		) );
	}

	/**
	 * AJAX: Clear errors
	 */
	public function ajax_clear_errors() {
		check_ajax_referer( 'atdw_live_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'atdw-live-products' ) ) );
		}

		$this->api_proxy->clear_errors();

		wp_send_json_success( array( 'message' => __( 'Errors cleared successfully!', 'atdw-live-products' ) ) );
	}

	/**
	 * Add action links to plugins page
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=atdw-live' ),
			__( 'Settings', 'atdw-live-products' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Show activation notice
	 */
	public function activation_notice() {
		if ( ! get_transient( 'atdw_live_activation_notice' ) ) {
			return;
		}

		delete_transient( 'atdw_live_activation_notice' );

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: Settings page URL */
					__( 'Jezpress ATDW Products activated! Please <a href="%s">configure your API key</a> to get started.', 'atdw-live-products' ),
					esc_url( admin_url( 'admin.php?page=atdw-live' ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}
