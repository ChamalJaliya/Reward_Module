<?php
namespace PointsPlus\Cron;

defined('ABSPATH') || exit;

// Custom hook name:
const HOOK = 'pointsplus_daily_export';
const LOCK_KEY = 'pointsplus_export_lock';

/**
 * Initialize cron functionality
 */
function init(): void {
    // Register activation/deactivation hooks
    register_activation_hook(POINTSPLUS_PLUGIN_FILE, __NAMESPACE__ . '\\activate');
    register_deactivation_hook(POINTSPLUS_PLUGIN_FILE, __NAMESPACE__ . '\\deactivate');

    // Hook our handler to WP-Cron
    add_action(HOOK, __NAMESPACE__ . '\\handle_daily_export');

    // Add custom cron schedules
    add_filter('cron_schedules', function($schedules) {
        // Add daily schedule if not exists
        if (!isset($schedules['daily'])) {
            $schedules['daily'] = [
                'interval' => DAY_IN_SECONDS,
                'display'  => __('Once Daily', 'points-plus')
            ];
        }

        // Keep the every_minute option for testing if needed
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display'  => __('Every Minute', 'points-plus')
            ];
        }

        return $schedules;
    });
}

/**
 * Plugin activation handler
 */
function activate(): void {
    schedule_daily_export();
    error_log('[PointsPlus] Plugin activated - daily export scheduled');
}

/**
 * Plugin deactivation handler
 */
function deactivate(): void {
    clear_daily_export();
    error_log('[PointsPlus] Plugin deactivated - daily export cleared');
}

/**
 * Schedule a daily event at midnight
 */
function schedule_daily_export(): void {
    // First clear any existing schedule
    clear_daily_export();

    // Calculate next midnight
    $next_midnight = strtotime('tomorrow midnight');

    if (function_exists('as_schedule_recurring_action')) {
        // Use Action Scheduler if available
        as_schedule_recurring_action(
            $next_midnight,
            DAY_IN_SECONDS,
            HOOK,
            [],
            'pointsplus'
        );
        error_log('[PointsPlus] Scheduled daily export with Action Scheduler starting at ' . date('Y-m-d H:i:s', $next_midnight));
        return;
    }

    // Fallback to WP-Cron
    wp_schedule_event($next_midnight, 'daily', HOOK);
    error_log('[PointsPlus] Scheduled daily export starting at ' . date('Y-m-d H:i:s', $next_midnight));
}

/**
 * Clear the scheduled event.
 */
function clear_daily_export(): void {
    if (function_exists('as_unschedule_action')) {
        as_unschedule_all_actions(HOOK);
        error_log('[PointsPlus] Cleared all Action Scheduler events');
    }

    $timestamp = wp_next_scheduled(HOOK);
    if ($timestamp) {
        wp_unschedule_event($timestamp, HOOK);
        error_log('[PointsPlus] Cleared WP-Cron event scheduled at ' . date('Y-m-d H:i:s', $timestamp));
    }
}

/**
 * Callback: generate the CSV and email it to the admin.
 */
function handle_daily_export(): void {
    // Prevent concurrent execution
    if (get_transient(LOCK_KEY)) {
        error_log('[PointsPlus] Export already in progress, skipping');
        return;
    }

    set_transient(LOCK_KEY, true, 10 * MINUTE_IN_SECONDS);

    try {
        error_log('[PointsPlus] DAILY export started at ' . date('Y-m-d H:i:s'));

        // 1) Load the exporter
        $export_file = plugin_dir_path(__DIR__) . 'includes/exports/students-redeems-export.php';
        if (!file_exists($export_file)) {
            error_log("[PointsPlus] ERROR: export file missing: {$export_file}");
            return;
        }
        require_once $export_file;

        // 2) Create temporary CSV file
        $upload = wp_upload_dir();
        $file = trailingslashit($upload['basedir']) . 'pending-reloads-' . date('Ymd_His') . '.csv';

        $fp = fopen($file, 'w');
        if (!$fp) {
            error_log('[PointsPlus] ERROR: cannot open file for writing: ' . $file . ' Error: ' . error_get_last()['message']);
            return;
        }

        // Write CSV header
        fputcsv($fp, ['Student ID','Name','Email','Reward ID','Reward','Reload Value','Mobile','Claimed On','Status']);

        // 3) Stream data directly to file
        $row_count = \PointsPlus\Exports\generate_students_redeems_csv(
            ['status' => 'pending'],
            false, // don't stream to browser
            true,  // only reloads
            function($row) use ($fp) {
                fputcsv($fp, $row);
            }
        );

        fclose($fp);

        if ($row_count === 0) {
            @unlink($file);
            error_log('[PointsPlus] No pending reloads found; skipping email.');
            return;
        }

        error_log('[PointsPlus] Retrieved ' . $row_count . ' rows for CSV. File: ' . $file);

        // 4) Send the email
//        $to = get_option('admin_email');
//        $subject = 'Daily Pending Reloads Export — ' . date('Y-m-d');
//        $body = "This is an automated email from the Points Plus system.\n\n";
//        $body .= "Pending reload redemptions as of " . date('Y-m-d') . ":\n";
//        $body .= "- Total records: " . $row_count . "\n\n";
//        $body .= "— Differently.study";
//        $attachments = [$file];
        $timestamp = date( 'Y-m-d H:i:s' );

        $context = [
            'row_count' => $row_count,
            'timestamp' => $timestamp,
        ];

        $to = get_option( 'admin_email' );
        // $subject = 'Hourly Pending Reloads Export — ' . $timestamp;
        $subject = \PointsPlus\Emails\get_email_subject('export-summary', [ 'timestamp' => $timestamp ]);
        $body = \PointsPlus\Emails\get_email_body( 'export-summary', $context );
        $attachments = [ $file ];

        try {
            $sent = wp_mail($to, $subject, $body, [], $attachments);

            if ($sent) {
                error_log('[PointsPlus] Email sent successfully to ' . $to);
            } else {
                error_log('[PointsPlus] ERROR: wp_mail() failed sending to ' . $to);
            }
        } finally {
            // 5) Clean up file
            if (file_exists($file)) {
                if (unlink($file)) {
                    error_log('[PointsPlus] Temp CSV deleted: ' . $file);
                } else {
                    error_log('[PointsPlus] WARNING: could not delete temp CSV: ' . $file);
                }
            }
        }

        error_log('[PointsPlus] Daily export completed at ' . date('Y-m-d H:i:s'));
    } finally {
        delete_transient(LOCK_KEY);
    }
}