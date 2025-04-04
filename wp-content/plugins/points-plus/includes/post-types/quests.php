<?php

/**
 * Registers the Quest custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class Quests {

    /**
     * Registers the Quest custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Quests', 'Post Type General Name', 'points-plus' ),
            'singular_name'      => _x( 'Quest', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'          => __( 'Quests', 'points-plus' ),
            'parent_item_colon'  => __( 'Parent Quest:', 'points-plus' ),
            'all_items'          => __( 'All Quests', 'points-plus' ),
            'view_item'          => __( 'View Quest', 'points-plus' ),
            'add_new_item'       => __( 'Add New Quest', 'points-plus' ),
            'add_new'            => __( 'Add New', 'points-plus' ),
            'edit_item'          => __( 'Edit Quest', 'points-plus' ),
            'update_item'        => __( 'Update Quest', 'points-plus' ),
            'search_items'       => __( 'Search Quests', 'points-plus' ),
            'not_found'          => __( 'No quests found', 'points-plus' ),
            'not_found_in_trash' => __( 'No quests found in Trash', 'points-plus' ),
            'featured_image'     => __( 'Featured Image', 'points-plus' ),
            'set_featured_image' => __( 'Set featured image', 'points-plus' ),
            'remove_featured_image' => __( 'Remove featured image', 'points-plus' ),
            'use_featured_image' => __( 'Use as featured image', 'points-plus' ),
            'insert_into_item'   => __( 'Insert into quest', 'points-plus' ),
            'uploaded_to_this_item' => __( 'Uploaded to this quest', 'points-plus' ),
            'items_list'         => __( 'Quests list', 'points-plus' ),
            'items_list_navigation' => __( 'Quests list navigation', 'points-plus' ),
            'filter_items_list' => __( 'Filter quests list', 'points-plus' ),
        );
        $args = array(
            'label'               => __( 'Quest', 'points-plus' ),
            'description'         => __( 'Manage quests that users can complete.', 'points-plus' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'custom-fields', 'thumbnail' ), // Added 'thumbnail'
            'hierarchical'        => false,
            'public'              => false, // Changed to false
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 27, // Position it after Rewards
            'menu_icon'           => 'dashicons-flag',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false, // Changed to false
            'can_export'          => true,
            'has_archive'         => false, // Changed to false
            'exclude_from_search' => true, // Changed to true
            'publicly_queryable'  => false, // Changed to false
            'rewrite'             => array( 'slug' => 'quest' ),
            'capability_type'     => 'post',
        );
        register_post_type( 'quest', $args );
    }

}

// Hook into the 'init' action to register the post type
add_action( 'init', array( __NAMESPACE__ . '\\Quests', 'register' ) );