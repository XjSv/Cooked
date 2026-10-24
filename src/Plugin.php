<?php
/**
 * Main plugin class
 *
 * @package     Cooked
 * @subpackage  Core
 * @since       1.0.0
 */

namespace Cooked;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Class.
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * The single plugin instance.
     *
     * @var \Cooked_Plugin
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Cooked Roles Object.
     *
     * @var object|\Cooked_Roles
     * @since 1.0.0
     */
    public $roles;

    /**
     * Cooked Admin Menus Object.
     *
     * @var object|\Cooked_Admin_Menus
     * @since 1.0.0
     */
    public $admin_menus;

    /**
     * Cooked Admin Enqueues Object.
     *
     * @var object|\Cooked_Admin_Enqueues
     * @since 1.0.0
     */
    public $admin_enqueues;

    /**
     * Cooked Enqueues Object.
     *
     * @var object|\Cooked_Enqueues
     * @since 1.0.0
     */
    public $enqueues;

    /**
     * Cooked Settings Object.
     *
     * @var object|\Cooked_Settings
     * @since 1.0.0
     */
    public $admin_settings;

    /**
     * Cooked Migration Object.
     *
     * @var object|\Cooked_Migration
     * @since 1.0.0
     */
    public $migration;

    /**
     * Cooked Updates Object.
     *
     * @var object|\Cooked_Updates
     * @since 1.0.0
     */
    public $updates;

    /**
     * Cooked Post Types Object.
     *
     * @var object|\Cooked_Post_Types
     * @since 1.0.0
     */
    public $post_types;

    /**
     * Cooked Recipe Meta Object.
     *
     * @var object|\Cooked_Recipe_Meta
     * @since 1.0.0
     */
    public $recipe_meta;

    /**
     * Cooked Measurements Object.
     *
     * @var object|\Cooked_Measurements
     * @since 1.0.0
     */
    public $measurements;

    /**
     * Cooked Users Object.
     *
     * @var object|\Cooked_Users
     * @since 1.0.0
     */
    public $users;

    /**
     * Cooked Recipes Object.
     *
     * @var object|\Cooked_Recipes
     * @since 1.0.0
     */
    public $recipes;

    /**
     * Cooked Shortcodes Object.
     *
     * @var object|\Cooked_Shortcodes
     * @since 1.0.0
     */
    public $shortcodes;

    /**
     * Cooked Ajax Object.
     *
     * @var object|\Cooked_Ajax
     * @since 1.0.0
     */
    public $ajax;

    /**
     * Cooked Functions Object.
     *
     * @var object|\Cooked_Functions
     * @since 1.0.0
     */
    public $functions;

    /**
     * Cooked Widgets Object.
     *
     * @var object|\Cooked_Widgets
     * @since 1.0.0
     */
    public $widget;

    /**
     * Cooked Gutenberg Object.
     *
     * @var object|\Cooked_Gutenberg
     * @since 1.0.0
     */
    public $gutenberg;

    /**
     * Cooked Elementor Object.
     *
     * @var object|\Cooked_Elementor
     * @since 1.0.0
     */
    public $elementor;

    /**
     * Cooked Rank Math SEO Object.
     *
     * @var object|\Cooked_RankMathSEO
     * @since 1.8.9
     */
    public $rankmathseo;

    /**
     * Cooked Yoast SEO Object.
     *
     * @var object|\Cooked_YoastSEO
     * @since 1.8.9
     */
    public $yoastseo;

    /**
     * Cooked Multilingual Object.
     *
     * @var object|\Cooked_Multilingual
     * @since 1.12.0
     */
    public $multilingual;

    /**
     * Cooked Related Recipes Object.
     *
     * @var object|\Cooked_Related_Recipes
     * @since 1.12.0
     */
    public $related_recipes;

    /**
     * Cooked Extra Object.
     *
     * @var object|\Cooked_Plugin_Extra
     * @since 1.8.7
     */
    public $extra;

    /**
     * Cooked Pixwell Theme Support Object.
     *
     * @var object|\Cooked_Pixwell
     * @since 1.16.0
     */
    public $pixwell;

    /**
     * Cooked Pro version check Object.
     *
     * @var object|\Cooked\Admin\ProCompat
     * @since 2.0.0
     */
    public $pro_compat;

    /**
     * Main Cooked_Plugin Instance.
     *
     * Insures that only one instance of Cooked_Plugin exists in memory at any one
     * time. Also prevents needing to define globals everywhere.
     *
     * @since 1.0.0
     * @static
     * @staticvar array $instance
     * @uses Cooked_Plugin::setup_constants() Setup the constants needed.
     * @uses Cooked_Plugin::includes() Include the required files.
     * @uses Cooked_Plugin::load_textdomain() load the language files.
     * @see Cooked()
     * @return object|\Cooked_Plugin
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof \Cooked_Plugin ) ) {
            self::$instance = new \Cooked_Plugin();
            self::$instance->setup_constants();

            add_action( 'init', [ self::$instance, 'load_textdomain' ] );
            add_action( 'plugins_loaded', [ self::$instance, 'check_plugin_update' ] );

            if ( COOKED_DEV ) {
                add_filter( 'style_loader_src', [ self::class, 'dev_asset_version' ] );
                add_filter( 'script_loader_src', [ self::class, 'dev_asset_version' ] );
            }

            self::$instance->includes();
            self::$instance->roles       = new \Cooked_Roles();
            self::$instance->admin_menus = new \Cooked_Admin_Menus();

            if ( is_admin() ) {
                self::$instance->admin_enqueues = new \Cooked_Admin_Enqueues();
            } else {
                self::$instance->enqueues = new \Cooked_Enqueues();
            }

            self::$instance->admin_settings  = new \Cooked_Settings();
            self::$instance->migration       = new \Cooked_Migration();
            self::$instance->related_recipes = new \Cooked_Related_Recipes();
            self::$instance->updates         = new \Cooked_Updates();
            self::$instance->post_types      = new \Cooked_Post_Types();
            self::$instance->recipe_meta     = new \Cooked_Recipe_Meta();
            self::$instance->measurements    = new \Cooked_Measurements();
            self::$instance->users           = new \Cooked_Users();
            self::$instance->recipes         = $GLOBALS['Cooked_Recipes'];
            self::$instance->shortcodes      = new \Cooked_Shortcodes();
            self::$instance->ajax            = new \Cooked_Ajax();
            self::$instance->functions       = new \Cooked_Functions();
            self::$instance->widget          = new \Cooked_Widgets();
            self::$instance->gutenberg       = new \Cooked_Gutenberg();
            self::$instance->elementor       = new \Cooked_Elementor();
            self::$instance->extra           = new \Cooked_Plugin_Extra();
            self::$instance->multilingual    = new \Cooked_Multilingual();
            self::$instance->pixwell         = new \Cooked_Pixwell();
            self::$instance->pro_compat      = new \Cooked\Admin\ProCompat();

            self::$instance->module_setup();

            add_action( 'init', [ self::$instance, 'initialize_plugin_support' ], 10 );

            do_action( 'cooked_loaded' );
        }

        return self::$instance;
    }

    /**
     * Initialize module support.
     *
     * @since 1.0.0
     * @return void
     */
    private function module_setup() {
        // Look for Cooked Modules.
        $modules = file_exists( COOKED_DIR . 'modules' ) ? scandir( COOKED_DIR . 'modules' ) : false;
        if ( $modules ) :
            foreach ( $modules as $module_name ) :
                if ( '.' !== $module_name && '..' !== $module_name ) :
                    if ( file_exists( COOKED_DIR . 'modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ) :
                        require_once COOKED_DIR . 'modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                    endif;
                endif;
            endforeach;
        endif;

        // Look for Cooked Modules in Parent Theme.
        $parent_theme_folder = trailingslashit( get_template_directory() );
        $modules             = file_exists( $parent_theme_folder . 'cooked-modules' ) ? scandir( $parent_theme_folder . 'cooked-modules' ) : false;
        if ( $modules ) :
            foreach ( $modules as $module_name ) :
                if ( '.' !== $module_name && '..' !== $module_name ) :
                    if ( file_exists( $parent_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ) :
                        require_once $parent_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                    endif;
                endif;
            endforeach;
        endif;

        // Look for Cooked Modules in Child Theme (if one is active).
        $child_theme_folder = trailingslashit( get_stylesheet_directory() );
        if ( $child_theme_folder !== $parent_theme_folder ) :
            $modules = file_exists( $child_theme_folder . 'cooked-modules' ) ? scandir( $child_theme_folder . 'cooked-modules' ) : false;
            if ( $modules ) :
                foreach ( $modules as $module_name ) :
                    if ( '.' !== $module_name && '..' !== $module_name ) :
                        if ( file_exists( $child_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ) :
                            require_once $child_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                        endif;
                    endif;
                endforeach;
            endif;
        endif;
    }

    /**
     * Initialize plugin support.
     *
     * @since 1.0.0
     * @return void
     */
    public function initialize_plugin_support() {
        if ( in_array( 'wordpress-seo/wp-seo.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            self::$instance->yoastseo = new \Cooked_YoastSEO();
        }

        if ( in_array( 'seo-by-rank-math/rank-math.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            self::$instance->rankmathseo = new \Cooked_RankMathSEO();
        }
    }

    /**
     * Check for plugin updates and run migrations if needed
     *
     * @since 1.10.0
     */
    public function check_plugin_update() {
        $current_version = get_option( 'cooked_version', '0' );

        if ( version_compare( $current_version, COOKED_VERSION, '<' ) ) {
            // Run migrations.
            $this->migration->init();

            // Update version in database.
            update_option( 'cooked_version', COOKED_VERSION );
        }
    }

    /**
     * Throw error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object therefore, we don't want the object to be cloned.
     *
     * @since 1.0.0
     * @access protected
     * @return void
     */
    public function __clone() {
        // Nope, can't do that.
    }

    /**
     * Disable unserializing of the class.
     *
     * @since 1.0.0
     * @access protected
     * @return void
     */
    public function __wakeup() {
        // Nope, can't do that either.
    }

    /**
     * The main plugin file (cooked.php), which the path and URL constants are based on.
     *
     * @since 2.0.0
     * @return string
     */
    private static function main_file() {
        return dirname( __DIR__ ) . '/cooked.php';
    }

    /**
     * Setup plugin constants.
     *
     * @access private
     * @since 1.0.0
     * @return void
     */
    private function setup_constants() {
        // Plugin Folder Path.
        if ( ! defined( 'COOKED_DIR' ) ) {
            define( 'COOKED_DIR', plugin_dir_path( self::main_file() ) );
        }

        // Plugin Folder Name.
        if ( ! defined( 'COOKED_FOLDER' ) ) {
            $foldername = untrailingslashit( str_replace( 'cooked.php', '', plugin_basename( self::main_file() ) ) );
            define( 'COOKED_FOLDER', $foldername );
        }

        // Plugin Root File.
        if ( ! defined( 'COOKED_PLUGIN_FILE' ) ) {
            define( 'COOKED_PLUGIN_FILE', self::main_file() );
        }

        // Plugin Folder URL.
        if ( ! defined( 'COOKED_URL' ) ) {
            define( 'COOKED_URL', plugin_dir_url( self::main_file() ) );
        }

        // WordPress Ajax URL.
        if ( ! defined( 'COOKED_AJAX_URL' ) ) {
            define( 'COOKED_AJAX_URL', admin_url( 'admin-ajax.php' ) );
        }

        // Make sure CAL_GREGORIAN is defined.
        if ( ! defined( 'CAL_GREGORIAN' ) ) {
            define( 'CAL_GREGORIAN', 1 );
        }

        // Time Format.
        if ( ! defined( 'COOKED_TIME_FORMAT' ) ) {
            define( 'COOKED_TIME_FORMAT', get_option( 'time_format', 'g:ia' ) );
        }

        // Date Format.
        if ( ! defined( 'COOKED_DATE_FORMAT' ) ) {
            define( 'COOKED_DATE_FORMAT', get_option( 'date_format', 'F j, Y' ) );
        }
    }

    /**
     * Include required files.
     *
     * @access private
     * @since 1.0.0
     * @return void
     */
    private function includes() {
        global $Cooked_Recipes;
        $Cooked_Recipes = new \Cooked_Recipes();
        require_once COOKED_DIR . 'src/functions.php';
        add_action( 'init', [ 'Cooked_Allergens', 'init' ] );

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'cooked tools', 'Cooked_CLI_Tools_Command' );
        }
    }

    /**
     * With COOKED_DEV on, version Cooked's own CSS and JS by their modification time so the
     * browser picks up every rebuild instead of a cached copy.
     *
     * @since 2.0.0
     * @param string $src Asset URL.
     * @return string
     */
    public static function dev_asset_version( $src ) {
        if ( ! is_string( $src ) || ! str_starts_with( $src, COOKED_URL ) ) {
            return $src;
        }

        $path = COOKED_DIR . strtok( substr( $src, strlen( COOKED_URL ) ), '?' );

        return file_exists( $path ) ? add_query_arg( 'ver', filemtime( $path ), $src ) : $src;
    }

    /**
     * Loads the plugin language files.
     *
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain() {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Bundled translations.
        load_plugin_textdomain( 'cooked', false, COOKED_FOLDER . '/languages' );
    }
}
