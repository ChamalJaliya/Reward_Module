<?php
/**
 * Enqueue plugin scripts and styles.
 *
 * @package CustomRewardNotifications
 */

// Enqueue our quest & notifications script.
function crn_enqueue_scripts() {

    wp_enqueue_script(
        'quest-handler',
        CRN_PLUGIN_URL . 'js/quest-handler.js', // We will move quest-handler.js into a new plugin folder later.
        array( 'jquery' ),
        '1.0',
        true
    );

    // Localize script to pass data from PHP to JS.
    wp_localize_script('quest-handler', 'quest_ajax_object', array(
        'ajax_url'           => admin_url('admin-ajax.php'),
        'play_nonce'         => wp_create_nonce('play_quest_nonce'),
        'fetch_nonce'        => wp_create_nonce('notification_nonce'),
        'mark_read_nonce'    => wp_create_nonce('notification_nonce'),
        // Hardcoded student identifier - adjust if needed.
        'student_identifier' => 'nipunchamika11@gmail.com'
    ));

    // Enqueue Dashicons if required for notification icons.
    wp_enqueue_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'crn_enqueue_scripts' );
