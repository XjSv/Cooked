<?php

global $recipes,$recipe,$_cooked_settings,$recipe_args,$current_recipe_page,$atts,$list_id_counter;

$atts['layout'] = ( !isset($atts['layout']) || isset($atts['layout']) && !$atts['layout'] ? ( isset( $_cooked_settings['recipe_list_style'] ) ? $_cooked_settings['recipe_list_style'] : [ 'grid' => 'Cooked_Recipes' ] ) : $atts['layout'] );

$author_template_override = false;
$is_author_page = ( !isset($atts['skip_heading']) && !empty($recipes) && isset($recipe_args['author_name']) && $recipe_args['author_name'] || !isset($atts['skip_heading']) && !empty($recipes) && isset($recipe_args['author']) && $recipe_args['author'] ? true : false );

if ( $is_author_page ):

	$first_recipe = current( $recipes );
	$recipe_id = $first_recipe['id'];
	$recipe = get_post($recipe_id);
	$author = Cooked_Users::get( $recipe->post_author );

	// Developers: You can override the author's template page with your own if needed.
	$author_template_override = apply_filters( 'cooked_author_template_override', false, $author );

endif;

// START 'cooked_author_template_override' Filter:
if ( $author_template_override ):

	echo wp_kses_post( $author_template_override );

else:

	$child_taxonomies = false;

	if( $is_author_page ):

        $hide_avatars = ( isset( $_cooked_settings['hide_author_avatars'][0] ) && $_cooked_settings['hide_author_avatars'][0] == 'hidden' ? true : false );
        echo '<div class="cooked-author-list-heading' . ( $hide_avatars ? ' cooked-no-avatar' : '' ) . '">';
            echo ( isset($author['profile_photo']) && $author['profile_photo'] ? ( !$hide_avatars ? '<span class="cooked-author-avatar">' . esc_html( $author['profile_photo'] ) . '</span>' : '' ) : '' );
            echo '<strong class="cooked-meta-title">' . sprintf( esc_html__('Recipes by %s','cooked'), $author['name'] ) . '</strong>';
            echo ( isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] ? '<br><a href="' . get_permalink($_cooked_settings['browse_page']) . '">' . esc_html__( 'View all recipes','cooked' ) . '</a>' : '' );
        echo '</div>';

	elseif ( $atts['search'] === 'true' ):

		$size = ( $atts['compact'] ? ' compact="true"' : false );
        $hide_browse = ( $atts['hide_browse'] ? ' hide_browse="true"' : false );
        $hide_sorting = ( $atts['hide_sorting'] ? ' hide_sorting="true"' : false );
		$inline_browse = ( $atts['inline_browse'] ? ' inline_browse="true"' : false );

        echo do_shortcode( '[cooked-search' . esc_attr( $size . $hide_browse . $hide_sorting . $inline_browse ) . ']' );

	endif;

    if ( isset( $recipe_args['tax_query'][0]['terms'][0] ) ):
        switch( $recipe_args['tax_query'][0]['taxonomy'] ):
            case 'cp_recipe_category':
                $shortcode = 'categories';
            break;
            case 'cp_recipe_cooking_method':
                $shortcode = 'cooking-methods';
            break;
            case 'cp_recipe_cuisine':
                $shortcode = 'cuisines';
            break;
            case 'cp_recipe_tags':
                $shortcode = 'tags';
            break;
            case 'cp_recipe_diet':
                $shortcode = 'diets';
            break;
        endswitch;
        $tax_slug = $recipe_args['tax_query'][0]['terms'][0];
        $child_taxonomies = do_shortcode( '[cooked-' . esc_attr( $shortcode ) . ' child_of="' . esc_attr( $tax_slug ) . '"]' );
    endif;

	if ( $child_taxonomies ):
		echo wp_kses_post( $child_taxonomies );
	endif;

    if ( !empty($recipes) && !isset( $recipes['raw'] ) && count($recipes) >= 1 || !empty($recipes) && isset( $recipes['raw'] ) && count($recipes) > 1 ):

		$list_id_counter++;
		$recipe_list_style = apply_filters( 'cooked_recipe_list_style', array( 'grid' => 'Cooked_Recipes' ), $atts['layout'] );
		$list_style = key( $recipe_list_style );
		$ls_method = 'list_style_' . esc_attr( $list_style );
		$ls_class = current( $recipe_list_style );

		echo '<section id="cooked-recipe-list-' . intval( $list_id_counter ) . '" class="cooked-clearfix cooked-recipe-' . esc_attr( $list_style ) . ' cooked-recipe-loader' . ( isset($atts['columns']) && $atts['columns'] ? ' cooked-columns-' . esc_attr( $atts['columns'] ) : '' ) . '">';

		foreach( $recipes as $key => $recipe ):

			if ( $key === 'raw' ):
				continue;
			endif;

			$ls_class::$ls_method();

		endforeach;

		echo '</section>';

		if ( $atts['pagination'] === 'true' ):
			echo Cooked_Recipes::pagination( $recipes['raw'], $recipe_args );
		endif;

		wp_enqueue_script( 'cooked-appear-js' );

	else:
		$post_type_obj = get_post_type_object('cp_recipe');
		echo '<p class="cooked-none-found">' . wp_kses_post( $post_type_obj->labels->not_found ) . '</p>';
	endif;

endif;
// END 'cooked_author_template_override' Filter.

