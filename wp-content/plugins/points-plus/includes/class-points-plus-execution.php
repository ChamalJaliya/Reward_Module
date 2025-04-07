<?php

/**
 * Handles the reward execution logic for the Reward Module.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Points_Plus_Execution {

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
        // Initialize any resources or setup needed for reward execution.
    }

    /**
     * Grants coins to a user.
     *
     * @param int   $user_id The ID of the user.
     * @param int   $amount The amount of coins to grant.
     * @param string $reason The reason for granting the coins.
     * @param array $response_data (Reference) Array to store reward information and notifications.
     */
    public function grant_coins( int $user_id, int $amount, string $reason, array &$response_data ): void {
        $current_balance = get_user_meta( $user_id, 'coin_balance', true ) ?: 0;
        $new_balance     = $current_balance + $amount;
        update_user_meta( $user_id, 'coin_balance', $new_balance );
        $this->log_coin_transaction( $user_id, 'credit', $amount, $reason );

        $response_data['rewards_granted'][] = array(
            'type'   => 'coins',
            'amount' => $amount,
            'reason' => $reason,
        );
        $response_data['notifications'][]   = "You have been granted {$amount} coins. Reason: {$reason}";

        // Trigger a notification (if needed - consider using a separate Notification Module)
        // notification_module_instance()->send_in_app_notification( $user_id, "You have been granted {$amount} coins. Reason: {$reason}" );
    }

    /**
     * Multiplies a user's coin balance.
     *
     * @param int   $user_id The ID of the user.
     * @param float $factor The multiplication factor.
     * @param array $response_data (Reference) Array to store reward information and notifications.
     */
    public function multiply_coins( int $user_id, float $factor, array &$response_data ): void {
        $current_balance = get_user_meta( $user_id, 'coin_balance', true ) ?: 0;
        $amount          = (int) ($current_balance * ($factor - 1)); // Calculate the increase
        $new_balance     = (int) ($current_balance * $factor);
        update_user_meta( $user_id, 'coin_balance', $new_balance );
        $this->log_coin_transaction( $user_id, 'credit', $amount, "Coins multiplied by {$factor}" );

        $response_data['rewards_granted'][] = array(
            'type'   => 'coins',
            'amount' => $amount,
            'reason' => "Coins multiplied by {$factor}",
        );
        $response_data['notifications'][]   = "Your coins have been multiplied by {$factor}.";
    }

    /**
     * Applies a promotion to a user.
     *
     * @param int   $user_id The ID of the user.
     * @param int   $promotion_id The ID of the promotion to apply.
     * @param array $response_data (Reference) Array to store reward information and notifications.
     */
    public function apply_promotion( int $user_id, int $promotion_id, array &$response_data ): void {
        // Implement the logic to apply the promotion here.
        // This will likely involve:
        // 1. Retrieving promotion details from the database (using $promotion_id).
        // 2. Checking if the user is eligible for the promotion.
        // 3. Granting the reward associated with the promotion.
        // 4. Updating user data and logging the promotion application.

        // For now, this is a placeholder:
        $response_data['rewards_granted'][] = array(
            'type'   => 'promotion',
            'promotion_id' => $promotion_id,
            'reason' => "Promotion applied.",
        );
        $response_data['notifications'][]   = "Promotion applied successfully!";
    }

    /**
     * Executes a custom function.
     *
     * @param int   $user_id The ID of the user.
     * @param string $function_name The name of the function to execute.
     * @param string $function_params JSON-encoded string of parameters to pass to the function.
     * @param array $response_data (Reference) Array to store reward information and notifications.
     */
    public function execute_custom_function( int $user_id, string $function_name, string $function_params, array &$response_data ): void {
        // Implement the logic to execute a custom function here.
        // **SECURITY WARNING:** This is a potentially dangerous feature, as it allows
        // arbitrary code execution. You MUST implement robust security measures
        // to prevent malicious code from being executed. Consider:
        // - Validating the function name against a whitelist.
        // - Sanitizing and validating the function parameters.
        // - Using a sandboxing environment to restrict the function's access to the system.

        // For now, this is a placeholder:
        $response_data['rewards_granted'][] = array(
            'type'   => 'custom_function',
            'function_name' => $function_name,
            'function_params' => $function_params,
            'reason' => "Custom function executed.",
        );
        $response_data['notifications'][]   = "Custom function executed!";
    }

    /**
     * Logs a coin transaction.
     *
     * @param int    $user_id The ID of the user.
     * @param string $type The type of transaction ('credit' or 'debit').
     * @param int    $amount The amount of coins involved.
     * @param string $reason The reason for the transaction.
     */
    private function log_coin_transaction( int $user_id, string $type, int $amount, string $reason = '' ): void {
        $transaction = array(
            'timestamp' => current_time( 'mysql' ),
            'type'      => $type,
            'amount'    => $amount,
            'reason'    => $reason,
        );
        $transactions   = get_user_meta( $user_id, 'coin_transactions', true ) ?: array();
        $transactions[] = $transaction;
        update_user_meta( $user_id, 'coin_transactions', $transactions );
    }

    // Add other reward granting functions here (e.g., grant_stars, apply_promotion)
}