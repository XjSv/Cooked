<?php

global $recipe, $recipe_settings, $recipe_classes, $_cooked_settings;

if ( !is_array( $recipe ) ) return false;

$recipe_array = $recipe;
$recipe_post = get_post( $recipe['id'] );
$recipe_settings = Cooked_Recipes::get( $recipe['id'], true );
$recipe_classes = !$recipe_classes ? apply_filters( 'cooked_single_recipe_classes', [ 'cooked-recipe', 'has-post-thumbnail' ], $recipe ) : apply_filters( 'cooked_single_recipe_classes', $recipe_classes, $recipe );
$recipe = $recipe_array;

if ( is_array($recipe_classes) && !empty($recipe_classes) ) {
	array_walk($recipe_classes, 'esc_attr');
} else {
	$recipe_classes = [];
}

echo '<article class="' . implode( ' ', $recipe_classes ) . ' cooked-recipe-card cooked-recipe-card-modern-centered">';

    do_action( 'cooked_recipe_grid_before_recipe', $recipe );

    do_action( 'cooked_recipe_grid_before_image', $recipe );

    echo has_post_thumbnail( $recipe['id'] ) ? '<a href="' . esc_url( get_permalink( $recipe['id'] ) ) . '" class="cooked-recipe-card-image" style="background-image:url(' . get_the_post_thumbnail_url( $recipe['id'], 'cooked-medium' ) . ');"></a>' : '<span class="cooked-recipe-image-empty"></span>';

    do_action( 'cooked_recipe_grid_after_image', $recipe );

    echo '<span class="cooked-recipe-card-content">';

        do_action( 'cooked_recipe_grid_before_name', $recipe );

        echo '<a href="' . esc_url( get_permalink( $recipe['id'] ) ) . '" class="cooked-recipe-card-title">' . wp_kses_post( $recipe_settings['title'] ) . '</a>';

        do_action( 'cooked_recipe_grid_after_name', $recipe );

        if ( !in_array('excerpt', $_cooked_settings['recipe_info_display_options']) && in_array('author', $_cooked_settings['recipe_info_display_options']) || in_array('excerpt', $_cooked_settings['recipe_info_display_options']) && !$recipe_settings['excerpt'] && in_array('author',$_cooked_settings['recipe_info_display_options']) ):
            echo '<span class="cooked-recipe-card-sep"></span>';
        endif;

        do_action( 'cooked_recipe_grid_before_author', $recipe );

        if ( in_array('author', $_cooked_settings['recipe_info_display_options']) ):
            echo '<span class="cooked-recipe-card-author">';
                $author = $recipe['author'];
                /* translators: referring to the author (ex: By John Smith) */
                echo sprintf( __( 'By %s', 'cooked' ), '<strong>' . esc_html( $author['name'] ) . '</strong>' );
            echo '</span>';
        endif;

        do_action( 'cooked_recipe_grid_after_author', $recipe );

        if ( in_array('excerpt', $_cooked_settings['recipe_info_display_options']) && $recipe_settings['excerpt'] ):
            echo '<span class="cooked-recipe-card-sep"></span>';
        endif;

        do_action( 'cooked_recipe_grid_before_excerpt', $recipe );

        if ( in_array('excerpt', $_cooked_settings['recipe_info_display_options']) && $recipe_settings['excerpt'] ):
            if ( (isset($args['hide_excerpt']) && $args['hide_excerpt'] !== 'true' ) ):
                echo '<span class="cooked-recipe-card-excerpt">' . wp_kses_post( $recipe_settings['excerpt'] ) . '</span>';
            endif;
        endif;

        // echo '<a href="' . esc_url( get_permalink( $recipe['id'] ) ) . '" class="cooked-button cooked-recipe-card-button">' . __( 'View Recipe', 'cooked' ) . '</a>';

        do_action( 'cooked_recipe_grid_after_excerpt', $recipe );

    echo '</span>';

    do_action( 'cooked_recipe_grid_after_recipe', $recipe );

echo '</article>';
