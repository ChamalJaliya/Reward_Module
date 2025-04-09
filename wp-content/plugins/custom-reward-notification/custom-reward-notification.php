<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://yourwebsite.com
 * @since             1.0.0
 * @package           Custom_Reward_Notification
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Reward & Notification System
 * Plugin URI:        https://https://yourwebsite.com
 * Description:       Handles quests, rewards, and notifications for students using custom AJAX and shortcodes.
 * Version:           1.0.0
 * Author:            Nipun Chamika
 * Author URI:        https://https://yourwebsite.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       custom-reward-notification
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
define( 'CUSTOM_REWARD_NOTIFICATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-reward-notification-activator.php
 */
function activate_custom_reward_notification() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-reward-notification-activator.php';
	Custom_Reward_Notification_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-reward-notification-deactivator.php
 */
function deactivate_custom_reward_notification() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-reward-notification-deactivator.php';
	Custom_Reward_Notification_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_custom_reward_notification' );
register_deactivation_hook( __FILE__, 'deactivate_custom_reward_notification' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-reward-notification.php';

/**
 * Define plugin constants.
 */
define( 'CRN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CRN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include all the necessary plugin files.
 */
require CRN_PLUGIN_DIR . 'includes/enqueue.php';
require CRN_PLUGIN_DIR . 'includes/quests.php';
require CRN_PLUGIN_DIR . 'includes/notifications.php';
require CRN_PLUGIN_DIR . 'includes/shortcodes.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_custom_reward_notification() {

	$plugin = new Custom_Reward_Notification();
	$plugin->run();

}
run_custom_reward_notification();
