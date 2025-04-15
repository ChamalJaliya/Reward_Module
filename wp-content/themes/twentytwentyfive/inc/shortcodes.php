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
        $target_email = 'nipunchamika11@gmail.com';
        $student_post_id = get_student_post_id_by_email($target_email);

        $points = 0;
        $coins = 0;
        $unread_count = 0;
        $rewards_output = '';

        if ($student_post_id && function_exists('get_field')) {
            // Get Points & Coins
            $points = get_field('points', $student_post_id) ?: 0;
            $coins = get_field('coins', $student_post_id) ?: 0;

            // Get Notifications and count unread
            $notifications = get_field('student_notifications', $student_post_id);
            if (is_array($notifications)) {
                foreach ($notifications as $note) {
                    if (isset($note['is_read']) && !$note['is_read']) {
                        $unread_count++;
                    }
                }
            }

            // Fetch Reward Items
            $reward_items = get_posts(array(
                'post_type' => 'reward-item',
                'posts_per_page' => -1,
            ));

            if ($reward_items) {
                $rewards_output .= '<div class="rewards-dropdown" style="display: none;">';
                $rewards_output .= '<ul>';

                foreach ($reward_items as $post) {
                    $required_coins = get_field('required_coins', $post->ID) ?: 0;

                    if ($coins >= $required_coins) {
                        $rewards_output .= '<li class="reward-item">';
                        $rewards_output .= '<span class="reward-name">' . esc_html(get_the_title($post->ID)) . '</span><br>';

                        $client_description = get_field('client_description', $post->ID);
                        if ($client_description) {
                            $rewards_output .= '<span class="reward-client-description">' . wp_kses_post($client_description) . '</span><br>';
                        }

                        if ($required_coins > 0) {
                            $rewards_output .= '<span class="reward-cost">Requires: ' . esc_html($required_coins) . ' Coins</span><br>';
                        }

                        $rewards_output .= '<button class="redeem-button" data-reward-id="' . esc_attr($post->ID) . '">Redeem</button>';
                        $rewards_output .= '</li>';
                    }
                }

                $rewards_output .= '</ul>';
                $rewards_output .= '</div>';
            }
        }

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

        return $output;
    }
endif;