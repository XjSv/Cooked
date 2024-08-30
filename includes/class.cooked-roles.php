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
        $caps = apply_filters('cooked_recipe_editor_caps', [
            'manage_categories' => 1,
            'upload_files' => 1,
            'unfiltered_html' => 1,
        ]);

        add_role('cooked_recipe_editor', __('Recipe Editor', 'cooked'), $caps);
    }

    public static function remove_roles() {
        remove_role('cooked_recipe_editor');
    }

    public static function add_caps() {
        global $wp_roles;

        if (class_exists('WP_Roles')) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {
            $wp_roles->add_cap('contributor', 'edit_cooked_recipes'); // General Edit Recipes
            $wp_roles->add_cap('author', 'edit_cooked_recipes'); // General Edit Recipes

            // Editor
            $wp_roles->add_cap('editor', 'edit_cooked_recipes'); // General Edit Recipes
            $wp_roles->add_cap('editor', 'delete_cooked_recipes'); // Delete Recipes

            // Recipe Editor
            $wp_roles->add_cap('cooked_recipe_editor', 'edit_cooked_recipes'); // General Edit Recipes
            $wp_roles->add_cap('cooked_recipe_editor', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->add_cap('cooked_recipe_editor', 'delete_cooked_recipes'); // Delete Recipes
            $wp_roles->add_cap('cooked_recipe_editor', 'edit_cooked_default_template'); // Edit Default Template

            // Administrator
            $wp_roles->add_cap('administrator', 'edit_cooked_recipes'); // General Edit Recipes
            $wp_roles->add_cap('administrator', 'edit_cooked_settings'); // Cooked Settings
            $wp_roles->add_cap('administrator', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->add_cap('administrator', 'delete_cooked_recipes'); // Delete Recipes
            $wp_roles->add_cap('administrator', 'edit_cooked_default_template'); // Edit Default Template
        }
    }

    public static function remove_caps() {
        global $wp_roles;

        if (class_exists('WP_Roles')) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {
            $roles = ['subscriber', 'contributor', 'author', 'editor', 'cooked_recipe_editor', 'administrator'];

            foreach ($roles as $role) {
                $wp_roles->remove_cap($role, 'edit_cooked_recipes' ); // General Edit Recipes
                $wp_roles->remove_cap($role, 'edit_cooked_settings'); // Recipe Settings
                $wp_roles->remove_cap($role, 'approve_cooked_recipes'); // Approve Recipes
                $wp_roles->remove_cap($role, 'delete_cooked_recipes'); // Delete Recipes
                $wp_roles->remove_cap($role, 'edit_cooked_default_template'); // Edit Default Template
            }
        }
    }

    public static function clean_caps() {
        global $wp_roles;

        if (class_exists('WP_Roles')) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {
            $singular = 'cp_recipe';
            $plural = 'cp_recipes';
            $roles = ['subscriber', 'contributor', 'author', 'editor', 'cooked_recipe_editor', 'administrator'];

            foreach ($roles as $role) {
                $wp_roles->remove_cap($role, 'edit_cooked_recipes' ); // General Edit Recipes
                $wp_roles->remove_cap($role, 'edit_cooked_settings'); // Recipe Settings
                $wp_roles->remove_cap($role, 'approve_cooked_recipes'); // Approve Recipes
                $wp_roles->remove_cap($role, 'delete_cooked_recipes'); // Delete Recipes
                $wp_roles->remove_cap($role, 'edit_cooked_default_template'); // Edit Default Template
                $wp_roles->remove_cap($role, "edit_{$singular}");
                $wp_roles->remove_cap($role, "edit_{$plural}");
                $wp_roles->remove_cap($role, "edit_others_{$plural}");
                $wp_roles->remove_cap($role, "publish_{$plural}");
                $wp_roles->remove_cap($role, "read_{$singular}");
                $wp_roles->remove_cap($role, "read_private_{$plural}");
                $wp_roles->remove_cap($role, "delete_{$singular}");
                $wp_roles->remove_cap($role, "delete_{$plural}");
                $wp_roles->remove_cap($role, "delete_private_{$plural}");
                $wp_roles->remove_cap($role, "delete_others_{$plural}");
                $wp_roles->remove_cap($role, "edit_published_{$plural}");
                $wp_roles->remove_cap($role, "edit_private_{$plural}");
                $wp_roles->remove_cap($role, "delete_published_{$plural}");
            }
        }
    }
}
