<?php

/**
 * Handles the rule engine logic for the Reward Module.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Points_Plus_Rule_Engine {

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
        // Initialize any resources or setup needed for the rule engine.
    }

    /**
     * Retrieves rules from the database that match the given trigger event.
     *
     * @param string $trigger_event The event that triggered the rule evaluation.
     * @return array An array of rules that match the trigger event.
     */
    public function get_matching_rules( string $trigger_event ): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'points_plus_rules';

        $query   = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = 'active' AND trigger_event = %s ORDER BY priority ASC",
            $trigger_event
        );
        $rules = $wpdb->get_results( $query, ARRAY_A );

        return $rules ? $rules : array();
    }

    /**
     * Evaluates the given rules against the user and event data and executes the rewards.
     *
     * @param int   $user_id The ID of the user.
     * @param array $rules   An array of rules to evaluate.
     * @param array $event_data Optional data associated with the event.
     * @return array An array containing information about rewards granted and notifications.
     */
    public function evaluate_and_execute_rules( int $user_id, array $rules, array $event_data = array() ): array {
        $response_data = array(
            'success'         => false,
            'rewards_granted' => array(),
            'notifications'   => array(),
        );

        foreach ( $rules as $rule ) {
            if ( $this->evaluate_rule_conditions( $user_id, $rule['conditions'], $event_data ) ) {
                $this->execute_reward_logic( $user_id, $rule['reward_logic'], $response_data );
                $response_data['success'] = true; // At least one rule was successful
            }
        }

        return $response_data;
    }

    /**
     * Evaluates the conditions of a single rule.
     *
     * @param int   $user_id The ID of the user.
     * @param string $conditions JSON-encoded string of rule conditions.
     * @param array $event_data Optional data associated with the event.
     * @return bool True if the conditions are met, false otherwise.
     */
    private function evaluate_rule_conditions( int $user_id, string $conditions, array $event_data = array() ): bool {
        if ( empty( $conditions ) ) {
            return true; // No conditions = always true
        }

        $conditions_array = json_decode( $conditions, true );

        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $conditions_array ) ) {
            error_log( 'Invalid JSON for rule conditions: ' . $conditions );
            return false; // Invalid JSON
        }

        // Implement your condition evaluation logic here!
        // This is a simplified placeholder. You'll need to expand it to handle
        // different condition types, operators, etc.
        foreach ( $conditions_array as $condition_group ) {
            $group_met = true;
            foreach ( $condition_group as $condition ) {
                $field    = $condition['field'] ?? '';
                $operator = $condition['operator'] ?? '';
                $value    = $condition['value'] ?? '';

                // Example: Simple check for user meta
                if ( strpos( $field, 'user_meta.' ) === 0 ) {
                    $meta_key       = str_replace( 'user_meta.', '', $field );
                    $user_meta_value = get_user_meta( $user_id, $meta_key, true ) ?: ''; // Get meta or empty string

                    switch ( $operator ) {
                        case '==':
                            if ( $user_meta_value != $value ) {
                                $group_met = false;
                            }
                            break;
                        case '>=':
                            if ( $user_meta_value < $value ) {
                                $group_met = false;
                            }
                            break;
                        // Add more operators as needed
                        default:
                            error_log( 'Unsupported operator: ' . $operator );
                            $group_met = false;
                    }
                } elseif ( $field === 'day_of_week' ) {
                    $current_day = date( 'l' ); // e.g., "Monday"
                    if ( $operator === '==' && $current_day != $value ) {
                        $group_met = false;
                    }
                } else {
                    // Add logic to handle other field types (event data, etc.)
                    error_log( 'Unsupported field type: ' . $field );
                    $group_met = false;
                }

                if ( ! $group_met ) {
                    break; // No need to check further in this group
                }
            }

            if ( $group_met ) {
                return true; // At least one group is met
            }

            if ( $condition_group['condition_logic'] === 'OR' && ! $group_met ) {
                return false;
            }
        }

        return false; // No group met
    }

    /**
     * Executes the reward logic of a single rule.
     *
     * @param int   $user_id The ID of the user.
     * @param string $reward_logic JSON-encoded string of reward logic.
     * @param array $response_data (Reference) Array to store reward information and notifications.
     */
    private function execute_reward_logic( int $user_id, string $reward_logic, array &$response_data ): void {
        if ( empty( $reward_logic ) ) {
            return; // No reward logic
        }

        $reward_logic_array = json_decode( $reward_logic, true );

        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $reward_logic_array ) ) {
            error_log( 'Invalid JSON for reward logic: ' . $reward_logic );
            return; // Invalid JSON
        }

        // Implement your reward execution logic here!
        // This is a simplified placeholder. You'll need to expand it to handle
        // different reward types (coins, stars, promotions, etc.).
        foreach ( $reward_logic_array as $reward ) {
            $type   = $reward['type'] ?? '';
            $params = $reward['parameters'] ?? array();

            switch ( $type ) {
                case 'grant_coins':
                    $amount = $params['amount'] ?? 0;
                    $reason = $params['reason'] ?? '';
                    Points_Plus::instance()->get_reward_execution()->grant_coins( $user_id, $amount, $reason, $response_data );
                    break;
                case 'multiply_coins':
                    $factor = $params['factor'] ?? 1;
                    Points_Plus::instance()->get_reward_execution()->multiply_coins( $user_id, $factor, $response_data );
                    break;
                case 'apply_promotion':
                    $promotion_id = $params['promotion_id'] ?? 0;
                    Points_Plus::instance()->get_reward_execution()->apply_promotion( $user_id, $promotion_id, $response_data );
                    break;
                case 'custom_function':
                    $function_name = $params['function_name'] ?? '';
                    $function_params = $params['function_params'] ?? '';
                    Points_Plus::instance()->get_reward_execution()->execute_custom_function( $user_id, $function_name, $function_params, $response_data );
                    break;
                // Add more reward types as needed
                default:
                    error_log( 'Unsupported reward type: ' . $type );
            }
        }
    }

    /**
     * Evaluates rules when a user logs in.
     *
     * @param string   $user_login The user's login name.
     * @param WP_User $user       The WP_User object for the logged-in user.
     */
    public function evaluate_rules_on_login( $user_login, $user ) {
        $user_id = $user->ID;
        $this->evaluate_and_execute_rules( $user_id, $this->get_matching_rules( 'user_login' ), array(
            'user_login' => $user_login,
        ) );
    }

    /**
     * Evaluates rules when a user registers.
     *
     * @param int $user_id The ID of the registered user.
     */
    public function evaluate_rules_on_user_register( $user_id ) {
        $this->evaluate_and_execute_rules( $user_id, $this->get_matching_rules( 'user_registers' ) );
    }

    /**
     * Evaluates rules when a post is published.
     *
     * @param int $post_id The ID of the published post.
     */
    public function evaluate_rules_on_post_published( $post_id ) {
        $this->evaluate_and_execute_rules( get_current_user_id(), $this->get_matching_rules( 'post_published' ), array(
            'post_id' => $post_id,
        ) );
    }

    /**
     * Evaluates rules when a quest is completed.
     *
     * @param int    $user_id   The ID of the user who completed the quest.
     * @param string $quest_key The unique key of the completed quest.
     */
    public function evaluate_rules_on_quest_completed( $user_id, $quest_key ) {
        $this->evaluate_and_execute_rules( $user_id, $this->get_matching_rules( 'points_plus_quest_completed' ), array(
            'quest_key' => $quest_key,
        ) );
    }
}