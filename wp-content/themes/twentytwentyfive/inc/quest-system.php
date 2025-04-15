<?php
/**
 * Quest system functionality
 */

// AJAX handler for playing quests
add_action('wp_ajax_play_quest', 'handle_play_quest_ajax');
add_action('wp_ajax_nopriv_play_quest', 'handle_play_quest_ajax');

if (!function_exists('handle_play_quest_ajax')) :
    function handle_play_quest_ajax() {
        // 1. Security Check
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'Nonce verification failed!']);
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'play_quest_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed!']);
            return;
        }

        // 2. Get Quest ID
        if (!isset($_POST['quest_id']) || !is_numeric($_POST['quest_id'])) {
            wp_send_json_error(['message' => 'Invalid Quest ID.']);
            return;
        }

        $quest_id = intval($_POST['quest_id']);

        // 3. Find the Hardcoded Student Post ID by Email
        $target_email = 'nipunchamika11@gmail.com';
        $student_post_id = get_student_post_id_by_email($target_email);

        if (!$student_post_id) {
            wp_send_json_error(['message' => 'Could not find student profile for ' . $target_email]);
            return;
        }

        // 4. Get Quest Rewards
        $points_reward = get_field('points_reward', $quest_id) ?: 0;
        $coins_reward = get_field('coins_reward', $quest_id) ?: 0;

        // 5. Get Target Student's Current Points/Coins
        $current_points = get_field('points', $student_post_id) ?: 0;
        $current_coins = get_field('coins', $student_post_id) ?: 0;

        // 6. Calculate New Totals
        $new_points = $current_points + $points_reward;
        $new_coins = $current_coins + $coins_reward;

        // 7. Update Target Student's Points/Coins
        $points_updated = update_field('points', $new_points, $student_post_id);
        $coins_updated = update_field('coins', $new_coins, $student_post_id);

        // 8. Process Notifications and Send Response
        if ($points_updated && $coins_updated) {
            // Email content setup
            $quest_post = get_post($quest_id);
            $quest_title = $quest_post ? $quest_post->post_title : 'Unknown Quest';

            // Send email notification
            $subject = "Quest Completed: " . esc_html($quest_title);
            $message = "Hello,\n\nYou have successfully completed the quest: " . esc_html($quest_title) . "\n\n";
            $message .= "You've earned:\n- {$points_reward} Points\n- {$coins_reward} Coins\n\n";
            $message .= "Your new totals are:\n- {$new_points} Points\n- {$new_coins} Coins\n\n";
            $message .= "Keep up the good work!\n\n— The Quest Team";

            wp_mail($target_email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));

            // Add in-site notification
            $notification_message = "Completed '" . esc_html($quest_title) . "': +" . esc_html($points_reward) . " Points, +" . esc_html($coins_reward) . " Coins.";
            add_notification_to_student_cpt($student_post_id, $notification_message);

            // Final AJAX success response
            wp_send_json_success([
                'message' => "Rewards added! +{$points_reward} Points, +{$coins_reward} Coins.",
                'new_points' => $new_points,
                'new_coins' => $new_coins
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update student records for ' . esc_html($target_email)]);
        }
    }
endif;