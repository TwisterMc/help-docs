<?php
/*
Plugin Name: Help Docs
Description:
Author: Thomas McMahon
Version: 0.1
Author URI: https://www.twistermc.com
Text Domain: help_docs
*/

// Register Custom Post Type
function custom_post_type() {

	$labels = array(
		'name'                  => _x( 'Documentation', 'Post Type General Name', 'help_docs' ),
		'singular_name'         => _x( 'Documentation', 'Post Type Singular Name', 'help_docs' ),
		'menu_name'             => __( 'Documentation', 'help_docs' ),
		'name_admin_bar'        => __( 'Documentation', 'help_docs' ),
		'archives'              => __( 'Item Archives', 'help_docs' ),
		'attributes'            => __( 'Item Attributes', 'help_docs' ),
		'parent_item_colon'     => __( 'Parent Item:', 'help_docs' ),
		'all_items'             => __( 'All Items', 'help_docs' ),
		'add_new_item'          => __( 'Add New Item', 'help_docs' ),
		'add_new'               => __( 'Add New', 'help_docs' ),
		'new_item'              => __( 'New Item', 'help_docs' ),
		'edit_item'             => __( 'Edit Item', 'help_docs' ),
		'update_item'           => __( 'Update Item', 'help_docs' ),
		'view_item'             => __( 'View Item', 'help_docs' ),
		'view_items'            => __( 'View Items', 'help_docs' ),
		'search_items'          => __( 'Search Item', 'help_docs' ),
		'not_found'             => __( 'Not found', 'help_docs' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'help_docs' ),
		'featured_image'        => __( 'Featured Image', 'help_docs' ),
		'set_featured_image'    => __( 'Set featured image', 'help_docs' ),
		'remove_featured_image' => __( 'Remove featured image', 'help_docs' ),
		'use_featured_image'    => __( 'Use as featured image', 'help_docs' ),
		'insert_into_item'      => __( 'Insert into item', 'help_docs' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'help_docs' ),
		'items_list'            => __( 'Items list', 'help_docs' ),
		'items_list_navigation' => __( 'Items list navigation', 'help_docs' ),
		'filter_items_list'     => __( 'Filter items list', 'help_docs' ),
	);
	$args   = array(
		'label'               => __( 'Documentation', 'help_docs' ),
		'description'         => __( 'Post Type Description', 'help_docs' ),
		'labels'              => $labels,
		'hierarchical'        => true,
		'supports'            => array( 'title', 'editor', 'page-attributes' ),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => false, // hides it in the WP admin
		'menu_position'       => 5,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'page',
		'show_in_rest'        => false, // also disables guteneberg
	);
	register_post_type( 'help_docs', $args );

}
add_action( 'init', 'custom_post_type', 0 );

/**
 * Add Admin Menus
 */
add_action( 'admin_menu', 'help_docs_admin_menu' );

function help_docs_admin_menu() {
	add_menu_page(
		'Help Docs',
		'Help Docs',
		'manage_options',
		'help-docs.php',
		'help_docs_admin_page',
		'dashicons-editor-help',
		6
	);
	add_submenu_page(
		'help-docs-info_page',
		'Help Docs Details',
		'Help Docs Details',
		'manage_options',
		'help-docs-info.php',
		'help_docs_admin_page_info'
	);
}

function help_docs_admin_page() {
	?>
	<div class="wrap">
		<h2>Welcome To Help Docs</h2>
		<?php
		echo '<p><a href="/wp-admin/post-new.php?post_type=help_docs" class="button button-large">New Help Doc</a></p>';
		// WP_Query arguments
		$args = array(
			'post_type'      => 'help_docs',
			'post_status'    => 'private',
			'posts_per_page' => '-1',
		);
		// The Query
		$query = new WP_Query( $args );

		// The Loop
		if ( $query->have_posts() ) {
			echo '<ul>';
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( get_the_title() !== 'Auto Draft' ) {
					echo '<li><a href="/wp-admin/admin.php?page=help-docs-info.php&id=' . get_the_ID() . '">';
					echo esc_html( get_the_title() );
					echo '</a></br>';
					echo '</li>';
				}
			}
			echo '</ul>';
		} else {
			echo '<p>' . __( 'There are no help documents yet.' ) . '</p>';
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		?>
	</div>
	<?php
}

function help_docs_admin_page_info() {
	?>
	<div class="wrap">
			<h2>Welcome To The Details (my-help)</h2>
			<?php
			$variable = $_GET['id'];
			echo '<p><a href="/wp-admin/admin.php?page=help-docs.php" class="button button-large">' . __( '< Back' ) . '</a> <a href="/wp-admin/post.php?post=' . esc_html( $variable ) . '&action=edit" class="button button-large">' . __( 'Edit' ) . '</a> <a href="/wp-admin/post-new.php?post_type=help_docs" class="button button-large">' . __( 'New Documentation Post' ) . '</a></p>';
			echo '<div class="entry-content">';
			echo '<h1>' . esc_html( get_the_title( $variable ) ) . '</h1>';
			echo wpautop( get_post_field( 'post_content', $variable ) );
			echo '</div>';
			?>
	</div>
	<?php
	}