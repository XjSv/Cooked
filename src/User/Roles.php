<?php
/**
 * Roles and Capabilities
 *
 * @package     Cooked
 * @subpackage  Roles
 * @since       1.0.0
 */

namespace Cooked\User;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Roles Class
 *
 * This class handles roles and capabilities.
 *
 * @since 1.0.0
 */
class Roles {

    /**
     * Adds the Recipe Editor role.
     *
     * @return void
     */
    public static function add_roles() {
        // No unfiltered_html: recipe content is sanitized with wp_kses_post() on save, and raw
        // HTML would also let the role bypass KSES on anything else it can edit. A site that
        // wants it can add it back through this filter.
        $caps = apply_filters(
            'cooked_recipe_editor_caps',
            [
				'manage_categories' => 1,
				'upload_files'      => 1,
			]
        );

        add_role( 'cooked_recipe_editor', __( 'Recipe Editor', 'cooked' ), $caps );

        // add_role() leaves an existing role alone, so take the capability back from sites
        // created before 2.0 (unless the filter above still grants it).
        $role = get_role( 'cooked_recipe_editor' );
        if ( $role && empty( $caps['unfiltered_html'] ) && $role->has_cap( 'unfiltered_html' ) ) {
            $role->remove_cap( 'unfiltered_html' );
        }
    }

    /**
     * Removes the Recipe Editor role.
     *
     * @return void
     */
    public static function remove_roles() {
        remove_role( 'cooked_recipe_editor' );
    }

    /**
     * Grants the Cooked capabilities to the default roles.
     *
     * @return void
     */
    public static function add_caps() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) ) {
            if ( ! isset( $wp_roles ) ) {
                $wp_roles = new \WP_Roles();
            }
        }

        if ( is_object( $wp_roles ) ) {
            $wp_roles->add_cap( 'contributor', 'edit_cooked_recipes' ); // General Edit Recipes.
            $wp_roles->add_cap( 'author', 'edit_cooked_recipes' ); // General Edit Recipes.

            // Editor.
            $wp_roles->add_cap( 'editor', 'edit_cooked_recipes' ); // General Edit Recipes.
            $wp_roles->add_cap( 'editor', 'delete_cooked_recipes' ); // Delete Recipes.

            // Recipe Editor.
            $wp_roles->add_cap( 'cooked_recipe_editor', 'edit_cooked_recipes' ); // General Edit Recipes.
            $wp_roles->add_cap( 'cooked_recipe_editor', 'approve_cooked_recipes' ); // Approve Recipes.
            $wp_roles->add_cap( 'cooked_recipe_editor', 'delete_cooked_recipes' ); // Delete Recipes.
            $wp_roles->add_cap( 'cooked_recipe_editor', 'edit_cooked_default_template' ); // Edit Default Template.

            // Administrator.
            $wp_roles->add_cap( 'administrator', 'edit_cooked_recipes' ); // General Edit Recipes.
            $wp_roles->add_cap( 'administrator', 'edit_cooked_settings' ); // Cooked Settings.
            $wp_roles->add_cap( 'administrator', 'approve_cooked_recipes' ); // Approve Recipes.
            $wp_roles->add_cap( 'administrator', 'delete_cooked_recipes' ); // Delete Recipes.
            $wp_roles->add_cap( 'administrator', 'edit_cooked_default_template' ); // Edit Default Template.
        }
    }

    /**
     * Removes the Cooked capabilities from every role.
     *
     * @return void
     */
    public static function remove_caps() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) ) {
            if ( ! isset( $wp_roles ) ) {
                $wp_roles = new \WP_Roles();
            }
        }

        if ( is_object( $wp_roles ) ) {
            $roles = [ 'subscriber', 'contributor', 'author', 'editor', 'cooked_recipe_editor', 'administrator' ];

            foreach ( $roles as $role ) {
                $wp_roles->remove_cap( $role, 'edit_cooked_recipes' ); // General Edit Recipes.
                $wp_roles->remove_cap( $role, 'edit_cooked_settings' ); // Recipe Settings.
                $wp_roles->remove_cap( $role, 'approve_cooked_recipes' ); // Approve Recipes.
                $wp_roles->remove_cap( $role, 'delete_cooked_recipes' ); // Delete Recipes.
                $wp_roles->remove_cap( $role, 'edit_cooked_default_template' ); // Edit Default Template.
            }
        }
    }

    /**
     * Removes capabilities that old versions granted by mistake.
     *
     * @return void
     */
    public static function clean_caps() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) ) {
            if ( ! isset( $wp_roles ) ) {
                $wp_roles = new \WP_Roles();
            }
        }

        if ( is_object( $wp_roles ) ) {
            $singular = 'cp_recipe';
            $plural   = 'cp_recipes';
            $roles    = [ 'subscriber', 'contributor', 'author', 'editor', 'cooked_recipe_editor', 'administrator' ];

            foreach ( $roles as $role ) {
                $wp_roles->remove_cap( $role, 'edit_cooked_recipes' ); // General Edit Recipes.
                $wp_roles->remove_cap( $role, 'edit_cooked_settings' ); // Recipe Settings.
                $wp_roles->remove_cap( $role, 'approve_cooked_recipes' ); // Approve Recipes.
                $wp_roles->remove_cap( $role, 'delete_cooked_recipes' ); // Delete Recipes.
                $wp_roles->remove_cap( $role, 'edit_cooked_default_template' ); // Edit Default Template.
                $wp_roles->remove_cap( $role, "edit_{$singular}" );
                $wp_roles->remove_cap( $role, "edit_{$plural}" );
                $wp_roles->remove_cap( $role, "edit_others_{$plural}" );
                $wp_roles->remove_cap( $role, "publish_{$plural}" );
                $wp_roles->remove_cap( $role, "read_{$singular}" );
                $wp_roles->remove_cap( $role, "read_private_{$plural}" );
                $wp_roles->remove_cap( $role, "delete_{$singular}" );
                $wp_roles->remove_cap( $role, "delete_{$plural}" );
                $wp_roles->remove_cap( $role, "delete_private_{$plural}" );
                $wp_roles->remove_cap( $role, "delete_others_{$plural}" );
                $wp_roles->remove_cap( $role, "edit_published_{$plural}" );
                $wp_roles->remove_cap( $role, "edit_private_{$plural}" );
                $wp_roles->remove_cap( $role, "delete_published_{$plural}" );
            }
        }
    }
}
