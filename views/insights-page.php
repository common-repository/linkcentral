<?php

/**
 * Template for the LinkCentral Insights page in the WordPress admin.
 *
 * This template displays various statistics and data about link usage,
 * including total clicks, most popular links, and recent click activity.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
do_action( 'linkcentral_admin_header' );
?>

<div class="wrap linkcentral-wrapper linkcentral-insights">
    <h1><?php 
esc_html_e( 'Insights', 'linkcentral' );
?></h1>
    
    <?php 
/**
 * Total Clicks Section
 * 
 * Displays a chart showing total clicks over time.
 */
?>
    <div class="linkcentral-stats-container">
        <h2><?php 
esc_html_e( 'Total Clicks', 'linkcentral' );
?></h2>
        <div class="linkcentral-stats-controls">
            <div class="left-controls">
                <button id="linkcentral-all-links" class="button"><?php 
esc_html_e( 'All Links', 'linkcentral' );
?></button>
                <p class="linkcentral-or-text"><?php 
esc_html_e( 'or', 'linkcentral' );
?></p>
                <?php 
?>
                    <div class="linkcentral-premium-feature">
                        <input type="text" id="linkcentral-link-search" placeholder="<?php 
esc_attr_e( 'Search for a specific link', 'linkcentral' );
?>" disabled>
                        <a href="<?php 
echo esc_url( admin_url( 'admin.php?page=linkcentral-settings#premium' ) );
?>" class="linkcentral-premium-tag to-input-field"><?php 
esc_html_e( 'Premium', 'linkcentral' );
?></a>
                    </div>
                <?php 
?>
            </div>
            <div class="right-controls">
                <select id="linkcentral-timeframe">
                    <option value="7"><?php 
esc_html_e( 'Last 7 days', 'linkcentral' );
?></option>
                    <option value="30"><?php 
esc_html_e( 'Last 30 days', 'linkcentral' );
?></option>
                    <option value="365"><?php 
esc_html_e( 'Last year', 'linkcentral' );
?></option>
                    <option value="custom"><?php 
esc_html_e( 'Custom range', 'linkcentral' );
?></option>
                </select>
                <div id="linkcentral-custom-range" style="display:none;">
                    <div class="custom-range-inputs">
                        <input type="date" id="linkcentral-date-from" aria-label="<?php 
esc_attr_e( 'From date', 'linkcentral' );
?>">
                        <input type="date" id="linkcentral-date-to" aria-label="<?php 
esc_attr_e( 'To date', 'linkcentral' );
?>">
                        <button id="linkcentral-apply-custom" class="button"><?php 
esc_html_e( 'Apply', 'linkcentral' );
?></button>
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 300px;">
            <div id="linkcentral-total-clicks-chart"></div>
        </div>
    </div>

    <?php 
/**
 * Most Popular Links Section
 * 
 * Displays a table of the most clicked links.
 */
?>
    <div class="linkcentral-top-links-container">
        <h2><?php 
esc_html_e( 'Most Popular Links', 'linkcentral' );
?></h2>
        <div class="linkcentral-top-links-controls">
            <select id="linkcentral-top-links-timeframe">
                <option value="1"><?php 
esc_html_e( 'Last 24 hours', 'linkcentral' );
?></option>
                <option value="7" selected><?php 
esc_html_e( 'Last 7 days', 'linkcentral' );
?></option>
                <option value="30"><?php 
esc_html_e( 'Last 30 days', 'linkcentral' );
?></option>
                <option value="365"><?php 
esc_html_e( 'Last year', 'linkcentral' );
?></option>
                <option value="all"><?php 
esc_html_e( 'All time', 'linkcentral' );
?></option>
            </select>
        </div>
        <?php 
$track_unique_visitors = get_option( 'linkcentral_track_unique_visitors', false );
?>
        <table class="wp-list-table widefat fixed striped" id="linkcentral-top-links-table">
            <thead>
                <tr>
                    <th class="column-title"><?php 
esc_html_e( 'Name', 'linkcentral' );
?></th>
                    <th class="column-slug"><?php 
esc_html_e( 'Slug', 'linkcentral' );
?></th>
                    <th class="column-destination_url"><?php 
esc_html_e( 'Destination URL', 'linkcentral' );
?></th>
                    <th class="column-total-clicks"><?php 
esc_html_e( 'Total Clicks', 'linkcentral' );
?></th>
                    <?php 
if ( $track_unique_visitors ) {
    ?>
                        <th class="column-unique-clicks"><?php 
    esc_html_e( 'Unique Clicks', 'linkcentral' );
    ?></th>
                    <?php 
}
?>
                </tr>
            </thead>
            <tbody>
                <?php 
foreach ( $initial_top_links_data['links'] as $link ) {
    ?>
                    <?php 
    $dynamic_rules = get_post_meta( $link->ID, '_linkcentral_dynamic_rules', true );
    $dynamic_indicator = ( linkcentral_fs()->can_use_premium_code__premium_only() && !empty( $dynamic_rules ) ? ' <span class="dashicons dashicons-randomize" title="' . esc_attr__( 'Dynamic redirects enabled', 'linkcentral' ) . '"></span>' : '' );
    ?>
                    <tr class="<?php 
    echo ( $link->is_deleted ? 'linkcentral-deleted-link' : (( $link->is_trashed ? 'linkcentral-trashed-link' : '' )) );
    ?>">
                        <td class="column-title">
                            <?php 
    if ( !$link->is_deleted ) {
        $edit_link = get_edit_post_link( $link->ID );
        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $link->post_title ) . '</a>';
    } else {
        echo esc_html( 'Deleted Link' );
    }
    if ( $link->is_deleted ) {
        echo ' <span class="dashicons dashicons-no" title="' . esc_attr__( 'This link has been deleted', 'linkcentral' ) . '"></span>';
    }
    if ( $link->is_trashed ) {
        echo ' <span class="dashicons dashicons-trash" title="' . esc_attr__( 'This link is in the trash', 'linkcentral' ) . '"></span>';
    }
    ?>
                        </td>
                        <td class="column-slug"><?php 
    echo esc_html( ( $link->is_deleted ? '' : '/' . $link->slug ) );
    ?></td>
                        <td class="column-destination_url"><?php 
    echo esc_url( ( $link->is_deleted ? '' : $link->destination_url ) );
    echo esc_html( $dynamic_indicator );
    ?></td>
                        <td class="column-total-clicks"><?php 
    echo esc_html( $link->total_clicks );
    ?></td>
                        <?php 
    if ( $track_unique_visitors ) {
        ?>
                            <td class="column-unique-clicks"><?php 
        echo esc_html( $link->unique_clicks );
        ?></td>
                        <?php 
    }
    ?>
                    </tr>
                <?php 
}
?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php 
$start = ($initial_top_links_data['current_page'] - 1) * $initial_top_links_data['items_per_page'] + 1;
$end = min( $initial_top_links_data['current_page'] * $initial_top_links_data['items_per_page'], $initial_top_links_data['total_items'] );
echo esc_html( "{$start}-{$end} of {$initial_top_links_data['total_items']} items" );
?>
                </span>
                <span class="pagination-links">
                    <a class="first-page button <?php 
echo ( $initial_top_links_data['current_page'] <= 1 ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'First page', 'linkcentral' );
?></span><span aria-hidden="true">&laquo;</span></a>
                    <a class="prev-page button <?php 
echo ( $initial_top_links_data['current_page'] <= 1 ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Previous page', 'linkcentral' );
?></span><span aria-hidden="true">&lsaquo;</span></a>
                    <span class="paging-input">
                        <label for="top-links-current-page" class="screen-reader-text"><?php 
esc_html_e( 'Current Page', 'linkcentral' );
?></label>
                        <input class="current-page" id="top-links-current-page" type="text" name="paged" value="<?php 
echo esc_attr( $initial_top_links_data['current_page'] );
?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span class="total-pages"><?php 
echo esc_html( $initial_top_links_data['total_pages'] );
?></span></span>
                    </span>
                    <a class="next-page button <?php 
echo ( $initial_top_links_data['current_page'] >= $initial_top_links_data['total_pages'] ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Next page', 'linkcentral' );
?></span><span aria-hidden="true">&rsaquo;</span></a>
                    <a class="last-page button <?php 
echo ( $initial_top_links_data['current_page'] >= $initial_top_links_data['total_pages'] ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Last page', 'linkcentral' );
?></span><span aria-hidden="true">&raquo;</span></a>
                </span>
            </div>
        </div>
    </div>

    <?php 
/**
 * Recent Clicks Section
 * 
 * Displays a table of recent click activity.
 */
?>
    <div class="linkcentral-recent-clicks-container">
        <h2 class="linkcentral-section-title"><?php 
esc_html_e( 'Recent Clicks', 'linkcentral' );
?></h2>
        <table class="wp-list-table widefat fixed striped" id="linkcentral-recent-clicks-table">
            <thead>
                <tr>
                    <th class="column-title"><?php 
esc_html_e( 'Name', 'linkcentral' );
?></th>
                    <th class="column-slug"><?php 
esc_html_e( 'Slug', 'linkcentral' );
?></th>
                    <th class="column-referring_url"><?php 
esc_html_e( 'Referring URL', 'linkcentral' );
?></th>
                    <th class="column-destination_url"><?php 
esc_html_e( 'Destination URL', 'linkcentral' );
?></th>
                    <?php 
if ( $track_user_agent ) {
    ?>
                        <th class="column-user-agent"><?php 
    esc_html_e( 'User Agent', 'linkcentral' );
    ?></th>
                    <?php 
}
?>
                    <th class="column-timestamp"><?php 
esc_html_e( 'Click Timestamp', 'linkcentral' );
?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
foreach ( $initial_recent_clicks_data['clicks'] as $click ) {
    ?>
                    <tr class="<?php 
    echo ( $click->is_deleted ? 'linkcentral-deleted-link' : (( $click->is_trashed ? 'linkcentral-trashed-link' : '' )) );
    ?>">
                        <td class="column-title">
                            <?php 
    if ( !$click->is_deleted ) {
        $edit_link = get_edit_post_link( $click->link_id );
        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $click->post_title ) . '</a>';
    } else {
        echo esc_html( 'Deleted Link' );
    }
    if ( $click->is_deleted ) {
        echo ' <span class="dashicons dashicons-no" title="' . esc_attr__( 'This link has been deleted', 'linkcentral' ) . '"></span>';
    }
    if ( $click->is_trashed ) {
        echo ' <span class="dashicons dashicons-trash" title="' . esc_attr__( 'This link is in the trash', 'linkcentral' ) . '"></span>';
    }
    ?>
                        </td>
                        <td class="column-slug"><?php 
    echo esc_html( ( $click->is_deleted ? '' : '/' . $click->slug ) );
    ?></td>
                        <td class="column-referring_url"><?php 
    echo esc_url( ( $click->is_deleted ? '' : $click->referring_url ) );
    ?></td>
                        <td class="column-destination_url"><?php 
    echo esc_url( $click->destination_url );
    ?></td>
                        <?php 
    if ( $track_user_agent ) {
        ?>
                            <td class="column-user-agent">
                                <?php 
        if ( !empty( $click->user_agent_info['browser'] ) && !empty( $click->user_agent_info['device'] ) ) {
            ?>
                                    <span class="browser-icon browser-<?php 
            echo esc_attr( strtolower( $click->user_agent_info['browser'] ) );
            ?>" title="<?php 
            echo esc_attr( $click->user_agent_info['browser'] );
            ?>"></span>
                                    <span class="dashicons <?php 
            echo esc_attr( $click->user_agent_info['device_icon'] );
            ?>" title="<?php 
            echo esc_attr( $click->user_agent_info['device'] );
            ?>"></span>
                                    <span class="os-info"><?php 
            echo esc_html( $click->user_agent_info['os'] );
            ?></span>
                                <?php 
        } else {
            ?>
                                    -
                                <?php 
        }
        ?>
                            </td>
                        <?php 
    }
    ?>
                        <td class="column-timestamp"><?php 
    echo esc_html( $click->formatted_date );
    ?></td>
                    </tr>
                <?php 
}
?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php 
$start = ($initial_recent_clicks_data['current_page'] - 1) * $initial_recent_clicks_data['items_per_page'] + 1;
$end = min( $initial_recent_clicks_data['current_page'] * $initial_recent_clicks_data['items_per_page'], $initial_recent_clicks_data['total_items'] );
echo esc_html( "{$start}-{$end} of {$initial_recent_clicks_data['total_items']} items" );
?>
                </span>
                <span class="pagination-links">
                    <a class="first-page button <?php 
echo ( $initial_recent_clicks_data['current_page'] <= 1 ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'First page', 'linkcentral' );
?></span><span aria-hidden="true">&laquo;</span></a>
                    <a class="prev-page button <?php 
echo ( $initial_recent_clicks_data['current_page'] <= 1 ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Previous page', 'linkcentral' );
?></span><span aria-hidden="true">&lsaquo;</span></a>
                    <span class="paging-input">
                        <label for="recent-clicks-current-page" class="screen-reader-text"><?php 
esc_html_e( 'Current Page', 'linkcentral' );
?></label>
                        <input class="current-page" id="recent-clicks-current-page" type="text" name="paged" value="<?php 
echo esc_attr( $initial_recent_clicks_data['current_page'] );
?>" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span class="total-pages"><?php 
echo esc_html( $initial_recent_clicks_data['total_pages'] );
?></span></span>
                    </span>
                    <a class="next-page button <?php 
echo ( $initial_recent_clicks_data['current_page'] >= $initial_recent_clicks_data['total_pages'] ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Next page', 'linkcentral' );
?></span><span aria-hidden="true">&rsaquo;</span></a>
                    <a class="last-page button <?php 
echo ( $initial_recent_clicks_data['current_page'] >= $initial_recent_clicks_data['total_pages'] ? 'disabled' : '' );
?>" href="#"><span class="screen-reader-text"><?php 
esc_html_e( 'Last page', 'linkcentral' );
?></span><span aria-hidden="true">&raquo;</span></a>
                </span>
            </div>
        </div>
    </div>
</div>
