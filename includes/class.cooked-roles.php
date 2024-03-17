<?php
/**
 * Roles and Capabilities
 *
 * @package     Cooked
 * @subpackage  Roles
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Cooked_Roles {

	public static function add_roles() {
	    $caps = apply_filters( 'cooked_recipe_editor_caps', array(
	    	'manage_categories' => 1,
	    	'upload_files' => 1,
	    	'unfiltered_html' => 1,
	    	'edit_posts' => 1,
	    	'edit_others_posts' => 1,
	    	'edit_published_posts' => 1,
	    	'publish_posts' => 1,
	    	'read' => 1,
	    	'delete_posts' => 1,
	    	'delete_others_posts' => 1,
	    	'delete_published_posts' => 1,
	    	'delete_private_posts' => 1,
	    	'edit_private_posts' => 1,
	    	'read_private_posts' => 1,
	    	'level_7' => 1,
	    	'level_6' => 1,
	    	'level_5' => 1,
	    	'level_4' => 1,
	    	'level_3' => 1,
	    	'level_2' => 1,
	    	'level_1' => 1,
	    	'level_0' => 1
	    ) );
	    add_role( 'cooked_recipe_editor', esc_html__( 'Recipe Editor', 'cooked' ), $caps );
	}

	public static function remove_roles() {
		remove_role( 'cooked_recipe_editor' );
	}

	public static function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			// Edit Recipes
			$wp_roles->add_cap( 'cooked_recipe_editor', 'edit_cooked_recipes' );
			$wp_roles->add_cap( 'contributor', 'edit_cooked_recipes' );
			$wp_roles->add_cap( 'author', 'edit_cooked_recipes' );
			$wp_roles->add_cap( 'editor', 'edit_cooked_recipes' );
			$wp_roles->add_cap( 'administrator', 'edit_cooked_recipes' );

			// Recipe Settings
			$wp_roles->add_cap( 'administrator', 'edit_cooked_settings' );

		}
	}

	public static function remove_caps() {

		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			// Edit Recipes
			$wp_roles->remove_cap( 'cooked_recipe_editor', 'edit_cooked_recipes' );
			$wp_roles->remove_cap( 'contributor', 'edit_cooked_recipes' );
			$wp_roles->remove_cap( 'author', 'edit_cooked_recipes' );
			$wp_roles->remove_cap( 'editor', 'edit_cooked_recipes' );
			$wp_roles->remove_cap( 'administrator', 'edit_cooked_recipes' );

			// Recipe Settings
			$wp_roles->remove_cap( 'administrator', 'edit_cooked_settings' );

		}
	}
}
