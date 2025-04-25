<?php
namespace PointsPlus\Exports;

/**
 * Generates CSV data for student redemptions with streaming support
 *
 * @param array $filters [
 *   'status'  => '', 'pending', 'processed', 'completed', 'failed',
 *   'student' => 0 (all) or student ID,
 *   'reward'  => 0 (all) or reward ID,
 *   'claimed_from' => date string,
 *   'claimed_to' => date string
 * ]
 * @param bool $stream_to_browser Whether to stream directly to browser
 * @param bool $only_reload Whether to only include reload-type rewards
 * @param callable|null $stream_callback Optional callback for streaming rows
 * @return array|int|false Returns array of rows, row count, or false if none
 */
function generate_students_redeems_csv(
    array $filters = [],
    bool $stream_to_browser = true,
    bool $only_reload = false,
    callable $stream_callback = null
) {
    // Build meta query
    $meta_query = [];

    // Status filter
    if (!empty($filters['status'])) {
        $meta_query[] = [
            'key'     => 'status',
            'value'   => $filters['status'],
            'compare' => '=',
        ];
    }

    // Student filter (ACF stores field as serialized array)
    if (!empty($filters['student'])) {
        $meta_query[] = [
            'key'     => 'student',
            'value'   => '"' . (int)$filters['student'] . '"',
            'compare' => 'LIKE',
        ];
    }

    // Reward filter (raw OR in array)
    if (!empty($filters['reward'])) {
        $rid = (int)$filters['reward'];
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

    // Claimed On date range
    if (!empty($filters['claimed_from']) || !empty($filters['claimed_to'])) {
        $from = $filters['claimed_from'] ? $filters['claimed_from'] . ' 00:00:00' : '';
        $to = $filters['claimed_to'] ? $filters['claimed_to'] . ' 23:59:59' : '';

        $meta_query[] = [
            'key'     => 'claimed_timestamp',
            'value'   => [$from, $to],
            'compare' => 'BETWEEN',
            'type'    => 'DATETIME',
        ];
    }

    // Base query args
    $query_args = [
        'post_type'      => 'students_redeems',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids', // Only get IDs to reduce memory usage
    ];

    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    $query = new \WP_Query($query_args);
    $row_count = 0;

    // Handle streaming to browser
    if ($stream_to_browser && !$stream_callback) {
        $filename = 'students_redeems_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student ID', 'Name', 'Email', 'Reward ID', 'Reward', 'Reload Value', 'Mobile', 'Claimed On', 'Status']);
    }

    // Process posts
    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            // Get student ID
            $student_field = get_field('student', $post_id, false); // Get raw value
            $student_id = 0;

            if (is_array($student_field)) {
                $student_id = (int)($student_field[0] ?? 0);
            } elseif (is_numeric($student_field)) {
                $student_id = (int)$student_field;
            }

            // Get reward ID
            $reward_field = get_field('reward_item', $post_id, false); // Get raw value
            $reward_id = 0;

            if (is_array($reward_field)) {
                $reward_id = (int)($reward_field[0] ?? 0);
            } elseif (is_numeric($reward_field)) {
                $reward_id = (int)$reward_field;
            }

            // Skip if missing required data
            if (!$student_id || !$reward_id) {
                continue;
            }

            // Skip non-reload rewards if requested
            if ($only_reload) {
                $promotion_type = get_field('promotion_type', $reward_id);
                if ($promotion_type !== 'reload') {
                    continue;
                }
            }

            // Get student details
            $first_name = ucfirst((string)get_field('first_name', $student_id));
            $last_name = ucfirst((string)get_field('last_name', $student_id));
            $full_name = trim("$first_name $last_name");
            $email = (string)get_field('email', $student_id);
            $mobile = (string)get_field('mobile_number', $student_id);

            // Get redemption details
            $claimed_on = (string)get_field('claimed_timestamp', $post_id);
            $reload_field = get_field('reload_value', $reward_id);
            $reload_value = is_object($reload_field) ? $reload_field->post_title : (string)$reload_field;
            $status_value = (string)get_field('status', $post_id);
            $reward_title = get_the_title($reward_id);

            // Build row data
            $row = [
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

            // Handle output based on mode
            if ($stream_callback) {
                $stream_callback($row);
                $row_count++;
            } elseif ($stream_to_browser) {
                fputcsv($output, $row);
                $row_count++;
            } else {
                $rows[] = $row;
            }
        }

        wp_reset_postdata();
    }

    // Close browser stream if used
    if ($stream_to_browser && !$stream_callback) {
        fclose($output);
        exit;
    }

    // Return appropriate result based on mode
    if ($stream_callback) {
        return $row_count;
    }

    if (empty($rows)) {
        return false;
    }

    // Sort by Reload Value
    usort($rows, function($a, $b) {
        return (int)$a[5] <=> (int)$b[5];
    });

    return $rows;
}