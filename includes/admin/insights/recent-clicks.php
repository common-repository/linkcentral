<?php
/**
 * LinkCentral Recent Clicks Class
 *
 * This class handles the functionality for retrieving and displaying
 * the most recent clicks on links.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_Recent_Clicks {
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
        // Register AJAX action for getting recent clicks
        add_action('wp_ajax_linkcentral_get_recent_clicks', array($this, 'ajax_get_recent_clicks'));
    }

    /**
     * AJAX handler for retrieving recent clicks.
     */
    public function ajax_get_recent_clicks() {
        // Verify nonce for security
        check_ajax_referer('linkcentral_admin_nonce', 'nonce');

        // Get the requested page number, default to 1 if not set
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Fetch recent clicks data
        $data = $this->get_recent_clicks_data($page);

        // Send the data as JSON response
        wp_send_json_success($data);
    }

    /**
     * Retrieve recent clicks data based on the given parameters.
     *
     * @param int $page     The current page number.
     * @param int $per_page The number of items per page.
     * @return array An array containing the recent clicks data and pagination information.
     */
    public function get_recent_clicks_data($page = 1, $per_page = 10) {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        // Get the click data from the database
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS s.*, s.user_agent, s.referring_url, s.destination_url
            FROM {$wpdb->prefix}linkcentral_stats s
            ORDER BY s.click_date DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        // Get total number of items for pagination
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");
        $total_pages = max(1, ceil($total_items / $per_page));

        // Get the link IDs from the results
        $link_ids = wp_list_pluck($results, 'link_id');

        // Fetch corresponding posts for the link IDs
        $query = new WP_Query(array(
            'post_type' => 'linkcentral_link',
            'post_status' => array('publish', 'draft', 'trash'),
            'posts_per_page' => -1,
            'post__in' => $link_ids,
            'orderby' => 'post__in',
        ));

        $posts = $query->posts;

        // Create a lookup array for quick access to post data
        $post_lookup = array();
        foreach ($posts as $post) {
            $post_lookup[$post->ID] = $post;
        }
        
        // Format the results
        $formatted_results = array_map(function($result) use ($post_lookup) {
            $click_date = new DateTime($result->click_date);
            $now = new DateTime();
            $yesterday = new DateTime('yesterday');

            // Format the date for display
            if ($click_date->format('Y-m-d') === $now->format('Y-m-d')) {
                $result->formatted_date = __('Today at', 'linkcentral') . ' ' . $click_date->format(get_option('time_format'));
            } elseif ($click_date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                $result->formatted_date = __('Yesterday at', 'linkcentral') . ' ' . $click_date->format(get_option('time_format'));
            } else {
                $result->formatted_date = $click_date->format(get_option('date_format') . ' ' . get_option('time_format'));
            }

            // Get browser information from user agent
            $result->browser = $this->get_browser_from_user_agent($result->user_agent);

            // Get device type, icon, and OS from user agent
            $device_and_os_info = $this->get_device_and_os_from_user_agent($result->user_agent);
            $result->device = $device_and_os_info['type'];
            $result->device_icon = $device_and_os_info['icon'];
            $result->os = $device_and_os_info['os'];

            // Combine browser and device information
            $result->user_agent_info = array(
                'browser' => $result->browser,
                'device' => $result->device,
                'device_icon' => $result->device_icon,
                'os' => $result->os
            );
            
            // Add post information if available
            if (isset($post_lookup[$result->link_id])) {
                $post = $post_lookup[$result->link_id];
                $result->post_title = $post->post_title;
                $result->post_status = $post->post_status;
                $result->slug = $post->post_name;
                $result->is_trashed = ($post->post_status === 'trash');
                $result->is_deleted = false;
                $result->is_draft = ($post->post_status === 'draft');
                $result->edit_link = get_edit_post_link($post->ID);
            } else {
                // Handle deleted posts
                $result->post_title = null;
                $result->post_status = 'deleted';
                $result->slug = '';
                $result->is_trashed = false;
                $result->is_deleted = true;
                $result->is_draft = false;
                $result->edit_link = '';
            }
            return $result;
        }, $results);

        return array(
            'clicks' => $formatted_results,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'total_items' => $total_items,
            'items_per_page' => $per_page
        );
    }

    /**
     * Determine the browser from the user agent string.
     *
     * @param string $user_agent The user agent string.
     * @return string The identified browser name or 'Other' if not recognized.
     */
    private function get_browser_from_user_agent($user_agent) {
        if (empty($user_agent)) {
            return '-';
        }
        if (strpos($user_agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident/') !== false) {
            return 'Internet Explorer';
        } else {
            return 'Other';
        }
    }

    /**
     * Determine the device type, corresponding dashicon, and operating system from the user agent string.
     *
     * @param string $user_agent The user agent string.
     * @return array An array containing the device type, corresponding dashicon class, and operating system.
     */
    private function get_device_and_os_from_user_agent($user_agent) {
        $user_agent = strtolower($user_agent);
        $info = array(
            'type' => 'Desktop',
            'icon' => 'dashicons-desktop',
            'os' => 'Other'
        );

        // Determine device type and icon
        if (strpos($user_agent, 'mobile') !== false
            || strpos($user_agent, 'android') !== false
            || strpos($user_agent, 'iphone') !== false
            || strpos($user_agent, 'ipod') !== false
            || strpos($user_agent, 'silk') !== false
            || strpos($user_agent, 'blackberry') !== false
            || strpos($user_agent, 'opera mini') !== false) {
            $info['type'] = 'Mobile';
            $info['icon'] = 'dashicons-smartphone';
        } elseif (strpos($user_agent, 'tablet') !== false
            || strpos($user_agent, 'ipad') !== false
            || strpos($user_agent, 'kindle') !== false) {
            $info['type'] = 'Tablet';
            $info['icon'] = 'dashicons-tablet';
        }

        // Determine OS
        if (strpos($user_agent, 'win') !== false) {
            $info['os'] = 'Windows';
        } elseif (strpos($user_agent, 'mac') !== false) {
            $info['os'] = 'macOS';
        } elseif (strpos($user_agent, 'linux') !== false) {
            $info['os'] = 'Linux';
        } elseif (strpos($user_agent, 'android') !== false) {
            $info['os'] = 'Android';
        } elseif (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false) {
            $info['os'] = 'iOS';
        }

        return $info;
    }
}