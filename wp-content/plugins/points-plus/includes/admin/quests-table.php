<?php

/**
 * Customizes the admin list table for the Quest post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/admin
 */

namespace PointsPlus\Admin;

class Quests_Table {

    /**
     * Sets the column headers for the Quest list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function set_quest_columns( $columns ) {
        $new_columns = array();
        $new_columns['title'] = __( 'Quest Name', 'points-plus' );
        $new_columns['quest_key'] = __( 'Quest Key', 'points-plus' );
        $new_columns['description'] = __( 'Description', 'points-plus' );
        $new_columns['points_reward'] = __( 'Points Reward', 'points-plus' );
        $new_columns['coins_reward'] = __( 'Coins Reward', 'points-plus' );
        $new_columns['completion_criteria'] = __( 'Criteria', 'points-plus' );
        $new_columns['target_value'] = __( 'Target', 'points-plus' );
        $new_columns['date'] = __( 'Date', 'points-plus' );
        return $new_columns;
    }

    /**
     * Populates the custom columns for the Quest list table.
     *
     * @param string $column  The name of the column.
     * @param int    $post_id The ID of the current post.
     */
    public static function populate_quest_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'quest_key':
                $quest_key = get_field( 'quest_key', $post_id );
                echo $quest_key ? esc_html( $quest_key ) : '-';
                break;
            case 'description':
                $description = get_field( 'description', $post_id );
                echo $description ? wp_trim_words( $description, 20, '...' ) : '-';
                break;
            case 'points_reward':
                $points_reward = get_field( 'points_reward', $post_id );
                echo $points_reward ? esc_html( $points_reward ) : '-';
                break;
            case 'coins_reward':
                $coins_reward = get_field( 'coins_reward', $post_id );
                echo $coins_reward ? esc_html( $coins_reward ) : '-';
                break;
            case 'completion_criteria':
                $completion_criteria = get_field( 'completion_criteria', $post_id );
                echo $completion_criteria ? esc_html( ucfirst( $completion_criteria ) ) : '-';
                break;
            case 'target_value':
                $target_value = get_field( 'target_value', $post_id );
                echo $target_value ? esc_html( $target_value ) : '-';
                break;
        }
    }

}

// Hook into WordPress filters and actions to modify the admin list table
add_filter( 'manage_quest_posts_columns', array( __NAMESPACE__ . '\\Quests_Table', 'set_quest_columns' ) );
add_action( 'manage_quest_posts_custom_column', array( __NAMESPACE__ . '\\Quests_Table', 'populate_quest_columns' ), 10, 2 );