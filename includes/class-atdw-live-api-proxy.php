<?php
/**
 * API Proxy Class
 *
 * Handles server-side API requests to ATDW (acts as a proxy to avoid CORS issues)
 *
 * @package ATDW_Live_Products
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATDW_Live_API_Proxy class
 */
class ATDW_Live_API_Proxy {

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
		// AJAX handlers for frontend
		add_action( 'wp_ajax_atdw_live_fetch', array( $this, 'ajax_fetch_products' ) );
		add_action( 'wp_ajax_nopriv_atdw_live_fetch', array( $this, 'ajax_fetch_products' ) );

		// AJAX handler for testing connection
		add_action( 'wp_ajax_atdw_live_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * AJAX handler for fetching products
	 */
	public function ajax_fetch_products() {
		// Verify nonce
		check_ajax_referer( 'atdw_live_nonce', 'nonce' );

		// Get endpoint and parameters
		$endpoint = isset( $_POST['endpoint'] ) ? sanitize_text_field( $_POST['endpoint'] ) : '/products';
		$params   = isset( $_POST['params'] ) ? $_POST['params'] : array();

		// Sanitize parameters
		$params = $this->sanitize_params( $params );

		// Debug log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ATDW AJAX Request - Endpoint: ' . $endpoint . ' | Params: ' . json_encode( $params ) );
		}

		// Make API request
		$result = $this->make_request( $endpoint, $params );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
				'code' => $result->get_error_code(),
				'endpoint' => $endpoint,
				'params' => $params
			) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for testing connection
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'atdw_live_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'atdw-live-products' ) ) );
		}

		$result = $this->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'atdw-live-products' ) ) );
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $params Query parameters.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_request( $endpoint, $params = array() ) {
		// Check if API key is set
		$api_key = $this->settings->get( 'atdw_live_api_key' );

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'no_api_key',
				__( 'API key is not configured. Please configure it in plugin settings.', 'atdw-live-products' )
			);
		}

		// Add required parameters
		$params['key'] = $api_key;

		// Force JSON output format
		$params['out'] = 'json';

		// Add market variant
		$market_variant = $this->settings->get( 'atdw_live_market_variant', 'ENGLISH' );
		if ( ! empty( $market_variant ) && 'ENGLISH' !== $market_variant ) {
			$params['mv'] = $market_variant;
		}

		// Add additionalQuery parameter if Organisation Number is set
		$org_number = $this->settings->get( 'atdw_live_org_number' );
		if ( ! empty( $org_number ) ) {
			// Only add for products endpoint
			if ( strpos( $endpoint, '/products' ) !== false ) {
				$params['additionalQuery'] = 'owningOrganisationNumber:("' . $org_number . '")';
			}
		}

		// Build URL
		$base_url = $this->settings->get( 'atdw_live_api_base_url' );
		$url      = add_query_arg( $params, $base_url . $endpoint );

		// Debug log the full URL
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ATDW API Full URL: ' . $url );
		}

		// Make request
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Request failed', $response->get_error_message(), $url );
			return $response;
		}

		// Get response code
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$error_message = sprintf(
				/* translators: %d: HTTP response code */
				__( 'API returned error code: %d', 'atdw-live-products' ),
				$response_code
			);
			$this->log_error( 'API Error', $error_message, $url );
			return new WP_Error( 'api_error', $error_message, array( 'status' => $response_code ) );
		}

		// Get body
		$body = wp_remote_retrieve_body( $response );

		// Log raw response for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ATDW API Response Body (first 500 chars): ' . substr( $body, 0, 500 ) );
			error_log( 'ATDW API Content-Type: ' . wp_remote_retrieve_header( $response, 'content-type' ) );
		}

		// Check if body is empty
		if ( empty( $body ) ) {
			$error_message = __( 'API returned empty response', 'atdw-live-products' );
			$this->log_error( 'Empty Response', $error_message, $url );
			return new WP_Error( 'empty_response', $error_message );
		}

		// Clean the JSON response - remove BOM and control characters
		$body = trim( $body );
		// Remove UTF-8 BOM if present
		$body = preg_replace( '/^\xEF\xBB\xBF/', '', $body );

		// Fix UTF-8 encoding issues
		if ( ! mb_check_encoding( $body, 'UTF-8' ) ) {
			$body = utf8_encode( $body );
		}

		// Fix common control character issues in JSON strings
		$body = preg_replace( '/[\x00-\x1F\x7F]/', '', $body );

		// Parse JSON
		$data = json_decode( $body, true );

		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			$error_message = __( 'Failed to parse API response', 'atdw-live-products' ) . ': ' . json_last_error_msg();

			// Log more details for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Parse Error: ' . json_last_error_msg() );
				error_log( 'Response body first 500 chars: ' . substr( $body, 0, 500 ) );
				error_log( 'Response body last 500 chars: ' . substr( $body, -500 ) );

				// Check if response might be HTML
				if ( strpos( $body, '<html' ) !== false || strpos( $body, '<!DOCTYPE' ) !== false ) {
					error_log( 'Response appears to be HTML instead of JSON' );
					$error_message = __( 'API returned HTML instead of JSON. This may indicate an API error.', 'atdw-live-products' );
				}
			}

			$this->log_error( 'Parse Error', $error_message . ' | Body preview: ' . substr( $body, 0, 200 ), $url );
			return new WP_Error( 'parse_error', $error_message );
		}

		return $data;
	}

	/**
	 * Test API connection
	 *
	 * @return bool|WP_Error True if connection successful, WP_Error otherwise.
	 */
	public function test_connection() {
		$result = $this->make_request( '/states' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Sanitize parameters
	 *
	 * @param array $params Parameters to sanitize.
	 * @return array Sanitized parameters.
	 */
	private function sanitize_params( $params ) {
		if ( ! is_array( $params ) ) {
			return array();
		}

		$sanitized = array();

		// Allowed ATDW API parameters
		$allowed_params = array(
			'st', 'area', 'loc', 'ct', 'lat', 'long', 'dist', 'cats', 'cat',
			'ptype', 'size', 'pge', 'priceMin', 'priceMax', 'productId',
			'q', 'region', 'category', 'type', 'sort', 'key', 'out', 'mv',
			'additionalQuery', 'fl'
		);

		foreach ( $params as $key => $value ) {
			// Skip if not a string key
			if ( ! is_string( $key ) ) {
				continue;
			}

			// Sanitize key - allow specific characters for ATDW API params
			$clean_key = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key );

			if ( is_array( $value ) ) {
				$sanitized[ $clean_key ] = $this->sanitize_params( $value );
			} else {
				// Convert numeric parameters to integers
				if ( in_array( $clean_key, array( 'size', 'pge', 'dist', 'priceMin', 'priceMax' ), true ) ) {
					$sanitized[ $clean_key ] = intval( $value );
				} else if ( in_array( $clean_key, array( 'lat', 'long' ), true ) ) {
					$sanitized[ $clean_key ] = floatval( $value );
				} else {
					$sanitized[ $clean_key ] = sanitize_text_field( $value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Log API error
	 *
	 * @param string $type Error type.
	 * @param string $message Error message.
	 * @param string $url Request URL.
	 */
	private function log_error( $type, $message, $url = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'ATDW Live API Error [%s]: %s | URL: %s',
					$type,
					$message,
					$url
				)
			);
		}

		// Store in database for admin viewing
		$errors = get_option( 'atdw_live_api_errors', array() );
		$errors[] = array(
			'type'      => $type,
			'message'   => $message,
			'url'       => $url,
			'timestamp' => current_time( 'mysql' ),
		);

		// Keep only last 50 errors
		$errors = array_slice( $errors, -50 );
		update_option( 'atdw_live_api_errors', $errors );
	}

	/**
	 * Get API errors
	 *
	 * @param int $limit Number of errors to retrieve.
	 * @return array Array of errors.
	 */
	public function get_errors( $limit = 10 ) {
		$errors = get_option( 'atdw_live_api_errors', array() );
		return array_slice( array_reverse( $errors ), 0, $limit );
	}

	/**
	 * Clear API errors
	 */
	public function clear_errors() {
		delete_option( 'atdw_live_api_errors' );
	}

}
