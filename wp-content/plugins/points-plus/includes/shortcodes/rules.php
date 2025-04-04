<?php

/**
 * Defines a shortcode to display rule information (USE WITH CAUTION!).
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/shortcodes
 *
 * **WARNING: Displaying raw rule data on the front-end can expose sensitive information and is generally NOT recommended.**
 */

namespace PointsPlus\Shortcodes;

class Rules {

    /**
     * Registers the 'points_plus_rules' shortcode.
     */
    public static function register(): void {
        add_shortcode( 'points_plus_rules', array( __CLASS__, 'display_rules' ) );
    }

    /**
     * Callback function for the 'points_plus_rules' shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output to display the rules.
     */
    public static function display_rules( $atts ): string {
        $atts = shortcode_atts(
            array(
                'numberposts' => -1, // -1 to display all rules
                'orderby'     => 'title',
                'order'       => 'ASC',
                // Add any other attributes you might need
            ),
            $atts,
            'points_plus_rules'
        );

        $args = array(
            'post_type'      => 'rule',
            'posts_per_page' => (int) $atts['numberposts'],
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        $rules = get_posts( $args );

        $output = '<div class="points-plus-rules-container">';
        if ( ! empty( $rules ) ) {
            $output .= '<ul class="points-plus-rules-list">';
            foreach ( $rules as $rule ) {
                $output .= '<li class="points-plus-rule-item">';
                $output .= '<h3 class="points-plus-rule-title">' . esc_html( get_the_title( $rule->ID ) ) . '</h3>';

                // Display ACF fields (WARNING: Be very careful what you display!)
                $status = get_field( 'status', $rule->ID );
                if ( $status ) {
                    $output .= '<p class="points-plus-rule-status">Status: ' . esc_html( ucfirst( $status ) ) . '</p>';
                }

                $trigger_event = get_field( 'trigger_event', $rule->ID );
                if ( $trigger_event ) {
                    $output .= '<p class="points-plus-rule-event">Trigger Event: ' . esc_html( ucfirst( str_replace( '_', ' ', $trigger_event ) ) ) . '</p>';
                }

                // Displaying conditions and reward logic is generally a bad idea!
                // It could expose sensitive information or internal logic.
                // Only do this if you have a VERY specific and safe use case.
                /*
                $conditions = get_field( 'conditions', $rule->ID );
                if ( $conditions ) {
                    $output .= '<p class="points-plus-rule-conditions">Conditions: ' . esc_html( $conditions ) . '</p>'; // VERY DANGEROUS!
                }

                $reward_logic = get_field( 'reward_logic', $rule->ID );
                if ( $reward_logic ) {
                    $output .= '<p class="points-plus-rule-logic">Reward Logic: ' . esc_html( $reward_logic ) . '</p>'; // VERY DANGEROUS!
                }
                */

                $priority = get_field( 'priority', $rule->ID );
                if ( $priority ) {
                    $output .= '<p class="points-plus-rule-priority">Priority: ' . esc_html( $priority ) . '</p>';
                }

                $description = get_field( 'description', $rule->ID );
                if ( $description ) {
                    $output .= '<p class="points-plus-rule-description">' . wp_kses_post( $description ) . '</p>';
                }

                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No rules defined.</p>';
        }
        $output .= '</div>';

        return $output;
    }

}

// Register the shortcode
add_action( 'init', array( __NAMESPACE__ . '\\Rules', 'register' ) );