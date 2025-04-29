<?php
/**
 * Notification system functionality
 */

if (!function_exists('add_notification_to_student_cpt')) :
    /**
     * Adds a notification entry to a student CPT's ACF repeater field.
     *
     * @param int $student_post_id The Post ID of the student CPT.
     * @param string $message The notification message content.
     * @return bool True on success, false on failure.
     */
    function add_notification_to_student_cpt($student_post_id, $message) {
        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
            return false;
        }

        $repeater_field_key = 'student_notifications';
        $notifications = get_field($repeater_field_key, $student_post_id) ?: [];

        $notifications[] = [
            'message' => $message,
            'is_read' => false,
            'timestamp' => current_time('mysql'),
        ];

        return update_field($repeater_field_key, $notifications, $student_post_id);
    }
endif;

// AJAX handler for fetching notifications
add_action('wp_ajax_fetch_student_notifications', 'fetch_student_notifications_ajax');
add_action('wp_ajax_nopriv_fetch_student_notifications', 'fetch_student_notifications_ajax');

if (!function_exists('fetch_student_notifications_ajax')) :
    function fetch_student_notifications_ajax() {
        $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'cjaliya.sln2@gmail.com';
        $student_post_id = get_student_post_id_by_email($student_identifier);

        if (!$student_post_id || !function_exists('get_field')) {
            wp_send_json_error(['message' => 'Could not find student or ACF.']);
            return;
        }

        $repeater_field_key = 'student_notifications';
        $notifications_data = get_field($repeater_field_key, $student_post_id);
        $notifications_to_send = [];

        if (is_array($notifications_data)) {
            $notifications_data = array_reverse($notifications_data, true);

            foreach ($notifications_data as $index => $note) {
                if (isset($note['message']) && isset($note['timestamp']) && isset($note['is_read'])) {
                    $notifications_to_send[] = [
                        'message'   => wp_kses_post($note['message']),
                        'timestamp' => esc_html($note['timestamp']),
                        'is_read'   => (bool)$note['is_read'],
                        'index'     => $index
                    ];
                }
            }
        }

        wp_send_json_success(['notifications' => $notifications_to_send]);
    }
endif;

// AJAX handler for marking notifications as read
add_action('wp_ajax_mark_notification_read', 'mark_notification_read_ajax');
add_action('wp_ajax_nopriv_mark_notification_read', 'mark_notification_read_ajax');

if (!function_exists('mark_notification_read_ajax')) :
    function mark_notification_read_ajax() {
        $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'cjaliya.sln2@gmail.com';
        $notification_index = isset($_POST['notification_index']) ? intval($_POST['notification_index']) : -1;
        $mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';

        $student_post_id = get_student_post_id_by_email($student_identifier);

        if (!$student_post_id || !function_exists('get_field')) {
            wp_send_json_error(['message' => 'Could not find student or ACF.']);
            return;
        }

        $repeater_field_key = 'student_notifications';
        $notifications = get_field($repeater_field_key, $student_post_id);
        $updated = false;
        $unread_count = 0;

        if (is_array($notifications)) {
            if ($mark_all) {
                foreach ($notifications as $index => $note) {
                    if (isset($note['is_read']) && !$note['is_read']) {
                        $notifications[$index]['is_read'] = true;
                        $updated = true;
                    }
                }
                $unread_count = 0;
            } elseif ($notification_index >= 0 && isset($notifications[$notification_index])) {
                if (isset($notifications[$notification_index]['is_read']) && !$notifications[$notification_index]['is_read']) {
                    $notifications[$notification_index]['is_read'] = true;
                    $updated = true;
                }

                foreach ($notifications as $note) {
                    if (isset($note['is_read']) && !$note['is_read']) {
                        $unread_count++;
                    }
                }
            } else {
                foreach ($notifications as $note) {
                    if (isset($note['is_read']) && !$note['is_read']) {
                        $unread_count++;
                    }
                }
            }

            if ($updated) {
                update_field($repeater_field_key, $notifications, $student_post_id);
            }

            wp_send_json_success(['new_unread_count' => $unread_count]);
        } else {
            wp_send_json_error(['message' => 'No notifications found to update.']);
        }
    }
endif;