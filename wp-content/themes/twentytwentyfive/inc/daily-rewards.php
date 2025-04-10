<?php

/**
 * Daily reward system functionality with added error logging for debugging.
 */

if (!function_exists('claim_daily_reward_ajax')) :
    /**
     * AJAX handler for claiming daily rewards.
     */
    function claim_daily_reward_ajax() {
        error_log('AJAX action: claim_daily_reward_ajax initiated.');

        // Security Check - COMMENTED OUT FOR TESTING
        // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'daily_reward_nonce')) {
        //     error_log("Nonce verification failed! POST data: " . print_r($_POST, true));
        //     wp_send_json_error(['success' => false, 'message' => 'Nonce verification failed!']);
        //     return;
        // }

        // Get the student identifier
        $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : '';
        error_log("claim_daily_reward_ajax: Student Identifier received: " . $student_identifier);
        if (empty($student_identifier)) {
            error_log("claim_daily_reward_ajax: Student identifier (email) is missing.");
            wp_send_json_error(['success' => false, 'message' => 'Student identifier (email) is missing.']);
            return;
        }

        // Find the student's post ID
        $student_post_id = get_student_post_id_by_email($student_identifier);
        error_log("claim_daily_reward_ajax: Student Post ID found: " . $student_post_id);
        if (!$student_post_id) {
            error_log("claim_daily_reward_ajax: Could not find student profile for email: " . $student_identifier);
            wp_send_json_error(['success' => false, 'message' => 'Could not find student profile.']);
            return;
        }

        // Get Daily Reward Details
        $daily_reward_data = get_daily_reward_data();
        error_log("claim_daily_reward_ajax: Daily Reward Data: " . print_r($daily_reward_data, true));
        if (!$daily_reward_data['success']) {
            error_log("claim_daily_reward_ajax: Failed to get daily reward data: " . $daily_reward_data['message']);
            wp_send_json_error(['success' => false, 'message' => $daily_reward_data['message']]);
            return;
        }

        $reward_points = $daily_reward_data['points'];
        $reward_coins = $daily_reward_data['coins'];
        $cooldown_period = $daily_reward_data['cooldown'];
        $daily_reward_post_id = $daily_reward_data['reward_id']; // Get the Reward Item post ID!
        error_log("claim_daily_reward_ajax: Reward Points: " . $reward_points . ", Reward Coins: " . $reward_coins . ", Cooldown: " . $cooldown_period . ", Daily Reward Post ID: " . $daily_reward_post_id);

        // Check eligibility (Cooldown)
        $is_eligible = is_student_eligible_for_daily_reward($student_post_id, $cooldown_period, $daily_reward_post_id); // Pass the ID!
        error_log("claim_daily_reward_ajax: Is student eligible for daily reward: " . ($is_eligible ? 'true' : 'false'));
        if ($cooldown_period > 0 && !$is_eligible) {
            error_log("claim_daily_reward_ajax: Student not eligible due to cooldown.");
            wp_send_json_error(['success' => false, 'message' => 'You are not eligible to claim the daily reward yet.']);
            return;
        }

        // Grant the reward
        $reward_granted_data = grant_daily_reward($student_post_id, $reward_points, $reward_coins, $daily_reward_post_id); // Pass the ID!
        error_log("claim_daily_reward_ajax: Reward Granted Data: " . print_r($reward_granted_data, true));

        if ($reward_granted_data['success']) {
            $updated_points = $reward_granted_data['points'];
            $updated_coins = $reward_granted_data['coins'];
            $new_unread_count = $reward_granted_data['unread_count'];
            error_log("claim_daily_reward_ajax: Daily reward granted successfully. Points: " . $updated_points . ", Coins: " . $updated_coins . ", Unread Notifications: " . $new_unread_count);
            wp_send_json_success([
                'success' => true,
                'message' => 'Daily reward claimed successfully!',
                'points' => $updated_points,
                'coins' => $updated_coins,
                'unread_count' => $new_unread_count
            ]);
        } else {
            error_log("claim_daily_reward_ajax: Failed to grant daily reward.");
            wp_send_json_error(['success' => false, 'message' => 'Failed to grant daily reward.']);
        }
    }
    add_action('wp_ajax_claim_daily_reward', 'claim_daily_reward_ajax');
    add_action('wp_ajax_nopriv_claim_daily_reward', 'claim_daily_reward_ajax');
endif;

if (!function_exists('grant_daily_reward')) :
    /**
     * Grants the daily reward to a student.
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $reward_points     The number of points to award.
     * @param int $reward_coins      The number of coins to award.
     * @param int $daily_reward_post_id The Post ID of the "Reward Item" post (Daily Reward).
     * @return array An array containing success status and updated data.
     */
    function grant_daily_reward($student_post_id, $reward_points, $reward_coins, $daily_reward_post_id) { // Added $daily_reward_post_id
        error_log("grant_daily_reward: Function initiated for Student ID: " . $student_post_id . ", Points: " . $reward_points . ", Coins: " . $reward_coins . ", Daily Reward Post ID: " . $daily_reward_post_id);
        if (!function_exists('get_field') || !function_exists('update_field') || !$student_post_id) {
            error_log("grant_daily_reward: ACF functions not found or Student Post ID is invalid.");
            return ['success' => false];
        }

        $current_points = get_field('points', $student_post_id) ?: 0;
        $current_coins = get_field('coins', $student_post_id) ?: 0;

        $new_points = $current_points + $reward_points;
        $new_coins = $current_coins + $reward_coins;
        error_log("grant_daily_reward: New Points: " . $new_points . ", New Coins: " . $new_coins);

        $points_updated = update_field('points', $new_points, $student_post_id);
        $coins_updated = update_field('coins', $new_coins, $student_post_id);
        error_log("grant_daily_reward: Points Updated: " . ($points_updated ? 'true' : 'false') . ", Coins Updated: " . ($coins_updated ? 'true' : 'false'));

        if (!$points_updated || !$coins_updated) {
            error_log("grant_daily_reward: Failed to update point or coin fields.");
            return ['success' => false];
        }

        // Add the last claim to the 'claimed_history' CPT
        $last_claimed_updated = manage_user_reward_claims($student_post_id, $daily_reward_post_id, current_time('timestamp'));
        if (!$last_claimed_updated) {
            error_log("grant_daily_reward: Failed to update user_reward_history.");
            return ['success' => false, 'message' => 'Failed to update reward history.'];
        }

        // Add notification
        $notification_message = sprintf(
            __('Daily reward claimed: +%d Points, +%d Coins', 'your-theme-text-domain'),
            $reward_points,
            $reward_coins
        );
        $notification_added = add_notification_to_student_cpt($student_post_id, $notification_message);
        error_log("grant_daily_reward: Notification added: " . ($notification_added ? 'true' : 'false'));

        // Get updated unread notification count
        $new_unread_count = get_student_unread_notification_count($student_post_id); // Helper function (see below)
        error_log("grant_daily_reward: New Unread Notification Count: " . $new_unread_count);

        return [
            'success' => true,
            'points' => $new_points,
            'coins' => $new_coins,
            'unread_count' => $new_unread_count
        ];
    }
endif;

if (!function_exists('is_student_eligible_for_daily_reward')) :
    /**
     * Checks if a student is eligible to claim their daily reward (based on cooldown).
     *
     * @param int $student_post_id   The Post ID of the student CPT.
     * @param int $cooldown_period The cooldown period in seconds.
     * @param int $daily_reward_post_id The Post ID of the "Reward Item" post (Daily Reward).
     * @return bool True if eligible, false otherwise.
     */
    function is_student_eligible_for_daily_reward($student_post_id, $cooldown_period, $daily_reward_post_id) { // Added $daily_reward_post_id
        error_log("is_student_eligible_for_daily_reward: Checking eligibility for Student ID: " . $student_post_id . ", Cooldown: " . $cooldown_period . ", Daily Reward Post ID: " . $daily_reward_post_id);
        if (!function_exists('get_field') || !$student_post_id) {
            error_log("is_student_eligible_for_daily_reward: ACF functions not found or Student Post ID is invalid.");
            return false;
        }

        if ($cooldown_period <= 0) {
            error_log("is_student_eligible_for_daily_reward: No cooldown, student is eligible.");
            return true; // No cooldown
        }

        $last_claimed_timestamps = manage_user_reward_claims($student_post_id, $daily_reward_post_id); // Get all timestamps
        $now = time();

        error_log("is_student_eligible_for_daily_reward: Last Claimed Timestamps: " . print_r($last_claimed_timestamps, true));
        error_log("is_student_eligible_for_daily_reward: Current Time: " . $now);

        $eligible = true;
        if (is_array($last_claimed_timestamps)) {
            foreach ($last_claimed_timestamps as $last_claimed_timestamp) {
                if (time() - $last_claimed_timestamp < $cooldown_period) {
                    $eligible = false;
                    break; // No need to check further if one is within cooldown
                }
            }
        }
        error_log("is_student_eligible_for_daily_reward: Eligibility: " . ($eligible ? 'true' : 'false'));
        return $eligible;
    }
endif;


if (!function_exists('get_daily_reward_data')) :
    /**
     * Helper function to get the daily reward points, coins, cooldown, and other details from a specific "Reward Item" post.
     *
     * @return array An array containing 'success' (bool), 'points' (int), 'coins' (int), 'cooldown' (int), and 'message' (string).
     */
    function get_daily_reward_data() {
        error_log("get_daily_reward_data: Function initiated.");
        if (!function_exists('get_field')) {
            error_log("get_daily_reward_data: ACF is not active.");
            return ['success' => false, 'message' => 'ACF is not active.'];
        }

        // 1. Get the "Daily Reward Configuration" post
        $daily_reward_posts = get_posts(array(
            'post_type' => 'reward-item',
            'title'     => 'Daily Reward', // Adjust this title if needed
            'posts_per_page' => 1,
            'fields' => 'ids',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));

        if (!$daily_reward_posts) {
            error_log("get_daily_reward_data: Daily Reward post not found.");
            return ['success' => false, 'message' => 'Daily Reward post not found.'];
        }

        $daily_reward_post_id = $daily_reward_posts[0];
        error_log("get_daily_reward_data: Daily Reward Post ID: " . $daily_reward_post_id);

        // 2. Get Reward Details
        $promotion_type = get_field('promotion_type', $daily_reward_post_id) ?: '';
        $additional_type = get_field('additional_type', $daily_reward_post_id) ?: '';
        $additional_reward = intval(get_field('additional_reward', $daily_reward_post_id) ?: 0);
        $cooldown_period = intval(get_field('cooldown_period', $daily_reward_post_id) ?: 0);
        $valid_from = get_field('valid_from', $daily_reward_post_id);
        $valid_until = get_field('valid_until', $daily_reward_post_id);
        error_log("get_daily_reward_data: Promotion Type: " . $promotion_type . ", Additional Type: " . $additional_type . ", Additional Reward: " . $additional_reward . ", Cooldown: " . $cooldown_period . ", Valid From: " . $valid_from . ", Valid Until: " . $valid_until);

        // 3. Validity Checks
        $now = current_time('timestamp');

        if ($valid_from && strtotime($valid_from) > $now) {
            error_log("get_daily_reward_data: Daily reward is not yet available. Valid From: " . $valid_from . " (timestamp: " . strtotime($valid_from) . "), Current Time: " . $now);
            return ['success' => false, 'message' => 'Daily reward is not yet available.'];
        }

        if ($valid_until && strtotime($valid_until) < $now) {
            error_log("get_daily_reward_data: Daily reward has expired. Valid Until: " . $valid_until . " (timestamp: " . strtotime($valid_until) . "), Current Time: " . $now);
            return ['success' => false, 'message' => 'Daily reward has expired.'];
        }

        // 4. Calculate Reward Values
        $reward_points = 0;
        $reward_coins = 0;

        if ($promotion_type === 'addition') {
            if ($additional_type === 'points' || $additional_type === 'both') {
                $reward_points = $additional_reward;
            }
            if ($additional_type === 'coins' || $additional_type === 'both') {
                $reward_coins = $additional_reward;
            }
        }
        error_log("get_daily_reward_data: Calculated Reward Points New Edited: " . $reward_points . ", Calculated Reward Coins: " . $reward_coins);

        return [
            'success' => true,
            'points' => $reward_points,
            'coins' => $reward_coins,
            'cooldown' => $cooldown_period,
            'reward_id' => $daily_reward_post_id // Return the Reward Item post ID!
        ];
    }
endif;

if (!function_exists('get_student_unread_notification_count')) :
    /**
     * Helper function to get the number of unread notifications for a student.
     *
     * @param int $student_post_id The Post ID of the student CPT.
     * @return int The number of unread notifications.
     */
    function get_student_unread_notification_count($student_post_id) {
        error_log("get_student_unread_notification_count: Function initiated for Student ID: " . $student_post_id);
        if (!function_exists('get_field') || !$student_post_id) {
            error_log("get_student_unread_notification_count: ACF functions not found or Student Post ID is invalid.");
            return 0;
        }

        $unread_count = 0;
        $notifications = get_field('student_notifications', $student_post_id);
        error_log("get_student_unread_notification_count: Retrieved notifications: " . print_r($notifications, true));
        if (is_array($notifications)) {
            foreach ($notifications as $note) {
                if (isset($note['is_read']) && !$note['is_read']) {
                    $unread_count++;
                }
            }
        }
        error_log("get_student_unread_notification_count: Unread notification count: " . $unread_count);
        return $unread_count;
    }
endif;

if (!function_exists('get_student_post_id_by_email')) :
    /**
     * Helper function to find a student CPT post ID by their email ACF field.
     *
     * @param string $email The student's email address.
     * @return int|false The student's post ID, or false if not found.
     */
    function get_student_post_id_by_email($email) {
        error_log("get_student_post_id_by_email: Searching for Student with email: " . $email);
        if (empty($email)) {
            error_log("get_student_post_id_by_email: Email address is empty.");
            return false;
        }
        $args = array(
            'post_type' => 'student',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '=',
                ),
            ),
            'fields' => 'ids',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        $student_query = new WP_Query($args);
        error_log("get_student_post_id_by_email: WP_Query results: " . print_r($student_query->posts, true));
        if ($student_query->have_posts()) {
            return $student_query->posts[0];
        }
        error_log("get_student_post_id_by_email: Student not found with email: " . $email);
        return false;
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

if (!function_exists('manage_reward_claims')) :
    /**
     * Helper function to manage the 'claimed_history' Repeater field.
     *
     * @param int    $student_post_id The Student CPT ID.
     * @param int    $promotion_id    The ID of the specific promotion/reward (Reward Item post ID).
     * @param int    $timestamp       (Optional) The timestamp to set. If null, retrieves the last claimed time.
     * @return int|bool If $timestamp is null, returns the last claimed timestamp (or 0 if not found).
     * If $timestamp is provided, returns true on success, false on failure.
     */
    function manage_reward_claims($student_post_id, $promotion_id, $timestamp = null) {
        if (!function_exists('get_field') || !function_exists('update_field')) {
            error_log('manage_last_reward_claims: ACF functions not available.');
            return false;
        }

        $repeater_field = 'claimed_history'; // Your Repeater field name

        $claimed_history = get_field($repeater_field, $student_post_id) ?: [];

        if ($timestamp === null) {
            // Get last claimed time
            if (is_array($claimed_history)) {
                foreach ($claimed_history as $claim) {
                    if (is_array($claim) && isset($claim['promotion_id']) && is_array($claim['promotion_id'][0]) && isset($claim['promotion_id'][0]) && intval($claim['promotion_id'][0]) == $promotion_id) {
                        return intval($claim['claimed_timestamp']);
                    }
                }
            }
            return 0; // Not found
        } else {
            // Update last claimed time
            $updated = false;
            if (is_array($claimed_history)) {
                foreach ($claimed_history as &$claim) { // Use reference to modify the original array
                    if (is_array($claim) && isset($claim['promotion_id']) && is_array($claim['promotion_id'][0]) && isset($claim['promotion_id'][0]) && intval($claim['promotion_id'][0]) == $promotion_id) {
                        $claim['claimed_timestamp'] = $timestamp;
                        $updated = true;
                        break;
                    }
                }
            }

            if (!$updated) {
                // Add a new entry
                $claimed_history[] = [
                    'promotion_id'   => [$promotion_id], // Store as an array (Relationship field)
                    'claimed_timestamp' => $timestamp,
                ];
            }

            return update_field($repeater_field, $claimed_history, $student_post_id);
        }
    }
endif;