<?php
/**
 * Student profile related functions
 */

if (!function_exists('get_student_post_id_by_email')) :
    /**
     * Helper function to find a student CPT post ID by their email ACF field.
     *
     * @param string $email Student email address
     * @return int|bool Student post ID or false if not found
     */
    function get_student_post_id_by_email($email) {
        if (empty($email)) return false;

        $args = array(
            'post_type' => 'student',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '=',
                ),
            ),
            'fields' => 'ids',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        $student_query = new WP_Query($args);
        return $student_query->have_posts() ? $student_query->posts[0] : false;
    }
endif;