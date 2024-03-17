<?php
/**
 * Cooked User-Specific Functions
 *
 * @package     Cooked
 * @subpackage  User-Specific Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_User Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Users {

	function __construct(){
		add_action( 'init', array(&$this, 'recipe_author_rewrite'), 10 );
	}

	public static function recipe_author_rewrite() {

		global $_cooked_settings;

		$browse_page_id = ( isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] ? $_cooked_settings['browse_page'] : false );
		$front_page_id = get_option( 'page_on_front' );
		$browse_page_slug = ( $browse_page_id ? basename( get_permalink( $browse_page_id ) ) : false );
		if ( $browse_page_id != $front_page_id ):

			add_rewrite_tag('%recipe_author%', '([^&]+)');
			if ( isset( $_cooked_settings['browse_page'] ) ):
				add_rewrite_rule('^' . $browse_page_slug . '/' . $_cooked_settings['recipe_author_permalink'] . '/([^/]*)/([^/]*)/page/([^/]*)/?', 'index.php?page_id=' . esc_attr( $_cooked_settings['browse_page'] ) . '&paged=$matches[3]&recipe_author=$matches[1]', 'top' );
				add_rewrite_rule('^' . $browse_page_slug . '/' . $_cooked_settings['recipe_author_permalink'] . '/([^/]*)/?', 'index.php?page_id=' . esc_attr( $_cooked_settings['browse_page'] ) . '&recipe_author=$matches[1]', 'top' );
	  		endif;

	  	endif;
	}

	public static function get( $user_id, $basic = false ){

		$_user = get_userdata( $user_id );
		$_user_meta = get_user_meta( $user_id, 'cooked_user_meta', true );

		if ( !empty($_user) ):

			if ( isset( $_user_meta['profile_photo_id'] ) && $_user_meta['profile_photo_id'] && wp_attachment_is_image( $_user_meta['profile_photo_id'] ) ):
				$profile_photo = wp_get_attachment_image( $_user_meta['profile_photo_id'], 'cooked-square' );
				$profile_photo_src = wp_get_attachment_image_src( $_user_meta['profile_photo_id'], 'cooked-square' );
				$profile_photo_src = ( isset($profile_photo_src[0]) && $profile_photo_src[0] ? $profile_photo_src[0] : false );
			else :
				$profile_photo = get_avatar( $_user->user_email, 'cooked-square' );
				$profile_photo_src = get_avatar_url( $_user->user_email, 'cooked-square' );
			endif;

			if ( is_array($_user_meta) ):
				$_user_data = $_user_meta;
			endif;

			$_user_data['id'] = $user_id;
			$_user_data['name'] = self::format_author_name( $_user->display_name );
			$_user_data['profile_photo'] = $profile_photo;
			$_user_data['profile_photo_src'] = $profile_photo_src;

			if ( !$basic ):

				$_user_data['raw'] = $_user;

				$user_recipe_args = array(
					'post_type' => 'cp_recipe',
				 	'post_status' => 'publish',
				 	'posts_per_page' => -1,
				 	'author' => $user_id
				);

				$_user_data['recipes'] = Cooked_Recipes::get( $user_recipe_args, false, true );

			endif;

			return $_user_data;

		else:

			return false;

		endif;

	}

	public static function format_author_name( $name, $format = false ){

		if ( !$name )
			return false;

		global $_cooked_settings;
		$_cooked_settings = ( !$_cooked_settings || $_cooked_settings && empty($_cooked_settings) ? Cooked_Settings::get() : $_cooked_settings );
		if ( !$format && isset($_cooked_settings['author_name_format']) && $_cooked_settings['author_name_format'] ):
			$format = $_cooked_settings['author_name_format'];
		elseif(!$format):
			$format = 'full';
		endif;

		switch( $format ):
			case 'full':
				return $name;
			break;
			case 'first_last_initial':
				$name = explode( ' ', $name );
				if ( isset($name[1]) ):
					return $name[0] . ' ' . substr( $name[1], 0, 1) . '.';
				endif;
				return $name[0];
			break;
			case 'first_initial_last':
				$name = explode( ' ', $name );
				if ( isset($name[1]) ):
					return substr( $name[0], 0, 1) . '. ' . esc_html( $name[1] );
				endif;
				return $name[0];
			break;
			case 'first_only':
				$name = explode( ' ', $name );
				return esc_html( $name[0] );
			break;
		endswitch;

		return esc_html( $name );

	}

}
