<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [notifications_list]
 */
add_shortcode('notifications_list', 'pp_notifications_list_shortcode');

function pp_notifications_list_shortcode() {
    // Enqueue styles and scripts
    $css_file = dirname(__FILE__) . '/../assets/css/notifications-list.css';
    $css_url  = plugin_dir_url(__FILE__) . '../assets/css/notifications-list.css';
    wp_enqueue_style('dashicons');

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'pp-notifications-list',
            $css_url,
            array(),
            filemtime($css_file)
        );
    }

    $js_file = dirname(__FILE__) . '/../assets/js/notifications-list.js';
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'pp-notifications-list-js',
            plugin_dir_url(__FILE__) . '../assets/js/notifications-list.js',
            array('jquery'),
            filemtime($js_file),
            true
        );
    }

    // Hard-coded email (MUST replace with session logic later)

    // Get student post
    if (!function_exists('get_student_post_id_by_email')) {
        return '<p class="notifications-error">Missing helper function</p>';
    }
    $student_id = Points_Plus_Student_Data::get_current_student_id();;
    if (!$student_id) {
        return '<p class="notifications-none">No student found.</p>';
    }

    // Get notifications with original indexes
    $notes = get_field('student_notifications', $student_id);
    if (!is_array($notes) || empty($notes)) {
        return '<p class="notifications-none">No notifications.</p>';
    }

    // Get all notifications
    $all_notes = get_field('student_notifications', $student_id);
    if (!is_array($all_notes) || empty($all_notes)) {
        return '<p class="notifications-none">You have no notifications.</p>';
    }

    // // Filter out read notifications
    // $notes = array_filter($all_notes, function($note) {
    //     return empty($note['is_read']);
    // });

    // Show ALL notifications (both read & unread)
    $notes = $all_notes;

    if (empty($notes)) {
        return '<p class="notifications-none">You have no notifications right now. We\'ll notify you when there is something new.</p>';
    }

    // Store original indexes and timestamps for sorting
    $sortable_notes = array();
    foreach ($notes as $original_index => $note) {
        $sortable_notes[] = array(
            'original_index' => $original_index + 1, // ACF rows are 1-based
            'timestamp' => strtotime($note['timestamp']),
            'data' => $note
        );
    }

    // Sort by timestamp descending
    usort($sortable_notes, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    // Count unread notifications
    $unread_count = array_reduce($notes, function($carry, $note) {
        return $carry + (empty($note['is_read']) ? 1 : 0);
    }, 0);

    // Build output
    $output = '<div class="pp-notifications-container" data-unread-count="' . esc_attr($unread_count) . '">';
    $output .= '<ul class="notifications-list">';

    foreach ($sortable_notes as $sorted_index => $sorted_note) {
        $note = $sorted_note['data'];
        $original_index = $sorted_note['original_index'];
        $is_read = !empty($note['is_read']);

        $classes = $is_read ? 'notification-item read' : 'notification-item unread';
        $date_str = '';

        if (!empty($note['timestamp'])) {
            $formatted = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($note['timestamp'])
            );
            $date_str = '<div class="notification-date">' . esc_html($formatted) . '</div>';
        }

        $output .= '<li class="' . esc_attr($classes) . '" 
            data-original-index="' . esc_attr($original_index) . '"
            data-sorted-index="' . esc_attr($sorted_index) . '">';

        $output .= '<div class="notification-content">';
        $output .= '<div class="notification-message">' . wp_kses_post($note['message']) . '</div>';
        $output .= $date_str;
        $output .= '</div>';
        $output .= '</li>';
    }

    $output .= '</ul></div>';

    wp_localize_script(
        'pp-notifications-list-js',
        'ppNotifications',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'student_id' => $student_id,
            'nonce' => wp_create_nonce('pp_mark_notification_read'),
            'no_notifications_message' => '<p class="notifications-none">You have no notifications right now. We\'ll notify you when there is something new.</p>'
        )
    );

    return $output;
}

// AJAX handler
add_action('wp_ajax_pp_mark_notification_read', 'pp_ajax_mark_notification_read');
add_action('wp_ajax_nopriv_pp_mark_notification_read', 'pp_ajax_mark_notification_read');

function pp_ajax_mark_notification_read() {
    // Verify nonce
    if (!check_ajax_referer('pp_mark_notification_read', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
        exit;
    }

    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $original_index = isset($_POST['row_index']) ? intval($_POST['row_index']) : 0;

    if (!$student_id || $original_index < 1) {
        wp_send_json_error('Invalid parameters');
        exit;
    }

    // Update using update_sub_field with 1-based index
    $success = update_sub_field(
        array('student_notifications', $original_index, 'is_read'),
        true,
        $student_id
    );

    if ($success) {
        wp_send_json_success();
    } else {
        // Add detailed error logging
        error_log('[Notifications] Update failed for:');
        error_log('Student ID: ' . $student_id);
        error_log('Original Index: ' . $original_index);
        error_log('Current Field Values: ' . print_r(get_field('student_notifications', $student_id), true));

        wp_send_json_error('Update failed');
    }
}