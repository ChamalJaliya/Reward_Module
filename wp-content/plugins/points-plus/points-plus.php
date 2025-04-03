<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://differently.study/
 * @since             1.0.0
 * @package           Points_Plus
 *
 * @wordpress-plugin
 * Plugin Name:       points-plus
 * Plugin URI:        https://differently.study/
 * Description:       A comprehensive WordPress plugin for managing and granting rewards to users based on various actions.

 * Version:           1.0.0
 * Author:            Chamal
 * Author URI:        https://differently.study//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       points-plus
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'POINTS_PLUS_VERSION', '1.0.0' );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-points-plus-activator.php
 */
function activate_points_plus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-activator.php';
	Points_Plus_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-points-plus-deactivator.php
 */
function deactivate_points_plus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-deactivator.php';
	Points_Plus_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_points_plus' );
register_deactivation_hook( __FILE__, 'deactivate_points_plus' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-points-plus.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

/**
 * Function to create custom database tables.
 */
function points_plus_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $user_rewards_table_name = $wpdb->prefix . 'points_plus_user_rewards';
    $sql_user_rewards = "CREATE TABLE IF NOT EXISTS $user_rewards_table_name (
        user_reward_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        reward_post_id BIGINT(20) UNSIGNED NOT NULL,
        awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        context TEXT,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
        FOREIGN KEY (reward_post_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_user_rewards );
}

add_action( 'init', 'points_plus_register_reward_post_type' );
/**
 * Registers the Reward custom post type.
 */
function points_plus_register_reward_post_type() {
    $labels = array(
        'name'               => _x( 'Rewards', 'Post Type General Name', 'points-plus' ),
        'singular_name'      => _x( 'Reward', 'Post Type Singular Name', 'points-plus' ),
        'menu_name'          => __( 'Rewards', 'points-plus' ),
        'parent_item_colon'  => __( 'Parent Reward:', 'points-plus' ),
        'all_items'          => __( 'All Rewards', 'points-plus' ),
        'view_item'          => __( 'View Reward', 'points-plus' ),
        'add_new_item'       => __( 'Add New', 'points-plus' ),
        'edit_item'          => __( 'Edit Reward', 'points-plus' ),
        'update_item'        => __( 'Update Reward', 'points-plus' ),
        'search_items'       => __( 'Search Rewards', 'points-plus' ),
        'not_found'          => __( 'No rewards found', 'points-plus' ),
        'not_found_in_trash' => __( 'No rewards found in Trash', 'points-plus' ),
    );
    $args = array(
        'label'               => __( 'Reward', 'points-plus' ),
        'description'         => __( 'Manage reward items for the Points Plus plugin.', 'points-plus' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'custom-fields' ), // We'll use ACF for custom fields
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-awards',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'rewrite'             => array( 'slug' => 'reward' ),
        'capability_type'     => 'post',
    );
    register_post_type( 'reward', $args );
}






function run_points_plus() {

	$plugin = new Points_Plus();
	$plugin->run();

}
run_points_plus();
