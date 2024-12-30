<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Enqueues
 * @since       1.0.0
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Cooked_Enqueues {

    function __construct() {
        add_action('wp_enqueue_scripts', [&$this, 'enqueues'], 10, 1);
        add_action('wp_enqueue_scripts', [&$this, 'css_colors'], 11);
        add_action('wp_enqueue_scripts', [&$this, 'css_responsive'], 11);
        add_action('wp_footer', [&$this, 'footer_enqueues']);
    }

    public function enqueues($hook) {
        global $_cooked_settings;

        $browse_page_id = !empty($_cooked_settings['browse_page']) ? $_cooked_settings['browse_page'] : false;
        $browse_page = get_post($browse_page_id);
        $browse_recipes_slug = !empty($browse_page) ? $browse_page->post_name : '';

        $cooked_js_vars = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'timer_sound' => apply_filters('cooked_timer_sound_mp3', COOKED_URL . 'assets/audio/ding.mp3'),
            'i18n_timer' => __('Timer', 'cooked'),
            'permalink_structure' => get_option('permalink_structure'),
            'browse_recipes_slug' => $browse_recipes_slug,
            'recipe_category_slug' => !isset($_cooked_settings['recipe_category_permalink']) ? 'recipe-category' : $_cooked_settings['recipe_category_permalink'],
            'recipe_cooking_method_slug' => !isset($_cooked_settings['recipe_cooking_method_permalink']) ? 'cooking-method' : $_cooked_settings['recipe_cooking_method_permalink'],
            'recipe_cuisine_slug' => !isset($_cooked_settings['recipe_cuisine_permalink']) ? 'cuisine' : $_cooked_settings['recipe_cuisine_permalink'],
            'recipe_tags_slug' => !isset($_cooked_settings['recipe_tag_permalink']) ? 'recipe-tag' : $_cooked_settings['recipe_tag_permalink'],
            'recipe_diet_slug' => !isset($_cooked_settings['recipe_diet_permalink']) ? 'diet' : $_cooked_settings['recipe_diet_permalink'],
        ];

        $min = COOKED_DEV ? '' : '.min';

        wp_enqueue_style('cooked-essentials', COOKED_URL . 'assets/admin/css/essentials' . $min . '.css', [], COOKED_VERSION);
        wp_enqueue_style('cooked-icons', COOKED_URL . 'assets/css/icons' . $min . '.css', [], COOKED_VERSION);
        wp_enqueue_style('cooked-styling', COOKED_URL . 'assets/css/style' . $min . '.css', [], COOKED_VERSION );
        wp_register_style('cooked-fotorama', COOKED_URL . 'assets/css/fotorama/fotorama.min.css', [], '4.6.4');
        wp_register_script('cooked-fotorama', COOKED_URL . 'assets/js/fotorama/fotorama' . $min . '.js', ['jquery'], '4.6.4');
        wp_register_script('cooked-timer', COOKED_URL . 'assets/js/timer/jquery.simple.timer' . $min . '.js', ['jquery'], '0.0.5');
        wp_register_script('cooked-nosleep', COOKED_URL . 'assets/js/nosleep/NoSleep' . $min . '.js', [], '0.12.0');

        // Compatibility with the Bridge Theme.
        if (!defined('QODE_ROOT')) {
            wp_register_script('cooked-appear', COOKED_URL . 'assets/js/appear/jquery.appear' . $min . '.js', ['jquery'], '0.3.6');
        }

        wp_register_script('cooked-functions', COOKED_URL . 'assets/js/cooked-functions' . $min . '.js', ['jquery'], COOKED_VERSION);
        wp_localize_script('cooked-functions', 'cooked_js_vars', $cooked_js_vars);
    }

    public function css_colors() {
        if (!isset($_GET['print'])) {
            $file = COOKED_DIR . 'assets/css/colors.php';
            $css = self::get_dynamic_css($file);
            wp_add_inline_style('cooked-styling', $css);
        }
    }

    public function css_responsive() {
        if (!isset($_GET['print'])) {
            $file = COOKED_DIR . 'assets/css/responsive.php';
            $css = self::get_dynamic_css($file);
            wp_add_inline_style('cooked-styling', $css);
        }
    }

    public static function get_dynamic_css($file = false) {
        if (!$file || $file && !file_exists($file)) return;

        ob_start();
        include $file;
        $css = ob_get_clean();
        $compressed_css = self::compress_css($css);

        return $compressed_css;
    }

    public static function compress_css($css) {
        // Remove tabs, spaces, newlines, etc.
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        return $css;
    }

    public function footer_enqueues() {
        wp_enqueue_script('cooked-functions');
    }

}
