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
	 * Get admin page heading
	 */
	public static function get_help_docs_page_heading() {
		return get_option( 'help_docs_page_heading', 'Help Docs' );
	}

	/**
	 * Custom Post Type
	 */
	public static function add_custom_post_type() {
		$labels = array(
			'name'                  => _x( 'Help Docs', 'Post Type General Name', 'help_docs' ),
			'singular_name'         => _x( 'Help Doc', 'Post Type Singular Name', 'help_docs' ),
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
			'label'               => __( 'Help Docs', 'help_docs' ),
			'description'         => __( 'Your site\'s help documentation', 'help_docs' ),
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
			'show_in_rest'        => (bool) get_option( 'help_docs_enable_gutenberg', false ),
			'rest_base' => 'help_docs',  // Explicitly set the REST endpoint
		);
		register_post_type( 'help_docs', $args );
	}

	/**
	 * Add Admin Menus
	 */
	public static function help_docs_admin_menu() {
		$page_heading = self::get_help_docs_page_heading();

		add_menu_page(
			'Help Docs',
			'Help Docs',
			'read',
			'help-docs.php',
			'help_docs_admin_page',
			'dashicons-editor-help',
			3
		);
		add_submenu_page(
			'help-docs.php',
			'Settings',
			'Settings',
			'manage_options',
			'help-docs-settings',
			'help_docs_settings'
		);
		add_submenu_page(
			'help-docs.php',
			'Help Doc Details',
			'Help Doc Details',
			'read',
			'help-docs-info.php',
			'help_docs_admin_page_info'
		);
	}

	/**
	 * Help Docs Admin Main Page
	 */
	public static function help_docs_admin_page() {
		$page_heading = self::get_help_docs_page_heading();

		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
		}
		?>
		<div class="help-docs-wrapper">
			<h2><?php 
				// Only show "Welcome To" prefix if using default heading
				if ( $page_heading === 'Help Docs' ) {
					echo esc_html__( 'Welcome To', 'help_docs' ) . ' ' . esc_html( $page_heading );
				} else {
					echo esc_html( $page_heading );
				}
			?></h2>
			<hr/>
			<?php
			echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=help_docs' ) ) . '" class="button button-large">' . esc_html__( 'New Help Doc', 'help_docs' ) . '</a></p>';
			echo '<ul class="help_pages">';

			$posts = get_posts( array(
				'post_type'      => 'help_docs',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'no_found_rows'  => true,
			) );

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$link = esc_url( add_query_arg( array( 'page' => 'help-docs-info.php', 'id' => $post->ID ), admin_url( 'admin.php' ) ) );
					$title = esc_html( $post->post_title );
					echo '<li><a href="' . $link . '">' . $title . '</a></li>';
				}
			} else {
				echo '<li>' . esc_html__( 'No help documents found.', 'help_docs' ) . '</li>';
			}

			echo '</ul>';		?>
		</div>
		<?php	}

	/**
	 * Help Page Details
	 *
	 * @var array $_GET is used to pass in the help document post id
	 */
	public static function help_docs_admin_page_info() {
		$page_heading = self::get_help_docs_page_heading();

		if ( ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
		}

		?>
		<div class="help-docs-wrapper">
			<h2><?php echo esc_html( $page_heading ); ?></h2>
			<hr/>
			<?php
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=help-docs.php' ) ) . '" class="button button-large">' . esc_html__( 'Back to Help Docs', 'help_docs' ) . '</a> <a href="' . esc_url( admin_url( 'post-new.php?post_type=help_docs' ) ) . '" class="button button-large">' . esc_html__( 'New Help Doc', 'help_docs' ) . '</a></p>';
			echo '<div class="entry-content">';
			if ( isset( $_GET['id'] ) ) {
				$id   = absint( $_GET['id'] );
				$post = get_post( $id );
				if ( $post && 'help_docs' === $post->post_type ) {
					echo '<h1>' . esc_html( get_the_title( $id ) ) . '</h1>';
					echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
					echo '<a href="' . esc_url( get_edit_post_link( $id ) ) . '" class="button button-large" aria-label="' . esc_attr( sprintf( __( 'Edit: %s', 'help_docs' ), get_the_title( $id ) ) ) . '">' . esc_html__( 'Edit', 'help_docs' ) . '</a>';
				} else {
					echo esc_html__( 'Content not found.', 'help_docs' );
				}
			} else {
				echo esc_html__( 'Sorry. No help document found.', 'help_docs' );
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
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
		}

		$page_heading = self::get_help_docs_page_heading();
		$current_heading = get_option( 'help_docs_page_heading', 'Help Docs' );
		$enable_gutenberg = get_option( 'help_docs_enable_gutenberg', false );
		?>
		<div class="help-docs-wrapper">
			<h2><?php esc_html_e( 'Help Docs Settings', 'help_docs' ); ?></h2>
			<hr/>
			<?php if ( get_transient( 'help_docs_settings_saved_' . get_current_user_id() ) ) :
				delete_transient( 'help_docs_settings_saved_' . get_current_user_id() );
			?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully!', 'help_docs' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'help_docs_save_settings', 'help_docs_settings_nonce' ); ?>
				<input type="hidden" name="action" value="help_docs_save_settings" />
				
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="help_docs_page_heading"><?php esc_html_e( 'Page Heading', 'help_docs' ); ?></label>
							</th>
							<td>
								<input type="text" 
									id="help_docs_page_heading" 
									name="help_docs_page_heading" 
									value="<?php echo esc_attr( $current_heading ); ?>" 
									class="regular-text" />
								<p class="description"><?php esc_html_e( 'Help Docs page heading.', 'help_docs' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Enable Gutenberg Editor', 'help_docs' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="help_docs_enable_gutenberg">
										<input type="checkbox" 
											id="help_docs_enable_gutenberg" 
											name="help_docs_enable_gutenberg" 
											value="1" 
											<?php checked( $enable_gutenberg, true ); ?> />
										<?php esc_html_e( 'Enable the Gutenberg block editor for Help Docs', 'help_docs' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'When enabled, uses the Gutenberg block editor. When disabled, uses the classic editor.', 'help_docs' ); ?></p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>
				
				<?php submit_button( __( 'Save Settings', 'help_docs' ) ); ?>
			</form>
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
		$output .= "\n$indent<ul class='parent'>\n";
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
			if ( in_array( $page->ID, (array) $_current_page->ancestors, true ) ) {
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
			$page->post_title = sprintf( __( '#%d (no title)', 'help_docs' ), $page->ID );
		}

		$args['link_before'] = empty( $args['link_before'] ) ? '' : $args['link_before'];
		$args['link_after']  = empty( $args['link_after'] ) ? '' : $args['link_after'];

		$link = esc_url( add_query_arg( array( 'page' => 'help-docs-info.php', 'id' => $page->ID ), admin_url( 'admin.php' ) ) );
		$title = esc_html( apply_filters( 'the_title', $page->post_title, $page->ID ) );
		$output .= $indent . sprintf(
			'<li class="%s"><a href="%s">%s%s%s</a>',
			esc_attr( $css_classes ),
			$link,
			$args['link_before'],
			$title,
			$args['link_after']
		);

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
