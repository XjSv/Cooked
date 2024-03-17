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

		add_action( 'admin_menu', array( &$this, 'add_menu') );
		if ( !is_admin() ): add_action( 'admin_bar_menu', array( &$this, 'add_admin_bar_menu' ), 100 ); endif;

	}

	public function add_menu() {

		global $cooked_taxonomies_for_menu;

		add_menu_page( esc_html__( 'Recipes', 'cooked' ), esc_html__( 'Recipes', 'cooked' ), 'edit_cooked_recipes', 'cooked_recipes_menu', '', 'none', 58 );
		add_submenu_page('cooked_recipes_menu', esc_html__('Add New','cooked'), esc_html__('Add New','cooked'), 'edit_cooked_recipes', 'post-new.php?post_type=cp_recipe', '' );
		if ( isset($cooked_taxonomies_for_menu) && !empty($cooked_taxonomies_for_menu) ):
			foreach ( $cooked_taxonomies_for_menu as $menu_item ):
				add_submenu_page( $menu_item['menu'], $menu_item['name'], $menu_item['name'], $menu_item['capability'], $menu_item['url'], '' );
			endforeach;
		endif;
		add_submenu_page('cooked_recipes_menu', esc_html__('Settings','cooked'), esc_html__('Settings','cooked'), 'edit_cooked_settings', 'cooked_settings', array(&$this, 'cooked_settings_page') );
		add_submenu_page('cooked_recipes_menu', esc_html__('What\'s New?','cooked'), esc_html__('What\'s New?','cooked'), 'edit_cooked_settings', 'cooked_welcome', array(&$this, 'cooked_welcome_content') );
		if ( !class_exists( 'Cooked_Pro_Plugin' ) ):
			add_submenu_page('cooked_recipes_menu', esc_html__('Upgrade to Pro','cooked'), '<span class="admin-menu-cooked-upgrade">' . esc_html__('Upgrade to Pro','cooked') . '</span>', 'edit_cooked_settings', 'cooked_pro', array(&$this, 'cooked_pro') );
		endif;

	}

	public function add_admin_bar_menu() {

		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array( 'id' => 'cooked-ab', 'title' => '<span class="ab-icon"></span>'.esc_html__('Recipes','cooked'), 'href' => get_admin_url() . 'edit.php?post_type=cp_recipe' ) );
		$wp_admin_bar->add_menu( array( 'parent' => 'cooked-ab', 'title' => esc_html__('All Recipes','cooked'), 'id' => 'cooked-recipes-ab', 'href' => get_admin_url() . 'edit.php?post_type=cp_recipe' ) );
		$wp_admin_bar->add_menu( array( 'parent' => 'cooked-ab', 'title' => esc_html__('Add New','cooked'), 'id' => 'cooked-add-new-ab', 'href' => get_admin_url() . 'post-new.php?post_type=cp_recipe' ) );
		$wp_admin_bar->add_menu( array( 'parent' => 'cooked-ab', 'title' => esc_html__('Settings','cooked'), 'id' => 'cooked-settings-ab', 'href' => get_admin_url() . 'admin.php?page=cooked_settings' ) );

	}

	// Settings Panel
	public function cooked_settings_page() {
		if(!current_user_can('edit_cooked_settings')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
		}
		include( COOKED_DIR . 'templates/admin/settings.php' );
	}

	// Welcome Page
	public function cooked_welcome_content() {
		if(!current_user_can('edit_cooked_settings')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
		}
		include( COOKED_DIR . 'templates/admin/welcome.php' );
	}

	// Cooked Pro
	public function cooked_pro() {
		if(!current_user_can('edit_cooked_settings')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'cooked'));
		}
		include( COOKED_DIR . 'templates/admin/pro.php' );
	}

}
