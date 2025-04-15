<?php
/**
 * Script and style enqueuing
 */

// Enqueue quest-related scripts
add_action('wp_enqueue_scripts', 'my_enqueue_quest_scripts');

if (!function_exists('my_enqueue_quest_scripts')) :
    function my_enqueue_quest_scripts() {
        wp_enqueue_script(
            'quest-handler',
            get_template_directory_uri() . '/assets/js/quest-handler.js',
            array('jquery'),
            '1.1',
            true
        );

        wp_localize_script('quest-handler', 'quest_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'play_nonce' => wp_create_nonce('play_quest_nonce'),
            'fetch_nonce' => wp_create_nonce('notification_nonce'),
            'mark_read_nonce' => wp_create_nonce('notification_nonce'),
            'student_identifier' => 'nipunchamika11@gmail.com'
        ));

        wp_enqueue_style('dashicons');
    }
endif;

// Enqueue reward-related scripts
add_action('wp_enqueue_scripts', 'my_enqueue_reward_scripts');

if (!function_exists('my_enqueue_reward_scripts')) :
    function my_enqueue_reward_scripts() {
        wp_enqueue_script(
            'reward-handler',
            get_template_directory_uri() . '/assets/js/reward-handler.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script(
            'reward-handler',
            'reward_ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
                'daily_reward_nonce'  => wp_create_nonce('daily_reward_nonce'),
                'student_identifier' => 'nipunchamika11@gmail.com',
                'ajax_error_message' => __('An error occurred. Please try again.', 'your-theme-text-domain')
            )
        );

        wp_enqueue_style('dashicons');
    }
endif;

// Ensure Dashicons are loaded
add_action('wp_enqueue_scripts', 'enqueue_dashicons_front_end');

if (!function_exists('enqueue_dashicons_front_end')) :
    function enqueue_dashicons_front_end() {
        wp_enqueue_style('dashicons');
    }
endif;