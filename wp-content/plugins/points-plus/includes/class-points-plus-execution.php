<?php

/**
 * Handles the reward execution logic for the Reward Module.
 *
 * @package Points_Plus
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

    private function __construct() {
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