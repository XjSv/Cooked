<?php
/**
 * WP Recipe Maker Import Class
 *
 * @package     Cooked_Recipe_Maker_Recipes
 * @subpackage  Cooked_Recipe_Maker_Recipes / Core
 * @since       1.11.2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Recipe_Maker Class
 *
 * This class handles the Delicious Recipes import functionality.
 *
 * @since 1.11.2
 */
class Cooked_Recipe_Maker_Recipes {

    /**
     * The import type.
     *
     * @since 1.11.2
     * @access public
     * @var string
     */
    public $import_type = 'wp_recipe_maker';

    /**
     * The constructor (empty for now).
     *
     * @since 1.11.2
     * @access public
     * @var string
     */
    public function __construct() {}

    /**
     * Get Delicious Recipes.
     *
     * @since 1.11.2
     * @access public
     */
    public static function get_recipes() {
        $wp_recipe_maker_recipes = [];

        $args = [
            'post_type' => 'wprm_recipe',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'wprm_type',
                    'value' => 'food',
                    'compare' => '=',
                ],
            ],
        ];

        $_recipes = new WP_Query( $args );

        if (!empty($_recipes->posts)) {
            foreach ($_recipes->posts as $rid) {
                $wp_recipe_maker_recipes[] = $rid;
            }
        }

        return $wp_recipe_maker_recipes;
    }

    /**
     * Import Delicious Recipes.
     *
     * @since 1.11.2
     * @access public
     */
    public static function import_recipe($id) {
        global $_cooked_settings;

        $post = get_post($id);
        $post_meta = get_post_custom($id);
        $wprm_notes = maybe_unserialize($post_meta['wprm_notes'][0]);

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
                'message' => __('Error importing WP Recipe Maker recipe.', 'cooked'),
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
        $new_cp_recipe_meta['excerpt'] = isset( $post->post_content ) ? sanitize_text_field( $post->post_content ) : '';
        $new_cp_recipe_meta['seo_description'] = isset( $post->post_content ) ? sanitize_text_field( $post->post_content ) : '';
        $new_cp_recipe_meta['notes'] = !empty( $wprm_notes ) ? wp_kses_post( $wprm_notes ) : '';

        $new_cp_recipe_meta['difficulty_level'] = 1;
        $new_cp_recipe_meta['prep_time'] = isset( $post_meta['wprm_prep_time'][0] ) ? (int)$post_meta['wprm_prep_time'][0] : 0;
        $new_cp_recipe_meta['cook_time'] = isset( $post_meta['wprm_cook_time'][0] ) ? (int)$post_meta['wprm_cook_time'][0] : 0;
        $new_cp_recipe_meta['total_time'] =  $new_cp_recipe_meta['prep_time'] + $new_cp_recipe_meta['cook_time'];

        // Recipe Ingredients.
        $recipeIngredients = isset( $post_meta['wprm_ingredients'][0] ) ? maybe_unserialize($post_meta['wprm_ingredients'][0]) : [];
        $new_cp_recipe_meta['ingredients'] = [];

        $measurements = Cooked_Measurements::get();
        $measurement_keys = array_keys($measurements);

        if (!empty($recipeIngredients)) {
            foreach ($recipeIngredients as $section) {
                // Check if this is a section with a name
                if (isset($section['name']) && !empty($section['name'])) {
                    $new_cp_recipe_meta['ingredients'][] = [
                        'section_heading_name' => $section['name'],
                    ];
                }

                // Process ingredients in this section
                if (isset($section['ingredients']) && !empty($section['ingredients'])) {
                    foreach ($section['ingredients'] as $ingredient) {
                        $new_cp_recipe_meta['ingredients'][] = [
                            'amount' => (!empty($ingredient['amount']) ? $ingredient['amount'] : ''),
                            'measurement' => (!empty($ingredient['unit']) && in_array($ingredient['unit'], $measurement_keys) ? $ingredient['unit'] : ''),
                            'name' => (!empty($ingredient['name']) ? $ingredient['name'] : ''),
                            'url' => '',
                            'description' => (!empty($ingredient['notes']) ? $ingredient['notes'] : ''),
                        ];
                    }
                }
            }
        }

        // Recipe Instructions.
        $recipeInstructions = isset( $post_meta['wprm_instructions'][0] ) ? maybe_unserialize($post_meta['wprm_instructions'][0]) : [];
        $new_cp_recipe_meta['directions'] = [];

        if (!empty($recipeInstructions)) {
            foreach ($recipeInstructions as $section) {
                // Check if this is a section with a name
                if (isset($section['name']) && !empty($section['name'])) {
                    $new_cp_recipe_meta['directions'][] = [
                        'section_heading_name' => $section['name'],
                    ];
                }

                // Process instructions in this section
                if (isset($section['instructions']) && !empty($section['instructions'])) {
                    foreach ($section['instructions'] as $instruction) {
                        $new_cp_recipe_meta['directions'][] = [
                            'image' => isset($instruction['image']) && $instruction['image'] !== 0 ? $instruction['image'] : '',
                            'content' => isset($instruction['text']) ? $instruction['text'] : '',
                        ];
                    }
                }
            }
        }

        // Gallery.
        $videoGalleryVids = isset( $post_meta['wprm_video_embed'] ) ? $post_meta['wprm_video_embed'][0] : '';
        $new_cp_recipe_meta['gallery'] = [
            'type' => 'cooked',
            'video_url' => $videoGalleryVids,
            'items' => [],
        ];

        // Nutrition Data.
        $new_cp_recipe_meta['nutrition']['serving_size']     = isset( $post_meta['wprm_nutrition_serving_size'][0] ) ? absint( $post_meta['wprm_nutrition_serving_size'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['servings']         = isset( $post_meta['wprm_servings'][0] ) ? absint( $post_meta['wprm_servings'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['calories']         = isset( $post_meta['wprm_nutrition_calories'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_calories'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['calories_fat']     = isset( $post_meta['wprm_nutrition_calories_from_fat'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_calories_from_fat'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['fat']              = isset( $post_meta['wprm_nutrition_fat'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_fat'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['sat_fat']          = isset( $post_meta['wprm_nutrition_saturated_fat'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_saturated_fat'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['trans_fat']        = isset( $post_meta['wprm_nutrition_trans_fat'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_trans_fat'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['cholesterol']      = isset( $post_meta['wprm_nutrition_cholesterol'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_cholesterol'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['sodium']           = isset( $post_meta['wprm_nutrition_sodium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_sodium'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['potassium']        = isset( $post_meta['wprm_nutrition_potassium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_potassium'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['carbs']            = isset( $post_meta['wprm_nutrition_carbohydrates'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_carbohydrates'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['fiber']            = isset( $post_meta['wprm_nutrition_fiber'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_fiber'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['sugars']           = isset( $post_meta['wprm_nutrition_sugar'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_sugar'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['protein']          = isset( $post_meta['wprm_nutrition_protein'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_protein'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_a']        = isset( $post_meta['wprm_nutrition_vitamin_a'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_a'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['vitamin_c']        = isset( $post_meta['wprm_nutrition_vitamin_c'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_c'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['calcium']          = isset( $post_meta['wprm_nutrition_calcium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_calcium'][0] ) : '';
        $new_cp_recipe_meta['nutrition']['iron']             = isset( $post_meta['wprm_nutrition_iron'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_iron'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['vitamin_d']        = isset( $post_meta['wprm_nutrition_vitamin_d'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_d'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['vitamin_e']        = isset( $post_meta['wprm_nutrition_vitamin_e'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_e'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['vitamin_k']        = isset( $post_meta['wprm_nutrition_vitamin_k'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_k'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['thiamin']          = isset( $post_meta['wprm_nutrition_thiamin'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_thiamin'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['riboflavin']       = isset( $post_meta['wprm_nutrition_riboflavin'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_riboflavin'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['niacin']           = isset( $post_meta['wprm_nutrition_niacin'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_niacin'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['vitamin_b6']       = isset( $post_meta['wprm_nutrition_vitamin_b6'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_b6'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['folate']           = isset( $post_meta['wprm_nutrition_folate'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_folate'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['vitamin_b12']      = isset( $post_meta['wprm_nutrition_vitamin_b12'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_vitamin_b12'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['biotin']           = isset( $post_meta['wprm_nutrition_biotin'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_biotin'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['pantothenic_acid'] = isset( $post_meta['wprm_nutrition_pantothenic_acid'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_pantothenic_acid'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['phosphorus']       = isset( $post_meta['wprm_nutrition_phosphorus'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_phosphorus'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['iodine']           = isset( $post_meta['wprm_nutrition_iodine'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_iodine'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['magnesium']        = isset( $post_meta['wprm_nutrition_magnesium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_magnesium'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['zinc']             = isset( $post_meta['wprm_nutrition_zinc'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_zinc'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['selenium']         = isset( $post_meta['wprm_nutrition_selenium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_selenium'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['copper']           = isset( $post_meta['wprm_nutrition_copper'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_copper'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['manganese']        = isset( $post_meta['wprm_nutrition_manganese'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_manganese'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['chromium']         = isset( $post_meta['wprm_nutrition_chromium'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_chromium'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['molybdenum']       = isset( $post_meta['wprm_nutrition_molybdenum'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_molybdenum'][0] ) : '';
        // $new_cp_recipe_meta['nutrition']['chloride']         = isset( $post_meta['wprm_nutrition_chloride'][0] ) ? sanitize_text_field( $post_meta['wprm_nutrition_chloride'][0] ) : '';

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
                $rating = get_comment_meta($comment->comment_ID, 'wprm-comment-rating', true);
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

        // Insert recipe taxonomies. Create the terms if they don't exist.
        $recipe_taxonomies_mapping = [
            'wprm_course' => 'cp_recipe_category',
            'wprm_cuisine' => 'cp_recipe_cuisine',
            'wprm_keyword' => 'cp_recipe_tags',
        ];

        $wp_recipe_maker_taxonomies = get_object_taxonomies('wprm_recipe');
        if (!empty($wp_recipe_maker_taxonomies)) {
            foreach ($wp_recipe_maker_taxonomies as $taxonomy) {
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
