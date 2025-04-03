<?php
/**
 * Handles the admin settings for the Points Plus plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register plugin settings.
 */
function points_plus_register_settings() {
    register_setting( 'points_plus_settings_group', 'points_plus_api_key' );
    // Add more settings as needed
}
add_action( 'admin_init', 'points_plus_register_settings' );

/**
 * Render the settings page content.
 */
function points_plus_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Points Plus Settings', 'points-plus' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'points_plus_settings_group' );
            do_settings_sections( 'points_plus_settings_group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'points-plus' ); ?></th>
                    <td>
                        <input type="text" name="points_plus_api_key" value="<?php echo esc_attr( get_option( 'points_plus_api_key' ) ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Enter your API key (if applicable).', 'points-plus' ); ?></p>
                    </td>
                </tr>
                </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}