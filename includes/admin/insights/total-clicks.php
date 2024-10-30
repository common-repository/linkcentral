<?php
/**
 * LinkCentral Total Clicks Class
 *
 * This class handles the functionality for retrieving and displaying
 * total click statistics for all links and specific links in the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_Total_Clicks {
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
        // Register AJAX actions for getting stats and specific link stats
        add_action('wp_ajax_linkcentral_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_linkcentral_get_specific_link_stats', array($this, 'ajax_get_specific_link_stats'));
    }

    //===========================================================================
    // ALL LINKS STATISTICS
    //===========================================================================

    /**
     * AJAX handler for retrieving statistics for all links.
     */
    public function ajax_get_stats() {
        // Verify nonce for security
        check_ajax_referer('linkcentral_admin_nonce', 'nonce');

        // Get parameters from the AJAX request
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : null;

        // Ensure dates are in the correct format
        if ($start_date) {
            $start_date = gmdate('Y-m-d', strtotime($start_date));
        }
        if ($end_date) {
            $end_date = gmdate('Y-m-d', strtotime($end_date));
        }

        // Get stats data
        $data = $this->get_stats_data($days, $start_date, $end_date);
        wp_send_json_success($data);
    }

    /**
     * Retrieve statistics data for all links.
     *
     * @param int    $days       The number of days to retrieve data for.
     * @param string $start_date The start date for custom date range (format: Y-m-d).
     * @param string $end_date   The end date for custom date range (format: Y-m-d).
     * @return array An array containing labels and click data for the chart.
     */
    public function get_stats_data($days, $start_date, $end_date) {
        global $wpdb;

        $data = array(
            'labels' => array(),
            'clicks' => array(),
            'unique_clicks' => array()
        );

        // Determine the date range
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $end->modify('+1 day'); // Include the end date
        } else {
            $end = new DateTime('tomorrow'); // Include today
            $start = clone $end;
            $start->modify("-{$days} days");
        }

        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);

        $track_unique_visitors = get_option('linkcentral_track_unique_visitors', false);

        // Get click data from the database
        $query = "SELECT DATE(click_date) as date, COUNT(*) as clicks";
        if ($track_unique_visitors) {
            $query .= ", COUNT(DISTINCT visitor_id) as unique_clicks";
        }
        $query .= " FROM {$wpdb->prefix}linkcentral_stats
                    WHERE click_date >= %s AND click_date < %s
                    GROUP BY DATE(click_date)";

        $results = $wpdb->get_results($wpdb->prepare($query, $start->format('Y-m-d'), $end->format('Y-m-d'))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $click_data = array();
        $unique_click_data = array();
        foreach ($results as $row) {
            $click_data[$row->date] = (int)$row->clicks;
            if ($track_unique_visitors) {
                $unique_click_data[$row->date] = (int)$row->unique_clicks;
            }
        }

        $date_format = get_option('date_format');

        // Prepare data for the chart
        foreach ($date_range as $date) {
            $date_string = $date->format('Y-m-d');
            $iso_date = $date->format('c'); // ISO 8601 format
            $data['labels'][] = $iso_date;
            $data['clicks'][] = isset($click_data[$date_string]) ? $click_data[$date_string] : 0;
            $data['unique_clicks'][] = isset($unique_click_data[$date_string]) ? $unique_click_data[$date_string] : 0;
        }

        return $data;
    }

    //===========================================================================
    // SPECIFIC LINK STATISTICS
    //===========================================================================

    /**
     * AJAX handler for retrieving statistics for a specific link.
     */
    public function ajax_get_specific_link_stats() {
        // Verify nonce for security
        check_ajax_referer('linkcentral_admin_nonce', 'nonce');

        // Get parameters from the AJAX request
        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : null;

        // Ensure dates are in the correct format
        if ($start_date) {
            $start_date = gmdate('Y-m-d', strtotime($start_date));
        }
        if ($end_date) {
            $end_date = gmdate('Y-m-d', strtotime($end_date));
        }

        // Get specific link stats data
        $data = $this->get_specific_link_stats_data($link_id, $days, $start_date, $end_date);
        wp_send_json_success($data);
    }

    /**
     * Retrieve statistics data for a specific link.
     *
     * @param int    $link_id    The ID of the specific link.
     * @param int    $days       The number of days to retrieve data for.
     * @param string $start_date The start date for custom date range (format: Y-m-d).
     * @param string $end_date   The end date for custom date range (format: Y-m-d).
     * @return array An array containing labels and click data for the chart.
     */
    private function get_specific_link_stats_data($link_id, $days, $start_date, $end_date) {
        global $wpdb;

        $data = array(
            'labels' => array(),
            'clicks' => array(),
            'unique_clicks' => array()
        );

        // Determine the date range
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $end->modify('+1 day'); // Include the end date
        } else {
            $end = new DateTime('tomorrow'); // Include today
            $start = clone $end;
            $start->modify("-{$days} days");
        }

        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);

        $track_unique_visitors = get_option('linkcentral_track_unique_visitors', false);

        // Get click data for the specific link from the database
        $query = "SELECT DATE(click_date) as date, COUNT(*) as clicks";
        if ($track_unique_visitors) {
            $query .= ", COUNT(DISTINCT visitor_id) as unique_clicks";
        }
        $query .= " FROM {$wpdb->prefix}linkcentral_stats
                    WHERE link_id = %d AND click_date >= %s AND click_date < %s
                    GROUP BY DATE(click_date)";

        $results = $wpdb->get_results($wpdb->prepare($query, $link_id, $start->format('Y-m-d'), $end->format('Y-m-d'))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $click_data = array();
        $unique_click_data = array();
        foreach ($results as $row) {
            $click_data[$row->date] = (int)$row->clicks;
            if ($track_unique_visitors) {
                $unique_click_data[$row->date] = (int)$row->unique_clicks;
            }
        }

        $date_format = get_option('date_format');

        // Prepare data for the chart
        foreach ($date_range as $date) {
            $date_string = $date->format('Y-m-d');
            $iso_date = $date->format('c'); // ISO 8601 format
            $data['labels'][] = $iso_date;
            $data['clicks'][] = isset($click_data[$date_string]) ? $click_data[$date_string] : 0;
            $data['unique_clicks'][] = isset($unique_click_data[$date_string]) ? $unique_click_data[$date_string] : 0;
        }

        return $data;
    }
}