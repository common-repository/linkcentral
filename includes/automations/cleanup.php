<?php
/**
 * LinkCentral Cleanup Class
 *
 * This class handles the automated cleanup of old tracking data
 * based on the data expiry settings configured in the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LinkCentral_Cleanup {
    /**
     * Schedule the daily cleanup event.
     */
    public static function schedule_cleanup() {
        if (get_option('linkcentral_enable_data_expiry', false)) {
            if (!wp_next_scheduled('linkcentral_daily_cleanup')) {
                wp_schedule_event(time(), 'daily', 'linkcentral_daily_cleanup');
            }
            add_action('linkcentral_daily_cleanup', array(__CLASS__, 'cleanup_old_tracking_data'));
        } else {
            self::deactivate_cleanup();
        }
    }

    /**
     * Perform the cleanup of old tracking data.
     *
     * This method deletes tracking data older than the specified number of days
     * if the data expiry feature is enabled in the settings.
     */
    public static function cleanup_old_tracking_data() {
        // Check if data expiry is enabled
        if (!get_option('linkcentral_enable_data_expiry', false)) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'linkcentral_stats';
        $days = get_option('linkcentral_data_expiry_days', 90);

        // Delete old tracking data
        $deleted_rows = $wpdb->delete(
            $table_name,
            array(
                'click_date' => array(
                    'value'   => gmdate('Y-m-d H:i:s', strtotime("-$days days")),
                    'compare' => '<',
                ),
            ),
            array('%s')
        );
    }

    /**
     * Deactivate the cleanup functionality.
     */
    public static function deactivate_cleanup() {
        wp_clear_scheduled_hook('linkcentral_daily_cleanup');
    }
}