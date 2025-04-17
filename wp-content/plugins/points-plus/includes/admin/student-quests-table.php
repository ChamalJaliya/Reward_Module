<?php
/**
 * Customizes the admin list table for the Student Quests custom post type.
 *
 * @package    Your_Theme_Or_Plugin
 * @subpackage Your_Theme_Or_Plugin/includes/admin
 */

namespace YourNamespace\Admin;

class Student_Quest_Table {

    /**
     * Sets up the admin columns for the Student Quests post type.
     */
    public static function admin_columns($columns) {
        unset($columns['date']);
        unset($columns['title']);

        $new_columns = array(
            'student_email'     => __('Student Email', 'your-text-domain'),
            'quest_info'        => __('Quest Info', 'your-text-domain'),
            'completion_date'   => __('Completion Date', 'your-text-domain'),
            'id'                => __('Quest Record ID', 'your-text-domain'),
        );

        return array_slice($columns, 0, 1, true) + $new_columns + array_slice($columns, 1, null, true);
    }

    /**
     * Populates the custom columns with data.
     */
    public static function column_data($column, $post_id) {
        switch ($column) {
            case 'student_email':
                self::display_student_email($post_id);
                break;

            case 'quest_info':
                self::display_quest_info($post_id);
                break;

            case 'completion_date':
                self::display_completion_date($post_id);
                break;

            case 'id':
                echo esc_html($post_id);
                break;
        }
    }

    /**
     * Helper function to display student email.
     */
    private static function display_student_email($post_id) {
        $students = get_field('student', $post_id);
        if (is_array($students) && !empty($students)) {
            $student = $students[0];
            if (is_object($student) && isset($student->ID)) {
                $email = get_field('email', $student->ID);
                if ($email) {
                    echo esc_html($email);
                } else {
                    if (is_email($student->post_title)) {
                        echo esc_html($student->post_title);
                    } else {
                        echo 'No Email Found';
                    }
                }
            } else {
                echo 'N/A';
            }
        } else {
            echo 'N/A';
        }
    }

    /**
     * Helper function to display quest information.
     */
    private static function display_quest_info($post_id) {
        $quests = get_field('quest', $post_id);
        if (is_array($quests) && !empty($quests)) {
            $quest = $quests[0];
            if (is_object($quest) && isset($quest->ID)) {
                $quest_name = get_field('quest_name', $quest->ID);
                if ($quest_name) {
                    echo esc_html($quest_name) . ' (ID: ' . $quest->ID . ')';
                } else {
                    echo esc_html($quest->post_title) . ' (ID: ' . $quest->ID . ')';
                }
            } else {
                echo 'N/A';
            }
        } else {
            echo 'N/A';
        }
    }

    /**
     * Helper function to display completion date.
     */
    private static function display_completion_date($post_id) {
        $completion_date = get_field('completion_date', $post_id);
        if ($completion_date) {
            echo date('F j, Y g:i a', strtotime($completion_date));
        } else {
            echo 'N/A';
        }
    }

    /**
     * Adds sorting to the custom columns.
     */
    public static function sortable_columns($columns) {
        $columns['student_email']     = 'student_email';
        $columns['quest_info']       = 'quest_info';
        $columns['completion_date']   = 'completion_date';
        $columns['id']               = 'ID';

        return $columns;
    }

    /**
     * Adds filters above the admin table.
     */
    public static function add_filters() {
        global $pagenow, $typenow;

        if ('student_quests' === $typenow && 'edit.php' === $pagenow) {
            self::student_filter();
            self::quest_filter();
            self::date_filter();
        }
    }

    /**
     * Filter by Student.
     */
    public static function student_filter() {
        $students = get_posts(array(
            'post_type'   => 'student',
            'numberposts' => -1,
            'orderby'    => 'title',
            'order'      => 'ASC',
        ));

        if ($students) {
            $selected = isset($_GET['student_filter']) ? $_GET['student_filter'] : '';
            ?>
            <select name="student_filter" id="student_filter">
                <option value=""><?php _e('All Students', 'your-text-domain'); ?></option>
                <?php foreach ($students as $student) :
                    $email = get_field('email', $student->ID);
                    $display = $email ? $email : $student->post_title;
                    ?>
                    <option value="<?php echo esc_attr($student->ID); ?>" <?php selected($selected, $student->ID); ?>>
                        <?php echo esc_html($display); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }

    /**
     * Filter by Quest.
     */
    public static function quest_filter() {
        $quests = get_posts(array(
            'post_type'   => 'quest',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ));

        if ($quests) {
            $selected = isset($_GET['quest_filter']) ? $_GET['quest_filter'] : '';
            ?>
            <select name="quest_filter" id="quest_filter">
                <option value=""><?php _e('All Quests', 'your-text-domain'); ?></option>
                <?php foreach ($quests as $quest) :
                    $quest_name = get_field('quest_name', $quest->ID);
                    $display = $quest_name ? $quest_name : $quest->post_title;
                    ?>
                    <option value="<?php echo esc_attr($quest->ID); ?>" <?php selected($selected, $quest->ID); ?>>
                        <?php echo esc_html($display); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }

    /**
     * Filter by Date Range.
     */
    public static function date_filter() {
        $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : '';

        ?>
        <span style="vertical-align: middle;"><?php _e('From:', 'your-text-domain'); ?></span>
        <input type="date" name="date_from" class="postform" value="<?php echo esc_attr($date_from); ?>">

        <span style="vertical-align: middle; margin-left: 10px;"><?php _e('To:', 'your-text-domain'); ?></span>
        <input type="date" name="date_to" class="postform" value="<?php echo esc_attr($date_to); ?>">
        <?php
    }

    /**
     * Modifies the main query to filter posts.
     */
    public static function filter_query($query) {
        global $pagenow, $typenow;

        if (!is_admin() || 'edit.php' !== $pagenow || 'student_quests' !== $typenow || !$query->is_main_query()) {
            return;
        }

        $meta_query = array();

        // Student filter
        if (!empty($_GET['student_filter'])) {
            $student_id = intval($_GET['student_filter']);
            $meta_query[] = array(
                'key' => 'student_%_student',
                'value' => $student_id,
                'compare' => '='
            );
        }

        // Quest filter
        if (!empty($_GET['quest_filter'])) {
            $quest_id = intval($_GET['quest_filter']);
            $meta_query[] = array(
                'key' => 'student_%_quest',
                'value' => $quest_id,
                'compare' => '='
            );
        }

        // Date range filter
        if (!empty($_GET['date_from']) || !empty($_GET['date_to'])) {
            $date_query = array();

            if (!empty($_GET['date_from'])) {
                $date_query['after'] = sanitize_text_field($_GET['date_from']);
            }

            if (!empty($_GET['date_to'])) {
                $date_query['before'] = sanitize_text_field($_GET['date_to']);
            }

            $date_query['inclusive'] = true;
            $query->set('date_query', $date_query);
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Pre-fetch student and quest data for better performance
     */
    private static function prefetch_related_data($post_ids) {
        if (empty($post_ids)) return;

        // Prefetch student data
        $student_ids = array();
        $quest_ids = array();

        foreach ($post_ids as $post_id) {
            $students = get_field('student', $post_id);
            $quests = get_field('quest', $post_id);

            if (is_array($students) && !empty($students)) {
                $student_ids[] = $students[0]->ID;
            }

            if (is_array($quests) && !empty($quests)) {
                $quest_ids[] = $quests[0]->ID;
            }
        }

        // Prime the cache for students and quests
        if (!empty($student_ids)) {
            get_posts(array(
                'post_type' => 'student',
                'include' => array_unique($student_ids),
                'posts_per_page' => -1,
            ));
        }

        if (!empty($quest_ids)) {
            get_posts(array(
                'post_type' => 'quest',
                'include' => array_unique($quest_ids),
                'posts_per_page' => -1,
            ));
        }
    }
}

// Hook into WordPress
add_filter('manage_student_quests_posts_columns', array(__NAMESPACE__ . '\\Student_Quest_Table', 'admin_columns'));
add_action('manage_student_quests_posts_custom_column', array(__NAMESPACE__ . '\\Student_Quest_Table', 'column_data'), 10, 2);
add_filter('manage_edit-student_quests_sortable_columns', array(__NAMESPACE__ . '\\Student_Quest_Table', 'sortable_columns'));
add_action('restrict_manage_posts', array(__NAMESPACE__ . '\\Student_Quest_Table', 'add_filters'));
add_action('pre_get_posts', array(__NAMESPACE__ . '\\Student_Quest_Table', 'filter_query'));

// Add this to help with debugging
add_action('admin_init', function() {
    if (isset($_GET['debug_filters']) && current_user_can('manage_options')) {
        global $wp_query;
        echo '<pre>';
        print_r($wp_query->query_vars);
        echo '</pre>';
        exit;
    }
});