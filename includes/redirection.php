<?php

/**
 * LinkCentral Redirection Class
 *
 * This class handles the redirection and click tracking functionality for LinkCentral.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Redirection {
    private $url_prefix;

    /**
     * Constructor.
     *
     * @param string $url_prefix The URL prefix for LinkCentral links.
     */
    public function __construct( $url_prefix ) {
        $this->url_prefix = $url_prefix;
    }

    /**
     * Initialize the redirection functionality.
     */
    public function init() {
        add_action( 'init', array($this, 'add_rewrite_rules') );
        add_filter( 'query_vars', array($this, 'add_query_vars') );
        add_action( 'template_redirect', array($this, 'handle_redirects') );
    }

    /**
     * Add rewrite rules for LinkCentral links.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( '^' . $this->url_prefix . '/([^/]+)/?$', 'index.php?linkcentral_link=$matches[1]', 'top' );
        if ( !get_option( 'linkcentral_rewrite_rules_flushed' ) ) {
            flush_rewrite_rules();
            update_option( 'linkcentral_rewrite_rules_flushed', true );
        }
    }

    /**
     * Add query vars for LinkCentral links.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'linkcentral_link';
        return $vars;
    }

    /**
     * Handle redirects for LinkCentral links.
     */
    public function handle_redirects() {
        global $wp_query;
        if ( isset( $wp_query->query_vars['linkcentral_link'] ) ) {
            $slug = $wp_query->query_vars['linkcentral_link'];
            $link = get_page_by_path( $slug, OBJECT, 'linkcentral_link' );
            if ( $link ) {
                // Check if the link is a draft, set as private, or scheduled for future
                if ( $link->post_status === 'draft' || $link->post_status === 'private' || $link->post_status === 'future' ) {
                    if ( !current_user_can( 'edit_posts' ) ) {
                        wp_die( 'This link is currently not accessible.', 'Inaccessible Link', array(
                            'response' => 404,
                        ) );
                    }
                }
                // Check if the link is password protected
                if ( post_password_required( $link->ID ) ) {
                    // Display the custom password form
                    include LINKCENTRAL_PLUGIN_DIR . 'views/password-form.php';
                    exit;
                }
                // Log the click server-side
                $destination_url = get_post_meta( $link->ID, '_linkcentral_destination_url', true );
                $this->record_click( $link->ID, $link->post_name, $destination_url );
                $redirection_type = $this->get_redirection_type( $link->ID );
                // Perform the redirection
                wp_redirect( $destination_url, $redirection_type );
                exit;
            }
        }
    }

    /**
     * Get the dynamic destination URL based on rules.
     *
     * @param int   $link_id The ID of the link.
     * @param array $rules   The dynamic rules for the link.
     * @return string|false The destination URL if a rule matches, false otherwise.
     */
    private function get_dynamic_destination_url( $link_id, $rules ) {
        if ( empty( $rules ) || !is_array( $rules ) ) {
            return false;
        }
        foreach ( $rules as $rule ) {
            if ( $this->rule_matches( $rule['variables'] ) ) {
                return $rule['destination'];
            }
        }
        return false;
    }

    /**
     * Check if a rule matches the current conditions.
     *
     * @param array $variables The variables to check.
     * @return bool Whether the rule matches.
     */
    private function rule_matches( $variables ) {
        foreach ( $variables as $variable ) {
            list( $type, $condition, $value ) = $variable;
            switch ( $type ) {
                case 'country':
                    if ( !$this->check_country_condition( $condition, $value ) ) {
                        return false;
                    }
                    break;
                case 'device':
                    if ( !$this->check_device_condition( $condition, $value ) ) {
                        return false;
                    }
                    break;
                case 'date':
                    if ( !$this->check_date_condition( $condition, $value ) ) {
                        return false;
                    }
                    break;
                case 'time':
                    if ( !$this->check_time_condition( $condition, $value ) ) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Check if the country condition is met.
     *
     * @param string $condition The condition to check.
     * @param array  $value     The countries to check against.
     * @return bool Whether the condition is met.
     */
    private function check_country_condition( $condition, $value ) {
        $user_country = $this->get_user_country();
        if ( $condition === 'is' ) {
            return in_array( $user_country, $value );
        } elseif ( $condition === 'is not' ) {
            return !in_array( $user_country, $value );
        }
        return false;
    }

    /**
     * Check if the device condition is met.
     *
     * @param string $condition The condition to check.
     * @param array  $value     The devices to check against.
     * @return bool Whether the condition is met.
     */
    private function check_device_condition( $condition, $value ) {
        $user_device = $this->get_user_device();
        if ( $condition === 'is' ) {
            return in_array( $user_device, $value );
        } elseif ( $condition === 'is not' ) {
            return !in_array( $user_device, $value );
        }
        return false;
    }

    /**
     * Check if the date condition is met.
     *
     * @param string $condition The condition to check.
     * @param string $value     The date to check against.
     * @return bool Whether the condition is met.
     */
    private function check_date_condition( $condition, $value ) {
        $current_date = current_time( 'Y-m-d' );
        switch ( $condition ) {
            case 'is before':
                return $current_date < $value;
            case 'is after':
                return $current_date > $value;
            case 'is on':
                return $current_date === $value;
            case 'is between':
            case 'is not between':
                if ( is_array( $value ) && count( $value ) === 2 ) {
                    $start_date = $value[0];
                    $end_date = $value[1];
                    $is_between = $current_date >= $start_date && $current_date <= $end_date;
                    return ( $condition === 'is between' ? $is_between : !$is_between );
                }
                return false;
        }
        return false;
    }

    /**
     * Check if the time condition is met.
     *
     * @param string $condition The condition to check.
     * @param string $value     The time to check against.
     * @return bool Whether the condition is met.
     */
    private function check_time_condition( $condition, $value ) {
        $current_time = current_time( 'H:i' );
        switch ( $condition ) {
            case 'is before':
                return $current_time < $value;
            case 'is after':
                return $current_time > $value;
            case 'is':
                return $current_time === $value;
            case 'is between':
            case 'is not between':
                if ( is_array( $value ) && count( $value ) === 2 ) {
                    $start_time = $value[0];
                    $end_time = $value[1];
                    $is_between = ( $start_time <= $end_time ? $current_time >= $start_time && $current_time <= $end_time : $current_time >= $start_time || $current_time <= $end_time );
                    return ( $condition === 'is between' ? $is_between : !$is_between );
                }
                return false;
        }
        return false;
    }

    /**
     * Get the user's country.
     *
     * @return string The user's country code.
     */
    private function get_user_country() {
        $geolocation_service = get_option( 'linkcentral_geolocation_service', 'none' );
        switch ( $geolocation_service ) {
            case 'cloudflare':
                return $this->get_country_from_cloudflare();
            case 'maxmind':
                return $this->get_country_from_maxmind();
            default:
                return '';
        }
    }

    /**
     * Get the country code from Cloudflare headers.
     *
     * @return string The country code or empty string if not available.
     */
    private function get_country_from_cloudflare() {
        return ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) : '' );
    }

    /**
     * Get the country code using MaxMind GeoIP2.
     *
     * @return string The country code or empty string if not available.
     */
    private function get_country_from_maxmind() {
        $upload_dir = wp_upload_dir();
        $maxmind_database_path = $upload_dir['basedir'] . '/linkcentral/GeoLite2-Country/GeoLite2-Country.mmdb';
        if ( !file_exists( $maxmind_database_path ) ) {
            return '';
        }
        try {
            require_once LINKCENTRAL_PLUGIN_DIR . 'vendor/autoload.php';
            $reader = new \GeoIp2\Database\Reader($maxmind_database_path);
            $record = $reader->country( $this->get_ip_address() );
            return $record->country->isoCode;
        } catch ( \Exception $e ) {
            return '';
        }
    }

    /**
     * Get the user's device type.
     *
     * @return string The user's device type (desktop, mobile, or tablet).
     */
    private function get_user_device() {
        $user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' );
        if ( preg_match( '/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower( $user_agent ) ) ) {
            return 'tablet';
        }
        if ( preg_match( '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower( $user_agent ) ) ) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Send click data to Google Analytics.
     *
     * @param int    $link_id The ID of the clicked link.
     * @param string $slug    The slug of the clicked link.
     */
    private function send_to_google_analytics( $link_id, $slug, $destination_url ) {
        // Check if Google Analytics integration is enabled
        if ( !get_option( 'linkcentral_enable_ga' ) ) {
            return;
            // Don't send if GA integration is not enabled
        }
        $measurement_id = get_option( 'linkcentral_ga_measurement_id' );
        $api_secret = get_option( 'linkcentral_ga_api_secret' );
        if ( empty( $measurement_id ) || empty( $api_secret ) ) {
            return;
            // Don't send if Measurement ID or API secret is not set
        }
        $client_id = $this->get_client_id();
        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}";
        $data = [
            'client_id'        => $client_id,
            'timestamp_micros' => round( microtime( true ) * 1000000 ),
            'events'           => [[
                'name'   => 'outbound_link_click',
                'params' => [
                    'link_url'        => home_url( $this->url_prefix . '/' . $slug ),
                    'link_title'      => get_the_title( $link_id ),
                    'link_id'         => $link_id,
                    'destination_url' => $destination_url,
                    'plugin'          => 'LinkCentral',
                ],
            ]],
        ];
        $args = [
            'body'     => wp_json_encode( $data ),
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
            'blocking' => false,
            'timeout'  => 1,
        ];
        wp_remote_post( $url, $args );
    }

    /**
     * Get or generate a client ID for Google Analytics.
     *
     * @return string The client ID.
     */
    private function get_client_id() {
        if ( !isset( $_COOKIE['_ga'] ) ) {
            $client_id = wp_generate_uuid4();
            setcookie(
                '_ga',
                $client_id,
                time() + 2 * 365 * 24 * 60 * 60,
                '/'
            );
        } else {
            $client_id = sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) );
        }
        return $client_id;
    }

    /**
     * Record a click for a link.
     *
     * @param int $link_id The ID of the clicked link.
     * @param string $slug The slug of the clicked link.
     */
    private function record_click( $link_id, $slug, $destination_url ) {
        // Check if reporting is disabled
        if ( get_option( 'linkcentral_disable_reporting', false ) ) {
            return;
        }
        if ( $this->should_record_click( $link_id ) ) {
            // Send data to Google Analytics
            $this->send_to_google_analytics( $link_id, $slug, $destination_url );
            global $wpdb;
            $table_name = $wpdb->prefix . 'linkcentral_stats';
            $data = array(
                'link_id'         => $link_id,
                'click_date'      => current_time( 'mysql' ),
                'referring_url'   => ( wp_get_referer() ? wp_get_referer() : '' ),
                'destination_url' => $destination_url,
            );
            // Only add IP address if tracking is enabled
            if ( get_option( 'linkcentral_track_ip', true ) ) {
                $data['ip_address'] = $this->get_ip_address();
            }
            // Only add user_agent if tracking is enabled and the header is set
            if ( get_option( 'linkcentral_track_user_agent', true ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
                $data['user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
            }
            // Check if unique visitor tracking is enabled
            if ( get_option( 'linkcentral_track_unique_visitors', true ) ) {
                $visitor_id = $this->get_or_set_visitor_id();
                $data['visitor_id'] = $visitor_id;
            }
            $wpdb->insert( $table_name, $data );
        }
    }

    /**
     * Get or set a unique visitor ID cookie.
     *
     * @return string The visitor ID.
     */
    private function get_or_set_visitor_id() {
        $cookie_name = 'lclink_visitor';
        $cookie_expiration = time() + 30 * 24 * 60 * 60;
        // 30 days
        if ( isset( $_COOKIE[$cookie_name] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE[$cookie_name] ) );
        } else {
            $visitor_id = wp_generate_uuid4();
            setcookie(
                $cookie_name,
                $visitor_id,
                $cookie_expiration,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
            return $visitor_id;
        }
    }

    /**
     * Get the IP address of the current user, accounting for various proxy situations.
     *
     * @return string The IP address.
     */
    private function get_ip_address() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            // Cloudflare
            'HTTP_X_REAL_IP',
            // Trusted proxy servers
            'HTTP_CLIENT_IP',
            // Some proxy servers (less reliable)
            'HTTP_X_FORWARDED_FOR',
            // Can contain multiple IPs (check the first valid one)
            'REMOTE_ADDR',
        );
        foreach ( $ip_headers as $header ) {
            if ( !empty( $_SERVER[$header] ) ) {
                // If header contains multiple IPs (in case of HTTP_X_FORWARDED_FOR)
                $ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[$header] ) ) );
                $ip = trim( $ip_list[0] );
                // Validate the IP address (both IPv4 and IPv6)
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
                    return $ip;
                }
            }
        }
        // Return an empty string if no valid IP was found
        return '';
    }

    /**
     * Determine if a click should be recorded.
     *
     * @param int $link_id The ID of the clicked link.
     * @return bool Whether the click should be recorded.
     */
    private function should_record_click( $link_id ) {
        $excluded_ips = get_option( 'linkcentral_excluded_ips', '' );
        $excluded_ip_list = array_map( 'trim', explode( "\n", $excluded_ips ) );
        $current_ip = $this->get_ip_address();
        if ( in_array( $current_ip, $excluded_ip_list ) ) {
            return false;
        }
        $excluded_roles = get_option( 'linkcentral_excluded_roles', array() );
        $current_user = wp_get_current_user();
        if ( array_intersect( $current_user->roles, $excluded_roles ) ) {
            return false;
        }
        // Check if bot exclusion is enabled and if the current request is from a bot
        if ( get_option( 'linkcentral_exclude_bots', false ) && $this->is_bot() ) {
            return false;
        }
        // Check if this click has been recorded recently
        $transient_key = 'linkcentral_click_' . $link_id . '_' . md5( $current_ip );
        if ( get_transient( $transient_key ) ) {
            return false;
        }
        // Set a transient to prevent double-counting within 5 seconds
        set_transient( $transient_key, true, 5 );
        return true;
    }

    /**
     * Determine if the current request is from a bot.
     *
     * @return bool Whether the request is from a bot.
     */
    private function is_bot() {
        if ( !isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }
        $user_agent = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
        $bot_keywords = array(
            'bot',
            'crawler',
            'spider',
            'slurp',
            'googlebot',
            'bingbot',
            'yandexbot'
        );
        foreach ( $bot_keywords as $keyword ) {
            if ( strpos( $user_agent, $keyword ) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the redirection type for a link.
     *
     * @param int $link_id The ID of the link.
     * @return int The HTTP status code for the redirection.
     */
    private function get_redirection_type( $link_id ) {
        $redirection_type = get_post_meta( $link_id, '_linkcentral_redirection_type', true );
        if ( $redirection_type === 'default' || !$redirection_type ) {
            $redirection_type = get_option( 'linkcentral_global_redirection_type', '307' );
        }
        return intval( $redirection_type );
    }

}
