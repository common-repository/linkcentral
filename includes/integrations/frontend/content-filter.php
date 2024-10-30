<?php

/**
 * LinkCentral Content Filter
 *
 * This file contains functions to process and replace LinkCentral links in post content.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Content_Filter {
    private $link_cache = array();

    private $url_prefix = '';

    private $global_css_classes = '';

    private $global_nofollow = false;

    private $global_sponsored = false;

    private $global_parameter_forwarding = false;

    private $is_elementor_active;

    /**
     * Constructor for the LinkCentral_Content_Filter class.
     * 
     * Initializes the class properties with values from WordPress options.
     */
    public function __construct() {
        $this->url_prefix = get_option( 'linkcentral_url_prefix', 'go' );
        $this->global_css_classes = get_option( 'linkcentral_custom_css_classes', '' );
        $this->global_nofollow = get_option( 'linkcentral_global_nofollow', false );
        $this->global_sponsored = get_option( 'linkcentral_global_sponsored', false );
        $this->global_parameter_forwarding = get_option( 'linkcentral_global_parameter_forwarding', false );
        $this->is_elementor_active = did_action( 'elementor/loaded' );
    }

    /**
     * Initialize and setup the content filter.
     */
    public function init() {
        // Always add the Elementor filter if Elementor is active
        if ( $this->is_elementor_active ) {
            add_filter( 'elementor/frontend/the_content', array($this, 'process_linkcentral_links'), 999 );
        }
        // Add the WordPress content filter, but check if it's needed on each page load
        add_action( 'wp', function () {
            if ( !$this->is_elementor_active || !$this->is_elementor_page() ) {
                add_filter( 'the_content', array($this, 'process_linkcentral_links'), 999 );
            }
        } );
    }

    /**
     * Check if the current page is an Elementor page.
     *
     * @return bool Whether the page is an Elementor page.
     */
    private function is_elementor_page() {
        if ( !class_exists( '\\Elementor\\Plugin' ) ) {
            return false;
        }
        $document = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
        return $document && $document->is_built_with_elementor();
    }

    /**
     * Process the content and replace LinkCentral links.
     *
     * @param string $content The post content.
     * @return string The processed content.
     */
    public function process_linkcentral_links( $content ) {
        if ( empty( $content ) || strpos( $content, 'data-linkcentral-id-sync' ) === false ) {
            return $content;
        }
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( mb_encode_numericentity( htmlspecialchars_decode( htmlentities(
            $content,
            ENT_NOQUOTES,
            'UTF-8',
            false
        ), ENT_NOQUOTES ), [
            0x80,
            0x10ffff,
            0,
            ~0
        ], 'UTF-8' ) );
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $links = $xpath->query( "//a[@data-linkcentral-id-sync]" );
        if ( $links->length === 0 ) {
            return $content;
        }
        $link_ids = array();
        foreach ( $links as $link ) {
            $link_id = $link->getAttribute( 'data-linkcentral-id-sync' );
            $link_ids[] = absint( $link_id );
        }
        $this->preload_link_data( $link_ids );
        foreach ( $links as $link ) {
            $link_id = $link->getAttribute( 'data-linkcentral-id-sync' );
            $link_data = $this->link_cache[$link_id] ?? null;
            if ( $link_data ) {
                $this->update_link_element( $link, $link_data );
            }
        }
        $new_content = $dom->saveHTML( $dom->documentElement );
        return ( $new_content ? $new_content : $content );
    }

    /**
     * Update the link element with the processed LinkCentral data.
     *
     * @param DOMElement $link     The link element to update.
     * @param array      $link_data The processed link data.
     */
    private function update_link_element( $link, $link_data ) {
        $new_href = $this->get_linkcentral_url( $link_data['ID'], $link_data['post_name'] );
        $link->setAttribute( 'href', esc_url_raw( $new_href ) );
        $css_classes = $this->get_css_classes( $link_data );
        $nofollow = $this->get_nofollow_attribute( $link_data );
        $sponsored = $this->get_sponsored_attribute( $link_data );
        // Preserve existing classes and append new ones
        $existing_classes = $link->getAttribute( 'class' );
        $existing_classes = preg_replace( '/\\blinkcentral-link\\b\\s*/', '', $existing_classes );
        $all_classes = trim( $existing_classes . ' ' . $css_classes );
        $all_classes = preg_replace( '/\\s+/', ' ', $all_classes );
        if ( !empty( $all_classes ) ) {
            $link->setAttribute( 'class', esc_attr( $all_classes ) );
        } else {
            $link->removeAttribute( 'class' );
        }
        // Set the rel attribute
        $rel_attributes = array_filter( [$nofollow, $sponsored] );
        if ( !empty( $rel_attributes ) ) {
            $link->setAttribute( 'rel', implode( ' ', $rel_attributes ) );
        } else {
            $link->removeAttribute( 'rel' );
        }
        // Preserve the target attribute if it exists
        $target = $link->getAttribute( 'target' );
        if ( !empty( $target ) ) {
            $link->setAttribute( 'target', esc_attr( $target ) );
        }
        // Remove the data-linkcentral-id-sync and data-linkcentral-parameters attributes
        $link->removeAttribute( 'data-linkcentral-id-sync' );
        //$link->removeAttribute('data-linkcentral-parameters');
    }

    /**
     * Preload link data for multiple link IDs.
     *
     * @param array $link_ids An array of link IDs to preload data for.
     */
    private function preload_link_data( $link_ids ) {
        $link_ids = array_unique( array_map( 'absint', $link_ids ) );
        $link_ids = array_diff( $link_ids, array_keys( $this->link_cache ) );
        if ( empty( $link_ids ) ) {
            return;
        }
        global $wpdb;
        $placeholders = implode( ',', array_fill( 0, count( $link_ids ), '%d' ) );
        $query = "\n            SELECT p.ID, p.post_name, \n                   MAX(CASE WHEN pm.meta_key = '_linkcentral_css_classes_option' THEN pm.meta_value END) AS css_classes_option,\n                   MAX(CASE WHEN pm.meta_key = '_linkcentral_custom_css_classes' THEN pm.meta_value END) AS custom_css_classes,\n                   MAX(CASE WHEN pm.meta_key = '_linkcentral_nofollow' THEN pm.meta_value END) AS nofollow,\n                   MAX(CASE WHEN pm.meta_key = '_linkcentral_sponsored' THEN pm.meta_value END) AS sponsored,\n                   MAX(CASE WHEN pm.meta_key = '_linkcentral_parameter_forwarding' THEN pm.meta_value END) AS parameter_forwarding\n            FROM {$wpdb->posts} p\n            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id\n            WHERE p.ID IN ({$placeholders})\n            AND p.post_type = 'linkcentral_link'\n            AND p.post_status = 'publish'\n            GROUP BY p.ID";
        $results = $wpdb->get_results( $wpdb->prepare( $query, ...$link_ids ), ARRAY_A );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        foreach ( $results as $result ) {
            $this->link_cache[$result['ID']] = $result;
        }
    }

    /**
     * Get the LinkCentral URL for a given link ID.
     *
     * @param string $link_id The LinkCentral link ID.
     * @param string $post_name The post name.
     * @return string|false The LinkCentral URL or false if not found.
     */
    private function get_linkcentral_url( $link_id, $post_name ) {
        return home_url( '/' . sanitize_title( $this->url_prefix ) . '/' . sanitize_title( $post_name ) );
    }

    /**
     * Get the CSS classes for a link based on its data and global settings.
     *
     * @param array $link_data The link data.
     * @return string The CSS classes to be applied to the link.
     */
    private function get_css_classes( $link_data ) {
        switch ( $link_data['css_classes_option'] ) {
            case 'replace':
                return $link_data['custom_css_classes'];
            case 'append':
                return trim( $this->global_css_classes . ' ' . $link_data['custom_css_classes'] );
            case 'default':
            default:
                return $this->global_css_classes;
        }
    }

    /**
     * Determine if the nofollow attribute should be applied to a link.
     *
     * @param array $link_data The link data.
     * @return string 'nofollow' if the attribute should be applied, empty string otherwise.
     */
    private function get_nofollow_attribute( $link_data ) {
        if ( $link_data['nofollow'] === 'yes' ) {
            return 'nofollow';
        } elseif ( $link_data['nofollow'] === 'no' ) {
            return '';
        } else {
            return ( $this->global_nofollow ? 'nofollow' : '' );
        }
    }

    /**
     * Determine if the sponsored attribute should be applied to a link.
     *
     * @param array $link_data The link data.
     * @return string 'sponsored' if the attribute should be applied, empty string otherwise.
     */
    private function get_sponsored_attribute( $link_data ) {
        if ( $link_data['sponsored'] === 'yes' ) {
            return 'sponsored';
        } elseif ( $link_data['sponsored'] === 'no' ) {
            return '';
        } else {
            return ( $this->global_sponsored ? 'sponsored' : '' );
        }
    }

}
