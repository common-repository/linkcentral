<?php

/**
 * LinkCentral Post Type Class
 *
 * This class handles the registration and management of the custom post type for LinkCentral links.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
// Include the countries file
require_once plugin_dir_path( __FILE__ ) . '../utils/countries.php';
class LinkCentral_Post_Type {
    /**
     * Nonce action for form submission
     * @var string
     */
    private $nonce_action = 'linkcentral_save_link_data';

    /**
     * Nonce name for form submission
     * @var string
     */
    private $nonce_name = 'linkcentral_link_nonce';

    /**
     * Initialize the class by hooking methods to WordPress actions
     */
    public function init() {
        add_action( 'init', array($this, 'register_post_type') );
        add_action( 'init', array($this, 'register_taxonomy') );
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'add_meta_boxes', array($this, 'add_how_to_use_meta_box') );
        add_action( 'add_meta_boxes', array($this, 'remove_slug_meta_box'), 11 );
        add_action(
            'save_post',
            array($this, 'save_meta_boxes'),
            10,
            2
        );
        add_action( 'admin_notices', array($this, 'show_title_error') );
        add_action( 'init', array($this, 'register_rest_fields') );
        add_action( 'before_delete_post', array($this, 'delete_tracking_data_on_link_deletion') );
        add_action( 'post_updated_messages', array($this, 'custom_post_updated_messages') );
        add_action( 'in_admin_header', array($this, 'add_admin_header') );
        add_action( 'wp_ajax_linkcentral_check_slug', array($this, 'ajax_check_slug') );
        add_filter(
            'wp_statuses_list',
            array($this, 'remove_post_statuses'),
            10,
            2
        );
        add_filter(
            'wp_insert_post_data',
            array($this, 'prevent_status_change'),
            10,
            2
        );
        // Add the enqueue action
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts') );
    }

    /**
     * Register the custom post type for LinkCentral links
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x( 'LinkCentral Links', 'post type general name', 'linkcentral' ),
            'singular_name'      => _x( 'LinkCentral Link', 'post type singular name', 'linkcentral' ),
            'menu_name'          => _x( 'LinkCentral Links', 'admin menu', 'linkcentral' ),
            'name_admin_bar'     => _x( 'LinkCentral Link', 'add new on admin bar', 'linkcentral' ),
            'add_new'            => _x( 'Add New', 'link', 'linkcentral' ),
            'add_new_item'       => __( 'Add a New Link', 'linkcentral' ),
            'new_item'           => __( 'New Link', 'linkcentral' ),
            'edit_item'          => __( 'Edit Link', 'linkcentral' ),
            'view_item'          => __( 'View Link', 'linkcentral' ),
            'all_items'          => __( 'All Links', 'linkcentral' ),
            'search_items'       => __( 'Search Links', 'linkcentral' ),
            'parent_item_colon'  => __( 'Parent Links:', 'linkcentral' ),
            'not_found'          => __( 'No links found.', 'linkcentral' ),
            'not_found_in_trash' => __( 'No links found in Trash.', 'linkcentral' ),
        );
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'query_var'             => true,
            'rewrite'               => array(
                'slug' => 'linkcentral-link',
            ),
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array('title', 'slug'),
            'show_in_rest'          => true,
            'rest_base'             => 'linkcentral_link',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        register_post_type( 'linkcentral_link', $args );
    }

    /**
     * Register custom taxonomy for link categories
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => _x( 'Link Categories', 'taxonomy general name', 'linkcentral' ),
            'singular_name'     => _x( 'Link Category', 'taxonomy singular name', 'linkcentral' ),
            'search_items'      => __( 'Search Link Categories', 'linkcentral' ),
            'all_items'         => __( 'All Link Categories', 'linkcentral' ),
            'parent_item'       => __( 'Parent Link Category', 'linkcentral' ),
            'parent_item_colon' => __( 'Parent Link Category:', 'linkcentral' ),
            'edit_item'         => __( 'Edit Link Category', 'linkcentral' ),
            'update_item'       => __( 'Update Link Category', 'linkcentral' ),
            'add_new_item'      => __( 'Add New Link Category', 'linkcentral' ),
            'new_item_name'     => __( 'New Link Category Name', 'linkcentral' ),
            'menu_name'         => __( 'Link Categories', 'linkcentral' ),
        );
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array(
                'slug' => 'link-category',
            ),
        );
        register_taxonomy( 'linkcentral_category', array('linkcentral_link'), $args );
    }

    /**
     * Add custom meta boxes for the link post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'linkcentral_link_details',
            __( 'Link Details', 'linkcentral' ),
            array($this, 'render_meta_box'),
            'linkcentral_link',
            'normal',
            'high'
        );
        add_meta_box(
            'linkcentral_link_tools',
            __( 'Tools', 'linkcentral' ),
            array($this, 'render_tools_meta_box'),
            'linkcentral_link',
            'normal',
            'default'
        );
    }

    /**
     * Remove the default slug meta box
     */
    public function remove_slug_meta_box() {
        remove_meta_box( 'slugdiv', 'linkcentral_link', 'normal' );
    }

    /**
     * Render the custom meta box for link details
     *
     * @param WP_Post $post The current post object
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( $this->nonce_action, $this->nonce_name );
        $destination_url = get_post_meta( $post->ID, '_linkcentral_destination_url', true );
        $nofollow = get_post_meta( $post->ID, '_linkcentral_nofollow', true );
        $sponsored = get_post_meta( $post->ID, '_linkcentral_sponsored', true );
        $redirection_type = get_post_meta( $post->ID, '_linkcentral_redirection_type', true );
        $parameter_forwarding = get_post_meta( $post->ID, '_linkcentral_parameter_forwarding', true );
        $css_classes_option = get_post_meta( $post->ID, '_linkcentral_css_classes_option', true );
        $custom_css_classes = get_post_meta( $post->ID, '_linkcentral_custom_css_classes', true );
        $url_prefix = get_option( 'linkcentral_url_prefix', 'go' );
        $global_css_classes = get_option( 'linkcentral_custom_css_classes', '' );
        // Set default value for destination URL if it's empty
        $destination_url = ( $destination_url ?: 'https://' );
        // Get existing rules and set button class only for premium users
        $existing_rules = [];
        $rules_set_class = '';
        $existing_rules_json = ( !empty( $existing_rules ) ? wp_json_encode( $existing_rules ) : '[]' );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="post_name"><?php 
        esc_html_e( 'Slug:', 'linkcentral' );
        ?></label></th>
                <td>
                    <div id="linkcentral-url-prefix"><?php 
        echo esc_html( home_url( '/' . $url_prefix . '/' ) );
        ?></div>
                    <div class="linkcentral-slug-container">
                        <input type="text" id="post_name" name="post_name" value="<?php 
        echo esc_attr( $post->post_name );
        ?>" required>
                        <button type="button" id="linkcentral-copy-url" class="button button-secondary"><?php 
        esc_html_e( 'Copy URL', 'linkcentral' );
        ?></button>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="linkcentral_destination_url"><?php 
        esc_html_e( 'Destination URL:', 'linkcentral' );
        ?></label></th>
                <td>
                    <div class="linkcentral-destination-container">
                        <input type="url" id="linkcentral_destination_url" name="linkcentral_destination_url" value="<?php 
        echo esc_url( $destination_url );
        ?>" size="50" required>
                        <button type="button" id="linkcentral-dynamic-redirect" class="button button-secondary <?php 
        echo esc_attr( $rules_set_class );
        ?>">
                            <span class="dashicons dashicons-randomize"></span>
                            <?php 
        esc_html_e( 'Dynamic', 'linkcentral' );
        ?>
                            <?php 
        ?>
                                <span class="dashicons dashicons-lock premium"></span>
                            <?php 
        ?>
                        </button>
                    </div>
                </td>
            </tr>
        </table>

        <h4><?php 
        esc_html_e( 'Link Attributes', 'linkcentral' );
        ?></h4>
        <table class="form-table" id="link-attributes">
            <tr>
                <th scope="row"><label for="linkcentral_nofollow"><?php 
        esc_html_e( 'Nofollow Attribute:', 'linkcentral' );
        ?></label></th>
                <td>
                    <select name="linkcentral_nofollow" id="linkcentral_nofollow">
                        <option value="default" <?php 
        selected( $nofollow, 'default' );
        ?>><?php 
        esc_html_e( 'Default (Global Settings)', 'linkcentral' );
        ?></option>
                        <option value="yes" <?php 
        selected( $nofollow, 'yes' );
        ?>><?php 
        esc_html_e( 'Yes', 'linkcentral' );
        ?></option>
                        <option value="no" <?php 
        selected( $nofollow, 'no' );
        ?>><?php 
        esc_html_e( 'No', 'linkcentral' );
        ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="linkcentral_sponsored"><?php 
        esc_html_e( 'Sponsored Attribute:', 'linkcentral' );
        ?></label></th>
                <td>
                    <select name="linkcentral_sponsored" id="linkcentral_sponsored">
                        <option value="default" <?php 
        selected( $sponsored, 'default' );
        ?>><?php 
        esc_html_e( 'Default (Global Settings)', 'linkcentral' );
        ?></option>
                        <option value="yes" <?php 
        selected( $sponsored, 'yes' );
        ?>><?php 
        esc_html_e( 'Yes', 'linkcentral' );
        ?></option>
                        <option value="no" <?php 
        selected( $sponsored, 'no' );
        ?>><?php 
        esc_html_e( 'No', 'linkcentral' );
        ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="linkcentral_redirection_type"><?php 
        esc_html_e( 'Redirection Type:', 'linkcentral' );
        ?></label></th>
                <td>
                    <select name="linkcentral_redirection_type" id="linkcentral_redirection_type">
                        <option value="default" <?php 
        selected( $redirection_type, 'default' );
        ?>><?php 
        esc_html_e( 'Default (Global Settings)', 'linkcentral' );
        ?></option>
                        <option value="307" <?php 
        selected( $redirection_type, '307' );
        ?>><?php 
        esc_html_e( '307 (Temporary)', 'linkcentral' );
        ?></option>
                        <option value="302" <?php 
        selected( $redirection_type, '302' );
        ?>><?php 
        esc_html_e( '302 (Temporary)', 'linkcentral' );
        ?></option>
                        <option value="301" <?php 
        selected( $redirection_type, '301' );
        ?>><?php 
        esc_html_e( '301 (Permanent)', 'linkcentral' );
        ?></option>
                    </select>
                </td>
            </tr>
            <tr class="linkcentral-premium-feature <?php 
        echo ( linkcentral_fs()->can_use_premium_code__premium_only() ? 'premium-active' : '' );
        ?>">
                <th scope="row"><label for="linkcentral_parameter_forwarding"><?php 
        esc_html_e( 'Parameter Forwarding:', 'linkcentral' );
        ?></label></th>
                <td>
                    <select name="linkcentral_parameter_forwarding" id="linkcentral_parameter_forwarding" <?php 
        echo ( linkcentral_fs()->is_free_plan() ? 'disabled' : '' );
        ?>>
                        <option value="default" <?php 
        selected( $parameter_forwarding, 'default' );
        ?>><?php 
        esc_html_e( 'Default (Global Settings)', 'linkcentral' );
        ?></option>
                        <option value="yes" <?php 
        selected( $parameter_forwarding, 'yes' );
        ?>><?php 
        esc_html_e( 'Yes', 'linkcentral' );
        ?></option>
                        <option value="no" <?php 
        selected( $parameter_forwarding, 'no' );
        ?>><?php 
        esc_html_e( 'No', 'linkcentral' );
        ?></option>
                    </select>
                    <?php 
        if ( linkcentral_fs()->is_free_plan() ) {
            ?>
                        <a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) );
            ?>" class="linkcentral-premium-tag"><?php 
            esc_html_e( 'Premium', 'linkcentral' );
            ?></a>
                    <?php 
        }
        ?>
                </td>
            </tr>
            <tr class="linkcentral-premium-feature <?php 
        echo ( linkcentral_fs()->can_use_premium_code__premium_only() ? 'premium-active' : '' );
        ?>">
                <th scope="row"><label for="linkcentral_css_classes_option"><?php 
        esc_html_e( 'CSS Classes:', 'linkcentral' );
        ?></label></th>
                <td>
                    <select name="linkcentral_css_classes_option" id="linkcentral_css_classes_option" <?php 
        echo ( linkcentral_fs()->is_free_plan() ? 'disabled' : '' );
        ?>>
                        <option value="default" <?php 
        selected( $css_classes_option, 'default' );
        ?>><?php 
        esc_html_e( 'Default (Global Settings)', 'linkcentral' );
        ?></option>
                        <option value="replace" <?php 
        selected( $css_classes_option, 'replace' );
        ?>><?php 
        esc_html_e( 'Replace with:', 'linkcentral' );
        ?></option>
                        <option value="append" <?php 
        selected( $css_classes_option, 'append' );
        ?>><?php 
        esc_html_e( 'Append with:', 'linkcentral' );
        ?></option>
                    </select>
                    <input type="text" id="linkcentral_custom_css_classes" name="linkcentral_custom_css_classes" value="<?php 
        echo esc_attr( $custom_css_classes );
        ?>" placeholder="<?php 
        esc_attr_e( 'Custom CSS Classes', 'linkcentral' );
        ?>" style="display: <?php 
        echo ( $css_classes_option === '' || $css_classes_option === 'default' || linkcentral_fs()->is_free_plan() ? 'none' : 'inline-block' );
        ?>;" <?php 
        echo ( linkcentral_fs()->is_free_plan() ? 'disabled' : '' );
        ?>>
                    <?php 
        if ( linkcentral_fs()->is_free_plan() ) {
            ?>
                        <a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) );
            ?>" class="linkcentral-premium-tag"><?php 
            esc_html_e( 'Premium', 'linkcentral' );
            ?></a>
                    <?php 
        }
        ?>
                </td>
            </tr>
        </table>
        <input type="hidden" id="linkcentral_dynamic_rules" name="linkcentral_dynamic_rules" value="<?php 
        echo esc_attr( $existing_rules_json );
        ?>">
        <div id="linkcentral-dynamic-redirect-modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-inner">
                    <span class="linkcentral-modal-close">&times;</span>
                    <h3>
                        <?php 
        esc_html_e( 'Dynamic Redirect Rules', 'linkcentral' );
        ?>
                        <?php 
        if ( linkcentral_fs()->is_free_plan() ) {
            ?>
                            <a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) );
            ?>" class="linkcentral-premium-tag"><?php 
            esc_html_e( 'Premium', 'linkcentral' );
            ?></a>
                        <?php 
        }
        ?>
                    </h3>
                    <div id="linkcentral-rules-container"></div>
                    <?php 
        ?>
                        <p><?php 
        esc_html_e( 'Upgrade to LinkCentral Premium to unlock advanced Dynamic Redirects, including redirects by country or specific date, and other powerful features!', 'linkcentral' );
        ?></p>
                        <a href="<?php 
        echo esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) );
        ?>" class="button button-primary"><?php 
        esc_html_e( 'Upgrade Now', 'linkcentral' );
        ?></a>
                    <?php 
        ?>
                </div>
            </div>
        </div>
        <?php 
    }

    /**
     * Render the meta box for tools
     *
     * @param WP_Post $post The current post object
     */
    public function render_tools_meta_box( $post ) {
        $note = get_post_meta( $post->ID, '_linkcentral_note', true );
        ?>
        <div class="linkcentral-tools-metabox">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="linkcentral_note"><?php 
        esc_html_e( 'Administrative Note:', 'linkcentral' );
        ?></label></th>
                    <td>
                        <div class="linkcentral-note-container">
                            <div class="linkcentral-note-display">
                                <span class="linkcentral-note-text"><?php 
        echo esc_html( $note );
        ?></span>
                                <a href="#" class="linkcentral-edit-note">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php 
        esc_html_e( 'Edit', 'linkcentral' );
        ?>
                                </a>
                            </div>
                            <div class="linkcentral-note-edit" style="display: none;">
                                <textarea name="linkcentral_note" id="linkcentral_note" rows="4" style="width: 100%;"><?php 
        echo esc_textarea( $note );
        ?></textarea>
                                <p class="description"><?php 
        esc_html_e( 'This note is for your administrative purposes only and will not be displayed publicly.', 'linkcentral' );
        ?></p>
                                <button type="button" class="linkcentral-save-note button button-primary"><?php 
        esc_html_e( 'Update', 'linkcentral' );
        ?></button>
                                <button type="button" class="linkcentral-cancel-edit button button-secondary"><?php 
        esc_html_e( 'Cancel', 'linkcentral' );
        ?></button>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <?php 
    }

    /**
     * Save the custom meta box data
     *
     * @param int $post_id The ID of the post being saved
     * @param WP_Post $post The post object
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Single nonce check
        if ( !isset( $_POST[$this->nonce_name] ) || !wp_verify_nonce( sanitize_key( $_POST[$this->nonce_name] ), $this->nonce_action ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( $post->post_type !== 'linkcentral_link' ) {
            return;
        }
        // Check if the title is empty
        if ( empty( $_POST['post_title'] ) ) {
            wp_delete_post( $post_id, true );
            set_transient( 'linkcentral_title_error', true, 45 );
            wp_redirect( admin_url( 'post-new.php?post_type=linkcentral_link' ) );
            exit;
        }
        // Save link fields
        $fields = [
            'linkcentral_destination_url',
            'linkcentral_nofollow',
            'linkcentral_sponsored',
            'linkcentral_redirection_type'
        ];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                $value = '';
                // Sanitize based on field type
                if ( $field === 'linkcentral_destination_url' ) {
                    $value = esc_url_raw( wp_unslash( $_POST[$field] ) );
                    if ( $value === 'https://' ) {
                        delete_post_meta( $post_id, "_{$field}" );
                        continue;
                    }
                } else {
                    $value = sanitize_text_field( wp_unslash( $_POST[$field] ) );
                }
                update_post_meta( $post_id, "_{$field}", $value );
            }
        }
        // Handle slug uniqueness
        if ( isset( $_POST['post_name'] ) ) {
            $slug = sanitize_title( wp_unslash( $_POST['post_name'] ) );
            // Temporarily remove the save_post action to prevent infinite loop
            remove_action(
                'save_post',
                array($this, 'save_meta_boxes'),
                10,
                2
            );
            $slug = wp_unique_post_slug(
                $slug,
                $post_id,
                $post->post_status,
                'linkcentral_link',
                $post->post_parent
            );
            wp_update_post( array(
                'ID'        => $post_id,
                'post_name' => $slug,
            ) );
            // Re-add the save_post action
            add_action(
                'save_post',
                array($this, 'save_meta_boxes'),
                10,
                2
            );
        }
        // Save the note
        if ( isset( $_POST['linkcentral_note'] ) ) {
            $note = sanitize_textarea_field( wp_unslash( $_POST['linkcentral_note'] ) );
            update_post_meta( $post_id, '_linkcentral_note', $note );
        }
    }

    private function sanitize_dynamic_rules( $rules ) {
        $sanitized_rules = [];
        foreach ( $rules as $rule ) {
            $sanitized_rule = [
                'variables'   => [],
                'destination' => esc_url_raw( $rule['destination'] ),
            ];
            foreach ( $rule['variables'] as $variable ) {
                $sanitized_variable = [sanitize_text_field( $variable[0] ), sanitize_text_field( $variable[1] ), ( is_array( $variable[2] ) ? array_map( 'sanitize_text_field', $variable[2] ) : sanitize_text_field( $variable[2] ) )];
                $sanitized_rule['variables'][] = $sanitized_variable;
            }
            $sanitized_rules[] = $sanitized_rule;
        }
        return $sanitized_rules;
    }

    /**
     * Register custom REST API fields for the link post type
     */
    public function register_rest_fields() {
        register_rest_field( 'linkcentral_link', 'slug', array(
            'get_callback' => function ( $object ) {
                return $object['slug'];
            },
            'schema'       => array(
                'description' => __( 'Slug for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'destination_url', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_destination_url', true );
            },
            'schema'       => array(
                'description' => __( 'Destination URL for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'nofollow', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_nofollow', true );
            },
            'schema'       => array(
                'description' => __( 'Nofollow setting for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'sponsored', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_sponsored', true );
            },
            'schema'       => array(
                'description' => __( 'Sponsored setting for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'redirection_type', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_redirection_type', true );
            },
            'schema'       => array(
                'description' => __( 'Redirection type for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'note', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_note', true );
            },
            'schema'       => array(
                'description' => __( 'Administrative note for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'parameter_forwarding', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_parameter_forwarding', true );
            },
            'schema'       => array(
                'description' => __( 'Parameter forwarding setting for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'css_classes_option', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_css_classes_option', true );
            },
            'schema'       => array(
                'description' => __( 'CSS classes setting for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
        register_rest_field( 'linkcentral_link', 'custom_css_classes', array(
            'get_callback' => function ( $object ) {
                return get_post_meta( $object['id'], '_linkcentral_custom_css_classes', true );
            },
            'schema'       => array(
                'description' => __( 'Custom CSS classes setting for the link', 'linkcentral' ),
                'type'        => 'string',
            ),
        ) );
    }

    /**
     * Display an error message if the link title is empty
     */
    public function show_title_error() {
        if ( get_transient( 'linkcentral_title_error' ) ) {
            ?>
            <div class="error">
                <p><?php 
            esc_html_e( 'Error: A title is required. The link was not saved.', 'linkcentral' );
            ?></p>
            </div>
            <?php 
            delete_transient( 'linkcentral_title_error' );
        }
    }

    /**
     * Add a meta box to display the How to Use for the link
     */
    public function add_how_to_use_meta_box() {
        add_meta_box(
            'linkcentral_how_to_use',
            __( 'How to Use This Link', 'linkcentral' ),
            array($this, 'render_how_to_use_meta_box'),
            'linkcentral_link',
            'side',
            'high'
        );
    }

    /**
     * Render the How to Use meta box
     *
     * @param WP_Post $post The current post object
     */
    public function render_how_to_use_meta_box( $post ) {
        $shortcode = sprintf( '[linkcentral id="%d"]Your text[/linkcentral]', $post->ID );
        ?>
        <h4 style="margin-bottom:0;">A. <?php 
        esc_html_e( 'Smart Insert (Recommended)', 'linkcentral' );
        ?></h4>
        <small><?php 
        esc_html_e( 'This will automatically sync changes to your link.', 'linkcentral' );
        ?></small>
        <ol type="A">
            <li><?php 
        esc_html_e( 'Use the built-in LinkCentral insert button in your page editor.', 'linkcentral' );
        ?></li>
            <li><?php 
        esc_html_e( 'Or use shortcodes:', 'linkcentral' );
        ?>
                <div class="linkcentral-shortcode-container">
                    <code><?php 
        echo esc_html( $shortcode );
        ?></code>
                    <a href="#" class="linkcentral-copy-shortcode" data-shortcode="<?php 
        echo esc_attr( $shortcode );
        ?>"><?php 
        esc_html_e( 'Copy', 'linkcentral' );
        ?></a>
                </div>
                <small><?php 
        esc_html_e( 'For new tab, add: ', 'linkcentral' );
        ?><pre style="display:inline;">newtab="true"</pre></small>
                <?php 
        ?>
            </li>
        </ol>
        <p></p>
        <hr>
        <h4 style="margin-bottom:0;">B. <?php 
        esc_html_e( 'Manual Insert', 'linkcentral' );
        ?></h4>
        <small><?php 
        esc_html_e( 'Copy & paste the URL into your content directly. This won\'t automatically sync changes to links.', 'linkcentral' );
        ?></small>
        <?php 
    }

    /**
     * Delete tracking data when a link is permanently deleted
     *
     * @param int $post_id The ID of the post being deleted
     */
    public function delete_tracking_data_on_link_deletion( $post_id ) {
        // Check if the setting is enabled
        if ( !get_option( 'linkcentral_delete_tracking_data_on_link_deletion', true ) ) {
            return;
        }
        // Check if the post type is 'linkcentral_link'
        if ( get_post_type( $post_id ) !== 'linkcentral_link' ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'linkcentral_stats';
        // Delete tracking data for the link
        $wpdb->delete( $table_name, array(
            'link_id' => $post_id,
        ) );
    }

    /**
     * Customize the post updated messages for the link post type
     *
     * @param array $messages The existing post update messages
     * @return array The modified post update messages
     */
    public function custom_post_updated_messages( $messages ) {
        global $post, $post_ID;
        $messages['linkcentral_link'] = array(
            0  => '',
            1  => __( 'Link updated.', 'linkcentral' ),
            2  => __( 'Custom field updated.', 'linkcentral' ),
            3  => __( 'Custom field deleted.', 'linkcentral' ),
            4  => __( 'Link updated.', 'linkcentral' ),
            5  => ( isset( $_GET['revision'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'view-post-revision_' . $post_ID ) ? sprintf( 
                // Translators: %s is the revision date
                __( 'Link restored to revision from %s', 'linkcentral' ),
                wp_post_revision_title( (int) sanitize_text_field( wp_unslash( $_GET['revision'] ) ), false )
             ) : false ),
            6  => __( 'Link published.', 'linkcentral' ),
            7  => __( 'Link saved.', 'linkcentral' ),
            8  => __( 'Link submitted.', 'linkcentral' ),
            9  => sprintf( __( 'Link scheduled for: <strong>%1$s</strong>.', 'linkcentral' ), date_i18n( __( 'M j, Y @ G:i', 'linkcentral' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Link draft updated (link is inactive).', 'linkcentral' ),
        );
        return $messages;
    }

    /**
     * Add custom admin header for LinkCentral pages
     */
    public function add_admin_header() {
        $screen = get_current_screen();
        if ( $screen->post_type === 'linkcentral_link' || $screen->id === 'edit-linkcentral_category' ) {
            do_action( 'linkcentral_admin_header' );
        }
    }

    /**
     * Handle AJAX request to check and generate a unique slug
     */
    public function ajax_check_slug() {
        check_ajax_referer( 'linkcentral_admin_nonce', 'nonce' );
        $slug = ( isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '' );
        $post_id = ( isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0 );
        if ( empty( $slug ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid slug.', 'linkcentral' ),
            ) );
        }
        $unique_slug = wp_unique_post_slug(
            $slug,
            $post_id,
            'publish',
            'linkcentral_link',
            0
        );
        wp_send_json_success( array(
            'unique_slug' => $unique_slug,
        ) );
    }

    /**
     * Remove 'pending' status for our custom post type.
     *
     * @param array   $statuses List of post statuses.
     * @param WP_Post $post     The post object.
     * @return array Modified list of post statuses.
     */
    public function remove_post_statuses( $statuses, $post ) {
        if ( $post && $post->post_type === 'linkcentral_link' ) {
            unset($statuses['pending']);
        }
        return $statuses;
    }

    /**
     * Prevent changing the post status to 'pending'.
     *
     * @param array $data    An array of slashed, sanitized, and processed post data.
     * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
     * @return array Modified post data.
     */
    public function prevent_status_change( $data, $postarr ) {
        if ( $data['post_type'] === 'linkcentral_link' ) {
            if ( in_array( $data['post_status'], ['pending'] ) ) {
                $data['post_status'] = 'draft';
            }
        }
        return $data;
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook The current admin page hook
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
            return;
        }
        global $post;
        if ( 'linkcentral_link' !== $post->post_type ) {
            return;
        }
        wp_enqueue_script(
            'linkcentral-dynamic-redirect-modal',
            LINKCENTRAL_PLUGIN_URL . 'assets/js/dynamic-redirect-modal.js',
            array('jquery'),
            LINKCENTRAL_VERSION,
            true
        );
        // Get the countries data
        $countries = linkcentral_get_countries();
        // Get the current geolocation service
        $geolocation_service = get_option( 'linkcentral_geolocation_service', 'none' );
        // Localize the script with new data
        wp_localize_script( 'linkcentral-dynamic-redirect-modal', 'linkcentral_data', array(
            'countries'                          => $countries,
            'can_use_premium_code__premium_only' => linkcentral_fs()->can_use_premium_code__premium_only(),
            'geolocation_service'                => $geolocation_service,
        ) );
    }

}
