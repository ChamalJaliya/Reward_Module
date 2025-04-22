<?php
namespace PointsPlus\Admin;

add_action('admin_head', function(){
    $screen = get_current_screen();
    // prints something like "edit-students-redeems" or "edit-students_redeems"
    printf(
      "<script>console.log('StudentsRedeems screen ID:', %s);</script>",
      json_encode( $screen->id )
    );
});


class StudentsRedeems_Table {
    public static function set_students_redeems_columns($columns) {
        return [
            'cb' => '<input type="checkbox" />',
            'student' => __('Student', 'points-plus'),
            'student_id' => __('Student ID', 'points-plus'),
            'reward_item' => __('Reward', 'points-plus'),
            'reward_id' => __('Reward ID', 'points-plus'),
            'mobile_number' => __('Mobile Number', 'points-plus'),
            'claimed_timestamp' => __('Claimed On', 'points-plus'),
            'status' => __('Status', 'points-plus'),
            'date' => __('Date', 'points-plus'),
        ];
    }

    public static function populate_students_redeems_columns($column, $post_id) {
        switch ($column) {
            case 'student':
                // display the student name
                $student_id = get_field('student', $post_id);
                if (is_array($student_id)) $student_id = $student_id[0] ?? null; // In case multiple are allowed
                echo $student_id ? esc_html( get_the_title( $student_id ) ) : '—';
                break;

            // case 'student_id':
            //     // just the raw ID
            //     $student_id = get_field('student', $post_id);
            //     if (is_array($student_id)) $student_id = $student_id[0] ?? null; // In case multiple are allowed
            //     echo $student_id ? intval( $student_id ) : '—';
            //     break;

            case 'student_id':
                $student = get_field('student', $post_id);

                // Normalize to an ID
                if (is_array($student)) {
                $student = array_map(function($s) {
                    if (is_object($s) && isset($s->ID)) return $s->ID;
                    return intval($s);
                }, $student);
                $student = $student[0] ?? 0;
                } elseif (is_object($student) && isset($student->ID)) {
                $student = $student->ID;
                } else {
                $student = intval($student);
                }

                echo $student ? intval($student) : '—';
                break;
            
    
            case 'reward_item':
                $reward = get_field('reward_item', $post_id);
                if (is_array($reward)) $reward = $reward[0] ?? null;
                echo $reward ? get_the_title($reward) : '—';
                break;

            case 'reward_id':
                $reward_id = get_field( 'reward_item', $post_id );
                if (is_array($reward_id)) $reward_id = $reward_id[0] ?? null;
                echo $reward_id ? intval( $reward_id ) : '—';
                break;

            // case 'mobile_number':
            //     // pull the mobile_number from the Student post
            //     $student_id = get_field( 'student', $post_id );
            //     if (is_array($student_id)) $student_id = $student_id[0] ?? null;
            //     if ( $student_id ) {
            //         $mobile = get_field( 'mobile_number', $student_id );
            //         echo $mobile ? esc_html( $mobile ) : '—';
            //     } else {
            //         echo '—';
            //     }
            //     break;

            // case 'mobile_number':
            //     // grab whatever get_field('student') returned…
            //     $student = get_field('student', $post_id);
            
            //     // if it was an array (or object), pull out the first element/ID
            //     if ( is_array( $student ) ) {
            //         $student = intval( $student[0] ?? 0 );
            //     }
            //     elseif ( is_object( $student ) && ! empty( $student->ID ) ) {
            //         $student = intval( $student->ID );
            //     } else {
            //         $student = intval( $student );
            //     }
            
            //     // if there still isn’t a valid student ID, bail
            //     if ( ! $student ) {
            //         echo '—';
            //         break;
            //     }
            
            //     // now we can safely ask ACF for the mobile_number on that post
            //     $mobile = get_field( 'mobile_number', $student );
            //     echo $mobile ? esc_html( $mobile ) : '—';
            //     break;
            
            case 'mobile_number':
                $student = get_field('student', $post_id);
            
                // Normalize to an ID
                if (is_array($student)) {
                    $student = array_map(function($s) {
                        if (is_object($s) && isset($s->ID)) return $s->ID;
                        return intval($s);
                    }, $student);
                    $student = $student[0] ?? 0;
                } elseif (is_object($student) && isset($student->ID)) {
                    $student = $student->ID;
                } else {
                    $student = intval($student);
                }
            
                if (!$student) {
                    echo '—';
                    break;
                }
            
                $mobile = get_field('mobile_number', $student);
                echo $mobile ? esc_html($mobile) : '—';
                break;
    
            case 'claimed_timestamp':
                $claimed = get_field('claimed_timestamp', $post_id);
                echo $claimed ? date('Y-m-d H:i', strtotime($claimed)) : '—';
                break;
    
            // case 'status':
            //     $status = get_field('status', $post_id);
            //     echo $status ? ucfirst($status) : '—';
            //     break;

            case 'status':
                $status = get_field('status', $post_id);
                $options = ['pending', 'processed', 'completed', 'failed'];
            
                echo '<select class="redeem-status-dropdown" data-id="' . esc_attr($post_id) . '">';
                foreach ($options as $option) {
                    printf(
                        '<option value="%1$s"%2$s>%3$s</option>',
                        esc_attr($option),
                        selected($status, $option, false),
                        ucfirst($option)
                    );
                }
                echo '</select>';
                break;            
        }
    }

    // Add Filters by Student, Reward, and Status
    public static function add_filters() {
        global $typenow;
        if ($typenow !== 'students_redeems') return;
        
        // Status Filter
        $status = $_GET['status_filter'] ?? '';
        $options = ['pending', 'processed', 'completed', 'failed'];
        echo '<select name="status_filter"><option value="">All Statuses</option>';
        foreach ($options as $opt) {
            printf('<option value="%s"%s>%s</option>', esc_attr($opt), selected($status, $opt, false), ucfirst($opt));
        }
        echo '</select>';
        
        // Student Filter
        $students = get_posts(['post_type' => 'student', 'numberposts' => -1]);
        $selected_student = $_GET['student_filter'] ?? '';
        echo '<select name="student_filter"><option value="">All Students</option>';
        foreach ($students as $student) {
            printf('<option value="%s"%s>%s</option>', $student->ID, selected($selected_student, $student->ID, false), $student->post_title);
        }
        echo '</select>';

        // Reward
        $rewards = get_posts([ 'post_type'=>'reward-item','numberposts'=>-1 ]);
        $sel_rw  = $_GET['reward_filter'] ?? '';
        echo '<select name="reward_filter"><option value="">All Rewards</option>';
        foreach ( $rewards as $rw ) {
            printf(
                '<option value="%1$d"%2$s>%3$s</option>',
                $rw->ID,
                selected($sel_rw,$rw->ID,false),
                esc_html($rw->post_title)
            );
        }
        echo '</select>';
        
        // Claimed On filter: from / to
        $claimed_from = $_GET['claimed_from'] ?? '';
        $claimed_to   = $_GET['claimed_to']   ?? '';
        echo '<span style="margin-left:2px;">From: </span>';
        echo '<input type="date" name="claimed_from" value="' . esc_attr($claimed_from) . '" placeholder="Claimed From" style="margin-left:2px;"/>';
        echo '<span style="margin-left:2px;">To: </span>';
        echo '<input type="date" name="claimed_to"   value="' . esc_attr($claimed_to)   . '" placeholder="Claimed To"  style="margin-left:2px;"/>';
    }
    
    public static function filter_query($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php') return;
        if (($query->get('post_type') !== 'students_redeems')) return;
    
        $meta_query = [];

        
        if (!empty($_GET['status_filter'])) {
            $meta_query[] = [
                'key' => 'status',
                'value' => sanitize_text_field($_GET['status_filter']),
                'compare' => '='
            ];
        }
        
        if (!empty($_GET['student_filter'])) {
            $meta_query[] = [
                'key' => 'student',
                'value' => '"' . intval($_GET['student_filter']) . '"',
                'compare' => 'LIKE'
            ];
        }
        
        // if (!empty($_GET['reward_filter'])) {
        //     $meta_query[] = [
        //         'key' => 'reward_item',
        //         'value' => '"' . intval($_GET['reward_filter']) . '"',
        //         'compare' => 'LIKE'
        //     ];
        // }
        
        // if ( ! empty( $_GET['reward_filter'] ) ) {
            //     $reward_id = intval( $_GET['reward_filter'] );
            
            //     // // If ACF stores a single ID:
            //     // $meta_query[] = [
                //     //     'key'     => 'reward_item',
                //     //     'value'   => $reward_id,
                //     //     'compare' => '=',
                //     // ];
                
                //     // —OR— if it stores a serialized array:
                    //     $meta_query[] = [
                        //         'key'     => 'reward_item',
                        //         'value'   => sprintf(':"%d";', $reward_id),
                        //         'compare' => 'LIKE',
                        //     ];
                        // }

        if ( ! empty( $_GET['reward_filter'] ) ) {
            $reward_id = intval( $_GET['reward_filter'] );
            
            // match either the raw ID or the quoted ID inside a serialized array
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'reward_item',
                    'value'   => '"' . $reward_id . '"',
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'reward_item',
                    'value'   => $reward_id,
                    'compare' => '=',
                ],
            ];
        }

        // --- Claimed On range filter ---
        $from = $_GET['claimed_from'] ?? '';
        $to   = $_GET['claimed_to']   ?? '';
        if ( $from || $to ) {
            // normalize to full datetime strings
            $from_dt = $from ? $from . ' 00:00:00' : '';
            $to_dt   = $to   ? $to   . ' 23:59:59' : '';

            $meta_query[] = [
                'key'     => 'claimed_timestamp',
                'value'   => [ $from_dt, $to_dt ],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME',
            ];
        }
    
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Print our Export Pending button above the list table.
     *
     * @param string $which 'top' or 'bottom' nav.
     */
    public static function extra_tablenav( $which ) {
        if ( $which !== 'top' ) return;
        $screen = get_current_screen();
        if ( $screen->id !== 'edit-students_redeems' ) return;

        // // build a nonceed URL
        // $url = add_query_arg([
        //     'export_students_redeems_pending' => '1',
        //     '_wpnonce' => wp_create_nonce( 'export_students_redeems_pending' ),
        // ], admin_url( 'edit.php?post_type=students_redeems' ) );

        // capture whatever filters are in the URL right now
        $filters = [
            'status_filter'  => $_GET['status_filter']  ?? '',
            'student_filter' => $_GET['student_filter'] ?? '',
            'reward_filter'  => $_GET['reward_filter']  ?? '',
            'claimed_from'  => $_GET['claimed_from']  ?? '',
            'claimed_to'    => $_GET['claimed_to']    ?? '',
            // export trigger + nonce
            'export_students_redeems' => '1',
            '_wpnonce'    => wp_create_nonce( 'export_students_redeems' ),
        ];

        // build the export link with ALL of them
        $url = add_query_arg( $filters, admin_url( 'edit.php?post_type=students_redeems' ) );

        echo '<div class="alignleft actions">';
        printf(
            '<a href="%1$s" class="button button-primary">
                <span class="dashicons dashicons-download" style="margin-right:4px;"></span>
                %2$s
            </a>',
            esc_url($url),
            esc_html__('Export CSV', 'points-plus')
        );
        echo '</div>';
    }
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'edit.php' && get_current_screen()->post_type === 'students_redeems') {
        wp_enqueue_script(
            'students-redeems-status',
            plugins_url('../assets/js/students-redeems-status.js', __FILE__),
            ['jquery'],
            POINTS_PLUS_VERSION,
            true
        );
        wp_localize_script('students-redeems-status', 'PointsPlus_Admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('students_redeems_update_status')
        ]);
    }
});

add_action('wp_ajax_update_students_redeems_status', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'No permission'], 403);
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'students_redeems_update_status')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');

    if (!$post_id || !in_array($status, ['pending', 'processed', 'completed', 'failed'])) {
        wp_send_json_error(['message' => 'Invalid input'], 400);
    }

    update_field('status', $status, $post_id);
    wp_send_json_success();
});

add_action('admin_notices', function () {
    global $pagenow;

    if (
        $pagenow === 'edit.php' &&
        $_GET['post_type'] === 'students_redeems' &&
        isset($_GET['export_empty']) &&
        $_GET['export_empty'] == '1'
    ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>No pending reload redemptions found to export.</strong></p>';
        echo '</div>';

        // Step 2: Remove the query string from the URL
        echo '<script>
            const url = new URL(window.location);
            url.searchParams.delete("export_empty");
            window.history.replaceState({}, document.title, url);
        </script>';
    }
});

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen->id !== 'edit-students_redeems') return;
    echo '<style>
        .dashicons-download {
            font-size: 18px;
            vertical-align: middle;
        }
    </style>';
});


// Hook into WordPress admin
add_filter('manage_students_redeems_posts_columns', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'set_students_redeems_columns']);
add_action('manage_students_redeems_posts_custom_column', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'populate_students_redeems_columns'], 10, 2);
add_action('restrict_manage_posts', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'add_filters']);
add_action('pre_get_posts', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'filter_query']);
add_action('manage_posts_extra_tablenav', [ __NAMESPACE__ . '\\StudentsRedeems_Table', 'extra_tablenav' ], 10, 1);
