<?php
/**
 * Cooked Gutenberg Functions
 *
 * @package     Cooked
 * @subpackage  Gutenberg Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Gutenberg Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Gutenberg {

    public function __construct(){
        add_filter( 'use_block_editor_for_post_type', array( &$this, 'gutenberg_support' ), 10, 2 );
        add_filter( 'gutenberg_can_edit_post_type', array( &$this, 'gutenberg_support' ), 10, 2 );
    }

    public function gutenberg_support( $can_edit, $post_type ){

        if ( $post_type == 'cp_recipe' )
            return false;

        return $can_edit;

    }

}