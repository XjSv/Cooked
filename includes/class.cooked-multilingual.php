<?php
/**
 * Cooked Multilingual Support
 *
 * @package     Cooked
 * @subpackage  Multilingual Support
 * @since       1.13.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Multilingual Class
 *
 * This class handles multilingual plugin support (Polylang, WPML, etc.).
 *
 * @since 1.13.0
 */
class Cooked_Multilingual {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_notices', [ $this, 'translation_notice' ] );
    }

    /**
     * Check if Polylang is active
     *
     * @return bool
     */
    public static function is_polylang_active() {
        return function_exists( 'pll_get_post' );
    }

    /**
     * Check if WPML is active
     *
     * Note: We check for ICL_SITEPRESS_VERSION constant which is specific to WPML.
     * We can't just check function_exists('icl_object_id') because Polylang
     * provides WPML compatibility functions.
     *
     * @return bool
     */
    public static function is_wpml_active() {
        return defined( 'ICL_SITEPRESS_VERSION' );
    }

    /**
     * Check if any multilingual plugin is active
     *
     * @return bool
     */
    public static function is_multilingual_active() {
        return self::is_polylang_active() || self::is_wpml_active();
    }

    /**
     * Get the current language code
     *
     * @return string|false Language code or false if not available
     */
    public static function get_current_language() {
        // Polylang support
        if ( self::is_polylang_active() && function_exists( 'pll_current_language' ) ) {
            return pll_current_language();
        }
        // WPML support
        elseif ( self::is_wpml_active() ) {
            return apply_filters( 'wpml_current_language', null );
        }
        return false;
    }

    /**
     * Get the browse page ID for the current language
     *
     * This method returns the translated browse page ID when a multilingual plugin is active.
     * Use this when checking is_page() or generating URLs with get_permalink().
     *
     * @param int|bool $browse_page_id Optional. The default browse page ID. If not provided, gets from settings.
     * @return int|bool The translated page ID or original, false if not set
     */
    public static function get_browse_page_id( $browse_page_id = null ) {
        // If no ID provided, get from settings
        if ( $browse_page_id === null ) {
            global $_cooked_settings;
            $browse_page_id = ! empty( $_cooked_settings['browse_page'] ) ? $_cooked_settings['browse_page'] : false;
        }

        if ( ! $browse_page_id ) {
            return false;
        }

        // Polylang support (check first, as it's more common)
        if ( self::is_polylang_active() ) {
            $translated_id = pll_get_post( $browse_page_id );
            if ( $translated_id ) {
                return $translated_id;
            }
        }
        // WPML support (only if Polylang is not active)
        elseif ( self::is_wpml_active() ) {
            $translated_id = icl_object_id( $browse_page_id, 'page', true );
            if ( $translated_id ) {
                return $translated_id;
            }
        }

        return $browse_page_id;
    }

    /**
     * Get all browse page translations
     *
     * Returns an array of all translated browse page IDs and slugs.
     * Useful for creating rewrite rules for each language.
     *
     * @return array Array of ['lang' => ['id' => int, 'slug' => string]]
     */
    public static function get_all_browse_pages() {
        global $_cooked_settings;

        $pages = [];
        $default_page_id = ! empty( $_cooked_settings['browse_page'] ) ? $_cooked_settings['browse_page'] : false;

        if ( ! $default_page_id ) {
            return $pages;
        }

        // Default page (always include)
        $pages['default'] = [
            'id'   => $default_page_id,
            'slug' => self::get_page_slug( $default_page_id )
        ];

        // Polylang translations (check first, as it's more common)
        if ( self::is_polylang_active() && function_exists( 'pll_get_post_translations' ) ) {
            $translations = pll_get_post_translations( $default_page_id );
            foreach ( $translations as $lang => $translated_id ) {
                if ( $translated_id && $translated_id != $default_page_id ) {
                    $pages[ $lang ] = [
                        'id'   => $translated_id,
                        'slug' => self::get_page_slug( $translated_id )
                    ];
                }
            }
        }
        // WPML translations (only if Polylang is not active)
        elseif ( self::is_wpml_active() ) {
            $languages = apply_filters( 'wpml_active_languages', null );
            if ( is_array( $languages ) ) {
                foreach ( $languages as $lang_code => $lang_data ) {
                    $translated_id = icl_object_id( $default_page_id, 'page', false, $lang_code );
                    if ( $translated_id && $translated_id != $default_page_id ) {
                        $pages[ $lang_code ] = [
                            'id'   => $translated_id,
                            'slug' => self::get_page_slug( $translated_id )
                        ];
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Get page slug from ID
     *
     * @param int $page_id The page ID
     * @return string|null The page slug (empty string for homepage), or null if page doesn't exist
     */
    private static function get_page_slug( $page_id ) {
        if ( ! $page_id ) {
            return null;
        }

        $permalink = get_permalink( $page_id );
        if ( ! $permalink ) {
            return null;
        }

        // Returns empty string for homepage, which is valid
        return ltrim( untrailingslashit( str_replace( home_url(), '', $permalink ) ), '/' );
    }

    /**
     * Check if browse page has translations
     *
     * @return array Array of missing language codes
     */
    public static function get_missing_translations() {
        global $_cooked_settings;

        $missing = [];
        $default_page_id = ! empty( $_cooked_settings['browse_page'] ) ? $_cooked_settings['browse_page'] : false;

        if ( ! $default_page_id ) {
            return $missing;
        }

        // Check Polylang translations (check first, as it's more common)
        if ( self::is_polylang_active() && function_exists( 'pll_languages_list' ) && function_exists( 'pll_get_post_translations' ) ) {
            $languages = pll_languages_list( [ 'fields' => 'slug' ] );
            $translations = pll_get_post_translations( $default_page_id );

            foreach ( $languages as $lang ) {
                if ( ! isset( $translations[ $lang ] ) || ! $translations[ $lang ] ) {
                    $missing[] = $lang;
                }
            }
        }
        // Check WPML translations (only if Polylang is not active)
        elseif ( self::is_wpml_active() ) {
            $languages = apply_filters( 'wpml_active_languages', null );
            if ( is_array( $languages ) ) {
                foreach ( $languages as $lang_code => $lang_data ) {
                    $translated_id = icl_object_id( $default_page_id, 'page', false, $lang_code );
                    if ( ! $translated_id ) {
                        $missing[] = $lang_code;
                    }
                }
            }
        }

        return $missing;
    }

    /**
     * Display admin notice about missing browse page translations
     */
    public function translation_notice() {
        // Only show on Cooked settings page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'cooked_settings' ) {
            return;
        }

        // Only show if multilingual plugin is active
        if ( ! self::is_multilingual_active() ) {
            return;
        }

        global $_cooked_settings;

        // Only show if browse page is set
        if ( empty( $_cooked_settings['browse_page'] ) ) {
            return;
        }

        $missing = self::get_missing_translations();

        if ( ! empty( $missing ) ) {
            $plugin_name = self::is_polylang_active() ? 'Polylang' : 'WPML';
            $class = 'notice notice-warning';
            $message = sprintf(
                '<strong>%s</strong> %s <strong>%s</strong>',
                __( 'Multilingual Setup:', 'cooked' ),
                __( 'Your Browse/Search Recipes page is missing translations for:', 'cooked' ),
                strtoupper( implode( ', ', $missing ) )
            );
            $message .= '<br><em>' . sprintf(
                /* translators: %s is the multilingual plugin name (Polylang or WPML) */
                __( 'Create translations of your browse page in %s for full multilingual support.', 'cooked' ),
                $plugin_name
            ) . '</em>';

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        }
    }
}
