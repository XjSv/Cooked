<?php
/**
 * Admin Menus
 *
 * @package     Cooked
 * @subpackage  Admin Menus
 * @since       1.0.0
 */

namespace Cooked\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menus Class
 *
 * This class handles the admin menu.
 *
 * @since 1.0.0
 */
class Menus {

    /**
     * Registers the admin menu hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', [ &$this, 'add_menu' ] );

        if ( ! is_admin() ) {
            add_action( 'admin_bar_menu', [ &$this, 'add_admin_bar_menu' ], 100 );
        }

        add_filter( 'parent_file', [ &$this, 'parent_file_filter' ] );
    }

    /**
     * Adds the Recipes menu and its Cooked submenu pages.
     *
     * @return void
     */
    public function add_menu() {
        global $cooked_taxonomies_for_menu;

        add_menu_page( __( 'Recipes', 'cooked' ), __( 'Recipes', 'cooked' ), 'edit_cooked_recipes', 'cooked_recipes_menu', '', 'none', 58 );
        add_submenu_page( 'cooked_recipes_menu', __( 'Add New', 'cooked' ), __( 'Add New', 'cooked' ), 'edit_cooked_recipes', 'post-new.php?post_type=cp_recipe', '' );

        if ( isset( $cooked_taxonomies_for_menu ) && ! empty( $cooked_taxonomies_for_menu ) ) {
            foreach ( $cooked_taxonomies_for_menu as $menu_item ) {
                add_submenu_page( $menu_item['menu'], $menu_item['name'], $menu_item['name'], $menu_item['capability'], $menu_item['url'], '', null );
            }
        }

        add_submenu_page( 'cooked_recipes_menu', __( 'Settings', 'cooked' ), __( 'Settings', 'cooked' ), 'edit_cooked_settings', 'cooked_settings', [ &$this, 'cooked_app_page' ] );
        add_submenu_page( 'cooked_recipes_menu', __( 'Import', 'cooked' ), __( 'Import', 'cooked' ), 'edit_cooked_settings', 'cooked_import', [ &$this, 'cooked_app_page' ] );
        add_submenu_page( 'cooked_recipes_menu', __( 'What\'s New?', 'cooked' ), __( 'What\'s New?', 'cooked' ), 'edit_cooked_settings', 'cooked_welcome', [ &$this, 'cooked_app_page' ] );

        if ( ! class_exists( 'Cooked_Pro_Plugin' ) ) {
            add_submenu_page( 'cooked_recipes_menu', __( 'Upgrade to Pro', 'cooked' ), '<span class="admin-menu-cooked-upgrade">' . __( 'Upgrade to Pro', 'cooked' ) . '</span>', 'edit_cooked_settings', 'cooked_pro', [ &$this, 'cooked_app_page' ] );
        }
    }

    /**
     * Adds the Recipes menu to the front-end admin bar.
     *
     * @return void
     */
    public function add_admin_bar_menu() {
        global $wp_admin_bar;

        if ( is_admin_bar_showing() ) {
            if ( current_user_can( 'edit_cooked_recipes' ) ) {
                $wp_admin_bar->add_menu(
                    [
						'id'    => 'cooked-ab',
						'title' => '<span class="ab-icon"></span>' . __( 'Recipes', 'cooked' ),
						'href'  => get_admin_url() . 'edit.php?post_type=cp_recipe',
					]
                );
                $wp_admin_bar->add_menu(
                    [
						'parent' => 'cooked-ab',
						'title'  => __( 'All Recipes', 'cooked' ),
						'id'     => 'cooked-recipes-ab',
						'href'   => get_admin_url() . 'edit.php?post_type=cp_recipe',
					]
                );
                $wp_admin_bar->add_menu(
                    [
						'parent' => 'cooked-ab',
						'title'  => __( 'Add New', 'cooked' ),
						'id'     => 'cooked-add-new-ab',
						'href'   => get_admin_url() . 'post-new.php?post_type=cp_recipe',
					]
                );
            }

            if ( current_user_can( 'edit_cooked_settings' ) ) {
                $wp_admin_bar->add_menu(
                    [
						'parent' => 'cooked-ab',
						'title'  => __( 'Settings', 'cooked' ),
						'id'     => 'cooked-settings-ab',
						'href'   => get_admin_url() . 'admin.php?page=cooked_settings',
					]
                );
            }
        }
    }

    /**
     * Keeps the Recipes menu open on Cooked taxonomy screens.
     *
     * @param string $parent_file Parent menu slug.
     * @return string
     */
    public function parent_file_filter( $parent_file ) {
        global $submenu_file, $current_screen, $pagenow;
        $post_type = 'cp_recipe';

        if ( $current_screen->post_type === $post_type && 'edit-tags.php' === $pagenow ) {
            $_cooked_taxonomies = \Cooked_Taxonomies::get();

            if ( array_key_exists( $current_screen->taxonomy, $_cooked_taxonomies ) ) {
                $submenu_file = 'edit-tags.php?taxonomy=' . $current_screen->taxonomy . '&post_type=' . $post_type;
            }

            $parent_file = 'cooked_recipes_menu';
        }

        return $parent_file;
    }

    /**
     * Admin frame screens (Settings, Import, What's New, Upgrade to Pro). Each of their pages
     * renders all of them; the frame shows the current one and switches between them in place.
     *
     * @return void
     */
    public function cooked_app_page() {
        if ( ! current_user_can( 'edit_cooked_settings' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cooked' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin page query var.
        $current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $screens = Enqueues::app_screens();

        // Classic layout: just this page's own screen, without the frame.
        if ( Enqueues::use_classic_ui() ) {
            if ( isset( $screens[ $current ] ) ) {
                include COOKED_DIR . 'templates/admin/' . $screens[ $current ]['classic_template'];
            }
            return;
        }

        echo '<div id="cooked-admin-app"></div>';
        foreach ( $screens as $slug => $screen ) {
            echo '<div class="cooked-admin-screen" data-screen="' . esc_attr( $slug ) . '"' . ( $slug === $current ? '' : ' hidden' ) . '>';
            include COOKED_DIR . 'templates/admin/' . $screen['template'];
            echo '</div>';
        }
    }
}
