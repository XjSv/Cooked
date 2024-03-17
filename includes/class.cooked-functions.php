<?php
/**
 * Misc Functions
 *
 * @package     Cooked
 * @subpackage  Misc Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Cooked_Functions {
	
	public static function sanitize_text_field( $text ){
		$text = htmlentities( stripslashes( $text ) );
		$text = sanitize_text_field( $text );
		return $text;
	}

	public static function array_splice_assoc( &$input, $offset, $length, $replacement = array() ) {

	    $replacement = (array) $replacement;

	    if ( is_array($input) ):
		    $key_indexes = array_flip(array_keys($input));
		    if (isset($input[$offset]) && is_string($offset)) {
		        $offset = $key_indexes[$offset];
		        $offset = (int) $offset + 1;
		    }
		    if (isset($input[$length]) && is_string($length)) {
		        $length = $key_indexes[$length] - $offset;
		    }

		    $input = array_slice($input, 0, $offset, TRUE)
		            + $replacement
		            + array_slice($input, $offset + $length, NULL, TRUE);
		endif;

	}

	public static function wpml_xml( $cooked_settings_tabs_fields ){
		echo '<div style="margin:30px 0 0 17px;">';
			echo '<h3 style="width:70%;position:relative;z-index:2;padding:10px 0 12px;margin:0;font-size:1.2em;line-height:1.6;font-weight:600;color:#07a780">WPML Config XML</h3>';
			echo '<pre style="color:#888;">';
				echo esc_html('<wpml-config>
&nbsp;&nbsp;&nbsp;&nbsp;<custom-fields>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<custom-field action="copy">_recipe_settings</custom-field>
&nbsp;&nbsp;&nbsp;&nbsp;</custom-fields>
&nbsp;&nbsp;&nbsp;&nbsp;<custom-types>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<custom-type translate="1">cp_recipe</custom-type>
&nbsp;&nbsp;&nbsp;&nbsp;</custom-types>
&nbsp;&nbsp;&nbsp;&nbsp;<taxonomies>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<taxonomy translate="1">cp_recipe_category</taxonomy>
&nbsp;&nbsp;&nbsp;&nbsp;</taxonomies>
&nbsp;&nbsp;&nbsp;&nbsp;<admin-texts>
');
				echo esc_html( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<key name=\"cooked_settings\">" ) . "<br>";
				foreach ( $cooked_settings_tabs_fields as $key => $val ):
					echo esc_html( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<key name=\"" . esc_attr( $key ) . "\">" ) . "<br>";
						foreach ( $val['fields'] as $sub_key => $sub_val ):
							echo esc_html( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<key name=\"" . esc_attr( $sub_key ) . "\" />" ) . "<br>";
						endforeach;
					echo esc_html( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</key>" ) . "<br>";
				endforeach;
				echo esc_html( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</key>" );
				echo esc_html('
&nbsp;&nbsp;&nbsp;&nbsp;</admin-texts>
</wpml-config>');
			echo '</pre>';
		echo '</div>';
	}

	public static function hex2rgb( $hex ){
		list( $r,$g,$b ) = sscanf( $hex, "#%02x%02x%02x" );
		$rgb_array = array( $r, $g, $b );
		return implode( ',',$rgb_array );
	}

	public static function parse_readme_changelog( $readme_url = false, $title = false ){

		$readme = ( !$readme_url ? file_get_contents( COOKED_DIR . 'readme.txt') : file_get_contents( $readme_url ) );
		$readme = make_clickable(esc_html($readme));
		$readme = preg_replace('/`(.*?)`/', '<code>\\1</code>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\NEW:\*\*/', '<strong class="new">' . esc_html__( 'New', 'cooked' ) . '</strong>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\TWEAK:\*\*/', '<strong class="tweak">' . esc_html__( 'Tweak', 'cooked' ) . '</strong>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\FIX:\*\*/', '<em class="fix">' . esc_html__( 'Fixed', 'cooked' ) . '</em>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\NEW\*\*/', '<strong class="new">' . esc_html__( 'New', 'cooked' ) . '</strong>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\TWEAK\*\*/', '<strong class="tweak">' . esc_html__( 'Tweak', 'cooked' ) . '</strong>', $readme);
		//$readme = preg_replace( '/[\040]\*\*\FIX\*\*/', '<em class="fix">' . esc_html__( 'Fixed', 'cooked' ) . '</em>', $readme);
		$readme = preg_replace( '/\*\*(.*?)\*\*/', '<strong>\\1</strong>', $readme);
		$readme = preg_replace( '/\*(.*?)\*/', '<em>\\1</em>', $readme);
		$readme = explode( '== Changelog ==', $readme );
		$readme = $readme[1];
		$whats_new_title = '<h4>' . ( $title ? esc_html( $title ) : apply_filters( 'cooked_whats_new_title', sprintf( esc_html__( "What's new in %s?", "cooked" ), 'Cooked ' . COOKED_VERSION ) ) ) . '</h4>';
		$readme = preg_replace('/= (.*?) =/', $whats_new_title, $readme);
		$readme = preg_replace("/\*+(.*)?/i","<ul class='cooked-whatsnew-list'><li>$1</li></ul>",$readme);
		$readme = preg_replace("/(\<\/ul\>\n(.*)\<ul class=\'cooked-whatsnew-list\'\>*)+/","",$readme);
		$readme = explode( $whats_new_title, $readme );
		$readme = $whats_new_title . $readme[1];
		return $readme;

	}

	public static function print_options() {

		$default_print_options = apply_filters( 'cooked_default_print_options', array(
			'print_options_title' => 'checked',
			'print_options_info' => '',
			'print_options_excerpt' => '',
			'print_options_images' => '',
			'print_options_ingredients' => 'checked',
			'print_options_directions' => 'checked',
			'print_options_nutrition' => '',
		));

		echo '<div id="cooked-print-options" class="cooked-clearfix">';

			echo '<button class="cooked-button" onclick="window.print();">' . esc_html__( 'Print','cooked') . '</button>';
			echo '<h3>' . esc_html__( 'Print Options:','cooked') . '</h3>';

			echo '<input id="print_options_title" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_title'] ) ? $default_print_options['print_options_title'] : '' ) . ' /> <label for="print_options_title">' . esc_html__('Title','cooked') . '</label>';
			echo '<input id="print_options_info" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_info'] ) ? $default_print_options['print_options_info'] : '' ) . ' /> <label for="print_options_info">' . esc_html__('Information','cooked') . '</label>';
			echo '<input id="print_options_excerpt" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_excerpt'] ) ? $default_print_options['print_options_excerpt'] : '' ) . ' /> <label for="print_options_excerpt">' . esc_html__('Excerpt','cooked') . '</label>';
			echo '<input id="print_options_images" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_images'] ) ? $default_print_options['print_options_images'] : '' ) . ' /> <label for="print_options_images">' . esc_html__('Images','cooked') . '</label>';
			echo '<input id="print_options_ingredients" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_ingredients'] ) ? $default_print_options['print_options_ingredients'] : '' ) . ' /> <label for="print_options_ingredients">' . esc_html__('Ingredients','cooked') . '</label>';
			echo '<input id="print_options_directions" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_directions'] ) ? $default_print_options['print_options_directions'] : '' ) . ' /> <label for="print_options_directions">' . esc_html__('Directions','cooked') . '</label>';
			echo '<input id="print_options_nutrition" type="checkbox" name="print_options" value="1" ' . ( isset( $default_print_options['print_options_nutrition'] ) ? $default_print_options['print_options_nutrition'] : '' ) . ' /> <label for="print_options_nutrition">' . esc_html__('Nutrition','cooked') . '</label>';

		echo '</div>';

	}

	public static function print_options_js(){

		?><script type="text/javascript">

			var print_options = document.getElementsByTagName('input');
	    	for (var i = 0, len = print_options.length; i < len; i++) {
	    		if ( print_options[i].getAttribute("name") == "print_options"){
	    			update_print_options( print_options[i] );
	    		}
	    	}

			document.addEventListener("click", function (e) {
				update_print_options( e.target );
			});

			function update_print_options( printOpt ){

	    		if (printOpt.id == "print_options_title" && typeof document.getElementById('printTitle') != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementById('printTitle').style.display = 'block';
			        } else {
			        	document.getElementById('printTitle').style.display = 'none';
			        }
			    }

			    if (printOpt.id == "print_options_nutrition" && typeof document.getElementsByClassName('cooked-nutrition-label')[0] != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-nutrition-label')[0].style.display = 'block';
			        } else {
			        	document.getElementsByClassName('cooked-nutrition-label')[0].style.display = 'none';
			        }
			    }

			    if (printOpt.id == "print_options_info" && typeof document.getElementsByClassName('cooked-recipe-info')[0] != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-recipe-info')[0].style.display = 'block';
			        } else {
			        	document.getElementsByClassName('cooked-recipe-info')[0].style.display = 'none';
			        }
			    }

			    if (printOpt.id == "print_options_excerpt" && typeof document.getElementsByClassName('cooked-recipe-excerpt')[0] != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-recipe-excerpt')[0].style.display = 'block';
			        } else {
			        	document.getElementsByClassName('cooked-recipe-excerpt')[0].style.display = 'none';
			        }
			    }

			    if (printOpt.id == "print_options_images" && typeof document.getElementsByClassName('cooked-post-featured-image')[0] != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-post-featured-image')[0].style.display = 'block';
			        	var print_images = document.getElementsByTagName('img');
			        	for (var i = 0, len = print_images.length; i < len; i++) {
			        		print_images[i].style.display = 'block';
			        	}
			        } else {
			        	document.getElementsByClassName('cooked-post-featured-image')[0].style.display = 'none';
			        	var print_images = document.getElementsByTagName('img');
			        	for (var i = 0, len = print_images.length; i < len; i++) {
			        		print_images[i].style.display = 'none';
			        	}
			        }
			    }

			    if (printOpt.id == "print_options_ingredients" && typeof document.getElementsByClassName('cooked-recipe-ingredients')[0] != 'undefined') {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-recipe-ingredients')[0].style.display = 'block';
			        } else {
			        	document.getElementsByClassName('cooked-recipe-ingredients')[0].style.display = 'none';
			        }
			    }

			    if (printOpt.id == "print_options_directions" && typeof document.getElementsByClassName('cooked-recipe-directions')[0] != 'undefined' ) {
			        if ( printOpt.checked ){
			        	document.getElementsByClassName('cooked-recipe-directions')[0].style.display = 'block';
			        } else {
			        	document.getElementsByClassName('cooked-recipe-directions')[0].style.display = 'none';
			        }
			    }

	    	}

		</script><?php

	}

}
