<?php
/**
 * Cooked Elementor Support
 *
 * @package     Cooked
 * @subpackage  ELementor Support
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Elementor Class
 *
 * This class handles Elementor support.
 *
 * @since 1.0.0
 */
class Cooked_Elementor {

    public function __construct() {
        add_action( 'plugins_loaded', [&$this, 'init'] );
    }

    public function init() {
        // Check if Elementor installed and activated
        if ( did_action( 'elementor/loaded' ) ) {
            add_filter( 'cooked_recipe_content_filter', [&$this, 'elementor_filter'], 15, 4 );
            add_filter( 'cooked_should_update_post_content', [&$this, 'should_update_content'], 10, 2 );
            // Deprecated. Now handled in pre_do_shortcode_tag filter in class.cooked-shortcodes.php.
            // add_action( 'elementor/element/before_section_start', [&$this, 'elementor_is_editing'], 10, 3 );
        }
    }

    // Load the recipe_settings when needed so we can display shortcode content in the editor.
    /* public function elementor_is_editing( $element, $section_id, $args ) {
        $post_id = get_the_ID();

        if ( !isset($recipe_settings) || isset($recipe_settings) && !isset($recipe_settings['author']) ) {
            if ( get_post_type( $post_id ) === 'cp_recipe' ) {
                global $recipe_settings;
                $recipe_settings = Cooked_Recipes::get_settings( $post_id );
            } else {
                // We are in the editor but not on a recipe post type. Maybe a single recipe template?
                // Uses the first recipe found in the database as a sample.
                $recipe_settings = Cooked_Recipes::get( false, true );
            }
        }
    } */

    public function elementor_filter( $recipe_content, $og_content, $recipe_id, $layout_content = null ) {
        $elementor_page = get_post_meta( $recipe_id, '_elementor_edit_mode', true );

        if ( $elementor_page ) {
            $og_content = (string) $og_content;
            if ( Cooked_Recipes::recipe_has_fullscreen( $recipe_id, $layout_content ) ) {
                global $recipe_settings;
                $og_content .= Cooked_Recipes::get_fsm_markup( $recipe_id, $recipe_settings );
            }
            return $og_content;
        }

        return $recipe_content;
    }

    public function should_update_content( $should_update, $recipe_id ) {
        $elementor_page = get_post_meta( $recipe_id, '_elementor_edit_mode', true );

        if ( $elementor_page ) {
            return false;
        }

        return $should_update;
    }

}
