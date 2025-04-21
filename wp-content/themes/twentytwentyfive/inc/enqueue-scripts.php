<?php
/**
 * Script and style enqueuing
 */

// **THIS IS ESSENTIAL!** It tells WordPress to run our function
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');

function my_theme_enqueue_scripts() {
    // 1. Enqueue Styles

    // Enqueue your main theme's style (if you have one) - Important to keep this
    wp_enqueue_style('my-theme-style', get_stylesheet_uri());

    // Enqueue reward system styles
    wp_enqueue_style(
        'reward-system-style',
        get_template_directory_uri() . '/assets/css/reward-system.css',
        array('my-theme-style', 'dashicons'), // Add 'dashicons' as a dependency if needed
        filemtime(get_template_directory() . '/assets/css/reward-system.css')
    );

    // 2. Enqueue Scripts
    wp_enqueue_script(
        'countdown-timer',
        get_template_directory_uri() . '/assets/js/countdown-timer.js',
        array('jquery'), // Add jQuery as dependency
        '1.0',
        true // Load in footer
    );
    // Enqueue quest-related scripts
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
        'student_identifier' => 'cjaliya.sln2@gmail.com' //  IMPORTANT:  Dynamically get the logged-in user's email
    ));

    // Enqueue reward-related scripts
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
            'student_identifier' => 'cjaliya.sln2@gmail.com', //  IMPORTANT:  Dynamically get the logged-in user's email
            'ajax_error_message' => __('An error occurred. Please try again.', 'your-theme-text-domain')
        )
    );

    // 3. Dashicons (Conditional Loading - Optional but Recommended)
    // It's often better to load Dashicons only when needed, not on every page.
    // Check if Dashicons are needed (e.g., if the shortcode is present)
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'student_header_info')) {
        wp_enqueue_style('dashicons');
    }
}