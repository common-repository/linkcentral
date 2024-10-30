<?php

/**
 * LinkCentral Admin Class
 *
 * This class handles the admin-side functionality of the plugin.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Admin {
    private $settings;

    private $insights;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new LinkCentral_Settings();
        $this->insights = new LinkCentral_Insights($this);
    }

    /**
     * Initialize the admin functionality
     */
    public function init() {
        // Add admin menu pages
        add_action( 'admin_menu', array($this, 'add_menu_pages') );
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
        // Set current menu for proper highlighting
        add_filter( 'parent_file', array($this, 'set_current_menu') );
        // Add action for the header
        add_action( 'linkcentral_admin_header', array($this, 'render_admin_header') );
        // Add LinkCentral shortcut to the admin bar
        add_action( 'admin_bar_menu', array($this, 'add_admin_bar_menu_item'), 100 );
        // Register REST API fields
        add_action( 'rest_api_init', array($this, 'register_rest_fields') );
    }

    /**
     * Register additional fields for the REST API
     * We need to add these so the API (used by Gutenberg) sends these values
     */
    public function register_rest_fields() {
        register_rest_field( 'linkcentral_link', 'global_nofollow', [
            'get_callback' => function () {
                return get_option( 'linkcentral_global_nofollow', false );
            },
            'schema'       => [
                'type' => 'boolean',
            ],
        ] );
        register_rest_field( 'linkcentral_link', 'global_sponsored', [
            'get_callback' => function () {
                return get_option( 'linkcentral_global_sponsored', false );
            },
            'schema'       => [
                'type' => 'boolean',
            ],
        ] );
        register_rest_field( 'linkcentral_link', 'global_css_classes', [
            'get_callback' => function () {
                return get_option( 'linkcentral_custom_css_classes', '' );
            },
            'schema'       => [
                'type' => 'string',
            ],
        ] );
    }

    /**
     * Add menu and submenu pages to the WordPress admin
     */
    public function add_menu_pages() {
        // Add main menu page
        add_menu_page(
            __( 'LinkCentral', 'linkcentral' ),
            __( 'LinkCentral', 'linkcentral' ),
            'manage_options',
            'linkcentral',
            array('LinkCentral_Links_Overview', 'render_all_links_page'),
            'data:image/svg+xml;base64,PHN2ZyBpZD0ibGlua2NlbnRyYWwtbG9nbyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgNDggNDgiPgogIDxwYXRoIGZpbGw9IiM5Y2EyYTciIGlkPSJ0ciIgZD0iTTQzLjI3LDI3LjYyYy0yLjEzLDIuMTMtNC44NywzLjUtOC41Nyw0LjI2LjAxLS4wOS4wMi0uMTkuMDQtLjI4LjA0LS4zMS4wOC0uNjMuMTItLjk1LjA5LS43NS4xOC0xLjU1LjI1LTIuMzQuMDItLjIxLjA0LS40MS4wNS0uNi4wMi0uMjEuMDQtLjQuMDUtLjYyLjAyLS4yMS4wMy0uNDEuMDUtLjYyLjAyLS4zMS4wNC0uNjMuMDUtLjkzLDEuMzMtLjUsMi40My0xLjIsMy4zNi0yLjEyLDMuODktMy44OSwzLjg5LTEwLjIyLDAtMTQuMS0xLjg4LTEuODktNC4zOS0yLjkyLTcuMDUtMi45MnMtNS4xNywxLjAzLTcuMDUsMi45MmMtLjg1Ljg1LTEuNTIsMi4xLTIsMy43Mi0uMDUuMTUtLjEuMzItLjE0LjUtLjg3LDMuMjEtLjk2LDcuMjctLjk0LDkuMzl2LjQ1cy4wMS4xNC4wMS4xNGMuMDEuOTcuMDUsMS41OC4wNiwxLjYxbC4wMy41OC4wNS43MmgxLjNjLjI2LDAsLjUyLDAsLjc3LjAxdi4yM2MwLC4yMi0uMDEuNDMtLjAyLjY2di4yNWMtLjAxLjE0LS4wMi4zLS4wMy40N3YuMjFzLS4wMS4xMi0uMDEuMTJsLS4wMi4yMnYuMTdzLS4wMy4yNi0uMDMuMjZ2LjEzcy0uMDEuMTEtLjAxLjExbC0uMDIuMTljLS4wMS4xLS4wMi4yMS0uMDMuMzMtLjAzLjMyLS4wNi42NC0uMS45N2gwcy0uMDEuMDMtLjAxLjA0di4wNWMtLjAzLjI3LS4wNy41NC0uMTEuNzl2LjA4cy0uMDMuMDktLjAzLjA5Yy0uMDQuMjktLjA5LjU3LS4xNS44Ny0xLjI4LS4wMS0yLjU3LS4wNC0zLjg0LS4wNi0xLjI5LS4wMy0yLjYtLjA1LTMuOTEtLjA3LS4wNy0xLjctLjE0LTQuOS4wMS04LjU2bC4wMi0uNDVjLjE3LTMuMy40OS02LjMuOTYtOC45MS44Ny00Ljc5LDIuMjItOC4xMiw0LjAxLTkuOTFDMjMuNDMsMS42OCwyNy40OSwwLDMxLjgyLDBzOC4zOSwxLjY4LDExLjQ1LDQuNzRjNi4zMSw2LjMxLDYuMzEsMTYuNTgsMCwyMi44OVoiLz4KICA8cGF0aCBmaWxsPSIjOWNhMmE3IiBpZD0iYmwiIGQ9Ik0zMi42MiwyNHYuMjJzLS4wMi4yMy0uMDIuMjN2LjExcy0uMDEuMDctLjAxLjA3Yy0uMTcsMy4yNC0uNDksNi4xNy0uOTUsOC43NGwtLjAzLjE4Yy0uODgsNC42OS0yLjIyLDcuOTYtMy45Nyw5LjcyLTMuMDYsMy4wNS03LjEyLDQuNzMtMTEuNDUsNC43M3MtOC40LTEuNjgtMTEuNDUtNC43M2MtNi4zMS02LjMyLTYuMzEtMTYuNTgsMC0yMi45LDIuMTMtMi4xMyw0Ljg2LTMuNDksOC41Ny00LjI2LS4wMy4yMy0uMDYuNDYtLjA5Ljd2LjA1cy0uMDEuMDEtLjAxLjAxYy0uMDQuMzEtLjA4LjYzLS4xMi45NWwtLjA0LjQ0Yy0uMDEuMDgtLjAyLjE3LS4wMy4yNS0uMDIuMi0uMDQuMzktLjA2LjU5LS4wNC4zOS0uMDcuNzktLjExLDEuMjFsLS4wNC42Yy0uMDQuNTItLjA4LDEuMDMtLjEsMS41NS0xLjMzLjQ5LTIuNDMsMS4xOS0zLjM2LDIuMTEtMy44OCwzLjg5LTMuODgsMTAuMjIsMCwxNC4xMSwxLjg4LDEuODgsNC4zOSwyLjkxLDcuMDUsMi45MXM1LjE3LTEuMDMsNy4wNS0yLjkyYy43NC0uNzQsMS4zNi0xLjgxLDEuODMtMy4xN2guMDVsLjE0LS41OC4wNi0uMjIuMDYtLjIzYy4wMi0uMDcuMDMtLjEzLjA0LS4xOS43OC0zLjAyLjkyLTYuNjcuOS05LjIyLDAtLjE3LDAtLjMyLS4wMS0uNDZ2LS45MWgtLjAyYy0uMDItLjQ5LS4wMy0uODEtLjA0LS44NWwtLjAzLS41OC0uMDUtLjcyaC0yLjA4di0uMjVjMC0uMjEuMDEtLjQyLjAyLS42NXYtLjI1cy4wMS0uMTQuMDEtLjE0di0uMTljLjAxLS4xMS4wMi0uMjQuMDMtLjM2di0uMDVjLjAyLS4yOS4wNC0uNTguMDYtLjg4LjAyLS4zNi4wNi0uNzIuMS0xLjExLjAxLS4xMi4wMy0uMjUuMDUtLjR2LS4wOGwuMDItLjExLjAyLS4xNXYtLjFjLjAyLS4wNi4wMy0uMTIuMDQtLjIyLjAxLS4wOC4wMy0uMTcuMDQtLjI4LjAxLS4wNy4wMi0uMTQuMDQtLjI1LjAyLS4wOC4wMy0uMTcuMDUtLjI5LjAxLS4wNy4wMi0uMTMuMDQtLjIxLjAyLS4wOS4wMy0uMTkuMDUtLjI3LDEuMjkuMDEsMi41OS4wMywzLjc2LjA2LDEuMTcuMDIsMi41OS4wNSw0LC4wNi4wOCwxLjg4LjE0LDUtLjAxLDguNTdaIi8+Cjwvc3ZnPg==',
            30
        );
        // Add submenu pages
        add_submenu_page(
            'linkcentral',
            __( 'All Links', 'linkcentral' ),
            __( 'All Links', 'linkcentral' ),
            'manage_options',
            'linkcentral',
            array('LinkCentral_Links_Overview', 'render_all_links_page')
        );
        add_submenu_page(
            'linkcentral',
            __( 'Add New Link', 'linkcentral' ),
            __( 'Add New Link', 'linkcentral' ),
            'manage_options',
            'post-new.php?post_type=linkcentral_link'
        );
        add_submenu_page(
            'linkcentral',
            __( 'Link Categories', 'linkcentral' ),
            __( 'Link Categories', 'linkcentral' ),
            'manage_categories',
            'edit-tags.php?taxonomy=linkcentral_category&post_type=linkcentral_link'
        );
        add_submenu_page(
            'linkcentral',
            __( 'Insights', 'linkcentral' ),
            __( 'Insights', 'linkcentral' ),
            'manage_options',
            'linkcentral-insights',
            array($this->insights, 'render_insights_page')
        );
        add_submenu_page(
            'linkcentral',
            __( 'Settings', 'linkcentral' ),
            __( 'Settings', 'linkcentral' ),
            'manage_options',
            'linkcentral-settings',
            array($this->settings, 'render_settings_page')
        );
    }

    /**
     * Enqueue scripts and styles for the admin area
     *
     * @param string $hook The current admin page
     */
    public function enqueue_scripts( $hook ) {
        // Enqueue the global admin CSS on all admin pages
        wp_enqueue_style(
            'global-admin',
            LINKCENTRAL_PLUGIN_URL . 'assets/css/global-admin.css',
            array(),
            LINKCENTRAL_VERSION
        );
        $screen = get_current_screen();
        // Check if we're on a LinkCentral-related page
        if ( strpos( $screen->id, 'linkcentral' ) === false && $screen->post_type !== 'linkcentral_link' && !($screen->base === 'edit-tags' && $screen->taxonomy === 'linkcentral_category') ) {
            return;
        }
        // Enqueue styles specific to LinkCentral pages
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style(
            'linkcentral-admin',
            LINKCENTRAL_PLUGIN_URL . 'assets/css/admin.css',
            array('global-admin'),
            LINKCENTRAL_VERSION
        );
        // Enqueue scripts specific to LinkCentral pages
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script(
            'linkcentral-admin',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            LINKCENTRAL_VERSION,
            true
        );
        wp_enqueue_script(
            'linkcentral-admin-settings',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            LINKCENTRAL_VERSION,
            true
        );
        wp_enqueue_script(
            'linkcentral-admin-tooltips',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/admin-tooltips.js',
            array('jquery', 'jquery-ui-tooltip'),
            LINKCENTRAL_VERSION,
            true
        );
        // Localize scripts
        wp_localize_script( 'linkcentral-admin', 'linkcentral_admin', array(
            'ajax_url'                           => admin_url( 'admin-ajax.php' ),
            'nonce'                              => wp_create_nonce( 'linkcentral_admin_nonce' ),
            'tinymce_nonce'                      => wp_create_nonce( 'linkcentral_tinymce_nonce' ),
            'can_use_premium_code__premium_only' => linkcentral_fs()->can_use_premium_code__premium_only(),
            'required_fields_message'            => __( 'Please fill in all required fields.', 'linkcentral' ),
            'invalid_url_message'                => __( 'Please enter a valid URL for the destination.', 'linkcentral' ),
            'copied_message'                     => __( 'Copied!', 'linkcentral' ),
            'copy_message'                       => __( 'Copy', 'linkcentral' ),
            'track_user_agent'                   => get_option( 'linkcentral_track_user_agent', true ),
            'track_ip'                           => get_option( 'linkcentral_track_ip', true ),
        ) );
        wp_localize_script( 'linkcentral-admin', 'linkcentral_post_type', array('linkcentral_link') );
        // Add the body class filter
        add_filter( 'admin_body_class', array($this, 'add_linkcentral_body_class') );
    }

    /**
     * Set the current menu for proper highlighting
     *
     * @param string $parent_file The current parent file
     * @return string The modified parent file
     */
    public function set_current_menu( $parent_file ) {
        global $submenu_file, $current_screen, $pagenow;
        if ( $current_screen->post_type == 'linkcentral_link' ) {
            if ( $pagenow == 'edit-tags.php' && $current_screen->taxonomy == 'linkcentral_category' ) {
                $submenu_file = 'edit-tags.php?taxonomy=linkcentral_category&post_type=linkcentral_link';
            }
            $parent_file = 'linkcentral';
        }
        return $parent_file;
    }

    /**
     * Get links for search
     *
     * @param string $search_term The search term
     * @param string $status The post status
     * @return array An array of links
     */
    public function get_links_for_search( $search_term, $status = 'publish' ) {
        $args = array(
            'post_type'      => 'linkcentral_link',
            'posts_per_page' => 10,
            's'              => $search_term,
            'post_status'    => $status,
        );
        $query = new WP_Query($args);
        $links = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $links[] = array(
                    'id'                 => get_the_ID(),
                    'title'              => get_the_title(),
                    'slug'               => get_post_field( 'post_name', get_the_ID() ),
                    'nofollow'           => get_post_meta( get_the_ID(), '_linkcentral_nofollow', true ),
                    'sponsored'          => get_post_meta( get_the_ID(), '_linkcentral_sponsored', true ),
                    'css_classes_option' => get_post_meta( get_the_ID(), '_linkcentral_css_classes_option', true ),
                    'custom_css_classes' => get_post_meta( get_the_ID(), '_linkcentral_custom_css_classes', true ),
                );
            }
        }
        wp_reset_postdata();
        return $links;
    }

    /**
     * Add LinkCentral body class to the admin body
     *
     * @param string $classes The current body classes
     * @return string The modified body classes
     */
    public function add_linkcentral_body_class( $classes ) {
        $classes .= ' linkcentral-pagestyles';
        return $classes;
    }

    /**
     * Render the admin header
     */
    public function render_admin_header() {
        $logo_url = LINKCENTRAL_PLUGIN_URL . 'assets/images/linkcentral-logo.svg';
        ?>
        <div class="linkcentral-admin-header">
            <div class="linkcentral-header-content" style="display: flex; align-items: center;">
                <img src="<?php 
        echo esc_url( $logo_url );
        ?>" alt="LinkCentral Logo" class="linkcentral-logo">
                <span class="linkcentral-header-text">LinkCentral</span>
                <a href="https://designforwp.com/docs/linkcentral/" target="_blank" class="button button-secondary" style="margin-left: auto; display: flex; align-items: center; padding: 5px 10px; color: #23282d; border-color: #23282d;">
                    <span class="dashicons dashicons-book" style="margin-right: 5px;"></span>
                    <?php 
        esc_html_e( 'Documentation', 'linkcentral' );
        ?>
                </a>
            </div>
        </div>
        <?php 
    }

    /**
     * Add LinkCentral shortcut to the admin bar "New" menu
     *
     * @param WP_Admin_Bar $wp_admin_bar WordPress admin bar object
     */
    public function add_admin_bar_menu_item( $wp_admin_bar ) {
        if ( !current_user_can( 'edit_posts' ) ) {
            return;
        }
        $wp_admin_bar->add_menu( array(
            'parent' => 'new-content',
            'id'     => 'new-linkcentral',
            'title'  => __( 'LinkCentral Link', 'linkcentral' ),
            'href'   => admin_url( 'post-new.php?post_type=linkcentral_link' ),
        ) );
    }

}
