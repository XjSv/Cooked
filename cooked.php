<?php

/*
Plugin Name: 	Cooked - Recipe Management
Plugin URI: 	https://wordpress.org/plugins/cooked/
Description: 	A recipe plugin for WordPress.
Author: 		Gora Tech
Author URI: 	https://goratech.dev
Version: 		1.8.9
Text Domain: 	cooked
Domain Path: 	languages
License:     	GPL2

Cooked is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Cooked is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Cooked. If not, see http://www.gnu.org/licenses/.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/vendor/autoload.php';

define( 'COOKED_VERSION', '1.8.9' );
define( 'COOKED_DEV', false );

if ( ! class_exists( 'Cooked_Plugin' ) ) :

/**
 * Cooked_Plugin Class.
 *
 * @since 1.0.0
 */
final class Cooked_Plugin {

    /**
     * @var Cooked_Plugin
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Cooked Roles Object.
     *
     * @var object|Cooked_Roles
     * @since 1.0.0
     */
    public $roles;

    /**
     * Cooked Admin Menus Object.
     *
     * @var object|Cooked_Admin_Menus
     * @since 1.0.0
     */
    public $admin_menus;

    /**
     * Cooked Admin Enqueues Object.
     *
     * @var object|Cooked_Admin_Enqueues
     * @since 1.0.0
     */
    public $admin_enqueues;

    /**
     * Cooked Enqueues Object.
     *
     * @var object|Cooked_Enqueues
     * @since 1.0.0
     */
    public $enqueues;

    /**
     * Cooked Settings Object.
     *
     * @var object|Cooked_Settings
     * @since 1.0.0
     */
    public $admin_settings;

    /**
     * Cooked Migration Object.
     *
     * @var object|Cooked_Migration
     * @since 1.0.0
     */
    public $migration;

    /**
     * Cooked Post Types Object.
     *
     * @var object|Cooked_Post_Types
     * @since 1.0.0
     */
    public $post_types;

    /**
     * Cooked Recipe Meta Object.
     *
     * @var object|Cooked_Recipe_Meta
     * @since 1.0.0
     */
    public $recipe_meta;

    /**
     * Cooked Measurements Object.
     *
     * @var object|Cooked_Measurements
     * @since 1.0.0
     */
    public $measurements;

    /**
     * Cooked Users Object.
     *
     * @var object|Cooked_Users
     * @since 1.0.0
     */
    public $users;

    /**
     * Cooked Recipes Object.
     *
     * @var object|Cooked_Recipes
     * @since 1.0.0
     */
    public $recipes;

    /**
     * Cooked Shortcodes Object.
     *
     * @var object|Cooked_Shortcodes
     * @since 1.0.0
     */
    public $shortcodes;

    /**
     * Cooked Ajax Object.
     *
     * @var object|Cooked_Ajax
     * @since 1.0.0
     */
    public $ajax;

    /**
     * Cooked Functions Object.
     *
     * @var object|Cooked_Functions
     * @since 1.0.0
     */
    public $functions;

    /**
     * Cooked Widgets Object.
     *
     * @var object|Cooked_Widgets
     * @since 1.0.0
     */
    public $widget;

    /**
     * Cooked Gutenberg Object.
     *
     * @var object|Cooked_Gutenberg
     * @since 1.0.0
     */
    public $gutenberg;

    /**
     * Cooked Elementor Object.
     *
     * @var object|Cooked_Elementor
     * @since 1.0.0
     */
    public $elementor;

    /**
     * Cooked Rank Math SEO Object.
     *
     * @var object|Cooked_RankMathSEO
     * @since 1.0.0
     */
    public $rankmathseo;

    /**
     * Cooked Yoast SEO Object.
     *
     * @var object|Cooked_YoastSEO
     * @since 1.0.0
     */
    public $yoastseo;

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
     * @return object|Cooked_Plugin
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Cooked_Plugin ) ) {
            self::$instance = new Cooked_Plugin;
            self::$instance->setup_constants();

            add_action( 'plugins_loaded', [self::$instance, 'load_textdomain'] );

            self::$instance->includes();
            self::$instance->roles = new Cooked_Roles();
            self::$instance->admin_menus = new Cooked_Admin_Menus();

            if (is_admin()) {
                self::$instance->admin_enqueues = new Cooked_Admin_Enqueues();
            } else {
                self::$instance->enqueues = new Cooked_Enqueues();
            }

            self::$instance->admin_settings = new Cooked_Settings();
            self::$instance->migration = new Cooked_Migration();
            self::$instance->post_types = new Cooked_Post_Types();
            self::$instance->recipe_meta = new Cooked_Recipe_Meta();
            self::$instance->recipe_meta = new Cooked_Measurements();
            self::$instance->users = new Cooked_Users();
            self::$instance->recipes = new Cooked_Recipes();
            self::$instance->shortcodes = new Cooked_Shortcodes();
            self::$instance->ajax = new Cooked_Ajax();
            self::$instance->functions = new Cooked_Functions();
            self::$instance->widget = new Cooked_Widgets();
            self::$instance->gutenberg = new Cooked_Gutenberg();
            self::$instance->elementor = new Cooked_Elementor();
            self::$instance->extra = new Cooked_Plugin_Extra();

            self::$instance->module_setup();

            add_action( 'plugins_loaded', [self::$instance, 'initialize_plugin_support'], 10 );
        }

        return self::$instance;
    }

    private function module_setup() {
        // Look for Cooked Modules
        $modules = file_exists( COOKED_DIR . 'modules' ) ? scandir( COOKED_DIR . 'modules' ) : false;
        if ($modules):
            foreach ($modules as $module_name):
                if ($module_name !== '.' && $module_name !== '..'):
                    if ( file_exists( COOKED_DIR . 'modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ):
                        require_once COOKED_DIR . 'modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                    endif;
                endif;
            endforeach;
        endif;

        // Look for Cooked Modules in Parent Theme
        $parent_theme_folder = trailingslashit( get_template_directory() );
        $modules = file_exists( $parent_theme_folder . 'cooked-modules' ) ? scandir( $parent_theme_folder . 'cooked-modules' ) : false;
        if ($modules):
            foreach ($modules as $module_name):
                if ($module_name !== '.' && $module_name !== '..'):
                    if ( file_exists( $parent_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ):
                        require_once $parent_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                    endif;
                endif;
            endforeach;
        endif;

        // Look for Cooked Modules in Child Theme (if one is active)
        $child_theme_folder = trailingslashit( get_stylesheet_directory() );
        if ($child_theme_folder != $parent_theme_folder):
            $modules = file_exists( $child_theme_folder . 'cooked-modules' ) ? scandir( $child_theme_folder . 'cooked-modules' ) : false;
            if ($modules):
                foreach ($modules as $module_name):
                    if ($module_name !== '.' && $module_name !== '..'):
                        if ( file_exists( $child_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php' ) ):
                            require_once $child_theme_folder . 'cooked-modules/' . esc_attr( $module_name ) . '/' . esc_attr( $module_name ) . '.php';
                        endif;
                    endif;
                endforeach;
            endif;
        endif;
    }

    public function initialize_plugin_support() {
        if (in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            require_once COOKED_DIR . 'includes/class.cooked-yoastseo.php';
            self::$instance->yoastseo = new Cooked_YoastSEO();
        }

        if (in_array('seo-by-rank-math/rank-math.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            require_once COOKED_DIR . 'includes/class.cooked-rankmathseo.php';
            self::$instance->rankmathseo = new Cooked_RankMathSEO();
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
        return false;
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
        return false;
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
            define( 'COOKED_DIR', plugin_dir_path( __FILE__ ) );
        }

        // Plugin Folder Name.
        if ( ! defined( 'COOKED_FOLDER' ) ) {
            $foldername = untrailingslashit( str_replace( 'cooked.php', '', plugin_basename( __FILE__ ) ) );
            define( 'COOKED_FOLDER', $foldername );
        }

        // Plugin Root File.
        if ( ! defined( 'COOKED_PLUGIN_FILE' ) ) {
             define( 'COOKED_PLUGIN_FILE', __FILE__ );
        }

        // Plugin Folder URL.
        if ( ! defined( 'COOKED_URL' ) ) {
            define( 'COOKED_URL', plugin_dir_url( __FILE__ ) );
        }

        // WordPress Ajax URL.
        if ( ! defined( 'COOKED_AJAX_URL' ) ) {
            define( 'COOKED_AJAX_URL', admin_url('admin-ajax.php') );
        }

        // Make sure CAL_GREGORIAN is defined.
        if ( ! defined( 'CAL_GREGORIAN' ) ) {
            define( 'CAL_GREGORIAN', 1 );
        }

        // Time Format
        if ( ! defined( 'COOKED_TIME_FORMAT' ) ) {
            define( 'COOKED_TIME_FORMAT', get_option('time_format','g:ia') );
        }

        // Date Format
        if ( ! defined( 'COOKED_DATE_FORMAT' ) ) {
            define( 'COOKED_DATE_FORMAT', get_option('date_format','F j, Y') );
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
        require_once COOKED_DIR . 'includes/class.cooked-users.php';
        require_once COOKED_DIR . 'includes/class.cooked-roles.php';
        require_once COOKED_DIR . 'includes/class.cooked-taxonomies.php';
        require_once COOKED_DIR . 'includes/class.cooked-post-types.php';
        require_once COOKED_DIR . 'includes/class.cooked-seo.php';
        require_once COOKED_DIR . 'includes/class.cooked-recipes.php';
        require_once COOKED_DIR . 'includes/class.cooked-recipe-meta.php';
        require_once COOKED_DIR . 'includes/class.cooked-shortcodes.php';
        require_once COOKED_DIR . 'includes/class.cooked-measurements.php';
        require_once COOKED_DIR . 'includes/class.cooked-admin-enqueues.php';
        require_once COOKED_DIR . 'includes/class.cooked-enqueues.php';
        require_once COOKED_DIR . 'includes/class.cooked-admin-menus.php';
        require_once COOKED_DIR . 'includes/class.cooked-settings.php';
        require_once COOKED_DIR . 'includes/class.cooked-migration.php';
        require_once COOKED_DIR . 'includes/class.cooked-ajax.php';
        require_once COOKED_DIR . 'includes/class.cooked-functions.php';
        require_once COOKED_DIR . 'includes/class.cooked-widgets.php';
        require_once COOKED_DIR . 'includes/class.cooked-gutenberg.php';
        require_once COOKED_DIR . 'includes/class.cooked-elementor.php';
        require_once COOKED_DIR . 'includes/class.cooked-plugin-extra.php';
    }

    /**
     * Loads the plugin language files.
     *
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain() {
        /*
         * When translating Cooked, be sure to move your language file into the proper location:
         *
         * - wp-content/languages/plugins
         *
         * If you do not move custom language files here, they will be lost when updating Cooked. Gora Tech
         * recommends Loco Translate for easy translations: hhttps://github.com/XjSv/Cooked/wiki/Translations-Text-Changes
         */

        // Set filter for plugin's languages directory.
        $cooked_lang_dir = apply_filters( 'cooked_languages_directory', COOKED_DIR . 'languages/' );


        // Load from WP_LANG_DIR first.
        load_textdomain(
            'cooked',
            sprintf(
                '%s/plugins/%s-%s.mo',
                WP_LANG_DIR,
                'cooked',
                determine_locale()
            )
        );

        // Fall back to plugin languages directory.
        load_plugin_textdomain(
            'cooked',
            false,
            $cooked_lang_dir
        );
    }
}

endif; // End if class_exists check.

// Uninstall Hook
register_uninstall_hook( __FILE__, 'cooked_uninstall' );
function cooked_uninstall() {
    Cooked_Roles::remove_caps();
    Cooked_Roles::remove_roles();
    flush_rewrite_rules();
}

/**
 * The main function for that returns Cooked_Plugin
 *
 * The main function responsible for returning the Cooked_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $cooked = Cooked(); ?>
 *
 * @since 1.0.0
 * @return object|Cooked_Plugin
 */
function Cooked() {
    return Cooked_Plugin::instance();
}

// Let's get cooking!
$CookedPlugin = Cooked();
