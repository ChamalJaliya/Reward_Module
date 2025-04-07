<?php

/**
 * Handles the REST API endpoint for the Reward Module.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Points_Plus_API {

    private static $instance;

    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    public function __construct() { // Changed from private to public
        // Plugin is being loaded.
    }

    private function init() {
        // Register REST API routes here
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registers the REST API routes for the plugin.
     */
    public function register_routes() {
        $namespace = 'points-plus/v1';

        register_rest_route( $namespace, '/evaluate-rules', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'evaluate_rules_callback' ),
            'permission_callback' => '__return_true', // Adjust permission callback as needed
            'args'                => array(
                'trigger_event' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'description'       => 'The event that triggered the rule evaluation.',
                ),
                'user_id'       => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'description'       => 'The ID of the user.',
                ),
                'event_data'    => array(
                    'required'          => false,
                    'type'              => 'object',
                    'description'       => 'Optional data associated with the event.',
                ),
            ),
        ) );
    }

    /**
     * Callback function for the /evaluate-rules endpoint.
     *
     * @param WP_REST_Request $request The REST API request.
     * @return WP_REST_Response
     */
    public function evaluate_rules_callback( $request ) {
        $trigger_event = $request->get_param( 'trigger_event' );
        $user_id       = $request->get_param( 'user_id' );
        $event_data    = $request->get_param( 'event_data' );

        $response_data = array(
            'success'         => false,
            'rewards_granted' => array(),
            'notifications'   => array(),
        );

        // 1. Retrieve Rules
        $rules = Points_Plus::instance()->get_rule_engine()->get_matching_rules( $trigger_event );

        if ( ! empty( $rules ) ) {
            // 2. Evaluate Rules and Grant Rewards
            $response_data = Points_Plus::instance()->get_rule_engine()->evaluate_and_execute_rules( $user_id, $rules, $event_data );
        } else {
            $response_data['notifications'][] = 'No matching rules found for this event.';
        }

        return rest_ensure_response( $response_data );
    }

    /**
     * Sets the column headers for the Reward Item list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function set_reward_columns( $columns ) {
        $new_columns = array();
        $new_columns['title'] = __( 'Promotion Name', 'points-plus' );
        $new_columns['promotion_type'] = __( 'Type', 'points-plus' );
        $new_columns['valid_from'] = __( 'Valid From', 'points-plus' );
        $new_columns['valid_until'] = __( 'Valid Until', 'points-plus' );
        $new_columns['required_coins'] = __( 'Required Coins', 'points-plus' );
        $new_columns['reload_value'] = __( 'Reload Value', 'points-plus' );
        $new_columns['description'] = __( 'Description', 'points-plus' );
        $new_columns['date'] = __( 'Date', 'points-plus' );
        return $new_columns;
    }

    /**
     * Populates the custom columns for the Reward Item list table.
     *
     * @param string $column  The name of the column.
     * @param int    $post_id The ID of the current post.
     */
    public function populate_reward_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'promotion_type':
                $promotion_type = get_field( 'promotion_type', $post_id );
                echo $promotion_type ? ucfirst( $promotion_type ) : '-';
                break;
            case 'valid_from':
                $valid_from = get_field( 'valid_from', $post_id );
                echo $valid_from ? date( 'Y-m-d H:i', strtotime( $valid_from ) ) : '-';
                break;
            case 'valid_until':
                $valid_until = get_field( 'valid_until', $post_id );
                echo $valid_until ? date( 'Y-m-d H:i', strtotime( $valid_until ) ) : '-';
                break;
            case 'required_coins':
                $required_coins = get_field( 'required_coins', $post_id );
                echo $required_coins ? $required_coins : '-';
                break;
            case 'reload_value':
                $reload_value = get_field( 'reload_value', $post_id );
                echo $reload_value ? $reload_value : '-';
                break;
            case 'description':
                $description = get_field( 'description', $post_id );
                echo $description ? wp_trim_words( $description, 20, '...' ) : '-';
                break;
        }
    }
}