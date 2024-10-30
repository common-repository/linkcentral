<?php

/**
 * LinkCentral Settings Class
 *
 * This class handles the settings functionality,
 * including registering settings, rendering the settings page, and handling
 * settings updates.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class LinkCentral_Settings {
    /**
     * Initialize the class and set up WordPress hooks.
     */
    public function init() {
        // Register actions for settings and AJAX
        add_action( 'admin_init', array($this, 'register_settings') );
        // Register the new setting
        register_setting( 'linkcentral_settings', 'linkcentral_enable_ga', 'intval' );
    }

    /**
     * Register all settings for LinkCentral.
     */
    public function register_settings() {
        // Register various settings for LinkCentral
        register_setting( 'linkcentral_settings', 'linkcentral_url_prefix', array(
            'sanitize_callback' => 'sanitize_title',
        ) );
        register_setting( 'linkcentral_settings', 'linkcentral_excluded_ips' );
        register_setting( 'linkcentral_settings', 'linkcentral_excluded_roles' );
        register_setting( 'linkcentral_settings', 'linkcentral_disable_reporting', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_enable_data_expiry', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_data_expiry_days', 'intval' );
        register_setting( 'linkcentral_settings', 'linkcentral_global_nofollow', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_global_sponsored', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_global_redirection_type', 'sanitize_text_field' );
        register_setting( 'linkcentral_settings', 'linkcentral_exclude_bots', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_track_user_agent', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_ga_measurement_id', 'sanitize_text_field' );
        register_setting( 'linkcentral_settings', 'linkcentral_ga_api_secret', 'sanitize_text_field' );
        register_setting( 'linkcentral_settings', 'linkcentral_delete_tracking_data_on_link_deletion', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_track_ip', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_track_unique_visitors', 'boolval' );
        register_setting( 'linkcentral_settings', 'linkcentral_default_link_insertion_type', 'sanitize_text_field' );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        // Check if settings are being saved
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'linkcentral_save_settings', 'linkcentral_settings_nonce' ) ) {
            $this->save_settings();
        }
        // Get current settings
        $url_prefix = get_option( 'linkcentral_url_prefix', 'go' );
        $excluded_ips = get_option( 'linkcentral_excluded_ips', '' );
        $excluded_roles = get_option( 'linkcentral_excluded_roles', array() );
        $disable_reporting = get_option( 'linkcentral_disable_reporting', false );
        $enable_data_expiry = get_option( 'linkcentral_enable_data_expiry', false );
        $data_expiry_days = get_option( 'linkcentral_data_expiry_days', 90 );
        $global_nofollow = get_option( 'linkcentral_global_nofollow', false );
        $global_sponsored = get_option( 'linkcentral_global_sponsored', false );
        $global_redirection_type = get_option( 'linkcentral_global_redirection_type', '307' );
        $exclude_bots = get_option( 'linkcentral_exclude_bots', false );
        $track_ip = get_option( 'linkcentral_track_ip', true );
        $track_user_agent = get_option( 'linkcentral_track_user_agent', true );
        $global_parameter_forwarding = get_option( 'linkcentral_global_parameter_forwarding', false );
        $delete_tracking_data_on_link_deletion = get_option( 'linkcentral_delete_tracking_data_on_link_deletion', true );
        $custom_css_classes = get_option( 'linkcentral_custom_css_classes', '' );
        // Get the active tab from the form submission or default to 'general'
        $active_tab = ( isset( $_POST['active_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['active_tab'] ) ) : 'general' );
        // Include the settings page template
        include LINKCENTRAL_PLUGIN_DIR . 'views/settings-page.php';
    }

    /**
     * Save the settings from the settings page form submission.
     */
    private function save_settings() {
        // Add nonce verification at the beginning of the method
        if ( !isset( $_POST['linkcentral_settings_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['linkcentral_settings_nonce'] ) ), 'linkcentral_save_settings' ) ) {
            wp_die( 'Security check failed. Please try again.' );
        }
        // Handle URL prefix changes
        $old_prefix = get_option( 'linkcentral_url_prefix', 'go' );
        $new_prefix = ( isset( $_POST['linkcentral_url_prefix'] ) ? sanitize_title( wp_unslash( $_POST['linkcentral_url_prefix'] ) ) : $old_prefix );
        if ( $old_prefix !== $new_prefix ) {
            update_option( 'linkcentral_url_prefix', $new_prefix );
            flush_rewrite_rules();
            delete_option( 'linkcentral_rewrite_rules_flushed' );
        }
        // Update various boolean settings
        update_option( 'linkcentral_enable_data_expiry', isset( $_POST['linkcentral_enable_data_expiry'] ) );
        // Only update the expiry days if data expiry is enabled and the value is set
        if ( isset( $_POST['linkcentral_enable_data_expiry'] ) && isset( $_POST['linkcentral_data_expiry_days'] ) ) {
            update_option( 'linkcentral_data_expiry_days', intval( $_POST['linkcentral_data_expiry_days'] ) );
        }
        update_option( 'linkcentral_global_nofollow', isset( $_POST['linkcentral_global_nofollow'] ) );
        update_option( 'linkcentral_global_sponsored', isset( $_POST['linkcentral_global_sponsored'] ) );
        // Handle the redirection type
        $redirection_type = ( isset( $_POST['linkcentral_global_redirection_type'] ) ? sanitize_text_field( wp_unslash( $_POST['linkcentral_global_redirection_type'] ) ) : '307' );
        $allowed_types = array('307', '302', '301');
        $redirection_type = ( in_array( $redirection_type, $allowed_types ) ? $redirection_type : '307' );
        update_option( 'linkcentral_global_redirection_type', $redirection_type );
        update_option( 'linkcentral_disable_reporting', isset( $_POST['linkcentral_disable_reporting'] ) );
        // Update tracking settings only if Disable Reporting is not checked
        if ( !isset( $_POST['linkcentral_disable_reporting'] ) ) {
            update_option( 'linkcentral_track_ip', isset( $_POST['linkcentral_track_ip'] ) );
            update_option( 'linkcentral_track_user_agent', isset( $_POST['linkcentral_track_user_agent'] ) );
            update_option( 'linkcentral_track_unique_visitors', isset( $_POST['linkcentral_track_unique_visitors'] ) );
            // Update excluded IPs
            $excluded_ips = ( isset( $_POST['linkcentral_excluded_ips'] ) ? sanitize_textarea_field( wp_unslash( $_POST['linkcentral_excluded_ips'] ) ) : '' );
            update_option( 'linkcentral_excluded_ips', $excluded_ips );
            // Update excluded roles
            $excluded_roles = ( isset( $_POST['linkcentral_excluded_roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['linkcentral_excluded_roles'] ) ) : array() );
            update_option( 'linkcentral_excluded_roles', $excluded_roles );
            update_option( 'linkcentral_exclude_bots', isset( $_POST['linkcentral_exclude_bots'] ) );
            // GA4 settings validation
            $enable_ga = isset( $_POST['linkcentral_enable_ga'] );
            update_option( 'linkcentral_enable_ga', $enable_ga );
            $measurement_id = ( isset( $_POST['linkcentral_ga_measurement_id'] ) ? sanitize_text_field( wp_unslash( $_POST['linkcentral_ga_measurement_id'] ) ) : '' );
            $api_secret = ( isset( $_POST['linkcentral_ga_api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['linkcentral_ga_api_secret'] ) ) : '' );
            if ( $enable_ga ) {
                if ( empty( $measurement_id ) || empty( $api_secret ) ) {
                    add_settings_error(
                        'linkcentral_messages',
                        'linkcentral_ga4_error',
                        __( 'Error: Google Analytics 4 Measurement ID and API Secret are required when Google Analytics integration is enabled.', 'linkcentral' ),
                        'error'
                    );
                } else {
                    update_option( 'linkcentral_ga_measurement_id', $measurement_id );
                    update_option( 'linkcentral_ga_api_secret', $api_secret );
                }
            }
        }
        update_option( 'linkcentral_delete_tracking_data_on_link_deletion', isset( $_POST['linkcentral_delete_tracking_data_on_link_deletion'] ) );
        // Schedule or unschedule the cleanup event based on the setting
        if ( isset( $_POST['linkcentral_enable_data_expiry'] ) ) {
            LinkCentral_Cleanup::schedule_cleanup();
        } else {
            LinkCentral_Cleanup::deactivate_cleanup();
        }
        // Save the default link insertion type
        $default_link_insertion_type = ( isset( $_POST['linkcentral_default_link_insertion_type'] ) ? sanitize_text_field( wp_unslash( $_POST['linkcentral_default_link_insertion_type'] ) ) : 'synchronized' );
        update_option( 'linkcentral_default_link_insertion_type', $default_link_insertion_type );
        // Only add the success message if there are no errors
        if ( !get_settings_errors( 'linkcentral_messages' ) ) {
            add_settings_error(
                'linkcentral_messages',
                'linkcentral_message',
                __( 'Settings saved successfully.', 'linkcentral' ),
                'updated'
            );
        }
    }

    /**
     * Get an array of preset URL prefixes.
     *
     * @return array An array of preset URL prefixes.
     */
    public function get_preset_prefixes() {
        // Return an array of preset URL prefixes
        return [
            'go',
            'refer',
            'link',
            'goto',
            'click',
            'proceed',
            'recommend',
            'ext'
        ];
    }

}
