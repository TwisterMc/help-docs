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
     * Capability required to access Help Docs screens.
     */
    private const HELP_DOCS_VIEW_CAP = 'edit_posts';
    private const GITHUB_OWNER = 'TwisterMc';
    private const GITHUB_REPO = 'help-docs';
    private const UPDATE_CACHE_KEY = 'help_docs_github_release_data';

    /**
     * Determine whether the current user can view a Help Doc by status.
     */
    private static function user_can_view_help_doc( WP_Post $post ): bool {
        if ( ! current_user_can( self::HELP_DOCS_VIEW_CAP ) ) {
            return false;
        }

        if ( 'help_docs' !== $post->post_type || 'trash' === $post->post_status ) {
            return false;
        }

        // Fix 1: Ensure private posts strictly respect user ownership and capabilities
        if ( 'private' === $post->post_status ) {
            return current_user_can( 'read_post', $post->ID );
        }

        if ( 'publish' === $post->post_status ) {
            return true;
        }

        if ( in_array( $post->post_status, array( 'draft', 'pending', 'future', 'auto-draft' ), true ) ) {
            return current_user_can( 'edit_post', $post->ID );
        }

        return false;
    }

    /**
     * Register all hooks and filters.
     */
    public static function init(): void {
        add_action( 'init', array( self::class, 'add_custom_post_type' ), 0 );
        add_action( 'admin_menu', array( self::class, 'help_docs_admin_menu' ) );
        add_action( 'admin_post_help_docs_save_settings', array( self::class, 'save_settings' ) );
        add_filter( 'default_post_status', array( self::class, 'default_post_status' ), 10, 3 );
        add_filter( 'wp_insert_post_data', array( self::class, 'force_private_status' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( self::class, 'add_style' ) );
        add_action( 'init', array( self::class, 'maybe_add_rest_auth' ), 20 );
        add_filter( 'pre_set_site_transient_update_plugins', array( self::class, 'inject_plugin_update' ) );
        add_filter( 'plugins_api', array( self::class, 'plugin_information' ), 10, 3 );
        
        // Use transition_post_status for reliable cache invalidation
        add_action( 'transition_post_status', array( self::class, 'invalidate_post_cache_on_transition' ), 10, 3 );
    }

    /**
     * Add update data to the standard WordPress plugin update transient.
     */
    public static function inject_plugin_update( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
            return $transient;
        }

        $plugin_basename = plugin_basename( HELP_DOCS_FILE );
        $plugin_slug     = dirname( $plugin_basename );

        if ( ! isset( $transient->checked[ $plugin_basename ] ) ) {
            return $transient;
        }

        $release = self::get_latest_release_data();
        if ( ! is_array( $release ) ) {
            return $transient;
        }

        if ( version_compare( $release['version'], HELP_DOCS_VERSION, '>' ) ) {
            $update              = new stdClass();
            $update->slug        = $plugin_slug;
            $update->plugin      = $plugin_basename;
            $update->new_version = $release['version'];
            $update->url         = $release['release_url'];
            $update->package     = $release['package_url'];

            $transient->response[ $plugin_basename ] = $update;
        } else {
            $no_update              = new stdClass();
            $no_update->slug        = $plugin_slug;
            $no_update->plugin      = $plugin_basename;
            $no_update->new_version = HELP_DOCS_VERSION;
            $no_update->url         = 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO;
            $no_update->package     = '';

            $transient->no_update[ $plugin_basename ] = $no_update;
        }

        return $transient;
    }

    /**
     * Provide plugin details for the native "View details" update modal.
     */
    public static function plugin_information( $result, string $action, $args ) {
        if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) ) {
            return $result;
        }

        $plugin_slug = dirname( plugin_basename( HELP_DOCS_FILE ) );
        if ( $plugin_slug !== $args->slug ) {
            return $result;
        }

        $release = self::get_latest_release_data();
        if ( ! is_array( $release ) ) {
            return $result;
        }

        $info                = new stdClass();
        $info->name          = 'Help Docs';
        $info->slug          = $plugin_slug;
        $info->version       = $release['version'];
        $info->author        = '<a href="https://www.twistermc.com">Thomas McMahon</a>';
        $info->homepage      = 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO;
        $info->download_link = $release['package_url'];
        $info->sections      = array(
            'description' => __( 'Adds a custom post type that is visible only in the admin.', 'help_docs' ),
            'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( wpautop( $release['body'] ) ) : __( 'See GitHub releases for changelog details.', 'help_docs' ),
        );

        return $info;
    }

    /**
     * Fetch and cache latest release metadata from GitHub.
     */
    private static function get_latest_release_data(): ?array {
        $cached = get_site_transient( self::UPDATE_CACHE_KEY );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $release_endpoint = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode( self::GITHUB_OWNER ),
            rawurlencode( self::GITHUB_REPO )
        );

        $response = wp_remote_get(
            $release_endpoint,
            array(
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
                ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $payload = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $payload ) || empty( $payload['tag_name'] ) ) {
            return null;
        }

        $package_url = self::find_release_zip_url( $payload );
        if ( empty( $package_url ) ) {
            return null;
        }

        $data = array(
            'tag_name'    => (string) $payload['tag_name'],
            'version'     => ltrim( (string) $payload['tag_name'], "vV" ),
            'release_url' => ! empty( $payload['html_url'] ) ? (string) $payload['html_url'] : 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
            'package_url' => $package_url,
            'body'        => isset( $payload['body'] ) ? (string) $payload['body'] : '',
        );

        set_site_transient( self::UPDATE_CACHE_KEY, $data, 6 * HOUR_IN_SECONDS );

        return $data;
    }

    /**
     * Resolve the plugin package URL from release assets.
     */
    private static function find_release_zip_url( array $payload ): string {
        if ( empty( $payload['assets'] ) || ! is_array( $payload['assets'] ) ) {
            return '';
        }

        $fallback = '';

        foreach ( $payload['assets'] as $asset ) {
            if ( ! is_array( $asset ) || empty( $asset['browser_download_url'] ) ) {
                continue;
            }

            $name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
            $url  = (string) $asset['browser_download_url'];

            if ( '' === $name || ! str_ends_with( strtolower( $name ), '.zip' ) ) {
                continue;
            }

            if ( str_starts_with( strtolower( $name ), strtolower( self::GITHUB_REPO . '-' ) ) ) {
                return esc_url_raw( $url );
            }

            if ( '' === $fallback ) {
                $fallback = esc_url_raw( $url );
            }
        }

        return $fallback;
    }

    /**
     * Get admin page heading.
     */
    public static function get_help_docs_page_heading(): string {
        return get_option( 'help_docs_page_heading', 'Help Docs' );
    }

    /**
     * Custom Post Type
     */
    public static function add_custom_post_type(): void {
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
            'show_in_menu'        => false,
            'menu_position'       => 5,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'page',
            'map_meta_cap'        => true, // Fix 2: Essential to translate page capabilities properly
            'show_in_rest'        => (bool) get_option( 'help_docs_enable_gutenberg', false ),
            'rest_base'           => 'help_docs',
        );
        register_post_type( 'help_docs', $args );
    }

    /**
     * Add Admin Menus
     */
public static function help_docs_admin_menu(): void {
        add_menu_page(
            __( 'Help Docs', 'help_docs' ),
            __( 'Help Docs', 'help_docs' ),
            self::HELP_DOCS_VIEW_CAP,
            'help-docs.php',
            array( self::class, 'help_docs_admin_page' ),
            'dashicons-editor-help',
            3
        );
        add_submenu_page(
            'help-docs.php',
            __( 'Settings', 'help_docs' ),
            __( 'Settings', 'help_docs' ),
            'manage_options',
            'help-docs-settings',
            array( self::class, 'help_docs_settings' )
        );
        
add_submenu_page(
    'help-docs.php', 
    __( 'Help Doc Details', 'help_docs' ),
    __( 'Help Doc Details', 'help_docs' ),
    self::HELP_DOCS_VIEW_CAP,
    'help-docs-info.php',
    array( self::class, 'help_docs_admin_page_info' )
);

// 2. Hide it from the layout view using a native structural wrapper
add_action( 'admin_head', function() {
    remove_submenu_page( 'help-docs.php', 'help-docs-info.php' );
});
    }

    /**
     * Help Docs Admin Main Page
     */
    public static function help_docs_admin_page(): void {
        if ( ! current_user_can( self::HELP_DOCS_VIEW_CAP ) ) {
            wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
        }

        $page_heading = self::get_help_docs_page_heading();
        ?>
        <div class="wrap help-docs-wrapper">
            <h1 class="wp-heading-inline"><?php
                if ( 'Help Docs' === $page_heading ) {
                    echo esc_html__( 'Welcome To Help Docs', 'help_docs' );
                } else {
                    echo esc_html( $page_heading );
                }
            ?></h1>
            <hr class="wp-header-end"/>
            <?php
            echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=help_docs' ) ) . '" class="button button-primary">' . esc_html__( 'New Help Doc', 'help_docs' ) . '</a></p>';
            echo '<ul class="help_pages">';

            $cache_key = 'help_docs_admin_list';
            $posts = get_transient( $cache_key );

            if ( false === $posts ) {
                $posts = get_posts( array(
                    'post_type'      => 'help_docs',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                    'no_found_rows'  => true,
                ) );
                set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
            }

            if ( $posts ) {
                foreach ( $posts as $post ) {
                    if ( ! self::user_can_view_help_doc( $post ) ) {
                        continue;
                    }

                    $link  = esc_url( add_query_arg( array( 'page' => 'help-docs-info.php', 'id' => $post->ID ), admin_url( 'admin.php' ) ) );
                    
                    // Fix 3: Gracefully handle structural display of untitled items
                    $raw_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( __( '(no title - ID: %d)', 'help_docs' ), $post->ID );
                    $title     = esc_html( apply_filters( 'the_title', $raw_title, $post->ID ) );
                    
                    echo '<li><a href="' . $link . '">' . $title . '</a></li>';
                }
            } else {
                echo '<li>' . esc_html__( 'No help documents found.', 'help_docs' ) . '</li>';
            }

            echo '</ul>';
            ?>
        </div>
        <?php
    }

   /**
     * Help Page Details
     */
    public static function help_docs_admin_page_info(): void {
        if ( ! current_user_can( self::HELP_DOCS_VIEW_CAP ) ) {
            wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
        }

        $page_heading = self::get_help_docs_page_heading();
        ?>
        <div class="wrap help-docs-wrapper">
            <h1><?php echo esc_html( $page_heading ); ?></h1>
            <hr class="wp-header-end"/>
            <?php
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=help-docs.php' ) ) . '" class="button button-large" aria-label="' . esc_attr( __( 'Back to Help Docs list', 'help_docs' ) ) . '">' . esc_html__( 'Back to Help Docs', 'help_docs' ) . '</a> <a href="' . esc_url( admin_url( 'post-new.php?post_type=help_docs' ) ) . '" class="button button-large" aria-label="' . esc_attr( __( 'Create a new Help Doc', 'help_docs' ) ) . '">' . esc_html__( 'New Help Doc', 'help_docs' ) . '</a></p>';
            echo '<div class="entry-content">';
            
            if ( isset( $_GET['id'] ) ) {
                $id   = absint( wp_unslash( $_GET['id'] ) );
                $post = get_post( $id );
                
                if ( $post && self::user_can_view_help_doc( $post ) ) {
                    $raw_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( __( '(no title - ID: %d)', 'help_docs' ), $post->ID );
                    echo '<h1>' . esc_html( apply_filters( 'the_title', $raw_title, $post->ID ) ) . '</h1>';
                    echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
                    
                    if ( current_user_can( 'edit_post', $id ) ) {
                        $edit_label = ! empty( $post->post_title ) ? get_the_title( $id ) : (string) $id;
                        echo '<p><a href="' . esc_url( get_edit_post_link( $id ) ) . '" class="button button-large" aria-label="' . esc_attr( sprintf( __( 'Edit: %s', 'help_docs' ), $edit_label ) ) . '">' . esc_html__( 'Edit', 'help_docs' ) . '</a></p>';
                    }
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
    public static function help_docs_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
        }

        $current_heading  = get_option( 'help_docs_page_heading', 'Help Docs' );
        $enable_gutenberg = get_option( 'help_docs_enable_gutenberg', false );
        ?>
        <div class="wrap help-docs-wrapper">
            <h1><?php esc_html_e( 'Help Docs Settings', 'help_docs' ); ?></h1>
            <hr class="wp-header-end"/>
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

    /**
     * Save settings handler.
     */
    public static function save_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
        }

        if ( ! isset( $_POST['help_docs_settings_nonce'] ) || ! wp_verify_nonce( $_POST['help_docs_settings_nonce'], 'help_docs_save_settings' ) ) {
            wp_die( esc_html__( 'Security check failed', 'help_docs' ) );
        }

        if ( isset( $_POST['help_docs_page_heading'] ) ) {
            update_option( 'help_docs_page_heading', sanitize_text_field( wp_unslash( $_POST['help_docs_page_heading'] ) ) );
        }

        $old_gutenberg_status = get_option( 'help_docs_enable_gutenberg', false );
        $new_gutenberg_status = isset( $_POST['help_docs_enable_gutenberg'] );

        update_option( 'help_docs_enable_gutenberg', $new_gutenberg_status );

        // Flush rewrite rules if REST visibility changes to prevent broken Gutenberg setups
        if ( $old_gutenberg_status !== $new_gutenberg_status ) {
            self::add_custom_post_type();
            flush_rewrite_rules();
        }

        set_transient( 'help_docs_settings_saved_' . get_current_user_id(), true, 60 );

        wp_safe_redirect( admin_url( 'admin.php?page=help-docs-settings' ) );
        exit;
    }

    /**
     * Clean cache anytime a post changes status.
     */
    public static function invalidate_post_cache_on_transition( string $new_status, string $old_status, WP_Post $post ): void {
        if ( 'help_docs' === $post->post_type ) {
            delete_transient( 'help_docs_admin_list' );
        }
    }

    /**
     * Set default status to private for new help_docs posts.
     */
    public static function default_post_status( string $post_status, string $post_type, WP_Post $post ): string {
        if ( 'help_docs' === $post_type && 'auto-draft' === $post_status ) {
            return 'private';
        }
        return $post_status;
    }

    /**
     * Force help_docs posts to always be private when saved.
     */
    public static function force_private_status( array $data, array $postarr ): array {
        if ( isset( $data['post_type'] ) && 'help_docs' === $data['post_type'] ) {
            if ( 'publish' === $data['post_status'] ) {
                $data['post_status'] = 'private';
            }
        }
        return $data;
    }

    /**
     * Enqueue admin styles on help-docs pages only.
     */
    public static function add_style( string $hook ): void {
        if ( false === stripos( $hook, 'help-docs' ) ) {
            return;
        }

        wp_enqueue_style( 'help-docs-style', HELP_DOCS_URL . 'style/style.css', array(), filemtime( HELP_DOCS_DIR . 'style/style.css' ) );

        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'help-docs-info.php' === $page ) {
            $post_type_object = get_post_type_object( 'help_docs' );
            if ( $post_type_object && ! empty( $post_type_object->show_in_rest ) ) {
                if ( wp_style_is( 'wp-block-library', 'registered' ) ) {
                    wp_enqueue_style( 'wp-block-library' );
                }
                if ( wp_style_is( 'wp-block-library-theme', 'registered' ) ) {
                    wp_enqueue_style( 'wp-block-library-theme' );
                }
            }
        }
    }

    /**
     * Restrict REST API access to the custom post type to logged-in users only.
     */
    public static function restrict_rest_auth( $result, $server, $request ) {
        if ( ! is_null( $result ) ) {
            return $result;
        }

        $route = $request->get_route();

        if ( preg_match( '#^/wp/v2/help_docs(?:/|$)#', $route ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( self::HELP_DOCS_VIEW_CAP ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    esc_html__( 'You do not have permission to access this resource.', 'help_docs' ),
                    array( 'status' => rest_authorization_required_code() )
                );
            }
        }

        return $result;
    }

    /**
     * Conditionally register REST auth filter after post type is registered.
     */
    public static function maybe_add_rest_auth(): void {
        $post_type_object = get_post_type_object( 'help_docs' );
        if ( $post_type_object && ! empty( $post_type_object->show_in_rest ) ) {
            add_filter( 'rest_pre_dispatch', array( self::class, 'restrict_rest_auth' ), 10, 3 );
        }
    }
}