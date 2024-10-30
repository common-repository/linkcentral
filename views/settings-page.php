<?php

/**
 * Template for the LinkCentral Settings page in the WordPress admin.
 *
 * This template displays various configuration options for the LinkCentral plugin,
 * including general settings, tracking settings, usage instructions, and premium features.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
do_action( 'linkcentral_admin_header' );
?>

<div class="wrap linkcentral-wrapper linkcentral-settings">
    <h1><?php 
esc_html_e( 'Settings', 'linkcentral' );
?></h1>
    
    <?php 
settings_errors( 'linkcentral_messages' );
?>

    <?php 
/**
 * Navigation Tabs
 * 
 * Displays tabs for different sections of the settings page.
 */
?>
    <h2 class="nav-tab-wrapper">
        <a href="#general" class="nav-tab <?php 
echo ( $active_tab == 'general' ? 'nav-tab-active' : '' );
?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php 
esc_html_e( 'General', 'linkcentral' );
?>
        </a>
        <a href="#tracking" class="nav-tab <?php 
echo ( $active_tab == 'tracking' ? 'nav-tab-active' : '' );
?>">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php 
esc_html_e( 'Tracking', 'linkcentral' );
?>
        </a>
        <a href="#usage" class="nav-tab <?php 
echo ( $active_tab == 'usage' ? 'nav-tab-active' : '' );
?>">
            <span class="dashicons dashicons-book"></span>
            <?php 
esc_html_e( 'Usage', 'linkcentral' );
?>
        </a>
        <a href="#premium" class="nav-tab <?php 
echo ( $active_tab == 'premium' ? 'nav-tab-active' : '' );
?>">
            <span class="dashicons dashicons-star-filled"></span>
            <?php 
esc_html_e( 'Premium', 'linkcentral' );
?>
        </a>
    </h2>

    <form method="post" action="">
        <?php 
wp_nonce_field( 'linkcentral_save_settings', 'linkcentral_settings_nonce' );
?>
        <input type="hidden" name="active_tab" id="active_tab" value="<?php 
echo esc_attr( $active_tab );
?>">
        
        <?php 
/**
 * General Settings Section
 * 
 * Contains settings for URL prefix, global link attributes, and other general options.
 */
?>
        <div id="general" class="tab-content">
            <h3><?php 
esc_html_e( 'Link Settings', 'linkcentral' );
?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="linkcentral_url_prefix"><?php 
esc_html_e( 'Custom URL Prefix', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell"></td>
                    <td>
                        <select id="linkcentral_url_prefix_select">
                            <?php 
foreach ( $this->get_preset_prefixes() as $prefix ) {
    ?>
                                <option value="<?php 
    echo esc_attr( $prefix );
    ?>" <?php 
    selected( $url_prefix, $prefix );
    ?>><?php 
    echo esc_html( $prefix );
    ?></option>
                            <?php 
}
?>
                            <option value="custom" <?php 
selected( !in_array( $url_prefix, $this->get_preset_prefixes() ) );
?>><?php 
esc_attr_e( 'Custom:', 'linkcentral' );
?></option>
                        </select>
                        <input type="text" id="linkcentral_url_prefix" name="linkcentral_url_prefix" value="<?php 
echo esc_attr( $url_prefix );
?>" class="regular-text" <?php 
echo ( in_array( $url_prefix, $this->get_preset_prefixes() ) ? 'style="display:none;"' : '' );
?>>
                        <p class="description">
                            <?php 
echo sprintf( 
    // translators: %1$s is the main site URL, %2$s is the custom URL prefix
    esc_html__( 'Your custom URLs look like this: %1$s/%2$s/custom-link.', 'linkcentral' ),
    esc_url( get_site_url() ),
    '<span id="prefix-example">' . esc_html( $url_prefix ) . '</span>'
 );
?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <hr>
            <h3><?php 
esc_html_e( 'Global Link Attributes', 'linkcentral' );
?></h3>
            <p class="description"><?php 
esc_html_e( 'The following settings are globally applied to links. They can be overridden for individual links.', 'linkcentral' );
?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="linkcentral_global_nofollow"><?php 
esc_html_e( 'Global No-Follow Attribute', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When enabled, all links will have the rel="nofollow" attribute, telling search engines not to follow these links. You can override this for individual links.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_global_nofollow" id="linkcentral_global_nofollow" value="1" <?php 
checked( $global_nofollow, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Add no-follow attribute to all links', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_global_sponsored"><?php 
esc_html_e( 'Global Sponsored Attribute', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When enabled, all links will have the rel="sponsored" attribute, indicating that they are paid or sponsored links. You can override this for individual links.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_global_sponsored" id="linkcentral_global_sponsored" value="1" <?php 
checked( $global_sponsored, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Add sponsored attribute to all links', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_global_redirection_type"><?php 
esc_html_e( 'Global Redirection Type', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Choose the default redirection type for all links. This can be overridden for individual links.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <select name="linkcentral_global_redirection_type" id="linkcentral_global_redirection_type">
                            <option value="307" <?php 
selected( $global_redirection_type, '307' );
?>><?php 
esc_html_e( '307 (Temporary)', 'linkcentral' );
?></option>
                            <option value="302" <?php 
selected( $global_redirection_type, '302' );
?>><?php 
esc_html_e( '302 (Temporary)', 'linkcentral' );
?></option>
                            <option value="301" <?php 
selected( $global_redirection_type, '301' );
?>><?php 
esc_html_e( '301 (Permanent)', 'linkcentral' );
?></option>
                        </select>
                    </td>
                </tr>
                <tr class="linkcentral-premium-feature <?php 
echo ( linkcentral_fs()->can_use_premium_code__premium_only() ? 'premium-active' : '' );
?>">
                    <th scope="row">
                        <label for="linkcentral_global_parameter_forwarding"><?php 
esc_html_e( 'Global Parameter Forwarding', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When enabled, query parameters from the custom URL will be appended to the destination URL. This can be overridden for individual links.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_global_parameter_forwarding" id="linkcentral_global_parameter_forwarding" value="1" <?php 
checked( linkcentral_fs()->can_use_premium_code__premium_only() && $global_parameter_forwarding, 1 );
?> <?php 
echo ( linkcentral_fs()->is_free_plan() ? 'disabled' : '' );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label">
                            <?php 
esc_html_e( 'Enable parameter forwarding for all links', 'linkcentral' );
?>
                            <?php 
if ( linkcentral_fs()->is_free_plan() ) {
    ?>
                                <a href="#premium" class="linkcentral-premium-tag"><?php 
    esc_html_e( 'Premium', 'linkcentral' );
    ?></a>
                            <?php 
}
?>
                        </span>
                    </td>
                </tr>
                <tr class="linkcentral-premium-feature <?php 
echo ( linkcentral_fs()->can_use_premium_code__premium_only() ? 'premium-active' : '' );
?>">
                    <th scope="row">
                        <label for="linkcentral_custom_css_classes"><?php 
esc_html_e( 'Global CSS Classes', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Add custom CSS classes to be applied to all link insertions. Separate multiple classes with spaces. This can be overridden for individual links. Gutenberg Buttons are not supported.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <input type="text" name="linkcentral_custom_css_classes" id="linkcentral_custom_css_classes" value="<?php 
echo esc_attr( $custom_css_classes );
?>" class="regular-text" <?php 
echo ( linkcentral_fs()->is_free_plan() ? 'disabled' : '' );
?>>
                        <?php 
if ( linkcentral_fs()->is_free_plan() ) {
    ?>
                            <a href="#premium" class="linkcentral-premium-tag"><?php 
    esc_html_e( 'Premium', 'linkcentral' );
    ?></a>
                        <?php 
}
?>
                    </td>
                </tr>
            </table>

            <?php 
?>
        </div>

        <?php 
/**
 * Tracking Settings Section
 * 
 * Contains settings related to click tracking, IP exclusions, and Google Analytics integration.
 */
?>
        <div id="tracking" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="linkcentral_disable_reporting"><?php 
esc_html_e( 'Disable Reporting', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When checked, no click data will be collected or stored.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_disable_reporting" id="linkcentral_disable_reporting" value="1" <?php 
checked( $disable_reporting, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Disable click data reporting', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_track_ip"><?php 
esc_html_e( 'Track IP Address', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When enabled, the IP address of the user will be recorded with each click.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_track_ip" id="linkcentral_track_ip" value="1" <?php 
checked( $track_ip, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Enable IP address tracking for all clicks', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_track_user_agent"><?php 
esc_html_e( 'Track User Agent', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When checked, the user agent of the user will be recorded with each click. This information is used to display browser icons in the insights.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_track_user_agent" id="linkcentral_track_user_agent" value="1" <?php 
checked( $track_user_agent, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Enable User Agent tracking for all clicks', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_track_unique_visitors"><?php 
esc_html_e( 'Track Unique Visitors', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When enabled, a cookie will be set to track unique visitors for more accurate click statistics.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_track_unique_visitors" id="linkcentral_track_unique_visitors" value="1" <?php 
checked( get_option( 'linkcentral_track_unique_visitors', false ), 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Enable unique visitor tracking (requires cookies)', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_enable_ga"><?php 
esc_html_e( 'Enable Google Analytics', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Enable integration with Google Analytics 4.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_enable_ga" id="linkcentral_enable_ga" value="1" <?php 
checked( get_option( 'linkcentral_enable_ga' ), 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Enable Google Analytics 4 integration', 'linkcentral' );
?></span>
                        <a href="#" class="linkcentral-configure-link" data-target="ga4" data-toggle-rows="#linkcentral_ga_measurement_id, #linkcentral_ga_api_secret">
                            <?php 
esc_html_e( 'Configure', 'linkcentral' );
?>
                            <span class="chevron"></span>
                        </a>
                    </td>
                </tr>
                <tr class="linkcentral-configure-row first" data-parent="ga4">
                    <th scope="row">
                        <label for="linkcentral_ga_measurement_id"><?php 
esc_html_e( 'Google Analytics 4 Measurement ID', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Enter your Google Analytics 4 Measurement ID (starts with G-).', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <input type="text" id="linkcentral_ga_measurement_id" name="linkcentral_ga_measurement_id" value="<?php 
echo esc_attr( get_option( 'linkcentral_ga_measurement_id' ) );
?>" placeholder="G-XXXXXXXXXX" class="regular-text">
                        <a href="https://designforwp.com/docs/linkcentral/tracking/set-up-google-analytics-4/" title="More about Google Analytics 4 integration" target="_blank"><?php 
esc_html_e( 'See instructions', 'linkcentral' );
?></a>
                    </td>
                </tr>
                <tr class="linkcentral-configure-row last" data-parent="ga4">
                    <th scope="row">
                        <label for="linkcentral_ga_api_secret"><?php 
esc_html_e( 'Google Analytics 4 API Secret', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Enter your Google Analytics 4 API Secret.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <input type="text" id="linkcentral_ga_api_secret" name="linkcentral_ga_api_secret" value="<?php 
echo esc_attr( get_option( 'linkcentral_ga_api_secret' ) );
?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_excluded_ips"><?php 
esc_html_e( 'Excluded IP Addresses', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Enter IP addresses to exclude from tracking, one per line.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <textarea id="linkcentral_excluded_ips" name="linkcentral_excluded_ips" rows="5" cols="50"><?php 
echo esc_textarea( $excluded_ips );
?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_excluded_roles"><?php 
esc_html_e( 'Excluded User Roles', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Select user roles to exclude from tracking. If someone is signed in as a user with one of these roles, their clicks will not be recorded.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <?php 
$roles = wp_roles()->get_names();
foreach ( $roles as $role_slug => $role_name ) {
    $checked = ( in_array( $role_slug, $excluded_roles ) ? 'checked' : '' );
    echo '<label><input type="checkbox" name="linkcentral_excluded_roles[]" value="' . esc_attr( $role_slug ) . '" ' . esc_attr( $checked ) . '> ' . esc_html( $role_name ) . '</label><br>';
}
?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_exclude_bots"><?php 
esc_html_e( 'Exclude Bots', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'When checked, clicks from known bots will not be recorded.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_exclude_bots" id="linkcentral_exclude_bots" value="1" <?php 
checked( $exclude_bots, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Exclude bots from click tracking', 'linkcentral' );
?></span>
                    </td>
                </tr>
            </table>
            
            <hr>
            <h3><?php 
esc_html_e( 'Deletion', 'linkcentral' );
?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="linkcentral_delete_tracking_data_on_link_deletion"><?php 
esc_html_e( 'Delete Tracking Data on Link Deletion', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'If enabled, all associated click tracking data for a link will be permanently deleted when that link is deleted from the system.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_delete_tracking_data_on_link_deletion" id="linkcentral_delete_tracking_data_on_link_deletion" value="1" <?php 
checked( $delete_tracking_data_on_link_deletion, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Delete click tracking data when the corresponding link is permanently deleted', 'linkcentral' );
?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="linkcentral_enable_data_expiry"><?php 
esc_html_e( 'Enable Data Expiry', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'If enabled, tracking data older than the specified number of days will be automatically deleted.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <label class="linkcentral-toggle-switch">
                            <input type="checkbox" name="linkcentral_enable_data_expiry" id="linkcentral_enable_data_expiry" value="1" <?php 
checked( $enable_data_expiry, 1 );
?>>
                            <span class="linkcentral-toggle-slider"></span>
                        </label>
                        <span class="linkcentral-toggle-label"><?php 
esc_html_e( 'Enable automatic deletion of old tracking data', 'linkcentral' );
?>:</span>
                        <select name="linkcentral_data_expiry_days" id="linkcentral_data_expiry_days" <?php 
disabled( !$enable_data_expiry );
?>>
                            <option value="90" <?php 
selected( $data_expiry_days, 90 );
?>><?php 
esc_html_e( '90 days', 'linkcentral' );
?></option>
                            <option value="180" <?php 
selected( $data_expiry_days, 180 );
?>><?php 
esc_html_e( '180 days', 'linkcentral' );
?></option>
                            <option value="365" <?php 
selected( $data_expiry_days, 365 );
?>><?php 
esc_html_e( '365 days', 'linkcentral' );
?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <?php 
/**
 * Usage Instructions Section
 * 
 * Provides information on how to use LinkCentral shortcodes and integrate with various editors.
 */
?>
        <div id="usage" class="tab-content" style="display:none;">
            <h3><?php 
esc_html_e( 'How to Use LinkCentral', 'linkcentral' );
?></h3>
            <p><?php 
esc_html_e( 'LinkCentral offers multiple ways to integrate short links into your content. While you can manually insert short links, using our integrations ensures automatic updates across your site when links change.', 'linkcentral' );
?></p>
            <p><?php 
esc_html_e( 'The following integrations are available, depending on what best suit your workflow:', 'linkcentral' );
?></p>
            
            <div class="linkcentral-accordion-container">
                <div class="linkcentral-accordion">
                    <h4 class="linkcentral-accordion-header"><?php 
esc_html_e( '1. Shortcodes', 'linkcentral' );
?></h4>
                    <div class="linkcentral-accordion-content">
                        <p><?php 
esc_html_e( 'Use shortcodes to insert LinkCentral links anywhere in your content:', 'linkcentral' );
?></p>
                        <code>[linkcentral id="123"]Your Link Text[/linkcentral]</code>
                        <p><strong><?php 
esc_html_e( 'Options:', 'linkcentral' );
?></strong></p>
                        <ul>
                            <li><?php 
esc_html_e( 'id="{id}" (required)', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'newtab="true"', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'parameters="p1=v1&p2=v2" (Premium only)', 'linkcentral' );
?></li>
                        </ul>
                    </div>
                </div>

                <div class="linkcentral-accordion">
                    <h4 class="linkcentral-accordion-header"><?php 
esc_html_e( '2. Gutenberg (Block Editor)', 'linkcentral' );
?></h4>
                    <div class="linkcentral-accordion-content">
                        <p><?php 
esc_html_e( 'In the Gutenberg editor, use the LinkCentral button in the toolbar:', 'linkcentral' );
?></p>
                        <ol>
                            <li><?php 
esc_html_e( 'Select text in a Paragraph block or Button block', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Click the LinkCentral icon in the toolbar', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Search for and select your link', 'linkcentral' );
?></li>
                        </ol>
                    </div>
                </div>

                <div class="linkcentral-accordion">
                    <h4 class="linkcentral-accordion-header"><?php 
esc_html_e( '3. Classic Editor (TinyMCE)', 'linkcentral' );
?></h4>
                    <div class="linkcentral-accordion-content">
                        <p><?php 
esc_html_e( 'In the Classic Editor, use the LinkCentral button in the toolbar:', 'linkcentral' );
?></p>
                        <ol>
                            <li><?php 
esc_html_e( 'Select text or place cursor where you want to insert the link', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Click the LinkCentral button in the editor toolbar', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Search for and select your link, then click "Insert"', 'linkcentral' );
?></li>
                        </ol>
                    </div>
                </div>

                <div class="linkcentral-accordion">
                    <h4 class="linkcentral-accordion-header"><?php 
esc_html_e( '4. Elementor', 'linkcentral' );
?></h4>
                    <div class="linkcentral-accordion-content">
                        <p><?php 
esc_html_e( 'Elementor uses the Classic Editor for content. See the steps above to use this integration.', 'linkcentral' );
?></p>
                        <p><?php 
esc_html_e( 'LinkCentral also integrates with Elementor Pro\'s dynamic tags:', 'linkcentral' );
?></p>
                        <ol>
                            <li><?php 
esc_html_e( 'Edit any element that supports URL input', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Click the dynamic tag icon next to the URL field', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Select "LinkCentral Link" from the list', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Choose your LinkCentral link from the dropdown', 'linkcentral' );
?></li>
                        </ol>
                    </div>
                </div>

                <div class="linkcentral-accordion">
                    <h4 class="linkcentral-accordion-header"><?php 
esc_html_e( '5. Beaver Builder', 'linkcentral' );
?></h4>
                    <div class="linkcentral-accordion-content">
                        <p><?php 
esc_html_e( 'Use LinkCentral with Beaver Builder\'s text editor:', 'linkcentral' );
?></p>
                        <ol>
                            <li><?php 
esc_html_e( 'Edit a text module', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'In the text editor, use the LinkCentral button (similar to Classic Editor)', 'linkcentral' );
?></li>
                            <li><?php 
esc_html_e( 'Search for and select your link, then click "Insert"', 'linkcentral' );
?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <hr>
            <h3><?php 
esc_html_e( 'Link Insertion', 'linkcentral' );
?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="linkcentral_default_link_insertion_type"><?php 
esc_html_e( 'Default Insertion Type', 'linkcentral' );
?></label>
                    </th>
                    <td class="linkcentral-info-icon-cell">
                        <span class="linkcentral-info-icon dashicons dashicons-info-outline" data-tooltip="<?php 
esc_attr_e( 'Specify the default link insertion method to be pre-selected when adding new links into your website.', 'linkcentral' );
?>"></span>
                    </td>
                    <td>
                        <select name="linkcentral_default_link_insertion_type" id="linkcentral_default_link_insertion_type">
                            <option value="synchronized" <?php 
selected( get_option( 'linkcentral_default_link_insertion_type', 'synchronized' ), 'synchronized' );
?>><?php 
esc_html_e( 'Synchronized', 'linkcentral' );
?></option>
                            <option value="direct" <?php 
selected( get_option( 'linkcentral_default_link_insertion_type' ), 'direct' );
?>><?php 
esc_html_e( 'Direct', 'linkcentral' );
?></option>
                            <option value="shortcode" <?php 
selected( get_option( 'linkcentral_default_link_insertion_type' ), 'shortcode' );
?>><?php 
esc_html_e( 'Shortcode', 'linkcentral' );
?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <?php 
/**
 * Premium Features Section
 * 
 * Displays information about premium features and license management.
 */
?>
        <div id="premium" class="tab-content" style="display:none;">
            <div>
                <?php 
?>
                    <div id="premium-upselling-container">
                        <div id="premium-upselling-bg-shapes">
                            <div class="soft-shape-1"></div>
                            <div class="soft-shape-2"></div>
                        </div>
                        <div id="premium-upselling-card">
                            <div class="premium-header">
                                <div class="premium-header-text">
                                    <h3><?php 
esc_html_e( 'LinkCentral Premium', 'linkcentral' );
?></h3>
                                    <p><?php 
esc_html_e( 'Unlock access to premium features.', 'linkcentral' );
?></p>
                                </div>
                                <div class="premium-header-logo">
                                    <img src="<?php 
echo esc_url( LINKCENTRAL_PLUGIN_URL . 'assets/images/linkcentral-premium-logo.svg' );
?>" width="50" alt="LinkCentral Premium Logo">
                                </div>
                            </div>
                            <hr>
                            <ul>
                                <li><?php 
esc_html_e( 'Link-specific insights', 'linkcentral' );
?></li>
                                <li><?php 
esc_html_e( 'Advanced Dynamic Redirects', 'linkcentral' );
?></li>
                                <li><?php 
esc_html_e( 'Parameter forwarding', 'linkcentral' );
?></li>
                                <li><?php 
esc_html_e( 'Custom styling with CSS classes', 'linkcentral' );
?></li>
                                <li><?php 
esc_html_e( '14-Day Money-Back Guarantee', 'linkcentral' );
?></li>
                                <li><a href="https://www.designforwp.com/linkcentral" target="_blank" rel="noopener noreferrer"><?php 
esc_html_e( 'See all features', 'linkcentral' );
?></a></li>
                            </ul>
                            <hr>
                            <a class="buy-now" href="https://www.designforwp.com/linkcentral" target="_blank" rel="noopener noreferrer"><?php 
esc_html_e( 'Get Premium', 'linkcentral' );
?></a>
                        </div>
                    </div>
                <?php 
?>
            </div>
        </div>

        <?php 
submit_button();
?>
    </form>
</div>
