<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Admin Enqueues
 * @since       1.0.0
 */

namespace Cooked\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Enqueues {

    /**
     * Admin color scheme colors.
     *
     * @var mixed
     */
    public static $admin_colors;

    /**
     * Admin screens that render inside the admin frame, keyed by page slug.
     *
     * Every screen is rendered on each of their pages (see Menus::cooked_app_page()),
     * so the frame switches between them without a page load.
     *
     * @since 2.0.0
     * @return array<string, array{title: string, template: string, classic_template: string, canSave: bool}>
     */
    public static function app_screens() {
        $screens = [
            'cooked_settings' => [
                'title'            => __( 'Settings', 'cooked' ),
                'template'         => 'frame/settings.php',
                // The settings form is shared: the classic layout shows its own header and tabs.
                'classic_template' => 'frame/settings.php',
                'canSave'          => true,
            ],
            'cooked_import'   => [
                'title'            => __( 'Import', 'cooked' ),
                'template'         => 'frame/import.php',
                'classic_template' => 'classic/import.php',
                'canSave'          => false,
            ],
            'cooked_welcome'  => [
                'title'            => __( 'What\'s New?', 'cooked' ),
                'template'         => 'frame/welcome.php',
                'classic_template' => 'classic/welcome.php',
                'canSave'          => false,
            ],
        ];
        if ( ! class_exists( 'Cooked_Pro_Plugin' ) ) {
            $screens['cooked_pro'] = [
                'title'            => __( 'Upgrade to Pro', 'cooked' ),
                'template'         => 'frame/pro.php',
                'classic_template' => 'classic/pro.php',
                'canSave'          => false,
            ];
        }
        return $screens;
    }

    /**
     * CSS for inside the recipe editor's TinyMCE iframes (the page styles cannot reach them):
     * the admin font instead of the default serif, the user's recipe editor dark mode, and for the
     * template editor, each top-level element (usually one shortcode per line) as a block.
     *
     * No double quotes: WordPress prints TinyMCE settings inside a double-quoted JavaScript string.
     *
     * @since 2.0.0
     * @param bool $template Whether this is the Recipe Template editor.
     * @return string
     */
    public static function recipe_editor_content_style( $template = false ) {
        $css = 'body{margin:12px 14px;color:#1e1e1e;font:14px/1.6 -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif}p{margin:0 0 10px}a{color:#007a55}';
        if ( $template ) {
            $css .= 'body{margin:12px}body>*{margin:0 0 8px;padding:8px 12px;border:1px solid #e3e5e8;border-radius:6px;background:#f6f7f8;color:#1e1e1e;font:13px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}body>ul,body>ol{padding-left:32px}';
        }

        $dark = 'body{background:#1f2328;color:#e4e6e8}a{color:#3dd6a3}';
        if ( $template ) {
            $dark .= 'body>*{border-color:#3a3f45;background:#262b31;color:#e4e6e8}';
        }

        $dark_mode = \Cooked_Settings::editor_dark_mode();
        if ( 'enabled' === $dark_mode ) {
            $css .= $dark;
        } elseif ( 'auto' === $dark_mode ) {
            $css .= '@media (prefers-color-scheme:dark){' . $dark . '}';
        }

        return $css;
    }

    /**
     * Whether the classic (pre-2.0) admin screens are selected in Settings > General.
     *
     * @since 2.0.0
     * @return bool
     */
    public static function use_classic_ui() {
        global $_cooked_settings;
        return ! empty( $_cooked_settings['admin_interface'] ) && in_array( 'classic', (array) $_cooked_settings['admin_interface'], true );
    }

    /**
     * Section menu entries (slug, name, icon) for a set of settings-style tabs.
     *
     * @since 2.0.0
     * @param array<string, array<string, mixed>> $tabs Tabs keyed by slug, each with 'name' and 'icon'.
     * @return array<int, array{slug: string, name: string, icon: string}>
     */
    private static function tab_sections( $tabs ) {
        $sections = [];
        foreach ( $tabs as $slug => $tab ) {
            $sections[] = [
                'slug' => $slug,
                'name' => $tab['name'],
                'icon' => $tab['icon'],
            ];
        }
        return $sections;
    }

    /**
     * Returns the frame screen slug for an admin page hook, or false.
     *
     * Matches on the `_page_{slug}` suffix: the prefix comes from the translated
     * "Recipes" menu title, so it differs per site language.
     *
     * @since 2.0.0
     * @param string $hook Admin page hook or screen ID.
     * @return string|false
     */
    private static function app_screen_for( $hook ) {
        if ( self::use_classic_ui() ) {
            return false;
        }
        foreach ( array_keys( self::app_screens() ) as $slug ) {
            if ( str_ends_with( $hook, '_page_' . $slug ) ) {
                return $slug;
            }
        }
        return false;
    }

    /**
     * Registers the admin asset hooks.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ &$this, 'register_shared_scripts' ], 5 );
        add_action( 'admin_enqueue_scripts', [ &$this, 'admin_enqueues' ], 10, 1 );
        add_action( 'admin_enqueue_scripts', [ &$this, 'widget_enqueues' ], 11, 1 );
        add_action( 'admin_enqueue_scripts', [ &$this, 'admin_app_enqueues' ], 20, 1 );
        add_action( 'customize_controls_enqueue_scripts', [ &$this, 'enqueue_widgets' ], 10, 1 );
        add_filter( 'admin_body_class', [ &$this, 'admin_body_class' ] );
    }

    /**
     * Adds a dedicated body class on the Cooked screens that use the admin frame.
     *
     * @since 2.0.0
     * @param string $classes Space-separated list of admin body classes.
     * @return string
     */
    public function admin_body_class( $classes ) {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return $classes;
        }

        // The recipe editor's new layout (templates/admin/frame/recipe-layout.php).
        $is_recipe_editor = 'cp_recipe' === $screen->post_type && 'post' === $screen->base && ! self::use_classic_ui();
        if ( $is_recipe_editor ) {
            $classes .= ' cooked-recipe-editor';
        }

        $is_frame_screen = (bool) self::app_screen_for( $screen->id );
        if ( $is_frame_screen ) {
            $classes .= ' cooked-admin-page';
        }

        if ( $is_frame_screen || $is_recipe_editor ) {
            // The user's own dark mode (Settings > Design): Admin Dark Mode for the frame screens,
            // Recipe Editor Dark Mode for the editor.
            $dark_mode = $is_recipe_editor ? \Cooked_Settings::editor_dark_mode() : \Cooked_Settings::admin_dark_mode();
            if ( 'enabled' === $dark_mode ) {
                $classes .= ' cooked-admin-dark';
            } elseif ( 'auto' === $dark_mode ) {
                $classes .= ' cooked-admin-dark-auto';
            }
        }
        return $classes;
    }

    /**
     * Registers Vue for Cooked Pro's Pending Recipes screen. Cooked itself no longer uses it.
     *
     * Runs before Cooked Pro's admin_enqueue_scripts callback, so Pro finds the handle registered
     * here instead of registering its own copy at the pre-2.0 path.
     *
     * @since 2.0.0
     */
    public function register_shared_scripts() {
        $min = COOKED_DEV ? '' : '.min';
        wp_register_script( 'cooked-vue', COOKED_URL . 'assets/vendor/vue/vue' . $min . '.js', [], '2.7.16', false );
    }

    /**
     * Loads the admin frame (header, page tabs, section menu) on Cooked's admin screens.
     *
     * @since 2.0.0
     * @param string $hook The current admin page hook.
     */
    public function admin_app_enqueues( $hook ) {
        $current = self::app_screen_for( $hook );
        if ( ! $current ) {
            return;
        }

        // Section menus: one entry per tab of the screen's fields (Settings, Import).
        $sections = [
            'cooked_settings' => self::tab_sections( \Cooked_Settings::tabs_fields() ),
            'cooked_import'   => self::tab_sections( \Cooked_Import::tabs_fields() ),
        ];
        if ( COOKED_DEV ) {
            $sections['cooked_settings'][] = [
				'slug' => 'developer',
				'name' => __( 'Developer', 'cooked' ),
				'icon' => 'code',
			];
        }

        $screens = [];
        foreach ( self::app_screens() as $slug => $screen ) {
            $screens[ $slug ] = [
                'title'    => $screen['title'],
                'url'      => admin_url( 'admin.php?page=' . $slug ),
                'canSave'  => $screen['canSave'],
                'sections' => isset( $sections[ $slug ] ) ? $sections[ $slug ] : [],
            ];
        }

        $pages = [
            [
				'slug'  => 'cooked_settings',
				'label' => __( 'Settings', 'cooked' ),
			],
            [
				'slug'  => 'cooked_import',
				'label' => __( 'Import', 'cooked' ),
			],
            [
				'slug'  => 'cooked_welcome',
				'label' => __( 'What\'s New?', 'cooked' ),
			],
        ];
        if ( ! class_exists( 'Cooked_Pro_Plugin' ) ) {
            $pages[] = [
				'slug'      => 'cooked_pro',
				'label'     => __( 'Upgrade to Pro', 'cooked' ),
				'highlight' => true,
			];
        }
        foreach ( $pages as &$page ) {
            $page['url'] = admin_url( 'admin.php?page=' . $page['slug'] );
        }
        unset( $page );

        wp_enqueue_style( 'wp-components' );
        self::register_tom_select();
        wp_enqueue_style( 'cooked-tom-select' );
        wp_enqueue_script( 'cooked-admin-app', COOKED_URL . 'assets/js/cooked-admin-app.js', [ 'wp-element', 'wp-components', 'cooked-admin-functions', 'cooked-tom-select' ], COOKED_VERSION, true );
        wp_localize_script(
            'cooked-admin-app',
            'cooked_admin_app_js_vars',
            [
				'version' => COOKED_VERSION,
				'docsUrl' => 'https://github.com/XjSv/Cooked/wiki',
				'current' => $current,
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonces'  => [
					'fullChangelog' => wp_create_nonce( 'cooked_full_changelog' ),
				],
				'screens' => $screens,
				'pages'   => $pages,
				'i18n'    => [
					'title'               => __( 'Cooked', 'cooked' ),
					'documentation'       => __( 'Documentation', 'cooked' ),
					'save'                => __( 'Update Settings', 'cooked' ),
					'pages'               => __( 'Cooked pages', 'cooked' ),
					'sections'            => __( 'Sections', 'cooked' ),
					'sectionsScrollLeft'  => __( 'Scroll sections left', 'cooked' ),
					'sectionsScrollRight' => __( 'Scroll sections right', 'cooked' ),
					'version'             => __( 'Version:', 'cooked' ),
					'changelog'           => __( 'Changelog', 'cooked' ),
					'changelogError'      => __( 'The changelog could not be loaded. Please try again.', 'cooked' ),
					/* translators: %s: a sample CSV file's name, e.g. "Small (1 recipe)" */
					'csvPreview'          => __( 'Sample CSV: %s', 'cooked' ),
					'csvError'            => __( 'The sample file could not be loaded. Please try again.', 'cooked' ),
					'csvImportError'      => __( 'This browser could not start the import. Download the file and upload it instead.', 'cooked' ),
					'download'            => __( 'Download', 'cooked' ),
					'importNow'           => __( 'Import Now', 'cooked' ),
					'searchLabel'         => __( 'Search settings', 'cooked' ),
					'searchPlaceholder'   => __( 'Search settings…', 'cooked' ),
					'searchEmpty'         => __( 'No settings found.', 'cooked' ),
					/* translators: %d: number of matching settings */
					'searchCount'         => __( '%d settings found. Use the up and down arrows to choose one.', 'cooked' ),
				],
			]
        );
    }

    /**
     * Registers Tom Select (searchable and styled select fields), used by the widgets'
     * recipe finder and the admin frame's settings selects.
     *
     * @since 2.0.0
     * @return void
     */
    private static function register_tom_select() {
        $min = COOKED_DEV ? '' : '.min';
        wp_register_style( 'cooked-tom-select', COOKED_URL . 'assets/vendor/tom-select/tom-select.default' . $min . '.css', [], '2.6.2' );
        wp_register_script( 'cooked-tom-select', COOKED_URL . 'assets/vendor/tom-select/tom-select.complete' . $min . '.js', [], '2.6.2', true );
    }

    /**
     * Loads the recipe finder assets (Tom Select) for the widget forms.
     *
     * @return void
     */
    public static function enqueue_widgets() {
        $cooked_js_vars = [
            'rest_url'   => esc_url( get_rest_url() ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        ];

        // Gonna need jQuery.
        wp_enqueue_script( 'jquery' );

        // Tom Select (searchable select fields).
        self::register_tom_select();
        wp_enqueue_style( 'cooked-tom-select' );
        wp_enqueue_style( 'cooked-admin', COOKED_URL . 'assets/css/admin.min.css', [ 'cooked-tom-select' ], COOKED_VERSION );

        // Cooked Widgets JS.
        wp_register_script( 'cooked-widgets', COOKED_URL . 'assets/js/cooked-widgets.js', [ 'jquery', 'cooked-tom-select' ], COOKED_VERSION, true );
        wp_localize_script( 'cooked-widgets', 'cooked_widgets_js_vars', $cooked_js_vars );
        wp_enqueue_script( 'cooked-widgets' );
    }

    /**
     * Loads the widget form assets on the Widgets screen.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function widget_enqueues( $hook ) {
        if ( 'widgets.php' === $hook ) {
            self::enqueue_widgets();
        }
    }

    /**
     * Loads Cooked's admin styles and scripts on its own screens and the recipe editor.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function admin_enqueues( $hook ) {
        global $_cooked_settings;

        $cooked_admin_hooks = [
            'index.php',
            'post-new.php',
            'post.php',
            'edit.php',
            'cooked_settings',
            'cooked_import',
            'cooked_welcome',
            'cooked_pending',
            'cooked_pro',
        ];

        $min = COOKED_DEV ? '' : '.min';

        // Required Assets for Entire Admin (icon font, admin menu icon).
        wp_enqueue_style( 'cooked-essentials', COOKED_URL . 'assets/css/essentials.min.css', [], COOKED_VERSION );

        $load_cooked_admin_assets = false;

        foreach ( $cooked_admin_hooks as $hook_slug ) {
            if ( strpos( $hook, $hook_slug ) || $hook_slug === $hook ) {
                $load_cooked_admin_assets = true;
            }
        }

        if ( $load_cooked_admin_assets ) {

            if ( function_exists( 'get_current_screen' ) ) :

                $screen    = get_current_screen();
                $post_type = $screen ? $screen->post_type : '';

                if ( ( 'post-new.php' !== $hook && 'post.php' !== $hook && 'index.php' !== $hook && 'edit.php' !== $hook ) || ( 'post-new.php' === $hook && 'cp_recipe' === $post_type ) || ( 'post.php' === $hook && 'cp_recipe' === $post_type ) || ( 'edit.php' === $hook && 'cp_recipe' === $post_type ) || 'index.php' === $hook ) : // widgets.php is covered by the first clause.
                    $enqueue = true;
                    add_thickbox();
                else :
                    $enqueue = false;
                endif;
            else :
                $enqueue = true;
            endif;

            if ( $enqueue ) :

                $old_recipes = get_transient( 'cooked_classic_recipes' );
                if ( $old_recipes && 'complete' !== $old_recipes && is_array( $old_recipes ) ) :
                    $total_old_recipes = count( $old_recipes );
                else :
                    $total_old_recipes = 0;
                endif;

                // Gonna need jQuery.
                wp_enqueue_media();
                wp_enqueue_editor();
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery-ui-core' );
                wp_enqueue_script( 'jquery-ui-draggable' );
                wp_enqueue_script( 'jquery-ui-resizable' );
                wp_enqueue_script( 'jquery-ui-sortable' );
                wp_enqueue_script( 'jquery-ui-slider' );

                wp_enqueue_script( 'cooked-conditions', COOKED_URL . 'assets/js/cooked-conditions.js', [ 'jquery', 'backbone' ], COOKED_VERSION, true );

                $wp_editor_roles_allowed = false;
                if ( is_user_logged_in() ) {
                    $user                    = wp_get_current_user();
                    $user_roles              = $user->roles;
                    $wp_editor_roles_allowed = isset( $_cooked_settings['recipe_wp_editor_roles'] ) && ! empty( array_intersect( $user_roles, $_cooked_settings['recipe_wp_editor_roles'] ) ) ? true : false;
                }

                $cooked_js_vars = [
                    'ajax_url'                             => admin_url( 'admin-ajax.php' ),
                    'cooked_plugin_url'                    => COOKED_URL,
                    'time_format'                          => get_option( 'time_format', 'g:ia' ),
                    'i18n_remaining'                       => __( 'remaining', 'cooked' ),
                    'i18n_image_title'                     => __( 'Add Image', 'cooked' ),
                    'i18n_image_change'                    => __( 'Change Image', 'cooked' ),
                    'i18n_image_button'                    => __( 'Use this Image', 'cooked' ),
                    'i18n_video_title'                     => __( 'Add Video', 'cooked' ),
                    'i18n_video_change'                    => __( 'Change Video', 'cooked' ),
                    'i18n_video_button'                    => __( 'Use this Video', 'cooked' ),
                    'i18n_gallery_image_title'             => __( 'Add to Gallery', 'cooked' ),
                    'i18n_edit_image_title'                => __( 'Edit Gallery Item', 'cooked' ),
                    'i18n_edit_image_button'               => __( 'Update Gallery Item', 'cooked' ),
                    'i18n_saved'                           => __( 'Saved', 'cooked' ),
                    'i18n_copied'                          => __( 'Copied', 'cooked' ),
                    'i18n_applied'                         => __( 'Applied', 'cooked' ),
                    'i18n_confirm_save_default_all'        => __( 'Are you sure you want to apply this new template to all of your recipes?', 'cooked' ),
                    'i18n_confirm_load_default'            => __( 'Are you sure you want to reset this recipe template to the Cooked plugin default?', 'cooked' ),
                    /* translators: confirmation for migrating all ### recipes, where ### displays the total number for the migration. */
                    'i18n_confirm_migrate_recipes'         => sprintf( __( 'Please confirm that you are ready to migrate all %s recipes.', 'cooked' ), number_format( $total_old_recipes ) ),
                    'i18n_confirm_import_recipes'          => __( 'Please confirm that you are ready to import all recipes.', 'cooked' ),
                    'i18n_confirm_csv_import'              => __( 'Are you sure you want to import recipes from this CSV file?', 'cooked' ),
                    'i18n_csv_no_file'                     => __( 'Please select a CSV file.', 'cooked' ),
                    'i18n_csv_invalid_file'                => __( 'Please select a valid CSV file.', 'cooked' ),
                    'i18n_uploading'                       => __( 'Uploading...', 'cooked' ),
                    'i18n_processing'                      => __( 'Processing...', 'cooked' ),
                    'i18n_recipes_imported'                => __( 'recipes imported', 'cooked' ),
                    'i18n_errors'                          => __( 'Errors:', 'cooked' ),
                    'i18n_import_failed'                   => __( 'Import failed.', 'cooked' ),
                    'i18n_failed_process_csv'              => __( 'Failed to process CSV file.', 'cooked' ),
                    'i18n_failed_upload_csv'               => __( 'Failed to upload CSV file.', 'cooked' ),
                    'i18n_file_upload_failed'              => __( 'File upload failed.', 'cooked' ),
                    'i18n_something_wrong'                 => __( 'Something went wrong', 'cooked' ),
                    'i18n_hrs'                             => __( 'hrs', 'cooked' ),
                    'i18n_mins'                            => __( 'mins', 'cooked' ),
                    'i18n_confirm_calculate_related'       => __( 'Pre-calculate related recipes for all published recipes? This may take a while on large sites.', 'cooked' ),
                    /* translators: 1: date and time, 2: number of recipes */
                    'i18n_last_calculated'                 => __( 'Last: %1$s · %2$s recipes', 'cooked' ),
                    'wp_editor_roles_allowed'              => esc_attr( $wp_editor_roles_allowed ),
                    'cooked_bulk_add_nonce'                => wp_create_nonce( 'cooked_bulk_add' ),
                    'cooked_import_nonce'                  => wp_create_nonce( 'cooked_admin_import' ),
                    'cooked_migrate_nonce'                 => wp_create_nonce( 'cooked_migrate_recipes' ),
                    'cooked_import_recipes_nonce'          => wp_create_nonce( 'cooked_import_recipes' ),
                    'cooked_load_default_nonce'            => wp_create_nonce( 'cooked_load_default' ),
                    'i18n_bulk_add_ingredients'            => __( 'Bulk Add Ingredients', 'cooked' ),
                    'i18n_bulk_add_directions'             => __( 'Bulk Add Directions', 'cooked' ),
                    'i18n_bulk_add_placeholder_ingredients' => __( "2 cups flour\n1 tsp salt\n1/2 cup sugar\n3 large eggs", 'cooked' ),
                    'i18n_bulk_add_placeholder_directions' => __( "Preheat oven to 350°F.\nMix dry ingredients together.\nAdd wet ingredients and stir.", 'cooked' ),
                    'i18n_bulk_add_section_heading'        => __( 'Section heading', 'cooked' ),
                    'i18n_bulk_add_parsing'                => __( 'Parsing...', 'cooked' ),
                    'i18n_bulk_add_submit_ingredients'     => __( 'Add Ingredients', 'cooked' ),
                    'i18n_bulk_add_submit_directions'      => __( 'Add Directions', 'cooked' ),
                    'i18n_bulk_add_amount'                 => __( 'Amount', 'cooked' ),
                    'i18n_bulk_add_unit'                   => __( 'Measurement', 'cooked' ),
                    'i18n_bulk_add_name'                   => __( 'Item', 'cooked' ),
                    'i18n_bulk_add_heading_line_label'     => __( 'Heading text', 'cooked' ),
                ];

                // Cooked Admin Style Assets
                // Tooltips (Floating UI, vendored — see assets/vendor/floating-ui/README.md).
                wp_register_script( 'cooked-floating-ui-core', COOKED_URL . 'assets/vendor/floating-ui/floating-ui.core.umd.min.js', [], '1.8.0', true );
                wp_register_script( 'cooked-floating-ui-dom', COOKED_URL . 'assets/vendor/floating-ui/floating-ui.dom.umd.min.js', [ 'cooked-floating-ui-core' ], '1.8.0', true );

                // Tom Select, for the frame screens and the recipe editor's new layout.
                self::register_tom_select();
                wp_register_script( 'cooked-admin-functions', COOKED_URL . 'assets/js/cooked-admin-functions' . $min . '.js', [ 'jquery', 'cooked-floating-ui-dom', 'cooked-tom-select' ], COOKED_VERSION, true );
                wp_enqueue_style( 'cooked-tom-select' );
                wp_enqueue_style( 'cooked-admin', COOKED_URL . 'assets/css/admin.min.css', [ 'cooked-tom-select' ], COOKED_VERSION );
                wp_enqueue_style( 'wp-color-picker' );

                // Cooked Admin Script.
                wp_localize_script( 'cooked-admin-functions', 'cooked_admin_functions_js_vars', $cooked_js_vars );
                wp_enqueue_script( 'cooked-admin-functions' );

                // The minified admin bundle already includes the migration script.
                if ( COOKED_DEV ) {
                    wp_enqueue_script( 'cooked-migration', COOKED_URL . 'assets/js/cooked-migration.js', [ 'cooked-admin-functions' ], COOKED_VERSION, true );
                }
            endif;
        }
    }
}
