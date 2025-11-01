<?php
/**
 * Admin Class
 *
 * Handles all admin functionality
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Handle AJAX requests
		add_action( 'wp_ajax_atdw_live_clear_errors', array( $this, 'ajax_clear_errors' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . ATDW_LIVE_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// Show activation notice
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'ATDW Live', 'atdw-live-products' ),
			__( 'ATDW Live', 'atdw-live-products' ),
			'manage_options',
			'atdw-live',
			array( $this, 'render_dashboard_page' ),
			'dashicons-palmtree',
			30
		);

		// Dashboard submenu
		add_submenu_page(
			'atdw-live',
			__( 'Dashboard', 'atdw-live-products' ),
			__( 'Dashboard', 'atdw-live-products' ),
			'manage_options',
			'atdw-live',
			array( $this, 'render_dashboard_page' )
		);

		// Settings submenu
		add_submenu_page(
			'atdw-live',
			__( 'Settings', 'atdw-live-products' ),
			__( 'Settings', 'atdw-live-products' ),
			'manage_options',
			'atdw-live-settings',
			array( $this, 'render_settings_page' )
		);

		// Logs submenu
		add_submenu_page(
			'atdw-live',
			__( 'Logs', 'atdw-live-products' ),
			__( 'Logs', 'atdw-live-products' ),
			'manage_options',
			'atdw-live-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'atdw-live' ) === false ) {
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
	 * Render dashboard page
	 */
	public function render_dashboard_page() {
		include ATDW_LIVE_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		// Handle form submission
		if ( isset( $_POST['atdw_live_settings_submit'] ) && check_admin_referer( 'atdw_live_settings_save', 'atdw_live_settings_nonce' ) ) {
			$this->save_settings();
		}

		include ATDW_LIVE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render logs page
	 */
	public function render_logs_page() {
		include ATDW_LIVE_PLUGIN_DIR . 'admin/views/logs.php';
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

		// Show success message
		add_settings_error(
			'atdw_live_settings',
			'settings_updated',
			__( 'Settings saved successfully!', 'atdw-live-products' ),
			'success'
		);
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
			admin_url( 'admin.php?page=atdw-live-settings' ),
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
					__( 'ATDW Live Products activated! Please <a href="%s">configure your API key</a> to get started.', 'atdw-live-products' ),
					esc_url( admin_url( 'admin.php?page=atdw-live-settings' ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}
