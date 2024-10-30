<?php
/**
 * The template for displaying the password form for protected LinkCentral links
 *
 * This template creates a custom password form page for LinkCentral protected links.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

// Enqueue the password form styles
function linkcentral_enqueue_password_form_styles() {
    wp_enqueue_style('linkcentral-password-form-styles', LINKCENTRAL_PLUGIN_URL . 'assets/css/password-form.css', array(), LINKCENTRAL_VERSION);
}
add_action('wp_enqueue_scripts', 'linkcentral_enqueue_password_form_styles');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('linkcentral-password-container'); ?>>
    <div class="linkcentral-password-form">
        <h1><?php esc_html_e('Password Protected', 'linkcentral'); ?></h1>
        <p><?php esc_html_e('This link is password protected. To view it please enter your password below.', 'linkcentral'); ?></p>

        <?php
        $post = get_post();
        $label = 'pwbox-' . ( empty($post->ID) ? wp_rand() : $post->ID );
        ?>

        <form action="<?php echo esc_url(site_url('wp-login.php?action=postpass', 'login_post')); ?>" class="post-password-form" method="post">
            <p>
                <label for="<?php echo esc_attr($label); ?>">Password: <input name="post_password" id="<?php echo esc_attr($label); ?>" type="password" spellcheck="false" size="20" /></label>
                <input type="submit" name="Submit" value="<?php esc_attr_e('Submit', 'linkcentral'); ?>" />
            </p>
        </form>

    </div>
    <?php wp_footer(); ?>
</body>
</html>