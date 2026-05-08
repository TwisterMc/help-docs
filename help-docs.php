<?php
/**
 * @wordpress-plugin
 * @package WordPress
 * Plugin Name: Help Docs
 * Description: Adds a custom post type that is visible only in the admin.
 * Author: Thomas McMahon
 * Version: 0.3
 * Author URI: https://www.twistermc.com
 * Text Domain: help_docs
 * Requires WordPress: 6.0
 * Requires PHP: 8.0
 * Requires: MySQL 8.0+, MariaDB 10.6+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HELP_DOCS_VERSION', '0.3' );
define( 'HELP_DOCS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELP_DOCS_URL', plugin_dir_url( __FILE__ ) );

require HELP_DOCS_DIR . 'inc/class-help-docs.php';

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'help_docs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

Help_Docs::init();
