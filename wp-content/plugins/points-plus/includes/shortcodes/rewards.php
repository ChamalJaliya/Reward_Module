<?php

/**
 * Defines the shortcode to display rewards on the frontend.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/shortcodes
 */

namespace PointsPlus\Shortcodes;

class Rewards {

    /**
     * Registers the 'points_plus_rewards' shortcode.
     */
    public static function register(): void {
        add_shortcode( 'points_plus_rewards', array( __CLASS__, 'display_rewards' ) );
    }

    /**
     * Callback function for the 'points_plus_rewards' shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output to display the rewards.
     */
    public static function display_rewards( $atts ): string {
        $atts = shortcode_atts(
            array(
                'numberposts' => -1, // -1 to display all rewards
                'orderby'     => 'title',
                'order'       => 'ASC',
                // Add any other attributes you might need
            ),
            $atts,
            'points_plus_rewards'
        );

        $args = array(
            'post_type'      => 'reward-item',
            'posts_per_page' => (int) $atts['numberposts'],
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        $rewards = get_posts( $args );

        $output = '<div class="points-plus-rewards-container">';
        if ( ! empty( $rewards ) ) {
            $output .= '<ul class="points-plus-rewards-list">';
            foreach ( $rewards as $reward ) {
                $output .= '<li class="points-plus-reward-item">';
                $output .= '<h3 class="points-plus-reward-title">' . esc_html( get_the_title( $reward->ID ) ) . '</h3>';

                // Display ACF fields
                $promotion_type = get_field( 'promotion_type', $reward->ID );
                if ( $promotion_type ) {
                    $output .= '<p class="points-plus-reward-type">Type: ' . esc_html( ucfirst( $promotion_type ) ) . '</p>';
                }

                $description = get_field( 'description', $reward->ID );
                if ( $description ) {
                    $output .= '<p class="points-plus-reward-description">' . wp_kses_post( $description ) . '</p>';
                }

                $valid_from = get_field( 'valid_from', $reward->ID );
                if ( $valid_from ) {
                    $output .= '<p class="points-plus-reward-valid-from">Valid From: ' . date( 'Y-m-d H:i', strtotime( $valid_from ) ) . '</p>';
                }

                $valid_until = get_field( 'valid_until', $reward->ID );
                if ( $valid_until ) {
                    $output .= '<p class="points-plus-reward-valid-until">Valid Until: ' . date( 'Y-m-d H:i', strtotime( $valid_until ) ) . '</p>';
                }

                $required_coins = get_field( 'required_coins', $reward->ID );
                if ( $required_coins ) {
                    $output .= '<p class="points-plus-reward-coins">Required Coins: ' . esc_html( $required_coins ) . '</p>';
                }

                $reload_value = get_field( 'reload_value', $reward->ID );
                if ($reload_value) {
                    $output .= '<p class="points-plus-reward-reload-value">Reload Value: ' . esc_html($reload_value) . '</p>';
                }

                $multiplication_type = get_field( 'multiplication_type', $reward->ID );
                if ($multiplication_type) {
                    $output .= '<p class="points-plus-reward-multiplication-type">Multiplication Type: ' . esc_html($multiplication_type) . '</p>';
                }

                $multiplication_factor = get_field( 'multiplication_factor', $reward->ID );
                if ($multiplication_factor) {
                    $output .= '<p class="points-plus-reward-multiplication-factor">Multiplication Factor: ' . esc_html($multiplication_factor) . '</p>';
                }

                // Add a button or link to redeem the reward (if applicable)
                $output .= '<a href="#" class="points-plus-reward-redeem" data-reward-id="' . esc_attr( $reward->ID ) . '">Redeem</a>';

                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No rewards available.</p>';
        }
        $output .= '</div>';

        return $output;
    }

}

// Register the shortcode
add_action( 'init', array( __NAMESPACE__ . '\\Rewards', 'register' ) );