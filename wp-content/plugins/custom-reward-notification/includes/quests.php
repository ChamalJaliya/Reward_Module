<?php
/**
 * Quest handling functionality.
 *
 * @package CustomRewardNotifications
 */

// AJAX Handler: Play Quest.
add_action('wp_ajax_play_quest', 'crn_handle_play_quest_ajax');
add_action('wp_ajax_nopriv_play_quest', 'crn_handle_play_quest_ajax');

function crn_handle_play_quest_ajax() {
    // 1. Security check.
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'play_quest_nonce') ) {
        wp_send_json_error(['message' => 'Nonce verification failed!']);
        return;
    }

    // 2. Validate Quest ID.
    if ( ! isset($_POST['quest_id']) || ! is_numeric($_POST['quest_id']) ) {
        wp_send_json_error(['message' => 'Invalid Quest ID.']);
        return;
    }
    $quest_id = intval($_POST['quest_id']);

    // 3. Get student post ID by email.
    $target_email = 'nipunchamika11@gmail.com';
    $student_post_id = crn_get_student_post_id_by_email($target_email);
    if ( ! $student_post_id ) {
        wp_send_json_error(['message' => 'Could not find student profile for ' . $target_email]);
        return;
    }

    // 4. Get Quest Rewards.
    $points_reward = intval(get_field('points_reward', $quest_id) ?: 0);
    $coins_reward  = intval(get_field('coins_reward', $quest_id) ?: 0);

    // 5. Get current Points & Coins.
    $current_points = intval(get_field('points', $student_post_id) ?: 0);
    $current_coins  = intval(get_field('coins', $student_post_id) ?: 0);

    // 6. Calculate new totals.
    $new_points = $current_points + $points_reward;
    $new_coins  = $current_coins + $coins_reward;

    // 7. Update student's Points & Coins.
    $points_updated = update_field('points', $new_points, $student_post_id);
    $coins_updated  = update_field('coins', $new_coins, $student_post_id);

    if ( $points_updated && $coins_updated ) {

        // Optionally, send an email notification (omitted for brevity).

        // Add an in-site notification.
        crn_add_notification_to_student($student_post_id, "Completed '" . get_the_title($quest_id) . "': +{$points_reward} Points, +{$coins_reward} Coins.");

        wp_send_json_success([
            'message'    => "Rewards added! +{$points_reward} Points, +{$coins_reward} Coins.",
            'new_points' => $new_points,
            'new_coins'  => $new_coins
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update student records for ' . esc_html($target_email)]);
    }
}

// Shortcode to display quests.
function crn_display_quests_shortcode_function() {
    $output = '';
    $quests_query = new WP_Query(array(
        'post_type'      => 'quest',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ));

    if ( $quests_query->have_posts() ) {
        $output .= '<div class="quests-list">';
        while ( $quests_query->have_posts() ) {
            $quests_query->the_post();
            $quest_id = get_the_ID();
            $points_reward = get_field('points_reward', $quest_id) ?: 0;
            $coins_reward  = get_field('coins_reward', $quest_id) ?: 0;
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
add_shortcode('display_quests', 'crn_display_quests_shortcode_function');

/**
 * Helper function to get a student post ID by email.
 */
function crn_get_student_post_id_by_email($email) {
    if ( empty($email) ) {
        return false;
    }
    $args = array(
        'post_type'              => 'student',
        'posts_per_page'         => 1,
        'meta_query'             => array(
            array(
                'key'     => 'email',
                'value'   => $email,
                'compare' => '=',
            ),
        ),
        'fields'                 => 'ids',
        'post_status'            => 'publish',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );
    $student_query = new WP_Query($args);
    return $student_query->have_posts() ? $student_query->posts[0] : false;
}
