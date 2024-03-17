<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Enqueues
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
class Cooked_Enqueues {

	function __construct() {
		add_action( 'wp_enqueue_scripts', array(&$this, 'enqueues'), 10, 1 );
		add_action( 'wp_enqueue_scripts', array(&$this, 'css_colors'), 11 );
		add_action( 'wp_enqueue_scripts', array(&$this, 'css_responsive'), 11 );
		add_action( 'wp_footer', array(&$this, 'footer_enqueues') );
	}

	public function enqueues( $hook ) {

		global $_cooked_settings;

		$cooked_js_vars = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'timer_sound' => apply_filters( 'cooked_timer_sound_mp3', COOKED_URL . 'assets/audio/ding.mp3' ),
			'i18n_timer' => esc_html__( 'Timer','cooked' ),
		);

		$min = ( COOKED_DEV ? '' : '.min' );

		wp_enqueue_style( 'cooked-essentials', COOKED_URL . 'assets/admin/css/essentials'.$min.'.css', array(), COOKED_VERSION );
		wp_enqueue_style( 'cooked-icons', COOKED_URL . 'assets/css/icons'.$min.'.css', array(), COOKED_VERSION );
		wp_enqueue_style( 'cooked-styling', COOKED_URL . 'assets/css/style'.$min.'.css', array(), COOKED_VERSION );
		wp_register_style( 'cooked-fotorama-style', COOKED_URL . 'assets/css/fotorama/fotorama.min.css', array(), '4.6.4' );
		wp_register_script( 'cooked-fotorama-js', COOKED_URL . 'assets/js/fotorama/fotorama.min.js', array('jquery'), '4.6.4', true );
		wp_register_script( 'cooked-timer', COOKED_URL . 'assets/js/timer/jquery.simple.timer.min.js', array('jquery'), COOKED_VERSION, true );
 
		if ( !defined('QODE_ROOT') ): // Compatibility with the Bridge Theme
			wp_register_script( 'cooked-appear-js', COOKED_URL . 'assets/js/appear/jquery.appear.min.js', array('jquery'), COOKED_VERSION, true );
		endif;

		wp_register_script( 'cooked-functions-js', COOKED_URL . 'assets/js/cooked-functions'.$min.'.js', array('jquery'), COOKED_VERSION, true );
		wp_localize_script( 'cooked-functions-js', 'cooked_js_vars', $cooked_js_vars );

	}

	public function css_colors(){
		if ( !isset($_GET['print']) ):
			$file = COOKED_DIR . 'assets/css/colors.php';
			$css = self::get_dynamic_css( $file );
			wp_add_inline_style( 'cooked-styling', $css );
		endif;
	}

	public function css_responsive(){
		if ( !isset($_GET['print']) ):
			$file = COOKED_DIR . 'assets/css/responsive.php';
			$css = self::get_dynamic_css( $file );
			wp_add_inline_style( 'cooked-styling', $css );
		endif;
	}

	public static function get_dynamic_css( $file = false ){

		if ( !$file || $file && !file_exists($file) )
			return;

		ob_start();
		include( $file );
		$css = ob_get_clean();
		$compressed_css = self::compress_css( $css );

		return $compressed_css;

	}

	public static function compress_css($css){

	    // Remove tabs, spaces, newlines, etc.
	    $css = str_replace( array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css );

	    return $css;

	}

	public function footer_enqueues() {
		wp_enqueue_script('cooked-functions-js');
	}

}
