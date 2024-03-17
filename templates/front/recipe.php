<?php

global $post,$recipe,$recipe_settings,$cooked_timer_identifier;

if ( empty($recipe_settings) ):
	$recipe_id = $post->ID;
	$recipe = get_post( $recipe_id );
	$recipe_settings = Cooked_Recipes::get_settings( $recipe_id );
else:
	$recipe_id = $recipe_settings['id'];
	$recipe = get_post( $recipe_id );
	$recipe_settings = Cooked_Recipes::get_settings( $recipe_id );
endif;

$recipe_content = ( isset($recipe_settings['content']) ? $recipe_settings['content'] : Cooked_Recipes::default_content() );
$recipe_content = apply_filters( 'cooked_pre_recipe_content', $recipe_content, $recipe_id );

if ( is_feed() && !is_admin() || is_singular() && !is_admin() ):

	$schema_array = Cooked_SEO::schema_values( $recipe_settings );

	if ( !empty($schema_array) && is_array($schema_array) ):
		$recipe_seo_content = Cooked_SEO::json_ld( $recipe_settings );
	endif;

	$cooked_timer_identifier = 0;

	global $wp_embed;
	$recipe_content = $wp_embed->autoembed( $recipe_content );
	$recipe_content .=  '<div id="cooked-fsm-' . intval( $recipe_id ) . '" class="cooked-fsm" data-recipe-id="' . intval( $recipe_id ) . '">';
		$recipe_content .=  do_shortcode( Cooked_Recipes::fsm_content() );
		$recipe_content .=  '<div class="cooked-fsm-top">' . wp_kses_post( $recipe_settings['title'] ) . '<a href="#" class="cooked-close-fsm"><i class="cooked-icon cooked-icon-close"></i></a></div>';
		$recipe_content .=  '<div class="cooked-fsm-mobile-nav">';
			$recipe_content .=  '<a href="#ingredients" data-nav-id="ingredients" class="cooked-fsm-nav-ingredients cooked-active">' . esc_html__( 'Ingredients', 'cooked' ) . '</a>';
			$recipe_content .=  '<a href="#directions" data-nav-id="directions" class="cooked-fsm-nav-directions">' . esc_html__( 'Directions', 'cooked' ) . '</a>';
		$recipe_content .=  '</div>';
	$recipe_content .=  '</div>';

	$recipe_content .= ( isset($recipe_seo_content) ? $recipe_seo_content : '' );

else:

 	$recipe_content = strip_shortcodes( $recipe_content );

endif;

echo apply_filters( 'cooked_recipe_content', $recipe_content, $recipe_id );