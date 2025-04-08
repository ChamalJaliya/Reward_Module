<?php

/**
 * Registers the Student custom post type.
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class Students {

    /**
     * Registers the Student custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Students', 'Post Type General Name', 'points-plus' ),
            'singular_name'      => _x( 'Student', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'          => __( 'Students', 'points-plus' ),
            'parent_item_colon'  => __( 'Parent Student:', 'points-plus' ),
            'all_items'          => __( 'All Students', 'points-plus' ),
            'add_new'            => __( 'Add New Student', 'points-plus' ),
            'add_new_item'       => __( 'Add New Student', 'points-plus' ),
            'edit_item'          => __( 'Edit Student', 'points-plus' ),
            'new_item'           => __( 'New Student', 'points-plus' ),
            'view_item'          => __( 'View Student', 'points-plus' ),
            'search_items'       => __( 'Search Students', 'points-plus' ),
            'not_found'          => __( 'No students found', 'points-plus' ),
            'not_found_in_trash' => __( 'No students found in Trash', 'points-plus' ),
        );
        $args   = array(
            'label'               => __( 'Student', 'points-plus' ),
            'description'         => __( 'Manage student profiles.', 'points-plus' ),
            'labels'              => $labels,
            'public'              => false, // Usually false for this scenario
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 29, // Adjust as needed
            'menu_icon'           => 'dashicons-groups', // Choose a suitable icon
            'supports'            => array( 'title', 'editor', 'custom-fields' ), // Use ACF for student details
            'hierarchical'        => false,
            'rewrite'             => array( 'slug' => 'student' ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
        );
        register_post_type( 'student', $args );
    }
}

add_action( 'init', array( __NAMESPACE__ . '\\Students', 'register' ) );
