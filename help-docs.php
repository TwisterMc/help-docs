<?php
/**
 * @wordpress-plugin
 * @package WordPress
 * Plugin Name: Help Docs
 * Description: Adds a custom post type that is visible only in the admin.
 * Author: Thomas McMahon
 * Version: 0.11
 * Author URI: https://www.twistermc.com
 * Text Domain: help_docs
 */

/**
 * Exit early if directly accessed via URL.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include the Class
 */
require plugin_dir_path( __FILE__ ) . 'inc/class-help-docs.php';

/**
 * Register Custom Post Type
 */
function add_custom_post_type() {
	Help_Docs::add_custom_post_type();
}
add_action( 'init', 'add_custom_post_type', 0 );

/**
 * Add Admin Menus
 */
function help_docs_admin_menu() {
	Help_Docs::help_docs_admin_menu();
}
add_action( 'admin_menu', 'help_docs_admin_menu' );

/**
 * Add Admin Page
 */
function help_docs_admin_page() {
	Help_Docs::help_docs_admin_page();
}

/**
 * Add Detail Page
 */
function help_docs_admin_page_info() {
	Help_Docs::help_docs_admin_page_info();
}

/**
 * Settings Page
 */
function help_docs_settings() {
	Help_Docs::help_docs_settings();
}

/**
 * Add Styles
 */
function help_docs_add_style() {
	wp_enqueue_style( 'help-docs-style', plugins_url() . '/help-docs/style/style.css', array(), '1.0' );
}
add_action( 'admin_enqueue_scripts', 'help_docs_add_style' );
