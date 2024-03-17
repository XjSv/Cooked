<?php
/**
 * Cooked SEO Functions
 *
 * @package     Cooked
 * @subpackage  SEO Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Cooked_SEO {

    public static function json_ld( $recipe = false ){

        global $_cooked_settings;
        if ( !in_array( 'disable_schema_output', $_cooked_settings['advanced'] ) ):

            $schema_values_json = json_encode( self::schema_values( $recipe ) );

            $schema_html = '<script type="application/ld+json">';
                $schema_html .= $schema_values_json;
            $schema_html .= '</script>';

            return apply_filters( 'cooked_schema_html', $schema_html, $recipe );

        else:

            return '';

        endif;

    }

    public static function schema_values( $recipe = false ){

        global $_cooked_settings;

        if ( !$recipe ):
            global $post;
            $recipe = Cooked_Recipes::get_settings( $post->ID );
            $rpost = get_post( $post->ID );
        else:
            $rpost = get_post( $recipe['id'] );
        endif;

        $recipe_thumbnail = ( has_post_thumbnail($rpost) ? get_the_post_thumbnail_url( $rpost, 'cooked-medium' ) : '' );
        if ( !$recipe_author = Cooked_Users::format_author_name( get_the_author_meta( 'display_name', $rpost->post_author ) ) ):
            $recipe_author = '';
        endif;

        if ( isset($recipe['ingredients']) && !empty($recipe['ingredients']) ):
            foreach ( $recipe['ingredients'] as $ing ):
                if ( isset( $ing['section_heading_name'] ) ): continue; endif;
                $ingredient = Cooked_Recipes::single_ingredient( $ing, false, true );
                $ingredient_cleaned = strip_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $ingredient) );
                $ingredients[] = $ingredient_cleaned;
            endforeach;
        endif;

        if ( isset($recipe['directions']) && !empty($recipe['directions']) ):
            foreach ( $recipe['directions'] as $dir ):
                $direction = Cooked_Recipes::single_direction( $dir, false, true );
                $direction_cleaned = strip_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $direction) );
                $directions[] = $direction_cleaned;
            endforeach;
        endif;

        $category_name = '';

        if (in_array('cp_recipe_category',$_cooked_settings['recipe_taxonomies'])):
            $categories = get_the_terms( $rpost->ID, 'cp_recipe_category' );
            if (!empty($categories)):
                $category = $categories[0];
                $category_name = $category->name;
            endif;
        endif;

        $cook_time = ( isset($recipe['cook_time']) && $recipe['cook_time'] ? esc_html( $recipe['cook_time'] ) : 0 );
        $prep_time = ( isset($recipe['prep_time']) && $recipe['prep_time'] ? esc_html( $recipe['prep_time'] ) : 0 );
        $total_time = $cook_time + $prep_time;

        $schema_array = false;

        $schema_array = apply_filters( 'cooked_schema_array', array(
            '@context' => 'http://schema.org',
            '@type' => 'Recipe',
            'author' => $recipe_author,
            'datePublished' => get_the_date( 'Y-m-d', $recipe['id'] ),
            'name' => ( isset($recipe['title']) ? $recipe['title'] : '' ),
            'image' => $recipe_thumbnail,
            'description' => ( isset($recipe['seo_description']) && $recipe['seo_description'] ? $recipe['seo_description'] : ( isset($recipe['excerpt']) && $recipe['excerpt'] ? $recipe['excerpt'] : ( isset($recipe['title']) ? $recipe['title'] : '' ) ) ),
            'recipeIngredient' => $ingredients,
            'recipeCategory' => $category_name,
            'recipeYield' => ( isset($recipe['nutrition']['servings']) && $recipe['nutrition']['servings'] ? $recipe['nutrition']['servings'] : '' ),
            'cookTime' => Cooked_Measurements::time_format($cook_time,'iso'),
            'prepTime' => Cooked_Measurements::time_format($prep_time,'iso'),
            'totalTime' => Cooked_Measurements::time_format($total_time,'iso'),
            'nutrition' => array(
                '@type' => 'NutritionInformation',
                'calories' => ( isset($recipe['nutrition']['calories']) && $recipe['nutrition']['calories'] ? $recipe['nutrition']['calories'] : 0 ),
                'carbohydrateContent' => ( isset($recipe['nutrition']['carbs']) && $recipe['nutrition']['carbs'] ? $recipe['nutrition']['carbs'] : '' ),
                'cholesterolContent' => ( isset($recipe['nutrition']['cholesterol']) && $recipe['nutrition']['cholesterol'] ? $recipe['nutrition']['cholesterol'] : '' ),
                'fatContent' => ( isset($recipe['nutrition']['fat']) && $recipe['nutrition']['fat'] ? $recipe['nutrition']['fat'] : '' ),
                'fiberContent' => ( isset($recipe['nutrition']['fiber']) && $recipe['nutrition']['fiber'] ? $recipe['nutrition']['fiber'] : '' ),
                'proteinContent' => ( isset($recipe['nutrition']['protein']) && $recipe['nutrition']['protein'] ? $recipe['nutrition']['protein'] : '' ),
                'saturatedFatContent' => ( isset($recipe['nutrition']['sat_fat']) && $recipe['nutrition']['sat_fat'] ? $recipe['nutrition']['sat_fat'] : '' ),
                'servingSize' => ( isset($recipe['nutrition']['serving_size']) && $recipe['nutrition']['serving_size'] ? $recipe['nutrition']['serving_size'] : '' ),
                'sodiumContent' => ( isset($recipe['nutrition']['sodium']) && $recipe['nutrition']['sodium'] ? $recipe['nutrition']['sodium'] : '' ),
                'sugarContent' => ( isset($recipe['nutrition']['sugars']) && $recipe['nutrition']['sugars'] ? $recipe['nutrition']['sugars'] : '' ),
                'transFatContent' => ( isset($recipe['nutrition']['trans_fat']) && $recipe['nutrition']['trans_fat'] ? $recipe['nutrition']['trans_fat'] : '' )
            ),
            'recipeInstructions' => $directions
        ), $rpost, $recipe );

        return $schema_array;

    }

}
