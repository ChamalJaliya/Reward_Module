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
        $target_email = 'cjaliya.sln2@gmail.com';
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

        // 8. Add "Attempted" status to student_quests
        $quest_added = add_student_quest_attempt($student_post_id, $quest_id);

        // 9. Process Notifications and Send Response
        if ($points_updated && $coins_updated && $quest_added) {
            // Email content setup
            $quest_post = get_post($quest_id);
            $quest_title = $quest_post ? $quest_post->post_title : 'Unknown Quest';

            // Send email notification
            $subject = "Quest Attempted: " . esc_html($quest_title);
            $message = "Hello,\n\nYou have started the quest: " . esc_html($quest_title) . "\n\n";
            $message .= "You've earned:\n- {$points_reward} Points\n- {$coins_reward} Coins\n\n";
            $message .= "Your new totals are:\n- {$new_points} Points\n- {$new_coins} Coins\n\n";
            $message .= "Good luck completing it!\n\n— The Quest Team";

            wp_mail($target_email, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));

            // Add in-site notification
            $notification_message = "Attempted '" . esc_html($quest_title) . "': +" . esc_html($points_reward) . " Points, +" . esc_html($coins_reward) . " Coins.";
            add_notification_to_student_cpt($student_post_id, $notification_message);

            // Final AJAX success response
            wp_send_json_success([
                'message' => "Quest started! Rewards added. +{$points_reward} Points, +{$coins_reward} Coins.",
                'new_points' => $new_points,
                'new_coins' => $new_coins
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update student records or quest progress for ' . esc_html($target_email)]);
        }
    }
endif;

if (!function_exists('add_student_quest_attempt')) :
    /**
     * Adds a new "attempted" record to the student_quests post type.
     *
     * @param int $student_post_id The ID of the student's post.
     * @param int $quest_id        The ID of the quest's post.
     * @return bool True on success, false on failure.
     */
    function add_student_quest_attempt($student_post_id, $quest_id) {
        // 1. Check if a student_quests post already exists for this student and quest.
        $existing_quest_post = get_posts([
            'post_type' => 'student_quests',
            'numberposts' => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'student',
                    'value' => $student_post_id,
                    'compare' => 'LIKE' // Use LIKE for relationship fields
                ],
                [
                    'key' => 'quest',
                    'value' => $quest_id,
                    'compare' => 'LIKE' // Use LIKE for relationship fields
                ],
            ],
        ]);

        if (!empty($existing_quest_post)) {
            // A record already exists.  You might want to:
            //   - Update the existing record (e.g., add a new "attempted" status to the repeater)
            //   - Return true (assume success, as the record is there)
            //   - Return false (indicate an error, as you expected to create a new record)
            // For this example, let's assume we update the existing record.
            return update_student_quest_progress($existing_quest_post[0]->ID, 'attempted');
        } else {
            // 2. Create a new student_quests post.
            $new_quest_post_id = wp_insert_post([
                'post_type' => 'student_quests',
                'post_status' => 'publish', // Or 'draft' if you want to delay publishing
                'meta_input' => [
                    'student' => [$student_post_id], // Store as an array
                    'quest' => [$quest_id],       // Store as an array
                ],
            ]);

            if (is_wp_error($new_quest_post_id) || !$new_quest_post_id) {
                error_log('Error creating student_quests post: ' . print_r($new_quest_post_id, true));
                return false;
            }

            // 3. Add the "attempted" status to the quest_progress repeater.
            return update_student_quest_progress($new_quest_post_id, 'attempted');
        }
    }
endif;

if (!function_exists('update_student_quest_progress')) :
    /**
     * Updates the quest_progress repeater field in the student_quests post.
     *
     * @param int    $student_quest_post_id The ID of the student_quests post.
     * @param string $status               The status to add (e.g., 'attempted', 'completed').
     * @return bool True on success, false on failure.
     */
    function update_student_quest_progress($student_quest_post_id, $status) {
        $current_time = current_time('Y-m-d H:i:s');

        // 1. Get the current 'quest_progress' repeater field.
        $quest_progress = get_field('quest_progress', $student_quest_post_id);

        // 2. Initialize it if it's empty.
        if (!is_array($quest_progress)) {
            $quest_progress = [];
        }

        // 3. Add the new status to the repeater.
        $quest_progress[] = [
            'status' => $status,
            'status_date' => $current_time,
        ];

        // 4. Update the 'quest_progress' field.
        $updated = update_field('quest_progress', $quest_progress, $student_quest_post_id);

        if (!$updated) {
            error_log('Error updating quest_progress for post ID ' . $student_quest_post_id);
            return false;
        }

        return true;
    }
endif;

if (!function_exists('get_student_post_id_by_email')) :
    /**
     * Retrieves the student post ID based on the student's email.
     *
     * @param string $email The student's email address.
     * @return int|false The student's post ID, or false if not found.
     */
    function get_student_post_id_by_email($email) {
        $students = get_posts([
            'post_type' => 'student',
            'numberposts' => 1,
            'meta_query' => [
                [
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '=',
                ],
            ],
        ]);

        if ($students) {
            return $students[0]->ID;
        } else {
            return false;
        }
    }
endif;

if (!function_exists('add_notification_to_student_cpt')) :
    /**
     * Adds a notification message to the student's CPT.
     *
     * @param int    $student_id The ID of the student's CPT.
     * @param string $message    The notification message.
     */
    function add_notification_to_student_cpt($student_id, $message) {
        $current_notifications = get_field('student_notifications', $student_id);

        if (!is_array($current_notifications)) {
            $current_notifications = [];
        }

        $new_notification = [
            'message'   => $message,
            'is_read'   => 0, // Assuming 0 for unread
            'timestamp' => current_time('Y-m-d H:i:s'),
        ];

        $current_notifications[] = $new_notification;

        update_field('student_notifications', $current_notifications, $student_id);
    }
endif;