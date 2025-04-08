<?php

/**
 * Defines the shortcode to display student information (USE WITH CAUTION!).
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/shortcodes
 *
 * **WARNING: Displaying raw user data on the front-end can expose sensitive information and is generally NOT recommended.**
 */

namespace PointsPlus\Shortcodes;

class Students {

    /**
     * Registers the 'points_plus_students' shortcode.
     */
    public static function register(): void {
        add_shortcode( 'points_plus_students', array( __CLASS__, 'display_students' ) );
    }

    /**
     * Callback function for the 'points_plus_students' shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output to display student information.
     */
    public static function display_students( $atts ): string {
        $atts = shortcode_atts(
            array(
                'numberposts' => 10,  // Display 10 students by default
                'orderby'     => 'display_name',
                'order'       => 'ASC',
                'role'        => 'student', // If you have a 'student' user role
                'fields'      => 'all',    // Or a comma-separated list of fields
            ),
            $atts,
            'points_plus_students'
        );

        $args = array(
            'number'  => (int) $atts['numberposts'],
            'orderby' => sanitize_text_field( $atts['orderby'] ),
            'order'   => sanitize_text_field( $atts['order'] ),
            'role'    => sanitize_text_field( $atts['role'] ),
        );

        $users = get_users( $args );

        $output = '<div class="points-plus-students-container">';
        if ( ! empty( $users ) ) {
            $output .= '<ul class="points-plus-students-list">';
            foreach ( $users as $user ) {
                $output .= '<li class="points-plus-student-item">';
                $output .= '<h3 class="points-plus-student-name">' . esc_html( $user->display_name ) . '</h3>';

                // Display ACF fields (WARNING: Be very careful what you display!)
                $fields_to_display = explode(',', $atts['fields']);
                $fields_to_display = array_map('trim', $fields_to_display); // Trim whitespace

                if (in_array('email', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $email = get_field( 'email', 'user_' . $user->ID );
                    if ( $email ) {
                        $output .= '<p class="points-plus-student-email">Email: ' . esc_html( $email ) . '</p>';
                    }
                }

                if (in_array('first_name', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $first_name = get_field( 'first_name', 'user_' . $user->ID );
                    if ( $first_name ) {
                        $output .= '<p class="points-plus-student-first-name">First Name: ' . esc_html( $first_name ) . '</p>';
                    }
                }

                if (in_array('last_name', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $last_name = get_field( 'last_name', 'user_' . $user->ID );
                    if ( $last_name ) {
                        $output .= '<p class="points-plus-student-last-name">Last Name: ' . esc_html( $last_name ) . '</p>';
                    }
                }

                if (in_array('points', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $points = get_field( 'points', 'user_' . $user->ID );
                    if ( $points ) {
                        $output .= '<p class="points-plus-student-points">Points: ' . esc_html( $points ) . '</p>';
                    }
                }

                if (in_array('keys', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $keys = get_field( 'keys', 'user_' . $user->ID );
                    if ( $keys ) {
                        $output .= '<p class="points-plus-student-keys">Keys: ' . esc_html( $keys ) . '</p>';
                    }
                }

                if (in_array('coins', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $coins = get_field( 'coins', 'user_' . $user->ID );
                    if ( $coins ) {
                        $output .= '<p class="points-plus-student-coins">Coins: ' . esc_html( $coins ) . '</p>';
                    }
                }

                if (in_array('status', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $status = get_field( 'status', 'user_' . $user->ID );
                    if ( $status ) {
                        $output .= '<p class="points-plus-student-status">Status: ' . esc_html( $status ) . '</p>';
                    }
                }

                if (in_array('mobile_number', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $mobile_number = get_field( 'mobile_number', 'user_' . $user->ID );
                    if ( $mobile_number ) {
                        $output .= '<p class="points-plus-student-mobile-number">Mobile Number: ' . esc_html( $mobile_number ) . '</p>';
                    }
                }

                if (in_array('date', $fields_to_display) || in_array('all', $fields_to_display)) {
                    $date = get_field( 'date', 'user_' . $user->ID );
                    if ( $date ) {
                        $output .= '<p class="points-plus-student-date">Date: ' . esc_html( $date ) . '</p>';
                    }
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No students found.</p>';
        }
        $output .= '</div>';

        return $output;
    }
}

// Register the shortcode
add_action( 'init', array( __NAMESPACE__ . '\\Students', 'register' ) );
