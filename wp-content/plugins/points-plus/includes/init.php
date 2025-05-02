<?php
/**
 * Initialize Points Plus plugin components
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'class-student-data.php';

// Register initialization hook
add_action('plugins_loaded', function() {
    // Make sure theme functions are loaded first
    do_action('points_plus_init');
});