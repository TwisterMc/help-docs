<?php
/**
 * @wordpress-plugin
 * @package WordPress
 * Plugin Name: Help Docs
 * Description: Adds a custom post type that is visible only in the admin.
 * Author: Thomas McMahon
 * Version: 0.6.2
 * Update URI: https://github.com/TwisterMc/help-docs
 * Author URI: https://www.twistermc.com
 * Text Domain: help_docs
 * Requires PHP:      8.1
 * Requires at least: 6.1
 * Tested up to:      7.0
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HELP_DOCS_VERSION', '0.6.2' );
define( 'HELP_DOCS_FILE', __FILE__ );
define( 'HELP_DOCS_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELP_DOCS_URL', plugin_dir_url( __FILE__ ) );

require HELP_DOCS_DIR . 'inc/class-help-docs.php';

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'help_docs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

Help_Docs::init();
