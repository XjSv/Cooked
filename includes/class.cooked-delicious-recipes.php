<?php
/**
 * Delicious Recipe-Specific Functions
 *
 * @package     Cooked_Delicious_Recipes
 * @subpackage  Cooked_Delicious_Recipes Specific Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Delicious_Recipes Class
 *
 * This class handles the recipe-specific functions.
 *
 * @since 1.0.0
 */
class Cooked_Delicious_Recipes {

    public $import_type = 'delicious_recipes';

    public function __construct() {

    }

    public static function get_recipes() {
        $delicious_recipes = [];

        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'delicious_recipes_metadata',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $_recipes = new WP_Query( $args );

        if (!empty($_recipes->posts)) {
            foreach ($_recipes->posts as $rid) {
                $delicious_recipes[] = $rid;
            }
        }

        return $delicious_recipes;
    }

    public static function import_recipe($id) {
        global $_cooked_settings;

        $post = get_post($id);
        $post_meta = get_post_custom($id);
        $delicious_recipe = maybe_unserialize($post_meta['delicious_recipes_metadata'][0]);

        // Create new cp_recipe post.
        $new_cp_recipe                    = [];
        $new_cp_recipe['post_type']       = 'cp_recipe';
        $new_cp_recipe['post_status']     = 'draft';
        $new_cp_recipe['post_title']      = isset( $post->post_title ) ? sanitize_text_field( $post->post_title ) : '';
        $new_cp_recipe['post_content']    = isset( $post->post_content ) ? sanitize_text_field( $post->post_content ) : '';
        $new_cp_recipe['post_author']     = isset( $post->post_author ) ? absint( $post->post_author ) : '';
        $new_cp_recipe['post_excerpt']    = isset( $post->post_excerpt ) ? sanitize_text_field( $post->post_excerpt ) : '';
        $new_cp_recipe['ping_status']     = isset( $post->ping_status ) ? sanitize_text_field( $post->ping_status ) : '';
        $new_cp_recipe['commnets_status'] = isset( $post->comment_status ) ? sanitize_text_field( $post->comment_status ) : '';
        $new_cp_recipe['comment_count']   = isset( $post->comment_count ) ? absint( $post->comment_count ) : '';

        $new_recipe_id = wp_insert_post($new_cp_recipe);
        if (is_wp_error($new_recipe_id)) {
            return [
                'status' => false,
                'message' => __('Error importing recipe.', 'cooked'),
            ];
        }

        if (isset($_cooked_settings['default_content'])) {
            $default_content = stripslashes($_cooked_settings['default_content']);
        } else {
            $default_content = Cooked_Recipes::default_content();
        }

        // Insert new post meta data.
        $new_cp_recipe_meta = [];
        $new_cp_recipe_meta['cooked_version'] = COOKED_VERSION;
        $new_cp_recipe_meta['content'] = $default_content;
        $new_cp_recipe_meta['excerpt'] = $post->post_title;
        $new_cp_recipe_meta['seo_description'] = isset( $delicious_recipe['recipeDescription'] ) ? sanitize_text_field( $delicious_recipe['recipeDescription'] ) : '';
        $new_cp_recipe_meta['notes'] = isset( $delicious_recipe['recipeNotes'] ) ? $delicious_recipe['recipeNotes'] : '';

        // Convert beginner, intermediate, advanced to 1, 2, 3.
        $difficulty_levels = [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
        ];
        $new_cp_recipe_meta['difficulty_level'] = isset( $delicious_recipe['difficultyLevel'] ) ? $difficulty_levels[$delicious_recipe['difficultyLevel'] ] : '';

        $new_cp_recipe_meta['prep_time'] = isset( $delicious_recipe['prepTime'] ) ? sanitize_text_field( $delicious_recipe['prepTime'] ) : '';
        $new_cp_recipe_meta['cook_time'] = isset( $delicious_recipe['cookTime'] ) ? sanitize_text_field( $delicious_recipe['cookTime'] ) : '';
        $new_cp_recipe_meta['total_time'] = isset( $delicious_recipe['prepTime'] ) && isset( $delicious_recipe['cookTime'] ) ? sanitize_text_field( $delicious_recipe['prepTime'] ) + sanitize_text_field( $delicious_recipe['cookTime'] ) : '';

        // Recipe Ingredients.
        $recipeIngredients = isset( $delicious_recipe['recipeIngredients'] ) ? $delicious_recipe['recipeIngredients'] : [];
        $new_cp_recipe_meta['ingredients'] = [];

        foreach ($recipeIngredients as $ingredient) {
            if (isset($ingredient['sectionTitle']) && !empty($ingredient['sectionTitle']) && empty($ingredient['ingredients'])) {
                $new_cp_recipe_meta['ingredients'][] = [
                    'section_heading_name' => $ingredient['sectionTitle'],
                ];
            } else {
                foreach ($ingredient['ingredients'] as $ingredient) {
                    $new_cp_recipe_meta['ingredients'][] = [
                        'amount' => $ingredient['quantity'],
                        'measurement' => $ingredient['unit'],
                        'name' => $ingredient['ingredient'],
                        'url' => '',
                        'description' => $ingredient['notes']
                    ];
                }
            }
        }

        // Recipe Instructions.
        $recipeInstructions = isset( $delicious_recipe['recipeInstructions'] ) ? $delicious_recipe['recipeInstructions'] : [];
        $new_cp_recipe_meta['directions'] = [];

        foreach ($recipeInstructions as $instruction) {
            if (isset($instruction['sectionTitle']) && !empty($instruction['sectionTitle']) && empty($instruction['instruction'])) {
                $new_cp_recipe_meta['directions'][] = [
                    'section_heading_name' => $instruction['sectionTitle'],
                ];
            } else {
                foreach ($instruction['instruction'] as $instruction) {
                    $new_cp_recipe_meta['directions'][] = [
                        'image' => $instruction['image'],
                        'content' => $instruction['instruction'],
                    ];
                }
            }
        }

        // Gallery.
        $imageGalleryImages = isset( $delicious_recipe['imageGalleryImages'] ) ? $delicious_recipe['imageGalleryImages'] : [];
        $videoGalleryVids = isset( $delicious_recipe['videoGalleryVids'] ) ? $delicious_recipe['videoGalleryVids'] : [];

        $video_url = '';
        if ($videoGalleryVids[0]['vidType'] === 'youtube') {
            $video_url = 'https://www.youtube.com/watch?v=' . $videoGalleryVids[0]['vidID'];
        }

        if ($videoGalleryVids[0]['vidType'] === 'vimeo') {
            $video_url = 'https://vimeo.com/' . $videoGalleryVids[0]['vidID'];
        }

        $new_cp_recipe_meta['gallery'] = [
            'type' => 'cooked',
            'video_url' => $video_url,
            'items' => [],
        ];

        if (!empty($imageGalleryImages)) {
            foreach ($imageGalleryImages as $image) {
                $new_cp_recipe_meta['gallery']['items'][] = $image['imageID'];
            }
        }

        // Nutrition Data.
        $new_cp_recipe_meta['nutrition']['serving_size']     = isset( $delicious_recipe['servingSize'] ) ? absint( $delicious_recipe['servingSize'] ) : '';
        $new_cp_recipe_meta['nutrition']['servings']         = isset( $delicious_recipe['servings'] ) ? absint( $delicious_recipe['servings'] ) : '';
        $new_cp_recipe_meta['nutrition']['calories']         = isset( $delicious_recipe['calories'] ) ? sanitize_text_field( $delicious_recipe['calories'] ) : '';
        $new_cp_recipe_meta['nutrition']['calories_fat']     = isset( $delicious_recipe['caloriesFromFat'] ) ? sanitize_text_field( $delicious_recipe['caloriesFromFat'] ) : '';
        $new_cp_recipe_meta['nutrition']['fat']              = isset( $delicious_recipe['totalFat'] ) ? sanitize_text_field( $delicious_recipe['totalFat'] ) : '';
        $new_cp_recipe_meta['nutrition']['sat_fat']          = isset( $delicious_recipe['saturatedFat'] ) ? sanitize_text_field( $delicious_recipe['saturatedFat'] ) : '';
        $new_cp_recipe_meta['nutrition']['trans_fat']        = isset( $delicious_recipe['transFat'] ) ? sanitize_text_field( $delicious_recipe['transFat'] ) : '';
        $new_cp_recipe_meta['nutrition']['cholesterol']      = isset( $delicious_recipe['cholesterol'] ) ? sanitize_text_field( $delicious_recipe['cholesterol'] ) : '';
        $new_cp_recipe_meta['nutrition']['sodium']           = isset( $delicious_recipe['sodium'] ) ? sanitize_text_field( $delicious_recipe['sodium'] ) : '';
        $new_cp_recipe_meta['nutrition']['potassium']        = isset( $delicious_recipe['potassium'] ) ? sanitize_text_field( $delicious_recipe['potassium'] ) : '';
        $new_cp_recipe_meta['nutrition']['carbs']            = isset( $delicious_recipe['totalCarbohydrate'] ) ? sanitize_text_field( $delicious_recipe['totalCarbohydrate'] ) : '';
        $new_cp_recipe_meta['nutrition']['fiber']            = isset( $delicious_recipe['dietaryFiber'] ) ? sanitize_text_field( $delicious_recipe['dietaryFiber'] ) : '';
        $new_cp_recipe_meta['nutrition']['sugars']           = isset( $delicious_recipe['sugars'] ) ? sanitize_text_field( $delicious_recipe['sugars'] ) : '';
        $new_cp_recipe_meta['nutrition']['protein']          = isset( $delicious_recipe['protein'] ) ? sanitize_text_field( $delicious_recipe['protein'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_a']        = isset( $delicious_recipe['vitaminA'] ) ? sanitize_text_field( $delicious_recipe['vitaminA'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_c']        = isset( $delicious_recipe['vitaminC'] ) ? sanitize_text_field( $delicious_recipe['vitaminC'] ) : '';
        $new_cp_recipe_meta['nutrition']['calcium']          = isset( $delicious_recipe['calcium'] ) ? sanitize_text_field( $delicious_recipe['calcium'] ) : '';
        $new_cp_recipe_meta['nutrition']['iron']             = isset( $delicious_recipe['iron'] ) ? sanitize_text_field( $delicious_recipe['iron'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_d']        = isset( $delicious_recipe['vitaminD'] ) ? sanitize_text_field( $delicious_recipe['vitaminD'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_e']        = isset( $delicious_recipe['vitaminE'] ) ? sanitize_text_field( $delicious_recipe['vitaminE'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_k']        = isset( $delicious_recipe['vitaminK'] ) ? sanitize_text_field( $delicious_recipe['vitaminK'] ) : '';
        $new_cp_recipe_meta['nutrition']['thiamin']          = isset( $delicious_recipe['thiamin'] ) ? sanitize_text_field( $delicious_recipe['thiamin'] ) : '';
        $new_cp_recipe_meta['nutrition']['riboflavin']       = isset( $delicious_recipe['riboflavin'] ) ? sanitize_text_field( $delicious_recipe['riboflavin'] ) : '';
        $new_cp_recipe_meta['nutrition']['niacin']           = isset( $delicious_recipe['niacin'] ) ? sanitize_text_field( $delicious_recipe['niacin'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_b6']       = isset( $delicious_recipe['vitaminB6'] ) ? sanitize_text_field( $delicious_recipe['vitaminB6'] ) : '';
        $new_cp_recipe_meta['nutrition']['folate']           = isset( $delicious_recipe['folate'] ) ? sanitize_text_field( $delicious_recipe['folate'] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_b12']      = isset( $delicious_recipe['vitaminB12'] ) ? sanitize_text_field( $delicious_recipe['vitaminB12'] ) : '';
        $new_cp_recipe_meta['nutrition']['biotin']           = isset( $delicious_recipe['biotin'] ) ? sanitize_text_field( $delicious_recipe['biotin'] ) : '';
        $new_cp_recipe_meta['nutrition']['pantothenic_acid'] = isset( $delicious_recipe['pantothenicAcid'] ) ? sanitize_text_field( $delicious_recipe['pantothenicAcid'] ) : '';
        $new_cp_recipe_meta['nutrition']['phosphorus']       = isset( $delicious_recipe['phosphorus'] ) ? sanitize_text_field( $delicious_recipe['phosphorus'] ) : '';
        $new_cp_recipe_meta['nutrition']['iodine']           = isset( $delicious_recipe['iodine'] ) ? sanitize_text_field( $delicious_recipe['iodine'] ) : '';
        $new_cp_recipe_meta['nutrition']['magnesium']        = isset( $delicious_recipe['magnesium'] ) ? sanitize_text_field( $delicious_recipe['magnesium'] ) : '';
        $new_cp_recipe_meta['nutrition']['zinc']             = isset( $delicious_recipe['zinc'] ) ? sanitize_text_field( $delicious_recipe['zinc'] ) : '';
        $new_cp_recipe_meta['nutrition']['selenium']         = isset( $delicious_recipe['selenium'] ) ? sanitize_text_field( $delicious_recipe['selenium'] ) : '';
        $new_cp_recipe_meta['nutrition']['copper']           = isset( $delicious_recipe['copper'] ) ? sanitize_text_field( $delicious_recipe['copper'] ) : '';
        $new_cp_recipe_meta['nutrition']['manganese']        = isset( $delicious_recipe['manganese'] ) ? sanitize_text_field( $delicious_recipe['manganese'] ) : '';
        $new_cp_recipe_meta['nutrition']['chromium']         = isset( $delicious_recipe['chromium'] ) ? sanitize_text_field( $delicious_recipe['chromium'] ) : '';
        $new_cp_recipe_meta['nutrition']['molybdenum']       = isset( $delicious_recipe['molybdenum'] ) ? sanitize_text_field( $delicious_recipe['molybdenum'] ) : '';
        $new_cp_recipe_meta['nutrition']['chloride']         = isset( $delicious_recipe['chloride'] ) ? sanitize_text_field( $delicious_recipe['chloride'] ) : '';

        // Insert new post meta data.
        update_post_meta($new_recipe_id, '_recipe_settings', $new_cp_recipe_meta);

        // Updte the _thumbnail_id meta.
        $thumbnail_id = get_post_meta($id, '_thumbnail_id', true);
        update_post_meta($new_recipe_id, '_thumbnail_id', $thumbnail_id);
    }
}