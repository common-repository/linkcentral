<?php
/**
 * Template for the Links Overview Page
 *
 * This template displays the main overview of all LinkCentral links.
 */

// Ensure this file is being included by a parent file
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

// Display the admin header
do_action('linkcentral_admin_header');
?>

<div class="wrap linkcentral-wrapper linkcentral-overview">
    <h1 class="wp-heading-inline"><?php esc_html_e('All Links', 'linkcentral'); ?></h1>
    <?php 
    // Display the "Add New" button if not in trash view
    if ($post_status !== 'trash'): 
    ?>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=linkcentral_link')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'linkcentral'); ?></a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php
    // Display admin notices
    settings_errors('linkcentral_notices');
    ?>

    <ul class="subsubsub">
        <?php 
        // Display the list of post status views (All, Published, Trash, etc.)
        echo wp_kses_post(implode(' | ', $list_table->get_views())); 
        ?>
    </ul>

    <form method="post">
        <?php
        // Display the links table
        $list_table->display();
        ?>
    </form>
</div>