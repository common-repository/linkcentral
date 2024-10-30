<?php

/**
 * LinkCentral Links Overview Class
 *
 * This class extends WP_List_Table to create a custom table for displaying and managing LinkCentral links.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class LinkCentral_Links_Overview extends WP_List_Table {
    /**
     * URL prefix for short links
     */
    private $url_prefix;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Link', 'linkcentral' ),
            'plural'   => __( 'Links', 'linkcentral' ),
            'ajax'     => false,
        ] );
        $this->url_prefix = get_option( 'linkcentral_url_prefix', 'go' );
    }

    /**
     * Define the columns for the links table
     */
    public function get_columns() {
        return [
            'cb'              => '<input type="checkbox" />',
            'title'           => __( 'Name', 'linkcentral' ),
            'slug'            => __( 'Short URL', 'linkcentral' ),
            'destination_url' => __( 'Destination', 'linkcentral' ),
            'category'        => __( 'Category', 'linkcentral' ),
            'clicks'          => __( 'Clicks', 'linkcentral' ),
            'date'            => __( 'Date Created', 'linkcentral' ),
        ];
    }

    /**
     * Define sortable columns configuration
     */
    private function get_sortable_columns_config() {
        return [
            'title'  => ['title', false, 'title'],
            'date'   => ['date', true, 'date'],
            'clicks' => ['clicks', false, 'meta_value_num'],
        ];
    }

    /**
     * Define sortable columns for the table
     */
    public function get_sortable_columns() {
        $sortable = $this->get_sortable_columns_config();
        return array_combine( array_keys( $sortable ), array_map( function ( $item ) {
            return $item[0];
        }, $sortable ) );
    }

    /**
     * Prepare the items for the table to process
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $post_status = ( isset( $_GET['post_status'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['linkcentral_post_status_nonce'] ?? '' ) ), 'linkcentral_post_status' ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : 'any' );
        // Set up the query arguments
        $args = [
            'post_type'      => 'linkcentral_link',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'post_status'    => $post_status,
        ];
        // Handle sorting
        $sortable = $this->get_sortable_columns_config();
        $orderby = ( isset( $_GET['orderby'] ) && isset( $sortable[$_GET['orderby']] ) ? sanitize_key( $_GET['orderby'] ) : 'date' );
        $order = ( isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC' );
        $args['orderby'] = $sortable[$orderby][2];
        $args['order'] = $order;
        if ( $orderby === 'clicks' ) {
            $args['meta_key'] = '_linkcentral_click_count';
        }
        $query = new WP_Query($args);
        $this->items = $query->posts;
        $total_items = $query->found_posts;
        // Set up pagination
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }

    /**
     * Get the total number of links
     */
    private function get_total_links() {
        $args = [
            'post_type'      => 'linkcentral_link',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get links for a specific page
     *
     * @param int $per_page Number of items per page
     * @param int $page_number Current page number
     * @return array
     */
    private function get_links( $per_page, $page_number ) {
        $args = [
            'post_type'      => 'linkcentral_link',
            'posts_per_page' => $per_page,
            'paged'          => $page_number,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Default column rendering
     *
     * @param object $item Current item
     * @param string $column_name Name of the column
     * @return string
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'destination_url':
                $destination_url = get_post_meta( $item->ID, '_linkcentral_destination_url', true );
                $dynamic_rules = get_post_meta( $item->ID, '_linkcentral_dynamic_rules', true );
                $output = esc_url( $destination_url );
                return $output;
            case 'category':
                return get_the_term_list(
                    $item->ID,
                    'linkcentral_category',
                    '',
                    ', ',
                    ''
                );
            case 'date':
                return get_the_date( 'd F Y', $item->ID );
            default:
                return __( 'Unavailable', 'linkcentral' );
        }
    }

    /**
     * Render the checkbox column
     *
     * @param object $item Current item
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="link[]" value="%s" />', $item->ID );
    }

    /**
     * Render the title column
     *
     * @param object $item Current item
     * @return string
     */
    public function column_title( $item ) {
        $edit_link = get_edit_post_link( $item->ID );
        $title = '<strong><a class="row-title" href="' . $edit_link . '">' . esc_html( $item->post_title ) . '</a>';
        $status = get_post_status( $item->ID );
        $status_object = get_post_status_object( $status );
        if ( $status !== 'publish' ) {
            if ( $status === 'draft' ) {
                $status_label = __( 'Draft (inactive)', 'linkcentral' );
            } else {
                $status_label = $status_object->label;
            }
            $title .= ' &mdash; <span class="post-state">' . $status_label . '</span>';
        }
        if ( $item->post_password ) {
            $title .= ' <span class="dashicons dashicons-lock" title="' . esc_attr__( 'Password protected', 'linkcentral' ) . '"></span>';
        }
        $title .= '</strong>';
        $actions = [
            'id' => sprintf( '<span class="post-id">ID: %d</span>', $item->ID ),
        ];
        if ( $status === 'trash' ) {
            $actions['untrash'] = sprintf( '<a href="%s">%s</a>', wp_nonce_url( admin_url( sprintf( 'admin.php?page=linkcentral&action=untrash&link=%d', $item->ID ) ), 'untrash-link_' . $item->ID ), __( 'Untrash', 'linkcentral' ) );
            $actions['delete'] = sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                get_delete_post_link( $item->ID, '', true ),
                esc_js( __( 'You are about to permanently delete this link. This action cannot be undone. Are you sure?', 'linkcentral' ) ),
                __( 'Delete Permanently', 'linkcentral' )
            );
        } else {
            $actions['edit'] = sprintf( '<a href="%s">%s</a>', $edit_link, __( 'Edit', 'linkcentral' ) );
            $actions['trash'] = sprintf( '<a href="%s">%s</a>', get_delete_post_link( $item->ID ), __( 'Trash', 'linkcentral' ) );
        }
        return $title . $this->row_actions( $actions );
    }

    /**
     * Render the slug column
     *
     * @param object $item Current item
     * @return string
     */
    public function column_slug( $item ) {
        $short_url = home_url( '/' . $this->url_prefix . '/' . $item->post_name );
        return sprintf(
            '%s <button class="button button-small linkcentral-copy-url" data-url="%s">%s</button>',
            $short_url,
            esc_attr( $short_url ),
            __( 'Copy', 'linkcentral' )
        );
    }

    /**
     * Render the clicks column
     *
     * @param object $item Current item
     * @return string
     */
    public function column_clicks( $item ) {
        $click_count = $this->get_click_count( $item->ID );
        // Update the click count in post meta for sorting
        update_post_meta( $item->ID, '_linkcentral_click_count', $click_count );
        return sprintf( '<span class="linkcentral-click-count" data-link-id="%d">%d</span>', $item->ID, $click_count );
    }

    /**
     * Get the click count for a specific link
     *
     * @param int $link_id Link ID
     * @return int
     */
    private function get_click_count( $link_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}linkcentral_stats` WHERE link_id = %d", $link_id ) );
    }

    /**
     * Define bulk actions
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [];
        $post_status = ( isset( $_GET['post_status'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['linkcentral_post_status_nonce'] ?? '' ) ), 'linkcentral_post_status' ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : 'any' );
        if ( $post_status === 'trash' ) {
            $actions['untrash'] = __( 'Untrash', 'linkcentral' );
            $actions['delete'] = __( 'Delete Permanently', 'linkcentral' );
        } else {
            $actions['trash'] = __( 'Move to Trash', 'linkcentral' );
        }
        return $actions;
    }

    /**
     * Generate views for different post statuses
     *
     * @return array
     */
    protected function get_views() {
        $status_links = array();
        $num_posts = wp_count_posts( 'linkcentral_link', 'readable' );
        $class = '';
        $allposts = '';
        // Calculate total posts excluding 'trash' and 'auto-draft'
        $total_posts = array_sum( (array) $num_posts ) - $num_posts->trash - $num_posts->{'auto-draft'};
        $class = ( empty( $class ) && empty( $_REQUEST['post_status'] ) ? ' class="current"' : '' );
        $all_text = sprintf( 
            /* translators: %s: number of links */
            __( 'All <span class="count">(%s)</span>', 'linkcentral' ),
            number_format_i18n( $total_posts )
         );
        $status_links['all'] = sprintf( '<a href="admin.php?page=linkcentral"%s>%s</a>', $class, $all_text );
        $statuses = get_post_stati( array(
            'show_in_admin_status_list' => true,
        ), 'objects' );
        if ( $statuses ) {
            foreach ( $statuses as $status ) {
                $class = '';
                $status_name = $status->name;
                if ( !in_array( $status_name, array(
                    'publish',
                    'draft',
                    'pending',
                    'trash',
                    'future',
                    'private',
                    'auto-draft'
                ) ) ) {
                    continue;
                }
                if ( empty( $num_posts->{$status_name} ) ) {
                    continue;
                }
                if ( isset( $_REQUEST['post_status'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['linkcentral_post_status_nonce'] ?? '' ) ), 'linkcentral_post_status' ) && $status_name === $_REQUEST['post_status'] ) {
                    $class = ' class="current"';
                }
                $url = wp_nonce_url( admin_url( "admin.php?page=linkcentral&post_status={$status_name}" ), 'linkcentral_post_status', 'linkcentral_post_status_nonce' );
                $label = $status->label_count['singular'];
                if ( $num_posts->{$status_name} > 1 ) {
                    $label = $status->label_count['plural'];
                }
                $count = number_format_i18n( $num_posts->{$status_name} );
                $status_links[$status_name] = sprintf(
                    '<a href="%s"%s>%s</a>',
                    esc_url( $url ),
                    $class,
                    sprintf( $label, $count )
                );
            }
        }
        return $status_links;
    }

    /**
     * Render the main links overview page
     */
    public static function render_all_links_page() {
        $list_table = new self();
        $list_table->prepare_items();
        $post_status = ( isset( $_GET['post_status'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['linkcentral_post_status_nonce'] ?? '' ) ), 'linkcentral_post_status' ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : 'any' );
        $trash_url = wp_nonce_url( admin_url( 'admin.php?page=linkcentral&post_status=trash' ), 'linkcentral_post_status', 'linkcentral_post_status_nonce' );
        $all_url = admin_url( 'admin.php?page=linkcentral' );
        // Display admin notices
        self::display_admin_notices();
        include LINKCENTRAL_PLUGIN_DIR . 'views/links-overview-page.php';
    }

    /**
     * Initialize the class
     */
    public static function init() {
        add_action( 'admin_init', array(__CLASS__, 'process_bulk_action') );
    }

    /**
     * Process bulk actions
     */
    public static function process_bulk_action() {
        if ( !isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'linkcentral' ) {
            return;
        }
        $list_table = new self();
        $action = $list_table->current_action();
        if ( empty( $action ) ) {
            return;
        }
        // Check nonce for all actions
        $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
        if ( !wp_verify_nonce( $nonce, 'bulk-' . $list_table->_args['plural'] ) && !wp_verify_nonce( $nonce, 'untrash-link_' . (( isset( $_GET['link'] ) ? absint( $_GET['link'] ) : '' )) ) ) {
            wp_die( 'Security check failed' );
        }
        $post_ids = [];
        if ( isset( $_REQUEST['link'] ) && wp_verify_nonce( $nonce, 'bulk-' . $list_table->_args['plural'] ) ) {
            $post_ids = ( is_array( $_REQUEST['link'] ) ? array_map( 'intval', $_REQUEST['link'] ) : [intval( $_REQUEST['link'] )] );
        }
        // Handle individual untrash action
        if ( $action === 'untrash' && isset( $_GET['link'] ) && wp_verify_nonce( $nonce, 'untrash-link_' . absint( $_GET['link'] ) ) ) {
            $post_ids = [intval( $_GET['link'] )];
        }
        if ( empty( $post_ids ) ) {
            return;
        }
        $current_url = add_query_arg( array() );
        // Get current URL with all parameters
        $redirect_url = remove_query_arg( ['action', 'link', '_wpnonce'], $current_url );
        // Remove action-related parameters
        $processed_count = 0;
        switch ( $action ) {
            case 'trash':
                foreach ( $post_ids as $post_id ) {
                    if ( wp_trash_post( $post_id ) ) {
                        $processed_count++;
                    }
                }
                $redirect_url = add_query_arg( 'trashed', $processed_count, $redirect_url );
                break;
            case 'untrash':
                foreach ( $post_ids as $post_id ) {
                    if ( wp_untrash_post( $post_id ) ) {
                        $processed_count++;
                    }
                }
                $redirect_url = add_query_arg( 'untrashed', $processed_count, $redirect_url );
                break;
            case 'delete':
                foreach ( $post_ids as $post_id ) {
                    if ( wp_delete_post( $post_id, true ) ) {
                        $processed_count++;
                    }
                }
                $redirect_url = add_query_arg( 'deleted', $processed_count, $redirect_url );
                break;
        }
        // Perform the redirect
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Display admin notices for bulk actions
     */
    private static function display_admin_notices() {
        $messages = array(
            'trashed'   => array(
                'singular' => __( '%s link moved to the Trash.', 'linkcentral' ),
                'plural'   => __( '%s links moved to the Trash.', 'linkcentral' ),
                'type'     => 'updated',
            ),
            'untrashed' => array(
                'singular' => __( '%s link restored from the Trash.', 'linkcentral' ),
                'plural'   => __( '%s links restored from the Trash.', 'linkcentral' ),
                'type'     => 'updated',
            ),
            'deleted'   => array(
                'singular' => __( '%s link permanently deleted.', 'linkcentral' ),
                'plural'   => __( '%s links permanently deleted.', 'linkcentral' ),
                'type'     => 'updated',
            ),
        );
        foreach ( $messages as $key => $message ) {
            if ( isset( $_REQUEST[$key] ) && wp_verify_nonce( wp_create_nonce( 'linkcentral_admin_notice' ), 'linkcentral_admin_notice' ) ) {
                $count = intval( $_REQUEST[$key] );
                $text = ( $count === 1 ? $message['singular'] : $message['plural'] );
                $notice = sprintf( $text, number_format_i18n( $count ) );
                echo "<div class='notice " . esc_attr( $message['type'] ) . " is-dismissible'><p>" . esc_html( $notice ) . "</p></div>";
            }
        }
    }

}
