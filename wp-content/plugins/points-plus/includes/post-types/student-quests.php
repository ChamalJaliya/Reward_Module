<?php

/**
 * Registers the Student Quests custom post type.
 *
 * @package    Your_Theme_Or_Plugin  // Change this!
 * @subpackage Your_Theme_Or_Plugin/includes/post-types // Adjust as needed
 */

namespace PointsPlus\PostTypes;  // Change this!

class StudentQuests {

    /**
     * Registers the Student Quests custom post type.
     */
    public static function register(): void {
        $labels = array(
            'name'               => _x( 'Student Quests', 'Post Type General Name', 'your-text-domain' ), // Change this!
            'singular_name'      => _x( 'Student Quest', 'Post Type Singular Name', 'your-text-domain' ), // Change this!
            'menu_name'          => __( 'Student Quests', 'your-text-domain' ), // Change this!
            'parent_item_colon'  => __( 'Parent Student Quest:', 'your-text-domain' ), // Change this!
            'all_items'          => __( 'All Student Quests', 'your-text-domain' ), // Change this!
            'add_new'            => __( 'Add New', 'your-text-domain' ),
            'add_new_item'       => __( 'Add New Student Quest', 'your-text-domain' ), // Change this!
            'edit_item'          => __( 'Edit Student Quest', 'your-text-domain' ), // Change this!
            'new_item'           => __( 'New Student Quest', 'your-text-domain' ), // Change this!
            'view_item'          => __( 'View Student Quest', 'your-text-domain' ), // Change this!
            'search_items'       => __( 'Search Student Quests', 'your-text-domain' ), // Change this!
            'not_found'          => __( 'No student quests found', 'your-text-domain' ), // Change this!
            'not_found_in_trash' => __( 'No student quests found in Trash', 'your-text-domain' ), // Change this!
        );
        $args   = array(
            'label'               => __( 'Student Quest', 'your-text-domain' ), // Change this!
            'description'         => __( 'Manage student quest completion records.', 'your-text-domain' ), // Change this!
            'labels'              => $labels,
            'public'              => false, //  Crucially, this is false. We don't want public access.
            'show_ui'             => true,  //  Show in the admin interface.
            'show_in_menu'        => true,  //  Show in the main menu.
            'menu_position'       => 30,     //  Adjust the menu position.
            'menu_icon'           => 'dashicons-book', //  Use a relevant icon.
            'query_var'          => true,
            'rewrite'             => array( 'slug' => 'student-quests' ), //  The URL slug.
            'capability_type'     => 'post',
            'has_archive'         => false, //  We don't need an archive page.
            'hierarchical'        => false, //  Quests are not hierarchical.
            'supports'            => array( 'title', 'custom-fields' ), //  'title' is useful for an auto-generated title like "Student Name - Quest Name - Date"
            'show_in_rest'        => false, //  Disable Gutenberg (Block Editor) if not needed.
        );
        register_post_type( 'student_quests', $args );  //  Register the post type.
    }
}

add_action( 'init', array( __NAMESPACE__ . '\\StudentQuests', 'register' ) ); //  Hook into WordPress's init action.