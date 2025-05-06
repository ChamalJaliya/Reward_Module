<?php
/**
 * Reward system functionality
 */
include_once plugin_dir_path(__FILE__) . 'notification-system.php';
include_once(plugin_dir_path(__FILE__) . 'manage-messege-helper.php');

// Helper function to get the reward points, coins, cooldown, and other details from a specific "Reward Item" post.
if (!function_exists('get_reward_data')) :
    /**
     * @param int $reward_id The ID of the Reward Item post.
     * @return array An array containing 'success' (bool), and all relevant reward details based on promotion type.
     */
    function get_reward_data($reward_id) {

        if (!function_exists('get_field')) {
            error_log("get_reward_data: ACF is not active.");
            return ['success' => false, 'message' => 'ACF is not active. Please ensure Advanced Custom Fields (ACF) Pro is installed and activated.'];
        }

        // 1. Get the "Reward Item" post
        $reward_post = get_post($reward_id);

        if (!$reward_post || $reward_post->post_type !== 'reward-item') {
            error_log("get_reward_data: Reward Item post not found or invalid.");
            return ['success' => false, 'message' => 'Reward Item post not found.'];
        }

        // 2. Get Reward Details
        $reward_data = [
            'success' => true,
            'promotion_name' => get_field('promotion_name', $reward_id) ?: '',
            'promotion_type' => get_field('promotion_type', $reward_id) ?: '',
            'description' => get_field('description', $reward_id) ?: '',
            'valid_from' => get_field('valid_from', $reward_id) ?: '',
            'valid_until' => get_field('valid_until', $reward_id) ?: '',
            'cooldown_period' => intval(get_field('cooldown_period', $reward_id) ?: 0),
            'client_description' => get_field('client_description', $reward_id) ?: '',
            'include_students' => get_field('include_students', $reward_id) ?: [],
            'exclude_students' => get_field('exclude_students', $reward_id) ?: [],
            'points' => 0,
            'coins' => 0,
        ];

        // Dynamically fetch fields based on promotion type
        $promotion_type = $reward_data['promotion_type'];

        if ($promotion_type === 'reload') {
            $reward_data['required_coins'] = intval(get_field('required_coins', $reward_id) ?: 0);
            $reward_data['reload_value'] = intval(get_field('reload_value', $reward_id) ?: 0);
        } elseif ($promotion_type === 'multiplication') {
            $reward_data['multiplication_type'] = get_field('multiplication_type', $reward_id) ?: '';
            $reward_data['multifaction_factor'] = intval(get_field('multifaction_factor', $reward_id) ?: 0);
            $reward_data['required_quests'] = get_field('required_quests', $reward_id) ?: [];
            $reward_data['quest_completion_count'] = intval(get_field('quest_completion_count', $reward_id) ?: 0);
        } elseif ($promotion_type === 'addition') {
            $reward_data['additional_type'] = get_field('additional_type', $reward_id) ?: '';
            $reward_data['additional_reward'] = intval(get_field('additional_reward', $reward_id) ?: 0);
            // Set points and coins based on additional_type
            $additional_reward_value = $reward_data['additional_reward'];
            switch ($reward_data['additional_type']) {
                case 'coins':
                    $reward_data['coins'] = $additional_reward_value;
                    break;
                case 'points':
                    $reward_data['points'] = $additional_reward_value;
                    break;
                case 'both':
                    $reward_data['points'] = $additional_reward_value;
                    $reward_data['coins'] = $additional_reward_value;
                    break;
            }
        }

        $reward_data['redemption_limit'] = intval(get_field('redemption_limit', $reward_id) ?: 0);

        // 3. Validity Checks
        $now = current_time('timestamp');

        if ($reward_data['valid_from'] && strtotime($reward_data['valid_from']) > $now) {
            error_log("get_reward_data: Reward is not yet available. Valid From: " . $reward_data['valid_from'] . " (timestamp: " . strtotime($reward_data['valid_from']) . "), Current Time: " . $now);
            $reward_data['success'] = false;
            $reward_data['message'] =  'Reward is not yet available.';
            return $reward_data;
        }

        if ($reward_data['valid_until'] && strtotime($reward_data['valid_until']) < $now) {
            error_log("get_reward_data: Reward has expired. Valid Until: " . $reward_data['valid_until'] . " (timestamp: " . strtotime($reward_data['valid_until']) . "), Current Time: " . $now);
            $reward_data['success'] = false;
            $reward_data['message'] =  'Reward has expired.';
            return $reward_data;
        }


        return $reward_data;
    }
endif;

// Helper function to get the student post ID by email
if (!function_exists('is_student_eligible_for_reward')) :
    /**
     * Checks if a student is eligible to claim their reward (based on include/exclude lists,
     * cooldown, redemption limits, and eligibility rules).
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $cooldown_period   The cooldown period in seconds.
     * @param int $reward_id         The Post ID of the "Reward Item" post.
     * @param int $redemption_limit  The maximum number of redemptions allowed (0 for unlimited).
     * @return bool True if eligible, false otherwise.
     */
    function is_student_eligible_for_reward($student_post_id, $cooldown_period, $reward_id, $redemption_limit) {
        error_log("=== STARTING ELIGIBILITY CHECK ===");

        if (!function_exists('get_field') || !$student_post_id) {
            error_log("ERROR: ACF functions not found or invalid Student ID");
            return ['eligible' => false, 'message' => 'ACF functions not found or invalid student ID'];
        }

        // 1. Get include/exclude lists
        $include_students = get_field('include_students', $reward_id);
        $exclude_students = get_field('exclude_students', $reward_id);
        $eligibility_rules = get_field('eligibility_rules', $reward_id);
        $valid_from = get_field('valid_from', $reward_id);


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

        // 4. Check eligibility rules if they exist
        if (!empty($eligibility_rules)) {
            error_log("=== STARTING RULES CHECK ===");

            $passed_all_rules = true;
            $rule_failure_message = '';

            foreach ($eligibility_rules as $rule_id) {
                $rule_status = get_field('status', $rule_id);

                if ($rule_status !== 'active') {
                    error_log("SKIP: Rule ID {$rule_id} is inactive (status: {$rule_status})");
                    continue; // Skip this rule
                }
                $rule_passed = evaluate_eligibility_rule($student_post_id, $rule_id, $valid_from);

                if (!$rule_passed['passed']) {
                    error_log("FAIL: Student failed rule ID {$rule_id}");
                    $passed_all_rules = false;
                    $rule_failure_message = $rule_passed['message'];
                    break;
                }

                error_log("PASS: Student passed rule ID {$rule_id}");
            }

            if (!$passed_all_rules) {
                error_log("FAIL: $rule_failure_message");
                return ['eligible' => false, 'message' => "Student failed one or more eligibility rules"];
            }
        }

        // 5. Check cooldown and redemption limits
        if ($cooldown_period > 0 || $redemption_limit > 0) {
            error_log("===COOLDOWN & REDEMPTION LIMIT CHECK ====");

            $claim_data = manage_reward_claims($student_post_id, $reward_id, $redemption_limit);
            $most_recent_timestamp = $claim_data['most_recent_timestamp'];
            $claim_count = $claim_data['claim_count'];

            error_log("Last Claimed: " . ($most_recent_timestamp ?: 'Never'));
            error_log("Total Claims: {$claim_count}" . ($redemption_limit > 0 ? "/{$redemption_limit}" : ''));

            // Check redemption limit first
            if ($redemption_limit > 0 && $claim_count >= $redemption_limit) {
                error_log("FAIL: Redemption limit reached");
                return ['eligible' => false, 'message' => 'Redemption limit reached'];
            }

            // Then check cooldown period if they've claimed before
            if ($most_recent_timestamp) {
                $now = current_time('timestamp');
                $last_claimed_time = strtotime($most_recent_timestamp);
                $time_since_last_claim = $now - $last_claimed_time;
                $cooldown_remaining = $cooldown_period - $time_since_last_claim;
                $cooldown_period_readable = seconds_to_readable_custom($cooldown_period);


                if ($cooldown_remaining > 0) {
                    $readable_time = seconds_to_readable_custom($cooldown_remaining);
                    error_log("FAIL: Cooldown not expired - {$readable_time} remaining");
                    return [
                        'eligible' => false,
                        'message' => sprintf(
                            esc_html( points_plus_translate('Sorry. You have claimed this Reward within the last %s. Please try again in %s.') ),
                            $cooldown_period_readable,
                            trim($readable_time)
                        )
                    ];
//                    return false;
                }
            }
        }

        error_log("PASS: Student is eligible for reward");
        return ['eligible' => true, 'message' => ''];
    }
endif;

if (!function_exists('evaluate_eligibility_rule')) :
    /**
     * Evaluates a single eligibility rule against a student
     *
     * @param int    $student_post_id
     * @param int    $rule_id
     * @param string $valid_from  The valid_from date for the reward (used for next_x_units)
     * @return array  // Changed return type to array
     */
    function evaluate_eligibility_rule($student_post_id, $rule_id, $valid_from) {
        error_log("=== STARTING RULE EVALUATION ===");
        error_log("Evaluating rule ID: {$rule_id} for student {$student_post_id}");
        error_log("Reward start date: {$valid_from} for reward");

        // Get rule data
        $rule_data = get_post($rule_id);

        if (!$rule_data) {
            error_log("ERROR: Rule with ID {$rule_id} not found.");
            return ['passed' => false, 'message' => 'Eligibility rule not found.'];
        }

        // Get rule status first
        $rule_status = get_field('status', $rule_id);
        if ($rule_status !== 'active') {
            error_log("FAIL: Rule {$rule_id} is not active (status: {$rule_status})");
            return ['passed' => false, 'message' => 'Eligibility rule is inactive.'];
        }

        $conditions = get_field('conditions', $rule_id);
        if (empty($conditions)) {
            error_log("PASS: Rule {$rule_id} has no conditions - automatically passes");
            return ['passed' => true];
        }

        // Track results for all groups
        $group_results = [];
        $has_or_groups = false;
        $has_passing_or_group = false;
        $overall_passed = true; // Track if the rule overall passes

        foreach ($conditions as $group_index => $condition_group) {
            $group_logic = isset($condition_group['group_logic']) ?
                strtoupper(trim($condition_group['group_logic'])) :
                'AND';

            // Clean up group logic string (remove any extra text)
            $group_logic = preg_replace('/:.*/', '', $group_logic);
            $group_logic = ($group_logic === 'OR') ? 'OR' : 'AND'; // Default to AND if not OR

            error_log("Processing group {$group_index} with logic: {$group_logic}");

            if ($group_logic === 'OR') {
                $has_or_groups = true;
            }

            $condition_items = $condition_group['condition_items'] ?? [];

            $group_passed = !(($group_logic === 'OR'));
            $group_fail_message = ''; // Store the message for the first failing condition

            foreach ($condition_items as $item_index => $condition) {
                if (!is_array($condition) || !isset($condition['field']) || !isset($condition['operator']) || !isset($condition['value'])) {
                    error_log("Invalid condition structure in group {$group_index}, item {$item_index}");
                    $group_passed = ($group_logic === 'OR') ? $group_passed : false;
                    $overall_passed = false; // Rule fails if any condition is invalid
                    $group_fail_message = 'Invalid condition structure.';
                    break; // Stop checking conditions in this group
                }

                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $time_scope = $condition['time_scope'] ?? 'lifetime';
                $time_params = $condition['time_parameters'] ?? [];


                // Get student data based on time scope
                $student_value = get_student_data_with_time_scope(
                    $student_post_id,
                    $field,
                    $time_scope,
                    $time_params,
                    $valid_from
                );

                error_log("Student value: {$student_value}");

                $comparison_result = compare_values($student_value, $operator, $value);
                $result_text = $comparison_result ? 'PASS' : 'FAIL';
                error_log("Comparison result: {$result_text}");

                // Apply group logic
                if ($group_logic === 'AND') {
                    $group_passed = $group_passed && $comparison_result;
                    if (!$group_passed) {
                        error_log("AND group condition failed - short-circuiting this group");
                        $overall_passed = false;
                        $group_fail_message = "Condition '{$field} {$operator} {$value}' failed.";
                        break; // No need to check other conditions in this AND group
                    }
                } else { // OR logic
                    $group_passed = $group_passed || $comparison_result;
                    if ($group_passed) {
                        error_log("OR group condition passed - short-circuiting this group");
                        break; // No need to check other conditions in this OR group
                    }
                }
            }

            $group_result_text = $group_passed ? 'PASS' : 'FAIL';
            error_log("Group {$group_index} result: {$group_result_text}");

            $group_results[] = [
                'logic' => $group_logic,
                'result' => $group_passed,
                'message' => $group_fail_message // Store the message
            ];

            // Track if we have any passing OR groups
            if ($group_logic === 'OR' && $group_passed) {
                $has_passing_or_group = true;
            }

            if (!$group_passed) {
                $overall_passed = false;
            }
        }

        // Determine final rule result
        $final_result = false;
        $final_message = "Unknown reason";

        if ($has_or_groups) {
            // For rules with OR groups, we need at least one OR group to pass
            $final_result = $has_passing_or_group;
            $final_message = $has_passing_or_group ?
                "PASS: At least one OR group passed" :
                "FAIL: No OR groups passed";
            error_log($final_message);
        } else {
            // For rules with only AND groups, all must pass
            $final_result = $overall_passed;
            $final_message = $overall_passed ?
                "PASS: All AND groups passed" :
                "FAIL: At least one AND group failed. " . ($group_fail_message ?: 'Unknown condition failed');
            error_log($final_message);
        }
        error_log("Final rule evaluation: " . ($final_result ? 'PASS' : 'FAIL'));
        error_log("=== END RULE EVALUATION ===");
        return ['passed' => $final_result, 'message' => $final_message]; // Return the result array
    }
endif;

if (!function_exists('get_student_data_with_time_scope')) :
    /**
     * Gets student data with time scope consideration.
     *
     * @param int    $student_id The ID of the student.
     * @param string $field      Field name: 'quests_attempted', 'quests_completed', 'points_balance', 'coins_balance'.
     * @param string $time_scope Time scope: 'lifetime', 'current_session', 'last_x_units', 'specific_range'
     * @param array  $time_params Optional parameters for the time scope
     * @param string $valid_from The valid_from date for the reward (used for next_x_units)
     * @return int The calculated or retrieved value.
     */
    function get_student_data_with_time_scope($student_id, $field, $time_scope = 'lifetime', $time_params = [] ,$valid_from = '') {
        error_log("=== GET STUDENT DETAIL WITH TIME SCOPE ===");
        // Handle balance fields (not time-scoped)
        if (in_array($field, ['points_balance', 'coins_balance'])) {
            switch ($field) {
                case 'points_balance':
                    return (int)(get_field('student_points', $student_id) ?: 0);
                case 'coins_balance':
                    return (int)(get_field('student_coins', $student_id) ?: 0);
            }
        }

        // Handle quest fields (time-scoped)
        if (in_array($field, ['quests_attempted', 'quests_completed'])) {
            $quest_type = str_replace('quests_', '', $field);
            $history = get_student_quest_history($student_id, $quest_type);

            if (empty($history)) {
                error_log("No quest history found for student {$student_id}");
                return 0;
            }

            $now = current_time('timestamp');
            $filtered_history = [];

            foreach ($history as $entry) {
                $entry_timestamp = strtotime($entry['timestamp']);

                switch ($time_scope) {
                    case 'lifetime':
                        // Include all records
                        $filtered_history[] = $entry;
                        break;

                    case 'current_session':
                        $session_start = get_user_meta($student_id, 'current_session_start', true);
                        if ($session_start && $entry_timestamp >= strtotime($session_start)) {
                            $filtered_history[] = $entry;
                        }
                        break;

                    case 'last_x_units':
                        $x_value = isset($time_params['x_value']) ? (int)$time_params['x_value'] : 1;
                        $time_unit = isset($time_params['time_unit']) ? $time_params['time_unit'] : 'days';

                        // Validate time unit
                        $valid_units = ['minutes', 'hours', 'days', 'weeks', 'months'];
                        if (!in_array($time_unit, $valid_units)) {
                            $time_unit = 'days';
                        }

                        $cutoff = strtotime("-{$x_value} {$time_unit}", $now);
                        if ($entry_timestamp >= $cutoff) {
                            $filtered_history[] = $entry;
                        }
                        break;
                    case 'next_x_units':
                        if (empty($valid_from)) {
                            error_log("next_x_units scope requires valid_from parameter");
                            break;
                        }

                        $x_value = isset($time_params['x_value']) ? (int)$time_params['x_value'] : 1;
                        $time_unit = isset($time_params['time_unit']) ? $time_params['time_unit'] : 'days';

                        // Validate time unit
                        $valid_units = ['minutes', 'hours', 'days', 'weeks', 'months'];
                        if (!in_array($time_unit, $valid_units)) {
                            $time_unit = 'days';
                        }

                        $start_time = strtotime($valid_from);
                        $end_time = strtotime("+{$x_value} {$time_unit}", $start_time);

                        if ($entry_timestamp >= $start_time && $entry_timestamp <= $end_time) {
                            $filtered_history[] = $entry;
                        }
                        break;


                    case 'specific_range':
                        $start_date = isset($time_params['start_date']) ? strtotime($time_params['start_date']) : 0;
                        $end_date = isset($time_params['end_date']) ? strtotime($time_params['end_date'] . ' 23:59:59') : PHP_INT_MAX;

                        if ($entry_timestamp >= $start_date && $entry_timestamp <= $end_date) {
                            $filtered_history[] = $entry;
                        }
                        break;

                    default:
                        error_log("Unknown time scope: {$time_scope}");
                        break;
                }
            }

            error_log("Found " . count($filtered_history) . " matching entries for scope {$time_scope}");
            return count($filtered_history);
        }

        error_log("ERROR: Unknown field: {$field}");
        return 0;
    }
endif;

if (!function_exists('get_student_quest_history')) :
    /**
     * Gets the student's quest history from reward_history.
     *
     * @param int    $student_id      The ID of the student.
     * @param string $quest_count_type 'attempted' or 'completed'.
     * @return array An array of quest history data with timestamp and type.
     */
    function get_student_quest_history($student_id, $quest_count_type) {
        $quest_history = [];

        if ($quest_count_type === 'completed') {
            $args = [
                'post_type' => 'reward_history',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'student',
                        'value' => $student_id,
                        'compare' => '='
                    ],
                    [
                        'key' => 'key',
                        'value' => 'played_quest',
                        'compare' => '='
                    ]
                ]
            ];

            $reward_posts = get_posts($args);

            foreach ($reward_posts as $post) {
                $timestamp = get_the_date('Y-m-d H:i:s', $post->ID);
                $quest_id = get_field('quest', $post->ID);

                // Maintain same format as original function
                $quest_history[] = [
                    'timestamp' => $timestamp,
                    'quest_count_type' => $quest_count_type,
                    // Additional optional fields you might want to include
                    'quest_id' => $quest_id,
                    'type' => get_field('type', $post->ID),
                    'value' => get_field('value', $post->ID)
                ];
            }

            // If you need to deduplicate entries with same timestamp
            $unique_history = [];
            $seen_timestamps = [];

            foreach ($quest_history as $entry) {
                if (!in_array($entry['timestamp'], $seen_timestamps)) {
                    $seen_timestamps[] = $entry['timestamp'];
                    $unique_history[] = $entry;
                }
            }

            return $unique_history;
        }
        // For attempted quests (keep your existing implementation)
        else if ($quest_count_type === 'attempted') {
            // Your original attempted quests implementation here
            // ...
        }

        return $quest_history;
    }
endif;

if (!function_exists('count_quests_by_status')) :
    /**
     * Counts quests by status within a time scope using ACF data
     */
    function count_quests_by_status($student_id, $status, $time_scope, $time_params) {
        error_log("Counting quests for student {$student_id} with status {$status} and scope {$time_scope}");

        // Get all student_quests posts for this student
        $student_quests = get_posts([
            'post_type' => 'student_quests',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'student',
                    'value' => $student_id,
                    'compare' => '='
                ]
            ]
        ]);

        if (empty($student_quests)) {
            error_log("No student quests found for student {$student_id}");
            return 0;
        }

        $count = 0;
        $now = current_time('timestamp');

        foreach ($student_quests as $student_quest) {
            $quest_progress = get_field('quest_progress', $student_quest->ID);

            if (empty($quest_progress)) {
                continue;
            }

            foreach ($quest_progress as $progress) {
                // Skip if status doesn't match
                if ($progress['status'] !== $status) {
                    continue;
                }

                $status_date = strtotime($progress['status_date']);

                // Check time scope conditions
                $include = true;

                switch ($time_scope) {
                    case 'last_x_units':
                        $x_value = isset($time_params['x_value']) ? (int)$time_params['x_value'] : 1;
                        $time_unit = isset($time_params['time_unit']) ? $time_params['time_unit'] : 'days';

                        // Calculate cutoff time based on unit
                        $cutoff = strtotime("-{$x_value} {$time_unit}", $now);
                        if ($status_date < $cutoff) {
                            $include = false;
                        }
                        break;

                    case 'specific_range':
                        $start_date = isset($time_params['start_date']) ? strtotime($time_params['start_date']) : 0;
                        $end_date = isset($time_params['end_date']) ? strtotime($time_params['end_date']) : PHP_INT_MAX;

                        if ($status_date < $start_date || $status_date > $end_date) {
                            $include = false;
                        }
                        break;

                    case 'current_session':
                        // Implement your session logic here
                        // Example: if (!is_within_current_session($status_date)) $include = false;
                        break;
                }

                if ($include) {
                    $count++;
                    error_log("Including quest progress: " . print_r($progress, true));
                }
            }
        }

        error_log("Final count for student {$student_id}: {$count} matching quests");
        return $count;
    }
endif;

if (!function_exists('compare_values')) :
    /**
     * Compares two values with the given operator
     */
    function compare_values($value1, $operator, $value2) {
        switch ($operator) {
            case '==': return $value1 == $value2;
            case '!=': return $value1 != $value2;
            case '>':  return $value1 > $value2;
            case '<':  return $value1 < $value2;
            case '>=': return $value1 >= $value2;
            case '<=': return $value1 <= $value2;

            default:   return false;
        }
    }
endif;

if (!function_exists('seconds_to_readable')) :
    /**
     * Converts seconds to human-readable format
     */
    function seconds_to_readable($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . esc_html( points_plus_translate( 'day' . ($days > 1 ? 's' : '') ) );
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . esc_html( points_plus_translate( 'hour' . ($hours > 1 ? 's' : '') ) );
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . esc_html( points_plus_translate( 'minute' . ($minutes > 1 ? 's' : '') ) );
        }
        if ($seconds > 0 && empty($parts)) {
            $parts[] = $seconds . ' ' . esc_html( points_plus_translate( 'second' . ($seconds > 1 ? 's' : '') ) );
        }

        return implode(' ', $parts);
    }
endif;

if (!function_exists('seconds_to_readable_custom')) :
    function seconds_to_readable_custom($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($days > 0) {
            $label = esc_html(points_plus_translate('day' . ($days > 1 ? 's' : '')));
            $parts[] = ($days > 1 ? "{$label} {$days}" : $label);
        }
        if ($hours > 0) {
            $label = esc_html(points_plus_translate('hour' . ($hours > 1 ? 's' : '')));
            $parts[] = ($hours > 1 ? "{$label} {$hours}" : $label);
        }
        if ($minutes > 0) {
            $label = esc_html(points_plus_translate('minute' . ($minutes > 1 ? 's' : '')));
            $parts[] = ($minutes > 1 ? "{$label} {$minutes}" : $label);
        }
        if ($seconds > 0 && empty($parts)) {
            $label = esc_html(points_plus_translate('second' . ($seconds > 1 ? 's' : '')));
            $parts[] = ($seconds > 1 ? "{$label} {$seconds}" : $label);
        }

        return implode(' ', $parts);
    }

endif;


// AJAX handler for redeeming rewards
add_action('wp_ajax_redeem_reward', 'handle_redeem_reward_ajax');
add_action('wp_ajax_nopriv_redeem_reward', 'handle_redeem_reward_ajax');

if (!function_exists('handle_redeem_reward_ajax')) :
    function handle_redeem_reward_ajax() {
        // Initialize response array

        $response = [
            'success' => false,
            'messages' => [],
            'data' => [],
            'needs_confirmation' => false
        ];

        try {
            // 1. Security check (uncomment when ready)
            /*
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'redeem_reward_nonce')) {
                throw new Exception(__('Security check failed!', 'your-theme-text-domain'));
            }
            */

            // 3. Find student post ID
            $student_post_id = Points_Plus_Student_Data::get_current_student_id();

            if (!$student_post_id) {
                $response = add_system_message(
                    $response,
                    esc_html( points_plus_translate('Your student profile could not be found. Please contact support.') ),
                    'error',
                    'STUDENT_NOT_FOUND'
                );
                throw new Exception('Could not find student profile.');
            }

            // 4. Validate reward ID
            $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 0;
            if (!$reward_id) {
                $response = add_system_message(
                    $response,
                    esc_html( points_plus_translate('Invalid reward selected. Please refresh the page and try again.') ),
                    'error',
                    'INVALID_REWARD'
                );
                throw new Exception(__('Invalid reward ID.', 'your-theme-text-domain'));
            }

            // 5. Get reward data
            $reward_data = get_reward_data($reward_id);
            if (!$reward_data['success']) {
                $error_msg = $reward_data['message'] ?? 'This reward is not currently available.';

                // Special handling for date-related messages
                if (strpos($error_msg, 'not yet available') !== false) {
                    $valid_from = isset($reward_data['valid_from']) ? date('F j, Y', strtotime($reward_data['valid_from'])) : __('a future date', 'your-theme-text-domain');
                    $error_msg = sprintf(
                        esc_html( points_plus_translate('This reward will be available starting %s.') ),
                        $valid_from
                    );
                } elseif (strpos($error_msg, 'has expired') !== false) {
                    $valid_until = isset($reward_data['valid_until']) ? date('F j, Y', strtotime($reward_data['valid_until'])) : __('the past', 'your-theme-text-domain');
                    $error_msg = sprintf(
                        esc_html( points_plus_translate('This reward expired on %s and is no longer available.') ),
                        $valid_until
                    );
                }

                $response = add_system_message(
                    $response,
                    $error_msg,
                    'error',
                    'REWARD_UNAVAILABLE'
                );
                throw new Exception($error_msg);
            }

            // 6. Check eligibility
            $eligibility = is_student_eligible_for_reward(
                $student_post_id,
                $reward_data['cooldown_period'],
                $reward_id,
                $reward_data['redemption_limit']
            );

            // Crucial: Check if $eligibility is an array and has the 'eligible' key
            if (!is_array($eligibility) || !isset($eligibility['eligible']) || !$eligibility['eligible']) {
                $error_code = 'ELIGIBILITY_CHECK_FAILED';
                $error_message = (is_array($eligibility) && isset($eligibility['message']))
                    ? $eligibility['message']
                    : esc_html( points_plus_translate('You are not eligible to claim this reward at this time.') );

                // Map specific error cases to better messages
                if (strpos($error_message, 'Redemption limit reached') !== false) {
                    $error_code = 'REDEMPTION_LIMIT';
                    $error_message = sprintf(
                        esc_html( points_plus_translate('You have already claimed this reward the maximum %d time(s).') ),
                        $reward_data['redemption_limit']
                    );
                } elseif (strpos($error_message, 'Cooldown not expired') !== false) {
                    $error_code = 'COOLDOWN_ACTIVE';
                    // Use explode to split the string
                    $parts = explode('Cooldown not expired - Time remaining: ', $error_message);
                    // Check if explode was successful and we have a second part
                    if (isset($parts[1])) {
                        $remaining_time = trim($parts[1]); // Extract and trim the time
                    } else {
                        $remaining_time = ''; // Or some default value or error handling
                    }

                    $error_message = sprintf(
                        esc_html( points_plus_translate('You can claim this reward again in %s.') ),
                        $remaining_time
                    );
                } elseif (strpos($error_message, 'Student is explicitly excluded') !== false) {
                    $error_code = 'EXCLUDED_STUDENT';
                    $error_message =  esc_html( points_plus_translate('This reward is not available for your account.') );
                } elseif (strpos($error_message, 'Student is not in the include list') !== false) {
                    $error_code = 'NOT_IN_INCLUDE_LIST';
                    $error_message =  esc_html( points_plus_translate('This reward is not available for your account.') );
                } elseif (strpos($error_message, 'failed one or more eligibility rules') !== false) {
                    $error_code = 'ELIGIBILITY_RULES_FAILED';
                    $error_message = esc_html( points_plus_translate('Please complete remaining required quests for claim this reward') );
                }

                $response = add_system_message(
                    $response,
                    $error_message,
                    'error',
                    $error_code
                );
                wp_send_json_error($response);
                wp_die();
            }

            // 7. Special handling for reload rewards requiring confirmation
            if ($reward_data['promotion_type'] === 'reload') {
                error_log('handle_redeem_reward_ajax: Processing reload reward');
                $phone_number = get_field('student_mobile', $student_post_id);
                error_log('handle_redeem_reward_ajax: Phone Number:'  . print_r($phone_number, true));
                $current_coins = get_field('student_coins', $student_post_id) ?: 0;
                error_log('handle_redeem_reward_ajax: Current Coins:'  . print_r($current_coins, true));

                if (empty($phone_number)) {
                    $response = add_system_message(
                        $response,
                        esc_html( points_plus_translate('No mobile number found in your profile. Please update your profile to redeem mobile reloads.') ),
                        'error',
                        'MISSING_PHONE'
                    );
                    throw new Exception('No mobile number found in profile.');
                }

                if ($current_coins < $reward_data['required_coins']) {
                    $response = add_system_message(
                        $response,
                        sprintf(
                            esc_html( points_plus_translate('You need %d more coins to redeem this reward (you have %d).') ),
                            $reward_data['required_coins'] - $current_coins,
                            $current_coins
                        ),
                        'error',
                        'INSUFFICIENT_COINS'
                    );
                    throw new Exception('Insufficient coins for reload reward.');
                }

                $is_confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';

                // If not yet confirmed, return confirmation request
                if (!$is_confirmed) {
                    $response = [
                        'success' => true,
                        'needs_confirmation' => true,
                        'message' => sprintf(
                            esc_html( points_plus_translate('%d coins will be deducted from your account and a reload of Rs. %s will be credited to your phone number below within the next 2 – 3 working days.') ),
                            $reward_data['required_coins'],
                            $reward_data['reload_value']
                        ),
                        'confirmation_data' => [
                            'phone_number' => $phone_number,
                            'reload_value' => $reward_data['reload_value'],
                            'coins_cost' => $reward_data['required_coins'],
                            'current_coins' => $current_coins,
                            'remaining_coins' => $current_coins - $reward_data['required_coins']
                        ]
                    ];
                    wp_send_json_success($response);
                    wp_die();
                }
            }

            // 8. Grant reward (only reaches here if all checks passed and confirmed if needed)
            $reward_granted_data = grant_reward($student_post_id, $reward_data, $reward_id);
            if ($reward_granted_data['success']) {
                $student_email = get_field('email', $student_post_id);

                if (is_email($student_email)) {
                    $subject = 'Reward Redeemed Successfully';

                    $message = "Hello,\n\n";
                    $message .= "You have successfully redeemed your reward:\n\n";

                    if ($reward_data['promotion_type'] === 'reload') {
                        $message .= sprintf(
                            "Mobile Reload: Rs. %d to %s\n",
                            $reward_data['reload_value'],
                            get_field('student_mobile', $student_post_id)
                        );
                    } else {
                        $message .= "Reward: " . get_the_title($reward_id) . "\n";
                    }

                    $message .= sprintf(
                        "Coins deducted: %d\n\n",
                        $reward_data['required_coins']
                    );

                    $message .= "Thank you for using our platform!\n\n";
                    $message .= "Differently.study Team";

                    // Send plain text email
                    wp_mail(
                        $student_email,
                        $subject,
                        $message,
                        ['Content-Type: text/plain; charset=UTF-8']
                    );
                }
            }
//
//            if (!$reward_granted_data['success']) {
//                $response = add_system_message(
//                    $response,
//                    $reward_granted_data['message'] ?? esc_html( points_plus_translate('Failed to process reward. Please try again.') ),
//                    'error',
//                    'REWARD_PROCESSING_FAILED'
//                );
//                throw new Exception($reward_granted_data['message'] ?? 'Failed to process reward.');
//            }

            // 9. Success response
            $success_message =  esc_html( points_plus_translate('Reward claimed successfully!') );

            // Custom success messages based on reward type
            switch ($reward_data['promotion_type']) {
                case 'reload':
                    $success_message = sprintf(
                        esc_html( points_plus_translate('₹%d reload to %s has been processed! It may take 2-3 business days to complete.') ),
                        $reward_data['reload_value'],
                        $phone_number
                    );
                    break;

                case 'multiplication':
                    $success_message = sprintf(
                        esc_html( points_plus_translate('You earned %d points and %d coins from played quests!') ),
                        $reward_granted_data['points_added'] ?? 0,
                        $reward_granted_data['coins_added'] ?? 0
                    );
                    break;

                case 'addition':
                    $success_message = sprintf(
                        esc_html( points_plus_translate('You earned %d points and %d coins!') ),
                        $reward_data['points'] ?? 0,
                        $reward_data['coins'] ?? 0
                    );
                    error_log($success_message);
                    break;
            }

            $response = [
                'success' => true,
                'messages' => [
                    [
                        'text' => $success_message,
                        'type' => 'success',
                        'code' => 'REWARD_CLAIMED'
                    ]
                ],
                'data' => [
                    'points' => $reward_granted_data['points'] ?? 0,
                    'coins' => $reward_granted_data['coins'] ?? 0,
                    'unread_notifications' => $reward_granted_data['unread_count'] ?? 0,
                    'reload_value' => $reward_granted_data['reload_value'] ?? null
                ]
            ];

            wp_send_json_success($response);
            wp_die();

        } catch (Exception $e) {
            if (empty($response['messages'])) {
                $response = add_system_message(
                    $response,
                    $e->getMessage(),
                    'error',
                    'UNKNOWN_ERROR'
                );
            }
            wp_send_json_error($response);
            wp_die();

        }
    }
endif;

if (!function_exists('grant_reward')) :
    /**
     * Grants the reward to a student, handling different promotion types.
     *
     * @param int   $student_post_id The Post ID of the student CPT.
     * @param array $reward_data     Array of reward data.
     * @param int   $reward_id       The Post ID of the reward CPT.
     *
     * @return array An array containing success status and updated data.
     */
    function grant_reward($student_post_id, $reward_data, $reward_id) {
        error_log("[REWARD SYSTEM] === STARTING GRANT_REWARD PROCESS ===");
        error_log("[REWARD SYSTEM] Student ID: {$student_post_id}, Reward ID: {$reward_id}");
        error_log("[REWARD SYSTEM] Reward Data: " . print_r($reward_data, true));

        // Validate prerequisites
        if (!function_exists('get_field') || !function_exists('update_field')) {
            error_log("[REWARD SYSTEM] ERROR: ACF functions not available");
            return ['success' => false];
        }

        if (!$student_post_id) {
            error_log("[REWARD SYSTEM] ERROR: Invalid Student Post ID");
            return ['success' => false];
        }

        $promotion_type = $reward_data['promotion_type'] ?? 'addition';
        error_log("[REWARD SYSTEM] Promotion Type: {$promotion_type}");

        try {
            switch ($promotion_type) {
                case 'addition':
                    error_log("[REWARD SYSTEM] Routing to addition reward handler");
                    $result = grant_addition_reward($student_post_id, $reward_data, $reward_id);
                    break;

                case 'multiplication':
                    error_log("[REWARD SYSTEM] Routing to multiplication reward handler");
                    $result = grant_multiplication_reward($student_post_id, $reward_data, $reward_id);
                    break;

                case 'reload':
                    error_log("[REWARD SYSTEM] Routing to reload reward handler");
                    $result = grant_reload_reward($student_post_id, $reward_data, $reward_id);
                    break;

                default:
                    error_log("[REWARD SYSTEM] ERROR: Unknown promotion type: {$promotion_type}");
                    $result = ['success' => false, 'message' => 'Unknown promotion type.'];
            }

            error_log("[REWARD SYSTEM] Reward processing result: " . print_r($result, true));
            return $result;

        } catch (Exception $e) {
            error_log("[REWARD SYSTEM] EXCEPTION: " . $e->getMessage());
            error_log("[REWARD SYSTEM] Stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'An unexpected error occurred'];
        }
    }
endif;

if( ! function_exists('grant_addition_reward') ) :
    /**
     * Grants an addition-based reward.
     *
     * @param int   $student_post_id The Post ID of the student CPT.
     * @param array $reward_data     Array of reward data.
     * @param int   $reward_id       The Post ID of the reward CPT.
     *
     * @return array An array containing success status and updated data.
     */
    function grant_addition_reward($student_post_id, $reward_data, $reward_id) {
        error_log("[REWARD SYSTEM] === STARTING ADDITION REWARD PROCESS ===");
        error_log("[REWARD SYSTEM] Student ID: {$student_post_id}, Reward ID: {$reward_id}");

        // Get current balances
        $current_points = get_field('student_points', $student_post_id) ?: 0;
        $current_coins = get_field('student_coins', $student_post_id) ?: 0;
        error_log("[REWARD SYSTEM] Current Balance - Points: {$current_points}, Coins: {$current_coins}");

        // Calculate rewards
        $awarded_points = $reward_data['additional_reward'];
        $awarded_coins = ($reward_data['additional_type'] === 'both' || $reward_data['additional_type'] === 'coins')
            ? $reward_data['additional_reward']
            : 0;

        error_log("[REWARD SYSTEM] Awarding - Points: {$awarded_points}, Coins: {$awarded_coins}");
        error_log("[REWARD SYSTEM] Reward Type: " . $reward_data['additional_type']);

        $new_points = $current_points + $awarded_points;
        $new_coins = $current_coins + $awarded_coins;
        error_log("[REWARD SYSTEM] New Balance - Points: {$new_points}, Coins: {$new_coins}");

        // Update fields
        $points_updated = update_field('student_points', $new_points, $student_post_id);
        $coins_updated = update_field('student_coins', $new_coins, $student_post_id);

        if (!$points_updated || !$coins_updated) {
            error_log("[REWARD SYSTEM] ERROR: Failed to update point or coin fields");
            if (!$points_updated) error_log("[REWARD SYSTEM] Points update failed");
            if (!$coins_updated) error_log("[REWARD SYSTEM] Coins update failed");
            return ['success' => false];
        }
        error_log("[REWARD SYSTEM] Balance update successful");

        // Record reward claim
        $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
        $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

        if (!$update_result) {
            error_log("[REWARD SYSTEM] WARNING: Failed to update reward history, but reward was granted");
        } else {
            error_log("[REWARD SYSTEM] Reward history updated successfully");
        }

        // Prepare notification
        $reward_title = get_the_title($reward_id);
        $notification_message = '';

        if ($reward_data['additional_type'] === 'both') {
            $notification_message = sprintf(
                'සුභ පැතුම්! ඔබ %s reward ය සඳහා සුදුසුකම් හිමි කරගෙන ඇති බැවින්, ඔබේ stars සහ coins එක් එක් %d කින් වැඩි වේ. ඒ අනුව,<br/>
                නව coins ශේෂය: %d<br/>
                නව stars ශේෂය: %d',
                $reward_title,
                $awarded_points,
                $new_coins,
                $new_points
            );
        } elseif ($reward_data['additional_type'] === 'coins') {
            $notification_message = sprintf(
                'සුභ පැතුම්! ඔබ %s reward ය සඳහා සුදුසුකම් හිමි කරගෙන, ඔබේ coins %d කින් වැඩි වනු ඇත. ඒ අනුව,<br/>
                නව coins ශේෂය: %d<br/>
                නව stars ශේෂය: %d',
                $reward_title,
                $awarded_coins,
                $new_coins,
                $new_points
            );
        } elseif ($reward_data['additional_type'] === 'points ') {
            $notification_message = sprintf(
                'සුභ පැතුම්! ඔබ %s reward ය සඳහා සුදුසුකම් හිමි කරගෙන, ඔබේ stars %d කින් වැඩි වනු ඇත. ඒ අනුව,<br/>
                නව coins ශේෂය: %d<br/>
                නව stars ශේෂය: %d',
                $reward_title,
                $awarded_points,
                $new_coins,
                $new_points
            );
        } else {
            $notification_message = sprintf(
                esc_html( points_plus_translate('You earned %d points and %d coins!') ),
                $awarded_points,
                $awarded_coins
            );
        }

        error_log("[REWARD SYSTEM] Notification message prepared: " . substr($notification_message, 0, 100) . "...");

        // Add notification
        $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
        error_log("[REWARD SYSTEM] Notification added: " . ($notification_added ? 'true' : 'false'));

        // Get updated unread count
        $new_unread_count = get_student_unread_notification_count($student_post_id);
        error_log("[REWARD SYSTEM] New unread notification count: {$new_unread_count}");

        error_log("[REWARD SYSTEM] === ADDITION REWARD PROCESS COMPLETED SUCCESSFULLY ===");
        return [
            'success'     => true,
            'points'      => $new_points,
            'coins'       => $new_coins,
            'unread_count' => $new_unread_count,
        ];
    }
endif;

if ( ! function_exists('grant_multiplication_reward') ) :
    /**
     * Grants a multiplication-based reward to a student based on completed quests.
     *
     * @param int   $student_post_id The Post ID of the student CPT.
     * @param array $reward_data     Array of reward data.
     * @param int   $reward_id       The Post ID of the reward CPT.
     *
     * @return array Response containing success status and result details.
     */
    function grant_multiplication_reward($student_post_id, $reward_data, $reward_id) {
        error_log("[REWARD SYSTEM] === STARTING MULTIPLICATION REWARD PROCESS ===");
        error_log("[REWARD SYSTEM] Student ID: {$student_post_id}, Reward ID: {$reward_id}");

        // Validate parameters
        if (!$student_post_id || !$reward_id) {
            error_log("[REWARD SYSTEM] ERROR: Missing required parameters");
            return ['success' => false, 'message' => 'Missing required parameters'];
        }

        // Get reward configuration
        $multiplication_type = $reward_data['multiplication_type'] ?? 'both';
        $multifaction_factor = max(0, floatval($reward_data['multifaction_factor'] ?? 1));
        error_log("[REWARD SYSTEM] Multiplication Type: {$multiplication_type}, Factor: {$multifaction_factor}");

        // Validate reward period
        $valid_from = get_field('valid_from', $reward_id);
        $valid_until = get_field('valid_until', $reward_id);

        if (!$valid_from || !$valid_until) {
            error_log("[REWARD SYSTEM] ERROR: Reward period not defined");
            return ['success' => false, 'message' => 'Reward period is not defined.'];
        }

        $start_time = strtotime($valid_from);
        $end_time = strtotime($valid_until);
        error_log("[REWARD SYSTEM] Valid Period: {$valid_from} to {$valid_until}");

        // Get completed quests in period
        $completed_quests = get_student_quest_history_in_range(
            $student_post_id,
            'completed',
            $start_time,
            $end_time
        );
        error_log("[REWARD SYSTEM] Found " . count($completed_quests) . " completed quests in period");

        // Calculate total rewards from quests
        $total_quest_points = 0;
        $total_quest_coins = 0;

        foreach ($completed_quests as $index => $quest) {
            if (isset($quest['reward_type'], $quest['reward_value'])) {
                if ($quest['reward_type'] === 'points') {
                    $total_quest_points += (float)$quest['reward_value'];
                } elseif ($quest['reward_type'] === 'coins') {
                    $total_quest_coins += (float)$quest['reward_value'];
                }
            }
            error_log("[REWARD SYSTEM] Quest #{$index}: Type: {$quest['reward_type']}, Value: {$quest['reward_value']}");
        }
        error_log("[REWARD SYSTEM] Total Quest Points: {$total_quest_points}, Total Quest Coins: {$total_quest_coins}");

        // Get current balances
        $current_points = (int)get_field('student_points', $student_post_id);
        $current_coins = (int)get_field('student_coins', $student_post_id);
        error_log("[REWARD SYSTEM] Current Balance - Points: {$current_points}, Coins: {$current_coins}");

        // Calculate rewards
        $points_added = 0;
        $coins_added = 0;

        switch ($multiplication_type) {
            case 'points':
                $points_added = $total_quest_points * ($multifaction_factor-1);
                break;
            case 'coins':
                $coins_added = $total_quest_coins * ($multifaction_factor-1);
                break;
            case 'both':
                $points_added = $total_quest_points * ($multifaction_factor-1);
                $coins_added = $total_quest_coins * ($multifaction_factor-1);
                break;
        }
        error_log("[REWARD SYSTEM] Calculated Rewards - Points: +{$points_added}, Coins: +{$coins_added}");

        $new_points = $current_points + $points_added;
        $new_coins = $current_coins + $coins_added;
        error_log("[REWARD SYSTEM] New Balance - Points: {$new_points}, Coins: {$new_coins}");

        // Update balances
        $points_updated = update_field('student_points', $new_points, $student_post_id);
        $coins_updated = update_field('student_coins', $new_coins, $student_post_id);

        if (!$points_updated || !$coins_updated) {
            error_log("[REWARD SYSTEM] ERROR: Failed to update balances");
            if (!$points_updated) error_log("[REWARD SYSTEM] Points update failed");
            if (!$coins_updated) error_log("[REWARD SYSTEM] Coins update failed");
            return ['success' => false, 'message' => 'Failed to update balances'];
        }
        error_log("[REWARD SYSTEM] Balances updated successfully");

        // Record redemption
        $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
        $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

        if (!$update_result) {
            error_log("[REWARD SYSTEM] WARNING: Failed to update reward history, but reward was granted");
        } else {
            error_log("[REWARD SYSTEM] Reward history updated successfully");
        }

        // Add notification
        $reward_title = get_the_title($reward_id);
        $notification_message = sprintf(
            'සුභ පැතුම්! ඔබ %s reward ය සඳහා සුදුසුකම් හිමි කරගෙන ඇති බැවින්, ඔබේ stars සහ coins  %d ගුණකයකින් වැඩි වේ. ඒ අනුව,<br/>
            මුළු coins: %d<br/>
            මුළු stars: %d<br/>
            ඔබේ ගිණුමට එකතු වේ',
            $reward_title,
            $multifaction_factor,
            $points_added,
            $coins_added
        );
        error_log("[REWARD SYSTEM] Notification message prepared: " . substr($notification_message, 0, 100) . "...");

        $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
        error_log("[REWARD SYSTEM] Notification added: " . ($notification_added ? 'true' : 'false'));

        error_log("[REWARD SYSTEM] === MULTIPLICATION REWARD PROCESS COMPLETED SUCCESSFULLY ===");
        return [
            'success'      => true,
            'points'       => $new_points,
            'coins'        => $new_coins,
            'points_added' => $points_added,
            'coins_added'  => $coins_added,
            'message'      => sprintf(
                'Reward applied! +%d points, +%d coins from played quests.',
                $points_added,
                $coins_added
            )
        ];
    }
endif;

if ( ! function_exists('grant_reload_reward') ) :
    /**
     * Grants a reload-based reward.
     *
     * @param int   $student_post_id The Post ID of the student CPT.
     * @param array $reward_data     Array of reward data.
     * @param int   $reward_id       The Post ID of the reward CPT.
     *
     * @return array An array containing success status and updated data.
     */
    function grant_reload_reward($student_post_id, $reward_data, $reward_id) {
        error_log("[REWARD SYSTEM] === STARTING RELOAD REWARD PROCESS ===");
        error_log("[REWARD SYSTEM] Student ID: {$student_post_id}, Reward ID: {$reward_id}");

        // Check confirmation
        $is_confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';

        if (!$is_confirmed) {
            error_log("[REWARD SYSTEM] ERROR: Reload request not confirmed");
            return [
                'success' => false,
                'message' => 'Reload request not confirmed'
            ];
        }
        error_log("[REWARD SYSTEM] Reload request confirmed");

        // Check coin balance
        error_log("[REWARD SYSTEM] === STARTING ADDITION REWARD PROCESS ===");
        error_log("[REWARD SYSTEM] Student ID: {$student_post_id}, Reward ID: {$reward_id}");

        // Get current balances
        $current_coins = get_field('student_coins', $student_post_id) ?: 0;
        $required_coins = $reward_data['required_coins'];
        error_log("[REWARD SYSTEM] Current Coins: {$current_coins}, Required: {$required_coins}");

        if ($current_coins < $required_coins) {
            error_log("[REWARD SYSTEM] ERROR: Insufficient coins balance");
            return [
                'success' => false,
                'message' => 'Insufficient coins balance',
            ];
        }

        // Process payment
        $new_coins = $current_coins - $required_coins;
        $coins_updated = update_field('student_coins', $new_coins, $student_post_id);

        if (!$coins_updated) {
            error_log("[REWARD SYSTEM] ERROR: Failed to update coin balance");
            return [
                'success' => false,
                'message' => 'Failed to process payment',
            ];
        }
        error_log("[REWARD SYSTEM] Payment processed successfully. New coin balance: {$new_coins}");

        // Record transaction
        $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
        $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

        if (!$update_result) {
            error_log("[REWARD SYSTEM] WARNING: Failed to update reward history, but reload was processed");
        } else {
            error_log("[REWARD SYSTEM] Reward history updated successfully");
        }

        // Add notification
        $notification_message = sprintf(
            esc_html( points_plus_translate('Your redeem reward request for ₹%d worth of reload is submitted. It will be processed within 2-3 working days.') ),
            $reward_data['reload_value']
        );
        error_log("[REWARD SYSTEM] Notification message prepared: " . substr($notification_message, 0, 100) . "...");

        $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
        error_log("[REWARD SYSTEM] Notification added: " . ($notification_added ? 'true' : 'false'));

        // Get updated unread count
        $new_unread_count = get_student_unread_notification_count($student_post_id);
        error_log("[REWARD SYSTEM] New unread notification count: {$new_unread_count}");

        error_log("[REWARD SYSTEM] === RELOAD REWARD PROCESS COMPLETED SUCCESSFULLY ===");
        return [
            'success'     => true,
            'message'     => __('Reload processed successfully!', 'your-theme-text-domain'),
            'coins'       => $new_coins,
            'reload_value' => $reward_data['reload_value'],
            'unread_count' => $new_unread_count,
        ];
    }
endif;

if ( ! function_exists('get_student_quest_history_in_range') ) :
    /**
     * Gets the student's quest history within a specified time range.
     *
     * @param int    $student_id      The ID of the student.
     * @param string $quest_count_type 'attempted' or 'completed'.
     * @param int    $start_time      Start timestamp.
     * @param int    $end_time        End timestamp.
     *
     * @return array An array of quest history data with student_quest_id included.
     */
    function get_student_quest_history_in_range($student_id, $quest_count_type, $start_time, $end_time) {
        error_log("get_student_quest_history_in_range: Searching for $quest_count_type quests for student $student_id between " .
            date('Y-m-d H:i:s', $start_time) . " and " . date('Y-m-d H:i:s', $end_time));

        $args = [
            'post_type'      => 'reward_history',
            'posts_per_page' => -1,
            'date_query'    => [
                [
                    'after'     => date('Y-m-d H:i:s', $start_time),
                    'before'    => date('Y-m-d H:i:s', $end_time),
                    'inclusive' => true
                ]
            ],
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
            ]
        ];

        $reward_posts = get_posts($args);
        $quest_history = [];

        foreach ($reward_posts as $post) {
            $post_date = strtotime($post->post_date);

            // Only include if within our time range (double-checking)
            if ($post_date >= $start_time && $post_date <= $end_time) {
                $quest_history[] = [
                    'timestamp'         => $post->post_date,
                    'quest_count_type' => 'completed', // All reward_history entries are completed
                    'student_quest_id' => $post->ID,  // Using reward_history post ID instead
                    'quest_id'         => get_field('quest', $post->ID), // Get the associated quest ID
                    'reward_type'      => get_field('type', $post->ID),  // Additional reward info
                    'reward_value'      => get_field('value', $post->ID)   // Additional reward info
                ];
            }
        }

        error_log("get_student_quest_history_in_range: Found " . count($quest_history) . " matching quest completions");
        return $quest_history;
    }
endif;

if (!function_exists('add_notification_to_student_cpt')) :
    /**
     * Adds a notification entry to a student CPT's ACF repeater field.
     *
     * @param int $student_post_id The Post ID of the student CPT.
     * @param string $message The notification message content.
     * @return bool True on success, false on failure.
     */
    function add_notification_to_student_cpt($student_post_id, $message) {
        error_log("add_notification_to_student_cpt: Adding notification for Student ID: " . $student_post_id . ", Message: " . $message);
        // Ensure ACF functions exist to prevent errors if ACF is inactive
        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
            error_log("add_notification_to_student_cpt: ACF functions not found or Student Post ID is invalid.");
            return false;
        }

        // Field key for the repeater (must match your ACF setup)
        $repeater_field_key = 'student_notifications';

        // Get existing notifications or initialize an empty array
        $notifications = get_field($repeater_field_key, $student_post_id) ?: [];
        if (!is_array($notifications)) {
            $notifications = [];
        }
//        error_log("add_notification_to_student_cpt: Existing notifications: " . print_r($notifications, true));

        // Add the new notification as an array matching sub-field keys
        $notifications[] = [
            'message' => $message,
            'is_read' => false, // Or 0, depending on ACF True/False return format
            'timestamp' => current_time('mysql'),
            // 'link' => '', // Optional: Add a link if needed
        ];
//        error_log("add_notification_to_student_cpt: New notifications array: " . print_r($notifications, true));

        // Update the repeater field for the specific student post
        $success = update_field($repeater_field_key, $notifications, $student_post_id);
        error_log("add_notification_to_student_cpt: Update successful: " . ($success ? 'true' : 'false'));

        return $success;
    }
endif;

if (! function_exists('manage_reward_claims')) :
    /**
     * Count and find the most recent claim by querying the students_redeems CPT.
     *
     * @param int $student_post_id   Student CPT ID.
     * @param int $reward_item_id    Reward CPT ID.
     * @param int $redemption_limit  Max allowed (0 = unlimited).
     * @return array {
     *   @type int    'claim_count'             Number of matching claim posts.
     *   @type string|null 'most_recent_timestamp'  MySQL datetime or null.
     *   @type bool   'can_redeem'              True if under limit.
     * }
     */
    function manage_reward_claims( $student_post_id, $reward_item_id, $redemption_limit ) {
        // 1) Query all matching claim posts
        $claims = get_posts([
            'post_type'      => 'students_redeems',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'student',
                    'value'   => $student_post_id,
                    'compare' => '='
                ],
                [
                    'key'     => 'reward_item',
                    'value'   => $reward_item_id,
                    'compare' => '='
                ],
            ],
        ]);

        $count = count( $claims );
        $most_recent = null;

        // 2) Find the latest timestamp
        foreach ( $claims as $post ) {
            // ACF field
            if ( function_exists('get_field') ) {
                $ts = get_field( 'claimed_timestamp', $post->ID );
            } else {
                $ts = get_post_meta( $post->ID, 'claimed_timestamp', true );
            }
            if ( $ts && ( $most_recent === null || strtotime($ts) > strtotime($most_recent) ) ) {
                $most_recent = $ts;
            }
        }

        // 3) Redemption check
        $can_redeem = ( $redemption_limit === 0 || $count < $redemption_limit );

        error_log( "manage_reward_claims: Student {$student_post_id}, Reward {$reward_item_id} → count={$count}, most_recent={$most_recent}, can_redeem=" . ($can_redeem?'true':'false') );

        return [
            'claim_count'            => $count,
            'most_recent_timestamp'  => $most_recent,
            'can_redeem'             => $can_redeem,
        ];
    }
endif;
if ( ! function_exists( 'update_reward_claims' ) ) :
    /**
     * Create a new Students Redeems CPT entry for each claim.
     *
     * @param int    $student_id     Student CPT ID.
     * @param int    $reward_item_id Reward CPT ID.
     * @param string $timestamp      MySQL datetime, e.g. '2025-04-17 15:30:59'.
     * @return bool True on success, false on failure.
     */
    function update_reward_claims( $student_id, $reward_item_id, $timestamp ) {
        // 1) Insert the CPT post
        $post_id = wp_insert_post( [
            'post_type'   => 'students_redeems',    // must match your register_post_type slug
            'post_title'  => sprintf(
                'Claim: Student %d → Reward %d @ %s',
                $student_id,
                $reward_item_id,
                $timestamp
            ),
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            error_log( 'update_reward_claims: wp_insert_post failed: '
                . ( is_wp_error( $post_id )
                    ? $post_id->get_error_message()
                    : 'unknown error'
                )
            );
            return false;
        }

        // 2) Determine default status
        $promotion_type = get_field( 'promotion_type', $reward_item_id );
        $status         = ( $promotion_type === 'reload' ) ? 'pending' : 'completed';

        // 3) Save all four ACF fields so your admin table can read them
        if ( function_exists( 'update_field' ) ) {
            update_field( 'student',           $student_id,     $post_id );
            update_field( 'reward_item',       $reward_item_id, $post_id );
            update_field( 'claimed_timestamp', $timestamp,      $post_id );
            update_field( 'status',            $status,         $post_id );
        } else {
            // fallback if ACF isn’t loaded yet
            update_post_meta( $post_id, 'student',           $student_id );
            update_post_meta( $post_id, 'reward_item',       $reward_item_id );
            update_post_meta( $post_id, 'claimed_timestamp', $timestamp );
            update_post_meta( $post_id, 'status',            $status );
        }

        error_log( "update_reward_claims: Created claim post #{$post_id} for student {$student_id}" );
        return true;
    }
endif;



if (!function_exists('confirm_reload_reward')) :
    /**
     * Handles the reload reward confirmation process
     */
    function confirm_reload_reward($student_post_id, $reward_data) {
        // Get student's phone number
        $phone_number = get_field('student_number', $student_post_id);

        if (empty($phone_number)) {
            return [
                'success' => false,
                'message' => 'No mobile number found in your profile. Please update your profile.'
            ];
        }

        // Verify sufficient coin balance
        $current_coins = get_field('student_coins', $student_post_id) ?: 0;
        if ($current_coins < $reward_data['required_coins']) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough coins to redeem this reward.'
            ];
        }

        return [
            'success' => true,
            'needs_confirmation' => true,
            'confirmation_data' => [
                'phone_number' => $phone_number,
                'reload_value' => $reward_data['reload_value'],
                'coins_cost' => $reward_data['required_coins'],
                'current_coins' => $current_coins,
                'remaining_coins' => $current_coins - $reward_data['required_coins']
            ],
            'message' => sprintf(
                'We will send ₹%d reload to %s. Confirm to proceed?',
                $reward_data['reload_value'],
                $phone_number
            )
        ];
    }
endif;
