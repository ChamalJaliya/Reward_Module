<?php
/**
 * Notification functionality.
 *
 * @package CustomRewardNotifications
 */

// Helper: Add a notification for a student.
function crn_add_notification_to_student($student_post_id, $message) {
    if ( ! function_exists('get_field') || ! function_exists('update_field') || ! $student_post_id ) {
        return false;
    }
    $repeater_field_key = 'student_notifications';
    $notifications = get_field($repeater_field_key, $student_post_id) ?: array();
    if ( ! is_array($notifications) ) {
        $notifications = array();
    }
    $notifications[] = array(
        'message'   => $message,
        'is_read'   => false,
        'timestamp' => current_time('mysql'),
    );
    return update_field($repeater_field_key, $notifications, $student_post_id);
}

// AJAX Handler: Fetch notifications.
add_action('wp_ajax_fetch_student_notifications', 'crn_fetch_student_notifications_ajax');
add_action('wp_ajax_nopriv_fetch_student_notifications', 'crn_fetch_student_notifications_ajax');

function crn_fetch_student_notifications_ajax() {
    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'nipunchamika11@gmail.com';
    $student_post_id = crn_get_student_post_id_by_email($student_identifier);
    if ( ! $student_post_id || ! function_exists('get_field') ) {
        wp_send_json_error(['message' => 'Could not find student or ACF.']);
        return;
    }
    $notifications_data = get_field('student_notifications', $student_post_id);
    $notifications_to_send = array();

    if ( is_array($notifications_data) ) {
        // Reverse array to show newest first.
        $notifications_data = array_reverse($notifications_data, true);
        foreach ( $notifications_data as $index => $note ) {
            if ( isset($note['message'], $note['timestamp'], $note['is_read']) ) {
                $notifications_to_send[] = array(
                    'message'   => wp_kses_post($note['message']),
                    'timestamp' => esc_html($note['timestamp']),
                    'is_read'   => (bool)$note['is_read'],
                    'index'     => $index
                );
            }
        }
    }
    wp_send_json_success(['notifications' => $notifications_to_send]);
}

// AJAX Handler: Mark notification(s) as read.
add_action('wp_ajax_mark_notification_read', 'crn_mark_notification_read_ajax');
add_action('wp_ajax_nopriv_mark_notification_read', 'crn_mark_notification_read_ajax');

function crn_mark_notification_read_ajax() {
    $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'nipunchamika11@gmail.com';
    $notification_index = isset($_POST['notification_index']) ? intval($_POST['notification_index']) : -1;
    $mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';
    $student_post_id = crn_get_student_post_id_by_email($student_identifier);

    if ( ! $student_post_id || ! function_exists('get_field') || ! function_exists('update_field') ) {
        wp_send_json_error(['message' => 'Could not find student or ACF.']);
        return;
    }
    
    $repeater_field_key = 'student_notifications';
    $notifications = get_field($repeater_field_key, $student_post_id);
    $updated = false;
    $unread_count = 0;

    if ( is_array($notifications) ) {
        if ( $mark_all ) {
            foreach ( $notifications as $index => $note ) {
                if ( isset($note['is_read']) && ! $note['is_read'] ) {
                    $notifications[$index]['is_read'] = true;
                    $updated = true;
                }
            }
            $unread_count = 0;
        } elseif ( $notification_index >= 0 && isset($notifications[$notification_index]) ) {
            if ( isset($notifications[$notification_index]['is_read']) && ! $notifications[$notification_index]['is_read'] ) {
                $notifications[$notification_index]['is_read'] = true;
                $updated = true;
            }
            foreach ( $notifications as $note ) {
                if ( isset($note['is_read']) && ! $note['is_read'] ) {
                    $unread_count++;
                }
            }
        } else {
            foreach ( $notifications as $note ) {
                if ( isset($note['is_read']) && ! $note['is_read'] ) {
                    $unread_count++;
                }
            }
        }
        if ( $updated ) {
            $update_success = update_field($repeater_field_key, $notifications, $student_post_id);
            if ( ! $update_success ) {
                error_log("Failed to update notifications for student ID: " . $student_post_id);
            }
        }
        wp_send_json_success(['new_unread_count' => $unread_count]);
    } else {
        wp_send_json_error(['message' => 'No notifications found to update.']);
    }
}
