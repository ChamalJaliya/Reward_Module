<?php
/**
 * Enqueue scripts and styles for the Points Plus plugin.
 *
 * @package Points_Plus
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue plugin scripts and styles
 */
function points_plus_enqueue_scripts() {
    // Get plugin directory URL and path
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_path = plugin_dir_path(__FILE__);

    // ======================
    // 1. Enqueue CSS Styles
    // ======================

    // Main reward system CSS
    $reward_css = '/assets/css/reward-system.css';
    if (file_exists($plugin_path . $reward_css)) {
        wp_enqueue_style(
            'points-plus-reward-style',
            $plugin_url . $reward_css,
            array('dashicons'), // Dashicons dependency
            filemtime($plugin_path . $reward_css) // Version based on file modification time
        );
    }
    $reward_modal_css = '/assets/css/reward-modal.css';
    if (file_exists($plugin_path . $reward_modal_css)) {
        wp_enqueue_style(
            'points-plus-reward-modal-style',
            $plugin_url . $reward_modal_css,
            filemtime($plugin_path . $reward_modal_css)
        );
    }
    $alert_css = '/assets/css/alert.css';
    if (file_exists($plugin_path . $alert_css)) {
        wp_enqueue_style(
            'points-plus-alert-style',
            $plugin_url . $alert_css,
            filemtime($plugin_path . $alert_css)
        );
    }

    // Admin-specific CSS (only loads in admin)
    if (is_admin()) {
        $admin_css = '/assets/css/admin-style.css';
        if (file_exists($plugin_path . $admin_css)) {
            wp_enqueue_style(
                'points-plus-admin-style',
                $plugin_url . $admin_css,
                array('dashicons'),
                filemtime($plugin_path . $admin_css)
            );
        }
    }

    // Only load the notifications list CSS on front-end singular pages that actually have the shortcode
    if ( ! is_admin() && is_singular() ) {
        global $post;
        if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'notifications_list' ) ) { 
            // Notifications list shortcode CSS
            $notifications_css = '/assets/css/notifications-list.css';
            if (file_exists($plugin_path . $notifications_css)) {
                wp_enqueue_style(
                    'points-plus-notifications-list',
                    $plugin_url . $notifications_css,
                    array('dashicons'),
                    filemtime($plugin_path . $notifications_css)
                );
            }
        }
    }

    // ======================
    // 2. Enqueue JavaScript
    // ======================

    // Countdown Timer
    $countdown_js = '/assets/js/countdown-timer.js';
    if (file_exists($plugin_path . $countdown_js)) {
        wp_enqueue_script(
            'points-plus-countdown-timer',
            $plugin_url . $countdown_js,
            array('jquery'), // jQuery dependency
            filemtime($plugin_path . $countdown_js),
            true // Load in footer
        );
    }
    wp_enqueue_script('your-plugin-script', plugin_dir_url(__FILE__) . 'js/your-script.js', ['jquery'], null, true);
    wp_localize_script('your-plugin-script', 'reward_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
        'get_reward_modal_nonce' => wp_create_nonce('get_reward_modal_nonce'), // Add this line
        'student_identifier' => get_current_user_id(), // Or however you identify the student
    ));
    // Reward Handler with AJAX localization
    $reward_js = '/assets/js/reward-handler.js';
    if (file_exists($plugin_path . $reward_js)) {
        wp_enqueue_script(
            'points-plus-reward-handler',
            $plugin_url . $reward_js,
            array('jquery'),
            filemtime($plugin_path . $reward_js),
            true
        );

        wp_localize_script(
            'points-plus-reward-handler',
            'reward_ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
                'daily_reward_nonce' => wp_create_nonce('daily_reward_nonce'),
                'student_identifier' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
                'i18n' => array(
                    'error_message' => __('An error occurred. Please try again.', 'points-plus'),
                    'success_message' => __('Action completed successfully!', 'points-plus')
                )
            )
        );
    }

    // Admin-specific JS (only loads in admin)
    if (is_admin()) {
        $admin_js = '/assets/js/admin-table.js';
        if (file_exists($plugin_path . $admin_js)) {
            wp_enqueue_script(
                'points-plus-admin-script',
                $plugin_url . $admin_js,
                array('jquery', 'wp-util'), // WP Utilities for admin
                filemtime($plugin_path . $admin_js),
                true
            );
        }
    }

    // Only load the notifications list JS on front-end singular pages that actually have the shortcode
    if ( ! is_admin() && is_singular() ) {
        global $post;
        if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'notifications_list' ) ) {
            // Notifications list shortcode JS
            $notifications_js = '/assets/js/notifications-list.js';
            if (file_exists($plugin_path . $notifications_js)) {
                wp_enqueue_script(
                    'points-plus-notifications-list-js',
                    $plugin_url . $notifications_js,
                    array('jquery'),
                    filemtime($plugin_path . $notifications_js),
                    true
                );

                // THIS SHOULD NOT HAPPEN
                $target_email = 'nipunchamika11@gmail.com';
                $student_id = 0;
                if ( function_exists( 'get_student_post_id_by_email' ) ) {
                    $student_id = get_student_post_id_by_email( $target_email );
                }

                wp_localize_script(
                    'points-plus-notifications-list-js',
                    'ppNotifications',
                    array(
                        'ajaxurl'    => admin_url( 'admin-ajax.php' ),
                        'student_id' => intval( $student_id ),      // or your session‐logic
                        'nonce'      => wp_create_nonce( 'pp_mark_notification_read' ),
                    )
                );
            }
        }
    }
}

// Hook into both frontend and admin
add_action('wp_enqueue_scripts', 'points_plus_enqueue_scripts');
add_action('admin_enqueue_scripts', 'points_plus_enqueue_scripts');

// Conditionally load editor styles for Gutenberg
add_action('enqueue_block_editor_assets', function() {
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_path = plugin_dir_path(__FILE__);

    $editor_css = '/assets/css/editor-style.css';
    if (file_exists($plugin_path . $editor_css)) {
        wp_enqueue_style(
            'points-plus-editor-style',
            $plugin_url . $editor_css,
            array('wp-edit-blocks'),
            filemtime($plugin_path . $editor_css)
        );
    }
});