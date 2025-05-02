<?php
/**
 * Student Data Handler for Points Plus plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Points_Plus_Student_Data {

    /**
     * Get current student data with fallback options
     *
     * @return array|false Student data array or false if not available
     */
    public static function get_current_student() {
        // First try to use theme's function
        if (function_exists('ms_get_current_student_data')) {
            $student_data = ms_get_current_student_data();
            if ($student_data) {
                return $student_data;
            }
        }

        // Fallback to basic WordPress user data
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user = get_userdata($user_id);

            return [
                'id' => $user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'points' => (int) get_user_meta($user_id, 'points', true),
                'coins' => (int) get_user_meta($user_id, 'coins', true)
            ];
        }

        return false;
    }

    /**
     * Get current student ID with fallback
     *
     * @return int|false Student ID or false if not available
     */
    public static function get_current_student_id() {
        $student = self::get_current_student();
        return $student ? $student['id'] : false;
    }

    /**
     * Get specific student field with fallback
     *
     * @param string $field Field to retrieve
     * @return mixed Field value or false if not found
     */
    public static function get_student_field($field) {
        $student = self::get_current_student();
        return $student[$field] ?? false;
    }
}