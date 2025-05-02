<?php
/**
 * Shortcode functionality
 */

if (!function_exists('count_all_completed_quests')) :
    /**
     * Counts all completed quests for a student from reward_history.
     * Groups entries with identical timestamps as single quest completions.
     *
     * @param int $student_id The ID of the student.
     * @return int The total number of completed quests.
     */
    function count_all_completed_quests($student_id) {
        error_log("count_all_completed_quests: Started for student: {$student_id}");

        $args = [
            'post_type'      => 'reward_history',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'student',
                    'value'   => $student_id,
                    'compare' => '='
                ],
                [
                    'key'     => 'key',
                    'value'   => 'played_quest',
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order'   => 'ASC'
        ];

        $reward_posts = get_posts($args);
        error_log("count_all_completed_quests: Found " . count($reward_posts) . " reward_history posts.");

        $unique_timestamps = [];
        $completed_count = 0;

        foreach ($reward_posts as $post) {
            $timestamp = $post->post_date; // Using post_date as the timestamp

            if (!in_array($timestamp, $unique_timestamps)) {
                $unique_timestamps[] = $timestamp;
                $completed_count++;
                error_log("count_all_completed_quests: New unique completion at {$timestamp}");
            }
        }

        error_log("count_all_completed_quests: Unique completed quests count: {$completed_count}");
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

/**
 * Shortcode to display eligible promotion list on promotions page
 */
add_shortcode('promotions_list', 'promotions_page_shortcode_function');


if (!function_exists('promotions_page_shortcode_function')) :
    function promotions_page_shortcode_function() {

        $student_post_id = 0;
        $student_data = Points_Plus_Student_Data::get_current_student();
        $plugin_url = plugin_dir_url(__FILE__) . '../'; // Points to the plugin root URL
        $plugin_path = plugin_dir_path(__FILE__) . '../'; // Points to the plugin root server path

        $reward_css = '/assets/css/reward-system.css';
        if (file_exists($plugin_path . $reward_css)) {
            wp_enqueue_style(
                'points-plus-reward-style',
                $plugin_url . $reward_css,
                array('dashicons'), // Dashicons dependency
                filemtime($plugin_path . $reward_css) // Version based on file modification time
            );
        }
        $reward_modal_css = '/assets/css/reward-modal.css';
        if (file_exists($plugin_path . $reward_modal_css)) {
            wp_enqueue_style(
                'points-plus-reward-modal-style',
                $plugin_url . $reward_modal_css,
                filemtime($plugin_path . $reward_modal_css)
            );
        }
        $alert_css = '/assets/css/alert.css';
        if (file_exists($plugin_path . $alert_css)) {
            wp_enqueue_style(
                'points-plus-alert-style',
                $plugin_url . $alert_css,
                filemtime($plugin_path . $alert_css)
            );
        }


        $countdown_js = '/assets/js/countdown-timer.js';
        if (file_exists($plugin_path . $countdown_js)) {
            wp_enqueue_script(
                'points-plus-countdown-timer',
                $plugin_url . $countdown_js,
                array('jquery'), // jQuery dependency
                filemtime($plugin_path . $countdown_js),
                true // Load in footer
            );
        }
        wp_enqueue_script('your-plugin-script', plugin_dir_url(__FILE__) . 'js/your-script.js', ['jquery'], null, true);
        wp_localize_script('your-plugin-script', 'reward_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
            'get_reward_modal_nonce' => wp_create_nonce('get_reward_modal_nonce'), // Add this line
            'student_data' => $student_data,
            'student_identifier' => $student_data ? $student_data['id'] : '',
        ));
        // Reward Handler with AJAX localization
        $reward_js = '/assets/js/reward-handler.js';
        if (file_exists($plugin_path . $reward_js)) {
            wp_enqueue_script(
                'points-plus-reward-handler',
                $plugin_url . $reward_js,
                array('jquery'),
                filemtime($plugin_path . $reward_js),
                true
            );
            $student_data = [];
            if (function_exists('ms_get_current_student_data')) {
                $student_data = ms_get_current_student_data();
            }

            wp_localize_script(
                'points-plus-reward-handler',
                'reward_ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'redeem_reward_nonce' => wp_create_nonce('redeem_reward_nonce'),
                    'daily_reward_nonce' => wp_create_nonce('daily_reward_nonce'),
                    'student_data' => $student_data,
                    'student_identifier' => $student_data ? $student_data['id'] : '',
                    'i18n' => array(
                        'error_message' => __('An error occurred. Please try again.', 'points-plus'),
                        'success_message' => __('Action completed successfully!', 'points-plus')
                    )
                )
            );
        }


        try {
            $student_post_id = Points_Plus_Student_Data::get_current_student_id(); // Use the helper function
            if (!$student_post_id) {
                return '<p>Error: Could not find student data.</p>';
            }
            $student_post = get_post($student_post_id);
            if (!$student_post) {
                return '<p>Error: Could not retrieve student data.</p>';
            }

        } catch (Exception $e) {
            return '<p>Error: ' . $e->getMessage() . '</p>';
        }


        $output = '<div class="promotions-page">';
        $points = get_field('student_points', $student_post_id) ?: 0;
        $coins = get_field('student_coins', $student_post_id) ?: 0;

        $now = current_time('Y-m-d H:i:s');

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
            $output .= '<div class="promotions-grid">';
            foreach ($reward_items as $post) {
                $promotion_id = $post->ID;
                $promotion_name = get_the_title($promotion_id);
                $required_coins = get_field('required_coins', $post->ID) ?: 0;
                $min_quests = get_field('minimum_quests_completed', $post->ID) ?: 0;
                $valid_from = get_field('valid_from', $post->ID);
                $valid_until = get_field('valid_until', $post->ID);

                $is_eligible = true;
                $eligibility_reasons = [];

                // 1. Check Exclude Students FIRST
                $exclude_students = get_field('exclude_students', $promotion_id);
                if ($exclude_students && in_array($student_post_id, $exclude_students)) {
                    $is_eligible = false;
                    $eligibility_reasons[] = "Student excluded from reward.";
                } else {
                    $eligibility_reasons[] = "Student not excluded.";
                }

                // 2. Then check Include Students
                if ($is_eligible) {
                    $include_students = get_field('include_students', $promotion_id);
                    if ($include_students && !in_array($student_post_id, $include_students)) {
                        $is_eligible = false;
                        $eligibility_reasons[] = "Student not in included students.";
                    } else {
                        $eligibility_reasons[] = "Student in included students (or no inclusion list).";
                    }
                }

                // 3. Check Date Range
                if ($is_eligible) {
                    if ($valid_from && strtotime($now) < strtotime($valid_from)) {
                        $is_eligible = false;
                        $eligibility_reasons[] = "Reward not yet valid.";
                    } else if ($valid_until && strtotime($now) > strtotime($valid_until)) {
                        $is_eligible = false;
                        $eligibility_reasons[] = "Reward has expired.";
                    } else {
                        $eligibility_reasons[] = "Reward within valid date range.";
                    }
                }
                // 4. Check Minimum Quests (Visibility Check)
                $total_completed_quests = count_all_completed_quests($student_post_id);
                if ($total_completed_quests < $min_quests) {
                    $is_eligible = false;
                    $eligibility_reasons[] = "Insufficient quests completed to view reward ($total_completed_quests / $min_quests).";
                } else {
                    $eligibility_reasons[] = "Sufficient quests completed to view reward ($total_completed_quests / $min_quests).";
                }

                if ($is_eligible) {
                    // 5.  REUSE the eligibility check!
                    $reward_data = get_reward_data($promotion_id); // Get reward data for cooldown, etc.
                    $is_eligible = is_student_eligible_for_reward_display(
                        $student_post_id,
                        $reward_data['cooldown_period'],
                        $promotion_id,
                        $reward_data['redemption_limit']
                    );
                }

                if ($is_eligible) {
                    $output .= '<div class="promotion-card">';
                    $output .= '<h3 class="promotion-name">' . esc_html($promotion_name) . '</h3>';
                    $client_description = get_field('client_description', $post->ID);
                    if ($client_description) {
                        $output .= '<p class="promotion-description">' . wp_kses_post($client_description) . '</p>';
                    }
                    if ($required_coins > 0) {
                        $output .= '<p class="promotion-cost">Requires: ' . esc_html($required_coins) . ' Coins</p>';
                    }

                    // Display Quest Progress
                    $quest_progress_data = get_quest_completion_progress_for_display($student_post_id, $promotion_id);
                    if (is_array($quest_progress_data) && count($quest_progress_data) > 0) {
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

                            $output .= '<div class="reward-progress-container">';
                            $output .= '<p class="condition-logic">' . esc_html($group_logic) . '</p>';
                            $output .= '<p class="progress-description">' . esc_html($time_description) . '</p>';
                            $output .= '<div class="progress-bar">';
                            $output .= '<div class="progress-fill" style="width: ' . esc_attr($progress_percentage) . '%;"></div>';
                            $output .= '</div>';
                            $output .= '<p class="progress-text">' . esc_html($quests_completed_for_claim) . ' / ' . esc_html($quests_needed_for_claim) . ' quests completed (' . esc_html($progress_percentage) . '%)</p>';
                            $output .= '</div>';
                        }
                        $can_claim_now = ($coins >= $required_coins);
                    } else {
                        $can_claim_now = ($coins >= $required_coins);
                    }

                    $output .= '<button class="redeem-button" data-reward-id="' . esc_attr($post->ID) . '">Redeem</button>';

                    if ($valid_until) {
                        $now_date = new DateTime();
                        $end_date = new DateTime($valid_until);
                        $interval = $now_date->diff($end_date);
                        $total_hours_left = ($interval->d * 24) + $interval->h;
                        $is_urgent = $total_hours_left < 24;

                        $output .= '<div class="clean-countdown-container" data-end-time="' . esc_attr($valid_until) . '" data-urgent="' . ($is_urgent ? 'true' : 'false') . '">';
                        $output .= '<div class="time-left-header">ඉතිරි කාලය :</div>';
                        $output .= '<div class="time-units">';
                        if ($interval->d > 0) {
                            $output .= '<div class="time-unit days">';
                            $output .= '<span class="value">' . esc_html($interval->d) . '</span>';
                            $output .= '<span class="label">days</span>';
                            $output .= '</div>';
                        }
                        $output .= '<div class="time-unit hours">';
                        $output .= '<span class="value">' . sprintf('%02d', $interval->h) . '</span>';
                        $output .= '<span class="label">hrs</span>';
                        $output .= '</div>';
                        $output .= '<div class="time-unit minutes">';
                        $output .= '<span class="value">' . sprintf('%02d', $interval->i) . '</span>';
                        $output .= '<span class="label">mins</span>';
                        $output .= '</div>';
                        $output .= '<div class="time-unit seconds">';
                        $output .= '<span class="value">' . sprintf('%02d', $interval->s) . '</span>';
                        $output .= '<span class="label">secs</span>';
                        $output .= '</div>';
                        $output .= '</div></div>';
                    }
                    $output .= '</div>'; // Close promotion-card
                }
            }
            $output .= '</div>'; // Close promotions-grid
        } else {
            $output .= '<p>No eligible promotions available at the moment.</p>';
        }

        $output .= '</div>';
        return $output;

    }
endif;
