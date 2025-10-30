<?php
/**
 * Settings Class
 *
 * Handles plugin settings and options
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATDW_Live_Settings class
 */
class ATDW_Live_Settings {

	/**
	 * Settings fields
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_settings();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Load settings from database
	 */
	private function load_settings() {
		$this->settings = array(
			'atdw_live_api_key'          => get_option( 'atdw_live_api_key', '' ),
			'atdw_live_api_base_url'     => get_option( 'atdw_live_api_base_url', 'https://atlas.atdw-online.com.au/api/atlas' ),
			'atdw_live_org_name'         => get_option( 'atdw_live_org_name', '' ),
			'atdw_live_org_number'       => get_option( 'atdw_live_org_number', '' ),
			'atdw_live_market_variant'   => get_option( 'atdw_live_market_variant', 'ENGLISH' ),
			'atdw_live_output_format'    => get_option( 'atdw_live_output_format', 'json' ),
			'atdw_live_cache_enabled'    => get_option( 'atdw_live_cache_enabled', true ),
			'atdw_live_cache_ttl'        => get_option( 'atdw_live_cache_ttl', 3600 ),
			'atdw_live_custom_css'       => get_option( 'atdw_live_custom_css', '' ),
			'atdw_live_gmaps_api_key'    => get_option( 'atdw_live_gmaps_api_key', '' ),
			'atdw_live_gmaps_base_url'   => get_option( 'atdw_live_gmaps_base_url', 'https://maps.googleapis.com/maps/api/js' ),
			'atdw_live_gallery_width'    => get_option( 'atdw_live_gallery_width', 600 ),
			'atdw_live_gallery_height'   => get_option( 'atdw_live_gallery_height', 400 ),
			'atdw_live_thumbnail_width'  => get_option( 'atdw_live_thumbnail_width', 150 ),
			'atdw_live_thumbnail_height' => get_option( 'atdw_live_thumbnail_height', 150 ),
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// API Settings
		register_setting( 'atdw_live_api_settings', 'atdw_live_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'atdw_live_api_settings', 'atdw_live_api_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://atlas.atdw-online.com.au/api/atlas',
		) );

		// Organisation Settings
		register_setting( 'atdw_live_api_settings', 'atdw_live_org_name', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'atdw_live_api_settings', 'atdw_live_org_number', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'atdw_live_api_settings', 'atdw_live_market_variant', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'ENGLISH',
		) );

		register_setting( 'atdw_live_api_settings', 'atdw_live_output_format', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'json',
		) );

		// Cache Settings
		register_setting( 'atdw_live_cache_settings', 'atdw_live_cache_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		register_setting( 'atdw_live_cache_settings', 'atdw_live_cache_ttl', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3600,
		) );

		// Custom CSS
		register_setting( 'atdw_live_style_settings', 'atdw_live_custom_css', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_css' ),
			'default'           => '',
		) );

		// Google Maps Settings
		register_setting( 'atdw_live_gmaps_settings', 'atdw_live_gmaps_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'atdw_live_gmaps_settings', 'atdw_live_gmaps_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://maps.googleapis.com/maps/api/js',
		) );

		// Gallery Settings
		register_setting( 'atdw_live_gallery_settings', 'atdw_live_gallery_width', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 600,
		) );

		register_setting( 'atdw_live_gallery_settings', 'atdw_live_gallery_height', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 400,
		) );

		register_setting( 'atdw_live_gallery_settings', 'atdw_live_thumbnail_width', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 150,
		) );

		register_setting( 'atdw_live_gallery_settings', 'atdw_live_thumbnail_height', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 150,
		) );
	}

	/**
	 * Get setting value
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if setting not found.
	 * @return mixed Setting value.
	 */
	public function get( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		return $default;
	}

	/**
	 * Update setting value
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool True if updated, false otherwise.
	 */
	public function update( $key, $value ) {
		$result = update_option( $key, $value );

		if ( $result ) {
			$this->settings[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Get all settings
	 *
	 * @return array All settings.
	 */
	public function get_all() {
		return $this->settings;
	}

	/**
	 * Sanitize checkbox value
	 *
	 * @param mixed $value Checkbox value.
	 * @return bool Sanitized value.
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	/**
	 * Sanitize CSS value
	 *
	 * @param string $value CSS value.
	 * @return string Sanitized CSS.
	 */
	public function sanitize_css( $value ) {
		// Strip tags but allow CSS
		$value = wp_strip_all_tags( $value, false );
		// Remove any potential script tags or dangerous content
		$value = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $value );
		return $value;
	}

	/**
	 * Get available market variants
	 *
	 * @return array Market variants.
	 */
	public function get_market_variants() {
		return array(
			'ENGLISH'   => __( 'English', 'atdw-live-products' ),
			'CHINESE-S' => __( 'Chinese Simplified', 'atdw-live-products' ),
			'CHINESE-T' => __( 'Chinese Traditional', 'atdw-live-products' ),
		);
	}

	/**
	 * Get available output formats
	 *
	 * @return array Output formats.
	 */
	public function get_output_formats() {
		return array(
			'json' => __( 'JSON', 'atdw-live-products' ),
			'xml'  => __( 'XML', 'atdw-live-products' ),
		);
	}

	/**
	 * Reset settings to defaults
	 *
	 * @return bool True if reset successfully.
	 */
	public function reset_to_defaults() {
		$defaults = array(
			'atdw_live_api_key'          => '',
			'atdw_live_api_base_url'     => 'https://atlas.atdw-online.com.au/api/atlas',
			'atdw_live_org_name'         => '',
			'atdw_live_org_number'       => '',
			'atdw_live_market_variant'   => 'ENGLISH',
			'atdw_live_cache_enabled'    => true,
			'atdw_live_cache_ttl'        => 3600,
			'atdw_live_output_format'    => 'json',
			'atdw_live_custom_css'       => '',
			'atdw_live_gmaps_api_key'    => '',
			'atdw_live_gmaps_base_url'   => 'https://maps.googleapis.com/maps/api/js',
			'atdw_live_gallery_width'    => 600,
			'atdw_live_gallery_height'   => 400,
			'atdw_live_thumbnail_width'  => 150,
			'atdw_live_thumbnail_height' => 150,
		);

		foreach ( $defaults as $key => $value ) {
			$this->update( $key, $value );
		}

		return true;
	}
}
