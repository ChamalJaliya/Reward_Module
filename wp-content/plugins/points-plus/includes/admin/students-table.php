<?php
/**
 * Customizes the admin list table for the Student post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class Students_Table {

    /**
     * Sets the column headers for the Student list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_student_columns( $columns ) {
        $new_columns = array();
        // You can change the order as needed.
        $new_columns['title'] = __( 'Email', 'points-plus' );
        $new_columns['first_name'] = __( 'First Name', 'points-plus' );
        $new_columns['last_name'] = __( 'Last Name', 'points-plus' );
        $new_columns['courses'] = __( 'Courses', 'points-plus' );
        $new_columns['points'] = __( 'Points', 'points-plus' );
        $new_columns['keys'] = __( 'Keys', 'points-plus' );
        $new_columns['coins'] = __( 'Coins', 'points-plus' );
        $new_columns['status'] = __( 'Status', 'points-plus' );
        $new_columns['mobile_number'] = __( 'Mobile Number', 'points-plus' );
        $new_columns['date'] = __( 'Date', 'points-plus' );
        return $new_columns;
    }

    /**
     * Populates the custom columns for the Student list table.
     *
     * @param string $column  The name of the column.
     * @param int    $post_id The ID of the current post.
     */
    public static function populate_student_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'first_name':
                $first_name = get_field( 'first_name', $post_id );
                echo $first_name ? esc_html( $first_name ) : '-';
                break;
            case 'last_name':
                $last_name = get_field( 'last_name', $post_id );
                echo $last_name ? esc_html( $last_name ) : '-';
                break;
            case 'courses':
                $courses = get_field( 'courses', $post_id );
                echo $courses ? wp_trim_words( $courses, 20, '...' ) : '-';
                break;
            case 'points':
                $points = get_field( 'points', $post_id );
                echo $points !== '' ? esc_html( $points ) : '-';
                break;
            case 'keys':
                $keys = get_field( 'keys', $post_id );
                echo $keys !== '' ? esc_html( $keys ) : '-';
                break;
            case 'coins':
                $coins = get_field( 'coins', $post_id );
                echo $coins !== '' ? esc_html( $coins ) : '-';
                break;
            case 'status':
                $status = get_field( 'status', $post_id );
                echo $status ? esc_html( ucfirst( $status ) ) : '-';
                break;
            case 'mobile_number':
                $mobile = get_field( 'mobile_number', $post_id );
                echo $mobile ? esc_html( $mobile ) : '-';
                break;
            // The "title" column (Email) and "date" are handled by WordPress by default.
        }
    }
}

// Hook into WordPress filters and actions to modify the admin list table
add_filter( 'manage_student_posts_columns', array( __NAMESPACE__ . '\\Students_Table', 'set_student_columns' ) );
add_action( 'manage_student_posts_custom_column', array( __NAMESPACE__ . '\\Students_Table', 'populate_student_columns' ), 10, 2 );
