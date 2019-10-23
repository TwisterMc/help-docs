<?php
/**
 * @package WordPress
 * Exit early if directly accessed via URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help_Docs Class
 * Controls all the things.
 */
class Help_Docs {

	/**
	 * Custom Admin Title
	 */
	public static function help_docs_admin_menu_title() {
		return 'Help Docs';
	}

	/**
	 * Custom Post Type
	 */
	public static function add_custom_post_type() {
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
			'show_in_menu'        => false, // hides it in the WP admin.
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'show_in_rest'        => false, // also disables guteneberg.
		);
		register_post_type( 'help_docs', $args );
	}

	/**
	 * Add Admin Menus
	 */
	public static function help_docs_admin_menu() {
		$admin_menu_title = self::help_docs_admin_menu_title();

		add_menu_page(
			$admin_menu_title,
			$admin_menu_title,
			'manage_options',
			'help-docs.php',
			'help_docs_admin_page',
			'dashicons-editor-help',
			6
		);
		add_submenu_page(
			'help-docs.php',
			'Settings',
			'Settings',
			'manage_options',
			'help-docs-settings.php',
			'help_docs_settings'
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

	/**
	 * Help Docs Admin Main Page
	 */
	public static function help_docs_admin_page() {
		$admin_menu_title = self::help_docs_admin_menu_title();
		?>
		<div class="help-docs-wrapper">
			<h2><?php echo esc_html( __( 'Welcome To ' ) ) . esc_html( $admin_menu_title ); ?></h2>
			<hr/>
			<?php
			echo '<p><a href="/wp-admin/post-new.php?post_type=help_docs" class="button button-large">' . esc_html( __( 'New Help Doc' ) ) . '</a></p>';
			echo '<ul class="help_pages" role="menu">';

			$walker = new Help_Docs_Walker();

			$args = array(
				'title_li'    => '',
				'sort_column' => 'menu_order',
				'post_type'   => 'help_docs',
				'walker'      => $walker,
			);

			wp_list_pages( $args );
			echo '</ul>';
	}

	/**
	 * Help Page Details
	 *
	 * @var array $_GET is used to pass in the help document post id
	 */
	public static function help_docs_admin_page_info() {
		$admin_menu_title = self::help_docs_admin_menu_title();

		?>
		<div class="help-docs-wrapper">
			<h2><?php echo esc_html( $admin_menu_title ); ?></h2>
			<hr/>
			<?php
			echo '<p><a href="/wp-admin/admin.php?page=help-docs.php" class="button button-large">' . esc_html( __( '< Back' ) ) . '</a> <a href="/wp-admin/post-new.php?post_type=help_docs" class="button button-large">' . esc_html( __( 'New Help Doc' ) ) . '</a></p>';
			echo '<div class="entry-content">';
			if ( isset( $_GET['id'] ) ) {
				$variable = sanitize_key( $_GET['id'] );
				echo '<h1>' . esc_html( get_the_title( $variable ) ) . '</h1>';
				echo wpautop( get_post_field( 'post_content', $variable ) );
				echo '<a href="/wp-admin/post.php?post=' . esc_html( $variable ) . '&action=edit" class="button button-large">' . esc_html( __( 'Edit' ) ) . '</a>';
			} else {
				echo 'Sorry. We\'re unable to load content due to missing ID';
			}
			echo '</div>';
			?>
		</div>
		<?php

	}

	/**
	 * Help Docs Settings
	 */
	public static function help_docs_settings() {
		$admin_menu_title = self::help_docs_admin_menu_title();
		?>
		<div class="help-docs-wrapper">
			<h2><?php echo esc_html( $admin_menu_title ) . esc_html( __( ' Settings' ) ); ?></h2>
		</div>

		<?php
	}
}

/**
 * Help_Docs Walker Class
 * Used to modify the links as I'm not using the permalink structure.
 * via: https://gist.github.com/donchenko/4ac5f1380ae687f11bfd13f379531f60
 */
class Help_Docs_Walker extends Walker_Page {
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class='parent' role='menu'>\n";
	}

	public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}

		$css_class = array( 'page_item', 'page-item-' . $page->ID );

		if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
			$css_class[] = 'page_item_has_children';
		}

		if ( ! empty( $current_page ) ) {
			$_current_page = get_post( $current_page );
			if ( in_array( $page->ID, $_current_page->ancestors ) ) {
				$css_class[] = 'current_page_ancestor';
			}
			if ( $page->ID === $current_page ) {
				$css_class[] = 'current_page_item';
			} elseif ( $_current_page && $page->ID === $_current_page->post_parent ) {
				$css_class[] = 'current_page_parent';
			}
		} elseif ( get_option( 'page_for_posts' ) === $page->ID ) {
			$css_class[] = 'current_page_parent';
		}

		/**
		 * Filter the list of CSS classes to include with each page item in the list.
		 *
		 * @since 2.8.0
		 *
		 * @see wp_list_pages()
		 *
		 * @param array $css_class An array of CSS classes to be applied to each list item.
		 * @param WP_Post $page Page data object.
		 * @param int $depth Depth of page, used for padding.
		 * @param array $args An array of arguments.
		 * @param int $current_page ID of the current page.
		 */
		$css_classes = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		if ( '' === $page->post_title ) {
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$args['link_before'] = empty( $args['link_before'] ) ? '' : $args['link_before'];
		$args['link_after']  = empty( $args['link_after'] ) ? '' : $args['link_after'];

		/** This filter is documented in wp-includes/post-template.php */
		if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
			$output .= $indent . sprintf(
				'<li class="%s"><a href="%s">%s%s%s</a>',
				$css_classes,
				'/wp-admin/admin.php?page=help-docs-info.php&id=' . $page->ID ,
				$args['link_before'],
				apply_filters( 'the_title', $page->post_title, $page->ID ),
				$args['link_after']
			);
		} else {
			$output .= $indent . sprintf(
				'<li class="%s"><a href="%s">%s%s%s</a>',
				$css_classes,
				'/wp-admin/admin.php?page=help-docs-info.php&id=' . $page->ID,
				$args['link_before'],
				apply_filters( 'the_title', $page->post_title, $page->ID ),
				$args['link_after']
			);
		}

		if ( ! empty( $args['show_date'] ) ) {
			if ( 'modified' === $args['show_date'] ) {
				$time = $page->post_modified;
			} else {
				$time = $page->post_date;
			}

			$date_format = empty( $args['date_format'] ) ? '' : $args['date_format'];
			$output     .= ' ' . mysql2date( $date_format, $time );
		}
	}
}
