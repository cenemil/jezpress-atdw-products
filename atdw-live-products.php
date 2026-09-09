<?php
/**
 * Plugin Name: Jezpress ATDW Products
 * Plugin URI: https://www.jezweb.com.au
 * Description: Display Australian tourism products from ATDW API with server-side caching and stale-while-revalidate fallback.
 * Version: 4.0.0
 * Author: Jezweb
 * Author URI: https://www.jezweb.com.au
 * License: Proprietary
 * Text Domain: atdw-live-products
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ATDW_LIVE_VERSION', '4.0.0' );
define( 'ATDW_LIVE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATDW_LIVE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ATDW_LIVE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ATDW_LIVE_FILE', __FILE__ );

// JezPress update server constants
define( 'ATDW_LIVE_JEZPRESS_SLUG', 'atdw-live-products' );
define( 'ATDW_LIVE_JEZPRESS_API', 'https://updates.jezpress.com' );

// --- JezPress Auto-Updater ---
require_once ATDW_LIVE_PLUGIN_DIR . 'includes/class-jezweb-jezpress-updater.php';

// --- JezPress License Handler ---
require_once ATDW_LIVE_PLUGIN_DIR . 'includes/class-jezweb-license.php';

// Initialize updater early — NOT gated by is_admin() because WP cron
// auto-updates need these hooks to fire outside admin context.
$updater = new Jezweb_JezPress_Updater( ATDW_LIVE_FILE );
$updater->set_slug( ATDW_LIVE_JEZPRESS_SLUG )
        ->set_api_url( ATDW_LIVE_JEZPRESS_API );

// Pass the license key so JezPress can authenticate downloads.
$_atdw_license = Jezweb_License::get_instance( ATDW_LIVE_FILE, ATDW_LIVE_JEZPRESS_SLUG, 'Jezpress ATDW Products' );
$_atdw_lic_key = $_atdw_license->get_license_key();
if ( ! empty( $_atdw_lic_key ) ) {
	$updater->set_license( $_atdw_lic_key );
}
unset( $_atdw_lic_key );

$updater->initialize();

/**
 * Main Jezpress ATDW Products Class
 */
final class ATDW_Live_Products {

	/**
	 * Single instance of the class
	 *
	 * @var ATDW_Live_Products
	 */
	private static $instance = null;

	/**
	 * Settings instance
	 *
	 * @var ATDW_Live_Settings
	 */
	public $settings = null;

	/**
	 * API Proxy instance
	 *
	 * @var ATDW_Live_API_Proxy
	 */
	public $api_proxy = null;

	/**
	 * Get singleton instance
	 *
	 * @return ATDW_Live_Products
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		// Core classes
		require_once ATDW_LIVE_PLUGIN_DIR . 'includes/class-atdw-live-settings.php';
		require_once ATDW_LIVE_PLUGIN_DIR . 'includes/class-atdw-live-api-proxy.php';

		// Admin classes
		if ( is_admin() ) {
			require_once ATDW_LIVE_PLUGIN_DIR . 'admin/class-atdw-live-admin.php';
		}

		// Public classes
		if ( ! is_admin() ) {
			require_once ATDW_LIVE_PLUGIN_DIR . 'public/class-atdw-live-public.php';
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Initialize plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Load textdomain
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Set default options
		$defaults = array(
			'atdw_live_api_key'        => '',
			'atdw_live_api_base_url'   => 'https://atlas.atdw-online.com.au/api/atlas',
			'atdw_live_market_variant' => 'ENGLISH',
			'atdw_live_output_format'  => 'json',
			'atdw_live_gallery_width'  => 600,
			'atdw_live_gallery_height' => 400,
			'atdw_live_thumbnail_size' => 150,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		// Set activation flag
		set_transient( 'atdw_live_activation_notice', true, 30 );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clear any transients
		delete_transient( 'atdw_live_activation_notice' );

		// Clean up license cron
		$license = Jezweb_License::get_instance();
		if ( $license ) {
			$license->cleanup();
		}
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Initialize core components
		$this->settings  = new ATDW_Live_Settings();
		$this->api_proxy = new ATDW_Live_API_Proxy( $this->settings );

		// Initialize license — registers admin notices + the License tab content via init()
		$license = Jezweb_License::get_instance(
			ATDW_LIVE_FILE,
			ATDW_LIVE_JEZPRESS_SLUG,
			'Jezpress ATDW Products'
		);
		$license->init();

		// Admin always loads (so menu, settings, cache purge are accessible)
		if ( is_admin() ) {
			new ATDW_Live_Admin( $this->settings, $this->api_proxy );
		}

		// Public frontend only loads when license is valid
		if ( ! is_admin() ) {
			if ( ! $license->should_disable_plugin() ) {
				new ATDW_Live_Public( $this->settings );
			}
		}

		// Allow other plugins to hook in
		do_action( 'atdw_live_loaded' );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'atdw-live-products',
			false,
			dirname( ATDW_LIVE_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Get main instance of ATDW_Live_Products
 *
 * @return ATDW_Live_Products
 */
function ATDW_Live() {
	return ATDW_Live_Products::instance();
}

// Initialize the plugin
ATDW_Live();
