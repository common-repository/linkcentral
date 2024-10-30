<?php

/**
 * Plugin Name: LinkCentral
 * Plugin URI: https://designforwp.com/linkcentral
 * Description: Easy URL shortener, custom link manager, and affiliate link tracking.
 * Version: 1.2.1
 * Author: Design for WP
 * Author URI: https://designforwp.com
 * License: GPL-3.0+
 * Text Domain: linkcentral
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}
if ( function_exists( 'linkcentral_fs' ) ) {
    linkcentral_fs()->set_basename( false, __FILE__ );
} else {
    // Define plugin constants
    define( 'LINKCENTRAL_VERSION', '1.2.1' );
    define( 'LINKCENTRAL_DB_VERSION', '1.0.0' );
    define( 'LINKCENTRAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'LINKCENTRAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    // Freemius initialization
    if ( !function_exists( 'linkcentral_fs' ) ) {
        // Create a helper function for easy SDK access.
        function linkcentral_fs() {
            global $linkcentral_fs;
            if ( !isset( $linkcentral_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $linkcentral_fs = fs_dynamic_init( array(
                    'id'             => '16835',
                    'slug'           => 'linkcentral',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_cbedf270c66e652bc776cff7b3b03',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'    => 'linkcentral',
                        'contact' => false,
                        'support' => false,
                        'pricing' => false,
                    ),
                    'anonymous_mode' => true,
                    'is_live'        => true,
                ) );
            }
            return $linkcentral_fs;
        }

        // Init Freemius.
        linkcentral_fs();
        // Signal that SDK was initiated.
        do_action( 'linkcentral_fs_loaded' );
        // Rename to license page
        linkcentral_fs()->override_i18n( [
            'account' => __( 'License', 'linkcentral' ),
        ] );
        // Disable opt-in option by default
        linkcentral_fs()->add_filter( 'permission_diagnostic_default', '__return_false' );
        linkcentral_fs()->add_filter( 'permission_extensions_default', '__return_false' );
        linkcentral_fs()->add_filter( 'hide_freemius_powered_by', '__return_true' );
        // Hide some admin notices
        linkcentral_fs()->add_filter(
            'show_admin_notice',
            function ( $show, $message ) {
                if ( $message['id'] === 'premium_activated' || $message['id'] === 'connect_account' || $message['id'] === 'plan_upgraded' ) {
                    return false;
                }
                return $show;
            },
            10,
            2
        );
    }
    // Include files needed for both admin and front-end
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/post-type/post-type.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/integrations/frontend/shortcode.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/redirection.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/integrations/frontend/content-filter.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/activator.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/updater.php';
    require_once LINKCENTRAL_PLUGIN_DIR . 'includes/automations/cleanup.php';
    /**
     * Main LinkCentral Class
     *
     * This class is the core of LinkCentral, initializing all major components.
     */
    class LinkCentral {
        private $url_prefix;

        private $post_type;

        private $shortcode;

        private $redirection;

        private $content_filter;

        // Admin-only properties
        private $admin;

        private $settings;

        private $insights;

        private $integrations;

        public function __construct() {
            $this->url_prefix = get_option( 'linkcentral_url_prefix', 'go' );
            $this->post_type = new LinkCentral_Post_Type();
            $this->shortcode = new LinkCentral_Shortcode($this->url_prefix);
            $this->redirection = new LinkCentral_Redirection($this->url_prefix);
            $this->content_filter = new LinkCentral_Content_Filter();
            $this->load_admin_files();
        }

        public function init() {
            add_action( 'init', array($this, 'load_textdomain') );
            $this->post_type->init();
            $this->shortcode->init();
            $this->redirection->init();
            $this->content_filter->init();
        }

        public function load_admin_files() {
            if ( is_admin() || is_user_logged_in() ) {
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/admin.php';
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/links-overview.php';
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/settings.php';
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/insights.php';
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/integrations/backend/integrations.php';
                $this->admin = new LinkCentral_Admin();
                $this->settings = new LinkCentral_Settings();
                $this->insights = new LinkCentral_Insights($this->admin);
                $this->integrations = new LinkCentral_integrations();
                $this->admin->init();
                $this->settings->init();
                $this->insights->init();
                $this->integrations->init();
                // Initialize LinkCentral_Links_Overview for bulk processing actions
                LinkCentral_Links_Overview::init();
            }
        }

        public function load_textdomain() {
            load_plugin_textdomain( 'linkcentral', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

    }

    /**
     * Initialize the plugin.
     */
    function linkcentral_init() {
        $linkcentral = new LinkCentral();
        $linkcentral->init();
    }

    add_action( 'plugins_loaded', 'linkcentral_init' );
    // Activation hook
    register_activation_hook( __FILE__, 'linkcentral_activate' );
    // Deactivation hook
    register_deactivation_hook( __FILE__, 'linkcentral_deactivate' );
}