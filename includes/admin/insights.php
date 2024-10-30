<?php

/**
 * LinkCentral Insights Class
 *
 * This class manages the Insights functionality of the plugin,
 * including total clicks, most popular clicks, and recent clicks.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/insights/total-clicks.php';
require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/insights/most-popular-clicks.php';
require_once LINKCENTRAL_PLUGIN_DIR . 'includes/admin/insights/recent-clicks.php';

class LinkCentral_Insights {
    /**
     * The main admin object.
     */
    private $admin;

    /**
     * The total clicks object.
     */
    private $total_clicks;

    /**
     * The most popular clicks object.
     */
    private $most_popular_clicks;

    /**
     * The recent clicks object.
     */
    private $recent_clicks;

    /**
     * Constructor.
     *
     * @param LinkCentral_Admin $admin The main admin object.
     */
    public function __construct($admin) {
        $this->admin = $admin;
        $this->total_clicks = new LinkCentral_Total_Clicks($admin);
        $this->most_popular_clicks = new LinkCentral_Most_Popular_Clicks($admin);
        $this->recent_clicks = new LinkCentral_Recent_Clicks($admin);
    }

    /**
     * Initialize the class and set up WordPress hooks.
     */
    public function init() {
        $this->total_clicks->init();
        $this->most_popular_clicks->init();
        $this->recent_clicks->init();

        // Add AJAX action for link search in Insights
        add_action('wp_ajax_linkcentral_insights_search_links', array($this, 'ajax_insights_search_links'));

        // Enqueue scripts for insights page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_insights_scripts'));
    }

    /**
     * Enqueue scripts for the insights page.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_insights_scripts($hook) {
        if (strpos($hook, 'linkcentral-insights') !== false) {
            // Enqueue main chunk
            wp_enqueue_script('linkcentral-admin-insights', LINKCENTRAL_PLUGIN_URL . 'assets/js/admin-insights.js', array('jquery'), LINKCENTRAL_VERSION, true);

            // Enqueue vendor chunks
            $vendor_chunks = glob(LINKCENTRAL_PLUGIN_DIR . 'assets/js/npm.*.js');
            foreach ($vendor_chunks as $chunk) {
                $chunk_name = basename($chunk, '.js');
                wp_enqueue_script("linkcentral-{$chunk_name}", LINKCENTRAL_PLUGIN_URL . "assets/js/{$chunk_name}.js", array(), LINKCENTRAL_VERSION, true);
            }

            // Add localized script data
            $track_unique_visitors = get_option('linkcentral_track_unique_visitors', false);
            
            wp_localize_script('linkcentral-admin-insights', 'linkcentral_insights_data', array(
                'can_use_premium_code__premium_only' => linkcentral_fs()->can_use_premium_code__premium_only(),
                'track_unique_visitors' => $track_unique_visitors ? '1' : '0'
            ));
        }
    }

    /**
     * Render the insights page.
     */
    public function render_insights_page() {
        // Get initial data for Recent Clicks
        $initial_recent_clicks_data = $this->recent_clicks->get_recent_clicks_data(1);
        $initial_top_links_data = $this->most_popular_clicks->get_top_links_data('7', 1, 10);
        
        // Check if user agent tracking is enabled
        $track_user_agent = get_option('linkcentral_track_user_agent', true);
        
        // Check if unique visitors tracking is enabled
        $track_unique_visitors = get_option('linkcentral_track_unique_visitors', false);
        
        // Pass this data to the template
        include LINKCENTRAL_PLUGIN_DIR . 'views/insights-page.php';
    }

    /**
     * AJAX handler for searching links in the Insights page.
     */
    public function ajax_insights_search_links() {
        check_ajax_referer('linkcentral_admin_nonce', 'nonce');

        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $links = $this->admin->get_links_for_search($search_term, 'publish');
        wp_send_json_success($links);
    }
}