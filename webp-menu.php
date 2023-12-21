<?php
// creates a menu item in the admin menu under settings
function webp_plugin_menu()
{
    add_options_page('WebP Converter', 'WebP Converter', 'manage_options', 'webp-converter', 'webp_plugin_options');
}
add_action('admin_menu', 'webp_plugin_menu');

// creates the html for the settings page
function webp_plugin_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    echo '<div class="wrap">';
    echo '<p>Converts all the images in the Media Library to WebP.</p>';
    echo '<p>Click the button below to convert all images.</p>';
    echo '<form method="post" action="">';
    settings_fields('webp_plugin_options');
    do_settings_sections('webp_plugin_options');
    echo '<input name="convert_media_lib" class="button button-primary" type="submit" value="Convert" />';
    echo '</form>';
    echo '</div>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_media_lib'])) {
        convert_media_lib();
    }
}
?>
