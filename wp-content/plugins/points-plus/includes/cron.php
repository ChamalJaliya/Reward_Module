<?php
namespace PointsPlus\Cron;

defined( 'ABSPATH' ) || exit;

// Custom hook name:
const HOOK = 'pointsplus_daily_export';

/**
 * Schedule a daily event at midnight.
 */
function schedule_daily_export(): void {
    if ( ! wp_next_scheduled( HOOK ) ) {
        // first run = tomorrow at 00:00 server time
        $first_run = strtotime( 'tomorrow midnight' );
        // $first_run = time() + 10; // in one minute
        wp_schedule_event( $first_run, 'daily', HOOK );
        // wp_schedule_event( $first_run, 'every_minute', HOOK );
        error_log( '[PointsPlus Cron] Scheduled daily export at ' . date( 'Y-m-d H:i:s', $first_run ) );
    } else {
        error_log( message: '[PointsPlus Cron] schedule_daily_export(): already scheduled' );
    }
}

/**
 * Clear the scheduled event.
 */
function clear_daily_export(): void {
    $timestamp = wp_next_scheduled( HOOK );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, HOOK );
        error_log( '[PointsPlus Cron] Cleared daily export scheduled at ' . date( 'Y-m-d H:i:s', $timestamp ) );
    } else {
        error_log( '[PointsPlus Cron] clear_daily_export(): nothing to clear' );
    }
}

/**
 * Callback: generate the CSV and email it to the admin.
 */
function handle_daily_export(): void {
    error_log( '[PointsPlus Cron] handle_daily_export(): start' );

    // 1) Load the exporter
    $export_file = plugin_dir_path( __DIR__ ) . 'includes/exports/students-redeems-export.php';
    if ( ! file_exists( $export_file ) ) {
        error_log( "[PointsPlus Cron] ERROR: export file missing: {$export_file}" );
        return;
    }
    require_once $export_file;

    // 2) Fetch pending reload-only rows (don't stream to browser)
    $filters = [ 'status' => 'pending' ];
    $rows    = \PointsPlus\Exports\generate_students_redeems_csv( $filters, false, true );

    if ( empty( $rows ) ) {
        error_log( '[PointsPlus Cron] No pending reloads found; skipping email.' );
        return; // nothing to send
    }
    error_log( '[PointsPlus Cron] Retrieved ' . count( $rows ) . ' rows for CSV.' );

    // 3) Build a temporary CSV file in uploads/
    $upload = wp_upload_dir();
    $file   = trailingslashit( $upload['basedir'] ) . 'pending-reloads-' . date( 'Ymd_His' ) . '.csv';
    $fp     = @fopen( $file, 'w' );
    if ( ! $fp ) {
        error_log( '[PointsPlus Cron] ERROR: cannot open file for writing: ' . $file );
        return;
    }

    fputcsv( $fp, [ 'Student ID','Name','Email','Reward ID','Reward','Reload Value','Mobile','Claimed On','Status' ] );
    foreach ( $rows as $row ) {
        fputcsv( $fp, $row );
    }
    fclose( $fp );
    error_log( '[PointsPlus Cron] CSV written to: ' . $file );

    // 4) Send the email
    $to          = get_option( 'admin_email' );
    // $to          = 'nipunchamika11@gmail.com';
    $subject     = 'Daily Pending Reloads Export — ' . date( 'Y-m-d' );
    $body        = "Hi there,\n\nPlease find attached today’s pending reload redemptions.\n\n— Differently.study";
    $attachments = [ $file ];

    $sent = wp_mail( $to, $subject, $body, [], $attachments );

    if ( $sent ) {
        error_log( '[PointsPlus Cron] Email sent successfully to ' . $to );
    } else {
        error_log( '[PointsPlus Cron] ERROR: wp_mail() failed sending to ' . $to );
    }

    // 5) Clean up file
    if ( @unlink( $file ) ) {
        error_log( '[PointsPlus Cron] Temp CSV deleted: ' . $file );
    } else {
        error_log( '[PointsPlus Cron] WARNING: could not delete temp CSV: ' . $file );
    }

    error_log( '[PointsPlus Cron] handle_daily_export(): end' );
}

// Hook our handler to WP‑Cron
add_action( HOOK, __NAMESPACE__ . '\\handle_daily_export' );

add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['every_minute'] ) ) {
        $schedules['every_minute'] = [
            'interval' => 30,
            'display'  => __( 'Every Minute', 'points-plus' )
        ];
    }
    return $schedules;
});
