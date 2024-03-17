<?php
/**
 * Cooked AJAX-Specific Functions
 *
 * @package     Cooked
 * @subpackage  AJAX-Specific Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Ajax Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Ajax {

	function __construct(){

		/**
		 * Back-End Ajax
		 */

		// Save Default Template
		add_action( 'wp_ajax_cooked_save_default', array(&$this,'save_default') );

		// Save Default Template in Bulk
		add_action( 'wp_ajax_cooked_save_default_bulk', array(&$this,'save_default_bulk') );

		// Load Default Template
		add_action( 'wp_ajax_cooked_load_default', array(&$this,'load_default') );

		// Get JSON list of Recipe IDs
		add_action( 'wp_ajax_cooked_get_recipe_ids', array(&$this,'get_recipe_ids') );

		// Get JSON list of Recipe IDs, ready for Migration
		add_action( 'wp_ajax_cooked_get_migrate_ids', array(&$this,'get_migrate_ids') );

		// Migrate Recipes
		add_action( 'wp_ajax_cooked_migrate_recipes', array(&$this,'migrate_recipes') );

	}

	public function get_migrate_ids(){

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		$old_recipes = get_transient( 'cooked_classic_recipes' );
		if ( $old_recipes != 'complete' ):
			$total = count($old_recipes);
			if ( $total > 0 ):
				echo json_encode( $old_recipes );
			else:
				echo 'false';
			endif;
		else:
			echo 'false';
		endif;
		wp_die();

	}

	public function migrate_recipes(){

		$bulk_amount = 10;

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		if ( isset($_POST['recipe_ids']) ):
			
			// Sanitize Recipe IDs
			$recipe_ids = json_decode( $_POST['recipe_ids'], true );
			if ( is_array( $recipe_ids ) && !empty( $recipe_ids ) ):
				$_recipe_ids = [];
				foreach( $recipe_ids as $_rid ):
					$safe_id = intval( $_rid );
					if ( $safe_id ):
						$_recipe_ids[] = $_rid;	
					endif;
				endforeach;
				$recipe_ids = $_recipe_ids;
			else:
				return false;	
			endif;
			
			$leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
			$recipe_ids = array_slice( $recipe_ids, 0, $bulk_amount );

			if ( !empty($recipe_ids) ):

				foreach( $recipe_ids as $rid ):

					$recipe_settings = Cooked_Recipes::get_settings( $rid );

					if ( !empty( $recipe_settings ) && !isset( $recipe_settings['cooked_version'] ) || !empty( $recipe_settings ) && isset( $recipe_settings['cooked_version'] ) && !$recipe_settings['cooked_version'] ):

						$recipe_settings['cooked_version'] = COOKED_VERSION;

						// Migrate the recipe settings.
        				update_post_meta( $rid, '_recipe_settings', $recipe_settings );
        				$recipe_excerpt = ( isset($recipe_settings['excerpt']) && $recipe_settings['excerpt'] ? $recipe_settings['excerpt'] : get_the_title( $rid ) );

        				$seo_content = apply_filters( 'cooked_seo_recipe_content', '[cooked-excerpt]<h2>' . __('Ingredients','cooked') . '</h2>[cooked-ingredients checkboxes=false]<h2>' . __('Directions','cooked') . '</h2>[cooked-directions numbers=false]' );
        				$seo_content = do_shortcode( $seo_content );

						wp_update_post( array( 'ID' => $rid, 'post_excerpt' => $recipe_excerpt, 'post_content' => $seo_content ) );

				 	endif;
			 	endforeach;

				if ( !empty( $leftover_recipe_ids ) ):
					echo json_encode( $leftover_recipe_ids );
					wp_die();
				endif;

			endif;

			set_transient( 'cooked_classic_recipes', 'complete', 60 * 60 * 24 * 7 );
			echo 'false';
			wp_die();

		endif;

		wp_die();

	}

	public function get_recipe_ids(){

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		$args = array(
			'post_type' => 'cp_recipe',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids'
		);

		$_recipe_ids = Cooked_Recipes::get( $args, false, true );
		echo json_encode( $_recipe_ids );
		wp_die();

	}

	public function save_default_bulk(){

		$bulk_amount = 5;

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		if ( isset($_POST['recipe_ids']) ):
			
			// Sanitize Recipe IDs
			$recipe_ids = json_decode( $_POST['recipe_ids'], true );
			if ( is_array( $recipe_ids ) && !empty( $recipe_ids ) ):
				$_recipe_ids = [];
				foreach( $recipe_ids as $_rid ):
					$safe_id = intval( $_rid );
					if ( $safe_id ):
						$_recipe_ids[] = $_rid;	
					endif;
				endforeach;
				$recipe_ids = $_recipe_ids;
			else:
				return false;	
			endif;

			$leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
			$recipe_ids = array_slice( $recipe_ids, 0, $bulk_amount );

			if ( empty($recipe_ids) ):
				echo 'false';
				wp_die();
			else:

				foreach( $recipe_ids as $rid ):
					$recipe_settings = get_post_meta( $rid, '_recipe_settings', true );
					if ( !empty( $recipe_settings ) ):
						$recipe_settings['content'] = wp_kses_post( $_POST['default_content'] );
				 		update_post_meta( $rid, '_recipe_settings', $recipe_settings );
				 	endif;
			 	endforeach;

				if ( !empty( $leftover_recipe_ids ) ):
					echo json_encode( $leftover_recipe_ids );
					wp_die();
				else:
					echo 'false';
					wp_die();
				endif;

			endif;

		endif;

		wp_die();

	}

	public function save_default(){

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		global $_cooked_settings;

		if ( isset($_POST['default_content']) ):
			$_cooked_settings['default_content'] = wp_kses_post( $_POST['default_content'] );
			update_option( 'cooked_settings',$_cooked_settings );
		else:
			echo 'No default content provided.';
		endif;

		wp_die();

	}

	public function load_default(){

		if ( !current_user_can('edit_cooked_recipes') ):
			wp_die();
		endif;

		global $_cooked_settings;
		if ( isset($_cooked_settings['default_content']) ):
			$default_content = stripslashes( $_cooked_settings['default_content'] );
		else:
			$default_content = Cooked_Recipes::default_content();
		endif;

		echo wp_kses_post( $default_content );

		wp_die();

	}

}
