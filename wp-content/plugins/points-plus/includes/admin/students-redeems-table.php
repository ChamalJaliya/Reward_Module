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
            'email_sent' => __('Email Sent','points-plus'),
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

            // // Working
            // case 'status':
            //     $status = get_field('status', $post_id);
            //     $options = ['pending', 'processed', 'completed', 'failed'];

            //     echo '<select class="redeem-status-dropdown" data-id="' . esc_attr($post_id) . '">';
            //     foreach ($options as $option) {
            //         printf(
            //             '<option value="%1$s"%2$s>%3$s</option>',
            //             esc_attr($option),
            //             selected($status, $option, false),
            //             ucfirst($option)
            //         );
            //     }
            //     echo '</select>';
            //     break;

            case 'email_sent':
                // figure out the linked reward ID
                $reward_raw = get_field('reward_item', $post_id);

                if ( is_array($reward_raw) ) {
                    $first       = reset($reward_raw);
                    $reward_id   = is_object($first) ? intval($first->ID) : intval($first);
                } elseif ( is_object($reward_raw) && isset($reward_raw->ID) ) {
                    $reward_id   = intval($reward_raw->ID);
                } else {
                    $reward_id   = intval($reward_raw);
                }

                // promotion type
                $promo_type = get_field('promotion_type', $reward_id);

                // only show for reload-type promotions
                if ( $promo_type !== 'reload' ) {
                    echo '—';
                    break;
                }

                // otherwise show Yes/No
                $sent = get_post_meta( $post_id, '_email_sent', true );
                echo $sent === '1'
                    ? '<span style="color:green;">Yes</span>'
                    : '<span style="color:red;">No</span>';
                break;


            case 'status':
                // fetch current status and email‐sent flag
                $status = get_field('status', $post_id);
                $email_sent = get_post_meta( $post_id, '_email_sent', true );

                // if the email is already sent and status is completed/failed, just show text
                if ( ($email_sent === '1' && in_array( $status, ['completed','failed'], true) || $status !== 'pending' ) ) {
                    echo ucfirst( $status );
                    break;
                }

                // if ( $email_sent === '1' && $status !== 'pending' ) {
                //     echo ucfirst( $status );
                //     break;
                // }

                // // figure out promotion type on the linked reward
                // $reward_raw  = get_field('reward_item', $post_id);

                // if (is_array($reward_raw)) {
                //     $first = reset($reward_raw);
                //     $reward_id = is_object($first) ? intval($first->ID) : intval($first);
                // } elseif (is_object($reward_raw) && isset($reward_raw->ID)) {
                //     $reward_id = intval($reward_raw->ID);
                // } else {
                //     $reward_id = intval($reward_raw);
                // }
                // $promo_type = get_field('promotion_type', $reward_id);

                // // if this is a reload‐based promo, email already went out, and status is no longer pending, just display status
                // if ( $promo_type === 'reload' && $email_sent === '1' && $status !== 'pending') {
                //     // …just show the text and skip the <select>
                //     echo ucfirst( $status );
                //     break;
                // }

                $options = ['pending', 'processed', 'completed', 'failed'];

                // Fetch related IDs and meta
                $student_raw = get_field('student', $post_id);
                // normalize to ID
                $student_id = is_array($student_raw) ? ($student_raw[0]->ID ?? intval($student_raw[0])) : (is_object($student_raw) ? $student_raw->ID : intval($student_raw));
                $student_email = get_field('email', $student_id);

                $reward_raw = get_field('reward_item', $post_id);
                $reward_id  = is_array($reward_raw) ? intval($reward_raw[0]) : (is_object($reward_raw) ? $reward_raw->ID : intval($reward_raw));
                $promo_type      = get_field('promotion_type', $reward_id);
                $reload_value    = get_field('reload_value', $reward_id);
                $required_coins  = get_field('required_coins', $reward_id);

                // Build the <select>
                echo '<select class="redeem-status-dropdown"'
                    . ' data-id="'. esc_attr($post_id) .'"'
                    . ' data-old-status="'. esc_attr($status) .'"'
                    . ' data-promotion-type="'. esc_attr($promo_type) .'"'
                    . ' data-reload-value="'. esc_attr($reload_value) .'"'
                    . ' data-coins-cost="'. esc_attr($required_coins) .'"'
                    . ' data-student-email="'. esc_attr($student_email) .'">'
                ;
                foreach ($options as $opt) {
                    printf(
                        '<option value="%1$s"%2$s>%3$s</option>',
                        esc_attr($opt),
                        selected($status, $opt, false),
                        ucfirst($opt)
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
        // === load jQuery UI Dialog ===
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }
});

// // Working
// add_action('wp_ajax_update_students_redeems_status', function () {
//     if (!current_user_can('edit_posts')) {
//         wp_send_json_error(['message' => 'No permission'], 403);
//     }

//     if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'students_redeems_update_status')) {
//         wp_send_json_error(['message' => 'Invalid nonce'], 403);
//     }

//     $post_id = intval($_POST['post_id'] ?? 0);
//     $status = sanitize_text_field($_POST['status'] ?? '');

//     if (!$post_id || !in_array($status, ['pending', 'processed', 'completed', 'failed'])) {
//         wp_send_json_error(['message' => 'Invalid input'], 400);
//     }

//     update_field('status', $status, $post_id);
//     wp_send_json_success();
// });

add_action('wp_ajax_update_students_redeems_status', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'No permission'], 403);
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'students_redeems_update_status')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    // $post_id   = intval($_POST['post_id']  ?? 0);
    // $newStatus = sanitize_text_field($_POST['status'] ?? '');
    // if (!$post_id || !in_array($newStatus, ['pending','processed','completed','failed'])) {
    //     error_log("PP-ERROR: Invalid input for update_students_redeems_status. post_id={$post_id}, status={$newStatus}");
    //     wp_send_json_error(['message' => 'Invalid input'], 400);
    // }

    // // 1) Load old status
    // $oldStatus = get_field('status', $post_id) ?: 'pending';
    // error_log("PP: Changing students_redeems #{$post_id} from '{$oldStatus}' to '{$newStatus}'");

    // // 2) Update the status ACF field
    // update_field('status', $newStatus, $post_id);

    // // 3) Only for reload-based, pending→completed or pending→failed
    // $reward_raw = get_field('reward_item', $post_id);
    // $reward_id  = is_array($reward_raw) ? intval($reward_raw[0]) : intval($reward_raw);
    // $promo_type = get_field('promotion_type', $reward_id);

    // if ($promo_type === 'reload' && $oldStatus === 'pending') {
    //     // --- completed case ---
    //     if ($newStatus === 'completed' && ! get_post_meta($post_id, '_email_sent', true)) {
    //         // send the “granted” email
    //         $student_raw  = get_field('student', $post_id);
    //         $student_id   = is_array($student_raw) ? intval($student_raw[0]) : intval($student_raw);
    //         $student_email= get_field('email', $student_id);
    //         $reloadValue  = get_field('reload_value', $reward_id);

    //         $subject = 'Your reload has been granted';
    //         $body    = sprintf(
    //           "Hi %s,\n\nYour mobile reload of ₹%d has just been applied to your number. Enjoy!\n\n—Differently.study",
    //           get_the_title($student_id),
    //           $reloadValue
    //         );
    //         // $headers = [
    //         //     'From: Differently.study <noreply@your‐domain.com>',
    //         //     'Content-Type: text/plain; charset=UTF-8',
    //         // ];

    //         error_log("PP: Sending grant-email for post {$post_id} to {$student_email}");
    //         $sent = wp_mail($student_email, $subject, $body);
    //         // add_post_meta($post_id, '_email_sent_completed', '1', true);
    //         if ($sent) {
    //             error_log("PP: wp_mail SUCCESS for post {$post_id}");
    //             update_post_meta($post_id, '_email_sent', '1');
    //         } else {
    //             error_log("PP-ERROR: wp_mail FAILED for post {$post_id}. To={$student_email}, Subject={$subject}");
    //         }
    //     }

    //     // --- failed case ---
    //     if ($newStatus === 'failed' && ! get_post_meta($post_id, '_email_sent', true)) {
    //         // send the “failed” email
    //         $student_raw   = get_field('student', $post_id);
    //         $student_id    = is_array($student_raw) ? intval($student_raw[0]) : intval($student_raw);
    //         $student_email = get_field('email', $student_id);
    //         $coinsCost     = get_field('required_coins', $reward_id);
    //         $subject = 'Your reload request failed';
    //         $body    = sprintf(
    //           "Hi %s,\n\nUnfortunately your reload of this time failed. We have refunded %d coins to your account.\n\n—Differently.study",
    //           get_the_title($student_id),
    //           $coinsCost
    //         );
    //         // $headers = [
    //         //     'From: Differently.study <noreply@your‐domain.com>',
    //         //     'Content-Type: text/plain; charset=UTF-8',
    //         // ];

    //         error_log("PP: Sending fail-email for post {$post_id} to {$student_email}");
    //         $sent = wp_mail($student_email, $subject, $body);

    //         // // refund coins into student’s balance
    //         // $currentCoins = intval(get_field('coins', $student_id));
    //         // update_field('coins', $currentCoins + $coinsCost, $student_id);

    //         // // add_post_meta($post_id, '_email_sent_failed', '1', true);
    //         // update_post_meta( $post_id, '_email_sent', '1' );

    //         if ($sent) {
    //             error_log("PP: wp_mail SUCCESS for post {$post_id} (failed notification)");
    //             // refund coins on success
    //             $currentCoins = intval(get_field('coins', $student_id));
    //             update_field('coins', $currentCoins + $coinsCost, $student_id);
    //             update_post_meta($post_id, '_email_sent', '1');
    //         } else {
    //             error_log("PP-ERROR: wp_mail FAILED for post {$post_id} (failed notification). To={$student_email}, Subject={$subject}");
    //         }
    //     }
    // }

    // wp_send_json_success();

    $post_id   = intval($_POST['post_id'] ?? 0);
    $newStatus = sanitize_text_field($_POST['status'] ?? '');
    $reason = isset($_POST['reason'])
        ? sanitize_textarea_field( wp_unslash($_POST['reason']) )
        : '';

    // error_log( "PP-DEBUG: failure reason = “" . $reason . "”" );

    // validate
    if (! $post_id || ! in_array($newStatus, ['pending','processed','completed','failed'], true)) {
        error_log("PP-ERROR: Invalid input. post_id={$post_id}, status={$newStatus}");
        wp_send_json_error(['message'=>'Invalid input'], 400);
    }

    $oldStatus = get_field('status', $post_id) ?: 'pending';
    error_log("PP: Changing students_redeems #{$post_id} from '{$oldStatus}' to '{$newStatus}'");

    update_field('status', $newStatus, $post_id);

    // get the promotion type
    $reward_raw = get_field('reward_item', $post_id);

    // Normalize reward ID (handles array, object, scalar)
    if (is_array($reward_raw)) {
        $first = reset($reward_raw);
        $reward_id = is_object($first) ? intval($first->ID) : intval($first);
    } elseif (is_object($reward_raw) && isset($reward_raw->ID)) {
        $reward_id = intval($reward_raw->ID);
    } else {
        $reward_id = intval($reward_raw);
    }

    $promo_type = get_field('promotion_type', $reward_id);

    if ($promo_type === 'reload' && $oldStatus === 'pending') {
        // common headers
        $headers = [
            'From: Differently.study <noreply@your-domain.com>',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        // --- COMPLETED case ---
        if ($newStatus === 'completed' && ! get_post_meta($post_id, '_email_sent', true)) {
            $student_raw = get_field('student', $post_id);

            // Normalize student ID
            if (is_array($student_raw)) {
                $first = reset($student_raw);
                $student_id = is_object($first) ? intval($first->ID) : intval($first);
            } elseif (is_object($student_raw) && isset($student_raw->ID)) {
                $student_id = intval($student_raw->ID);
            } else {
                $student_id = intval($student_raw);
            }

            // Now fetch the e‑mail
            // $student_email = get_field('email', $student_id);
            $student_email = 'cjaliya.sln2@gmail.com';
            if (! is_email($student_email)) {
                error_log("PP-ERROR: No valid student e-mail for post {$post_id} (student_id={$student_id})");
            } else {
                $reloadValue = intval(get_field('reload_value', $reward_id));
                $subject     = 'Your reload has been granted';
                $body        = sprintf(
                    "Hi %s,\n\nYour mobile reload of ₹%d has just been applied to your number. Enjoy!\n\n—Differently.study",
                    get_the_title($student_id),
                    $reloadValue
                );

                error_log("PP: Sending grant-email for post {$post_id} to {$student_email}");
                $sent = wp_mail($student_email, $subject, $body, $headers);
                if ($sent) {
                    error_log("PP: wp_mail SUCCESS for post {$post_id}");
                    update_post_meta($post_id, '_email_sent', '1');
                } else {
                    error_log("PP-ERROR: wp_mail FAILED for post {$post_id}. To={$student_email}");
                }
            }
        }

        // --- FAILED case ---
        if ($newStatus === 'failed' && ! get_post_meta($post_id, '_email_sent', true)) {
            $student_raw = get_field('student', $post_id);

            // Normalize student ID
            if (is_array($student_raw)) {
                $first = reset($student_raw);
                $student_id = is_object($first) ? intval($first->ID) : intval($first);
            } elseif (is_object($student_raw) && isset($student_raw->ID)) {
                $student_id = intval($student_raw->ID);
            } else {
                $student_id = intval($student_raw);
            }

            // Now fetch the e‑mail
            // $student_email = get_field('email', $student_id);
            $student_email = 'cjaliya.sln2@gmail.com';
            // (normalize student_id exactly the same as above)…
            // fetch and validate $student_email as above…
            // then:
            $coinsCost = intval(get_field('required_coins', $reward_id));
            $subject   = 'Your reload request failed';
            $body      = sprintf(
                "Hi %s,\n\n".
                "Unfortunately your reload this time failed. We have refunded %d coins to your account.\n\n".
                "%s\n\n".
                "—Differently.study",
                get_the_title($student_id),
                $coinsCost,
                $reason ? "Reason: $reason" : ''
            );

            error_log("PP: Sending fail-email for post {$post_id} to {$student_email}");
            $sent = wp_mail($student_email, $subject, $body, $headers);
            if ($sent) {
                error_log("PP: wp_mail SUCCESS for failed-notification on post {$post_id}");
                // refund coins only when mail succeeded
                $currentCoins = intval(get_field('coins', $student_id));
                update_field('coins', $currentCoins + $coinsCost, $student_id);
                update_post_meta($post_id, '_email_sent', '1');
            } else {
                error_log("PP-ERROR: wp_mail FAILED for failed-notification on post {$post_id}. To={$student_email}");
            }
        }
    }

    // choose a custom message based on status
    if ( $newStatus === 'completed' ) {
        $msg = 'Reload granted and notification e-mail sent.';
    } elseif ( $newStatus === 'failed' ) {
        $msg = 'Reload failed, coins refunded and notification e-mail sent.';
    } else {
        $msg = 'Status updated.';
    }

    wp_send_json_success( [ 'message' => $msg ] );
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
        echo '<p><strong>No reward redemptions found to export.</strong></p>';
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

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'edit-students_redeems') {
        return;
    }
    ?>
    <div id="pp-reload-confirm-dialog" title="<?php esc_attr_e('Confirm Reload Grant', 'points-plus'); ?>" style="display:none;">
        <p id="pp-reload-confirm-text"></p>

        <!-- Failure‐reason container, hidden by default -->
        <div id="pp-failure-reason-container" style="display:none; margin-top:1em;">
            <label for="pp-failure-reason">
                <?php esc_html_e('Reason for failure:', 'points-plus'); ?>
            </label>
            <textarea id="pp-failure-reason" rows="3" style="width:100%;"></textarea>
        </div>
    </div>
    <style>
        /* Optional: tweak dialog width */
        #pp-reload-confirm-dialog { max-width: 400px; }
    </style>
    <?php
});

// Hook into WordPress admin
add_filter('manage_students_redeems_posts_columns', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'set_students_redeems_columns']);
add_action('manage_students_redeems_posts_custom_column', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'populate_students_redeems_columns'], 10, 2);
add_action('restrict_manage_posts', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'add_filters']);
add_action('pre_get_posts', [__NAMESPACE__ . '\\StudentsRedeems_Table', 'filter_query']);
add_action('manage_posts_extra_tablenav', [ __NAMESPACE__ . '\\StudentsRedeems_Table', 'extra_tablenav' ], 10, 1);