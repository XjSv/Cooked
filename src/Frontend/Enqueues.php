<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Enqueues
 * @since       1.0.0
 */

namespace Cooked\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Enqueues {

    /**
     * Registers the front-end asset hooks.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ &$this, 'enqueues' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ &$this, 'css_colors' ], 11 );
        add_action( 'wp_footer', [ &$this, 'footer_enqueues' ] );

        // Runs before the template prints <html>, so the static dark-mode CSS can key off it.
        add_filter( 'language_attributes', [ &$this, 'dark_mode_language_attributes' ] );
    }

    /**
     * Loads Cooked's front-end styles and scripts.
     *
     * @return void
     */
    public function enqueues() {
        global $_cooked_settings;

        $browse_page_id      = \Cooked_Multilingual::get_browse_page_id();
        $browse_page         = $browse_page_id ? get_post( $browse_page_id ) : null;
        $browse_recipes_slug = ! empty( $browse_page ) ? $browse_page->post_name : '';

        // Get the full browse page URL (includes language prefix when using Polylang/WPML).
        $browse_page_url = $browse_page_id ? (string) get_permalink( $browse_page_id ) : get_site_url();

        $cooked_js_vars = [
            'ajax_url'                   => admin_url( 'admin-ajax.php' ),
            'timer_sound'                => apply_filters( 'cooked_timer_sound_mp3', COOKED_URL . 'assets/audio/ding.mp3' ),
            'permalink_structure'        => get_option( 'permalink_structure' ),
            'site_url'                   => get_site_url(),
            'browse_page'                => $browse_page_id,
            'browse_page_url'            => untrailingslashit( $browse_page_url ),
            'front_page'                 => get_option( 'page_on_front' ),
            'browse_recipes_slug'        => $browse_recipes_slug,
            'recipe_category_slug'       => ! isset( $_cooked_settings['recipe_category_permalink'] ) ? 'recipe-category' : $_cooked_settings['recipe_category_permalink'],
            'recipe_cooking_method_slug' => ! isset( $_cooked_settings['recipe_cooking_method_permalink'] ) ? 'cooking-method' : $_cooked_settings['recipe_cooking_method_permalink'],
            'recipe_cuisine_slug'        => ! isset( $_cooked_settings['recipe_cuisine_permalink'] ) ? 'cuisine' : $_cooked_settings['recipe_cuisine_permalink'],
            'recipe_tags_slug'           => ! isset( $_cooked_settings['recipe_tag_permalink'] ) ? 'recipe-tag' : $_cooked_settings['recipe_tag_permalink'],
            'recipe_diet_slug'           => ! isset( $_cooked_settings['recipe_diet_permalink'] ) ? 'diet' : $_cooked_settings['recipe_diet_permalink'],
        ];

        $cooked_i18n_js_vars = [
            'i18n_timer'               => __( 'Timer', 'cooked' ),
            'i18n_timer_pause'         => __( 'Pause timer', 'cooked' ),
            'i18n_timer_play'          => __( 'Resume timer', 'cooked' ),
            'i18n_timer_reset'         => __( 'Restart timer', 'cooked' ),
            'i18n_timer_close'         => __( 'Close timer', 'cooked' ),
            'i18n_browse_loading'      => __( 'Loading recipes…', 'cooked' ),
            'i18n_browse_results_one'  => __( '1 recipe found.', 'cooked' ),
            /* translators: %s: number of recipes found. */
            'i18n_browse_results_many' => __( '%s recipes found.', 'cooked' ),
            'i18n_browse_no_results'   => __( 'No recipes found.', 'cooked' ),
        ];

        $min = COOKED_DEV ? '' : '.min';

        wp_enqueue_style( 'cooked-essentials', COOKED_URL . 'assets/css/essentials.min.css', [], COOKED_VERSION );
        wp_enqueue_style( 'cooked-styling', COOKED_URL . 'assets/css/style.min.css', [ 'cooked-essentials' ], COOKED_VERSION );

        wp_register_style( 'cooked-tom-select', COOKED_URL . 'assets/vendor/tom-select/tom-select.default' . $min . '.css', [], '2.6.2' );
        wp_register_script( 'cooked-tom-select', COOKED_URL . 'assets/vendor/tom-select/tom-select.complete' . $min . '.js', [], '2.6.2', true );

        $load_tom_select = $browse_page_id && is_page( absint( $browse_page_id ) );

        if ( ! $load_tom_select && is_singular() ) {
            $queried_post = get_queried_object();

            if ( $queried_post instanceof \WP_Post && ( has_shortcode( $queried_post->post_content, 'cooked-browse' ) || has_shortcode( $queried_post->post_content, 'cooked-search' ) ) ) {
                $load_tom_select = true;
            }
        }

        if ( $load_tom_select ) {
            wp_enqueue_style( 'cooked-tom-select' );
            wp_enqueue_script( 'cooked-tom-select' );
        }

        wp_register_style( 'cooked-photoswipe', COOKED_URL . 'assets/vendor/photoswipe/photoswipe.css', [], '5.4.4' );
        wp_register_script( 'cooked-photoswipe', COOKED_URL . 'assets/vendor/photoswipe/photoswipe.umd.min.js', [], '5.4.4', true );
        wp_register_script( 'cooked-photoswipe-lightbox', COOKED_URL . 'assets/vendor/photoswipe/photoswipe-lightbox.umd.min.js', [ 'cooked-photoswipe' ], '5.4.4', true );
        wp_register_script( 'cooked-gallery', COOKED_URL . 'assets/js/cooked-gallery.js', [ 'cooked-photoswipe-lightbox' ], COOKED_VERSION, true );
        wp_register_script( 'cooked-timer', COOKED_URL . 'assets/vendor/jquery-simple-timer/jquery.simple.timer' . $min . '.js', [ 'jquery' ], '0.0.5', true );
        wp_register_script( 'cooked-nosleep', COOKED_URL . 'assets/vendor/nosleep/NoSleep' . $min . '.js', [], '0.12.0', true );

        // Compatibility with the Bridge Theme.
        if ( ! defined( 'QODE_ROOT' ) ) {
            wp_register_script( 'cooked-appear', COOKED_URL . 'assets/vendor/jquery-appear/jquery.appear' . $min . '.js', [ 'jquery' ], '0.3.6', true );
        }

        wp_enqueue_script( 'wp-sanitize' );
        wp_register_script( 'cooked-functions', COOKED_URL . 'assets/js/cooked-functions' . $min . '.js', [ 'jquery', 'wp-sanitize' ], COOKED_VERSION, true );
        wp_localize_script( 'cooked-functions', 'cooked_functions_i18n_js_vars', $cooked_i18n_js_vars );
        wp_add_inline_script( 'cooked-functions', 'const cooked_functions_js_vars = ' . wp_json_encode( $cooked_js_vars, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
    }

    /**
     * Loads the static colour stylesheet and prints the configured colours as CSS variables.
     *
     * @return void
     */
    public function css_colors() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only print view flag.
        if ( isset( $_GET['print'] ) ) {
            return;
        }

        wp_enqueue_style( 'cooked-colors', COOKED_URL . 'assets/css/colors.min.css', [ 'cooked-styling' ], COOKED_VERSION );

        $settings = \Cooked_Settings::get();
        wp_add_inline_style( 'cooked-colors', self::get_colors_css( $settings ) );
    }

    /**
     * The configured colours as CSS custom properties.
     *
     * @param array<string, mixed> $settings Cooked settings.
     * @return string
     */
    public static function get_colors_css( $settings ) {
        $main_color       = isset( $settings['main_color'] ) && is_string( $settings['main_color'] ) ? sanitize_hex_color( $settings['main_color'] ) : '';
        $main_color_hover = isset( $settings['main_color_hover'] ) && is_string( $settings['main_color_hover'] ) ? sanitize_hex_color( $settings['main_color_hover'] ) : '';

        if ( ! $main_color ) {
            $main_color = '#16a780';
        }

        if ( ! $main_color_hover ) {
            $main_color_hover = '#1b9371';
        }

        return ':root{'
            . '--cooked-main-color:' . esc_html( \Cooked_Functions::accessible_color( $main_color ) ) . ';'
            . '--cooked-main-color-hover:' . esc_html( \Cooked_Functions::accessible_color( $main_color_hover, 5.5 ) ) . ';'
            . '--cooked-main-color-ring:' . esc_html( $main_color ) . ';'
            . '}';
    }

    /**
     * Marks <html> for the static dark-mode rules.
     *
     * @param string $output The language attributes.
     * @return string
     */
    public function dark_mode_language_attributes( $output ) {
        if ( false !== strpos( $output, 'data-cooked-dark-mode' ) ) {
            return $output;
        }

        $settings = \Cooked_Settings::get();
        $mode     = \Cooked_Settings::normalize_dark_mode( isset( $settings['dark_mode'] ) ? $settings['dark_mode'] : 'off' );

        if ( 'enabled' === $mode ) {
            return $output . ' data-cooked-dark-mode="enabled"';
        }

        if ( 'auto' === $mode ) {
            return $output . ' data-cooked-dark-mode="auto"';
        }

        return $output;
    }

    /**
     * Prints the full-screen mode markup in the footer when a recipe needs it.
     *
     * @return void
     */
    public function footer_enqueues() {
        wp_enqueue_script( 'cooked-functions' );
    }
}
