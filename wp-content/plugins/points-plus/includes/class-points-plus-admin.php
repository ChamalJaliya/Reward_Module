<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://differently.study/
 * @since      1.0.0
 *
 * @package    Points_Plus
 * @subpackage Points_Plus/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and the ability to register all
 * admin-specific hooks of the plugin.
 *
 * @since      1.0.0
 * @package    Points_Plus
 * @subpackage Points_Plus/admin
 * @author     Chamal <cjaliya.sln2@gmail.com>
 */
class Points_Plus_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The unique identifier of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of the plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void {

        wp_enqueue_script( $this->plugin_name . '-admin-tables', plugin_dir_url( __FILE__ ) . 'js/admin-tables.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void {

        wp_enqueue_style( $this->plugin_name . '-admin-styles', plugin_dir_url( __FILE__ ) . 'css/admin-styles.css', array(), $this->version, 'all' );

    }
}