<?php

/**
 * LinkCentral Shortcode Class
 *
 * This class handles the functionality for the LinkCentral shortcode,
 * allowing users to easily insert LinkCentral links into their content.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Shortcode {
    /**
     * The URL prefix for LinkCentral links.
     */
    private $url_prefix;

    /**
     * Constructor.
     */
    public function __construct( $url_prefix ) {
        $this->url_prefix = $url_prefix;
    }

    /**
     * Initialize the shortcode.
     */
    public function init() {
        add_shortcode( 'linkcentral', array($this, 'render_shortcode') );
    }

    /**
     * Render the LinkCentral shortcode.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content The content between the shortcode tags.
     * @return string The rendered shortcode output.
     */
    public function render_shortcode( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'id'         => 0,
            'newtab'     => 'false',
            'parameters' => '',
        ), $atts, 'linkcentral' );
        $link_id = intval( $atts['id'] );
        if ( !$link_id ) {
            return $content;
        }
        $link = get_post( $link_id );
        if ( !$link || $link->post_type !== 'linkcentral_link' || $link->post_status !== 'publish' ) {
            return $content;
        }
        $url = home_url( '/' . $this->url_prefix . '/' . $link->post_name );
        $nofollow = $this->get_nofollow_attribute( $link_id );
        $sponsored = $this->get_sponsored_attribute( $link_id );
        $target = ( $atts['newtab'] === 'true' ? ' target="_blank"' : '' );
        $css_classes = $this->get_css_classes( $link_id );
        $rel_attributes = array_filter( [$nofollow, $sponsored] );
        $rel = ( !empty( $rel_attributes ) ? ' rel="' . implode( ' ', $rel_attributes ) . '"' : '' );
        $class_attribute = ( !empty( $css_classes ) ? ' class="' . esc_attr( $css_classes ) . '"' : '' );
        return '<a href="' . esc_url( $url ) . '"' . $target . $rel . $class_attribute . '>' . $content . '</a>';
    }

    /**
     * Get the nofollow attribute for a link.
     *
     * @param int $link_id The ID of the link.
     * @return string The nofollow attribute if applicable, otherwise an empty string.
     */
    private function get_nofollow_attribute( $link_id ) {
        $nofollow = get_post_meta( $link_id, '_linkcentral_nofollow', true );
        if ( $nofollow === 'default' ) {
            $nofollow = ( get_option( 'linkcentral_global_nofollow', false ) ? 'yes' : 'no' );
        }
        return ( $nofollow === 'yes' ? 'nofollow' : '' );
    }

    /**
     * Get the sponsored attribute for a link.
     *
     * @param int $link_id The ID of the link.
     * @return string The sponsored attribute if applicable, otherwise an empty string.
     */
    private function get_sponsored_attribute( $link_id ) {
        $sponsored = get_post_meta( $link_id, '_linkcentral_sponsored', true );
        if ( $sponsored === 'default' ) {
            $sponsored = ( get_option( 'linkcentral_global_sponsored', false ) ? 'yes' : 'no' );
        }
        return ( $sponsored === 'yes' ? 'sponsored' : '' );
    }

    /**
     * Get the CSS classes for a link.
     *
     * @param int $link_id The ID of the link.
     * @return string The CSS classes to be applied to the link.
     */
    private function get_css_classes( $link_id ) {
        $css_classes_option = get_post_meta( $link_id, '_linkcentral_css_classes_option', true );
        $custom_css_classes = get_post_meta( $link_id, '_linkcentral_custom_css_classes', true );
        $global_css_classes = get_option( 'linkcentral_custom_css_classes', '' );
        switch ( $css_classes_option ) {
            case 'replace':
                return $custom_css_classes;
            case 'append':
                return $global_css_classes . ' ' . $custom_css_classes;
            case 'default':
            default:
                return $global_css_classes;
        }
    }

    /**
     * Determine if parameters should be forwarded for a link.
     *
     * @param int $link_id The ID of the link.
     * @return bool Whether parameters should be forwarded.
     */
    private function should_forward_parameters( $link_id ) {
        $parameter_forwarding = get_post_meta( $link_id, '_linkcentral_parameter_forwarding', true );
        if ( $parameter_forwarding === 'yes' ) {
            return true;
        } elseif ( $parameter_forwarding === 'no' ) {
            return false;
        } else {
            return get_option( 'linkcentral_global_parameter_forwarding', false );
        }
    }

}
