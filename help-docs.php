<?php
/**
 * @wordpress-plugin
 * @package WordPress
 * Plugin Name: Help Docs
 * Description: Adds a custom post type that is visible only in the admin.
 * Author: Thomas McMahon
 * Version: 0.13
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

add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'help_docs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

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
function help_docs_add_style( $hook ) {
	// Only enqueue on help-docs admin pages.
	if ( false === stripos( $hook, 'help-docs' ) ) {
		return;
	}

	// Plugin admin styles for list/detail pages
	wp_enqueue_style( 'help-docs-style', plugin_dir_url( __FILE__ ) . 'style/style.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'style/style.css' ) );

	// When viewing Help Docs detail page in admin, enqueue core block styles
	// so block content (Gutenberg) is rendered correctly outside the block editor.
	if ( isset( $_GET['page'] ) && 'help-docs-info.php' === $_GET['page'] ) {
		// Enqueue WP core block styles if they are registered.
		if ( wp_style_is( 'wp-block-library', 'registered' ) ) {
			wp_enqueue_style( 'wp-block-library' );
		}
		if ( wp_style_is( 'wp-block-library-theme', 'registered' ) ) {
			wp_enqueue_style( 'wp-block-library-theme' );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'help_docs_add_style' );



/**
 * Restrict REST API access to the custom post type to logged-in users only.
 *
 * Keep `show_in_rest => true` so Gutenberg works, but block unauthenticated
 * public access to the `help_docs` REST endpoints. Diagnostic logging is
 * only emitted when `WP_DEBUG` is enabled to avoid spamming production logs.
 *
 * @param WP_Error|null|true $result Authentication result so far.
 * @return WP_Error|null|true
 */
function help_docs_restrict_rest_auth( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }

    try {
        $server = rest_get_server();

        // Some WP versions don't expose get_current_request() on the server. Bail safely.
        if ( ! $server || ! method_exists( $server, 'get_current_request' ) ) {
            return $result;
        }

        $request = $server->get_current_request();
        if ( ! $request ) {
            return $result;
        }

        $route = $request->get_route();

        // Debug logging removed.

        // Match '/wp/v2/help_docs' or '/wp/v2/help_docs/...'
        if ( preg_match( '#^/wp/v2/help_docs(?:/|$)#', $route ) ) {

            // Not logged in → 401
            if ( ! is_user_logged_in() ) {
                return new WP_Error(
                    'rest_forbidden',
                    'Authentication required to access this resource.',
                    array( 'status' => rest_authorization_required_code() )
                );
            }

            // Logged in but doesn't have admin access → 403
            // 'read' is a broad capability that indicates WP admin access for typical installs.
            if ( ! current_user_can( 'read' ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    'You do not have permission to access this resource.',
                    array( 'status' => 403 )
                );
            }
        }
    } catch ( Throwable $e ) {
        // Swallow exceptions and avoid causing a 500 response.
        return $result;
    }

    return $result;
}
add_filter( 'rest_authentication_errors', 'help_docs_restrict_rest_auth' );
