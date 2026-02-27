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
 * Handles related recipes via a single on-demand WP_Query: recipes sharing at least
 * one term in any enabled taxonomy (categories, cuisines, cooking methods, tags, diets),
 * ordered randomly. No caching or pre-calculation.
 *
 * @since 1.12.0
 */
class Cooked_Related_Recipes {

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
     * Get related recipes (on-demand query, no cache).
     * Uses all enabled taxonomies with OR relation and orderby rand.
     *
     * @param int   $recipe_id Recipe ID.
     * @param array $atts      Shortcode attributes.
     * @return array Array of [ 'id' => int ].
     */
    public static function get_related_recipes( $recipe_id, $atts ) {
        $source_recipe = Cooked_Recipes::get( $recipe_id, true );
        if ( ! $source_recipe || empty( $source_recipe ) ) {
            return [];
        }

        return self::find_related_recipes( $source_recipe, $atts );
    }

    /**
     * Find related recipes: one WP_Query with tax_query (all taxonomies OR) and orderby rand.
     *
     * @param array $source_recipe Source recipe data.
     * @param array $atts          Shortcode attributes.
     * @return array [ ['id' => int], ... ]
     */
    public static function find_related_recipes( $source_recipe, $atts ) {
        $recipe_id = $source_recipe['id'];
        $limit = isset( $atts['limit'] ) ? max( 1, (int) $atts['limit'] ) : 4;

        // Build OR clause for each taxonomy that is enabled via match_* and where the source recipe has terms.
        $clauses = [];
        $taxonomy_atts = [
            'cp_recipe_category'       => 'match_categories',
            'cp_recipe_cuisine'        => 'match_cuisines',
            'cp_recipe_cooking_method' => 'match_cooking_methods',
            'cp_recipe_tags'           => 'match_tags',
            'cp_recipe_diet'           => 'match_diets',
        ];
        foreach ( $taxonomy_atts as $taxonomy => $att_key ) {
            if ( empty( $atts[ $att_key ] ) || $atts[ $att_key ] === 'false' ) {
                continue;
            }
            $terms = self::get_recipe_terms( $recipe_id, $taxonomy );
            if ( ! empty( $terms ) ) {
                $clauses[] = [ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $terms ];
            }
        }

        if ( empty( $clauses ) ) {
            return [];
        }

        $tax_query = array_merge( [ 'relation' => 'OR' ], $clauses );

        $query_args = [
            'post_type'      => 'cp_recipe',
            'post_status'    => 'publish',
            'post__not_in'   => [ $recipe_id ],
            'posts_per_page' => $limit,
            'orderby'        => 'rand',
            'fields'         => 'ids',
        ];

        $query_args['tax_query'] = $tax_query;

        $current_language = false;
        if ( class_exists( 'Cooked_Multilingual' ) && Cooked_Multilingual::is_multilingual_active() ) {
            $current_language = Cooked_Multilingual::get_current_language();
        }
        $query_args = apply_filters( 'cooked_related_recipes_query_args', $query_args, $current_language );

        $query = new \WP_Query( $query_args );
        $ids = ! empty( $query->posts ) ? $query->posts : [];
        wp_reset_postdata();

        $result = [];
        foreach ( $ids as $id ) {
            $result[] = [ 'id' => (int) $id ];
        }

        /**
         * Filter the related recipe IDs before return.
         *
         * @since 1.12.0
         *
         * @param array $result        Array of [ 'id' => int ].
         * @param array $source_recipe Source recipe data.
         * @param array $atts         Shortcode attributes.
         */
        return apply_filters( 'cooked_related_recipes_result', $result, $source_recipe, $atts );
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
}
