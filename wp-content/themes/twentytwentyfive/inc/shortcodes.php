<?php
/**
 * Shortcode functionality
 */

// Shortcode to display quests
add_shortcode('display_quests', 'display_quests_shortcode_function');

if (!function_exists('display_quests_shortcode_function')) :
    function display_quests_shortcode_function() {
        $output = '';
        $args = array(
            'post_type' => 'quest',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $quests_query = new WP_Query($args);

        if ($quests_query->have_posts()) {
            $output .= '<div class="quests-list">';

            while ($quests_query->have_posts()) {
                $quests_query->the_post();
                $quest_id = get_the_ID();
                $points_reward = get_field('points_reward', $quest_id) ?: 0;
                $coins_reward = get_field('coins_reward', $quest_id) ?: 0;

                $output .= '<div class="quest-item">';
                $output .= '<h3>' . get_the_title() . '</h3>';
                $output .= '<p>Rewards: ' . esc_html($points_reward) . ' Points, ' . esc_html($coins_reward) . ' Coins</p>';
                $output .= '<button class="play-quest-button" data-quest-id="' . esc_attr($quest_id) . '">Play Quest</button>';
                $output .= '</div>';
            }

            $output .= '</div>';
            wp_reset_postdata();
        } else {
            $output .= '<p>No quests available at the moment.</p>';
        }

        return $output;
    }
endif;

// Shortcode for student header info
add_shortcode('student_header_info', 'student_header_info_shortcode_function');

if (!function_exists('student_header_info_shortcode_function')) :
    function student_header_info_shortcode_function() {
        $target_email = 'cjaliya.sln2@gmail.com';  // IMPORTANT: Dynamically get logged-in user's email
        $student_post_id = 0;
        error_log("student_header_info_shortcode_function: Started for email: {$target_email}");

        try {
            $student_post_id = get_student_post_id_by_email($target_email);
            if (!$student_post_id) {
                error_log('student_header_info_shortcode_function: Could not find student post ID for email: ' . $target_email);
                return '<p>Error: Could not find student data.</p>';
            }
            $student_post = get_post($student_post_id);
            if (!$student_post) {
                error_log('student_header_info_shortcode_function: Could not retrieve student post for ID: ' . $student_post_id);
                return '<p>Error: Could not retrieve student data.</p>';
            }
            error_log("student_header_info_shortcode_function: Student found with ID: {$student_post_id}");

        } catch (Exception $e) {
            error_log('student_header_info_shortcode_function: Error getting student post ID: ' . $e->getMessage());
            return '<p>Error: ' . $e->getMessage() . '</p>';
        }

        $points = 0;
        $coins = 0;
        $unread_count = 0;
        $rewards_output = '';
        $log_output = "Reward Eligibility Log for Student (Post ID: $student_post_id, Email: $target_email):\n";

        if ($student_post_id && function_exists('get_field')) {
            try {
                // Get Points & Coins
                $points = get_field('points', $student_post_id) ?: 0;
                $coins = get_field('coins', $student_post_id) ?: 0;
                error_log("student_header_info_shortcode_function: Student points: {$points}, coins: {$coins}");

                // Get Notifications and count unread
                $notifications = get_field('student_notifications', $student_post_id);
                if (is_array($notifications)) {
                    foreach ($notifications as $note) {
                        if (isset($note['is_read']) && !$note['is_read']) {
                            $unread_count++;
                        }
                    }
                }
                error_log("student_header_info_shortcode_function: Unread notifications: {$unread_count}");

                // Fetch Reward Items
                $now = current_time('Y-m-d H:i:s');
                error_log("student_header_info_shortcode_function: Current time: {$now}");

                $reward_items = get_posts(array(
                    'post_type' => 'reward-item',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'status',
                            'value' => 1, // Assuming 1 is "Active"
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'valid_from',
                            'value' => $now,
                            'compare' => '<=',
                            'type' => 'DATETIME'
                        ),
                        array(
                            'key' => 'valid_until',
                            'value' => $now,
                            'compare' => '>=',
                            'type' => 'DATETIME'
                        )
                    )
                ));

                if ($reward_items) {
                    $rewards_output .= '<div class="rewards-dropdown" style="display: none;">';
                    $rewards_output .= '<ul>';
                    $rewards_output .= '<div class="rewards-progress-section">'; // Added wrapper for progress

                    foreach ($reward_items as $post) {
                        $promotion_id = $post->ID;
                        $promotion_name = get_the_title($promotion_id);
                        $required_coins = get_field('required_coins', $post->ID) ?: 0;
                        $min_quests = get_field('minimum_quests_completed', $post->ID) ?: 0;
                        $valid_from = get_field('valid_from', $post->ID);
                        $valid_until = get_field('valid_until', $post->ID);

                        $is_eligible = true;
                        $eligibility_reasons = [];

                        error_log("student_header_info_shortcode_function: Checking reward: {$promotion_name} (ID: {$promotion_id})");

                        // 1. Check Exclude Students FIRST
                        $exclude_students = get_field('exclude_students', $promotion_id);
                        if ($exclude_students && in_array($student_post_id, $exclude_students)) {
                            $is_eligible = false;
                            $eligibility_reasons[] = "Student excluded from reward.";
                            error_log("student_header_info_shortcode_function: Student excluded.");
                        } else {
                            $eligibility_reasons[] = "Student not excluded.";
                            error_log("student_header_info_shortcode_function: Student not excluded.");
                        }

                        // 2. Then check Include Students
                        if ($is_eligible) {
                            $include_students = get_field('include_students', $promotion_id);
                            if ($include_students && !in_array($student_post_id, $include_students)) {
                                $is_eligible = false;
                                $eligibility_reasons[] = "Student not in included students.";
                                error_log("student_header_info_shortcode_function: Student not included.");
                            } else {
                                $eligibility_reasons[] = "Student in included students (or no inclusion list).";
                                error_log("student_header_info_shortcode_function: Student included (or no list).");
                            }
                        }

                        // 3. Check Date Range
                        if ($is_eligible) {
                            if ($valid_from && strtotime($now) < strtotime($valid_from)) {
                                $is_eligible = false;
                                $eligibility_reasons[] = "Reward not yet valid.";
                                error_log("student_header_info_shortcode_function: Reward not yet valid.");
                            } else if ($valid_until && strtotime($now) > strtotime($valid_until)) {
                                $is_eligible = false;
                                $eligibility_reasons[] = "Reward has expired.";
                                error_log("student_header_info_shortcode_function: Reward expired.");
                            } else {
                                $eligibility_reasons[] = "Reward within valid date range.";
                                error_log("student_header_info_shortcode_function: Reward within valid range.");
                            }
                        }

                        // 4. Check Minimum Quests (Visibility Check)
                        $total_completed_quests = count_all_completed_quests($student_post_id); // Helper function
                        error_log("student_header_info_shortcode_function: Total completed quests: {$total_completed_quests}, Minimum quests: {$min_quests}");

                        if ($total_completed_quests < $min_quests) {
                            $is_eligible = false;
                            $eligibility_reasons[] = "Insufficient quests completed to view reward ($total_completed_quests / $min_quests).";
                            error_log("student_header_info_shortcode_function: Insufficient quests.");
                        } else {
                            $eligibility_reasons[] = "Sufficient quests completed to view reward ($total_completed_quests / $min_quests).";
                            error_log("student_header_info_shortcode_function: Sufficient quests.");
                        }

                        $log_output .= "\n- Reward: $promotion_name (ID: $promotion_id)\n";
                        $log_output .= "  - Eligibility: " . ($is_eligible ? "PASS" : "FAIL") . "\n";
                        $log_output .= "  - Reasons: " . implode(", ", $eligibility_reasons) . "\n";
                        $log_output .= "  - Total Completed Quests: " . $total_completed_quests . "\n";

                        if ($is_eligible) {
                            // 5.  REUSE the eligibility check!
                            $reward_data = get_reward_data($promotion_id); // Get reward data for cooldown, etc.
                            $is_eligible = is_student_eligible_for_reward_display(
                                $student_post_id,
                                $reward_data['cooldown_period'],
                                $promotion_id,
                                $reward_data['redemption_limit']
                            );

                            if ($is_eligible) {
                                $log_output .= "  - Student IS eligible (using is_student_eligible_for_reward)\n";

                                // 6. Display Quest Progress
                                $quest_progress_data = get_quest_completion_progress_for_display($student_post_id, $promotion_id);
                                $log_output .= "  - Quest Progress Data: " . print_r($quest_progress_data, true) . "\n";

                                // Output reward item
                                $rewards_output .= '<li class="reward-item">';
                                $rewards_output .= '<span class="reward-name">' . esc_html(get_the_title($post->ID)) . '</span><br>';

                                $client_description = get_field('client_description', $post->ID);
                                if ($client_description) {
                                    $rewards_output .= '<span class="reward-client-description">' . wp_kses_post($client_description) . '</span><br>';
                                }

                                if ($required_coins > 0) {
                                    $rewards_output .= '<span class="reward-cost">Requires: ' . esc_html($required_coins) . ' Coins</span><br>';
                                }

                                // Add Quest Progress
                                if (is_array($quest_progress_data) && count($quest_progress_data) > 0) {
                                    // Display all quest conditions
                                    foreach ($quest_progress_data as $index => $progress_item) {
                                        $quests_needed_for_claim = $progress_item['quests_needed'];
                                        $quests_completed_for_claim = $progress_item['quests_completed'];
                                        $progress_percentage = $progress_item['progress'];
                                        $time_scope = $progress_item['time_scope'];
                                        $time_params = $progress_item['time_params'];
                                        $group_logic = $progress_item['group_logic'];

                                        // Generate a user-friendly time description
                                        $time_description = '';
                                        if ($time_scope === 'specific_range') {
                                            $start_date = isset($time_params['start_date']) ? date('Y-m-d', strtotime($time_params['start_date'])) : '';
                                            $end_date = isset($time_params['end_date']) ? date('Y-m-d', strtotime($time_params['end_date'])) : '';
                                            $time_description = sprintf(
                                                'Complete %d quests between %s and %s',
                                                $quests_needed_for_claim,
                                                $start_date,
                                                $end_date
                                            );
                                        } elseif ($time_scope === 'next_x_units') {
                                            $x_value = $time_params['x_value'] ?? 1;
                                            $time_unit = $time_params['time_unit'] ?? 'days';

                                            // Format the valid_from date for better readability
                                            $start_date_formatted = $valid_from? date('Y-m-d H:i:s', strtotime($valid_from)) : 'the reward start date';
                                            $time_description = sprintf(
                                                'Complete %d quests within the next %d %s starting from %s',
                                                $quests_needed_for_claim,
                                                $x_value,
                                                $time_unit,
                                                $start_date_formatted
                                            );
                                        }
                                        elseif ($time_scope === 'last_x_units') {
                                            $x_value = $time_params['x_value'] ?? 1;
                                            $time_unit = $time_params['time_unit'] ?? 'days';
                                            $time_description = sprintf(
                                                'Complete %d quests in the last %d %s',
                                                $quests_needed_for_claim,
                                                $x_value,
                                                $time_unit
                                            );
                                        } else {
                                            $time_description = sprintf('Complete %d quests', $quests_needed_for_claim);
                                        }

                                        $rewards_output .= '<div class="reward-progress-container">';
                                        $rewards_output .= '<p class="condition-logic">' . esc_html($group_logic) . '</p>'; // Show AND/OR
                                        $rewards_output .= '<p class="progress-description">' . esc_html($time_description) . '</p>';
                                        $rewards_output .= '<div class="progress-bar">';
                                        $rewards_output .= '<div class="progress-fill" style="width: ' . esc_attr($progress_percentage) . '%;"></div>';
                                        $rewards_output .= '</div>';
                                        $rewards_output .= '<p class="progress-text">' . esc_html($quests_completed_for_claim) . ' / ' . esc_html($quests_needed_for_claim) . ' quests completed (' . esc_html($progress_percentage) . '%)</p>';
                                        $rewards_output .= '</div>';
                                    }

                                    // Determine overall claim eligibility based on all conditions
                                    $can_claim_now = ($coins >= $required_coins);

                                } else {
                                    // No quest conditions
                                    $can_claim_now = ($coins >= $required_coins);
                                }

                                if ($can_claim_now) {
                                    $rewards_output .= '<button class="redeem-button" data-reward-id="' . esc_attr($post->ID) . '">Redeem</button>';
                                    error_log("student_header_info_shortcode_function: Student can claim reward.");
                                } else {
                                    $rewards_output .= '<span class="reward-status">Eligible</span>'; // Change this line!
                                    error_log("student_header_info_shortcode_function: Student meets quest requirements but not enough coins.");
                                }
                                if ($valid_until) {
                                    $now_date = new DateTime();
                                    $end_date = new DateTime($valid_until);
                                    $interval = $now_date->diff($end_date);

                                    $total_hours_left = ($interval->d * 24) + $interval->h;
                                    $is_urgent = $total_hours_left < 24;

                                    $rewards_output .= '<div class="clean-countdown-container" data-end-time="' . esc_attr($valid_until) . '" data-urgent="' . ($is_urgent ? 'true' : 'false') . '">';
                                    $rewards_output .= '<div class="time-left-header">Time Remaining:</div>';
                                    $rewards_output .= '<div class="time-units">';

                                    if ($interval->d > 0) {
                                        $rewards_output .= '<div class="time-unit days">';
                                        $rewards_output .= '<span class="value">' . esc_html($interval->d) . '</span>';
                                        $rewards_output .= '<span class="label">days</span>';
                                        $rewards_output .= '</div>';
                                    }

                                    $rewards_output .= '<div class="time-unit hours">';
                                    $rewards_output .= '<span class="value">' . sprintf('%02d', $interval->h) . '</span>';
                                    $rewards_output .= '<span class="label">hrs</span>';
                                    $rewards_output .= '</div>';

                                    $rewards_output .= '<div class="time-unit minutes">';
                                    $rewards_output .= '<span class="value">' . sprintf('%02d', $interval->i) . '</span>';
                                    $rewards_output .= '<span class="label">mins</span>';
                                    $rewards_output .= '</div>';

                                    $rewards_output .= '<div class="time-unit seconds">';
                                    $rewards_output .= '<span class="value">' . sprintf('%02d', $interval->s) . '</span>';
                                    $rewards_output .= '<span class="label">secs</span>';
                                    $rewards_output .= '</div>';

                                    $rewards_output .= '</div></div>'; // Close time-units and container
                                }
                                $rewards_output .= '</li>';

                            } else {
                                $log_output .= "  - Student is NOT eligible (using is_student_eligible_for_reward)\n";
                                $rewards_output .= '<li class="reward-item">';
                                $rewards_output .= '<span class="reward-name">' . esc_html(get_the_title($post->ID)) . '</span><br>';
                                $rewards_output .= '<span class="reward-status">Not Eligible Yet</span>';
                                $rewards_output .= '</li>';
                                error_log("student_header_info_shortcode_function: Student is not eligible.");
                            }
                        }
                    }

                    $rewards_output .= '</div>'; // Close progress section
                    $rewards_output .= '</ul>';

                    $rewards_output .= '</div>';
                }
            } catch (Exception $e) {
                error_log('Error processing rewards: ' . $e->getMessage());
                $rewards_output = '<p>Error: Could not display rewards.</p>';
            }
        }

        error_log($log_output);

        // Prepare HTML output
        $output = '<div class="student-header-info">';
        $output .= '<span class="student-points">Points: ' . esc_html($points) . '</span>';
        $output .= '<span class="student-coins">Coins: ' . esc_html($coins) . '</span>';

        // Rewards Icon Area
        $output .= '<div class="rewards-icon-area" style="position: relative; cursor: pointer;">';
        $output .= '<span class="student-rewards-icon dashicons dashicons-tickets"></span>';
        $output .= $rewards_output;
        $output .= '</div>';

        // Notification Bell Area
        $output .= '<div class="notification-bell-area" data-student-identifier="' . esc_attr($target_email) . '" style="position: relative; cursor: pointer;">';
        $output .= '<span class="student-notification-icon dashicons dashicons-bell"></span>';

        if ($unread_count > 0) {
            $output .= '<span class="notification-count-badge">' . esc_html($unread_count) . '</span>';
        } else {
            $output .= '<span class="notification-count-badge" style="display: none;">0</span>';
        }

        $output .= '<div class="notifications-dropdown" style="display: none;"></div>';
        $output .= '</div>';
        $output .= '</div>';

        error_log("student_header_info_shortcode_function: Finished execution.");
        return $output;
    }
endif;

if (!function_exists('count_all_completed_quests')) :
    /**
     * Counts all completed quests for a student.
     *
     * @param int $student_id The ID of the student.
     * @return int The total number of completed quests.
     */
    function count_all_completed_quests($student_id) {
        error_log("count_all_completed_quests: Started for student: {$student_id}");

        $args = [
            'post_type'   => 'student_quests',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => 'student',
                    'value' => $student_id,
                    'compare' => 'LIKE'
                ],
            ],
        ];

        $student_quests = get_posts($args);
        $completed_count = 0;
        error_log("count_all_completed_quests: Found " . count($student_quests) . " student_quests posts.");

        foreach ($student_quests as $student_quest) {
            $progress_data = get_field('quest_progress', $student_quest->ID);
            if (is_array($progress_data)) {
                foreach ($progress_data as $progress) {
                    if (isset($progress['status']) && $progress['status'] === 'completed') {
                        $completed_count++;
                    }
                }
            }
        }

        error_log("count_all_completed_quests: Completed quests count: {$completed_count}");
        return $completed_count;
    }
endif;

if (!function_exists('get_quest_completion_progress_for_display')) :
    /**
     * Calculates quest completion progress for display purposes, considering eligibility rules.
     *
     * @param int $student_id The ID of the student.
     * @param int $reward_id  The ID of the reward item.
     * @return array An array of quest progress data, one entry per 'quests_completed' condition.
     */
    function get_quest_completion_progress_for_display($student_id, $reward_id) {
        error_log("get_quest_completion_progress_for_display: Started for student: {$student_id}, reward: {$reward_id}");

        $eligibility_rules = get_field('eligibility_rules', $reward_id);
        $valid_from = get_field('valid_from', $reward_id);
        $progress_data = [];

        if (!empty($eligibility_rules)) {
            error_log("get_quest_completion_progress_for_display: Found " . count($eligibility_rules) . " eligibility rules.");

            foreach ($eligibility_rules as $rule_id) {
                $rule_progress_data = get_quests_needed_for_rule_display($student_id, $rule_id ,$valid_from);
                if (is_array($rule_progress_data)) {
                    $progress_data = array_merge($progress_data, $rule_progress_data);
                }
            }
        } else {
            error_log("get_quest_completion_progress_for_display: No eligibility rules found.");
        }

        error_log("get_quest_completion_progress_for_display: Final progress data: " . print_r($progress_data, true));
        return $progress_data;
    }
endif;

if (!function_exists('get_quests_needed_for_rule_display')) :
    /**
     * Helper function to get quests needed and progress for a single rule, handling multiple conditions.
     *
     * @param int $student_id The ID of the student.
     * @param int $rule_id    The ID of the rule.
     * @param string $valid_from The valid_from date for the reward (used for next_x_units)
     * @return array An array of quest progress data for this rule.
     */
    function get_quests_needed_for_rule_display($student_id, $rule_id,$valid_from) {
        error_log("get_quests_needed_for_rule_display: Started for student: {$student_id}, rule: {$rule_id}");
        error_log("Calling get_quests_needed_for_rule_display with RULE: {$rule_id}, STUDENT: {$student_id}");
        $conditions = get_field('conditions', $rule_id);

    // Debug: check if conditions are retrieved correctly
        error_log("Conditions for rule {$rule_id}: " . print_r($conditions, true));

        $progress_data = [];

        if (!empty($conditions)) {
            error_log("get_quests_needed_for_rule_display: Found " . count($conditions) . " condition groups.");

            foreach ($conditions as $condition_group) {
                $condition_items = $condition_group['condition_items'] ?? [];
                error_log("Condition Group: " . print_r($condition_group, true));

                $group_logic = isset($condition_group['group_logic']) ?
                    strtoupper(trim($condition_group['group_logic'])) :
                    'AND';
                $group_logic = preg_replace('/:.*/', '', $group_logic);
                $group_logic = ($group_logic === 'OR') ? 'OR' : 'AND';

                error_log("get_quests_needed_for_rule_display: Processing condition group with logic: {$group_logic}");

                foreach ($condition_items as $condition_item) {
                    error_log("Condition Item: " . print_r($condition_item, true));

                    if ($condition_item['field'] === 'quests_completed') {
                        $value = intval($condition_item['value']);
                        $quests_needed = $value;
                        $operator = $condition_item['operator'] ?? '';
                        $time_scope = $condition_item['time_scope'];
                        $time_params = $condition_item['time_parameters'];

                        error_log("Student ID: {$student_id} | Time Scope: {$time_scope} | Time Params: " . print_r($time_params, true));

                        $quests_completed_in_scope = get_student_data_with_time_scope(
                            $student_id,
                            $condition_item['field'],
                            $time_scope,
                            $time_params,
                            $valid_from
                        );
                        error_log("Quests completed: {$quests_completed_in_scope} | Operator: {$operator} | Target Value: {$value}");
                        $progress = ($quests_needed > 0) ? min(($quests_completed_in_scope / $quests_needed) * 100, 100) : 0;

                        $progress_data[] = [
                            'quests_needed'   => $quests_needed,
                            'quests_completed' => $quests_completed_in_scope,
                            'progress'        => $progress,
                            'time_scope'     => $time_scope,
                            'time_params'     => $time_params,
                            'group_logic'     => $group_logic,
                        ];

                        error_log("get_quests_needed_for_rule_display: quests_completed condition - Value: {$value}, Time scope: {$time_scope}, Quests completed in scope: {$quests_completed_in_scope}, Quests needed: {$quests_needed}, Progress: {$progress}");
                    }
                }
            }
        } else {
            error_log("get_quests_needed_for_rule_display: No conditions found for rule.");
        }

        error_log("get_quests_needed_for_rule_display: Final progress data: " . print_r($progress_data, true));
        return $progress_data;
    }
endif;

if (!function_exists('is_student_eligible_for_reward_display')) :
    /**
     * Checks if a student is eligible to claim their reward (based on include/exclude lists,
     * cooldown, redemption limits).
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $cooldown_period   The cooldown period in seconds.
     * @param int $reward_id         The Post ID of the "Reward Item" post.
     * @param int $redemption_limit  The maximum number of redemptions allowed (0 for unlimited).
     * @return bool True if eligible, false otherwise.
     */
    function is_student_eligible_for_reward_display($student_post_id, $cooldown_period, $reward_id, $redemption_limit) {
        error_log("=== STARTING ELIGIBILITY CHECK ===");
        error_log("Student ID: {$student_post_id}, Reward ID: {$reward_id}");
        error_log("Cooldown: {$cooldown_period}s, Redemption Limit: " . ($redemption_limit ?: 'Unlimited'));

        if (!function_exists('get_field') || !$student_post_id) {
            error_log("ERROR: ACF functions not found or invalid Student ID");
            return false;
        }

        // 1. Get include/exclude lists
        $include_students = get_field('include_students', $reward_id);
        $exclude_students = get_field('exclude_students', $reward_id);
        $eligibility_rules = get_field('eligibility_rules', $reward_id);

        error_log("Include Students: " . print_r($include_students, true));
        error_log("Exclude Students: " . print_r($exclude_students, true));
        error_log("Eligibility Rules: " . print_r($eligibility_rules, true));

        // 2. Exclude check (highest priority)
        if (is_array($exclude_students) && in_array($student_post_id, $exclude_students)) {
            error_log("FAIL: Student is explicitly excluded");
            return false;
        }

        // 3. Include check (if not excluded)
        if (is_array($include_students) && !empty($include_students) && !in_array($student_post_id, $include_students)) {
            error_log("FAIL: Student is not in the include list");
            return false;
        }

        error_log("PASS: Student is eligible for reward");
        return true;
    }
endif;


