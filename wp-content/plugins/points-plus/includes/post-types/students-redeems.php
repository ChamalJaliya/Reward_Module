<?php

/**
 * Registers the Students Redeems custom post type.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class StudentsRedeems {

    /**
     * Registers the Students Redeems custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Students Redeems', 'Post Type General Name', 'points-plus' ),
            'singular_name'      => _x( 'Student Redeem', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'          => __( 'Students Redeems', 'points-plus' ),
            'parent_item_colon'  => __( 'Parent Student Redeem:', 'points-plus' ),
            'all_items'          => __( 'All Students Redeems', 'points-plus' ),
            'add_new'            => __( 'Add New', 'points-plus' ),
            'add_new_item'       => __( 'Add New Student Redeem', 'points-plus' ),
            'edit_item'          => __( 'Edit Student Redeem', 'points-plus' ),
            'new_item'           => __( 'New Student Redeem', 'points-plus' ),
            'view_item'          => __( 'View Student Redeem', 'points-plus' ),
            'search_items'       => __( 'Search Students Redeems', 'points-plus' ),
            'not_found'          => __( 'No students redeems found', 'points-plus' ),
            'not_found_in_trash' => __( 'No students redeems found in Trash', 'points-plus' ),
        );
        $args   = array(
            'label'               => __( 'Student Redeem', 'points-plus' ),
            'description'         => __( 'Manage student reward redemption history.', 'points-plus' ),
            'labels'              => $labels,
            'public'              => false, // Usually false for this scenario
            'show_ui'             => true, // Adjust if you want it visible in admin
            'show_in_menu'        => true, // Adjust if you want it visible in admin
            'menu_position'       => 30,
            'menu_icon'           => 'dashicons-chart-bar',
            'query_var'          => true,
            'rewrite'             => array( 'slug' => 'students-redeems' ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'editor', 'custom-fields' ),  // Consider 'title' for auto-generated titles
            'show_in_rest'        => false, // If you're using the Block Editor
        );
        register_post_type( 'students_redeems', $args );
    }
}

add_action( 'init', array( __NAMESPACE__ . '\\StudentsRedeems', 'register' ) );