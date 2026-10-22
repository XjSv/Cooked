<?php
/**
 * Cooked Elementor Support
 *
 * @package     Cooked
 * @subpackage  ELementor Support
 * @since       1.5.3
 */

namespace Cooked\Integrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor Class
 *
 * This class handles Elementor support.
 *
 * @since 1.5.3
 */
class Elementor {

    /**
     * Registers the Elementor integration.
     */
    public function __construct() {
        add_action( 'plugins_loaded', [ &$this, 'init' ] );
    }

    /**
     * Hooks into recipe content when Elementor is active.
     *
     * @return void
     */
    public function init() {
        // Check if Elementor installed and activated.
        if ( did_action( 'elementor/loaded' ) ) {
            add_filter( 'cooked_recipe_content_filter', [ &$this, 'elementor_filter' ], 15, 4 );
            add_filter( 'cooked_should_update_post_content', [ &$this, 'should_update_content' ], 10, 2 );
            // Recipe settings in the Elementor editor come from the pre_do_shortcode_tag filter in
            // src/Frontend/Shortcodes.php.
        }
    }

    /**
     * Uses the Elementor layout for recipes built with Elementor.
     *
     * @param string      $recipe_content Recipe content from the template.
     * @param string      $og_content     Original post content.
     * @param int         $recipe_id      Recipe ID.
     * @param string|null $layout_content Recipe layout, if any.
     * @return string
     */
    public function elementor_filter( $recipe_content, $og_content, $recipe_id, $layout_content = null ) {
        $elementor_page = get_post_meta( $recipe_id, '_elementor_edit_mode', true );

        if ( $elementor_page ) {
            $og_content = (string) $og_content;
            if ( \Cooked_Recipes::recipe_has_fullscreen( $recipe_id, $layout_content ) ) {
                global $recipe_settings;
                $og_content .= \Cooked_Recipes::get_fsm_markup( $recipe_id, $recipe_settings );
            }
            return $og_content;
        }

        return $recipe_content;
    }

    /**
     * Stops Cooked overwriting post content for recipes built with Elementor.
     *
     * @param bool $should_update Whether Cooked would update the content.
     * @param int  $recipe_id     Recipe ID.
     * @return bool
     */
    public function should_update_content( $should_update, $recipe_id ) {
        $elementor_page = get_post_meta( $recipe_id, '_elementor_edit_mode', true );

        if ( $elementor_page ) {
            return false;
        }

        return $should_update;
    }
}
