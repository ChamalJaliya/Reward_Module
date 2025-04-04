<?php

/**
 * Registers the Rule Builder custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class Rule_Builder {

    /**
     * Registers the Rule Builder custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Rules', 'Post Type General Name', 'points-plus' ),
            'singular_name'      => _x( 'Rule', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'          => __( 'Rules', 'points-plus' ),
            'parent_item_colon'  => __( 'Parent Rule:', 'points-plus' ),
            'all_items'          => __( 'All Rules', 'points-plus' ),
            'view_item'          => __( 'View Rule', 'points-plus' ),
            'add_new_item'       => __( 'Add New Rule', 'points-plus' ),
            'add_new'            => __( 'Add New', 'points-plus' ),
            'edit_item'          => __( 'Edit Rule', 'points-plus' ),
            'update_item'        => __( 'Update Rule', 'points-plus' ),
            'search_items'       => __( 'Search Rules', 'points-plus' ),
            'not_found'          => __( 'No rules found', 'points-plus' ),
            'not_found_in_trash' => __( 'No rules found in Trash', 'points-plus' ),
        );
        $args = array(
            'label'               => __( 'Rule', 'points-plus' ),
            'description'         => __( 'Manage rules for the Points Plus plugin.', 'points-plus' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'custom-fields' ), // We'll use ACF for the rule details
            'hierarchical'        => false,
            'public'              => false, // Make it private
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 28, // Adjust as needed
            'menu_icon'           => 'dashicons-editor-code', // Choose an appropriate icon
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'rewrite'             => array( 'slug' => 'rule' ),
            'capability_type'     => 'post',
        );
        register_post_type( 'rule', $args ); // Register the 'rule' post type
    }

}

// Hook into the 'init' action to register the post type
add_action( 'init', array( __NAMESPACE__ . '\\Rule_Builder', 'register' ) );