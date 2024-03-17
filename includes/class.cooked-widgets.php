<?php
/**
 * Widgets
 *
 * @package     Cooked
 * @subpackage  Widgets
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once 'widgets/init.php';

class Cooked_Widgets {

	public function __construct() {
		add_action( 'widgets_init', array(&$this, 'register_widgets'), 10, 1 );
	}

	public function register_widgets() {
		$widgets = apply_filters( 'cooked_widgets', array(
			'Cooked_Widget_Nutrition',
			'Cooked_Widget_Search',
			'Cooked_Widget_Recipe_List',
			'Cooked_Widget_Recipe_Categories',
			'Cooked_Widget_Recipe_Card',
		));
		if ( !empty($widgets) ):
			foreach( $widgets as $widget ):
				register_widget( $widget );
			endforeach;
		endif;
	}

	public static function recipe_finder( $field_id = '', $field_name = '', $included = '' ) {
		$button_title = ( !empty( $included ) ? esc_html__( 'Edit Recipe(s)...', 'cooked' ) : esc_html__( 'Choose recipe(s)...', 'cooked' ) );
	    echo '<div style="margin:-10px 0 0 0;"><a href="#" class="button cooked-recipe-finder-show" id="' . esc_attr( $field_id ) . '-SHOW">' . esc_html( $button_title ) . '</a></div>';
	    echo '<select multiple class="widefat cooked-recipe-finder" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr__( 'Choose recipe(s)...', 'cooked' ) . '">';
	    if ( !empty( $included ) ):
	        foreach( $included as $recipe ):
	            $_recipe = Cooked_Recipes::get( $recipe, true );
	            echo '<option selected="selected" value="' . esc_attr( $_recipe['id'] ) . '">' . esc_html( $_recipe['title'] ) . '</option>';
	        endforeach;
	    endif;
	    echo '</select>';
	}

}
