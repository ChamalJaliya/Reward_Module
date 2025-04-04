<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Points_Plus
 * @subpackage Points_Plus/includes
 * @author     Chamal <cjaliya.sln2@gmail.com>
 */
class Points_Plus_Install {

    /**
     * Create necessary database tables.
     *
     * @since    1.0.0
     */
    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $rules_table_name = $wpdb->prefix . 'points_plus_rules';
        $sql_rules = "CREATE TABLE IF NOT EXISTS $rules_table_name (
               rule_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               name VARCHAR(255) NOT NULL,
               status VARCHAR(10) NOT NULL DEFAULT 'inactive',
               trigger_event VARCHAR(255) NOT NULL,
               conditions LONGTEXT,
               reward_logic LONGTEXT,
               time_constraints LONGTEXT,
               priority INT DEFAULT 0,
               description TEXT,
               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
           ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_rules );

        // You can add other installation tasks here if needed.
    }
}