<?php
/**
 * Registers the Student custom post type.
 *
 * @package Points_Plus
 * @subpackage Points_Plus/includes/post-types
 */

namespace PointsPlus\PostTypes;

class Students {

    /**
     * Registers the Student custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'                  => _x( 'Students', 'Post Type General Name', 'points-plus' ),
            'singular_name'         => _x( 'Student', 'Post Type Singular Name', 'points-plus' ),
            'menu_name'             => __( 'Students', 'points-plus' ),
            'all_items'             => __( 'All Students', 'points-plus' ),
            'view_item'             => __( 'View Student', 'points-plus' ),
            'add_new_item'          => __( 'Add New Student', 'points-plus' ),
            'add_new'               => __( 'Add New', 'points-plus' ),
            'edit_item'             => __( 'Edit Student', 'points-plus' ),
            'update_item'           => __( 'Update Student', 'points-plus' ),
            'search_items'          => __( 'Search Students', 'points-plus' ),
            'not_found'             => __( 'No students found', 'points-plus' ),
            'not_found_in_trash'    => __( 'No students found in Trash', 'points-plus' ),
            'date'                  => __( 'Date', 'points-plus' ),
        );
        $args = array(
            'label'                 => __( 'Student', 'points-plus' ),
            'description'           => __( 'Manage students and their reward data.', 'points-plus' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false, // Keep it private if you want to manage from admin only.
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 30, // Adjust the position as needed.
            'menu_icon'             => 'dashicons-welcome-learn-more',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => array( 'slug' => 'student' ),
            'capability_type'       => 'post',
        );
        register_post_type( 'student', $args );
    }
}

// Hook into the 'init' action to register the post type
add_action( 'init', array( __NAMESPACE__ . '\\Students', 'register' ) );
