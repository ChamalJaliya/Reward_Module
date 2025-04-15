<?php
/**
 * Reward system functionality
 */

// AJAX handler for redeeming rewards
add_action('wp_ajax_redeem_reward', 'handle_redeem_reward_ajax');
add_action('wp_ajax_nopriv_redeem_reward', 'handle_redeem_reward_ajax');

if (!function_exists('handle_redeem_reward_ajax')) :
    function handle_redeem_reward_ajax() {
        // Security check
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Nonce verification failed!']);
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'redeem_reward_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed!']);
            return;
        }

        // Get reward ID and student identifier
        $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 0;
        $student_identifier = isset($_POST['student_identifier']) ? sanitize_email($_POST['student_identifier']) : 'nipunchamika11@gmail.com';

        // Find student
        $student_post_id = get_student_post_id_by_email($student_identifier);

        if (!$student_post_id) {
            wp_send_json_error(['message' => 'Could not find student profile.']);
            return;
        }

        // Get reward details
        $required_coins = get_field('required_coins', $reward_id) ?: 0;
        $promotion_type = get_field('promotion_type', $reward_id) ?: '';
        $additional_type = get_field('additional_type', $reward_id) ?: '';
        $additional_reward = get_field('additional_reward', $reward_id) ?: 0;

        // Get student's current coins
        $current_coins = get_field('coins', $student_post_id) ?: 0;

        if ($current_coins < $required_coins) {
            wp_send_json_error(['message' => 'Not enough coins to redeem this reward.']);
            return;
        }

        // Process reward redemption
        $new_coins = $current_coins - $required_coins;
        $coins_updated = update_field('coins', $new_coins, $student_post_id);

        // Apply reward benefits if coins were successfully deducted
        if ($coins_updated) {
            // Handle different reward types
            if ($promotion_type === 'addition' && $additional_reward > 0) {
                $current_points = get_field('points', $student_post_id) ?: 0;

                if ($additional_type === 'Points' || $additional_type === 'Both') {
                    $new_points = $current_points + $additional_reward;
                    update_field('points', $new_points, $student_post_id);
                }

                if ($additional_type === 'Coins' || $additional_type === 'Both') {
                    $new_coins += $additional_reward;
                    update_field('coins', $new_coins, $student_post_id);
                }
            }

            // Add notification
            $reward_title = get_the_title($reward_id);
            $notification_message = "Redeemed reward: {$reward_title}";
            add_notification_to_student_cpt($student_post_id, $notification_message);

            wp_send_json_success([
                'message' => 'Reward redeemed successfully!',
                'new_coins' => $new_coins
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update student coins.']);
        }
    }
endif;