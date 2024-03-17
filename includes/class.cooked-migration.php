<?php
/**
 * Tools for Migration from Cooked Classic
 *
 * @package     Cooked
 * @subpackage  Migration
 * @since       1.0.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Migration Class
 *
 * This class handles the migration from Cooked Classic.
 *
 * @since 1.0.0
 */
class Cooked_Migration {

	public function __construct(){
		add_action( 'plugins_loaded', array( &$this, 'init' ) );
	}

	public static function init() {

		// Check for recipes from Cooked Classic and display an "Update" notice
		$old_recipes = self::get_cooked_classic_recipes();

		if ( $old_recipes != 'complete' && !empty($old_recipes) ):

			$total_old_recipes = count( $old_recipes );

			if ( $total_old_recipes > 0 ):
				add_filter( 'cooked_settings_tabs_fields', array( 'Cooked_Migration', 'settings_filter' ), 10, 1 );
				add_action( 'admin_notices', array( 'Cooked_Migration', 'old_recipes_message' ), 10 );
			endif;

		endif;

	}

	public static function settings_filter( $settings ){

		$old_recipes = self::get_cooked_classic_recipes();

		if ( $old_recipes != 'complete' && !empty($old_recipes) ):

			$total = count( $old_recipes );

			$html_desc = sprintf( esc_html( _n( 'There is %s recipe that should be migrated from %s to take advantage of new features and reliability.', 'There are %s recipes that should be migrated from %s to take advantage of new features and reliability.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong>Cooked Classic</strong>' );
			$html_desc .= '<br>';
			$html_desc .= esc_html__( 'Please click the button below to migrate these recipes. Here is what will happen to your recipes:', 'cooked' );
			$html_desc .= '<ul class="cooked-admin-ul">';
				$html_desc .= '<li>' . esc_html__( 'NO DATA LOSS, all fields will be remapped.', 'cooked' ) . '</li>';
				$html_desc .= '<li>' . esc_html__( 'Remapped fields will greatly speed up recipe loading times.', 'cooked' ) . '</li>';
				$html_desc .= '<li>' . esc_html__( 'If recipe excerpt exists, the short description will be moved to the top of the recipe template.', 'cooked' ) . '</li>';
				$html_desc .= '<li>' . esc_html__( 'If no recipe excerpt exists, the short description will be used instead.', 'cooked' ) . '</li>';
				$html_desc .= '<li>' . esc_html__( 'Version number will be applied to each recipe.', 'cooked' ) . '</li>';
			$html_desc .= '</ul>';
			if ( $total > 2000 ):
				$html_desc .= '<p><strong>' . esc_html__( 'Wow, you have a lot of recipes!', 'cooked' ) . '</strong><br><em style="color:#333;">' . esc_html__( 'It is definitely recommended that you get yourself a cup of coffee or tea after clicking this button.', 'cooked' ) . '</em></p>';
			else:
				$html_desc .= '<p><strong>' . esc_html__( 'Note:', 'cooked' ) . '</strong> ' . esc_html__( 'The more recipes you have, the longer this will take.', 'cooked' ) . '</p>';
			endif;

			if ( $total > 0 ):
				$settings['migration'] = array(
					'name' => esc_html__('Migration','cooked'),
					'icon' => 'migrate',
					'fields' => array(
						'cooked_migrate_button' => array(
							'title' => 'Cooked Classic&nbsp;&nbsp;<i class="cooked-icon cooked-icon-angle-right"></i>&nbsp;&nbsp;Cooked',
							'desc' => $html_desc,
							'type' => 'migrate_button'
						)
					)
				);
			endif;

		endif;

		return $settings;

	}

	public static function old_recipes_message(){

		$old_recipes = get_transient( 'cooked_classic_recipes' );

		if ( $old_recipes != 'complete' ):

			$total = count($old_recipes);

			if ( $total > 0 ):
				$class = 'notice notice-error';
				$message = sprintf( esc_html( _n( 'There is %s recipe that is from an older version of Cooked. Please %s to migrate this recipe.', 'There are %s recipes that are from an older version of Cooked. Please %s to migrate these recipes.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong><a href="' . esc_url( add_query_arg( array( 'page' => 'cooked_settings', 'cm' => '1' ), admin_url( 'admin.php' ) ) ) . '#migration">' . __( 'click here', 'cooked' ) . '</a></strong>' );
				printf( '<div class="%1$s" style="padding:10px 20px"><p style="font-size:1.2em">%2$s</p></div>', esc_attr( $class ), $message );
			endif;

		endif;

	}

	public static function get_cooked_classic_recipes(){

		$classic_recipes = get_transient( 'cooked_classic_recipes' );
		if ( empty($classic_recipes) && $classic_recipes != 'complete' ):

			$classic_recipes = array();

			$args = array(
				'post_type' => 'cp_recipe',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'fields' => 'ids',
				'meta_query' => array(
			        'settings_clause' => array(
			            'key' => '_recipe_settings',
			            'compare' => 'NOT EXISTS',
			        )
			    )
			);

			$_recipes = Cooked_Recipes::get( $args, false, true );
			if ( !empty($_recipes) ):
				foreach( $_recipes as $rid ):

					$recipe_settings = Cooked_Recipes::get_settings( $rid, false );
					if ( !isset($recipe_settings['cooked_version']) ):
						$classic_recipes[] = $rid;
					endif;

				endforeach;
			else:
				$classic_recipes = array();
			endif;

		endif;

		if ( empty($classic_recipes) ):
			set_transient( 'cooked_classic_recipes', 'complete', 60 * 60 * 24 * 7 );
			return 'complete';
		else:
			set_transient( 'cooked_classic_recipes', $classic_recipes, 60 * 60 );
			return $classic_recipes;
		endif;

	}

}
