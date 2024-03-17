<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Admin Enqueues
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Cooked_Admin_Enqueues {

	public static $admin_colors;

	function __construct() {

		add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueues'), 10, 1 );
		add_action( 'admin_enqueue_scripts', array(&$this, 'widget_enqueues'), 11, 1 );
		add_action( 'customize_controls_enqueue_scripts', array(&$this, 'enqueue_widgets'), 10, 1 );

	}

	public static function enqueue_widgets(){

		$cooked_js_vars = array(
			'rest_url' => esc_url( get_rest_url() ),
		);

		// Gonna need jQuery
		wp_enqueue_script( 'jquery' );

		// Selectize (searchable select fields)
		wp_enqueue_style( 'cooked-selectize', COOKED_URL . '/assets/admin/css/selectize/selectize.css' );
    	wp_enqueue_style( 'cooked-selectize-custom', COOKED_URL . '/assets/admin/css/selectize/cooked-selectize.css' );
    	wp_enqueue_script( 'cooked-selectize', COOKED_URL . '/assets/admin/js/selectize/selectize.min.js', array('jquery'), '0.12.6', true );
        wp_enqueue_script( 'cooked-microplugin', COOKED_URL . '/assets/admin/js/selectize/microplugin.min.js', array('jquery'), '0.0.3', true );

        // Cooked Widgets JS
    	wp_register_script( 'cooked-widgets', COOKED_URL . '/assets/admin/js/cooked-widgets.js', array('jquery'), COOKED_VERSION, true );
        wp_localize_script( 'cooked-widgets', 'cooked_js_vars', $cooked_js_vars );
		wp_enqueue_script( 'cooked-widgets');

	}

	public function widget_enqueues( $hook ) {
		if ( $hook == 'widgets.php' ):
			self::enqueue_widgets();
        endif;
	}

	public function admin_enqueues( $hook ) {

		global $post,$typenow,$pagenow;

		$cooked_admin_hooks = array(
			'index.php',
			'post-new.php',
			'post.php',
			'edit.php',
			'cooked_settings',
			'cooked_welcome',
			'cooked_pending',
			'cooked_pro'
		);

		$min = COOKED_DEV ? '' : '.min';

		// Required Assets for Entire Admin (icons, etc.)
		wp_enqueue_style( 'cooked-essentials', COOKED_URL . 'assets/admin/css/essentials'.$min.'.css', array(), COOKED_VERSION );
		wp_enqueue_style( 'cooked-icons', COOKED_URL . 'assets/css/icons'.$min.'.css', array(), COOKED_VERSION );

		$load_cooked_admin_assets = false;

		foreach( $cooked_admin_hooks as $hook_slug ):
			if ( strpos( $hook, $hook_slug ) || $hook_slug == $hook ):
				$load_cooked_admin_assets = true;
			endif;
		endforeach;

	    if ( $load_cooked_admin_assets ) {

		    if (function_exists('get_current_screen')):

		    	$screen = get_current_screen();
		    	$post_type = $screen->post_type;

				if ($hook != 'post-new.php' && $hook != 'post.php' && $hook != 'index.php' && $hook != 'edit.php' || $hook === 'post-new.php' && $post_type === 'cp_recipe' || $hook === 'post.php' && $post_type === 'cp_recipe' || $hook === 'edit.php' && $post_type === 'cp_recipe' || $hook === 'index.php' || $hook === 'widgets.php'):
					$enqueue = true;
					add_thickbox();
				else:
					$enqueue = false;
				endif;
			else:
				$enqueue = true;
			endif;

			if ($enqueue):

				$old_recipes = get_transient( 'cooked_classic_recipes' );
				if ( $old_recipes != 'complete' ):
					$total_old_recipes = count( $old_recipes );
				else:
					$total_old_recipes = 0;
				endif;

				// Gonna need jQuery
				wp_enqueue_media();
	            wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-draggable' );
				wp_enqueue_script( 'jquery-ui-resizable' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-slider' );

				wp_enqueue_style( 'cooked-switchery', COOKED_URL . 'assets/admin/css/switchery/switchery.min.css', array(), COOKED_VERSION );
	    		wp_enqueue_script( 'cooked-switchery', COOKED_URL . 'assets/admin/js/switchery/switchery.min.js', array(), COOKED_VERSION, true );
	    		wp_enqueue_script( 'cooked-vue', COOKED_URL . 'assets/admin/js/vue/vue'.$min.'.js', array(), null, false );

		        $cooked_js_vars = array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'cooked_plugin_url' => COOKED_URL,
					'time_format' => get_option('time_format','g:ia'),
					'i18n_remaining' => esc_html__( 'remaining', 'cooked' ),
					'i18n_image_title' => esc_html__( 'Add Image', 'cooked' ),
					'i18n_image_change' => esc_html__( 'Change Image', 'cooked' ),
	                'i18n_image_button' => esc_html__( 'Use this Image', 'cooked' ),
	                'i18n_gallery_image_title' => esc_html__( 'Add to Gallery', 'cooked' ),
	                'i18n_edit_image_title' => esc_html__( 'Edit Gallery Item', 'cooked' ),
	                'i18n_edit_image_button' => esc_html__( 'Update Gallery Item', 'cooked' ),
	                'i18n_saved' => esc_html__('Saved','cooked'),
	                'i18n_applied' => esc_html__('Applied','cooked'),
					'i18n_confirm_save_default_all' => esc_html__('Are you sure you want to apply this new template to all of your recipes?','cooked'),
					'i18n_confirm_load_default' => esc_html__('Are you sure you want to reset this recipe template to the Cooked plugin default?','cooked'),
					'i18n_confirm_migrate_recipes' => sprintf( esc_html__('Please confirm that you are ready to migrate all %s recipes.','cooked'), number_format($total_old_recipes) ),
				);

				// Cooked Admin Style Assets
		    	wp_register_script( 'cooked-functions', COOKED_URL . 'assets/admin/js/cooked-functions'.$min.'.js', array('jquery'), COOKED_VERSION, true );
		    	wp_register_script( 'cooked-migration', COOKED_URL . 'assets/admin/js/cooked-migration'.$min.'.js', array('jquery'), COOKED_VERSION, true );
				wp_enqueue_style( 'cooked-admin', COOKED_URL . 'assets/admin/css/style'.$min.'.css', array(), COOKED_VERSION );
				wp_enqueue_style( 'wp-color-picker' );

				// Tooltipster
				wp_enqueue_script('cooked-tooltipster', COOKED_URL . 'assets/admin/js/tooltipster/jquery.tooltipster.min.js', array('jquery'), COOKED_VERSION, true );
				wp_enqueue_style('cooked-tooltipster-core', COOKED_URL . 'assets/admin/css/tooltipster/tooltipster.min.css', array(), COOKED_VERSION, 'screen' );
				wp_enqueue_style('cooked-tooltipster-theme', COOKED_URL . 'assets/admin/css/tooltipster/themes/tooltipster-light.min.css', array(), COOKED_VERSION, 'screen' );

				// Cooked Admin Script
				wp_localize_script('cooked-functions', 'cooked_js_vars', $cooked_js_vars );
				wp_localize_script('cooked-migration', 'cooked_js_vars', $cooked_js_vars );
				wp_enqueue_script('cooked-functions');
				wp_enqueue_script('cooked-migration');

			endif;

	    }

	}

}
