<?php
namespace PointsPlus\Admin;

add_action('admin_init', function () {
    if (
        !is_admin() ||
        !isset($_GET['export_students_redeems']) ||
        !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'export_students_redeems')
    ) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die('Permission denied');
    }

    require_once plugin_dir_path(__DIR__) . 'exports/students-redeems-export.php';

    // $result = \PointsPlus\Exports\generate_students_redeems_csv('pending', true);

    // pull out whatever filters the admin had set (may be empty strings)
    $filters = [
        'status'  => sanitize_text_field( $_GET['status_filter']  ?? '' ),
        'student' => intval( $_GET['student_filter'] ?? 0 ),
        'reward'  => intval( $_GET['reward_filter']  ?? 0 ),
        'claimed_from'  => sanitize_text_field( $_GET['claimed_from']  ?? '' ),
        'claimed_to'    => sanitize_text_field( $_GET['claimed_to']    ?? '' ),
    ];

    // pass _all_ the filters into the export function…
    $result = \PointsPlus\Exports\generate_students_redeems_csv( $filters, true );

    if (!$result) {
        // Redirect back to the list view with a custom query param
        wp_redirect(add_query_arg([
            'post_type' => 'students_redeems',
            'export_empty' => '1'
        ], admin_url('edit.php')));
    
        exit;
    }
});
