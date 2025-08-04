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
     * Initialize the updates system
     */
    public function __construct() {
        // Add action to check version and update settings at the end of page load.
        add_action( 'shutdown', [&$this, 'init'] );
    }

    /**
     * Initialize the updates
     */
    public static function init() {
        self::$current_version = COOKED_VERSION;
        self::$previous_version = get_option( 'cooked_settings_version', '1.0.0' );

        // Only run updates if version has changed
        if ( version_compare( self::$previous_version, self::$current_version, '<' ) ) {
            self::run_updates();
        }
    }

    /**
     * Run all necessary updates
     */
    private static function run_updates() {
        // Store the previous version for logging
        $old_version = self::$previous_version;

        // Run version-specific updates
        self::run_version_updates();

        // Update the stored version
        update_option( 'cooked_settings_version', self::$current_version );

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
                            self::$method();
                        } catch ( Exception $e ) {
                            error_log( sprintf( 'Cooked: Error running update method %s: %s', $method, $e->getMessage() ) );
                        }
                    }
                }
            }
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
     * Update rewrite rules if needed
     *
     * @since 1.11.2
     */
    private static function update_rewrite_rules() {
        flush_rewrite_rules();
        error_log( 'Cooked: Flushed rewrite rules due to version update.' );
    }

    /**
     * Get current version
     *
     * @return string Current version
     */
    public static function get_current_version() {
        return self::$current_version;
    }

    /**
     * Get previous version
     *
     * @return string Previous version
     */
    public static function get_previous_version() {
        return self::$previous_version;
    }

    /**
     * Check if an update is needed
     *
     * @return bool True if update is needed
     */
    public static function needs_update() {
        return version_compare( self::$previous_version, self::$current_version, '<' );
    }
}
