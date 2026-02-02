<?php
/**
 * Delicious Recipe Import Class
 *
 * @package     Cooked_Delicious_Recipes
 * @subpackage  Cooked_Delicious_Recipes / Core
 * @since       1.8.2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Delicious_Recipes Class
 *
 * This class handles the Delicious Recipes import functionality.
 *
 * @since 1.8.2
 */
class Cooked_Delicious_Recipes {

    /**
     * The import type.
     *
     * @since 1.8.2
     * @access public
     * @var string
     */
    public $import_type = 'delicious_recipes';

    /**
     * The constructor (empty for now).
     *
     * @since 1.8.2
     * @access public
     * @var string
     */
    public function __construct() {}

    /**
     * Get Delicious Recipes.
     *
     * @since 1.8.2
     * @access public
     */
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

    /**
     * Import Delicious Recipes.
     *
     * @since 1.8.2
     * @access public
     */
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
                'message' => __('Error importing WP Delicious recipe.', 'cooked'),
            ];
        }

        if (isset($_cooked_settings['default_content'])) {
            $default_content = wp_unslash($_cooked_settings['default_content']);
        } else {
            $default_content = Cooked_Recipes::default_content();
        }

        // Insert new post meta data.
        $new_cp_recipe_meta = [];
        $new_cp_recipe_meta['cooked_version'] = COOKED_VERSION;
        $new_cp_recipe_meta['content'] = $default_content;
        $new_cp_recipe_meta['excerpt'] = isset( $delicious_recipe['recipeDescription'] ) ? sanitize_text_field( $delicious_recipe['recipeDescription'] ) : '';
        $new_cp_recipe_meta['seo_description'] = isset( $delicious_recipe['recipeDescription'] ) ? sanitize_text_field( $delicious_recipe['recipeDescription'] ) : '';
        $new_cp_recipe_meta['notes'] = isset( $delicious_recipe['recipeNotes'] ) ? $delicious_recipe['recipeNotes'] : '';

        // Convert beginner, intermediate, advanced to 1, 2, 3.
        $difficulty_levels = [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
        ];
        $new_cp_recipe_meta['difficulty_level'] = isset( $delicious_recipe['difficultyLevel'] ) && isset($difficulty_levels[$delicious_recipe['difficultyLevel']]) ? $difficulty_levels[$delicious_recipe['difficultyLevel']] : 1;

        $new_cp_recipe_meta['prep_time'] = isset( $delicious_recipe['prepTime'] ) ? (int)$delicious_recipe['prepTime'] : 0;
        $new_cp_recipe_meta['cook_time'] = isset( $delicious_recipe['cookTime'] ) ? (int)$delicious_recipe['cookTime'] : 0;
        $new_cp_recipe_meta['total_time'] =  $new_cp_recipe_meta['prep_time'] + $new_cp_recipe_meta['cook_time'];

        // Recipe Ingredients.
        $recipeIngredients = isset( $delicious_recipe['recipeIngredients'] ) ? $delicious_recipe['recipeIngredients'] : [];
        $new_cp_recipe_meta['ingredients'] = [];

        if (!empty($recipeIngredients)) {
            foreach ($recipeIngredients as $ingredient) {
                if (isset($ingredient['sectionTitle']) && !empty($ingredient['sectionTitle']) && empty($ingredient['ingredients'])) {
                    $new_cp_recipe_meta['ingredients'][] = [
                        'section_heading_name' => $ingredient['sectionTitle'],
                    ];
                } else {
                    foreach ($ingredient['ingredients'] as $ingredient) {
                        $new_cp_recipe_meta['ingredients'][] = [
                            'amount' => (!empty($ingredient['quantity']) ? $ingredient['quantity'] : ''),
                            'measurement' => (!empty($ingredient['unit']) ? $ingredient['unit'] : ''),
                            'name' => (!empty($ingredient['ingredient']) ? $ingredient['ingredient'] : ''),
                            'url' => '',
                            'description' => (!empty($ingredient['notes']) ? $ingredient['notes'] : ''),
                        ];
                    }
                }
            }
        }

        // Recipe Instructions.
        $recipeInstructions = isset( $delicious_recipe['recipeInstructions'] ) ? $delicious_recipe['recipeInstructions'] : [];
        $new_cp_recipe_meta['directions'] = [];

        if (!empty($recipeInstructions)) {
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
        }

        // Gallery.
        $imageGalleryImages = isset( $delicious_recipe['imageGalleryImages'] ) ? $delicious_recipe['imageGalleryImages'] : [];
        $videoGalleryVids = isset( $delicious_recipe['videoGalleryVids'] ) ? $delicious_recipe['videoGalleryVids'] : [];

        $video_url = '';
        if (!empty($videoGalleryVids)) {
            if ($videoGalleryVids[0]['vidType'] === 'youtube') {
                $video_url = 'https://www.youtube.com/watch?v=' . $videoGalleryVids[0]['vidID'];
            }

            if ($videoGalleryVids[0]['vidType'] === 'vimeo') {
                $video_url = 'https://vimeo.com/' . $videoGalleryVids[0]['vidID'];
            }
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

        // Copy comments from old recipe to new recipe.
        $ratings = [];
        $comments = get_comments(['post_id' => $id]);
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $commentdata = [
                    'comment_post_ID' => $new_recipe_id,
                    'comment_author' => $comment->comment_author,
                    'comment_author_email' => $comment->comment_author_email,
                    'comment_author_url' => $comment->comment_author_url,
                    'comment_content' => $comment->comment_content,
                    'comment_type' => $comment->comment_type,
                    'comment_parent' => $comment->comment_parent,
                    'user_id' => $comment->user_id,
                    'comment_author_IP' => $comment->comment_author_IP,
                    'comment_agent' => $comment->comment_agent,
                    'comment_date' => $comment->comment_date,
                    'comment_approved' => $comment->comment_approved,
                ];
                wp_insert_comment($commentdata);

                // Rating are stored in the wp_commentmeta table with the key: 'rating'.
                $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                if (!empty($rating)) {
                    $ratings[] = $rating;
                }
            }
        }

        // Insert recipe ratings meta data.
        $rating_sum = array_sum($ratings);
        $rating_average = 0;
        if ($rating_sum > 0) {
            $rating_average = number_format($rating_sum / count($ratings), 1 );
        }
        update_post_meta($new_recipe_id, '_recipe_rating', $rating_average);

        // Insert recipe likes and wishlist meta data.
        $likes = get_post_meta( $id, '_recipe_likes', true );
        update_post_meta( $new_recipe_id, '_recipe_favorites', $likes );

        // Insert recipe taxonomies. Create the terms if they don't exist.
        $recipe_taxonomies_mapping = [
            'recipe-course' => 'cp_recipe_category',
            'recipe-cuisine' => 'cp_recipe_cuisine',
            'recipe-cooking-method' => 'cp_recipe_cooking_method',
            'recipe-tag' => 'cp_recipe_tags',
            'recipe-dietary' =>  'cp_recipe_diet',
        ];

        $delicious_recipes_taxonomies = get_object_taxonomies('recipe');
        if (!empty($delicious_recipes_taxonomies)) {
            foreach ($delicious_recipes_taxonomies as $taxonomy) {
                if (isset($recipe_taxonomies_mapping[$taxonomy])) {
                    $terms = wp_get_object_terms($id, $taxonomy, ['fields' => 'all']);

                    if (!empty($terms)) {
                        $new_terms = [];

                        foreach ($terms as $term_id) {
                            $term = get_term($term_id);
                            $term_exists = term_exists($term->name, $recipe_taxonomies_mapping[$taxonomy]);

                            if (!$term_exists) {
                                $new_term = wp_insert_term($term->name, $recipe_taxonomies_mapping[$taxonomy]);
                                if (is_wp_error($new_term)) {
                                    continue;
                                }

                                $new_term_id = (int)$new_term['term_id'];
                            } else {
                                $new_term_id = (int)$term_exists['term_id'];
                            }

                            $new_terms[] = $new_term_id;
                        }

                        wp_set_object_terms($new_recipe_id, $new_terms, $recipe_taxonomies_mapping[$taxonomy], true);

                        // Update the term count.
                        wp_update_term_count($new_terms, $recipe_taxonomies_mapping[$taxonomy]);
                    }
                }
            }
        }
    }
}