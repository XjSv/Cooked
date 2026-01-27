<?php
/**
 * Cooked Related Recipes
 *
 * @package     Cooked
 * @subpackage  Related Recipes
 * @since       1.12.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cooked_Related_Recipes Class
 *
 * Handles related recipes logic, transient caching, and admin pre-calculation.
 *
 * @since 1.12.0
 */
class Cooked_Related_Recipes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'cooked_settings_tabs_fields', [ $this, 'add_related_recipes_tab' ], 15, 1 );
        add_action( 'save_post', [ $this, 'maybe_bump_cache_version' ], 10, 1 );
        add_action( 'delete_post', [ $this, 'maybe_bump_cache_version' ], 10, 1 );
    }

    /**
     * Add Tools tab to Settings with Calculate Related Recipes section.
     *
     * @param array $tabs Existing tabs.
     * @return array
     */
    public function add_related_recipes_tab( $tabs ) {
        $tabs['tools'] = [
            'name'   => __( 'Tools', 'cooked' ),
            'icon'   => 'gear',
            'fields' => [
                'cooked_calculate_related_button' => [
                    'title' => __( 'Calculate Related Recipes', 'cooked' ),
                    'desc'  => __( 'Pre-calculate related recipes for every published recipe. Uses default shortcode options. Run this after importing or adding many recipes, or when the cache was cleared. One recipe is processed per step to avoid memory issues on large sites.', 'cooked' ),
                    'type'  => 'calculate_related_button',
                ],
            ],
        ];
        return $tabs;
    }

    /**
     * Bump cache version when a recipe is saved or deleted.
     *
     * @param int $post_id Post ID.
     */
    public function maybe_bump_cache_version( $post_id ) {
        if ( get_post_type( $post_id ) === 'cp_recipe' ) {
            self::bump_related_cache_version();
        }
    }

    /**
     * Get default shortcode attributes.
     *
     * @return array
     */
    public static function get_default_atts() {
        $default_atts = [
            'id'                    => false,
            'title'                 => __( 'Related Recipes', 'cooked' ),
            'limit'                 => 4,
            'columns'               => 2,
            'hide_image'            => false,
            'hide_excerpt'          => false,
            'hide_author'           => false,
            'match_categories'      => true,
            'match_cuisines'        => true,
            'match_cooking_methods' => true,
            'match_tags'            => true,
            'match_diets'           => true,
            'match_ingredients'     => true,
            'match_author'          => false,
            'match_difficulty'      => false,
            'category_weight'       => 10,
            'cuisine_weight'        => 8,
            'ingredient_weight'     => 9,
            'cooking_method_weight' => 5,
            'tag_weight'            => 5,
            'diet_weight'           => 4,
            'author_weight'         => 3,
            'difficulty_weight'     => 2,
        ];

        /**
         * Filter default attributes for the related recipes shortcode.
         *
         * @since 1.12.0
         *
         * @param array $default_atts Default shortcode attributes.
         */
        return apply_filters( 'cooked_related_recipes_default_atts', $default_atts );
    }

    /**
     * Extract only scoring-related attributes (excludes display-only attributes).
     *
     * @param array $atts All shortcode attributes.
     * @return array Scoring-related attributes only.
     */
    public static function get_scoring_atts( $atts ) {
        return [
            'match_categories'      => isset( $atts['match_categories'] ) ? $atts['match_categories'] : true,
            'match_cuisines'        => isset( $atts['match_cuisines'] ) ? $atts['match_cuisines'] : true,
            'match_cooking_methods' => isset( $atts['match_cooking_methods'] ) ? $atts['match_cooking_methods'] : true,
            'match_tags'            => isset( $atts['match_tags'] ) ? $atts['match_tags'] : true,
            'match_diets'           => isset( $atts['match_diets'] ) ? $atts['match_diets'] : true,
            'match_ingredients'     => isset( $atts['match_ingredients'] ) ? $atts['match_ingredients'] : true,
            'match_author'          => isset( $atts['match_author'] ) ? $atts['match_author'] : false,
            'match_difficulty'      => isset( $atts['match_difficulty'] ) ? $atts['match_difficulty'] : false,
            'category_weight'       => isset( $atts['category_weight'] ) ? $atts['category_weight'] : 10,
            'cuisine_weight'        => isset( $atts['cuisine_weight'] ) ? $atts['cuisine_weight'] : 8,
            'cooking_method_weight' => isset( $atts['cooking_method_weight'] ) ? $atts['cooking_method_weight'] : 6,
            'tag_weight'            => isset( $atts['tag_weight'] ) ? $atts['tag_weight'] : 5,
            'diet_weight'           => isset( $atts['diet_weight'] ) ? $atts['diet_weight'] : 4,
            'ingredient_weight'     => isset( $atts['ingredient_weight'] ) ? $atts['ingredient_weight'] : 7,
            'author_weight'         => isset( $atts['author_weight'] ) ? $atts['author_weight'] : 3,
            'difficulty_weight'     => isset( $atts['difficulty_weight'] ) ? $atts['difficulty_weight'] : 2,
        ];
    }

    /**
     * Build cache key for related recipes.
     * Uses scoring-related attributes + limit + language (since they affect what we store in cache).
     *
     * @param int   $recipe_id Recipe ID.
     * @param array $atts      Shortcode attributes.
     * @return string
     */
    public static function get_cache_key( $recipe_id, $atts ) {
        $version = (int) get_option( 'cooked_related_version', 1 );
        $scoring_atts = self::get_scoring_atts( $atts );
        // Include limit in cache key since it affects what we store
        $limit = isset( $atts['limit'] ) ? (int) $atts['limit'] : 0;
        $cache_key_data = $scoring_atts;
        $cache_key_data['limit'] = $limit;

        // Include current language in cache key for multilingual support
        $current_language = false;
        if ( class_exists( 'Cooked_Multilingual' ) && Cooked_Multilingual::is_multilingual_active() ) {
            $current_language = Cooked_Multilingual::get_current_language();
        }
        if ( $current_language ) {
            $cache_key_data['lang'] = $current_language;
        }

        return 'cooked_related_v' . $version . '_' . $recipe_id . '_' . md5( serialize( $cache_key_data ) );
    }

    /**
     * Bump the related recipes cache version to invalidate all cached results.
     */
    public static function bump_related_cache_version() {
        update_option( 'cooked_related_version', (int) get_option( 'cooked_related_version', 1 ) + 1 );
    }

    /**
     * Get related recipes: check cache first, compute and store on miss.
     * Cache is based on scoring attributes only, so it works across different display settings.
     *
     * @param int   $recipe_id Recipe ID.
     * @param array $atts      Shortcode attributes.
     * @return array Array of [ 'id' => int, 'score' => int ].
     */
    public static function get_related_recipes( $recipe_id, $atts ) {
        $key = self::get_cache_key( $recipe_id, $atts );
        $cached = get_transient( $key );
        if ( $cached !== false && is_array( $cached ) ) {
            // Cache already has the limit applied, just return it
            return $cached;
        }

        $source_recipe = Cooked_Recipes::get( $recipe_id, true );
        if ( ! $source_recipe || empty( $source_recipe ) ) {
            return [];
        }

        $scores = self::find_related_recipes( $source_recipe, $atts );

        // Apply limit when storing in cache - store only what we'll actually use
        $limit = isset( $atts['limit'] ) ? (int) $atts['limit'] : 0;
        if ( $limit > 0 && count( $scores ) > $limit ) {
            $scores = array_slice( $scores, 0, $limit );
        }

        $ttl = (int) apply_filters( 'cooked_related_recipes_cache_ttl', 7 * DAY_IN_SECONDS );
        set_transient( $key, $scores, $ttl );

        return $scores;
    }

    /**
     * Pre-fill cache for one recipe using default atts. Used by admin Calculate tool.
     *
     * @param int $recipe_id Recipe ID.
     */
    public static function prime_cache_for_recipe( $recipe_id ) {
        $source_recipe = Cooked_Recipes::get( $recipe_id, true );
        if ( ! $source_recipe || empty( $source_recipe ) ) {
            return;
        }

        $atts = self::get_default_atts();
        $scores = self::find_related_recipes( $source_recipe, $atts );

        // Apply default limit when storing in cache - store only what we'll actually use
        $limit = isset( $atts['limit'] ) ? (int) $atts['limit'] : 0;
        if ( $limit > 0 && count( $scores ) > $limit ) {
            $scores = array_slice( $scores, 0, $limit );
        }

        $key = self::get_cache_key( $recipe_id, $atts );
        $ttl = (int) apply_filters( 'cooked_related_recipes_cache_ttl', 7 * DAY_IN_SECONDS );
        set_transient( $key, $scores, $ttl );
    }

    /**
     * Find related recipes based on various factors.
     * Processes recipes in batches to avoid memory issues with large sites.
     *
     * @param array $source_recipe Source recipe data.
     * @param array $atts          Shortcode attributes.
     * @return array [ ['id' => int, 'score' => int], ... ]
     */
    public static function find_related_recipes( $source_recipe, $atts ) {
        $recipe_id = $source_recipe['id'];
        $scores = [];

        // Batch size for processing recipes (process 100 at a time to avoid memory issues)
        $batch_size = apply_filters( 'cooked_related_recipes_batch_size', 100 );
        $paged = 1;

        // Pre-calculate source recipe data once
        $source_categories      = self::get_recipe_terms( $recipe_id, 'cp_recipe_category' );
        $source_cuisines        = self::get_recipe_terms( $recipe_id, 'cp_recipe_cuisine' );
        $source_cooking_methods = self::get_recipe_terms( $recipe_id, 'cp_recipe_cooking_method' );
        $source_tags            = self::get_recipe_terms( $recipe_id, 'cp_recipe_tags' );
        $source_diets           = self::get_recipe_terms( $recipe_id, 'cp_recipe_diet' );
        $source_ingredients     = self::get_recipe_ingredients( $source_recipe );
        $source_author          = isset( $source_recipe['author']['id'] ) ? $source_recipe['author']['id'] : false;
        $source_difficulty      = isset( $source_recipe['difficulty_level'] ) ? $source_recipe['difficulty_level'] : false;

        // Get current language for multilingual support
        $current_language = false;
        if ( class_exists( 'Cooked_Multilingual' ) && Cooked_Multilingual::is_multilingual_active() ) {
            $current_language = Cooked_Multilingual::get_current_language();
        }

        // Process recipes in batches using WP_Query directly
        while ( true ) {
            $query_args = [
                'post_type'      => 'cp_recipe',
                'post_status'    => 'publish',
                'post__not_in'   => [ $recipe_id ],
                'posts_per_page' => $batch_size,
                'paged'          => $paged,
                'fields'         => 'ids', // Only get IDs to save memory
                'no_found_rows'  => false, // We need to know if there are more pages
            ];

            // Allow multilingual plugins to filter the query
            $query_args = apply_filters( 'cooked_related_recipes_query_args', $query_args, $current_language );

            $query = new \WP_Query( $query_args );
            $recipe_ids = $query->posts;

            if ( empty( $recipe_ids ) ) {
                break;
            }

            // Process each recipe in the current batch
            foreach ( $recipe_ids as $rid ) {
                $score = 0;

                // Get recipe settings only when needed (lazy loading)
                $recipe_settings = null;

                if ( $atts['match_categories'] && $atts['match_categories'] !== 'false' ) {
                    $categories = self::get_recipe_terms( $rid, 'cp_recipe_category' );
                    $score += count( array_intersect( $source_categories, $categories ) ) * (int) $atts['category_weight'];
                }

                if ( $atts['match_cuisines'] && $atts['match_cuisines'] !== 'false' ) {
                    $cuisines = self::get_recipe_terms( $rid, 'cp_recipe_cuisine' );
                    $score += count( array_intersect( $source_cuisines, $cuisines ) ) * (int) $atts['cuisine_weight'];
                }

                if ( $atts['match_cooking_methods'] && $atts['match_cooking_methods'] !== 'false' ) {
                    $cooking_methods = self::get_recipe_terms( $rid, 'cp_recipe_cooking_method' );
                    $score += count( array_intersect( $source_cooking_methods, $cooking_methods ) ) * (int) $atts['cooking_method_weight'];
                }

                if ( $atts['match_tags'] && $atts['match_tags'] !== 'false' ) {
                    $tags = self::get_recipe_terms( $rid, 'cp_recipe_tags' );
                    $score += count( array_intersect( $source_tags, $tags ) ) * (int) $atts['tag_weight'];
                }

                if ( $atts['match_diets'] && $atts['match_diets'] !== 'false' ) {
                    $diets = self::get_recipe_terms( $rid, 'cp_recipe_diet' );
                    $score += count( array_intersect( $source_diets, $diets ) ) * (int) $atts['diet_weight'];
                }

                // Only load full recipe data if we need ingredients, author, or difficulty
                if ( ( $atts['match_ingredients'] && $atts['match_ingredients'] !== 'false' ) ||
                     ( $atts['match_author'] && $atts['match_author'] !== 'false' && $source_author ) ||
                     ( $atts['match_difficulty'] && $atts['match_difficulty'] !== 'false' && $source_difficulty ) ) {

                    if ( is_null( $recipe_settings ) ) {
                        $recipe_settings = Cooked_Recipes::get_settings( $rid );
                    }

                    if ( $atts['match_ingredients'] && $atts['match_ingredients'] !== 'false' ) {
                        $recipe_data = [ 'ingredients' => isset( $recipe_settings['ingredients'] ) ? $recipe_settings['ingredients'] : [] ];
                        $ingredients = self::get_recipe_ingredients( $recipe_data );
                        $score += self::compare_ingredients( $source_ingredients, $ingredients ) * (int) $atts['ingredient_weight'];
                        unset( $recipe_data ); // Free memory immediately
                    }

                    if ( $atts['match_author'] && $atts['match_author'] !== 'false' && $source_author ) {
                        $post = get_post( $rid );
                        $author = $post ? (int) $post->post_author : false;
                        if ( $author && $author === (int) $source_author ) {
                            $score += (int) $atts['author_weight'];
                        }
                    }

                    if ( $atts['match_difficulty'] && $atts['match_difficulty'] !== 'false' && $source_difficulty ) {
                        $difficulty = isset( $recipe_settings['difficulty_level'] ) ? $recipe_settings['difficulty_level'] : false;
                        if ( $difficulty && $difficulty === $source_difficulty ) {
                            $score += (int) $atts['difficulty_weight'];
                        }
                    }
                }

                if ( $score > 0 ) {
                    $recipe_score = [ 'id' => $rid, 'score' => $score ];
                    
                    /**
                     * Filter individual recipe score before adding to results.
                     *
                     * @since 1.12.0
                     *
                     * @param array $recipe_score Recipe score data ['id' => int, 'score' => int].
                     * @param int   $rid          Recipe ID.
                     * @param array $source_recipe Source recipe data.
                     * @param array $atts         Shortcode attributes.
                     */
                    $recipe_score = apply_filters( 'cooked_related_recipes_recipe_score', $recipe_score, $rid, $source_recipe, $atts );
                    
                    if ( $recipe_score && isset( $recipe_score['id'] ) && isset( $recipe_score['score'] ) && $recipe_score['score'] > 0 ) {
                        $scores[] = $recipe_score;
                    }
                }

                // Clear recipe settings for next iteration
                $recipe_settings = null;
            }

            // Clean up query to free memory
            wp_reset_postdata();

            // Check if there are more recipes to process
            if ( $paged >= $query->max_num_pages ) {
                break;
            }
            $paged++;
        }

        /**
         * Filter scores before sorting.
         *
         * @since 1.12.0
         *
         * @param array $scores        Array of recipe scores [ ['id' => int, 'score' => int], ... ].
         * @param array $source_recipe Source recipe data.
         * @param array $atts         Shortcode attributes.
         */
        $scores = apply_filters( 'cooked_related_recipes_scores_before_sort', $scores, $source_recipe, $atts );

        // Sort by score descending
        usort( $scores, function ( $a, $b ) {
            return $b['score'] - $a['score'];
        } );

        /**
         * Filter final sorted scores.
         *
         * @since 1.12.0
         *
         * @param array $scores        Array of sorted recipe scores [ ['id' => int, 'score' => int], ... ].
         * @param array $source_recipe Source recipe data.
         * @param array $atts         Shortcode attributes.
         */
        $scores = apply_filters( 'cooked_related_recipes_scores', $scores, $source_recipe, $atts );

        return $scores;
    }

    /**
     * Get recipe term IDs for a taxonomy.
     *
     * @param int    $recipe_id Recipe ID.
     * @param string $taxonomy  Taxonomy name.
     * @return array
     */
    public static function get_recipe_terms( $recipe_id, $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return [];
        }
        $terms = wp_get_object_terms( $recipe_id, $taxonomy, [ 'fields' => 'ids' ] );
        return is_wp_error( $terms ) ? [] : $terms;
    }

    /**
     * Get normalized ingredient names from recipe data.
     *
     * @param array $recipe Recipe data.
     * @return array
     */
    public static function get_recipe_ingredients( $recipe ) {
        $ingredients = [];
        if ( ! isset( $recipe['ingredients'] ) || empty( $recipe['ingredients'] ) ) {
            return $ingredients;
        }
        foreach ( $recipe['ingredients'] as $ing ) {
            if ( isset( $ing['section_heading_name'] ) ) {
                continue;
            }
            $name = isset( $ing['name'] ) ? trim( strtolower( $ing['name'] ) ) : '';
            if ( $name ) {
                $ingredients[] = $name;
            }
        }
        return array_unique( $ingredients );
    }

    /**
     * Compare ingredient overlap between two recipes.
     *
     * @param array $ingredients1 First recipe ingredients.
     * @param array $ingredients2 Second recipe ingredients.
     * @return float
     */
    public static function compare_ingredients( $ingredients1, $ingredients2 ) {
        if ( empty( $ingredients1 ) || empty( $ingredients2 ) ) {
            return 0;
        }
        $exact = count( array_intersect( $ingredients1, $ingredients2 ) );
        $partial = 0;
        foreach ( $ingredients1 as $ing1 ) {
            foreach ( $ingredients2 as $ing2 ) {
                if ( $ing1 !== $ing2 && ( strpos( $ing1, $ing2 ) !== false || strpos( $ing2, $ing1 ) !== false ) ) {
                    $partial += 0.5;
                    break;
                }
            }
        }
        return $exact + $partial;
    }
}
