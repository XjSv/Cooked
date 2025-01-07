<?php
/**
 * Admin Menus
 *
 * @package     Cooked
 * @subpackage  Admin Menus
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

class Cooked_Admin_Menus {

    function __construct() {
        add_action( 'admin_menu', [&$this, 'add_menu'] );

        if (!is_admin()) {
            add_action( 'admin_bar_menu', [&$this, 'add_admin_bar_menu'], 100 );
        }

        add_action('parent_file', [&$this, 'parent_file_filter']);
    }

    public function add_menu() {
        global $cooked_taxonomies_for_menu;

        add_menu_page( __( 'Recipes', 'cooked' ), __( 'Recipes', 'cooked' ), 'edit_cooked_recipes', 'cooked_recipes_menu', '', 'none', 58 );
        add_submenu_page('cooked_recipes_menu', __('Add New','cooked'), __('Add New','cooked'), 'edit_cooked_recipes', 'post-new.php?post_type=cp_recipe', '' );

        if ( isset($cooked_taxonomies_for_menu) && !empty($cooked_taxonomies_for_menu) ) {
            foreach ( $cooked_taxonomies_for_menu as $menu_item ) {
                add_submenu_page($menu_item['menu'], $menu_item['name'], $menu_item['name'], $menu_item['capability'], $menu_item['url'], '', null );
            }
        }

        add_submenu_page('cooked_recipes_menu', __('Settings', 'cooked'), __('Settings','cooked'), 'edit_cooked_settings', 'cooked_settings', [&$this, 'cooked_settings_page'] );
        add_submenu_page('cooked_recipes_menu', __('Import', 'cooked'), __('Import','cooked'), 'edit_cooked_settings', 'cooked_import', [&$this, 'cooked_import_page'] );
        add_submenu_page('cooked_recipes_menu', __('What\'s New?','cooked'), __('What\'s New?','cooked'), 'edit_cooked_settings', 'cooked_welcome', [&$this, 'cooked_welcome_content'] );

        if ( !class_exists( 'Cooked_Pro_Plugin' ) ) {
            add_submenu_page('cooked_recipes_menu', __('Upgrade to Pro','cooked'), '<span class="admin-menu-cooked-upgrade">' . __('Upgrade to Pro','cooked') . '</span>', 'edit_cooked_settings', 'cooked_pro', [&$this, 'cooked_pro'] );
        }
    }

    public function add_admin_bar_menu() {
        global $wp_admin_bar;

        if (is_admin_bar_showing()) {
            if (current_user_can('edit_cooked_recipes')) {
                $wp_admin_bar->add_menu(['id' => 'cooked-ab', 'title' => '<span class="ab-icon"></span>' . __('Recipes', 'cooked'), 'href' => get_admin_url() . 'edit.php?post_type=cp_recipe'] );
                $wp_admin_bar->add_menu(['parent' => 'cooked-ab', 'title' => __('All Recipes', 'cooked'), 'id' => 'cooked-recipes-ab', 'href' => get_admin_url() . 'edit.php?post_type=cp_recipe'] );
                $wp_admin_bar->add_menu(['parent' => 'cooked-ab', 'title' => __('Add New', 'cooked'), 'id' => 'cooked-add-new-ab', 'href' => get_admin_url() . 'post-new.php?post_type=cp_recipe'] );
            }

            if (current_user_can('edit_cooked_settings')) {
                $wp_admin_bar->add_menu(['parent' => 'cooked-ab', 'title' => __('Settings', 'cooked'), 'id' => 'cooked-settings-ab', 'href' => get_admin_url() . 'admin.php?page=cooked_settings'] );
            }
        }
    }

    public function parent_file_filter($parent_file) {
        global $submenu_file, $current_screen, $pagenow;
        $post_type = 'cp_recipe';

        if ($current_screen->post_type === $post_type && $pagenow === 'edit-tags.php') {
            $_cooked_taxonomies = Cooked_Taxonomies::get();

            if (array_key_exists($current_screen->taxonomy, $_cooked_taxonomies)) {
                $submenu_file = 'edit-tags.php?taxonomy=' . $current_screen->taxonomy . '&post_type=' . $post_type;
            }

            $parent_file = 'cooked_recipes_menu';
        }

        return $parent_file;
    }

    // Settings Panel
    public function cooked_settings_page() {
        if (!current_user_can('edit_cooked_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
        }

        include COOKED_DIR . 'templates/admin/settings.php';
    }

    // Settings Panel
    public function cooked_import_page() {
        if (!current_user_can('edit_cooked_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
        }

        include COOKED_DIR . 'templates/admin/import.php';
    }

    // Welcome Page
    public function cooked_welcome_content() {
        if (!current_user_can('edit_cooked_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
        }

        include COOKED_DIR . 'templates/admin/welcome.php';
    }

    // Cooked Pro
    public function cooked_pro() {
        if (!current_user_can('edit_cooked_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
        }

        include COOKED_DIR . 'templates/admin/pro.php';
    }

}
