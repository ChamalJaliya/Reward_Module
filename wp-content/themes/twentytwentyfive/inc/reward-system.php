<?php
/**
 * Reward system functionality
 */

// Helper function to get the reward points, coins, cooldown, and other details from a specific "Reward Item" post.
if (!function_exists('get_reward_data')) :
    /**
     * @param int $reward_id The ID of the Reward Item post.
     * @return array An array containing 'success' (bool), and all relevant reward details based on promotion type.
     */
    function get_reward_data($reward_id) {
        error_log("get_reward_data: Function initiated for Reward ID: " . $reward_id);
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

        $reward_data['redemption_limit'] = intval(get_field('redemption_limit', $reward_id) ?: 0); // Always fetch this

        error_log("get_reward_data: All Fields - " . print_r($reward_data, true));

        // 3. Validity Checks
        $now = current_time('timestamp');

        if ($reward_data['valid_from'] && strtotime($reward_data['valid_from']) > $now) {
            error_log("get_reward_data: Reward is not yet available. Valid From: " . $reward_data['valid_from'] . " (timestamp: " . strtotime($reward_data['valid_from']) . "), Current Time: " . $now);
            $reward_data['success'] = false;
            $reward_data['message'] = 'Reward is not yet available.';
            return $reward_data;
        }

        if ($reward_data['valid_until'] && strtotime($reward_data['valid_until']) < $now) {
            error_log("get_reward_data: Reward has expired. Valid Until: " . $reward_data['valid_until'] . " (timestamp: " . strtotime($reward_data['valid_until']) . "), Current Time: " . $now);
            $reward_data['success'] = false;
            $reward_data['message'] = 'Reward has expired.';
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

        // 4. Check eligibility rules if they exist
        if (!empty($eligibility_rules)) {
            error_log("Checking eligibility rules...");
            $passed_all_rules = true;

            foreach ($eligibility_rules as $rule_id) {
                $rule_passed = evaluate_eligibility_rule($student_post_id, $rule_id);

                if (!$rule_passed) {
                    error_log("FAIL: Student failed rule ID {$rule_id}");
                    $passed_all_rules = false;
                    break;
                }

                error_log("PASS: Student passed rule ID {$rule_id}");
            }

            if (!$passed_all_rules) {
                error_log("FAIL: Student failed one or more eligibility rules");
                return false;
            }
        }

        // 5. Check cooldown and redemption limits
        if ($cooldown_period > 0 || $redemption_limit > 0) {
            error_log("Checking cooldown and redemption limits...");
            $claim_data = manage_reward_claims($student_post_id, $reward_id, $redemption_limit);
            $most_recent_timestamp = $claim_data['most_recent_timestamp'];
            $claim_count = $claim_data['claim_count'];

            error_log("Last Claimed: " . ($most_recent_timestamp ?: 'Never'));
            error_log("Total Claims: {$claim_count}" . ($redemption_limit > 0 ? "/{$redemption_limit}" : ''));

            // Check redemption limit first
            if ($redemption_limit > 0 && $claim_count >= $redemption_limit) {
                error_log("FAIL: Redemption limit reached");
                return false;
            }

            // Then check cooldown period if they've claimed before
            if ($most_recent_timestamp) {
                $now = current_time('timestamp');
                $last_claimed_time = strtotime($most_recent_timestamp);
                $time_since_last_claim = $now - $last_claimed_time;
                $cooldown_remaining = $cooldown_period - $time_since_last_claim;

                error_log("Current Time: " . date('Y-m-d H:i:s', $now));
                error_log("Last Claim Time: " . date('Y-m-d H:i:s', $last_claimed_time));
                error_log("Time Since Last Claim: {$time_since_last_claim}s");
                error_log("Cooldown Remaining: {$cooldown_remaining}s");

                if ($cooldown_remaining > 0) {
                    $readable_time = seconds_to_readable($cooldown_remaining);
                    error_log("FAIL: Cooldown not expired - {$readable_time} remaining");
                    return false;
                }
            }
        }

        error_log("PASS: Student is eligible for reward");
        return true;
    }
endif;

if (!function_exists('evaluate_eligibility_rule')) :
    /**
     * Evaluates a single eligibility rule against a student
     *
     * @param int $student_post_id
     * @param int $rule_id
     * @return bool
     */
    function evaluate_eligibility_rule($student_post_id, $rule_id) {
        error_log("=== STARTING RULE EVALUATION ===");
        error_log("Evaluating rule ID: {$rule_id} for student {$student_post_id}");

        // Get rule data
        $rule_data = get_post($rule_id);
        error_log("Rule Post Data: " . print_r($rule_data, true)); // Log the entire post object
        if (!$rule_data) {
            error_log("ERROR: Rule with ID {$rule_id} not found.");
            return false;
        }
        // Get rule status first
        $rule_status = get_field('status', $rule_id);
        if ($rule_status !== 'active') {
            error_log("FAIL: Rule {$rule_id} is not active (status: {$rule_status})");
            return false;
        }

        $conditions = get_field('conditions', $rule_id);
        if (empty($conditions)) {
            error_log("PASS: Rule {$rule_id} has no conditions - automatically passes");
            return true;
        }

        // Track results for all groups
        $group_results = [];
        $has_or_groups = false;
        $has_passing_or_group = false;

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

            $condition_items = isset($condition_group['condition_items']) ?
                $condition_group['condition_items'] : [];

            $group_result = ($group_logic === 'OR') ? false : true;

            foreach ($condition_items as $item_index => $condition) {
                if (!is_array($condition) || !isset($condition['field']) || !isset($condition['operator']) || !isset($condition['value'])) {
                    error_log("Invalid condition structure in group {$group_index}, item {$item_index}");
                    $group_result = ($group_logic === 'OR') ? $group_result : false;
                    continue;
                }

                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $time_scope = isset($condition['time_scope']) ? $condition['time_scope'] : 'lifetime';
                $time_params = isset($condition['time_parameters']) ? $condition['time_parameters'] : [];

                error_log("Condition {$item_index}: {$field} {$operator} {$value} (Time scope: {$time_scope})");

                // Get student data based on time scope
                $student_value = get_student_data_with_time_scope(
                    $student_post_id,
                    $field,
                    $time_scope,
                    $time_params
                );

                error_log("Student value: {$student_value}");

                $comparison_result = compare_values($student_value, $operator, $value);
                $result_text = $comparison_result ? 'PASS' : 'FAIL';
                error_log("Comparison result: {$result_text}");

                // Apply group logic
                if ($group_logic === 'AND') {
                    $group_result = $group_result && $comparison_result;
                    if (!$group_result) {
                        error_log("AND group condition failed - short-circuiting this group");
                        break; // No need to check other conditions in this AND group
                    }
                } else { // OR logic
                    $group_result = $group_result || $comparison_result;
                    if ($group_result) {
                        error_log("OR group condition passed - short-circuiting this group");
                        break; // No need to check other conditions in this OR group
                    }
                }
            }

            $group_result_text = $group_result ? 'PASS' : 'FAIL';
            error_log("Group {$group_index} result: {$group_result_text}");

            $group_results[] = [
                'logic' => $group_logic,
                'result' => $group_result
            ];

            // Track if we have any passing OR groups
            if ($group_logic === 'OR' && $group_result) {
                $has_passing_or_group = true;
            }
        }

        // Determine final rule result
        if ($has_or_groups) {
            // For rules with OR groups, we need at least one OR group to pass
            $final_result = $has_passing_or_group;
            error_log($has_passing_or_group ?
                "PASS: At least one OR group passed" :
                "FAIL: No OR groups passed");
        } else {
            // For rules with only AND groups, all must pass
            $all_and_groups_passed = true;
            foreach ($group_results as $group) {
                if (!$group['result']) {
                    $all_and_groups_passed = false;
                    break;
                }
            }

            $final_result = $all_and_groups_passed;
            error_log($all_and_groups_passed ?
                "PASS: All AND groups passed" :
                "FAIL: At least one AND group failed");
        }

        error_log("Final rule evaluation: " . ($final_result ? 'PASS' : 'FAIL'));
        error_log("=== END RULE EVALUATION ===");
        return $final_result;
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
     * @return int The calculated or retrieved value.
     */
    function get_student_data_with_time_scope($student_id, $field, $time_scope = 'lifetime', $time_params = []) {
        error_log("Getting {$field} for student {$student_id} with scope {$time_scope}");

        // Handle balance fields (not time-scoped)
        if (in_array($field, ['points_balance', 'coins_balance'])) {
            switch ($field) {
                case 'points_balance':
                    return (int)(get_field('points', $student_id) ?: 0);
                case 'coins_balance':
                    return (int)(get_field('coins', $student_id) ?: 0);
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
                        // Implement your session logic here
                        // Example: if (is_within_current_session($entry_timestamp)) $filtered_history[] = $entry;
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

//if (!function_exists('get_student_data_with_time_scope')) :
//    /**
//     * Gets student data with time scope consideration.
//     *
//     * @param int    $student_id The ID of the student.
//     * @param string $field      Field name: 'quests_attempted', 'quests_completed', 'points_balance', 'coins_balance'.
//     * @param string $time_scope Time scope: 'all', 'daily', 'weekly', 'monthly', 'custom'.
//     * @param array  $time_params Optional. Used for custom ranges. ['from' => 'Y-m-d', 'to' => 'Y-m-d']
//     *
//     * @return int The calculated or retrieved value.
//     */
//    function get_student_data_with_time_scope($student_id, $field, $time_scope = 'all', $time_params = []) {
//        error_log("Getting {$field} for student {$student_id} with scope {$time_scope}");
//
//        // Handle quests
//        if (in_array($field, ['quests_attempted', 'quests_completed'])) {
//            $quest_type = str_replace('quests_', '', $field);
//            $history = get_student_quest_history($student_id, $quest_type); // already returns timestamped data
//
//            if ($time_scope === 'all') {
//                return count($history);
//            }
//
//            $filtered = array_filter($history, function ($entry) use ($time_scope, $time_params) {
//                $timestamp = strtotime($entry['timestamp']);
//
//                switch ($time_scope) {
//                    case 'daily':
//                        return date('Y-m-d') === date('Y-m-d', $timestamp);
//                    case 'weekly':
//                        return date('oW') === date('oW', $timestamp); // ISO week number comparison
//                    case 'monthly':
//                        return date('Y-m') === date('Y-m', $timestamp);
//                    case 'custom':
//                        if (!empty($time_params['from']) && !empty($time_params['to'])) {
//                            $from = strtotime($time_params['from']);
//                            $to = strtotime($time_params['to'] . ' 23:59:59');
//                            return $timestamp >= $from && $timestamp <= $to;
//                        }
//                        return false;
//                    default:
//                        return true;
//                }
//            });
//
//            return count($filtered);
//        }
//
//        // Handle balance fields
//        switch ($field) {
//            case 'points_balance':
//                return (int)(get_field('points', $student_id) ?: 0);
//            case 'coins_balance':
//                return (int)(get_field('coins', $student_id) ?: 0);
//            default:
//                error_log("ERROR: Unknown field: {$field}");
//                return 0;
//        }
//    }
//endif;


if (!function_exists('get_student_quest_history')) :
    /**
     * Gets the student's quest history.
     *
     * @param int    $student_id      The ID of the student.
     * @param string $quest_count_type 'attempted' or 'completed'.
     * @return array An array of quest history data.
     */
    function get_student_quest_history($student_id, $quest_count_type) {
        $args = [
            'post_type' => 'student_quests',
            'numberposts' => -1, // Get all student_quests posts
            'meta_query' => [
                [
                    'key' => 'student', // ACF field name for student relationship
                    'value' => $student_id,
                    'compare' => 'LIKE', // ACF relationship fields
                ],
            ],
        ];

        $student_quests = get_posts($args); // Get the student_quests posts
        $quest_history = [];

        if ($student_quests) {
            foreach ($student_quests as $student_quest) {
                $progress_data = get_field('quest_progress', $student_quest->ID); // Get the repeater field

                if (is_array($progress_data) && !empty($progress_data)) {
                    foreach ($progress_data as $progress) {
                        if (isset($progress['status']) && $progress['status'] == $quest_count_type && isset($progress['status_date'])) {
                            $quest_history[] = [
                                'timestamp' => $progress['status_date'],
                                'quest_count_type' => $quest_count_type,
                            ];
                        }
                    }
                }
            }
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
            // Add more operators if needed (IN, CONTAINS, etc.)
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
        if ($days > 0) $parts[] = "{$days} day" . ($days > 1 ? 's' : '');
        if ($hours > 0) $parts[] = "{$hours} hour" . ($hours > 1 ? 's' : '');
        if ($minutes > 0) $parts[] = "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        if ($seconds > 0 && empty($parts)) {
            $parts[] = "{$seconds} second" . ($seconds > 1 ? 's' : '');
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
            'message' => '',
            'data' => [],
            'needs_confirmation' => false
        ];

        try {
            // 1. Security check (uncomment when ready)
            /*
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'redeem_reward_nonce')) {
                throw new Exception('Security check failed!');
            }
            */

            // 2. Validate student identifier
            $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : '';
            if (empty($student_identifier)) {
                throw new Exception('Student email address is required.');
            }

            // 3. Find student post ID
            $student_post_id = get_student_post_id_by_email($student_identifier);
            if (!$student_post_id) {
                throw new Exception('Could not find student profile.');
            }

            // 4. Validate reward ID
            $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 0;
            if (!$reward_id) {
                throw new Exception('Invalid reward ID.');
            }

            // 5. Get reward data
            $reward_data = get_reward_data($reward_id);
            if (!$reward_data['success']) {
                throw new Exception($reward_data['message'] ?? 'This reward is not available.');
            }

            // 6. Check eligibility
            $is_eligible = is_student_eligible_for_reward(
                $student_post_id,
                $reward_data['cooldown_period'],
                $reward_id,
                $reward_data['redemption_limit']
            );

            if (!$is_eligible) {
                $response['message'] = 'Not eligible to claim this reward at this time.';
                wp_send_json_error($response);
                wp_die();
            }

            // 7. Special handling for reload rewards requiring confirmation
            if ($reward_data['promotion_type'] === 'reload') {
                error_log("handle_redeem_reward_ajax: Processing reload reward.");
                $phone_number = get_field('mobile_number', $student_post_id);
                error_log("handle_redeem_reward_ajax: Phone Number: " . print_r($phone_number, true));
                $current_coins = get_field('coins', $student_post_id) ?: 0;
                error_log("handle_redeem_reward_ajax: Current Coins: " . print_r($current_coins, true));

                if (empty($phone_number)) {
                    error_log("handle_redeem_reward_ajax: Phone number is empty!");
                    throw new Exception('No mobile number found in your profile.');
                }

                if ($current_coins < $reward_data['required_coins']) {
                    error_log("handle_redeem_reward_ajax: Insufficient coins!");
                    throw new Exception('You don\'t have enough coins for this reward.');
                }

                error_log("handle_redeem_reward_ajax: Confirmation checks passed.");

                $is_confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';
                error_log("handle_redeem_reward_ajax: Confirmation status: " . ($is_confirmed ? 'confirmed' : 'needs confirmation'));


                // If not yet confirmed, return confirmation request
//                if (!$is_confirmed) {
//                    $response = [
//                        'success' => true,
//                        'needs_confirmation' => true,
//                        'message' => sprintf(
//                            'We will send ₹%d reload to %s. Confirm to proceed?',
//                            $reward_data['reload_value'],
//                            $phone_number
//                        ),
//                        'confirmation_data' => [
//                            'phone_number' => $phone_number,
//                            'reload_value' => $reward_data['reload_value'],
//                            'coins_cost' => $reward_data['required_coins'],
//                            'current_coins' => $current_coins,
//                            'remaining_coins' => $current_coins - $reward_data['required_coins']
//                        ]
//                    ];
//                    wp_send_json_success($response);
//                    wp_die(); // THIS WAS MISSING AND CRUCIAL
//                }
            }

            // 8. Grant reward (only reaches here if all checks passed and confirmed if needed)
            $reward_granted_data = grant_reward($student_post_id, $reward_data, $reward_id);

            if (!$reward_granted_data['success']) {
                throw new Exception($reward_granted_data['message'] ?? 'Failed to process reward.');
            }

            // 9. Success response
            $response = [
                'success' => true,
                'message' => $reward_granted_data['message'] ?? 'Reward claimed successfully!',
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
            $response['message'] = $e->getMessage();
            wp_send_json_error($response);
            wp_die();
        }
    }
endif;

//if (!function_exists('grant_reward')) :
//    /**
//     * Grants the daily reward to a student.
//     *
//     * @param int $student_post_id   The Post ID of the student CPT.
//     * @param int $reward_data     The number of points to award.
//     * @param int $reward_id      The Post ID of the reward CPT
//     * @return array An array containing success status and updated data.
//     */
//    function grant_reward($student_post_id, $reward_data ,$reward_id  ) {
//        error_log("grant_reward: Function initiated for Student ID: " . $student_post_id . ", Reward Data: " . $reward_data . ", Reward Post ID: " . $reward_id);
//
//        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
//            error_log("daily_reward: ACF functions not found or Student Post ID is invalid.");
//            return ['success' => false];
//        }
//        $promotion_type = $reward_data['promotion_type'] ?? 'addition';
//
//        switch ($promotion_type) {
//            case 'addition':
//                error_log("grant_reward: Applying Addition-Based Reward.");
//
//                $current_points = get_field('points', $student_post_id) ?: 0;
//                $current_coins = get_field('coins', $student_post_id) ?: 0;
//
//                $new_points = $current_points + $reward_data['points'];
//                $new_coins = $current_coins + $reward_data['coins'];
//
//                error_log("grant_reward: New Points: " . $new_points . ", New Coins: " . $new_coins);
//
//                $points_updated = update_field('points', $new_points, $student_post_id);
//                $coins_updated = update_field('coins', $new_coins, $student_post_id);
//                error_log("grant_reward: Points Updated: " . ($points_updated ? 'true' : 'false') . ", Coins Updated: " . ($coins_updated ? 'true' : 'false'));
//
//                if (!$points_updated || !$coins_updated) {
//                    error_log("grant_reward: Failed to update point or coin fields.");
//                    return ['success' => false];
//                }
//
//                // Add the last claim to the 'claimed_history' CPT
//                $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
//                $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);
//                if (!$update_result) {
//                    error_log("grant_reward: Failed to update user_reward_history.");
//                    return ['success' => false, 'message' => 'Failed to update reward history.'];
//                }
//
//
//                // Add notification
//                $notification_message = sprintf(
//                    __('reward claimed: +%d Points, +%d Coins', 'your-theme-text-domain'),
//                    $reward_data['points'],
//                    $reward_data['coins']
//                );
//                $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
//                error_log("grant_additional_reward: Notification added: " . ($notification_added ? 'true' : 'false'));
//
//                // Get updated unread notification count
//                $new_unread_count = get_student_unread_notification_count($student_post_id); // Helper function (see below)
//                error_log("grant_reward: New Unread Notification Count: " . $new_unread_count);
//
//                return [
//                    'success' => true,
//                    'points' => $new_points,
//                    'coins' => $new_coins,
//                    'unread_count' => $new_unread_count
//                ];
//
//            case 'multiplication':
//                error_log("grant_reward: Applying Multiplication-Based Reward.");
//                $multiplication_type = $reward_data['multiplication_type'] ?? 'both';
//                $multifaction_factor = $reward_data['multifaction_factor'] ?? 1;
//
//                switch ($multiplication_type) {
//                    case 'coins':
////                        $new_coins *= $multifaction_factor;
//                        break;
//                    case 'points':
//                        $current_stars = get_field('stars', $student_post_id) ?: 0;
//                        $new_stars = $current_stars * $multifaction_factor;
//                        $stars_updated = update_field('stars', $new_stars, $student_post_id);
//                        error_log("grant_reward: Stars Updated: " . ($stars_updated ? 'true' : 'false'));
//                        break;
//                    case 'both':
////                        $new_points *= $multifaction_factor;
////                        $new_coins *= $multifaction_factor;
//                        break;
//                }
//                break;
//            case 'reload':
//                error_log("grant_reward: Applying Reload-Based Reward.");
//
//                // Check if this is a confirmed request
////                $is_confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';
////
////                if (!$is_confirmed) {
////                    return [
////                        'success' => false,
////                        'message' => 'Reload request not confirmed'
////                    ];
////                }
//
//                $current_coins = get_field('coins', $student_post_id) ?: 0;
//
//                if ($current_coins < $reward_data['required_coins']) {
//                    error_log("grant_reward: You don't have enough coins to redeem this reward.");
//                    return [
//                        'success' => false,
//                        'message' => 'Insufficient coins balance'
//                    ];
//                }
//
//                // Deduct coins
//                $new_coins = $current_coins - $reward_data['required_coins'];
//                $coins_updated = update_field('coins', $new_coins, $student_post_id);
//
//                if (!$coins_updated) {
//                    error_log("grant_reward: Failed to update coin fields.");
//                    return [
//                        'success' => false,
//                        'message' => 'Failed to process payment'
//                    ];
//                }
//
//                // Process the reload (this would call your actual reload API)
////                $reload_processed = process_mobile_reload(
////                    get_field('mobile_number', $student_post_id),
////                    $reward_data['reload_value']
////                );
////
////                if (!$reload_processed) {
////                    // Refund coins if reload failed
////                    update_field('coins', $current_coins, $student_post_id);
////                    return [
////                        'success' => false,
////                        'message' => 'Reload processing failed. Coins have been refunded.'
////                    ];
////                }
//
//                // Record the transaction
//                $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
//                $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);
//
//                if (!$update_result) {
//                    error_log("grant_reward: Failed to update reward history.");
//                    // Still return success since reload was processed
//                }
//
//                // Add notification
//                $notification_message = sprintf(
//                    __('Your redeem reward request for ₹%d worth of reload is submitted. It will be processed within 2-3 working days.', 'your-theme-text-domain'),
//                    $reward_data['reload_value']
//                );
//                add_notification_to_student_cpt($student_post_id, $notification_message);
//
//                return [
//                    'success' => true,
//                    'message' => __('Reload processed successfully!', 'your-theme-text-domain'),
//                    'coins' => $new_coins,
//                    'reload_value' => $reward_data['reload_value'],
//                    'unread_count' => get_student_unread_notification_count($student_post_id)
//                ];
//            default:
//                error_log("grant_reward: Unknown promotion type: " . $promotion_type);
//                return ['success' => false, 'message' => 'Unknown promotion type.'];
//        }
//
//
//    }
//endif;

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
        error_log("grant_reward: Function initiated for Student ID: " . $student_post_id .
            ", Reward Data: " . print_r($reward_data, true) .
            ", Reward Post ID: " . $reward_id);

        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
            error_log("grant_reward: ACF functions not found or Student Post ID is invalid.");
            return ['success' => false];
        }

        $promotion_type = $reward_data['promotion_type'] ?? 'addition';

        switch ($promotion_type) {
            case 'addition':
                return grant_addition_reward($student_post_id, $reward_data, $reward_id);

            case 'multiplication':
                return grant_multiplication_reward($student_post_id, $reward_data, $reward_id, $reward_id);

            case 'reload':
                return grant_reload_reward($student_post_id, $reward_data, $reward_id);

            default:
                error_log("grant_reward: Unknown promotion type: " . $promotion_type);
                return ['success' => false, 'message' => 'Unknown promotion type.'];
        }
    }

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
        error_log("grant_addition_reward: Applying Addition-Based Reward.");

        $current_points = get_field('points', $student_post_id) ?: 0;
        $current_coins = get_field('coins', $student_post_id) ?: 0;

        $new_points = $current_points + $reward_data['additional_reward'];
        $new_coins = $current_coins + ($reward_data['additional_type'] === 'both'
                ? $reward_data['additional_reward']
                : 0);

        error_log("grant_addition_reward: New Points: " . $new_points . ", New Coins: " . $new_coins);

        $points_updated = update_field('points', $new_points, $student_post_id);
        $coins_updated = update_field('coins', $new_coins, $student_post_id);

        error_log("grant_addition_reward: Points Updated: " . ($points_updated ? 'true' : 'false') .
            ", Coins Updated: " . ($coins_updated ? 'true' : 'false'));

        if (!$points_updated || !$coins_updated) {
            error_log("grant_addition_reward: Failed to update point or coin fields.");
            return ['success' => false];
        }

        // Add the last claim to the 'claimed_history' CPT
        $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
        $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);
        if (!$update_result) {
            error_log("grant_addition_reward: Failed to update user_reward_history.");
            return ['success' => false, 'message' => 'Failed to update reward history.'];
        }

        // Add notification
        $notification_message = sprintf(
            __('Reward claimed: +%d Points, +%d Coins', 'your-theme-text-domain'),
            $reward_data['additional_reward'],
            ($reward_data['additional_type'] === 'both' ? $reward_data['additional_reward'] : 0)
        );
        $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
        error_log("grant_addition_reward: Notification added: " . ($notification_added ? 'true' : 'false'));

        // Get updated unread notification count
        $new_unread_count = get_student_unread_notification_count($student_post_id); // Helper function (see below)
        error_log("grant_addition_reward: New Unread Notification Count: " . $new_unread_count);

        return [
            'success'     => true,
            'points'      => $new_points,
            'coins'       => $new_coins,
            'unread_count' => $new_unread_count,
        ];
    }

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
            error_log("grant_multiplication_reward: Starting for student $student_post_id and reward $reward_id");

            if (!$student_post_id || !$reward_id) {
                return ['success' => false, 'message' => 'Missing required parameters'];
            }

            // Reward config
            $multiplication_type = $reward_data['multiplication_type'] ?? 'both';
            $multifaction_factor = max(0, floatval($reward_data['multifaction_factor'] ?? 1));

            error_log("grant_multiplication_reward: Type: $multiplication_type, Factor: $multifaction_factor");

            // Time range
            $valid_from = get_field('valid_from', $reward_id);
            $valid_until = get_field('valid_until', $reward_id);

            if (!$valid_from || !$valid_until) {
                return ['success' => false, 'message' => 'Reward period is not defined.'];
            }

            $start_time = strtotime($valid_from);
            $end_time = strtotime($valid_until);

            // ✅ Get only quest_ids now
            $quest_ids = get_completed_quest_ids_for_student($student_post_id, $start_time, $end_time);

            error_log("grant_multiplication_reward: Found " . count($quest_ids) . " completed quest IDs");

            $total_quest_points = 0;
            $total_quest_coins = 0;

            foreach ($quest_ids as $quest_id) {
                $rewards = get_quest_rewards($quest_id);
                $total_quest_points += $rewards['points'];
                $total_quest_coins += $rewards['coins'];
            }

            error_log("grant_multiplication_reward: Total Points: $total_quest_points, Total Coins: $total_quest_coins");

            // Get current student balance
            $current_points = (int) get_field('points', $student_post_id);
            $current_coins = (int) get_field('coins', $student_post_id);

            // Calculate reward
            $points_added = 0;
            $coins_added = 0;

            switch ($multiplication_type) {
                case 'points':
                    $points_added = $total_quest_points * $multifaction_factor;
                    break;
                case 'coins':
                    $coins_added = $total_quest_coins * $multifaction_factor;
                    break;
                case 'both':
                    $points_added = $total_quest_points * $multifaction_factor;
                    $coins_added = $total_quest_coins * $multifaction_factor;
                    break;
            }

            $new_points = $current_points + $points_added;
            $new_coins = $current_coins + $coins_added;

            error_log(sprintf(
                "grant_multiplication_reward: New Points: %d (+%d), New Coins: %d (+%d)",
                $new_points,
                $points_added,
                $new_coins,
                $coins_added
            ));

            // Update balances
            $points_updated = update_field('points', $new_points, $student_post_id);
            $coins_updated = update_field('coins', $new_coins, $student_post_id);

            if ( !$points_updated||!$coins_updated) {
                error_log("grant_multiplication_reward: Failed to update point or coin fields");
                error_log("Current points value: " . print_r(get_field('points', $student_post_id), true));
                error_log("Current coins value: " . print_r(get_field('coins', $student_post_id), true));
                return ['success' => false, 'message' => 'Failed to update balances'];
            }

            // Record redemption
            $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
            $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

            if (!$update_result) {
                error_log("grant_multiplication_reward: Failed to update reward history");
            }

            // Add notification
            $notification_message = sprintf(
                __('Reward claimed: +%d Points, +%d Coins (from %d quests)', 'your-theme-text-domain'),
                $points_added,
                $coins_added,
                count($quest_ids)
            );

            $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
            error_log("grant_multiplication_reward: Notification added: " . ($notification_added ? 'true' : 'false'));

            return [
                'success'      => true,
                'points'       => $new_points,
                'coins'        => $new_coins,
                'points_added' => $points_added,
                'coins_added'  => $coins_added,
                'quests_count' => count($quest_ids),
                'message'      => sprintf(
                    'Reward applied! +%d points, +%d coins from %d quests.',
                    $points_added,
                    $coins_added,
                    count($quest_ids)
                )
            ];
        }
    endif;




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
        error_log("get_student_quest_history_in_range: Searching for $quest_count_type quests for student $student_id between " . date('Y-m-d H:i:s', $start_time) . " and " . date('Y-m-d H:i:s', $end_time));

        $args = [
            'post_type'      => 'student_quests',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'student',
                    'value'   => '"' . $student_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ];

        $student_quests = get_posts($args);
        $quest_history = [];

        if ($student_quests) {
            foreach ($student_quests as $student_quest) {
                $progress_data = get_field('quest_progress', $student_quest->ID);

                if (is_array($progress_data)) {
                    foreach ($progress_data as $progress) {
                        if (isset($progress['status'], $progress['status_date']) &&
                            $progress['status'] === $quest_count_type) {

                            $status_date = strtotime($progress['status_date']);
                            if ($status_date >= $start_time && $status_date <= $end_time) {

                                $quest_history[] = [
                                    'timestamp'         => $progress['status_date'],
                                    'quest_count_type' => $quest_count_type,
                                    'student_quest_id' => $student_quest->ID,
                                    'quest_id' => array_values((array) get_field('quest', $student_quest->ID))[0] ?? null // Get quest ID directly
                                ];
                            }
                        }
                    }
                }
            }
        }

        error_log("get_student_quest_history_in_range: Found " . count($quest_history) . " matching quests");
        error_log("get_student_quest_history_in_range: Quest History Data: " . print_r($quest_history, true));
        return $quest_history;
    }

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
        error_log("grant_reload_reward: Applying Reload-Based Reward.");

        $current_coins = get_field('coins', $student_post_id) ?: 0;

        if ($current_coins < $reward_data['required_coins']) {
            error_log("grant_reload_reward: You don't have enough coins to redeem this reward.");
            return [
                'success' => false,
                'message' => 'Insufficient coins balance',
            ];
        }

        // Deduct coins
        $new_coins = $current_coins - $reward_data['required_coins'];
        $coins_updated = update_field('coins', $new_coins, $student_post_id);

        if (!$coins_updated) {
            error_log("grant_reload_reward: Failed to update coin fields.");
            return [
                'success' => false,
                'message' => 'Failed to process payment',
            ];
        }

        // Process the reload (this would call your actual reload API)
        //        $reload_processed = process_mobile_reload(
        //            get_field('mobile_number', $student_post_id),
        //            $reward_data['reload_value']
        //        );
        //
        //        if (!$reload_processed) {
        //            // Refund coins if reload failed
        //            update_field('coins', $current_coins, $student_post_id);
        //            return [
        //                'success' => false,
        //                'message' => 'Reload processing failed. Coins have been refunded.',
        //            ];
        //        }

        // Record the transaction
        $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
        $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

        if (!$update_result) {
            error_log("grant_reload_reward: Failed to update reward history.");
            // Still return success since reload was processed
        }

        // Add notification
        $notification_message = sprintf(
            __('Your redeem reward request for ₹%d worth of reload is submitted. It will be processed within 2-3 working days.',
                'your-theme-text-domain'),
            $reward_data['reload_value']
        );
        add_notification_to_student_cpt($student_post_id, $notification_message);

        return [
            'success'     => true,
            'message'     => __('Reload processed successfully!', 'your-theme-text-domain'),
            'coins'       => $new_coins,
            'reload_value' => $reward_data['reload_value'],
            'unread_count' => get_student_unread_notification_count($student_post_id),
        ];
    }
endif;

if ( ! function_exists('get_quest_rewards') ) :
    /**
     * Get quest reward details by quest ID.
     *
     * @param int $quest_id The ID of the quest post.
     * @return array An array with 'points' and 'coins' as integers.
     */
    function get_quest_rewards($quest_id) {
        if (!$quest_id || get_post_type($quest_id) !== 'quest') {
            error_log("get_quest_rewards: Invalid quest ID $quest_id");
            return ['points' => 0, 'coins' => 0];
        }

        $points = (int) get_field('points_reward', $quest_id);
        $coins = (int) get_field('coins_reward', $quest_id);

        $title = get_the_title($quest_id);
        error_log("get_quest_rewards: Quest ID: $quest_id | Title: $title | Points: $points | Coins: $coins");

        return ['points' => $points, 'coins' => $coins];
    }
endif;

if ( ! function_exists('get_completed_quest_ids_for_student') ) :
    /**
     * Get array of quest IDs completed by student in given range.
     *
     * @param int $student_id
     * @param int $start_time Timestamp
     * @param int $end_time Timestamp
     * @return array Array of unique quest IDs
     */
    function get_completed_quest_ids_for_student($student_id, $start_time, $end_time) {
        $quests_data = get_student_quest_history_in_range(
            $student_id,
            'completed',
            $start_time,
            $end_time
        );

        $quest_ids = [];

        foreach ($quests_data as $entry) {
            $quest_id = $entry['quest_id'];
            $quest_ids[] = $quest_id;
        }

        return array_unique($quest_ids);

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
        error_log("add_notification_to_student_cpt: Existing notifications: " . print_r($notifications, true));

        // Add the new notification as an array matching sub-field keys
        $notifications[] = [
            'message' => $message,
            'is_read' => false, // Or 0, depending on ACF True/False return format
            'timestamp' => current_time('mysql'),
            // 'link' => '', // Optional: Add a link if needed
        ];
        error_log("add_notification_to_student_cpt: New notifications array: " . print_r($notifications, true));

        // Update the repeater field for the specific student post
        $success = update_field($repeater_field_key, $notifications, $student_post_id);
        error_log("add_notification_to_student_cpt: Update successful: " . ($success ? 'true' : 'false'));

        return $success;
    }
endif;

if (!function_exists('is_student_eligible_for_reward')) :
    /**
     * Checks if a student is eligible to claim their reward (based on include/exclude lists,
     * cooldown, and redemption limits).
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $cooldown_period   The cooldown period in seconds.
     * @param int $reward_id         The Post ID of the "Reward Item" post.
     * @param int $redemption_limit  The maximum number of redemptions allowed (0 for unlimited).
     * @return bool True if eligible, false otherwise.
     */
    function is_student_eligible_for_reward($student_post_id, $cooldown_period, $reward_id, $redemption_limit) {
        error_log("is_student_eligible_for_reward: Checking eligibility for Student ID: {$student_post_id}, Cooldown: {$cooldown_period}, Reward ID: {$reward_id}");

        if (!function_exists('get_field') || !$student_post_id) {
            error_log("is_student_eligible_for_reward: ACF functions not found or Student Post ID is invalid.");
            return false;
        }

        // 1. Get include/exclude lists
        $include_students = get_field('include_students', $reward_id);
        $exclude_students = get_field('exclude_students', $reward_id);

        error_log("is_student_eligible_for_reward: Include Students: " . print_r($include_students, true));
        error_log("is_student_eligible_for_reward: Exclude Students: " . print_r($exclude_students, true));

        // 2. Exclude check (highest priority)
        if (is_array($exclude_students) && in_array($student_post_id, $exclude_students)) {
            error_log("is_student_eligible_for_reward: Student is explicitly excluded.");
            return false;
        }

        // 3. Include check (if not excluded)
        if (is_array($include_students) && !empty($include_students) && !in_array($student_post_id, $include_students)) {
            error_log("is_student_eligible_for_reward: Student is not in the include list.");
            return false;
        }

        // 4. If both lists are empty, all students are eligible
        if ((!is_array($include_students) || empty($include_students)) && (!is_array($exclude_students) || empty($exclude_students))) {
            error_log("is_student_eligible_for_reward: Both include and exclude lists are empty - All students eligible.");
            // 5. Cooldown and Redemption Limits Check (if not excluded)
            if ($cooldown_period > 0 || $redemption_limit > 0) {
                $claim_data = manage_reward_claims($student_post_id, $reward_id, $redemption_limit);
                $most_recent_timestamp = $claim_data['most_recent_timestamp'];
                $claim_count = $claim_data['claim_count'];

                error_log("is_student_eligible_for_reward: Last Claimed Timestamp: " . ($most_recent_timestamp ?? 'Never'));
                error_log("is_student_eligible_for_reward: Total claims: {$claim_count}");

                // If never claimed before, and within redemption limits, they're eligible (cooldown not relevant)
                if (empty($most_recent_timestamp) || ($redemption_limit > 0 && $claim_count < $redemption_limit)) {
                    error_log("is_student_eligible_for_reward: No previous claims or within redemption limit - eligible");
                    return true;
                }

                $now = current_time('timestamp');
                $last_claimed_time = strtotime($most_recent_timestamp);
                $time_since_last_claim = $now - $last_claimed_time;

                error_log("is_student_eligible_for_reward: Current Time: {$now}");
                error_log("is_student_eligible_for_reward: Last Claim Time: {$last_claimed_time}");
                error_log("is_student_eligible_for_reward: Time Since Last Claim: {$time_since_last_claim} seconds");
                error_log("is_student_eligible_for_reward: Cooldown Period: {$cooldown_period} seconds");

                // Check redemption limit
                if ($redemption_limit > 0 && $claim_count >= $redemption_limit) {
                    error_log("is_student_eligible_for_reward: Redemption limit reached ({$claim_count}/{$redemption_limit})");
                    return false;
                }

                // Then check cooldown period
                $cooldown_remaining = $cooldown_period - $time_since_last_claim;
                if ($cooldown_remaining > 0) {
                    // Convert seconds to human-readable format
                    $days = floor($cooldown_remaining / 86400);
                    $hours = floor(($cooldown_remaining % 86400) / 3600);
                    $minutes = floor(($cooldown_remaining % 3600) / 60);
                    $seconds = $cooldown_remaining % 60;

                    $time_remaining = '';
                    if ($days > 0) $time_remaining .= "{$days} day" . ($days > 1 ? 's' : '') . ' ';
                    if ($hours > 0) $time_remaining .= "{$hours} hour" . ($hours > 1 ? 's' : '') . ' ';
                    if ($minutes > 0) $time_remaining .= "{$minutes} minute" . ($minutes > 1 ? 's' : '') . ' ';
                    if ($seconds > 0 && ($days == 0 && $hours == 0)) {
                        $time_remaining .= "{$seconds} second" . ($seconds > 1 ? 's' : '');
                    }

                    error_log("is_student_eligible_for_reward: Cooldown not expired - Time remaining: " . trim($time_remaining));
                    return false;
                }
            }
            error_log("is_student_eligible_for_reward: Student is eligible");
            return true;
        }

        // 6. Cooldown and Redemption Limits Check (if not excluded or included)
        if ($cooldown_period > 0 || $redemption_limit > 0) {
            $claim_data = manage_reward_claims($student_post_id, $reward_id, $redemption_limit);
            $most_recent_timestamp = $claim_data['most_recent_timestamp'];
            $claim_count = $claim_data['claim_count'];

            error_log("is_student_eligible_for_reward: Last Claimed Timestamp: " . ($most_recent_timestamp ?? 'Never'));
            error_log("is_student_eligible_for_reward: Total claims: {$claim_count}");

            // If never claimed before, and within redemption limits, they're eligible (cooldown not relevant)
            if (empty($most_recent_timestamp) || ($redemption_limit > 0 && $claim_count < $redemption_limit)) {
                error_log("is_student_eligible_for_reward: No previous claims or within redemption limit - eligible");
                return true;
            }

            $now = current_time('timestamp');
            $last_claimed_time = strtotime($most_recent_timestamp);
            $time_since_last_claim = $now - $last_claimed_time;

            error_log("is_student_eligible_for_reward: Current Time: {$now}");
            error_log("is_student_eligible_for_reward: Last Claim Time: {$last_claimed_time}");
            error_log("is_student_eligible_for_reward: Time Since Last Claim: {$time_since_last_claim} seconds");
            error_log("is_student_eligible_for_reward: Cooldown Period: {$cooldown_period} seconds");

            // Check redemption limit
            if ($redemption_limit > 0 && $claim_count >= $redemption_limit) {
                error_log("is_student_eligible_for_reward: Redemption limit reached ({$claim_count}/{$redemption_limit})");
                return false;
            }

            // Then check cooldown period
            $cooldown_remaining = $cooldown_period - $time_since_last_claim;
            if ($cooldown_remaining > 0) {
                // Convert seconds to human-readable format
                $days = floor($cooldown_remaining / 86400);
                $hours = floor(($cooldown_remaining % 86400) / 3600);
                $minutes = floor(($cooldown_remaining % 3600) / 60);
                $seconds = $cooldown_remaining % 60;

                $time_remaining = '';
                if ($days > 0) $time_remaining .= "{$days} day" . ($days > 1 ? 's' : '') . ' ';
                if ($hours > 0) $time_remaining .= "{$hours} hour" . ($hours > 1 ? 's' : '') . ' ';
                if ($minutes > 0) $time_remaining .= "{$minutes} minute" . ($minutes > 1 ? 's' : '') . ' ';
                if ($seconds > 0 && ($days == 0 && $hours == 0)) {
                    $time_remaining .= "{$seconds} second" . ($seconds > 1 ? 's' : '');
                }

                error_log("is_student_eligible_for_reward: Cooldown not expired - Time remaining: " . trim($time_remaining));
                return false;
            }
        }

        error_log("is_student_eligible_for_reward: Student is eligible");
        return true;
    }
endif;

if (!function_exists('manage_reward_claims')) :
    /**
     * Helper function to check for existing reward claims and check redemption limits.
     *
     * @param int    $student_post_id  The Student CPT ID.
     * @param int    $reward_item_id   The ID of the specific reward (Reward Item post ID).
     * @param int    $redemption_limit The maximum number of times the reward can be claimed (0 for unlimited).
     * @return array An array containing 'claim_count' (int), 'most_recent_timestamp' (string or null), and 'can_redeem' (bool).
     */
    function manage_reward_claims($student_post_id, $reward_item_id, $redemption_limit) {
        error_log("manage_reward_claims: Function started. Student ID: {$student_post_id}, Reward ID: {$reward_item_id}, Limit: {$redemption_limit}");

        if (!function_exists('get_field')) {
            error_log('manage_reward_claims: ACF functions not available.');
            return ['claim_count' => 0, 'most_recent_timestamp' => null, 'can_redeem' => false, 'log_messages' => []];
        }

        error_log("manage_reward_claims: ACF check passed.");

        $log_messages = [];
        $student_redeems_posts = get_posts([
            'post_type' => 'students_redeems',
            'numberposts' => -1,
        ]);

        error_log("manage_reward_claims: Retrieved " . count($student_redeems_posts) . " students_redeems posts.");

        $claim_count = 0;
        $most_recent_timestamp = null;

        if (!empty($student_redeems_posts)) {
            error_log("manage_reward_claims: Processing student_redeems posts.");
            foreach ($student_redeems_posts as $student_redeems_post) {
                error_log("manage_reward_claims: Checking post ID: " . $student_redeems_post->ID);
                $claimed_history = get_field('claimed_history', $student_redeems_post->ID);
                error_log("manage_reward_claims: Claimed history: " . print_r($claimed_history, true));

                if (is_array($claimed_history)) {
                    error_log("manage_reward_claims: Claimed history is an array.");
                    foreach ($claimed_history as $claim) {
                        error_log("manage_reward_claims: Checking claim: " . print_r($claim, true));
                        // Check if this claim matches our student and reward
                        if (is_array($claim) &&
                            isset($claim['reward_item']) &&
                            is_array($claim['reward_item']) &&
                            isset($claim['reward_item'][0]) &&
                            intval($claim['reward_item'][0]) == $reward_item_id &&
                            isset($claim['student']) &&
                            is_array($claim['student']) &&
                            isset($claim['student'][0]) &&
                            intval($claim['student'][0]) == $student_post_id
                        ) {
                            error_log("manage_reward_claims: Found matching claim in post {$student_redeems_post->ID}");

                            // Check if we have timestamps for this claim
                            if (isset($claim['claimed_timestamps']) && is_array($claim['claimed_timestamps'])) {
                                error_log("manage_reward_claims: Processing claimed timestamps.");
                                foreach ($claim['claimed_timestamps'] as $timestamp_entry) {
                                    if (isset($timestamp_entry['timestamp'])) {
                                        $claim_count++;
                                        $current_timestamp = strtotime($timestamp_entry['timestamp']);

                                        // Update most recent timestamp if this one is newer
                                        if ($most_recent_timestamp === null || $current_timestamp > strtotime($most_recent_timestamp)) {
                                            $most_recent_timestamp = $timestamp_entry['timestamp'];
                                            error_log("manage_reward_claims: New most recent timestamp: {$most_recent_timestamp}");
                                        }
                                    }
                                }
                            } else {
                                error_log("manage_reward_claims: No claimed_timestamps found for this claim");
                            }
                        }
                    }
                } else {
                    error_log("manage_reward_claims: Claimed history is NOT an array.");
                }
            }
        } else {
            error_log("manage_reward_claims: No student_redeems posts found.");
        }

        $can_redeem = ($redemption_limit == 0 || $claim_count < $redemption_limit);
        error_log("manage_reward_claims: Final count - claims: {$claim_count}, can_redeem: " . ($can_redeem ? 'true' : 'false'));

        return [
            'claim_count' => $claim_count,
            'most_recent_timestamp' => $most_recent_timestamp,
            'can_redeem' => $can_redeem,
            'log_messages' => $log_messages,
        ];
    }
endif;

if (!function_exists('update_reward_claims')) :
    /**
     * Updates the reward claims by adding a new entry to the claimed_history repeater
     * with the timestamp in the correct nested structure, and sets the status within the statuses repeater.
     */
    function update_reward_claims($student_post_id, $reward_item_id, $timestamp) { // Removed $status parameter
        if (!function_exists('get_field') || !function_exists('update_field')) {
            error_log('update_reward_claims: ACF functions not available.');
            return ['success' => false, 'message' => 'ACF functions not available'];
        }

        // Get or create students_redeems post
        $student_redeems_posts = get_posts([
            'post_type' => 'students_redeems',
            'numberposts' => 1,
            'orderby' => 'ID',
            'order' => 'DESC'
        ]);

        if (empty($student_redeems_posts)) {
            $new_post_id = wp_insert_post([
                'post_type' => 'students_redeems',
                'post_title' => 'Reward Claims History',
                'post_status' => 'publish'
            ]);

            if (!$new_post_id || is_wp_error($new_post_id)) {
                error_log('update_reward_claims: Failed to create new students_redeems post.');
                return ['success' => false, 'message' => 'Failed to create history post'];
            }
            $student_redeems_post = get_post($new_post_id);
        } else {
            $student_redeems_post = $student_redeems_posts[0];
        }

        // Get existing claimed_history or initialize
        $claimed_history = get_field('claimed_history', $student_redeems_post->ID);
        if (!is_array($claimed_history)) {
            $claimed_history = [];
        }

        // Get the promotion type from the reward item
        $promotion_type = get_field('promotion_type', $reward_item_id);
        $status = ($promotion_type == 'reload') ? 'pending' : 'completed';


        // Find or create the specific claim entry
        $claim_found = false;
        foreach ($claimed_history as &$claim) {
            if (isset($claim['reward_item'][0]) && $claim['reward_item'][0] == $reward_item_id &&
                isset($claim['student'][0]) && $claim['student'][0] == $student_post_id) {

                // Initialize claimed_timestamps if not exists
                if (!isset($claim['claimed_timestamps']) || !is_array($claim['claimed_timestamps'])) {
                    $claim['claimed_timestamps'] = [];
                }

                // Add new timestamp
                $claim['claimed_timestamps'][] = ['timestamp' => $timestamp];

                // Initialize statuses if not exists
                if (!isset($claim['statuses']) || !is_array($claim['statuses'])) {
                    $claim['statuses'] = [];
                }

                // Add new status
                $claim['statuses'][] = ['status' => $status];

                $claim_found = true;
                break;
            }
        }

        // If no existing claim found, create new one
        if (!$claim_found) {
            $claimed_history[] = [
                'reward_item' => [$reward_item_id],
                'student' => [$student_post_id],
                'claimed_timestamps' => [
                    ['timestamp' => $timestamp]
                ],
                'statuses' => [
                    ['status' => $status]
                ]
            ];
        }

        // Update the field - ACF sometimes returns unexpected values
        $update_result = update_field('claimed_history', $claimed_history, $student_redeems_post->ID);

        // Verify the update actually worked by checking the stored value
        $updated_history = get_field('claimed_history', $student_redeems_post->ID);
        $update_verified = false;
        $outer_loop_broken = false; // Flag to indicate outer loop break

        if (is_array($updated_history)) {
            foreach ($updated_history as $claim) {
                if ($outer_loop_broken) {
                    break; // Break out of the outer loop if needed
                }
                if (isset($claim['reward_item'][0]) && $claim['reward_item'][0] == $reward_item_id &&
                    isset($claim['student'][0]) && $claim['student'][0] == $student_post_id) {

                    $timestamp_verified = false;
                    if (isset($claim['claimed_timestamps']) && is_array($claim['claimed_timestamps'])) {
                        foreach ($claim['claimed_timestamps'] as $ts) {
                            if (isset($ts['timestamp']) && $ts['timestamp'] == $timestamp) {
                                $timestamp_verified = true;
                                break;
                            }
                        }
                    }

                    $status_verified = false;
                    if (isset($claim['statuses']) && is_array($claim['statuses'])) {
                        foreach ($claim['statuses'] as $s) {
                            if (isset($s['status']) && $s['status'] == $status) {
                                $status_verified = true;
                                break;
                            }
                        }
                    }

                    if ($timestamp_verified && $status_verified) {
                        $update_verified = true;
                        $outer_loop_broken = true;
                        break;
                    }
                }
            }
        }


        if ($update_verified) {
            error_log("update_reward_claims: Successfully verified update for student {$student_post_id} and reward {$reward_item_id}");
            return ['success' => true, 'message' => 'Reward claim updated'];
        } else {
            error_log("update_reward_claims: Update verification failed for student {$student_post_id} and reward {$reward_item_id}");
            error_log("update_reward_claims: Update result was: " . print_r($update_result, true));
            error_log("update_reward_claims: Attempted to store: " . print_r($claimed_history, true));
            error_log("update_reward_claims: Actually stored: " . print_r($updated_history, true));
            return ['success' => false, 'message' => 'Failed to verify reward claim update'];
        }
    }
endif;

if (!function_exists('confirm_reload_reward')) :
    /**
     * Handles the reload reward confirmation process
     */
    function confirm_reload_reward($student_post_id, $reward_data) {
        // Get student's phone number
        $phone_number = get_field('mobile_number', $student_post_id);

        if (empty($phone_number)) {
            return [
                'success' => false,
                'message' => 'No mobile number found in your profile. Please update your profile.'
            ];
        }

        // Verify sufficient coin balance
        $current_coins = get_field('coins', $student_post_id) ?: 0;
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
