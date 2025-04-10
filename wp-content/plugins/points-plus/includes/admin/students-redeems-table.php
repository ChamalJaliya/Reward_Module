<?php

/**
 * Customizes the admin list table for the Students Redeems custom post type, including filtering.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class StudentsRedeems_Table {

    /**
     * Sets the column headers for the Students Redeems post type list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_students_redeems_columns($columns) {
        $new_columns = array();
        $new_columns['title'] = __('Title', 'points-plus');
        $new_columns['student'] = __('Student', 'points-plus');
        $new_columns['reward_item'] = __('Reward', 'points-plus');
        $new_columns['claimed_timestamp'] = __('Claimed On', 'points-plus');
        $new_columns['date'] = __('Date', 'points-plus');
        return $new_columns;
    }

    /**
     * Populates the custom columns with data.
     *
     * @param string $column  The name of the column to display.
     * @param int    $post_id The current post ID.
     */
    public static function populate_students_redeems_columns($column, $post_id) {
        switch ($column) {
            case 'student':
                $claimed_history = get_field('claimed_history', $post_id);
                if (is_array($claimed_history) && !empty($claimed_history)) {
                    $first_claim = reset($claimed_history);
                    if (!empty($first_claim['student'])) {
                        $student_id = intval($first_claim['student']);
                        $student = get_post($student_id);
                        echo $student ? '<a href="' . get_edit_post_link($student_id) . '">' . esc_html($student->post_title) . '</a>' : 'N/A';
                    } else {
                        echo 'N/A';
                    }
                } else {
                    echo 'N/A';
                }
                break;

            case 'reward_item':
                $claimed_history = get_field('claimed_history', $post_id);
                if (is_array($claimed_history) && !empty($claimed_history)) {
                    $first_claim = reset($claimed_history);
                    if (!empty($first_claim['reward_item'])) {
                        $reward_id = intval($first_claim['reward_item']);
                        $reward = get_post($reward_id);
                        echo $reward ? '<a href="' . get_edit_post_link($reward_id) . '">' . esc_html($reward->post_title) . '</a>' : 'N/A';
                    } else {
                        echo 'N/A';
                    }
                } else {
                    echo 'N/A';
                }
                break;

            case 'claimed_timestamp':
                $claimed_history = get_field('claimed_history', $post_id);
                if (is_array($claimed_history) && !empty($claimed_history)) {
                    $first_claim = reset($claimed_history);
                    echo !empty($first_claim['claimed_timestamp'])
                        ? esc_html(date('Y-m-d H:i:s', strtotime($first_claim['claimed_timestamp'])))
                        : 'N/A';
                } else {
                    echo 'N/A';
                }
                break;

            // Handle default WordPress columns
            case 'title':
                // Get the actual post title instead of "Hello world"
                $title = get_the_title($post_id);
                echo !empty($title) ? esc_html($title) : 'N/A';
                break;

            case 'date':
                // Get the actual post date
                $post = get_post($post_id);
                echo $post ? esc_html(get_the_date('Y/m/d \a\t g:i a', $post)) : 'N/A';
                break;
        }
    }

    /**
     * Adds filters to the Students Redeems post type list view.
     *
     * @param string $post_type The current post type.
     */
    public static function add_students_redeems_filters($post_type) {
        if ($post_type == 'students_redeems') {
            // Filter by Student Email
            self::add_filter_by_student_email();

            // Filter by Promotion Type
            self::add_filter_by_promotion_type();
        }
    }

    /**
     * Adds a filter to the Students Redeems list view to filter by Student Email.
     */
    private static function add_filter_by_student_email() {
        $email = isset($_GET['student_email']) ? sanitize_email($_GET['student_email']) : '';
        ?>
        <label for="student_email" class="screen-reader-text"><?php _e('Filter by Student Email', 'your-theme-text-domain'); ?></label>
        <input type="email" name="student_email" id="student_email" value="<?php echo esc_attr($email); ?>" placeholder="<?php _e('Student Email', 'your-theme-text-domain'); ?>" />
        <?php
    }

    /**
     * Adds a filter to the Students Redeems list view to filter by Promotion Type.
     */
    private static function add_filter_by_promotion_type() {
        $promotion_type = isset($_GET['promotion_type']) ? sanitize_text_field($_GET['promotion_type']) : '';
        ?>
        <label for="promotion_type" class="screen-reader-text"><?php _e('Filter by Promotion Type', 'your-theme-text-domain'); ?></label>
        <select name="promotion_type" id="promotion_type">
            <option value=""><?php _e('All Promotion Types', 'your-theme-text-domain'); ?></option>
            <?php
            $promotion_types = self::get_promotion_types(); // Implement this function to get your types
            if (is_array($promotion_types)) {
                foreach ($promotion_types as $type) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($type),
                        selected($promotion_type, $type, false),
                        esc_html($type)
                    );
                }
            }
            ?>
        </select>
        <?php
    }

    /**
     * Gets the available promotion types.
     *
     * @return array Array of promotion types.
     */
    private static function get_promotion_types() {
        // *** IMPLEMENT THIS FUNCTION TO RETRIEVE YOUR PROMOTION TYPES ***
        // This is a placeholder.  You'll need to adapt this based on how you store your promotion types.
        // For example, if you store them in a taxonomy:
        // $terms = get_terms( array( 'taxonomy' => 'promotion_type_taxonomy', 'hide_empty' => false ) );
        // if ( is_array( $terms ) && ! empty( $terms ) ) {
        //     return wp_list_pluck( $terms, 'name' );
        // }
        return array('addition', 'multiplication', 'reload'); // Placeholder
    }

    /**
     * Modifies the main query to filter Students Redeems posts.
     *
     * @param WP_Query $query The WP_Query instance (passed by reference).
     */
    public static function filter_students_redeems_query($query) {
        global $pagenow;
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

        if (is_admin() && $pagenow == 'edit.php' && $post_type == 'students_redeems') {
            // Get all Students Redeems posts
            $query->set('posts_per_page', -1); // Get all posts
            $query->set('meta_query', array()); // Clear any previous meta_query (important!)
        }
    }

    /**
     * Filter the displayed posts manually.
     *
     * @param array $posts Array of posts to be displayed.
     * @return array Filtered array of posts.
     */
    public static function filter_students_redeems_posts($posts) {
        $filtered_posts = array();
        $student_email = isset($_GET['student_email']) ? sanitize_email($_GET['student_email']) : '';
        $promotion_type = isset($_GET['promotion_type']) ? sanitize_text_field($_GET['promotion_type']) : '';

        if (empty($student_email) && empty($promotion_type)) {
            return $posts; // No filters, return all
        }

        foreach ($posts as $post) {
            $include_post = true;

            // Filter by Student Email
            if (!empty($student_email)) {
                $student_ids_for_email = self::get_student_post_ids_by_email($student_email);
                $found_student = false;
                $claimed_history = get_field('claimed_history', $post->ID);
                if (is_array($claimed_history)) {
                    foreach ($claimed_history as $claim) {
                        if (isset($claim['student']) && in_array(intval($claim['student']), $student_ids_for_email)) {
                            $found_student = true;
                            break;
                        }
                    }
                }
                if (!$found_student) {
                    $include_post = false;
                }
            }

            // Filter by Promotion Type
            if ($include_post && !empty($promotion_type)) {
                $found_promotion = false;
                $claimed_history = get_field('claimed_history', $post->ID);
                if (is_array($claimed_history)) {
                    foreach ($claimed_history as $claim) {
                        if (isset($claim['reward_item'])) {
                            $reward_item_id = intval($claim['reward_item']);
                            $reward_item_promotion_type = get_field('promotion_type', $reward_item_id);
                            if ($reward_item_promotion_type == $promotion_type) {
                                $found_promotion = true;
                                break;
                            }
                        }
                    }
                }
                if (!$found_promotion) {
                    $include_post = false;
                }
            }

            if ($include_post) {
                $filtered_posts[] = $post;
            }
        }

        return $filtered_posts;
    }

    /**
     * Helper function to get Student CPT post IDs by email.
     *
     * @param string $email The student's email.
     * @return array Array of Student CPT post IDs.
     */
    private static function get_student_post_ids_by_email($email) {
        $student_ids = array();
        if (empty($email)) {
            return $student_ids; // Empty array if no email provided
        }

        $args = array(
            'post_type'      => 'student', // Replace with your Student CPT name
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'email', // Replace with your ACF field name for email
                    'value'   => $email,
                    'compare' => '=',
                ),
            ),
        );
        $student_query = new \WP_Query($args);

        if ($student_query->have_posts()) {
            $student_ids = wp_list_pluck($student_query->posts, 'ID');
        }

        wp_reset_postdata(); // Reset the global $post object
        return $student_ids;
    }

    /**
     * Helper function to get Reward Item CPT post IDs by promotion type.
     *
     * @param string $promotion_type The promotion type to filter by.
     * @return array Array of Reward Item CPT post IDs.
     */
    private static function get_reward_item_post_ids_by_promotion_type($promotion_type) {
        $reward_item_ids = array();
        if (empty($promotion_type)) {
            return $reward_item_ids; // Empty array if no promotion type provided
        }

        $args = array(
            'post_type'      => 'reward-item', // Replace with your Reward Item CPT name
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'promotion_type', // Replace with your ACF field name for promotion type
                    'value'   => $promotion_type,
                    'compare' => '=',
                ),
            ),
        );
        $reward_item_query = new \WP_Query($args);

        if ($reward_item_query->have_posts()) {
            $reward_item_ids = wp_list_pluck($reward_item_query->posts, 'ID');
        }

        wp_reset_postdata(); // Reset the global $post object
        return $reward_item_ids;
    }
}

// Hook into WordPress filters and actions to modify the Students Redeems post type list table
add_filter('manage_students_redeems_posts_columns', array(__NAMESPACE__ . '\\StudentsRedeems_Table', 'set_students_redeems_columns'));
add_action('manage_students_redeems_posts_custom_column', array(__NAMESPACE__ . '\\StudentsRedeems_Table', 'populate_students_redeems_columns'), 10, 2);
add_action('restrict_manage_posts', array(__NAMESPACE__ . '\\StudentsRedeems_Table', 'add_students_redeems_filters'));
add_action('pre_get_posts', array(__NAMESPACE__ . '\\StudentsRedeems_Table', 'filter_students_redeems_query'));

// Add this line to filter the posts after they are retrieved
add_filter('posts_results', array(__NAMESPACE__ . '\\StudentsRedeems_Table', 'filter_students_redeems_posts'));