<?php

/**
 * Registers the Reward Item custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class Rewards {

    /**
     * Registers the Reward Item custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Reward Items', 'Post Type General Name', 'points-plus' ),
            'singular_name'      => _x( 'Reward Item', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'          => __( 'Rewards', 'points-plus' ),
            'parent_item_colon'  => __( 'Parent Reward Item:', 'points-plus' ),
            'all_items'          => __( 'All Rewards', 'points-plus' ),
            'view_item'          => __( 'View Reward Item', 'points-plus' ),
            'add_new_item'       => __( 'Add New Reward', 'points-plus' ),
            'add_new'            => __( 'Add New', 'points-plus' ),
            'edit_item'          => __( 'Edit Reward Item', 'points-plus' ),
            'update_item'        => __( 'Update Reward Item', 'points-plus' ),
            'search_items'       => __( 'Search Reward Items', 'points-plus' ),
            'not_found'          => __( 'No reward items found', 'points-plus' ),
            'not_found_in_trash' => __( 'No reward items found in Trash', 'points-plus' ),
            'featured_image'     => __( 'Featured Image', 'points-plus' ),
            'set_featured_image' => __( 'Set featured image', 'points-plus' ),
            'remove_featured_image' => __( 'Remove featured image', 'points-plus' ),
            'use_featured_image' => __( 'Use as featured image', 'points-plus' ),
            'insert_into_item'   => __( 'Insert into reward item', 'points-plus' ),
            'uploaded_to_this_item' => __( 'Uploaded to this reward item', 'points-plus' ),
            'items_list'         => __( 'Reward items list', 'points-plus' ),
            'items_list_navigation' => __( 'Reward items list navigation', 'points-plus' ),
            'filter_items_list' => __( 'Filter reward items list', 'points-plus' ),
        );
        $args = array(
            'label'               => __( 'Reward Item', 'points-plus' ),
            'description'         => __( 'Manage specific reward items (e.g., a badge, a coupon) for the Points Plus plugin.', 'points-plus' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'custom-fields', 'thumbnail' ), // Added 'thumbnail'
            'hierarchical'        => false,
            'public'              => false, // Changed to false
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-awards',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false, // Changed to false
            'can_export'          => true,
            'has_archive'         => false, // Changed to false
            'exclude_from_search' => true, // Changed to true
            'publicly_queryable'  => false, // Changed to false
            'rewrite'             => array( 'slug' => 'reward-item' ),
            'capability_type'     => 'post',
        );
        register_post_type( 'reward-item', $args );
    }

}

// Hook into the 'init' action to register the post type
add_action( 'init', array( __NAMESPACE__ . '\\Rewards', 'register' ) );