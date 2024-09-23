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

            $schema_values_json = wp_json_encode( self::schema_values( $recipe ) );

            $schema_html = '<script type="application/ld+json">';
                $schema_html .= $schema_values_json;
            $schema_html .= '</script>';

            return apply_filters( 'cooked_schema_html', $schema_html, $recipe );

        else:

            return '';

        endif;

    }

    public static function schema_values( $recipe = false ) {
        global $_cooked_settings;

        if ( !$recipe ):
            global $post;
            $recipe = Cooked_Recipes::get_settings( $post->ID );
            $rpost = get_post( $post->ID );
        else:
            $rpost = get_post( $recipe['id'] );
        endif;

        $_nutrition_facts = Cooked_Measurements::nutrition_facts();

        $recipe_thumbnail = ( has_post_thumbnail($rpost) ? get_the_post_thumbnail_url( $rpost, 'cooked-medium' ) : '' );
        if ( !$recipe_author = Cooked_Users::format_author_name( get_the_author_meta( 'display_name', $rpost->post_author ) ) ):
            $recipe_author = '';
        endif;

        $ingredients = [];
        if ( isset($recipe['ingredients']) && !empty($recipe['ingredients']) ):
            foreach ( $recipe['ingredients'] as $ing ):
                if ( isset( $ing['section_heading_name'] ) ): continue; endif;
                $ingredient = Cooked_Recipes::single_ingredient( $ing, false, true );
                $ingredient_cleaned = wp_strip_all_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $ingredient) );
                $ingredients[] = $ingredient_cleaned;
            endforeach;
        endif;

        $directions = [];
        if ( isset($recipe['directions']) && !empty($recipe['directions']) ):
            $number = 1;
            $dir_name = '';

            foreach ( $recipe['directions'] as $dir ):
                $dir_name = isset( $dir['section_heading_name'] ) ? $dir['section_heading_name'] : $dir_name;

                if ( isset( $dir['section_heading_name'] ) ): continue; endif;

                $direction = Cooked_Recipes::single_direction( $dir, false, true );
                $direction_cleaned = wp_strip_all_tags( preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $direction) );
                $image_id = isset($dir['image']) ? $dir['image'] : false;

                $image = '';
                if ( $image_id ):
                    $image = wp_get_attachment_image_src( $image_id, 'full' );
                    $image = $image[0];
                endif;

                if (empty($dir_name)) {
                    $dir_name = esc_html__('Step ' . $number);
                }

                $directions[] = [
                    '@type' => 'HowToStep',
                    'name' => $dir_name,
                    'text' => $direction_cleaned,
                    'url' => get_permalink($rpost) . '#cooked-single-direction-step-' . $number,
                    'image' => $image,
                ];

                $number++;
            endforeach;
        endif;

        $category_name = '';
        if (in_array('cp_recipe_category', $_cooked_settings['recipe_taxonomies'])):
            $categories = get_the_terms( $rpost->ID, 'cp_recipe_category' );
            if (!empty($categories)):
                $category = $categories[0];
                $category_name = $category->name;
            endif;
        endif;

        $cook_time = ( isset($recipe['cook_time']) && $recipe['cook_time'] ? esc_html( $recipe['cook_time'] ) : 0 );
        $prep_time = ( isset($recipe['prep_time']) && $recipe['prep_time'] ? esc_html( $recipe['prep_time'] ) : 0 );
        $total_time = $cook_time + $prep_time;

        $unsaturatedFatAmount = (isset($recipe['nutrition']['monounsaturated_fat']) && $recipe['nutrition']['monounsaturated_fat'] ? $recipe['nutrition']['monounsaturated_fat'] : 0) + (isset($recipe['nutrition']['polyunsaturated_fat']) && $recipe['nutrition']['polyunsaturated_fat'] ? $recipe['nutrition']['polyunsaturated_fat'] : 0);

        if ($unsaturatedFatAmount):
            $unsaturatedFatContent = $unsaturatedFatAmount . ' ' . $_nutrition_facts['main']['fat']['subs']['monounsaturated_fat']['measurement'];
        else:
            $unsaturatedFatContent = '';
        endif;

        $schema_array = false;
        $schema_data = [
            '@context' => 'http://schema.org',
            '@type' => 'Recipe',
            'author' => [
                '@type' => 'Person',
                'name' => $recipe_author
            ],
            'datePublished' => get_the_date('Y-m-d', $recipe['id']),
            'name' => (isset($recipe['title']) ? $recipe['title'] : ''),
            'image' => $recipe_thumbnail,
            'description' => (isset($recipe['seo_description']) && $recipe['seo_description'] ? $recipe['seo_description'] : (isset($recipe['excerpt']) && $recipe['excerpt'] ? $recipe['excerpt'] : (isset($recipe['title']) ? $recipe['title'] : ''))),
            'recipeIngredient' => $ingredients,
            'recipeCategory' => $category_name,
            'recipeYield' => (isset($recipe['nutrition']['servings']) && $recipe['nutrition']['servings'] ? $recipe['nutrition']['servings'] . ' ' . strtolower($_nutrition_facts['top']['servings']['name'])  : ''),
            'cookTime' => Cooked_Measurements::time_format($cook_time, 'iso'),
            'prepTime' => Cooked_Measurements::time_format($prep_time, 'iso'),
            'totalTime' => Cooked_Measurements::time_format($total_time, 'iso'),
            'nutrition' => [
                '@type' => 'NutritionInformation',
                'calories' => (isset($recipe['nutrition']['calories']) && $recipe['nutrition']['calories'] ? $recipe['nutrition']['calories'] . ' ' . strtolower($_nutrition_facts['mid']['calories']['name']) : 0),
                'carbohydrateContent' => (isset($recipe['nutrition']['carbs']) && $recipe['nutrition']['carbs'] ? $recipe['nutrition']['carbs'] . ' ' . $_nutrition_facts['main']['carbs']['measurement'] : ''),
                'cholesterolContent' => (isset($recipe['nutrition']['cholesterol']) && $recipe['nutrition']['cholesterol'] ? $recipe['nutrition']['cholesterol'] . ' ' . $_nutrition_facts['main']['cholesterol']['measurement'] : ''),
                'fatContent' => (isset($recipe['nutrition']['fat']) && $recipe['nutrition']['fat'] ? $recipe['nutrition']['fat'] . ' ' . $_nutrition_facts['main']['fat']['measurement'] : ''),
                'fiberContent' => (isset($recipe['nutrition']['fiber']) && $recipe['nutrition']['fiber'] ? $recipe['nutrition']['fiber'] . ' ' . $_nutrition_facts['main']['carbs']['subs']['fiber']['measurement'] : ''),
                'proteinContent' => (isset($recipe['nutrition']['protein']) && $recipe['nutrition']['protein'] ? $recipe['nutrition']['protein'] . ' ' . $_nutrition_facts['main']['protein']['measurement'] : ''),
                'saturatedFatContent' => (isset($recipe['nutrition']['sat_fat']) && $recipe['nutrition']['sat_fat'] ? $recipe['nutrition']['sat_fat'] . ' ' . $_nutrition_facts['main']['fat']['subs']['sat_fat']['measurement'] : ''),
                'servingSize' => (isset($recipe['nutrition']['serving_size']) && $recipe['nutrition']['serving_size'] ? $recipe['nutrition']['serving_size'] . ' ' . strtolower($_nutrition_facts['top']['servings']['name']) : ''),
                'sodiumContent' => (isset($recipe['nutrition']['sodium']) && $recipe['nutrition']['sodium'] ? $recipe['nutrition']['sodium'] . ' ' . $_nutrition_facts['main']['sodium']['measurement'] : ''),
                'sugarContent' => (isset($recipe['nutrition']['sugars']) && $recipe['nutrition']['sugars'] ? $recipe['nutrition']['sugars'] . ' ' . $_nutrition_facts['main']['carbs']['subs']['sugars']['measurement'] : ''),
                'transFatContent' => (isset($recipe['nutrition']['trans_fat']) && $recipe['nutrition']['trans_fat'] ? $recipe['nutrition']['trans_fat'] . ' ' . $_nutrition_facts['main']['fat']['subs']['trans_fat']['measurement'] : ''),
                'unsaturatedFatContent' => $unsaturatedFatContent,
            ],
            'recipeInstructions' => $directions,
        ];

        $schema_array = apply_filters('cooked_schema_array', $schema_data, $rpost, $recipe);

        return $schema_array;
    }

}
