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

                $mobile = get_field('student_mobile', $student);
                echo $mobile ? esc_html($mobile) : '—';
                break;

            case 'claimed_timestamp':
                $claimed = get_field('claimed_timestamp', $post_id);
                echo $claimed ? date('Y-m-d H:i', strtotime($claimed)) : '—';
                break;

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

add_action('wp_ajax_update_students_redeems_status', function () {
    // 1. Authorization checks
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'No permission'], 403);
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'students_redeems_update_status')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    // 2. Input validation
    $post_id = intval($_POST['post_id'] ?? 0);
    $newStatus = sanitize_text_field($_POST['status'] ?? '');
    $reason = isset($_POST['reason']) ? sanitize_textarea_field(wp_unslash($_POST['reason'])) : '';

    if (!$post_id || !in_array($newStatus, ['pending','processed','completed','failed'], true)) {
        wp_send_json_error(['message' => 'Invalid input'], 400);
    }

    // 3. Get current status
    $oldStatus = get_post_meta($post_id, 'status', true) ?: 'pending';
    update_field('status', $newStatus, $post_id);

    // 4. Get reward details
    $reward_raw = get_field('reward_item', $post_id);

    // Normalize reward ID
    if (is_array($reward_raw)) {
        $reward_id = reset($reward_raw);
        $reward_id = is_object($reward_id) ? $reward_id->ID : intval($reward_id);
    } elseif (is_object($reward_raw)) {
        $reward_id = $reward_raw->ID;
    } else {
        $reward_id = intval($reward_raw);
    }

    $promo_type = get_field('promotion_type', $reward_id);

    // 5. Only process reload promotions moving from pending
    if ($promo_type === 'reload' && $oldStatus === 'pending') {
        $student_raw = get_field('student', $post_id);

        // Normalize student ID
        if (is_array($student_raw)) {
            $student_id = reset($student_raw);
            $student_id = is_object($student_id) ? $student_id->ID : intval($student_id);
        } elseif (is_object($student_raw)) {
            $student_id = $student_raw->ID;
        } else {
            $student_id = intval($student_raw);
        }

        // 6. Email setup
        $headers = [
            'From: Differently.study <no-reply@differently.study>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        // 7. Handle completed status
        if ($newStatus === 'completed' && !get_post_meta($post_id, '_email_sent', true)) {
            $first_name = get_field('student_fname', $student_id);
            $last_name = get_field('student_lname', $student_id);
            $student_name = trim( ucfirst($first_name) . ' ' . ucfirst($last_name) );
            $reload_value = intval(get_field('reload_value', $reward_id));
            $student_email = get_the_title($student_id);
            $reward_data = get_reward_data($reward_id);

            // Gather context
            $context = [
                'student_name' => $student_name,
                'reload_value' => $reload_value,
            ];


            if (is_email($student_email)) {
                // $subject = "Your $reload_value LKR Reload was Processed!";
                // $body = "Hello $student_name,\n\nYour mobile reload of $reload_value LKR has been successfully processed!";

                $subject = \PointsPlus\Emails\get_email_subject('reload-completed', [ 'reload_value' => $reload_value ]);
                $body = \PointsPlus\Emails\get_email_body('reload-completed', $context);

                if (wp_mail($student_email, $subject, $body, $headers)) {
                    update_post_meta($post_id, '_email_sent', '1');
                    $notification_message = sprintf(
                        esc_html( points_plus_translate('The reload amount of Rs. %d for the reward %s has been successfully credited to your phone number.') ),
                        $reload_value,
                        $reward_data['promotion_name']
                    );

                    $notification_added = add_notification_to_student_cpt($student_id, $notification_message);
                    error_log("[STUDENT REDEEM SYSTEM] Notification added: " . ($notification_added ? 'true' : 'false'));
                }
            }
        }

        // 8. Handle failed status
        if ($newStatus === 'failed' && !get_post_meta($post_id, '_email_sent', true)) {
            $first_name = get_field('student_fname', $student_id);
            $last_name = get_field('student_lname', $student_id);
            $student_name = trim( ucfirst($first_name) . ' ' . ucfirst($last_name) );
            $coins_cost = intval(get_field('required_coins', $reward_id));
            $student_email = get_the_title($student_id);

            // Gather context
            $context = [
                'student_name' => $student_name,
                'coins_cost' => $coins_cost,
                'reason' => $reason,
            ];

            if (is_email($student_email)) {
                // $subject = "Reload Request Failed";
                // $body = "We couldn't process your reload request.\nReason: $reason\n\n$coins_cost coins have been refunded to your account.";

                $subject = \PointsPlus\Emails\get_email_subject('reload-failed');
                $body    = \PointsPlus\Emails\get_email_body( 'reload-failed', $context );

                if (wp_mail($student_email, $subject, $body, $headers)) {
                    // Refund coins
                    $current_coins = intval(get_field('student_coins', $student_id));
                    update_field('student_coins', $current_coins + $coins_cost, $student_id);
                    update_post_meta($post_id, '_email_sent', '1');

                    $notification_message = sprintf(
                        esc_html( points_plus_translate('Your reload redeem request for reward %s has been rejected by the Differently team. The amount of %d Coins collected from you has also been credited back to your account.') ),
                        $reward_data['promotion_name'],
                        $coins_cost

                    );

                    $notification_added = add_notification_to_student_cpt($student_id, $notification_message);
                    error_log("[STUDENT REDEEM SYSTEM] Notification added: " . ($notification_added ? 'true' : 'false'));
                }
            }
        }
    }

    // 9. Final response
    wp_send_json_success([
        'message' => 'Status updated successfully',
        'debug' => [
            'post_id' => $post_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'promo_type' => $promo_type
        ]
    ]);
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