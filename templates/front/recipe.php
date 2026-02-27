<?php

global $post, $recipe, $recipe_settings, $cooked_timer_identifier;

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

global $cooked_recipe_layout_content;
$cooked_recipe_layout_content = $recipe_content;

if ( is_feed() && !is_admin() || is_singular() && !is_admin() ):
	$schema_array = Cooked_SEO::schema_values( $recipe_settings );

	if ( !empty($schema_array) && is_array($schema_array) ):
		$recipe_seo_content = Cooked_SEO::json_ld( $recipe_settings );
	endif;

	$cooked_timer_identifier = 0;

	global $wp_embed;
	$recipe_content = $wp_embed->autoembed( $recipe_content );
	$recipe_content .= Cooked_Recipes::get_fsm_markup( $recipe_id, $recipe_settings );

	$recipe_content .= isset($recipe_seo_content) ? $recipe_seo_content : '';
else:
 	$recipe_content = strip_shortcodes( $recipe_content );
endif;

echo apply_filters( 'cooked_recipe_content', $recipe_content, $recipe_id );