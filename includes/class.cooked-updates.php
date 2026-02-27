<?php
/**
 * Plugin Updates and Data Migrations
 *
 * @package     Cooked_Updates
 * @subpackage  Cooked_Updates / Core
 * @since       1.11.2
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Updates Class
 *
 * This class handles all version updates and data migrations for the Cooked plugin.
 * It follows WordPress best practices for plugin updates and ensures data integrity.
 *
 * @since 1.11.2
 */
class Cooked_Updates {

    /**
     * Current plugin version
     */
    private static $current_version;

    /**
     * Previous plugin version
     */
    private static $previous_version;

    /**
     * Current pro plugin version
     */
    private static $current_pro_version;

    /**
     * Previous pro plugin version
     */
    private static $previous_pro_version;

    /**
     * Cooked Settings Saved
     */
    private static $cooked_settings_saved;

    /**
     * Initialize the updates system
     */
    public function __construct() {
        // Add action to check version and update settings at the end of page load.
        add_action( 'shutdown', [__CLASS__, 'init'] );
    }

    /**
     * Initialize the updates
     */
    public static function init() {
        self::$cooked_settings_saved = get_option( 'cooked_settings_saved', false );
        self::$current_version = COOKED_VERSION;
        self::$previous_version = get_option( 'cooked_settings_version', '1.0.0' );
        self::$current_pro_version = defined('COOKED_PRO_VERSION') ? COOKED_PRO_VERSION : null;
        self::$previous_pro_version = get_option( 'cooked_pro_settings_version', '1.0.0' );

        if ( !self::$cooked_settings_saved ) {
            global $_cooked_settings;

            if ( empty($_cooked_settings) ) {
                $_cooked_settings = Cooked_Settings::get();
            }

            update_option( 'cooked_settings', $_cooked_settings );
            self::$cooked_settings_saved = true;
        }

        // Only run updates if version has changed
        if ( self::needs_update() ) {
            self::run_updates();
        }
    }

    /**
     * Check if an update is needed
     *
     * @return bool True if update is needed
     */
    public static function needs_update() {
        // Check both versions.
        $cooked_version_compare = version_compare( self::$previous_version, self::$current_version );
        $cooked_pro_version_compare = ( defined('COOKED_PRO_VERSION') && self::$current_pro_version !== null ) ? version_compare( self::$previous_pro_version, self::$current_pro_version ) : 0;

        // Update if either version has changed.
        if ( $cooked_version_compare < 0 || $cooked_pro_version_compare < 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Run all necessary updates
     */
    private static function run_updates() {
        // Store the previous version for logging
        $old_version = self::$previous_version;

        // Run version-specific updates
        self::run_version_updates();

        // Update both version numbers.
        update_option( 'cooked_settings_version', self::$current_version );
        if ( defined('COOKED_PRO_VERSION') ) {
            update_option( 'cooked_pro_settings_version', self::$current_pro_version );
        }

        // Log the update
        error_log( sprintf( 'Cooked: Updated from version %s to %s', $old_version, self::$current_version ) );
    }

    /**
     * Run version-specific updates
     */
    private static function run_version_updates() {
        $updates = self::get_version_updates();

        foreach ( $updates as $version => $update_methods ) {
            if ( version_compare( self::$previous_version, $version, '<' ) ) {
                foreach ( $update_methods as $method ) {
                    if ( method_exists( __CLASS__, $method ) ) {
                        try {
                            call_user_func( [__CLASS__, $method] );
                        } catch ( Exception $e ) {
                            error_log( sprintf( 'Cooked: Error running update method %s: %s', $method, $e->getMessage() ) );
                        }
                    }
                }
            }
        }
    }

    /**
     * Get list of tools that can be run on demand (for Tools page and WP-CLI).
     *
     * @return array[] List of tools: [ 'id' => method_name, 'name' => label, 'description' => desc ]
     */
    public static function get_runnable_tools() {
        $labels = [
            'update_rewrite_rules' => [
                'name'        => __( 'Flush rewrite rules', 'cooked' ),
                'description' => __( 'Refreshes permalink rules for recipe and profile URLs.', 'cooked' ),
            ],
            'fix_recipe_line_endings' => [
                'name'        => __( 'Fix recipe line endings', 'cooked' ),
                'description' => __( 'Normalizes line endings in recipe content for exporter/importer compatibility.', 'cooked' ),
            ],
            'purge_legacy_related_recipes_cache' => [
                'name'        => __( 'Purge legacy related recipes cache', 'cooked' ),
                'description' => __( 'Removes old transients and options from the previous related-recipes cache.', 'cooked' ),
            ],
            'remove_recipes_from_cooked_user_meta' => [
                'name'        => __( 'Remove recipes from user meta', 'cooked' ),
                'description' => __( 'Removes the legacy "recipes" key from all users\' cooked_user_meta.', 'cooked' ),
            ],
        ];

        $updates = self::get_version_updates();
        $method_names = [];
        foreach ( $updates as $methods ) {
            foreach ( $methods as $method ) {
                $method_names[ $method ] = true;
            }
        }
        $method_names = array_keys( $method_names );

        $tools = [];
        foreach ( $method_names as $method ) {
            $tools[] = [
                'id'          => $method,
                'name'        => isset( $labels[ $method ]['name'] ) ? $labels[ $method ]['name'] : $method,
                'description' => isset( $labels[ $method ]['description'] ) ? $labels[ $method ]['description'] : '',
            ];
        }
        return $tools;
    }

    /**
     * Run a single migration/tool by name (on demand). Used by Tools page and WP-CLI.
     *
     * @param string $tool_name Method name (must exist in get_version_updates).
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public static function run_tool( $tool_name ) {
        $tool_name = is_string( $tool_name ) ? trim( $tool_name ) : '';
        if ( $tool_name === '' ) {
            return new \WP_Error( 'cooked_tool_empty', __( 'Tool name is required.', 'cooked' ) );
        }

        $updates = self::get_version_updates();
        $allowed = [];
        foreach ( $updates as $methods ) {
            foreach ( $methods as $method ) {
                $allowed[ $method ] = true;
            }
        }
        if ( ! isset( $allowed[ $tool_name ] ) ) {
            return new \WP_Error( 'cooked_tool_invalid', sprintf( __( 'Unknown tool: %s.', 'cooked' ), $tool_name ) );
        }
        if ( ! method_exists( __CLASS__, $tool_name ) ) {
            return new \WP_Error( 'cooked_tool_missing', sprintf( __( 'Tool method %s does not exist.', 'cooked' ), $tool_name ) );
        }

        try {
            call_user_func( [ __CLASS__, $tool_name ] );
            return true;
        } catch ( \Exception $e ) {
            return new \WP_Error( 'cooked_tool_error', $e->getMessage() );
        }
    }

    /**
     * Define version-specific updates
     *
     * @return array Array of version updates with their corresponding methods
     */
    private static function get_version_updates() {
        return apply_filters( 'cooked_version_updates', [
            '1.9.0' => [
                'update_rewrite_rules'
            ],
            '1.9.1' => [
                'update_rewrite_rules'
            ],
            '1.9.2' => [
                'update_rewrite_rules'
            ],
            '1.9.4' => [
                'update_rewrite_rules'
            ],
            '1.9.5' => [
                'update_rewrite_rules'
            ],
            '1.11.2' => [
                'fix_recipe_line_endings',
                'update_rewrite_rules'
            ],
            '1.13.0' => [
                'purge_legacy_related_recipes_cache',
                'remove_recipes_from_cooked_user_meta',
            ],
        ]);
    }

    /**
     * Fix line endings in existing recipes to prevent WordPress exporter/importer issues
     *
     * @since 1.11.2
     */
    private static function fix_recipe_line_endings() {
        // Get all recipe posts
        $recipes = get_posts([
            'post_type' => 'cp_recipe',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        if ( empty($recipes) ) {
            return;
        }

        $updated_count = 0;

        foreach ( $recipes as $recipe ) {
            $recipe_settings = get_post_meta( $recipe->ID, '_recipe_settings', true );

            if ( empty($recipe_settings) ) {
                continue;
            }

            $needs_update = false;

            // Fix content field
            if ( isset( $recipe_settings['content'] ) ) {
                $original_content = $recipe_settings['content'];
                $recipe_settings['content'] = str_replace( ["\r\n", "\r"], "\n", $recipe_settings['content'] );
                if ( $original_content !== $recipe_settings['content'] ) {
                    $needs_update = true;
                }
            }

            // Fix excerpt field
            if ( isset( $recipe_settings['excerpt'] ) ) {
                $original_excerpt = $recipe_settings['excerpt'];
                $recipe_settings['excerpt'] = str_replace( ["\r\n", "\r"], "\n", $recipe_settings['excerpt'] );
                if ( $original_excerpt !== $recipe_settings['excerpt'] ) {
                    $needs_update = true;
                }
            }

            // Fix notes field
            if ( isset( $recipe_settings['notes'] ) ) {
                $original_notes = $recipe_settings['notes'];
                $recipe_settings['notes'] = str_replace( ["\r\n", "\r"], "\n", $recipe_settings['notes'] );
                if ( $original_notes !== $recipe_settings['notes'] ) {
                    $needs_update = true;
                }
            }

            // Fix directions content
            if ( isset( $recipe_settings['directions'] ) && is_array( $recipe_settings['directions'] ) ) {
                foreach ( $recipe_settings['directions'] as $key => $direction ) {
                    if ( isset( $direction['content'] ) ) {
                        $original_direction_content = $direction['content'];
                        $recipe_settings['directions'][$key]['content'] = str_replace( ["\r\n", "\r"], "\n", $direction['content'] );
                        if ( $original_direction_content !== $recipe_settings['directions'][$key]['content'] ) {
                            $needs_update = true;
                        }
                    }
                }
            }

            // Update the recipe if changes were made
            if ( $needs_update ) {
                update_post_meta( $recipe->ID, '_recipe_settings', $recipe_settings );
                $updated_count++;
            }
        }

        // Log the update if any recipes were modified
        if ( $updated_count > 0 ) {
            error_log( sprintf( 'Cooked: Fixed line endings in %d recipes for WordPress exporter/importer compatibility.', $updated_count ) );
        }
    }

    /**
     * Purge legacy related-recipes transients and options (cache and pre-calculation data).
     * One-time cleanup when moving to on-demand related recipes with no cache.
     *
     * @since 1.13.0
     */
    private static function purge_legacy_related_recipes_cache() {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cooked_related_%' OR option_name LIKE '_transient_timeout_cooked_related_%'" );
        delete_option( 'cooked_related_version' );
        delete_option( 'cooked_related_calculation_last' );

        error_log( 'Cooked: Purged legacy related-recipes cache and options.' );
    }

    /**
     * Remove legacy 'recipes' key from cooked_user_meta for all users.
     * Recipes are computed on demand in Cooked_Users::get(); they must not be stored in user meta.
     *
     * @since 1.13.0
     */
    private static function remove_recipes_from_cooked_user_meta() {
        $user_ids = get_users( [
            'meta_key' => 'cooked_user_meta',
            'fields'   => 'ID',
        ] );

        if ( empty( $user_ids ) ) {
            return;
        }

        $updated_count = 0;

        foreach ( $user_ids as $user_id ) {
            $meta = get_user_meta( $user_id, 'cooked_user_meta', true );

            if ( ! is_array( $meta ) || ! isset( $meta['recipes'] ) ) {
                continue;
            }

            unset( $meta['recipes'] );
            update_user_meta( $user_id, 'cooked_user_meta', $meta );
            $updated_count++;
        }

        if ( $updated_count > 0 ) {
            error_log( sprintf( 'Cooked: Removed legacy recipes key from %d user(s) cooked_user_meta.', $updated_count ) );
        }
    }

    /**
     * Update rewrite rules if needed
     *
     * @since 1.11.2
     */
    private static function update_rewrite_rules() {
        flush_rewrite_rules();
        error_log( 'Cooked: Flushed rewrite rules due to version update.' );
    }

}
