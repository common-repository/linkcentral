<?php

/**
 * LinkCentral Activator Class
 *
 * This class handles the activation and deactivation logic for the plugin.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Activator {
    /**
     * Activation logic.
     */
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'linkcentral_stats';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (\n            id mediumint(9) NOT NULL AUTO_INCREMENT,\n            link_id mediumint(9) NOT NULL,\n            click_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,\n            ip_address varchar(45) NOT NULL,\n            referring_url TEXT,\n            user_agent text NOT NULL,\n            visitor_id varchar(36),\n            destination_url TEXT,\n            PRIMARY KEY  (id)\n        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        // Set default options if they don't exist.
        $default_options = array(
            'linkcentral_url_prefix'                            => 'go',
            'linkcentral_excluded_ips'                          => '',
            'linkcentral_excluded_roles'                        => array(),
            'linkcentral_disable_reporting'                     => false,
            'linkcentral_enable_data_expiry'                    => false,
            'linkcentral_data_expiry_days'                      => 90,
            'linkcentral_global_nofollow'                       => true,
            'linkcentral_global_sponsored'                      => false,
            'linkcentral_global_redirection_type'               => '307',
            'linkcentral_global_parameter_forwarding'           => false,
            'linkcentral_exclude_bots'                          => false,
            'linkcentral_track_user_agent'                      => true,
            'linkcentral_delete_tracking_data_on_link_deletion' => true,
            'linkcentral_enable_ga'                             => false,
            'linkcentral_ga_measurement_id'                     => '',
            'linkcentral_ga_api_secret'                         => '',
            'linkcentral_track_ip'                              => true,
            'linkcentral_track_unique_visitors'                 => true,
            'linkcentral_default_link_insertion_type'           => 'synchronized',
        );
        foreach ( $default_options as $option_name => $default_value ) {
            if ( get_option( $option_name ) === false ) {
                add_option( $option_name, $default_value );
            }
        }
        // Set the initial database version
        if ( get_option( 'linkcentral_db_version' ) === false ) {
            add_option( 'linkcentral_db_version', LINKCENTRAL_DB_VERSION );
        }
        // Schedule events based on current settings
        LinkCentral_Cleanup::schedule_cleanup();
        // Flush rewrite rules
        flush_rewrite_rules();
        delete_option( 'linkcentral_rewrite_rules_flushed' );
    }

    /**
     * Deactivation logic.
     */
    public static function deactivate() {
        // Flush rewrite rules on deactivation as well
        flush_rewrite_rules();
        // Clear the scheduled cleanup event
        LinkCentral_Cleanup::deactivate_cleanup();
        // Set the cleanup setting to false upon deactivation, in case plugin is re-activated later
        update_option( 'linkcentral_enable_data_expiry', false );
    }

}

/**
 * Activation hook callback.
 */
function linkcentral_activate() {
    LinkCentral_Activator::activate();
}

/**
 * Deactivation hook callback.
 */
function linkcentral_deactivate() {
    LinkCentral_Activator::deactivate();
}
