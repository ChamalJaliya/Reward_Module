<?php
/**
 * Daily Reward System with Nonce Debugging
 */

if (!defined('ABSPATH')) exit;

class Debug_Daily_Reward_System {
    const NONCE_ACTION = 'debug_daily_reward_action';

    public function __construct() {
        add_action('wp_ajax_claim_daily_reward', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_claim_daily_reward', [$this, 'handle_ajax_request']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Log initialization
        $this->log('Debug Daily Reward System initialized');
    }

    public function enqueue_scripts() {
        $this->log('Enqueueing scripts');

        wp_enqueue_script(
            'debug-reward-handler',
            get_template_directory_uri() . '/js/debug-reward-handler.js',
            ['jquery'],
            filemtime(get_template_directory() . '/js/debug-reward-handler.js'),
            true
        );

        $nonce = wp_create_nonce(self::NONCE_ACTION);
        $this->log("Generated nonce for frontend: {$nonce}");

        wp_localize_script('debug-reward-handler', 'debugRewardData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $nonce,
            'user_id' => get_current_user_id(),
            'debug' => WP_DEBUG // Pass debug mode to frontend
        ]);
    }

    public function handle_ajax_request() {
        $this->log("\n=== New AJAX Request ===");
        $this->log("Request method: {$_SERVER['REQUEST_METHOD']}");
        $this->log("POST data: " . print_r($_POST, true));
        $this->log("GET data: " . print_r($_GET, true));

        try {
            $this->verify_nonce();
            $result = $this->process_daily_reward();
            wp_send_json_success($result);

        } catch (Exception $e) {
            $this->log("Error: {$e->getMessage()} (Code: {$e->getCode()})");
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'debug' => [
                    'received_nonce' => $_POST['nonce'] ?? $_GET['nonce'] ?? 'none',
                    'expected_action' => self::NONCE_ACTION,
                    'user_id' => get_current_user_id(),
                    'timestamp' => current_time('mysql')
                ]
            ]);
        }
    }

    private function verify_nonce() {
        $received_nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        $this->log("Received nonce: {$received_nonce}");

        $expected_nonce = wp_create_nonce(self::NONCE_ACTION);
        $this->log("Expected nonce for this user/action: {$expected_nonce}");

        $verification_result = wp_verify_nonce($received_nonce, self::NONCE_ACTION);
        $this->log("Nonce verification result: " . ($verification_result ? 'VALID' : 'INVALID'));

        if (!$verification_result) {
            $this->log_noncedata_debug();
            throw new Exception('Nonce verification failed', 403);
        }
    }

    private function log_noncedata_debug() {
        $user_id = get_current_user_id();
        $this->log("Nonce debug for user {$user_id}:");

        // Check user token
        $user_token = get_user_meta($user_id, 'session_tokens', true);
        $this->log("User session tokens: " . print_r($user_token, true));

        // Check nonce life
        $this->log("Nonce life: " . wp_nonce_tick());

        // Check if nonce was ever valid
        $this->log("Nonce age check:");
        for ($i = 0; $i < 2; $i++) {
            $tick = wp_nonce_tick() - $i;
            $expected = substr(wp_hash($tick . '|' . self::NONCE_ACTION . '|' . $user_id, 'nonce'), -12, 10);
            $this->log("Tick {$i}: {$expected}");
        }
    }

    private function process_daily_reward() {
        $this->log("Processing daily reward");

        if (!isset($_POST['user_id'])) {
            throw new Exception('User ID required', 400);
        }

        return [
            'message' => 'Reward claimed successfully!',
            'points' => 100,
            'coins' => 50,
            'debug' => [
                'nonce_action' => self::NONCE_ACTION,
                'verification_time' => current_time('mysql')
            ]
        ];
    }

    private function log($message) {
        error_log("[DailyRewardDebug] {$message}");
    }
}

new Debug_Daily_Reward_System();