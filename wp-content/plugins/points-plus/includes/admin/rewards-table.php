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
        return [
            'cb' => '<input type="checkbox" />',
            'title' => __( 'Promotion Name', 'points-plus' ),
            'promotion_type' => __( 'Type', 'points-plus' ),
            'description' => __( 'Description', 'points-plus' ),
            'required_coins' => __( 'Required Coins', 'points-plus' ),
            'reload_value' => __( 'Reload Value', 'points-plus' ),
            'valid_from' => __( 'Valid From', 'points-plus' ),
            'valid_until' => __( 'Valid Until', 'points-plus' ),
            'status' => __( 'Status', 'points-plus' ),
            'date' => __( 'Date', 'points-plus' ),
        ];
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
            case 'description':
                $description = get_field( 'description', $post_id );
                echo $description ? wp_trim_words( $description, 20, '...' ) : '-';
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
            case 'status':
                $status = get_field( 'status', $post_id );
                printf(
                    '<label class="pp-toggle-switch">
                        <input type="checkbox" class="promotion-status-toggle" data-id="%1$d" %2$s />
                        <span class="pp-slider"></span>
                    </label>',
                    $post_id,
                    checked( $status, true, false )
                );
                break;
        }
    }

}

// Hook into WordPress filters and actions to modify the admin list table
add_filter( 'manage_reward-item_posts_columns', array( __NAMESPACE__ . '\\Rewards_Table', 'set_reward_columns' ) );
add_action( 'manage_reward-item_posts_custom_column', array( __NAMESPACE__ . '\\Rewards_Table', 'populate_reward_columns' ), 10, 2 );

add_action('admin_enqueue_scripts', function($hook){
    if ( $hook === 'edit.php' && function_exists('get_current_screen') ) {
        $screen = get_current_screen();
        if ( $screen->post_type === 'reward-item' ) {
            // JS toggle handler
            wp_enqueue_script(
                'points-plus-promotion-status-toggle',
                plugins_url( '../assets/js/promotion-status-toggle.js', __FILE__ ),
                [ 'jquery' ],
                POINTS_PLUS_VERSION,
                true
            );
            wp_localize_script(
                'points-plus-promotion-status-toggle',
                'PointsPlus_Admin',
                [
                  'ajax_url' => admin_url('admin-ajax.php'),
                  'nonce'    => wp_create_nonce('points_plus_toggle_status'),
                ]
            );

            // CSS for slider switches
            wp_enqueue_style(
                'points-plus-admin-tables',
                plugins_url( '../assets/css/rewards-table.css', __FILE__ ),
                [],
                POINTS_PLUS_VERSION
              );
              
        }
    }
});

// – Verifies the one‑time nonce.
// – Confirms the current user can edit that Reward.
// – Calls ACF’s update_field('status', …) to flip the “true_false” toggle.
// – Returns success: true so JS won’t revert the box.
add_action( 'wp_ajax_toggle_promotion_status', function(){
    // verify nonce
    if ( ! isset($_REQUEST['_wpnonce'])
      || ! wp_verify_nonce($_REQUEST['_wpnonce'], 'points_plus_toggle_status')
    ) {
        wp_send_json_error(['message'=>'Bad nonce'], 403);
    }

    $post_id = intval( $_REQUEST['post_id'] ?? 0 );
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error(['message'=>'No permission'], 403);
    }

    $new = isset($_REQUEST['status']) && intval($_REQUEST['status']) ? 1 : 0;

    // Update your ACF true/false field
    if ( ! function_exists('update_field') ) {
      wp_send_json_error(['message'=>'ACF not active'], 500);
    }
    update_field( 'status', $new, $post_id );

    wp_send_json_success();
});


