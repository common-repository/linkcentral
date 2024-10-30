<?php

/**
 * Modify the URL used in the rendered widgets if the LinkCentral URL is enabled.
 * This filter only runs on the frontend.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

add_filter('elementor/frontend/the_content', function($content) {
    // Get our random identifier 
    $elementor_random_identifier = LinkCentral_integrations::get_random_identifier();

    if ($elementor_random_identifier) {
        // Updated regex to handle the new format: #linkcentral-{linkcentral-id}-{elementor-id} with optional parameters
        $content = preg_replace(
            '/<a\s+((?:[^>]*\s)?href="#linkcentral-([^-]+)-' . esc_attr($elementor_random_identifier) . '(\?([^"]*))?"[^>]*)>/',
            '<a $1 href="#linkcentral" data-linkcentral-id-sync="$2" data-linkcentral-parameters="$4">',
            $content
        );
    }

    return $content;
});

