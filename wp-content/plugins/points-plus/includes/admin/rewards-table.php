<?php

/**
 * Customizes the admin list table for the Reward Item post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class Rewards_Table {

    /**
     * Sets the column headers for the Reward Item list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_reward_columns( $columns ) {
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
    public static function populate_reward_columns( $column, $post_id ) {
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

// Hook into WordPress filters and actions to modify the admin list table
add_filter( 'manage_reward-item_posts_columns', array( __NAMESPACE__ . '\\Rewards_Table', 'set_reward_columns' ) );
add_action( 'manage_reward-item_posts_custom_column', array( __NAMESPACE__ . '\\Rewards_Table', 'populate_reward_columns' ), 10, 2 );