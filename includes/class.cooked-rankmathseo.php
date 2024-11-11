<?php
/**
 * Cooked Rank Math SEO Support
 *
 * @package     Cooked
 * @subpackage  Rank Math SEO Support
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_RankMathSEO Class
 *
 * This class handles Rank Math SEO support.
 *
 * @since 1.0.0
 */
class Cooked_RankMathSEO {

    private $variable_registered = false;

    public function __construct() {
        if (!$this->variable_registered) {
            add_action('rank_math/vars/register_extra_replacements', [$this, 'register_rank_math_variables']);
        }
    }

    public function register_rank_math_variables() {
		rank_math_register_var_replacement(
			'cooked_recipe_category',
            [
                'name'        => __( 'Recipe Category', 'cooked' ),
                'description' => __( 'Current recipe category being viewed', 'cooked' ),
                'variable'    => 'cooked_recipe_category',
                'example'     => $this->get_cooked_recipe_category(),
            ],
            [ $this, 'get_cooked_recipe_category' ]
		);
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

		// Look for recipe-category segment
		$permalink = isset($_cooked_settings['recipe_category_permalink']) && $_cooked_settings['recipe_category_permalink'] ? $_cooked_settings['recipe_category_permalink'] : 'recipe-category';
		$key = array_search($permalink, $parts);
		if ($key !== false && isset($parts[$key + 1])) {
			$term_slug = $parts[$key + 1];
			$term = get_term_by('slug', $term_slug, 'cp_recipe_category');
			return $term ? $term->name : '';
		}

		// Fallback to query param - with sanitization
		if (isset($_GET['cp_recipe_category'])) {
			$slug = sanitize_text_field($_GET['cp_recipe_category']);
			$term = get_term_by('slug', $slug, 'cp_recipe_category');
			return $term ? $term->name : '';
		}

		return '';
    }

}
