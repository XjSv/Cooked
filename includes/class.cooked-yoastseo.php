<?php
/**
 * Cooked Yoast SEO Support
 *
 * @package     Cooked
 * @subpackage  Yoast SEO Support
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_YoastSEO Class
 *
 * This class handles Yoast SEO support.
 *
 * @since 1.0.0
 */
class Cooked_YoastSEO {

    private $variable_registered = false;

    public function __construct() {
        if (!$this->variable_registered) {
            add_action('wpseo_register_extra_replacements', [$this, 'cooked_register_extra_yoast_variables']);
        }
    }

    public function cooked_register_extra_yoast_variables() {
        wpseo_register_var_replacement( '%%cooked_recipe_category%%', [$this, 'get_cooked_recipe_category'], 'advanced', __( 'Current Cooked recipe category being viewed', 'cooked' ) );
        $this->variable_registered = true;
	}

	/**
     * Retrieves the current recipe category.
     *
     * @return string
     */
    public function get_cooked_recipe_category() {
        global $_cooked_settings;

        // Sanitize the REQUEST_URI before parsing.
        $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        $parts = explode('/', $path);

		// Look for recipe-category segment.
		$permalink = isset($_cooked_settings['recipe_category_permalink']) && $_cooked_settings['recipe_category_permalink'] ? $_cooked_settings['recipe_category_permalink'] : 'recipe-category';
		$key = array_search($permalink, $parts);
		if ($key !== false && isset($parts[$key + 1])) {
			$term_slug = $parts[$key + 1];
			$term = get_term_by('slug', $term_slug, 'cp_recipe_category');
			return $term ? $term->name : '';
		}

		// Fallback to query param - with sanitization.
		if (isset($_GET['cp_recipe_category'])) {
			$slug = sanitize_text_field($_GET['cp_recipe_category']);
			$term = get_term_by('slug', $slug, 'cp_recipe_category');
			return $term ? $term->name : '';
		}

		return '';
    }

}
