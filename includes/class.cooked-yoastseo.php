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

        add_filter('wpseo_canonical', [$this, 'modify_browse_page_canonical_url'], 20, 2);
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

    public function modify_browse_page_canonical_url($canonical_url, $post = null) {
        global $_cooked_settings, $wp_query;

        if (!is_page()) {
            return $canonical_url;
        }

        $browse_page_id = !empty($_cooked_settings['browse_page']) ? $_cooked_settings['browse_page'] : false;

        // Only modify for browse page with category.
        if (is_page($browse_page_id) &&
            isset($wp_query->query['cp_recipe_category']) &&
            taxonomy_exists('cp_recipe_category') &&
            term_exists($wp_query->query['cp_recipe_category'], 'cp_recipe_category')) {

            // Build the canonical URL based on permalink structure.
            if (get_option('permalink_structure')) {
                $new_canonical = untrailingslashit(get_permalink($browse_page_id)) . '/' . $_cooked_settings['recipe_category_permalink'] . '/' . $wp_query->query['cp_recipe_category'];
            } else {
                $new_canonical = add_query_arg('cp_recipe_category', $wp_query->query['cp_recipe_category'], get_permalink($browse_page_id));
            }

            return $new_canonical;
        }

        return $canonical_url;
    }
}
