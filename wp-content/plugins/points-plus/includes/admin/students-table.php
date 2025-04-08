<?php

/**
 * Customizes the admin list table for the Student custom post type.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class Students_Table {

    /**
     * Sets the column headers for the Student custom post type list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_student_columns( $columns ) {
        $new_columns = array();
        $new_columns['title'] = __( 'Student Name', 'points-plus' ); // Or whatever you want to call it
        $new_columns['email'] = __( 'Email', 'points-plus' );
        $new_columns['first_name'] = __( 'First Name', 'points-plus' );
        $new_columns['last_name'] = __( 'Last Name', 'points-plus' );
        $new_columns['points'] = __( 'Points', 'points-plus' );
        $new_columns['keys']   = __( 'Keys', 'points-plus' );
        $new_columns['coins']  = __( 'Coins', 'points-plus' );
        $new_columns['status'] = __( 'Status', 'points-plus' );
        $new_columns['date']   = __( 'Date', 'points-plus' ); // Keep the date
        return $new_columns;
    }

    /**
     * Populates the custom columns for the Student custom post type list table.
     *
     * @param string $column  The name of the column.
     * @param int    $post_id The ID of the current post.
     */
    public static function populate_student_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'email':
                echo esc_html( get_field( 'email', $post_id ) );
                break;
            case 'first_name':
                echo esc_html( get_field( 'first_name', $post_id ) );
                break;
            case 'last_name':
                echo esc_html( get_field( 'last_name', $post_id ) );
                break;
            case 'points':
                echo esc_html( get_field( 'points', $post_id ) );
                break;
            case 'keys':
                echo esc_html( get_field( 'keys', $post_id ) );
                break;
            case 'coins':
                echo esc_html( get_field( 'coins', $post_id ) );
                break;
            case 'status':
                echo esc_html( get_field( 'status', $post_id ) );
                break;
            case 'date':
                echo esc_html( get_the_date( '', $post_id ) ); // Use get_the_date for formatted date
                break;
        }
    }
}

// Hook into WordPress filters and actions to modify the Student post type list table
add_filter( 'manage_student_posts_columns', array( __NAMESPACE__ . '\\Students_Table', 'set_student_columns' ) );
add_action( 'manage_student_posts_custom_column', array( __NAMESPACE__ . '\\Students_Table', 'populate_student_columns' ), 10, 2 );
