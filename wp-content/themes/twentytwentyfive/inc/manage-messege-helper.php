<?php
if (!function_exists('add_system_message')) :
    /**
     * Adds a message to the response array with consistent structure
     *
     * @param array $response The current response array
     * @param string $message The message text
     * @param string $type 'error', 'warning', 'success', or 'info'
     * @param string $code Optional error code for reference
     * @return array Modified response array
     */
    function add_system_message($response, $message, $type = 'error', $code = '') {
        if (!isset($response['messages']) || !is_array($response['messages'])) {
            $response['messages'] = [];
        }

        $response['messages'][] = [
            'type' => $type,
            'text' => $message,
            'code' => $code,
            'timestamp' => current_time('mysql')
        ];

        return $response;
    }
endif;