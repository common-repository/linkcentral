<?php
/**
 * LinkCentral Integrations Class
 *
 * This class handles the integration of LinkCentral functionality
 * with various WordPress editors, including Gutenberg and TinyMCE.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_integrations {
    /**
     * Initialize the integrations.
     *
     * Sets up WordPress hooks for various editor integrations.
     */
    public function init() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('admin_init', array($this, 'add_editor_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_tinymce_integration'));
        add_filter('mce_external_plugins', array($this, 'add_tinymce_plugin'), 9999);
        add_filter('mce_buttons', array($this, 'register_tinymce_button'), 9999);
        add_action('wp_ajax_linkcentral_tinymce_search_links', array($this, 'ajax_tinymce_search_links'));
        add_action('wp_ajax_linkcentral_get_link_data', array($this, 'ajax_get_link_data'));

        // Elementor
        add_action('elementor/init', function() {
            if (did_action('elementor/loaded')) {
                // Generate a random identifier for Elementor
                // We can generate it for each page run because Elementor does not cache elements with dynamic tags.
                self::get_random_identifier();

                add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_tinymce_integration'));
                add_action('elementor/preview/enqueue_scripts', array($this, 'enqueue_elementor_preview_script'));
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/integrations/backend/elementor/elementor-controls.php';
                require_once LINKCENTRAL_PLUGIN_DIR . 'includes/integrations/frontend/elementor/elementor-output.php';
            }
        });

        // Beaver Builder
        if (class_exists('FLBuilderModel')) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_tinymce_integration'));
        }
    }

    /**
     * Add custom styles to the editor.
     */
    public function add_editor_styles() {
        add_editor_style(LINKCENTRAL_PLUGIN_URL . 'assets/css/gutenberg-editor.css?ver=' . LINKCENTRAL_VERSION);
    }

    /**
     * Enqueue assets for the Gutenberg block editor.
     *
     * Loads scripts and localized data for Gutenberg integration.
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'linkcentral-gutenberg-integration',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/gutenberg-integration.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch'),
            LINKCENTRAL_VERSION,
            true
        );

        $common_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkcentral_gutenberg_nonce'),
            'site_url' => get_site_url(),
            'url_prefix' => get_option('linkcentral_url_prefix', 'go'),
            'plugin_url' => LINKCENTRAL_PLUGIN_URL,
            'can_use_premium_code__premium_only' => linkcentral_fs()->can_use_premium_code__premium_only(),
            'default_link_insertion_type' => get_option('linkcentral_default_link_insertion_type', 'synchronized')
        );

        wp_localize_script('linkcentral-gutenberg-integration', 'linkcentral_gutenberg_data', $common_data);
    }

    /**
     * Enqueue assets for TinyMCE integration.
     *
     * Loads scripts and localized data for TinyMCE integration on edit pages.
     */
    public function enqueue_tinymce_integration() {
        // Check if we're on an edit page with sufficient rights
        if (!$this->can_edit_on_page()) {
            return;
        }
        
        wp_enqueue_script(
            'linkcentral-tinymce-integration',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/tinymce-integration.js',
            array('jquery'),
            LINKCENTRAL_VERSION,
            true
        );

        $common_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkcentral_tinymce_nonce'),
            'site_url' => get_site_url(),
            'url_prefix' => get_option('linkcentral_url_prefix', 'go'),
            'plugin_url' => LINKCENTRAL_PLUGIN_URL,
            'can_use_premium_code__premium_only' => linkcentral_fs()->can_use_premium_code__premium_only(),
            'default_link_insertion_type' => get_option('linkcentral_default_link_insertion_type', 'synchronized')
        );

        wp_localize_script('linkcentral-tinymce-integration', 'linkcentral_tinymce_data', $common_data);
    }

    /**
     * Add the LinkCentral TinyMCE plugin.
     *
     * @param array $plugin_array An array of TinyMCE plugins.
     * @return array The modified array of TinyMCE plugins.
     */
    public function add_tinymce_plugin($plugin_array) {
        $plugin_array['linkcentral'] = LINKCENTRAL_PLUGIN_URL . 'assets/js/tinymce-integration.js';
        return $plugin_array;
    }

    /**
     * Register the LinkCentral TinyMCE button.
     *
     * @param array $buttons An array of TinyMCE buttons.
     * @return array The modified array of TinyMCE buttons.
     */
    public function register_tinymce_button($buttons) {
        array_push($buttons, 'linkcentral');
        return $buttons;
    }

    /**
     * AJAX handler for searching links in TinyMCE.
     */
    public function ajax_tinymce_search_links() {
        check_ajax_referer('linkcentral_tinymce_nonce', 'nonce');

        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $admin = new LinkCentral_Admin();
        $links = $admin->get_links_for_search($search_term, 'publish');

        if (!empty($links)) {
            // Fetch global options
            $global_nofollow = get_option('linkcentral_global_nofollow', false);
            $global_sponsored = get_option('linkcentral_global_sponsored', false);
            $global_css_classes = get_option('linkcentral_custom_css_classes', '');

            // Add global options to each link
            foreach ($links as &$link) {
                $link['global_nofollow'] = $global_nofollow;
                $link['global_sponsored'] = $global_sponsored;
                $link['global_css_classes'] = $global_css_classes;
            }

            wp_send_json_success($links);
        } else {
            wp_send_json_error(__('No links found', 'linkcentral'));
        }
    }

    /**
     * AJAX handler for getting link data.
     */
    public function ajax_get_link_data() {
        check_ajax_referer('linkcentral_tinymce_nonce', 'nonce');

        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
        $link = get_post($link_id);
    
        if ($link && $link->post_type === 'linkcentral_link' && $link->post_status === 'publish') {
            $nofollow = get_post_meta($link_id, '_linkcentral_nofollow', true);
            $sponsored = get_post_meta($link_id, '_linkcentral_sponsored', true);
            $css_classes_option = get_post_meta($link_id, '_linkcentral_css_classes_option', true);
            $custom_css_classes = get_post_meta($link_id, '_linkcentral_custom_css_classes', true);
    
            // Fetch global options
            $global_nofollow = get_option('linkcentral_global_nofollow', false);
            $global_sponsored = get_option('linkcentral_global_sponsored', false);
            $global_css_classes = get_option('linkcentral_custom_css_classes', '');
    
            wp_send_json_success(array(
                'id' => $link->ID,
                'title' => $link->post_title,
                'slug' => $link->post_name,
                'nofollow' => $nofollow,
                'sponsored' => $sponsored,
                'css_classes_option' => $css_classes_option,
                'custom_css_classes' => $custom_css_classes,
                'global_nofollow' => $global_nofollow,
                'global_sponsored' => $global_sponsored,
                'global_css_classes' => $global_css_classes,
            ));
        } else {
            wp_send_json_error(__('Link not found or inactive', 'linkcentral'));
        }
    }

    /**
     * Check if the user has correct edit capabilities.
     *
     */
    private function can_edit_on_page() {
        return current_user_can('edit_posts') && get_user_option('rich_editing');
    }

    /**
     * Enqueue custom script for Elementor preview
     */
    public function enqueue_elementor_preview_script() {
        wp_enqueue_script('elementor-preview-custom', LINKCENTRAL_PLUGIN_URL . 'assets/js/elementor-integration.js', array(), LINKCENTRAL_VERSION, true);
        wp_localize_script('elementor-preview-custom', 'linkcentral_data', array(
            'plugin_url' => LINKCENTRAL_PLUGIN_URL
        ));
    }

    /**
     * Generate and get a random number identifier.
     */
    private static $random_identifier;

    public static function get_random_identifier() {
        if (null === self::$random_identifier) {
            self::$random_identifier = wp_rand();
        }
        return self::$random_identifier;
    }
}
