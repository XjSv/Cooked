<?php
/**
 * Widgets
 *
 * @package     Cooked
 * @subpackage  Widgets
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

require_once 'widgets/init.php';

class Cooked_Widgets {

	public function __construct() {
		add_action( 'widgets_init', [ &$this, 'register_widgets' ], 10, 1 );
	}

	public function register_widgets() {
		$widgets = apply_filters( 'cooked_widgets', [
			'Cooked_Widget_Nutrition',
			'Cooked_Widget_Search',
			'Cooked_Widget_Recipe_List',
			'Cooked_Widget_Recipe_Categories',
			'Cooked_Widget_Recipe_Card',
		] );
		if ( ! empty( $widgets ) ) :
			foreach ( $widgets as $widget ) :
				register_widget( $widget );
			endforeach;
		endif;
	}

	public static function recipe_finder( $field_id = '', $field_name = '', $included = '' ) {
		$button_title = ( ! empty( $included ) ? __( 'Edit Recipe(s)...', 'cooked' ) : __( 'Choose recipe(s)...', 'cooked' ) );
		echo '<div style="margin:-10px 0 0 0;"><a href="#" class="button cooked-recipe-finder-show" id="' . esc_attr( $field_id ) . '-SHOW">' . esc_html( $button_title ) . '</a></div>';
		echo '<select multiple class="widefat cooked-recipe-finder" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . __( 'Choose recipe(s)...', 'cooked' ) . '">';
		if ( ! empty( $included ) ) :
			foreach ( $included as $recipe ) :
				$recipe_status = get_post_status( $recipe );
				if ( $recipe_status === 'publish' ) :
					$_recipe = Cooked_Recipes::get( $recipe, true, false, false, true );
					echo '<option selected="selected" value="' . esc_attr( $_recipe['id'] ) . '">' . esc_html( $_recipe['title'] ) . '</option>';
				endif;
			endforeach;
		endif;
		echo '</select>';
	}

}
