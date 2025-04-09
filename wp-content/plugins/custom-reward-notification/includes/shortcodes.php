<?php
/**
 * Shortcodes for displaying student header info.
 *
 * @package CustomRewardNotifications
 */

function crn_student_header_info_shortcode() {
    $target_email = 'nipunchamika11@gmail.com';
    $student_post_id = crn_get_student_post_id_by_email($target_email);
    $points = 0; 
    $coins = 0; 
    $unread_count = 0;

    if ( $student_post_id && function_exists('get_field') ) {
        $points = intval(get_field('points', $student_post_id) ?: 0);
        $coins  = intval(get_field('coins', $student_post_id) ?: 0);
        $notifications = get_field('student_notifications', $student_post_id);
        if ( is_array($notifications) ) {
            foreach ( $notifications as $note ) {
                if ( isset($note['is_read']) && ! $note['is_read'] ) {
                    $unread_count++;
                }
            }
        }
    }

    $output  = '<div class="student-header-info">';
    $output .= '<span class="student-points">Points: ' . esc_html($points) . '</span>';
    $output .= '<span class="student-coins">Coins: ' . esc_html($coins) . '</span>';
    $output .= '<div class="notification-bell-area" data-student-identifier="' . esc_attr($target_email) . '" style="position: relative;">';
    $output .= '<span class="student-notification-icon dashicons dashicons-bell"></span>';
    if ( $unread_count > 0 ) {
        $output .= '<span class="notification-count-badge">' . esc_html($unread_count) . '</span>';
    } else {
        $output .= '<span class="notification-count-badge" style="display: none;">0</span>';
    }
    $output .= '<div class="notifications-dropdown" style="display: none;"></div>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}
add_shortcode('student_header_info', 'crn_student_header_info_shortcode');
