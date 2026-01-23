<?php
/**
 * Post Types
 *
 * @package     Cooked
 * @subpackage  Post Types
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Cooked_Post_Types {

    function __construct() {
        register_activation_hook( COOKED_PLUGIN_FILE, [&$this, 'activation'] );

        add_action( 'init', [&$this, 'init'] );
        add_filter( 'admin_init', [&$this, 'init_roles'] );
        add_action( 'after_setup_theme', [&$this, 'image_sizes'] );
        add_action( 'wp_head', [&$this, 'cooked_meta_tags'], 5 );
        add_action( 'manage_cp_recipe_posts_custom_column', [&$this, 'custom_columns_data'], 10, 2 );

        add_filter( 'enter_title_here', [&$this, 'change_new_recipe_title'] );
        add_filter( 'query_vars', [&$this, 'add_query_vars_filter'] );
        add_filter( 'manage_cp_recipe_posts_columns', [&$this, 'custom_columns'] );
        add_filter( 'nav_menu_css_class', [&$this, 'cooked_nav_classes'], 10, 2 );
        add_filter( 'redirect_canonical', [&$this, 'disable_canonical_redirect'], 10, 2 );

        // Taxonomy Titles
        add_action( 'template_redirect', [&$this, 'remove_default_title_tag'] );
        add_filter( 'the_title', [&$this, 'taxonomy_page_title'], 10, 2 );
        add_filter( 'pre_wp_nav_menu', [&$this, 'disable_taxonomy_page_title'], 10, 2 );
        add_filter( 'wp_nav_menu_items', [&$this, 'enable_taxonomy_page_title'], 10, 2 );
        add_filter( 'wp_title', [&$this, 'taxonomy_meta_title'], 10 );

        // Add a post display state for special pages.
        add_filter( 'display_post_states', [&$this, 'add_display_post_states' ], 10, 2 );
    }

    function disable_taxonomy_page_title( $nav_menu, $args ) {
        remove_filter( 'the_title', [&$this, 'taxonomy_page_title'], 10 );
        return $nav_menu;
    }

    function enable_taxonomy_page_title( $items, $args ) {
        add_filter( 'the_title', [&$this, 'taxonomy_page_title'], 10, 2 );
        return $items;
    }

    function taxonomy_page_title( $title = '', $id = 0 ) {
        if ( is_admin() ) return $title;

        global $wp_query, $post, $_cooked_settings;
        $browse_page_id = Cooked_Multilingual::get_browse_page_id();

        if ( is_page( $browse_page_id ) && $id == $browse_page_id && isset($wp_query->query['cp_recipe_category']) && taxonomy_exists('cp_recipe_category') && term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ):
            $cooked_term = get_term_by( 'slug', $wp_query->query['cp_recipe_category'], 'cp_recipe_category' );
            return $cooked_term->name;
        endif;

        return $title;
    }

    function taxonomy_meta_title( $title = '' ) {
        global $wp_query, $post, $_cooked_settings;
        $browse_page_id = Cooked_Multilingual::get_browse_page_id();

        if ( is_page( $browse_page_id ) && $post->ID == $browse_page_id && isset($wp_query->query['cp_recipe_category']) && taxonomy_exists('cp_recipe_category') && term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ):
            $cooked_term = get_term_by( 'slug', $wp_query->query['cp_recipe_category'], 'cp_recipe_category' );
            return $cooked_term->name;
        endif;

        return $title;
    }

    function custom_columns( $columns ) {
          $new_columns = [];
        foreach( $columns as $key => $val ):
            $new_columns[$key] = $val;
            if ( $key == 'cb' ):
                $new_columns['featured_image'] = __( 'Photo', 'cooked' );
            endif;
        endforeach;
        return $new_columns;
    }

    function custom_columns_data( $column, $post_id ) {
        if ( $column == 'featured_image' ):
            echo '<span class="cooked-admin-recipes-list-image">';
                echo the_post_thumbnail( 'thumbnail' );
            echo '</span>';
        endif;
    }

    public static function cooked_nav_classes( $classes, $item ) {
        global $_cooked_settings;
        $blog_page_id = get_option( 'page_for_posts', false );
        $browse_page_id = Cooked_Multilingual::get_browse_page_id();

        if ( ( is_post_type_archive( 'cp_recipe' ) || is_singular( 'cp_recipe' ) )
             && $item->object_id == $blog_page_id ){
             $classes = array_diff( $classes, ['current_page_parent'] );
        }

        if ( ( is_post_type_archive( 'cp_recipe' ) || is_singular( 'cp_recipe' ) )
             && $item->object_id == $browse_page_id && is_array($classes) && !in_array( 'current_page_parent', $classes ) ){
             $classes[] = 'current_page_parent';
        }

        return $classes;
    }

    public static function cooked_meta_tags() {
        global $_cooked_settings, $post, $wp_query;

        if ( isset($_cooked_settings['advanced']) && !empty($_cooked_settings['advanced']) && in_array( 'disable_meta_tags', $_cooked_settings['advanced'] ) )
            return false;

        if ( isset($wp_query->query['cp_recipe_category']) && taxonomy_exists('cp_recipe_category') && term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ) {
            $cooked_term = get_term_by( 'slug', $wp_query->query['cp_recipe_category'], 'cp_recipe_category' );
        }

        // Browse page.
        if ( isset( $cooked_term ) && $cooked_term->name ) {
            ?><title><?php echo esc_html($cooked_term->name) . ' / ' . esc_html(get_bloginfo('name')); ?></title>
            <meta name="description" content="<?php echo esc_attr( $cooked_term->description ); ?>">
            <meta property="og:title" content="<?php echo esc_attr( $cooked_term->name ); ?>">
            <meta property="og:description" content="<?php echo esc_attr( $cooked_term->description ); ?>"><?php
        }

        // Single recipe.
        if ( isset( $post->post_type ) && $post->post_type == 'cp_recipe' ) {
            ob_start();

            $recipe = get_post( $post->ID );
            $recipe_settings = Cooked_Recipes::get( $post->ID, true );
            $image_url = false;

            if ( has_post_thumbnail($recipe) ) {
                $image_url = get_the_post_thumbnail_url( $recipe, 'cooked-large' );
            }

            $description = '';
            if (!empty($recipe_settings['seo_description'])):
                $description = wp_strip_all_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $recipe_settings['seo_description']) ); ;
            elseif (!empty($recipe_settings['excerpt'])):
                $description = wp_strip_all_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $recipe_settings['excerpt']) );
            elseif (!empty($recipe_settings['title'])):
                $description = $recipe_settings['title'];
            endif;
            ?>

            <meta name="description" content="<?php echo esc_attr( $description ); ?>">
            <meta property="og:type" content="website">
            <meta property="og:title" content="<?php echo esc_attr( $post->post_title ); ?>">
            <meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
            <meta property="og:image" content="<?php echo esc_attr( $image_url ); ?>">
            <meta property="og:locale" content="<?php echo esc_attr( get_locale() ); ?>">
            <meta property="og:url" content="<?php echo get_permalink( $post->ID ); ?>"><?php

            echo ob_get_clean();
        }
    }

    public static function add_query_vars_filter( $vars ) {
        $vars[] = 'servings';

        return $vars;
    }

    public function remove_default_title_tag() {
        global $wp_query;
        if ( isset($wp_query->query['cp_recipe_category']) && taxonomy_exists('cp_recipe_category') && term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ) {
            remove_action( 'wp_head', '_wp_render_title_tag', 1 );
        }
    }

    public static function activation() {
        self::init();
        self::init_roles();
        flush_rewrite_rules();
    }

    public static function init_roles() {
        // Clean up for any old caps or caps that were inserted incorrectly.
        if ( $role_object = get_role( 'subscriber' ) ) {
            if ( $role_object->has_cap( 'edit_cp_recipes' ) ) {
                Cooked_Roles::clean_caps();
            }
        }

        Cooked_Roles::add_roles();
        Cooked_Roles::add_caps();
    }

    public static function init() {
        $_cooked_settings = Cooked_Settings::get();
        $_cooked_taxonomies = Cooked_Taxonomies::get();

        // Security check: Only allow settings update from admin area with proper permissions
        if (!empty($_GET['settings-updated']) && is_admin() && current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'cooked_settings') {
            // Recipe Permalink
            $permalink_parts = explode( '/', $_cooked_settings['recipe_permalink'] );
            if ( isset( $permalink_parts[1] ) ):
                foreach ( $permalink_parts as $key => $part ):
                    $part = sanitize_title_with_dashes( $part, null, 'save');
                    $permalink_parts[$key] = sanitize_title_with_dashes( $part, null, 'save');
                endforeach;
                $recipe_permalink = implode( '/', $permalink_parts );
            else:
                $recipe_permalink = sanitize_title_with_dashes( $_cooked_settings['recipe_permalink'], null, 'save');
            endif;

            // Recipe Author Permalink
            $permalink_parts = explode( '/', $_cooked_settings['recipe_author_permalink'] );
            if ( isset( $permalink_parts[1] ) ):
                foreach( $permalink_parts as $key => $part ):
                    $part = sanitize_title_with_dashes( $part, null, 'save');
                    $permalink_parts[$key] = sanitize_title_with_dashes( $part, null, 'save');
                endforeach;
                $recipe_author_permalink = implode( '/', $permalink_parts );
            else:
                $recipe_author_permalink = sanitize_title_with_dashes( $_cooked_settings['recipe_author_permalink'], null, 'save');
            endif;

            // Recipe Category Permalink
            $permalink_parts = explode( '/', $_cooked_settings['recipe_category_permalink'] );
            if ( isset( $permalink_parts[1] ) ):
                foreach ( $permalink_parts as $key => $part ):
                    $part = sanitize_title_with_dashes( $part, null, 'save');
                    $permalink_parts[$key] = sanitize_title_with_dashes( $part, null, 'save');
                endforeach;
                $recipe_category_permalink = implode( '/', $permalink_parts );
            else:
                $recipe_category_permalink = sanitize_title_with_dashes( $_cooked_settings['recipe_category_permalink'], null, 'save');
            endif;

            $taxonomy_settings_update = apply_filters( 'cooked_taxonomy_settings_update', [
                'recipe_permalink' => (!$_cooked_settings['recipe_permalink'] ? 'recipes' : $recipe_permalink),
                'recipe_author_permalink' => (!$_cooked_settings['recipe_author_permalink'] || 'author' == $_cooked_settings['recipe_author_permalink'] ? 'recipe-author' : $recipe_author_permalink),
                'recipe_category_permalink' => (!$_cooked_settings['recipe_category_permalink'] ? 'recipe-category' : $recipe_category_permalink)
            ]);

            foreach ( $taxonomy_settings_update as $setting_key => $setting_value ) {
                $_cooked_settings[ $setting_key ] = $setting_value;
            }

            update_option( 'cooked_settings', $_cooked_settings );
            update_option( 'cooked_settings_saved', true );

            flush_rewrite_rules();
        }

        global $cooked_taxonomies_for_menu;

        // Register taxonomies first (only once)
        if ( !empty($_cooked_taxonomies) ) {
            foreach ( $_cooked_taxonomies as $slug => $args ) {
                register_taxonomy( $slug, ['cp_recipe'], $args );
                add_rewrite_tag("%{$slug}%", '([^/]+)');

                $cooked_taxonomies_for_menu[] = [
                    'menu' => 'cooked_recipes_menu',
                    'name' => $args['labels']['menu_name'],
                    'capability' => 'manage_categories',
                    'url' => 'edit-tags.php?taxonomy=' . $slug . '&post_type=cp_recipe'
                ];
            }
        }

        // Get all browse page translations (including default)
        $browse_pages = Cooked_Multilingual::get_all_browse_pages();

        // Create rewrite rules for each browse page translation
        foreach ( $browse_pages as $lang => $page_data ) {
            self::add_browse_page_rewrite_rules( $page_data['id'], $page_data['slug'], $_cooked_taxonomies );
        }

        add_rewrite_tag('%cooked_search_s%', '([^&]+)');
        add_rewrite_tag('%cooked_browse_sort_by%', '([^&]+)');

        $post_types = self::get();
        if ( !empty($post_types) ) {
            foreach ( $post_types as $slug => $args ) {
                register_post_type( $slug, $args );
            }
        }
    }

    /**
     * Add rewrite rules for a specific browse page
     *
     * @param int         $page_id   The browse page ID
     * @param string|null $page_slug The browse page slug/path (empty string for homepage, null if invalid)
     * @param array       $taxonomies The registered taxonomies
     */
    private static function add_browse_page_rewrite_rules( $page_id, $page_slug, $taxonomies ) {
        // Page ID is required, but slug can be empty string (for homepage)
        if ( ! $page_id || $page_slug === null ) {
            return;
        }

        // Get base path - either parent page slug or empty (for homepage)
        $base_path = $page_slug !== '' ? $page_slug . '/' : '';

        // Add taxonomy rewrite rules
        if ( !empty($taxonomies) ) {
            foreach ( $taxonomies as $slug => $args ) {
                // Taxonomy search sort pagination
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/search/([^/]*)/sort/([^/]*)/page/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]&cooked_search_s=$matches[2]&cooked_browse_sort_by=$matches[3]&paged=$matches[4]',
                    'top'
                );

                // Taxonomy search sort
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/search/([^/]*)/sort/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]&cooked_search_s=$matches[2]&cooked_browse_sort_by=$matches[3]',
                    'top'
                );

                // Taxonomy sort pagination
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/sort/([^/]*)/page/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]&cooked_browse_sort_by=$matches[2]&paged=$matches[3]',
                    'top'
                );

                // Taxonomy sort
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/sort/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]&cooked_browse_sort_by=$matches[2]',
                    'top'
                );

                // Taxonomy search
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/search/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]&cooked_search_s=$matches[2]',
                    'top'
                );

                // Taxonomy pagination
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/page/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&paged=$matches[2]&' . $slug . '=$matches[1]',
                    'top'
                );

                // Taxonomy
                add_rewrite_rule(
                    '^' . $base_path . $args['rewrite']['slug'] . '/([^/]*)/?',
                    'index.php?page_id=' . $page_id . '&' . $slug . '=$matches[1]',
                    'top'
                );
            }
        }

        // Search sort pagination
        add_rewrite_rule(
            '^' . $base_path . 'search/([^/]*)/sort/([^/]*)/page/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&cooked_search_s=$matches[1]&cooked_browse_sort_by=$matches[2]&paged=$matches[3]',
            'top'
        );

        // Search sort
        add_rewrite_rule(
            '^' . $base_path . 'search/([^/]*)/sort/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&cooked_search_s=$matches[1]&cooked_browse_sort_by=$matches[2]',
            'top'
        );

        // Sort Pagination
        add_rewrite_rule(
            '^' . $base_path . 'sort/([^/]*)/page/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&cooked_browse_sort_by=$matches[1]&paged=$matches[2]',
            'top'
        );

        // Sort
        add_rewrite_rule(
            '^' . $base_path . 'sort/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&cooked_browse_sort_by=$matches[1]',
            'top'
        );

        // Search
        add_rewrite_rule(
            '^' . $base_path . 'search/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&cooked_search_s=$matches[1]',
            'top'
        );

        // Pagination
        add_rewrite_rule(
            '^' . $base_path . 'page/([^/]*)/?',
            'index.php?page_id=' . $page_id . '&paged=$matches[1]',
            'top'
        );

        // Plain - only add for non-homepage pages (WordPress handles homepage already)
        if ( $page_slug !== '' ) {
            add_rewrite_rule(
                '^' . $page_slug . '/?$',
                'index.php?page_id=' . $page_id,
                'top'
            );
        }
    }

    public static function image_sizes() {
        add_image_size( 'cooked-profile-photo', 150, 150, true );
        add_image_size( 'cooked-square', 700, 700, true );
        add_image_size( 'cooked-medium', 700, 525, true );
        add_image_size( 'cooked-large', 2000, 2000 );
    }

    public static function get() {
        global $_cooked_settings;

        $recipe_permalink = isset($_cooked_settings['recipe_permalink']) && $_cooked_settings['recipe_permalink'] ? $_cooked_settings['recipe_permalink'] : 'recipes';
        $public_recipes = true;
        $has_archive_slug = sanitize_title_with_dashes( __('Recipe Archive', 'cooked') );
        $exclude_from_search = false;

        if ( !isset($_GET['print']) && isset( $_cooked_settings['advanced'] ) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'] ) ) {
            $public_recipes = false;
            $has_archive_slug = false;
            $exclude_from_search = true;
        }

        if ( isset( $_cooked_settings['advanced'] ) && in_array( 'disable_cp_recipe_archive', $_cooked_settings['advanced'] ) ) {
            $has_archive_slug = false;
        }

        $post_types = apply_filters( 'cooked_post_types', [
                'cp_recipe' => [
                    'labels' => [
                        'name' => _x('Recipes', 'cooked'),
                        'singular_name' => _x('Recipe', 'cooked'),
                        'menu_name' => __('Recipes', 'cooked'),
                        'name_admin_bar' => __('Recipe', 'cooked'),
                        'add_new' => __('Add New', 'cooked'),
                        'add_new_item' => __('Add New Recipe', 'cooked'),
                        'new_item' => __('New Recipe', 'cooked'),
                        'edit_item' => __('Edit Recipe', 'cooked'),
                        'view_item' => __('View Recipe', 'cooked'),
                        'all_items' => __('All Recipes', 'cooked'),
                        'search_items' => __('Search Recipes', 'cooked'),
                        'not_found' => __('No recipes found.', 'cooked'),
                        'not_found_in_trash' => __('No recipes found in trash.', 'cooked')
                    ],
                    'description' => __('Recipes', 'cooked'),
                    'public' => $public_recipes,
                    'show_ui' => true,
                    'show_in_admin_bar' => true,
                    'show_in_menu' => 'cooked_recipes_menu',
                    'show_in_rest' => true,
                    'rest_base' => 'cooked_recipe',
                    'rest_controller_class' => 'WP_REST_Posts_Controller',
                    'exclude_from_search' => $exclude_from_search,
                    'has_archive' => $has_archive_slug,
                    'menu_position' => 25,
                    'supports' => ['title', 'editor', 'thumbnail', 'comments', 'author'],
                    'rewrite' => [
                        'with_front' => false,
                        'slug' => $recipe_permalink
                    ]
                ]
            ]
        );

        return $post_types;
    }

    public function change_new_recipe_title($title) {
        $screen = get_current_screen();
        if  ('cp_recipe' == $screen->post_type) {
            $title = __('Recipe title ...', 'cooked');
        }

        return $title;
    }

    /**
     * Add a post display state for special Cooked pages in the page list table.
     *
     * @param array   $post_states An array of post display states.
     * @param WP_Post $post        The current post object.
     */
    public function add_display_post_states( $post_states, $post ) {
        global $_cooked_settings;

        // Check both the main browse page and any translations
        $main_browse_page_id = !empty($_cooked_settings['browse_page']) ? $_cooked_settings['browse_page'] : false;
        $browse_pages = Cooked_Multilingual::get_all_browse_pages();

        // Check if this post is the main browse page or any translation
        $is_browse_page = ( $main_browse_page_id == $post->ID );
        if ( ! $is_browse_page && ! empty( $browse_pages ) ) {
            foreach ( $browse_pages as $lang => $page_data ) {
                if ( $page_data['id'] == $post->ID ) {
                    $is_browse_page = true;
                    break;
                }
            }
        }

        if ( $is_browse_page ) {
            $post_states['cooked_page_for_browse_recipes'] = __( 'Cooked Browse Recipes Page', 'cooked' );
        }

        return $post_states;
    }

    /**
     * Disable canonical redirects for Cooked URLs on the homepage
     *
     * @param string $redirect_url The redirect URL
     * @param string $requested_url The originally requested URL
     * @return string|bool The redirect URL or false to prevent redirect
     */
    public function disable_canonical_redirect($redirect_url, $requested_url) {
        global $_cooked_settings;
        $_cooked_taxonomies = Cooked_Taxonomies::get();

        // Only process if this is the homepage
        if (!is_front_page()) {
            return $redirect_url;
        }

        // Check if any Cooked query vars are present
        $cooked_query_vars = [
            'cooked_search_s',
            'cooked_browse_sort_by',
            'paged'
        ];

        // Add taxonomy query vars
        if (!empty($_cooked_taxonomies)) {
            foreach ( $_cooked_taxonomies as $slug => $args ) {
                $cooked_query_vars[] = $slug;
            }
        }

        foreach ($cooked_query_vars as $var) {
            if (get_query_var($var)) {
                return false;
            }
        }

        return $redirect_url;
    }

}
