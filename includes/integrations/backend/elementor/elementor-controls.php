<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
class LinkCentral_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'linkcentral_dynamic_tag';
    }

    public function get_title() {
        return __( 'LinkCentral Link', 'linkcentral' );
    }

    public function get_group() {
        return 'linkcentral_group';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::URL_CATEGORY];
    }

    public function register_advanced_section() {
        return false;
    }

    protected function register_controls() {
        $this->add_control( 'linkcentral_link', [
            'label'       => __( 'Select LinkCentral Link', 'linkcentral' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'options'     => $this->get_linkcentral_links(),
            'label_block' => true,
        ] );
        $this->end_controls_section();
        $this->start_controls_section( 'advanced_settings', [
            'label' => esc_html__( 'Advanced', 'linkcentral' ),
        ] );
        $this->add_control( 'custom_panel_notice', [
            'type'        => \Elementor\Controls_Manager::NOTICE,
            'notice_type' => 'warning',
            'dismissible' => false,
            'heading'     => esc_html__( 'Get LinkCentral Premium', 'linkcentral' ),
            'content'     => esc_html__( 'Enable more options, such as parameters, with LinkCentral Premium.', 'linkcentral' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) ) . '" target="_blank">' . esc_html__( 'More info', 'linkcentral' ) . '</a>',
        ] );
    }

    public function render() {
        $link_id = $this->get_settings( 'linkcentral_link' );
        if ( !$link_id ) {
            return;
        }
        // Get the random identifier
        $elementor_random_identifier = LinkCentral_integrations::get_random_identifier();
        // Check if we are in edit mode
        $is_edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() || defined( 'DOING_AJAX' ) && DOING_AJAX && $this->verify_ajax_nonce();
        if ( $is_edit_mode ) {
            echo "#linkcentral";
            // Don't expose the link ID in the editor or preview to avoid confusion among editors.
        } else {
            $url = "#linkcentral-" . $link_id . '-' . $elementor_random_identifier;
            // Check if parameters are set and append them to the URL
            $parameters = $this->get_settings( 'parameters' );
            if ( !empty( $parameters ) ) {
                $url .= '?' . ltrim( $parameters, '?' );
            }
            echo esc_url( $url );
        }
        return;
    }

    private function get_linkcentral_links() {
        $links = get_posts( [
            'post_type'      => 'linkcentral_link',
            'posts_per_page' => -1,
        ] );
        $options = [];
        foreach ( $links as $link ) {
            $options[$link->ID] = $link->post_title;
        }
        return $options;
    }

    private function verify_ajax_nonce() {
        if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'elementor_ajax' || !isset( $_POST['_nonce'] ) ) {
            return false;
        }
        $nonce = sanitize_text_field( wp_unslash( $_POST['_nonce'] ) );
        return wp_verify_nonce( $nonce, 'elementor_ajax' );
    }

}

// Register the dynamic tag and the group together
function register_linkcentral_dynamic_tag(  $dynamic_tags  ) {
    // Register custom group
    $dynamic_tags->register_group( 'linkcentral_group', [
        'title' => __( 'LinkCentral', 'linkcentral' ),
    ] );
    // Register the dynamic tag itself
    $dynamic_tags->register( new LinkCentral_Dynamic_Tag() );
}

add_action( 'elementor/dynamic_tags/register', 'register_linkcentral_dynamic_tag' );