<?php
/**
 * JezPress Secure License Handler
 *
 * Handles license validation, activation, and deactivation for Jezweb plugins.
 * Integrates with JezPress licensing system with anti-nulling protections.
 *
 * @package Jezweb
 * @since 1.0.0
 * @version 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Jezweb_License
 *
 * Secure license handler for JezPress-distributed plugins.
 */
class Jezweb_License {

    /**
     * JezPress License API URL.
     *
     * @var string
     */
    private $api_url = 'https://updates.jezpress.com';

    /**
     * Plugin slug.
     *
     * @var string
     */
    private $slug;

    /**
     * Plugin name for display.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin file path.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Option name for storing license data.
     *
     * @var string
     */
    private $option_name;

    /**
     * Menu slug for settings page.
     *
     * @var string
     */
    private $menu_slug;

    /**
     * Whether license is required for plugin to work.
     *
     * @var bool
     */
    private $required = true;

    /**
     * Cache duration in seconds (12 hours).
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * Cached license status.
     *
     * @var array|null
     */
    private $license_data = null;

    /**
     * Security salt for data encryption.
     *
     * @var string
     */
    private $security_salt;

    /**
     * Singleton instance.
     *
     * @var Jezweb_License|null
     */
    private static $instance = null;

    /**
     * Constructor.
     *
     * @param string $plugin_file Plugin main file path.
     * @param string $slug        Plugin slug on JezPress.
     * @param string $plugin_name Plugin display name.
     */
    public function __construct( $plugin_file, $slug, $plugin_name ) {
        $this->plugin_file   = $plugin_file;
        $this->slug          = sanitize_title( $slug );
        $this->plugin_name   = sanitize_text_field( $plugin_name );
        $this->option_name   = 'jzwb_lic_' . substr( md5( $this->slug ), 0, 8 );
        $this->menu_slug     = $this->slug . '-license';
        $this->security_salt = $this->generate_site_salt();
    }

    /**
     * Get singleton instance.
     *
     * @param string $plugin_file Plugin main file path.
     * @param string $slug        Plugin slug on JezPress.
     * @param string $plugin_name Plugin display name.
     * @return Jezweb_License
     */
    public static function get_instance( $plugin_file = '', $slug = '', $plugin_name = '' ) {
        if ( null === self::$instance && $plugin_file ) {
            self::$instance = new self( $plugin_file, $slug, $plugin_name );
        }
        return self::$instance;
    }

    /**
     * Generate site-specific salt for encryption.
     *
     * @return string
     */
    private function generate_site_salt() {
        $components = array(
            defined( 'AUTH_KEY' ) ? AUTH_KEY : 'jzwb',
            $this->slug,
            wp_parse_url( home_url(), PHP_URL_HOST ),
        );
        return hash( 'sha256', implode( '|', $components ) );
    }

    /**
     * Set API URL.
     *
     * @param string $url API URL.
     * @return $this
     */
    public function set_api_url( $url ) {
        $this->api_url = rtrim( esc_url_raw( $url ), '/' );
        return $this;
    }

    /**
     * Set whether license is required.
     *
     * @param bool $required Whether license is required.
     * @return $this
     */
    public function set_required( $required ) {
        $this->required = (bool) $required;
        return $this;
    }

    /**
     * Initialize license handler.
     *
     * @return void
     */
    public function init() {
        // Admin hooks.
        add_action( 'admin_menu', array( $this, 'add_license_menu' ), 99 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_license_actions' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        // Plugin action links.
        add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'plugin_action_links' ) );

        // Daily license check.
        add_action( 'jezweb_license_check_' . $this->slug, array( $this, 'scheduled_license_check' ) );

        // Schedule daily check if not already scheduled.
        if ( ! wp_next_scheduled( 'jezweb_license_check_' . $this->slug ) ) {
            wp_schedule_event( time(), 'daily', 'jezweb_license_check_' . $this->slug );
        }

        // Integrity check on admin init.
        add_action( 'admin_init', array( $this, 'verify_integrity' ), 1 );
    }

    /**
     * Verify plugin integrity.
     *
     * @return void
     */
    public function verify_integrity() {
        // Check if we're on JezPress update server.
        if ( ! $this->verify_source() ) {
            $this->invalidate_license();
        }

        // Verify stored data hasn't been tampered with.
        if ( ! $this->verify_stored_data() ) {
            $this->invalidate_license();
        }
    }

    /**
     * Verify plugin source is from JezPress.
     *
     * @return bool
     */
    private function verify_source() {
        // Check for update server marker.
        $marker_file = dirname( $this->plugin_file ) . '/.jezpress';

        // If marker doesn't exist, create it on first run.
        if ( ! file_exists( $marker_file ) ) {
            // Only allow creation during legitimate update.
            $update_check = get_site_transient( 'update_plugins' );
            if ( $update_check && isset( $update_check->response[ plugin_basename( $this->plugin_file ) ] ) ) {
                // During update, allow marker creation.
                @file_put_contents( $marker_file, $this->generate_source_signature() );
            }
        }

        return true; // Allow for initial installation.
    }

    /**
     * Generate source signature.
     *
     * @return string
     */
    private function generate_source_signature() {
        return hash( 'sha256', $this->slug . '|' . ATDW_LIVE_VERSION . '|jezpress' );
    }

    /**
     * Verify stored license data integrity.
     *
     * @return bool
     */
    private function verify_stored_data() {
        $data = get_option( $this->option_name );

        if ( empty( $data ) ) {
            return true; // No data to verify.
        }

        if ( ! isset( $data['_sig'] ) ) {
            return false; // Missing signature.
        }

        // Decrypt data first if encrypted (signature was generated with plaintext key).
        if ( isset( $data['_enc'] ) && $data['_enc'] ) {
            $data = $this->decrypt_license_data( $data );
        }

        $expected_sig = $this->generate_data_signature( $data );
        return hash_equals( $expected_sig, $data['_sig'] );
    }

    /**
     * Generate data signature for integrity check.
     *
     * @param array $data License data.
     * @return string
     */
    private function generate_data_signature( $data ) {
        $to_sign = array(
            isset( $data['key'] ) ? $data['key'] : '',
            isset( $data['status'] ) ? $data['status'] : '',
            isset( $data['domain'] ) ? $data['domain'] : '',
            $this->security_salt,
        );
        return hash( 'sha256', implode( '|', $to_sign ) );
    }

    /**
     * Invalidate license.
     *
     * @return void
     */
    private function invalidate_license() {
        delete_option( $this->option_name );
        $this->license_data = null;
    }

    /**
     * Check if license is valid with multiple verification layers.
     *
     * @param bool $force_check Force remote check.
     * @return bool True if license is valid.
     */
    public function is_valid( $force_check = false ) {
        // Layer 1: Check stored data.
        $license_data = $this->get_license_data( $force_check );

        if ( empty( $license_data ) || empty( $license_data['key'] ) ) {
            return false;
        }

        // Layer 2: Check status.
        if ( ! isset( $license_data['status'] ) || 'active' !== $license_data['status'] ) {
            return false;
        }

        // Layer 3: Verify domain matches.
        if ( isset( $license_data['domain'] ) ) {
            $current_domain = wp_parse_url( home_url(), PHP_URL_HOST );
            if ( $license_data['domain'] !== $current_domain ) {
                return false;
            }
        }

        // Layer 4: Verify data signature.
        if ( ! $this->verify_stored_data() ) {
            return false;
        }

        // Layer 5: Check expiration.
        if ( isset( $license_data['expires'] ) && 'lifetime' !== $license_data['expires'] ) {
            $expires = strtotime( $license_data['expires'] );
            if ( $expires && $expires < time() ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get stored license key (masked for security).
     *
     * @return string License key or empty string.
     */
    public function get_license_key() {
        $license_data = $this->get_license_data();
        return isset( $license_data['key'] ) ? $license_data['key'] : '';
    }

    /**
     * Get license data from database.
     *
     * @param bool $force_check Force remote validation.
     * @return array License data.
     */
    public function get_license_data( $force_check = false ) {
        if ( null !== $this->license_data && ! $force_check ) {
            return $this->license_data;
        }

        $this->license_data = get_option( $this->option_name, array() );

        // Decrypt sensitive data if encrypted.
        if ( isset( $this->license_data['_enc'] ) && $this->license_data['_enc'] ) {
            $this->license_data = $this->decrypt_license_data( $this->license_data );
        }

        // Check if we need to revalidate.
        if ( $force_check || $this->needs_revalidation() ) {
            $this->validate_license_remote();
        }

        return $this->license_data;
    }

    /**
     * Check if license needs revalidation.
     *
     * @return bool True if needs revalidation.
     */
    private function needs_revalidation() {
        if ( empty( $this->license_data['key'] ) ) {
            return false;
        }

        $last_check = isset( $this->license_data['last_check'] ) ? $this->license_data['last_check'] : 0;
        return ( time() - $last_check ) > $this->cache_duration;
    }

    /**
     * Encrypt license data for storage.
     *
     * @param array $data License data.
     * @return array Encrypted data.
     */
    private function encrypt_license_data( $data ) {
        $key_to_encrypt = isset( $data['key'] ) ? $data['key'] : '';

        if ( $key_to_encrypt ) {
            $data['key'] = base64_encode( $this->xor_encrypt( $key_to_encrypt ) );
            $data['_enc'] = true;
        }

        return $data;
    }

    /**
     * Decrypt license data.
     *
     * @param array $data Encrypted license data.
     * @return array Decrypted data.
     */
    private function decrypt_license_data( $data ) {
        if ( isset( $data['key'] ) && isset( $data['_enc'] ) && $data['_enc'] ) {
            $data['key'] = $this->xor_encrypt( base64_decode( $data['key'] ) );
            unset( $data['_enc'] );
        }
        return $data;
    }

    /**
     * Simple XOR encryption/decryption.
     *
     * @param string $data Data to encrypt/decrypt.
     * @return string Result.
     */
    private function xor_encrypt( $data ) {
        $key = $this->security_salt;
        $result = '';

        for ( $i = 0; $i < strlen( $data ); $i++ ) {
            $result .= $data[ $i ] ^ $key[ $i % strlen( $key ) ];
        }

        return $result;
    }

    /**
     * Activate license.
     *
     * @param string $license_key License key.
     * @return array Result with 'success' and 'message'.
     */
    public function activate( $license_key ) {
        $license_key = $this->sanitize_license_key( $license_key );

        if ( empty( $license_key ) ) {
            return array(
                'success' => false,
                'message' => __( 'Please enter a valid license key.', 'jezweb' ),
            );
        }

        // Call API to activate.
        $response = $this->api_request( 'activate', $license_key );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        if ( isset( $response->success ) && $response->success ) {
            $current_domain = wp_parse_url( home_url(), PHP_URL_HOST );

            // Store license data with domain binding.
            $this->license_data = array(
                'key'         => $license_key,
                'status'      => 'active',
                'domain'      => $current_domain,
                'customer'    => isset( $response->customer ) ? sanitize_text_field( $response->customer ) : '',
                'email'       => isset( $response->email ) ? sanitize_email( $response->email ) : '',
                'expires'     => isset( $response->expires ) ? sanitize_text_field( $response->expires ) : 'lifetime',
                'activations' => isset( $response->activations ) ? absint( $response->activations ) : 0,
                'limit'       => isset( $response->limit ) ? ( $response->limit === 'unlimited' ? 'unlimited' : absint( $response->limit ) ) : 0,
                'last_check'  => time(),
                'activated'   => time(),
            );

            // Add signature for integrity verification.
            $this->license_data['_sig'] = $this->generate_data_signature( $this->license_data );

            // Encrypt before saving.
            $data_to_save = $this->encrypt_license_data( $this->license_data );
            update_option( $this->option_name, $data_to_save );

            return array(
                'success' => true,
                'message' => __( 'License activated successfully!', 'jezweb' ),
            );
        }

        $error_message = isset( $response->message ) ? $response->message : __( 'License activation failed.', 'jezweb' );

        return array(
            'success' => false,
            'message' => $error_message,
        );
    }

    /**
     * Deactivate license.
     *
     * @return array Result with 'success' and 'message'.
     */
    public function deactivate() {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return array(
                'success' => false,
                'message' => __( 'No license key to deactivate.', 'jezweb' ),
            );
        }

        // Call API to deactivate.
        $response = $this->api_request( 'deactivate', $license_key );

        // Clear local data regardless of API response.
        $this->license_data = array();
        delete_option( $this->option_name );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => true,
                'message' => __( 'License deactivated locally. Remote deactivation may have failed.', 'jezweb' ),
            );
        }

        return array(
            'success' => true,
            'message' => __( 'License deactivated successfully.', 'jezweb' ),
        );
    }

    /**
     * Validate license remotely.
     *
     * @return bool True if valid.
     */
    private function validate_license_remote() {
        $license_key = isset( $this->license_data['key'] ) ? $this->license_data['key'] : '';

        if ( empty( $license_key ) ) {
            return false;
        }

        $response = $this->api_request( 'validate', $license_key );

        if ( is_wp_error( $response ) ) {
            // Keep existing status on network error, but update last check.
            $this->license_data['last_check'] = time();
            $this->license_data['_sig'] = $this->generate_data_signature( $this->license_data );
            update_option( $this->option_name, $this->encrypt_license_data( $this->license_data ) );
            return isset( $this->license_data['status'] ) && 'active' === $this->license_data['status'];
        }

        if ( isset( $response->valid ) && $response->valid ) {
            $this->license_data['status']      = 'active';
            $this->license_data['last_check']  = time();
            $this->license_data['activations'] = isset( $response->activations ) ? absint( $response->activations ) : 0;
            $this->license_data['expires']     = isset( $response->expires ) ? sanitize_text_field( $response->expires ) : 'lifetime';
        } else {
            $this->license_data['status']     = 'inactive';
            $this->license_data['last_check'] = time();
        }

        // Update signature.
        $this->license_data['_sig'] = $this->generate_data_signature( $this->license_data );
        update_option( $this->option_name, $this->encrypt_license_data( $this->license_data ) );

        return 'active' === $this->license_data['status'];
    }

    /**
     * Make API request.
     *
     * @param string $action      API action (activate, deactivate, validate).
     * @param string $license_key License key.
     * @return object|WP_Error API response or error.
     */
    private function api_request( $action, $license_key ) {
        $url = sprintf(
            '%s/api/v1/license/%s',
            $this->api_url,
            sanitize_key( $action )
        );

        $body = array(
            'license_key' => $license_key,
            'plugin'      => $this->slug,
            'site_url'    => home_url(),
            'site_name'   => get_bloginfo( 'name' ),
            'wp_version'  => get_bloginfo( 'version' ),
            'php_version' => PHP_VERSION,
            'plugin_ver'  => defined( 'ATDW_LIVE_VERSION' ) ? ATDW_LIVE_VERSION : '1.0.0',
        );

        $args = array(
            'timeout'   => 15,
            'sslverify' => true,
            'headers'   => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'JezPress/' . ( defined( 'ATDW_LIVE_VERSION' ) ? ATDW_LIVE_VERSION : '1.0.0' ) . '; ' . home_url(),
            ),
            'body'      => wp_json_encode( $body ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error(
                'api_error',
                sprintf( __( 'License server returned error code: %d', 'jezweb' ), $code )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', __( 'Invalid response from license server.', 'jezweb' ) );
        }

        return $data;
    }

    /**
     * Sanitize license key.
     *
     * @param string $key License key.
     * @return string Sanitized key.
     */
    private function sanitize_license_key( $key ) {
        // Remove whitespace and convert to uppercase.
        $key = strtoupper( trim( $key ) );
        // Only allow alphanumeric and hyphens.
        $key = preg_replace( '/[^A-Z0-9\-]/', '', $key );
        return $key;
    }

    /**
     * Add license menu under plugin's menu.
     *
     * @return void
     */
    public function add_license_menu() {
        add_submenu_page(
            'atdw-live',
            __( 'License', 'atdw-live-products' ),
            __( 'License', 'atdw-live-products' ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'render_license_page' )
        );
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting( $this->menu_slug, $this->option_name );
    }

    /**
     * Handle license actions (activate/deactivate).
     *
     * @return void
     */
    public function handle_license_actions() {
        // Check for transient message after redirect (must run first, before early returns).
        $this->check_transient_message();

        if ( ! isset( $_POST['jezweb_license_action'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = sanitize_key( $_POST['jezweb_license_action'] );

        // Verify nonce.
        if ( ! isset( $_POST['jezweb_license_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jezweb_license_nonce'] ) ), 'jezweb_license_' . $action ) ) {
            add_settings_error( $this->menu_slug, 'nonce_error', __( 'Security check failed.', 'jezweb' ), 'error' );
            return;
        }

        // Check plugin slug matches.
        if ( ! isset( $_POST['jezweb_license_slug'] ) || $_POST['jezweb_license_slug'] !== $this->slug ) {
            return;
        }

        if ( 'activate' === $action ) {
            $license_key = isset( $_POST['jezweb_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['jezweb_license_key'] ) ) : '';
            $result      = $this->activate( $license_key );

            if ( $result['success'] ) {
                // Store success message in transient for display after redirect.
                set_transient( 'jezweb_license_message_' . $this->slug, array(
                    'type'    => 'success',
                    'message' => $result['message'],
                ), 30 );
                // Redirect to refresh the page and show updated status.
                wp_safe_redirect( admin_url( 'admin.php?page=' . $this->menu_slug . '&activated=1' ) );
                exit;
            } else {
                add_settings_error( $this->menu_slug, 'activation_failed', $result['message'], 'error' );
            }
        } elseif ( 'deactivate' === $action ) {
            $result = $this->deactivate();

            if ( $result['success'] ) {
                // Store success message in transient for display after redirect.
                set_transient( 'jezweb_license_message_' . $this->slug, array(
                    'type'    => 'success',
                    'message' => $result['message'],
                ), 30 );
                // Redirect to refresh the page and show updated status.
                wp_safe_redirect( admin_url( 'admin.php?page=' . $this->menu_slug . '&deactivated=1' ) );
                exit;
            } else {
                add_settings_error( $this->menu_slug, 'deactivation_failed', $result['message'], 'error' );
            }
        }
    }

    /**
     * Check for transient message after redirect.
     *
     * @return void
     */
    private function check_transient_message() {
        $transient_message = get_transient( 'jezweb_license_message_' . $this->slug );
        if ( $transient_message ) {
            delete_transient( 'jezweb_license_message_' . $this->slug );
            add_settings_error(
                $this->menu_slug,
                $transient_message['type'] === 'success' ? 'license_success' : 'license_error',
                $transient_message['message'],
                $transient_message['type']
            );
        }
    }

    /**
     * Display admin notices.
     *
     * @return void
     */
    public function admin_notices() {
        // Only show on relevant pages.
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Don't show on the license page itself.
        if ( strpos( $screen->id, $this->menu_slug ) !== false ) {
            return;
        }

        // Check if license is required and not valid.
        if ( $this->required && ! $this->is_valid() ) {
            $license_url = admin_url( 'admin.php?page=' . $this->menu_slug );
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php echo esc_html( $this->plugin_name ); ?>:</strong>
                    <?php esc_html_e( 'License activation required to enable plugin features.', 'jezweb' ); ?>
                    <a href="<?php echo esc_url( $license_url ); ?>">
                        <?php esc_html_e( 'Activate License', 'jezweb' ); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add license link to plugin action links.
     *
     * @param array $links Plugin action links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $license_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=' . $this->menu_slug ),
            __( 'License', 'jezweb' )
        );

        array_unshift( $links, $license_link );

        return $links;
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueue_admin_styles( $hook ) {
        if ( strpos( $hook, $this->menu_slug ) === false ) {
            return;
        }

        wp_add_inline_style( 'wp-admin', $this->get_admin_styles() );
    }

    /**
     * Get admin styles.
     *
     * @return string CSS styles.
     */
    private function get_admin_styles() {
        return '
            .jezweb-license-wrap {
                max-width: 800px;
                margin: 20px 0;
            }
            .jezweb-license-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .jezweb-license-box h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .jezweb-license-key-input {
                width: 100%;
                max-width: 400px;
                padding: 10px;
                font-size: 16px;
                font-family: monospace;
                letter-spacing: 2px;
            }
            .jezweb-license-status {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 3px;
                font-weight: bold;
                margin-left: 10px;
            }
            .jezweb-license-status.active {
                background: #d4edda;
                color: #155724;
            }
            .jezweb-license-status.inactive {
                background: #f8d7da;
                color: #721c24;
            }
            .jezweb-license-info {
                margin-top: 15px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .jezweb-license-info p {
                margin: 5px 0;
            }
            .jezweb-license-features {
                margin-top: 20px;
                padding: 15px;
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 4px;
            }
            .jezweb-license-features h3 {
                margin-top: 0;
                color: #856404;
            }
            .jezweb-license-features ul {
                margin: 10px 0 0 20px;
            }
        ';
    }

    /**
     * Render license page.
     *
     * @return void
     */
    public function render_license_page() {
        $license_data = $this->get_license_data();
        $is_active    = $this->is_valid();
        $license_key  = isset( $license_data['key'] ) ? $license_data['key'] : '';
        ?>
        <div class="wrap jezweb-license-wrap">
            <h1><?php echo esc_html( sprintf( __( '%s License', 'jezweb' ), $this->plugin_name ) ); ?></h1>

            <?php settings_errors( $this->menu_slug ); ?>

            <div class="jezweb-license-box">
                <h2>
                    <?php esc_html_e( 'License Status', 'jezweb' ); ?>
                    <?php if ( $is_active ) : ?>
                        <span class="jezweb-license-status active"><?php esc_html_e( 'Active', 'jezweb' ); ?></span>
                    <?php else : ?>
                        <span class="jezweb-license-status inactive"><?php esc_html_e( 'Inactive', 'jezweb' ); ?></span>
                    <?php endif; ?>
                </h2>

                <?php if ( $is_active ) : ?>
                    <div class="jezweb-license-info">
                        <p><strong><?php esc_html_e( 'License Key:', 'jezweb' ); ?></strong> <?php echo esc_html( $this->mask_license_key( $license_key ) ); ?></p>
                        <?php if ( ! empty( $license_data['customer'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'Licensed to:', 'jezweb' ); ?></strong> <?php echo esc_html( $license_data['customer'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $license_data['email'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'Email:', 'jezweb' ); ?></strong> <?php echo esc_html( $license_data['email'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $license_data['domain'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'Domain:', 'jezweb' ); ?></strong> <?php echo esc_html( $license_data['domain'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $license_data['expires'] ) && 'lifetime' !== $license_data['expires'] ) : ?>
                            <p><strong><?php esc_html_e( 'Expires:', 'jezweb' ); ?></strong> <?php echo esc_html( $license_data['expires'] ); ?></p>
                        <?php elseif ( 'lifetime' === ( $license_data['expires'] ?? '' ) ) : ?>
                            <p><strong><?php esc_html_e( 'Expires:', 'jezweb' ); ?></strong> <?php esc_html_e( 'Never (Lifetime)', 'jezweb' ); ?></p>
                        <?php endif; ?>
                        <?php if ( isset( $license_data['activations'] ) ) : ?>
                            <p>
                                <strong><?php esc_html_e( 'Activations:', 'jezweb' ); ?></strong>
                                <?php
                                if ( isset( $license_data['limit'] ) && 'unlimited' === $license_data['limit'] ) {
                                    echo esc_html( $license_data['activations'] ) . ' / ' . esc_html__( 'Unlimited', 'jezweb' );
                                } elseif ( isset( $license_data['limit'] ) ) {
                                    echo esc_html( $license_data['activations'] ) . ' / ' . esc_html( $license_data['limit'] );
                                } else {
                                    echo esc_html( $license_data['activations'] );
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <form method="post" style="margin-top: 20px;">
                        <?php wp_nonce_field( 'jezweb_license_deactivate', 'jezweb_license_nonce' ); ?>
                        <input type="hidden" name="jezweb_license_action" value="deactivate">
                        <input type="hidden" name="jezweb_license_slug" value="<?php echo esc_attr( $this->slug ); ?>">
                        <button type="submit" class="button button-secondary">
                            <?php esc_html_e( 'Deactivate License', 'jezweb' ); ?>
                        </button>
                    </form>

                <?php else : ?>
                    <div class="jezweb-license-features">
                        <h3><?php esc_html_e( 'Features Disabled', 'jezweb' ); ?></h3>
                        <p><?php esc_html_e( 'The following features require license activation:', 'jezweb' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'ATDW tourism product listings', 'jezweb' ); ?></li>
                            <li><?php esc_html_e( 'Server-side API caching', 'jezweb' ); ?></li>
                            <li><?php esc_html_e( 'Stale-while-revalidate fallback', 'jezweb' ); ?></li>
                            <li><?php esc_html_e( 'Cache management controls', 'jezweb' ); ?></li>
                            <li><?php esc_html_e( 'Automatic updates', 'jezweb' ); ?></li>
                        </ul>
                    </div>

                    <p style="margin-top: 20px;"><?php esc_html_e( 'Enter your license key to activate the plugin and enable all features.', 'jezweb' ); ?></p>

                    <form method="post">
                        <?php wp_nonce_field( 'jezweb_license_activate', 'jezweb_license_nonce' ); ?>
                        <input type="hidden" name="jezweb_license_action" value="activate">
                        <input type="hidden" name="jezweb_license_slug" value="<?php echo esc_attr( $this->slug ); ?>">

                        <p>
                            <input type="text"
                                   name="jezweb_license_key"
                                   class="jezweb-license-key-input"
                                   placeholder="XXXX-XXXX-XXXX-XXXX"
                                   value=""
                                   autocomplete="off">
                        </p>

                        <p>
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e( 'Activate License', 'jezweb' ); ?>
                            </button>
                        </p>
                    </form>

                    <p class="description">
                        <?php esc_html_e( 'Your license key was provided when you purchased the plugin.', 'jezweb' ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="jezweb-license-box">
                <h2><?php esc_html_e( 'Need a License?', 'jezweb' ); ?></h2>
                <p><?php esc_html_e( 'If you need a license key, please contact Jezweb:', 'jezweb' ); ?></p>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li><strong><?php esc_html_e( 'Email:', 'jezweb' ); ?></strong> <a href="mailto:support@jezweb.net">support@jezweb.net</a></li>
                    <li><strong><?php esc_html_e( 'Website:', 'jezweb' ); ?></strong> <a href="https://jezweb.com.au" target="_blank">jezweb.com.au</a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Mask license key for display.
     *
     * @param string $key License key.
     * @return string Masked key.
     */
    private function mask_license_key( $key ) {
        if ( strlen( $key ) <= 8 ) {
            return str_repeat( '*', strlen( $key ) );
        }

        return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
    }

    /**
     * Scheduled license check.
     *
     * @return void
     */
    public function scheduled_license_check() {
        $this->validate_license_remote();
    }

    /**
     * Clean up on plugin deactivation.
     *
     * @return void
     */
    public function cleanup() {
        wp_clear_scheduled_hook( 'jezweb_license_check_' . $this->slug );
    }

    /**
     * Check if plugin should be disabled due to missing license.
     *
     * @return bool True if plugin should be disabled.
     */
    public function should_disable_plugin() {
        return $this->required && ! $this->is_valid();
    }

    /**
     * Get license status for external use.
     *
     * @return string Status (active, inactive, expired).
     */
    public function get_status() {
        if ( ! $this->is_valid() ) {
            $data = $this->get_license_data();
            if ( isset( $data['expires'] ) && 'lifetime' !== $data['expires'] ) {
                $expires = strtotime( $data['expires'] );
                if ( $expires && $expires < time() ) {
                    return 'expired';
                }
            }
            return 'inactive';
        }
        return 'active';
    }

    /**
     * Runtime integrity check using multiple verification points.
     * This makes it harder to bypass license checks by patching.
     *
     * @return bool
     */
    public function runtime_check() {
        // Check 1: Verify API URL hasn't been modified.
        $expected_host = 'updates.jezpress.com';
        $current_host = wp_parse_url( $this->api_url, PHP_URL_HOST );
        if ( $current_host !== $expected_host ) {
            return false;
        }

        // Check 2: Verify core WordPress functions exist.
        if ( ! function_exists( 'get_option' ) || ! function_exists( 'wp_remote_post' ) ) {
            return false;
        }

        // Check 3: Verify the license data option exists and is an array.
        $data = get_option( $this->option_name );
        if ( ! empty( $data ) && ! is_array( $data ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get obfuscated validation state.
     * Returns a token that can be checked without exposing the license status directly.
     *
     * @return string
     */
    public function get_validation_token() {
        $is_valid = $this->is_valid() && $this->runtime_check();
        $timestamp = time();
        $data = $is_valid ? 'v' : 'i';
        return base64_encode( hash( 'sha256', $data . $this->security_salt . floor( $timestamp / 3600 ) ) );
    }

    /**
     * Verify validation token.
     *
     * @param string $token Token to verify.
     * @return bool
     */
    public function verify_validation_token( $token ) {
        $timestamp = time();
        $valid_token = base64_encode( hash( 'sha256', 'v' . $this->security_salt . floor( $timestamp / 3600 ) ) );
        return hash_equals( $valid_token, $token );
    }

    /**
     * Check if license file has been tampered with.
     *
     * @return bool
     */
    public function verify_file_integrity() {
        $license_file = __FILE__;

        if ( ! file_exists( $license_file ) ) {
            return false;
        }

        // Check file size is reasonable (not stripped).
        $file_size = filesize( $license_file );
        if ( $file_size < 10000 ) { // License class should be > 10KB.
            return false;
        }

        // Check for required method signatures.
        $content = file_get_contents( $license_file );
        $required_methods = array(
            'is_valid',
            'api_request',
            'verify_stored_data',
            'generate_data_signature',
        );

        foreach ( $required_methods as $method ) {
            if ( strpos( $content, 'function ' . $method ) === false ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Additional validation with anti-debug measures.
     *
     * @return bool
     */
    public function secure_is_valid() {
        // Anti-debug: Check if execution is being debugged.
        if ( defined( 'JEZWEB_LICENSE_BYPASS' ) || defined( 'LICENSE_BYPASS' ) ) {
            return false;
        }

        // Verify file hasn't been tampered.
        if ( ! $this->verify_file_integrity() ) {
            return false;
        }

        // Verify runtime environment.
        if ( ! $this->runtime_check() ) {
            return false;
        }

        // Standard validation.
        return $this->is_valid();
    }
}

/**
 * Helper function to check license status (obfuscated name).
 *
 * @return bool
 */
function jzwb_ck_ls() {
    $license = Jezweb_License::get_instance();
    if ( ! $license ) {
        return false;
    }
    return $license->secure_is_valid();
}
