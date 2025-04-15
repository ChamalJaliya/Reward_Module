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
if (!function_exists('get_student_post_id_by_email')) :
    /**
     * Retrieves the WordPress post ID of a student based on their email address.
     * Assumes the student's email is stored as a post meta field (e.g., 'student_email').
     *
     * @param string $email The student's email address.
     * @return int|false The post ID if found, false otherwise.
     */
    function get_student_post_id_by_email($email) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'student_email' AND meta_value = %s",
            $email
        );
        $result = $wpdb->get_col($query);
        return !empty($result) ? intval($result[0]) : false;
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
               if (!$is_confirmed) {
                   $response = [
                       'success' => true,
                       'needs_confirmation' => true,
                       'message' => sprintf(
                           'We will send ₹%d reload to %s. Confirm to proceed?',
                           $reward_data['reload_value'],
                           $phone_number
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
                   wp_die(); // THIS WAS MISSING AND CRUCIAL
               }
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

if (!function_exists('grant_reward')) :
    /**
     * Grants the daily reward to a student.
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $reward_data     The number of points to award.
     * @param int $reward_id      The Post ID of the reward CPT
     * @return array An array containing success status and updated data.
     */
    function grant_reward($student_post_id, $reward_data ,$reward_id  ) {
        error_log("grant_reward: Function initiated for Student ID: " . $student_post_id . ", Reward Data: " . $reward_data . ", Reward Post ID: " . $reward_id);

        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
            error_log("daily_reward: ACF functions not found or Student Post ID is invalid.");
            return ['success' => false];
        }
        $promotion_type = $reward_data['promotion_type'] ?? 'addition';

        switch ($promotion_type) {
            case 'addition':
                error_log("grant_reward: Applying Addition-Based Reward.");

                $current_points = get_field('points', $student_post_id) ?: 0;
                $current_coins = get_field('coins', $student_post_id) ?: 0;

                $new_points = $current_points + $reward_data['points'];
                $new_coins = $current_coins + $reward_data['coins'];

                error_log("grant_reward: New Points: " . $new_points . ", New Coins: " . $new_coins);

                $points_updated = update_field('points', $new_points, $student_post_id);
                $coins_updated = update_field('coins', $new_coins, $student_post_id);
                error_log("grant_reward: Points Updated: " . ($points_updated ? 'true' : 'false') . ", Coins Updated: " . ($coins_updated ? 'true' : 'false'));

                if (!$points_updated || !$coins_updated) {
                    error_log("grant_reward: Failed to update point or coin fields.");
                    return ['success' => false];
                }

                // Add the last claim to the 'claimed_history' CPT
                $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
                $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);
                if (!$update_result) {
                    error_log("grant_reward: Failed to update user_reward_history.");
                    return ['success' => false, 'message' => 'Failed to update reward history.'];
                }


                // Add notification
                $notification_message = sprintf(
                    __('reward claimed: +%d Points, +%d Coins', 'your-theme-text-domain'),
                    $reward_data['points'],
                    $reward_data['coins']
                );
                $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
                error_log("grant_additional_reward: Notification added: " . ($notification_added ? 'true' : 'false'));

                // Get updated unread notification count
                $new_unread_count = get_student_unread_notification_count($student_post_id); // Helper function (see below)
                error_log("grant_reward: New Unread Notification Count: " . $new_unread_count);

                return [
                    'success' => true,
                    'points' => $new_points,
                    'coins' => $new_coins,
                    'unread_count' => $new_unread_count
                ];

            case 'multiplication':
                error_log("grant_reward: Applying Multiplication-Based Reward.");
                $multiplication_type = $reward_data['multiplication_type'] ?? 'both';
                $multifaction_factor = $reward_data['multifaction_factor'] ?? 1;

                switch ($multiplication_type) {
                    case 'coins':
//                        $new_coins *= $multifaction_factor;
                        break;
                    case 'stars': // Assuming 'stars' is a field in your student CPT
                        $current_stars = get_field('stars', $student_post_id) ?: 0;
                        $new_stars = $current_stars * $multifaction_factor;
                        $stars_updated = update_field('stars', $new_stars, $student_post_id);
                        error_log("grant_reward: Stars Updated: " . ($stars_updated ? 'true' : 'false'));
                        break;
                    case 'both':
//                        $new_points *= $multifaction_factor;
//                        $new_coins *= $multifaction_factor;
                        break;
                }
                break;
            case 'reload':
                error_log("grant_reward: Applying Reload-Based Reward.");

                // Check if this is a confirmed request
               $is_confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === 'true';

               if (!$is_confirmed) {
                   return [
                       'success' => false,
                       'message' => 'Reload request not confirmed'
                   ];
               }

                $current_coins = get_field('coins', $student_post_id) ?: 0;

                if ($current_coins < $reward_data['required_coins']) {
                    error_log("grant_reward: You don't have enough coins to redeem this reward.");
                    return [
                        'success' => false,
                        'message' => 'Insufficient coins balance'
                    ];
                }

                // Deduct coins
                $new_coins = $current_coins - $reward_data['required_coins'];
                $coins_updated = update_field('coins', $new_coins, $student_post_id);

                if (!$coins_updated) {
                    error_log("grant_reward: Failed to update coin fields.");
                    return [
                        'success' => false,
                        'message' => 'Failed to process payment'
                    ];
                }

            //     // Process the reload (this would call your actual reload API)
            //    $reload_processed = process_mobile_reload(
            //        get_field('mobile_number', $student_post_id),
            //        $reward_data['reload_value']
            //    );

            //    if (!$reload_processed) {
            //        // Refund coins if reload failed
            //        update_field('coins', $current_coins, $student_post_id);
            //        return [
            //            'success' => false,
            //            'message' => 'Reload processing failed. Coins have been refunded.'
            //        ];
            //    }

                // Record the transaction
                $timestamp = date('Y-m-d H:i:s', current_time('timestamp'));
                $update_result = update_reward_claims($student_post_id, $reward_id, $timestamp);

                if (!$update_result) {
                    error_log("grant_reward: Failed to update reward history.");
                    // Still return success since reload was processed
                }

                // Add notification
                $notification_message = sprintf(
                    __('Your redeem reward request for ₹%d worth of reload is submitted. It will be processed within 2-3 working days.', 'your-theme-text-domain'),
                    $reward_data['reload_value']
                );
                add_notification_to_student_cpt($student_post_id, $notification_message);

                return [
                    'success' => true,
                    'message' => __('Reload processed successfully!', 'your-theme-text-domain'),
                    'coins' => $new_coins,
                    'reload_value' => $reward_data['reload_value'],
                    'unread_count' => get_student_unread_notification_count($student_post_id)
                ];
            default:
                error_log("grant_reward: Unknown promotion type: " . $promotion_type);
                return ['success' => false, 'message' => 'Unknown promotion type.'];
        }


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
     * Checks if a student is eligible to claim their reward (based on cooldown and redemption limits).
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

        // No cooldown means always eligible (if within redemption limits)
        if ($cooldown_period <= 0) {
            error_log("is_student_eligible_for_reward: No cooldown period set.");
            return true;
        }

        $claim_data = manage_reward_claims($student_post_id, $reward_id, $redemption_limit);
        $most_recent_timestamp = $claim_data['most_recent_timestamp'];
        $claim_count = $claim_data['claim_count'];

        error_log("is_student_eligible_for_reward: Last Claimed Timestamp: " . ($most_recent_timestamp ?? 'Never'));
        error_log("is_student_eligible_for_reward: Total claims: {$claim_count}");

        // If never claimed before, they're eligible
        if (empty($most_recent_timestamp)) {
            error_log("is_student_eligible_for_reward: No previous claims found - eligible");
            return true;
        }

        $now = current_time('timestamp');
        $last_claimed_time = strtotime($most_recent_timestamp);
        $time_since_last_claim = $now - $last_claimed_time;

        error_log("is_student_eligible_for_reward: Current Time: {$now}");
        error_log("is_student_eligible_for_reward: Last Claim Time: {$last_claimed_time}");
        error_log("is_student_eligible_for_reward: Time Since Last Claim: {$time_since_last_claim} seconds");
        error_log("is_student_eligible_for_reward: Cooldown Period: {$cooldown_period} seconds");

        // Check redemption limit first
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
     * with the timestamp in the correct nested structure.
     */
    function update_reward_claims($student_post_id, $reward_item_id, $timestamp) {
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
                ]
            ];
        }

        // Update the field - ACF sometimes returns unexpected values
        $update_result = update_field('claimed_history', $claimed_history, $student_redeems_post->ID);

        // Verify the update actually worked by checking the stored value
        $updated_history = get_field('claimed_history', $student_redeems_post->ID);
        $update_verified = false;

        if (is_array($updated_history)) {
            foreach ($updated_history as $claim) {
                if (isset($claim['reward_item'][0]) && $claim['reward_item'][0] == $reward_item_id &&
                    isset($claim['student'][0]) && $claim['student'][0] == $student_post_id) {

                    if (isset($claim['claimed_timestamps'])) {
                        foreach ($claim['claimed_timestamps'] as $ts) {
                            if ($ts['timestamp'] == $timestamp) {
                                $update_verified = true;
                                break 2;
                            }
                        }
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
