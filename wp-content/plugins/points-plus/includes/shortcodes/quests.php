<?php

/**
 * Defines the shortcode to display quests on the frontend.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/shortcodes
 */

namespace PointsPlus\Shortcodes;

class Quests {

    /**
     * Registers the 'points_plus_quests' shortcode.
     */
    public static function register(): void {
        add_shortcode( 'points_plus_quests', array( __CLASS__, 'display_quests' ) );
    }

    /**
     * Callback function for the 'points_plus_quests' shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output to display the quests.
     */
    public static function display_quests( $atts ): string {
        $atts = shortcode_atts(
            array(
                'numberposts' => -1, // -1 to display all quests
                'orderby'     => 'title',
                'order'       => 'ASC',
                // Add any other attributes you might need
            ),
            $atts,
            'points_plus_quests'
        );

        $args = array(
            'post_type'      => 'quest',
            'posts_per_page' => (int) $atts['numberposts'],
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        $quests = get_posts( $args );

        $output = '<div class="points-plus-quests-container">';
        if ( ! empty( $quests ) ) {
            $output .= '<ul class="points-plus-quests-list">';
            foreach ( $quests as $quest ) {
                $output .= '<li class="points-plus-quest-item">';
                $output .= '<h3 class="points-plus-quest-title">' . esc_html( get_the_title( $quest->ID ) ) . '</h3>';

                // Display ACF fields
                $quest_key = get_field( 'quest_key', $quest->ID );
                if ( $quest_key ) {
                    $output .= '<p class="points-plus-quest-key">Key: ' . esc_html( $quest_key ) . '</p>';
                }

                $description = get_field( 'description', $quest->ID );
                if ( $description ) {
                    $output .= '<p class="points-plus-quest-description">' . wp_kses_post( $description ) . '</p>';
                }

                $points_reward = get_field( 'points_reward', $quest->ID );
                if ( $points_reward ) {
                    $output .= '<p class="points-plus-quest-reward">Reward: ' . esc_html( $points_reward ) . ' points</p>';
                }

                $completion_criteria = get_field( 'completion_criteria', $quest->ID );
                if ( $completion_criteria ) {
                    $output .= '<p class="points-plus-quest-criteria">Criteria: ' . esc_html( ucfirst( $completion_criteria ) ) . '</p>';
                }

                $target_value = get_field( 'target_value', $quest->ID );
                if ( $target_value ) {
                    $output .= '<p class="points-plus-quest-target">Target: ' . esc_html( $target_value ) . '</p>';
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No quests available.</p>';
        }
        $output .= '</div>';

        return $output;
    }

}

// Register the shortcode
add_action( 'init', array( __NAMESPACE__ . '\\Quests', 'register' ) );