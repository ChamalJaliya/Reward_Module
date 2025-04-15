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

include_once plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-install.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-points-plus-activator.php
 */
function activate_points_plus(): void {
    include_once plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-activator.php';
    Points_Plus_Activator::activate();

    // Call function to create custom tables on activation
    if ( class_exists( 'Points_Plus_Install' ) ) { // Check if the class exists
        Points_Plus_Install::install();
    } else {
        error_log( 'Points_Plus_Install class not found!' ); // Log an error
    }
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-points-plus-deactivator.php
 */
function deactivate_points_plus(): void {
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
require plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-api.php'; // Include API handler
require plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-rule-engine.php'; // Include Rule Engine
require plugin_dir_path( __FILE__ ) . 'includes/class-points-plus-execution.php'; // Include Reward Execution

/**
 * Function to create custom database tables.
 */

add_action( 'init', 'points_plus_register_reward_post_type' );
/**
 * Registers the Reward custom post type.
 */
function points_plus_register_reward_post_type(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/rewards.php';
    \PointsPlus\PostTypes\Rewards::register();
}

add_action( 'init', 'points_plus_register_quest_post_type' ); // Register Quest CPT
/**
 * Registers the Quest custom post type.
 */
function points_plus_register_quest_post_type(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/quests.php';
    \PointsPlus\PostTypes\Quests::register(); // Call the function directly
}

add_action( 'init', 'points_plus_register_rule_post_type' );
/**
 * Registers the Rule custom post type.
 */
function points_plus_register_rule_post_type(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/rules.php';
    \PointsPlus\PostTypes\Rule_Builder::register();
}
add_action( 'init', 'points_plus_register_student_post_type' );
/**
 * Registers the Student custom post type.
 */
function points_plus_register_student_post_type(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/students.php';
    \PointsPlus\PostTypes\Students::register();
}
add_action( 'init', 'points_plus_register_students_redeems_post_type' );
/**
 * Registers the Students Redeems custom post type.
 */
function points_plus_register_students_redeems_post_type(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/students-redeems.php';
    \PointsPlus\PostTypes\StudentsRedeems::register();
}

//add_action( 'init', 'points_plus_register_acf_fields' );
/**
 * Registers the ACF fields.
 */
function points_plus_register_acf_fields(): void {
    if ( function_exists( 'acf_add_local_field_group' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/fields/reward-fields.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/fields/quest-fields.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/fields/rule-fields.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/fields/student-fields.php';

    }
}

add_action( 'init', 'points_plus_register_admin_tables' );
/**
 * Registers the admin table customizations.
 */
function points_plus_register_admin_tables(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/rewards-table.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/quests-table.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/rules-table.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/students-table.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/students-redeems-table.php';
}

add_action( 'init', 'points_plus_register_shortcodes' );
/**
 * Registers the shortcodes.
 */
function points_plus_register_shortcodes(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes/rewards.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes/quests.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes/rules.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes/students.php';
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */


function run_points_plus() {

	$plugin = new Points_Plus();
	$plugin->run();

}
run_points_plus();
