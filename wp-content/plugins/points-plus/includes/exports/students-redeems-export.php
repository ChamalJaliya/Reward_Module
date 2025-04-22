<?php
namespace PointsPlus\Exports;

/**
 * @param array $filters [
 *   'status'  => '', 'pending', 'processed', 'completed', 'failed',
 *   'student' => 0 (all) or student ID,
 *   'reward'  => 0 (all) or reward ID,
 * ]
 * @param bool  $stream_to_browser
 * @param bool  $only_reload      if true, skip any redemption whose parent reward isn't a reload‑type
 * @return array|false            rows or false if none
 */
function generate_students_redeems_csv(array $filters = [], $stream_to_browser = true, $only_reload = false) {
    $rows = [];
    $meta_query = [];

    // --- status filter ---
    if ( ! empty( $filters['status'] ) ) {
        $meta_query[] = [
            'key'     => 'status',
            'value'   => $filters['status'],
            'compare' => '=',
        ];
    }

    // --- student filter (ACF stores field as serialized array) ---
    if ( ! empty( $filters['student'] ) ) {
        $meta_query[] = [
            'key'     => 'student',
            'value'   => '"' . intval( $filters['student'] ) . '"',
            'compare' => 'LIKE',
        ];
    }

    // --- reward filter (raw OR in array) ---
    if ( ! empty( $filters['reward'] ) ) {
        $rid = intval( $filters['reward'] );
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'reward_item',
                'value'   => '"' . $rid . '"',
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'reward_item',
                'value'   => $rid,
                'compare' => '=',
            ],
        ];
    }

    // --- Claimed On range in export ---
    if ( ! empty( $filters['claimed_from'] ) || ! empty( $filters['claimed_to'] ) ) {
        $from = $filters['claimed_from'] ? $filters['claimed_from'] . ' 00:00:00' : '';
        $to   = $filters['claimed_to']   ? $filters['claimed_to']   . ' 23:59:59' : '';

        $meta_query[] = [
            'key'     => 'claimed_timestamp',
            'value'   => [ $from, $to ],
            'compare' => 'BETWEEN',
            'type'    => 'DATETIME',
        ];
    }

    // base WP_Query args
    $query_args = [
        'post_type'      => 'students_redeems',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $query = new \WP_Query( $query_args );

    // build rows
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $post_id = $post->ID;

            // normalize student ID
            $student_field = get_field('student', $post_id);
            $student_id = 0;

            if (is_array($student_field)) {
                $first = $student_field[0] ?? null;
                if (is_object($first) && isset($first->ID)) {
                    $student_id = intval($first->ID);
                } elseif (is_numeric($first)) {
                    $student_id = intval($first);
                }
            } elseif (is_object($student_field) && isset($student_field->ID)) {
                $student_id = intval($student_field->ID);
            } elseif (is_numeric($student_field)) {
                $student_id = intval($student_field);
            }

            // reward ID (ACF single or array)
            $reward_id = get_field('reward_item', $post_id);
            if (is_array($reward_id)) $reward_id = $reward_id[0] ?? null;
            $reward_id = intval($reward_id);

            // bail if either missing
            if (!$student_id || !$reward_id) continue;

            // only‑reload check, if requested
            if ( $only_reload ) {
                $promotion_type = get_field('promotion_type', $reward_id);
                if ($promotion_type !== 'reload') continue; // Only export reloads
            }

            // Get Student details
            $first_name = ucfirst(get_field('first_name', $student_id));
            $last_name  = ucfirst(get_field('last_name', $student_id));
            $full_name  = trim($first_name . ' ' . $last_name);
            $email      = get_field('email', $student_id);
            $mobile        = get_field('mobile_number', $student_id);

            $claimed_on       = get_field('claimed_timestamp', $post_id);
            $reload_field = get_field('reload_value', $reward_id);
            $reload_value = is_object($reload_field) ? $reload_field->post_title : $reload_field;

            $status_value  = get_field('status', $post_id);

            $reward_title = get_the_title( $reward_id );

            $rows[] = [
                $student_id,
                $full_name,
                $email,
                $reward_id,
                $reward_title,
                $reload_value,
                $mobile,
                $claimed_on,
                $status_value
            ];
        }
    }

    // If no matching rows, return false
    if (empty($rows)) {
        return false;
    }

    if ($stream_to_browser) {
        $filename = 'students_redeems_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV header
        fputcsv($output, ['Student ID', 'Name', 'Email', 'Reward ID', 'Reward', 'Reload Value', 'Mobile Number', 'Claimed On', 'Status']);
        // fputcsv($output, ['Student', 'Student ID', 'Reward', 'Reward ID', 'Mobile Number', 'Claimed On', 'Status']); // Optional: old version

        foreach ($rows as $row) {
            $safe_row = array_map(function ($value) {
                if (is_object($value)) return method_exists($value, '__toString') ? (string)$value : '';
                if (is_array($value)) return json_encode($value); // optional
                return $value;
            }, $row);

            fputcsv($output, $safe_row);
        }

        fclose($output);
        exit;
    }

    return $rows;
}
