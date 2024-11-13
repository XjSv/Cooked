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
                'description' => __( 'Current recipe category being viewed.', 'cooked' ),
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
        global $wp_query;

        if ( isset($wp_query->query['cp_recipe_category']) && taxonomy_exists('cp_recipe_category') && term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ) {
            $cooked_term = get_term_by( 'slug', $wp_query->query['cp_recipe_category'], 'cp_recipe_category' );

            if (!empty($cooked_term) && $cooked_term->name) {
                return $cooked_term->name;
            }
        }

        return '';
    }

}
