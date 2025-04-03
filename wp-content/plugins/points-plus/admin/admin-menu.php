<?php
/**
 * Creates the admin menu for the Points Plus plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add the admin menu.
 */
function points_plus_add_admin_menu() {
    add_menu_page(
        __( 'Points Plus', 'points-plus' ),
        __( 'Points Plus', 'points-plus' ),
        'manage_options',
        'points-plus',
        'points_plus_admin_dashboard',
        'dashicons-awards',
        25
    );

    // WordPress automatically creates the submenu for the custom post type "reward",
    // so we don't need to add a "Rewards List" submenu.

    // Add a settings submenu (we'll implement this later)
    add_submenu_page(
        'points-plus',
        __( 'Settings', 'points-plus' ),
        __( 'Settings', 'points-plus' ),
        'manage_options',
        'points-plus-settings',
        'points_plus_admin_settings_page'
    );
}
add_action( 'admin_menu', 'points_plus_add_admin_menu' );

/**
 * Render the admin dashboard page.
 */
function points_plus_admin_dashboard() {
    // We'll add content to this page later, for now, just a placeholder
    echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1><p>Welcome to Points Plus!</p></div>';
}

/**
 * Render the settings page.
 */
function points_plus_admin_settings_page() {
    // We'll add settings form later
    echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1><p>Points Plus Settings</p></div>';
}
?>