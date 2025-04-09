<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Core theme setup
if (!function_exists('twentytwentyfive_post_format_setup')) :
    function twentytwentyfive_post_format_setup() {
        add_theme_support('post-formats', array('aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video'));
    }
endif;
add_action('after_setup_theme', 'twentytwentyfive_post_format_setup');

// Editor style
if (!function_exists('twentytwentyfive_editor_style')) :
    function twentytwentyfive_editor_style() {
        add_editor_style(get_parent_theme_file_uri('assets/css/editor-style.css'));
    }
endif;
add_action('after_setup_theme', 'twentytwentyfive_editor_style');

// Frontend styles
if (!function_exists('twentytwentyfive_enqueue_styles')) :
    function twentytwentyfive_enqueue_styles() {
        wp_enqueue_style(
            'twentytwentyfive-style',
            get_parent_theme_file_uri('style.css'),
            array(),
            wp_get_theme()->get('Version')
        );
    }
endif;
add_action('wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles');

// Block styles
if (!function_exists('twentytwentyfive_block_styles')) :
    function twentytwentyfive_block_styles() {
        register_block_style(
            'core/list',
            array(
                'name'         => 'checkmark-list',
                'label'        => __('Checkmark', 'twentytwentyfive'),
                'inline_style' => '
                ul.is-style-checkmark-list {
                    list-style-type: "\2713";
                }
                ul.is-style-checkmark-list li {
                    padding-inline-start: 1ch;
                }',
            )
        );
    }
endif;
add_action('init', 'twentytwentyfive_block_styles');

// Pattern categories
if (!function_exists('twentytwentyfive_pattern_categories')) :
    function twentytwentyfive_pattern_categories() {
        register_block_pattern_category(
            'twentytwentyfive_page',
            array(
                'label'       => __('Pages', 'twentytwentyfive'),
                'description' => __('A collection of full page layouts.', 'twentytwentyfive'),
            )
        );

        register_block_pattern_category(
            'twentytwentyfive_post-format',
            array(
                'label'       => __('Post formats', 'twentytwentyfive'),
                'description' => __('A collection of post format patterns.', 'twentytwentyfive'),
            )
        );
    }
endif;
add_action('init', 'twentytwentyfive_pattern_categories');

// Block bindings
if (!function_exists('twentytwentyfive_register_block_bindings')) :
    function twentytwentyfive_register_block_bindings() {
        register_block_bindings_source(
            'twentytwentyfive/format',
            array(
                'label'              => _x('Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive'),
                'get_value_callback' => 'twentytwentyfive_format_binding',
            )
        );
    }
endif;
add_action('init', 'twentytwentyfive_register_block_bindings');

if (!function_exists('twentytwentyfive_format_binding')) :
    function twentytwentyfive_format_binding() {
        $post_format_slug = get_post_format();
        if ($post_format_slug && 'standard' !== $post_format_slug) {
            return get_post_format_string($post_format_slug);
        }
    }
endif;

// Include modular functionality files
require_once get_template_directory() . '/inc/student-profile.php';
require_once get_template_directory() . '/inc/quest-system.php';
require_once get_template_directory() . '/inc/reward-system.php';
require_once get_template_directory() . '/inc/notification-system.php';
require_once get_template_directory() . '/inc/daily-rewards.php';
require_once get_template_directory() . '/inc/shortcodes.php';
require_once get_template_directory() . '/inc/enqueue-scripts.php';