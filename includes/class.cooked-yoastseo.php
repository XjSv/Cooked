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
        wpseo_register_var_replacement( '%%cooked_recipe_category%%', [$this, 'get_cooked_recipe_category'], 'advanced', __( 'Current recipe category being viewed.', 'cooked' ) );
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
