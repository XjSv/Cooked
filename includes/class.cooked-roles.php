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
            'unfiltered_html' => 0,
        ]);

        add_role('cooked_recipe_editor', __('Recipe Editor', 'cooked'), $caps);
    }

    public static function remove_roles() {
        remove_role('cooked_recipe_editor');
    }

    public static function add_caps() {
        global $wp_roles;

        $singular = 'cp_recipe';
        $plural = 'cp_recipes';

        if (class_exists('WP_Roles')) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {
            // Edit Recipes
            $wp_roles->add_cap('cooked_recipe_editor', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->add_cap('cooked_recipe_editor', "edit_{$singular}");
            $wp_roles->add_cap('cooked_recipe_editor', "edit_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "edit_others_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "publish_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "read_{$singular}");
            $wp_roles->add_cap('cooked_recipe_editor', "read_private_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "delete_{$singular}");
            $wp_roles->add_cap('cooked_recipe_editor', "delete_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "delete_private_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "delete_others_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "edit_published_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "edit_private_{$plural}");
            $wp_roles->add_cap('cooked_recipe_editor', "delete_published_{$plural}");

            $wp_roles->add_cap('subscriber', "edit_{$singular}");
            $wp_roles->add_cap('subscriber', "edit_{$plural}");
            $wp_roles->add_cap('subscriber', "read_{$singular}");
            $wp_roles->add_cap('subscriber', "read_private_{$plural}");
            $wp_roles->add_cap('subscriber', "edit_private_{$plural}");

            $wp_roles->add_cap('contributor', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->add_cap('contributor', "edit_{$singular}");
            $wp_roles->add_cap('contributor', "edit_{$plural}");
            $wp_roles->add_cap('contributor', "read_{$singular}");
            $wp_roles->add_cap('contributor', "read_private_{$plural}");
            $wp_roles->add_cap('contributor', "delete_{$singular}");
            $wp_roles->add_cap('contributor', "delete_{$plural}");
            $wp_roles->add_cap('contributor', "delete_private_{$plural}");
            $wp_roles->add_cap('contributor', "edit_private_{$plural}");

            $wp_roles->add_cap('author', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->add_cap('author', "edit_{$singular}");
            $wp_roles->add_cap('author', "edit_{$plural}");
            $wp_roles->add_cap('author', "read_{$singular}");
            $wp_roles->add_cap('author', "read_private_{$plural}");
            $wp_roles->add_cap('author', "delete_{$singular}");
            $wp_roles->add_cap('author', "delete_{$plural}");
            $wp_roles->add_cap('author', "delete_private_{$plural}");
            $wp_roles->add_cap('author', "edit_private_{$plural}");
            $wp_roles->add_cap('author', "delete_published_{$plural}");

            $wp_roles->add_cap('editor', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->add_cap('editor', "edit_{$singular}");
            $wp_roles->add_cap('editor', "edit_{$plural}");
            $wp_roles->add_cap('editor', "edit_others_{$plural}");
            $wp_roles->add_cap('editor', "publish_{$plural}");
            $wp_roles->add_cap('editor', "read_{$singular}");
            $wp_roles->add_cap('editor', "read_private_{$plural}");
            $wp_roles->add_cap('editor', "delete_{$singular}");
            $wp_roles->add_cap('editor', "delete_{$plural}");
            $wp_roles->add_cap('editor', "delete_private_{$plural}");
            $wp_roles->add_cap('editor', "delete_others_{$plural}");
            $wp_roles->add_cap('editor', "edit_published_{$plural}");
            $wp_roles->add_cap('editor', "edit_private_{$plural}");
            $wp_roles->add_cap('editor', "delete_published_{$plural}");

            $wp_roles->add_cap('administrator', 'edit_cooked_settings'); // Recipe Settings
            $wp_roles->add_cap('administrator', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->add_cap('administrator', "edit_{$singular}");
            $wp_roles->add_cap('administrator', "edit_{$plural}");
            $wp_roles->add_cap('administrator', "edit_others_{$plural}");
            $wp_roles->add_cap('administrator', "publish_{$plural}");
            $wp_roles->add_cap('administrator', "read_{$singular}");
            $wp_roles->add_cap('administrator', "read_private_{$plural}");
            $wp_roles->add_cap('administrator', "delete_{$singular}");
            $wp_roles->add_cap('administrator', "delete_{$plural}");
            $wp_roles->add_cap('administrator', "delete_private_{$plural}");
            $wp_roles->add_cap('administrator', "delete_others_{$plural}");
            $wp_roles->add_cap('administrator', "edit_published_{$plural}");
            $wp_roles->add_cap('administrator', "edit_private_{$plural}");
            $wp_roles->add_cap('administrator', "delete_published_{$plural}");
        }
    }

    public static function remove_caps() {
        global $wp_roles;

        $singular = 'cp_recipe';
        $plural = 'cp_recipes';

        if (class_exists('WP_Roles')) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {
            // Edit Recipes
            $wp_roles->remove_cap('cooked_recipe_editor', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->remove_cap('cooked_recipe_editor', "edit_{$singular}");
            $wp_roles->remove_cap('cooked_recipe_editor', "edit_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "edit_others_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "publish_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "read_{$singular}");
            $wp_roles->remove_cap('cooked_recipe_editor', "read_private_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "delete_{$singular}");
            $wp_roles->remove_cap('cooked_recipe_editor', "delete_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "delete_private_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "delete_others_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "edit_published_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "edit_private_{$plural}");
            $wp_roles->remove_cap('cooked_recipe_editor', "delete_published_{$plural}");

            $wp_roles->remove_cap('subscriber', "edit_{$singular}");
            $wp_roles->remove_cap('subscriber', "edit_{$plural}");
            $wp_roles->remove_cap('subscriber', "read_{$singular}");
            $wp_roles->remove_cap('subscriber', "read_private_{$plural}");
            $wp_roles->remove_cap('subscriber', "edit_private_{$plural}");

            $wp_roles->remove_cap('contributor', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->remove_cap('contributor', "edit_{$singular}");
            $wp_roles->remove_cap('contributor', "edit_{$plural}");
            $wp_roles->remove_cap('contributor', "read_{$singular}");
            $wp_roles->remove_cap('contributor', "read_private_{$plural}");
            $wp_roles->remove_cap('contributor', "delete_{$singular}");
            $wp_roles->remove_cap('contributor', "delete_{$plural}");
            $wp_roles->remove_cap('contributor', "delete_private_{$plural}");
            $wp_roles->remove_cap('contributor', "edit_private_{$plural}");

            $wp_roles->remove_cap('author', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->remove_cap('author', "edit_{$singular}");
            $wp_roles->remove_cap('author', "edit_{$plural}");
            $wp_roles->remove_cap('author', "read_{$singular}");
            $wp_roles->remove_cap('author', "read_private_{$plural}");
            $wp_roles->remove_cap('author', "delete_{$singular}");
            $wp_roles->remove_cap('author', "delete_{$plural}");
            $wp_roles->remove_cap('author', "delete_private_{$plural}");
            $wp_roles->remove_cap('author', "edit_private_{$plural}");
            $wp_roles->remove_cap('author', "delete_published_{$plural}");

            $wp_roles->remove_cap('editor', 'edit_cooked_recipes'); // Edit Recipes
            $wp_roles->remove_cap('editor', "edit_{$singular}");
            $wp_roles->remove_cap('editor', "edit_{$plural}");
            $wp_roles->remove_cap('editor', "edit_others_{$plural}");
            $wp_roles->remove_cap('editor', "publish_{$plural}");
            $wp_roles->remove_cap('editor', "read_{$singular}");
            $wp_roles->remove_cap('editor', "read_private_{$plural}");
            $wp_roles->remove_cap('editor', "delete_{$singular}");
            $wp_roles->remove_cap('editor', "delete_{$plural}");
            $wp_roles->remove_cap('editor', "delete_private_{$plural}");
            $wp_roles->remove_cap('editor', "delete_others_{$plural}");
            $wp_roles->remove_cap('editor', "edit_published_{$plural}");
            $wp_roles->remove_cap('editor', "edit_private_{$plural}");
            $wp_roles->remove_cap('editor', "delete_published_{$plural}");

            $wp_roles->remove_cap('administrator', 'edit_cooked_settings'); // Recipe Settings
            $wp_roles->remove_cap('administrator', 'approve_cooked_recipes'); // Approve Recipes
            $wp_roles->remove_cap('administrator', "edit_{$singular}");
            $wp_roles->remove_cap('administrator', "edit_{$plural}");
            $wp_roles->remove_cap('administrator', "edit_others_{$plural}");
            $wp_roles->remove_cap('administrator', "publish_{$plural}");
            $wp_roles->remove_cap('administrator', "read_{$singular}");
            $wp_roles->remove_cap('administrator', "read_private_{$plural}");
            $wp_roles->remove_cap('administrator', "delete_{$singular}");
            $wp_roles->remove_cap('administrator', "delete_{$plural}");
            $wp_roles->remove_cap('administrator', "delete_private_{$plural}");
            $wp_roles->remove_cap('administrator', "delete_others_{$plural}");
            $wp_roles->remove_cap('administrator', "edit_published_{$plural}");
            $wp_roles->remove_cap('administrator', "edit_private_{$plural}");
            $wp_roles->remove_cap('administrator', "delete_published_{$plural}");
        }
    }
}
