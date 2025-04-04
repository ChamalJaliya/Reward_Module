<?php

/**
 * Customizes the admin list table for the Rule custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class Rules_Table {

    /**
     * Sets the column headers for the Rule list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_rule_columns( $columns ) {
        $new_columns = array();
        $new_columns['title'] = __( 'Rule Name', 'points-plus' );
        $new_columns['status'] = __( 'Status', 'points-plus' );
        $new_columns['trigger_event'] = __( 'Trigger Event', 'points-plus' );
        $new_columns['priority'] = __( 'Priority', 'points-plus' );
        $new_columns['description'] = __( 'Description', 'points-plus' );
        $new_columns['date'] = __( 'Date', 'points-plus' );
        return $new_columns;
    }

    /**
     * Populates the custom columns for the Rule list table.
     *
     * @param string $column  The name of the column.
     * @param int    $post_id The ID of the current post.
     */
    public static function populate_rule_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'status':
                $status = get_field( 'status', $post_id );
                echo $status ? ucfirst( $status ) : '-';
                break;
            case 'trigger_event':
                $trigger_event = get_field( 'trigger_event', $post_id );
                echo $trigger_event ? ucfirst( str_replace( '_', ' ', $trigger_event ) ) : '-'; // Replace underscores with spaces
                break;
            case 'priority':
                $priority = get_field( 'priority', $post_id );
                echo $priority !== '' ? $priority : '-';
                break;
            case 'description':
                $description = get_field( 'description', $post_id );
                echo $description ? wp_trim_words( $description, 20, '...' ) : '-';
                break;
        }
    }
}

// Hook into WordPress filters and actions to modify the admin list table
add_filter( 'manage_rule_posts_columns', array( __NAMESPACE__ . '\\Rules_Table', 'set_rule_columns' ) );
add_action( 'manage_rule_posts_custom_column', array( __NAMESPACE__ . '\\Rules_Table', 'populate_rule_columns' ), 10, 2 );