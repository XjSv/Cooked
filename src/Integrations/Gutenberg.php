<?php
/**
 * Cooked Gutenberg Functions
 *
 * @package     Cooked
 * @subpackage  Gutenberg Functions
 * @since       1.5.2
 */

namespace Cooked\Integrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.5.2
 */
class Gutenberg {

    /**
     * Registers the block editor integration.
     */
    public function __construct() {
        add_filter( 'use_block_editor_for_post_type', [ &$this, 'gutenberg_support' ], 10, 2 );
        add_filter( 'gutenberg_can_edit_post_type', [ &$this, 'gutenberg_support' ], 10, 2 );
    }

    /**
     * Uses the classic editor for recipes.
     *
     * @param bool   $can_edit  Whether the block editor would be used.
     * @param string $post_type Post type.
     * @return bool
     */
    public function gutenberg_support( $can_edit, $post_type ) {
        if ( 'cp_recipe' === $post_type ) {
            return false;
        }

        return $can_edit;
    }
}
