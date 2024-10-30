<?php
/**
 * LinkCentral Most Popular Clicks Class
 *
 * This class handles the functionality for retrieving and displaying
 * the most popular (most clicked) links.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_Most_Popular_Clicks {
    /**
     * The main admin object.
     */
    private $admin;

    /**
     * Constructor.
     */
    public function __construct($admin) {
        $this->admin = $admin;
    }

    /**
     * Initialize the class and set up WordPress hooks.
     */
    public function init() {
        // Register AJAX action for getting top links
        add_action('wp_ajax_linkcentral_get_top_links', array($this, 'ajax_get_top_links'));
    }

    /**
     * AJAX handler for retrieving top links.
     */
    public function ajax_get_top_links() {
        // Verify nonce for security
        check_ajax_referer('linkcentral_admin_nonce', 'nonce');

        // Get parameters from the AJAX request
        $timeframe = isset($_POST['timeframe']) ? sanitize_text_field(wp_unslash($_POST['timeframe'])) : '7';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10; // Set the number of items per page

        // Get top links data
        $data = $this->get_top_links_data($timeframe, $page, $per_page);
        wp_send_json_success($data);
    }

    /**
     * Retrieve top links data based on the given parameters.
     *
     * @param string $timeframe The time period to consider for clicks.
     * @param int    $page      The current page number.
     * @param int    $per_page  The number of items per page.
     * @return array An array containing the top links data and pagination information.
     */
    public function get_top_links_data($timeframe, $page, $per_page) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'linkcentral_stats';

        // Query to get click counts and unique click counts for all links
        $query = "
            SELECT s.link_id, 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT s.visitor_id) as unique_clicks
            FROM {$wpdb->prefix}linkcentral_stats s
            WHERE 1=1";

        $query_params = array();

        if ($timeframe !== 'all') {
            if ($timeframe == '1') {
                $query .= " AND s.click_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            } else {
                $query .= " AND s.click_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)";
                $query_params[] = intval($timeframe);
            }
        }

        $query .= " GROUP BY s.link_id ORDER BY total_clicks DESC";

        $click_counts = $wpdb->get_results(
            $wpdb->prepare($query, $query_params), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            OBJECT_K
        );

        // Step 2: Fetch corresponding posts
        $paged_link_ids = array_slice(array_keys($click_counts), ($page - 1) * $per_page, $per_page);

        $args = array(
            'post_type' => 'linkcentral_link',
            'posts_per_page' => -1, // Get all posts
            'post_status' => array('publish', 'draft', 'trash'),
            'post__in' => $paged_link_ids,
            'orderby' => 'post__in', // Maintain the order from click counts
        );

        $query = new WP_Query($args);

        // Process the results
        $processed_results = array();
        foreach ($paged_link_ids as $link_id) {
            $post = $this->find_post_by_id($query->posts, $link_id);
            if ($post) {
                $dynamic_rules = get_post_meta($post->ID, '_linkcentral_dynamic_rules', true);
                $is_premium = linkcentral_fs()->can_use_premium_code__premium_only();

                $processed_results[] = (object) array(
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_status' => $post->post_status,
                    'slug' => $post->post_name,
                    'total_clicks' => isset($click_counts[$post->ID]) ? $click_counts[$post->ID]->total_clicks : 0,
                    'unique_clicks' => isset($click_counts[$post->ID]) ? $click_counts[$post->ID]->unique_clicks : 0,
                    'destination_url' => get_post_meta($post->ID, '_linkcentral_destination_url', true),
                    'is_deleted' => false,
                    'is_trashed' => ($post->post_status === 'trash'),
                    'is_draft' => ($post->post_status === 'draft'),
                    'edit_link' => get_edit_post_link($post->ID),
                    'has_dynamic_rules' => $is_premium && !empty($dynamic_rules)
                );
            } else {
                // Handle deleted posts
                $processed_results[] = (object) array(
                    'ID' => $link_id,
                    'post_title' => 'Deleted Link',
                    'post_status' => 'deleted',
                    'slug' => '',
                    'total_clicks' => isset($click_counts[$link_id]) ? $click_counts[$link_id]->total_clicks : 0,
                    'unique_clicks' => isset($click_counts[$link_id]) ? $click_counts[$link_id]->unique_clicks : 0,
                    'destination_url' => '',
                    'is_deleted' => true,
                    'is_trashed' => false,
                    'is_draft' => false,
                    'edit_link' => ''
                );
            }
        }

        // Calculate pagination info
        $total_items = count($click_counts);
        $total_pages = ceil($total_items / $per_page);

        return array(
            'links' => $processed_results,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'items_per_page' => $per_page
        );
    }

    /**
     * Find a post by ID in an array of posts.
     *
     * @param array $posts An array of WP_Post objects.
     * @param int   $id    The ID of the post to find.
     * @return WP_Post|null The found post object or null if not found.
     */
    private function find_post_by_id($posts, $id) {
        foreach ($posts as $post) {
            if ($post->ID == $id) {
                return $post;
            }
        }
        return null;
    }
}
