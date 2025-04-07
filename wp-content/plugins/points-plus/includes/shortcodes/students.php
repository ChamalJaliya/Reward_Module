<?php
/**
 * Defines the shortcode to display students on the frontend.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/shortcodes
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
     * @return string HTML output to display the students.
     */
    public static function display_students( $atts ): string {
        // Set default attributes and merge with user-defined attributes.
        $atts = shortcode_atts(
            array(
                'numberposts' => -1, // Display all students by default.
                'orderby'     => 'title',
                'order'       => 'ASC',
            ),
            $atts,
            'points_plus_students'
        );

        // Build query arguments to fetch student posts.
        $args = array(
            'post_type'      => 'student',
            'posts_per_page' => (int) $atts['numberposts'],
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        $students = get_posts( $args );

        // Start building the HTML output.
        $output = '<div class="points-plus-students-container">';
        if ( ! empty( $students ) ) {
            $output .= '<ul class="points-plus-students-list">';
            foreach ( $students as $student ) {
                $output .= '<li class="points-plus-student-item">';
                $output .= '<h3 class="points-plus-student-title">' . esc_html( get_the_title( $student->ID ) ) . '</h3>';

                // Display ACF fields for each student.
                $email = get_field( 'email', $student->ID );
                if ( $email ) {
                    $output .= '<p class="points-plus-student-email">Email: ' . esc_html( $email ) . '</p>';
                }

                $first_name = get_field( 'first_name', $student->ID );
                if ( $first_name ) {
                    $output .= '<p class="points-plus-student-first-name">First Name: ' . esc_html( $first_name ) . '</p>';
                }

                $last_name = get_field( 'last_name', $student->ID );
                if ( $last_name ) {
                    $output .= '<p class="points-plus-student-last-name">Last Name: ' . esc_html( $last_name ) . '</p>';
                }

                // $courses = get_field( 'courses', $student->ID );
                // if ( $courses ) {
                //     $output .= '<p class="points-plus-student-courses">Courses: ' . wp_kses_post( $courses ) . '</p>';
                // }

                $points = get_field( 'points', $student->ID );
                if ( $points !== '' ) {
                    $output .= '<p class="points-plus-student-points">Points: ' . esc_html( $points ) . '</p>';
                }

                $keys = get_field( 'keys', $student->ID );
                if ( $keys !== '' ) {
                    $output .= '<p class="points-plus-student-keys">Keys: ' . esc_html( $keys ) . '</p>';
                }

                $coins = get_field( 'coins', $student->ID );
                if ( $coins !== '' ) {
                    $output .= '<p class="points-plus-student-coins">Coins: ' . esc_html( $coins ) . '</p>';
                }

                $status = get_field( 'status', $student->ID );
                if ( $status ) {
                    $output .= '<p class="points-plus-student-status">Status: ' . esc_html( ucfirst( $status ) ) . '</p>';
                }

                $mobile = get_field( 'mobile_number', $student->ID );
                if ( $mobile ) {
                    $output .= '<p class="points-plus-student-mobile">Mobile: ' . esc_html( $mobile ) . '</p>';
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No students available.</p>';
        }
        $output .= '</div>';

        return $output;
    }
}

// Register the shortcode.
add_action( 'init', array( __NAMESPACE__ . '\\Students', 'register' ) );
