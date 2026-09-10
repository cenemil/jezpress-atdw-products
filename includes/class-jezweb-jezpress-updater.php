<?php
/**
 * JezPress Updater Class
 *
 * Automatic plugin updates from JezPress update server.
 *
 * @package Jezweb
 * @since 1.0.0
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Jezweb_JezPress_Updater {

    /**
     * JezPress base URL.
     *
     * @var string
     */
    private $api_url = 'https://updates.jezpress.com';

    /**
     * Plugin file path.
     *
     * @var string
     */
    private $file;

    /**
     * Plugin data from header.
     *
     * @var array
     */
    private $plugin = array();

    /**
     * Plugin basename (e.g. jezpress-atdw-products/jezpress-atdw-products.php).
     *
     * @var string
     */
    private $basename = '';

    /**
     * Plugin slug on JezPress.
     *
     * @var string
     */
    private $slug = '';

    /**
     * Cached API response (in-memory).
     *
     * @var object|null
     */
    private $api_response = null;

    /**
     * License key for authenticated requests.
     *
     * @var string
     */
    private $license_key = '';

    /**
     * Transient cache duration in seconds (12 hours).
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * Constructor.
     *
     * @param string $file Plugin main file path (__FILE__ from main plugin).
     */
    public function __construct( $file ) {
        if ( empty( $file ) || ! file_exists( $file ) ) {
            return;
        }

        $this->file = $file;
    }

    /**
     * Set plugin properties from plugin header.
     */
    private function ensure_plugin_properties() {
        if ( ! empty( $this->plugin ) && ! empty( $this->basename ) ) {
            return;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->plugin   = get_plugin_data( $this->file );
        $this->basename = plugin_basename( $this->file );
    }

    /**
     * Set JezPress plugin slug.
     *
     * @param string $slug Plugin slug on JezPress.
     * @return $this
     */
    public function set_slug( $slug ) {
        $this->slug = sanitize_title( $slug );
        return $this;
    }

    /**
     * Set license key for authenticated downloads.
     *
     * @param string $license_key License key.
     * @return $this
     */
    public function set_license( $license_key ) {
        $this->license_key = sanitize_text_field( $license_key );
        return $this;
    }

    /**
     * Set API base URL (must be HTTPS).
     *
     * @param string $url API base URL (e.g. https://updates.jezpress.com).
     * @return $this
     */
    public function set_api_url( $url ) {
        $url = esc_url_raw( $url );

        if ( strpos( $url, 'https://' ) !== 0 ) {
            $url = str_replace( 'http://', 'https://', $url );
        }

        $this->api_url = rtrim( $url, '/' );
        return $this;
    }

    /**
     * Initialize updater hooks. Call this LAST after set_slug/set_api_url.
     *
     * IMPORTANT: Do NOT wrap this in is_admin(). WP cron auto-updates
     * need these hooks to fire outside admin context.
     */
    public function initialize() {
        if ( empty( $this->slug ) ) {
            return;
        }

        // Inject update info into WP's update check (fires from admin AND cron).
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

        // Plugin information popup (View details link).
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );

        // Add "Check for updates" link in plugin row.
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

        // Handle manual "Check for updates" click.
        add_action( 'admin_init', array( $this, 'handle_manual_check' ) );

        // Clear cached data after this plugin is updated.
        add_action( 'upgrader_process_complete', array( $this, 'clear_cache_on_update' ), 10, 2 );
    }

    /**
     * Fetch update info from JezPress /api/v1/update endpoint.
     *
     * This endpoint validates the license key and returns an authenticated
     * download_url with license_key + site_url baked in.
     *
     * @param bool $force_refresh Bypass cache.
     * @return object|false
     */
    private function get_remote_info( $force_refresh = false ) {
        if ( ! $force_refresh && ! empty( $this->api_response ) ) {
            return $this->api_response;
        }

        $cache_key = 'jezpress_update_' . md5( $this->slug . $this->license_key );

        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached && is_object( $cached ) && ! empty( $cached->version ) ) {
                $this->api_response = $cached;
                return $cached;
            }
        }

        $this->ensure_plugin_properties();

        $params = array(
            'plugin'  => $this->slug,
            'version' => ! empty( $this->plugin['Version'] ) ? $this->plugin['Version'] : '0.0.0',
        );
        if ( ! empty( $this->license_key ) ) {
            $params['license_key'] = $this->license_key;
        }
        if ( function_exists( 'home_url' ) ) {
            $params['site_url'] = home_url();
        }

        $url = add_query_arg( $params, $this->api_url . '/api/v1/update' );

        $response = wp_remote_get( $url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'headers'   => array( 'Accept' => 'application/json' ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'API request failed: ' . $response->get_error_message() );
            return false;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $this->log( 'API returned HTTP ' . wp_remote_retrieve_response_code( $response ) );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $body ) || empty( $body->version ) ) {
            $this->log( 'Invalid API response: ' . wp_remote_retrieve_body( $response ) );
            return false;
        }

        $info = new stdClass();
        $info->version      = sanitize_text_field( $body->version );
        $info->download_url = isset( $body->download_url ) ? esc_url_raw( $body->download_url ) : '';
        $info->changelog    = isset( $body->changelog ) ? wp_kses_post( $body->changelog ) : '';
        $info->requires_wp  = isset( $body->requires_wp ) ? sanitize_text_field( $body->requires_wp ) : '';
        $info->requires_php = isset( $body->requires_php ) ? sanitize_text_field( $body->requires_php ) : '';
        $info->tested_wp    = isset( $body->tested_wp ) ? sanitize_text_field( $body->tested_wp ) : '';
        $info->name         = isset( $body->name ) ? sanitize_text_field( $body->name ) : '';
        $info->author       = isset( $body->author ) ? sanitize_text_field( $body->author ) : '';
        $info->last_updated = isset( $body->last_updated ) ? sanitize_text_field( $body->last_updated ) : '';
        $info->description  = isset( $body->description ) ? wp_kses_post( $body->description ) : '';

        $this->api_response = $info;
        set_transient( $cache_key, $info, $this->cache_duration );

        return $info;
    }

    /**
     * Inject our plugin into WordPress update checks.
     *
     * @param object $transient The update_plugins transient.
     * @return object
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $this->ensure_plugin_properties();

        if ( empty( $this->plugin['Version'] ) || empty( $this->basename ) ) {
            return $transient;
        }

        $remote = $this->get_remote_info();

        if ( false === $remote || empty( $remote->version ) ) {
            return $transient;
        }

        if ( version_compare( $remote->version, $this->plugin['Version'], '>' ) ) {
            // Update available — add to response list.
            $transient->response[ $this->basename ] = (object) array(
                'id'           => $this->basename,
                'slug'         => dirname( $this->basename ),
                'plugin'       => $this->basename,
                'new_version'  => $remote->version,
                'url'          => '',
                'package'      => $remote->download_url,
                'icons'        => array(),
                'banners'      => array(),
                'tested'       => $remote->tested_wp,
                'requires_php' => $remote->requires_php,
                'requires'     => $remote->requires_wp,
            );
        } else {
            // Up to date — add to no_update list so WP knows we checked.
            $transient->no_update[ $this->basename ] = (object) array(
                'id'          => $this->basename,
                'slug'        => dirname( $this->basename ),
                'plugin'      => $this->basename,
                'new_version' => $this->plugin['Version'],
                'url'         => '',
                'package'     => '',
            );
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View details" popup.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        $this->ensure_plugin_properties();

        if ( ! isset( $args->slug ) || dirname( $this->basename ) !== $args->slug ) {
            return $result;
        }

        $remote = $this->get_remote_info();

        if ( false === $remote ) {
            return $result;
        }

        return (object) array(
            'name'              => ! empty( $remote->name ) ? $remote->name : $this->plugin['Name'],
            'slug'              => dirname( $this->basename ),
            'version'           => $remote->version,
            'author'            => ! empty( $remote->author ) ? $remote->author : $this->plugin['AuthorName'],
            'author_profile'    => $this->plugin['AuthorURI'],
            'last_updated'      => $remote->last_updated,
            'homepage'          => $this->plugin['PluginURI'],
            'short_description' => $this->plugin['Description'],
            'sections'          => array(
                'description' => ! empty( $remote->description ) ? $remote->description : $this->plugin['Description'],
                'changelog'   => $remote->changelog,
            ),
            'download_link'     => $remote->download_url,
            'requires'          => $remote->requires_wp,
            'requires_php'      => $remote->requires_php,
            'tested'            => $remote->tested_wp,
        );
    }

    /**
     * Add "Check for updates" link to plugin row meta.
     *
     * @param array  $links
     * @param string $file
     * @return array
     */
    public function plugin_row_meta( $links, $file ) {
        $this->ensure_plugin_properties();

        if ( $file !== $this->basename ) {
            return $links;
        }

        if ( ! current_user_can( 'update_plugins' ) ) {
            return $links;
        }

        $url = wp_nonce_url(
            admin_url( 'plugins.php?jezpress_check=' . rawurlencode( $this->slug ) ),
            'jezpress_check_' . $this->slug,
            'jezpress_nonce'
        );

        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'jezweb' ) . '</a>';

        return $links;
    }

    /**
     * Handle the manual "Check for updates" click.
     */
    public function handle_manual_check() {
        if ( ! isset( $_GET['jezpress_check'] ) || $_GET['jezpress_check'] !== $this->slug ) {
            return;
        }

        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_die( 'Permission denied.', 403 );
        }

        if ( ! isset( $_GET['jezpress_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['jezpress_nonce'] ) ), 'jezpress_check_' . $this->slug ) ) {
            wp_die( 'Security check failed.', 403 );
        }

        // Clear all caches so WP re-checks immediately.
        $this->clear_cache();
        delete_site_transient( 'update_plugins' );

        wp_safe_redirect( admin_url( 'plugins.php?jezpress_checked=1' ) );
        exit;
    }

    /**
     * Clear our cache after this plugin has been updated.
     *
     * @param WP_Upgrader $upgrader
     * @param array       $options
     */
    public function clear_cache_on_update( $upgrader, $options ) {
        if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
            return;
        }

        $this->ensure_plugin_properties();

        if ( isset( $options['plugins'] ) && in_array( $this->basename, (array) $options['plugins'], true ) ) {
            $this->clear_cache();
        }
    }

    /**
     * Clear cached API response.
     */
    public function clear_cache() {
        delete_transient( 'jezpress_update_' . md5( $this->slug . $this->license_key ) );
        $this->api_response = null;
    }

    /**
     * Log message when WP_DEBUG is on.
     *
     * @param string $message
     */
    private function log( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( '[JezPress Updater] ' . $this->slug . ': ' . $message );
        }
    }
}
