<?php
/**
 * Post Types
 *
 * @package     Cooked
 * @subpackage  Taxonomies
 * @since       1.0.0
 */

namespace Cooked\Recipe;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomies Class
 *
 * This class handles the taxonomy creation.
 *
 * @since 1.0.0
 */
class Taxonomies {

    /**
     * The recipe taxonomies (filterable).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get() {
        global $query_var;

        $_cooked_settings = \Cooked_Settings::get();

        $front_page_id = get_option( 'page_on_front' );
        $query_var     = ( ! empty( $_cooked_settings['browse_page'] ) && (int) $_cooked_settings['browse_page'] === (int) $front_page_id ) ? false : true;

        $taxonomy_permalinks = apply_filters(
            'cooked_taxonomy_settings',
            [
				'cp_recipe_category' => ( isset( $_cooked_settings['recipe_category_permalink'] ) && $_cooked_settings['recipe_category_permalink'] ? $_cooked_settings['recipe_category_permalink'] : 'recipe-category' ),
			]
        );

        $taxonomies = apply_filters(
            'cooked_taxonomies',
            [
				'cp_recipe_category' => [
					'hierarchical'          => true,
					'labels'                => [
						'name'              => __( 'Categories', 'cooked' ),
						'singular_name'     => __( 'Category', 'cooked' ),
						'search_items'      => __( 'Search Categories', 'cooked' ),
						'all_items'         => __( 'All Categories', 'cooked' ),
						'parent_item'       => __( 'Parent Category', 'cooked' ),
						'parent_item_colon' => __( 'Parent Category:', 'cooked' ),
						'edit_item'         => __( 'Edit Category', 'cooked' ),
						'update_item'       => __( 'Update Category', 'cooked' ),
						'add_new_item'      => __( 'Add New Category', 'cooked' ),
						'new_item_name'     => __( 'New Category Name', 'cooked' ),
						'menu_name'         => __( 'Categories', 'cooked' ),
						'not_found'         => __( 'No Categories', 'cooked' ),
					],
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => true,
					'show_in_rest'          => true,
					'rest_base'             => 'recipe_category',
					'rest_controller_class' => 'WP_REST_Terms_Controller',
					'query_var'             => $query_var,
					'rewrite'               => [ 'slug' => $taxonomy_permalinks['cp_recipe_category'] ],
				],

			],
			$taxonomy_permalinks,
			$query_var
        );

        if ( ! empty( $_cooked_settings['recipe_taxonomies'] ) && ! in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies'], true ) ) :
			unset( $taxonomies['cp_recipe_category'] );
endif;

        // Filters.
        add_filter( 'term_link', [ 'Cooked_Taxonomies', 'term_link_filter' ], 10, 3 );

        return $taxonomies;
    }

    /**
     * Outputs one category block for [cooked-categories].
     *
     * @param int|string|false $term_id  Term ID.
     * @param string           $style    block or list.
     * @param string           $taxonomy Taxonomy.
     * @return void
     */
    public static function single_taxonomy_block( $term_id = false, $style = 'block', $taxonomy = 'cp_recipe_category' ) {
        if ( ! $term_id ) {
			return;
        }

        $term = get_term( (int) $term_id );
        if ( $term && ! is_wp_error( $term ) ) :

            if ( 'block' === $style ) :
                echo '<div class="cooked-term-block cooked-col-25">';
                    echo do_shortcode( '[cooked-recipe-card style="modern-centered" category="' . esc_attr( (string) $term_id ) . '"]' );
                echo '</div>';
            elseif ( 'list' === $style ) :
                echo '<div class="cooked-term-item">';
                    $term_name = apply_filters( 'cooked_term_name', $term->name, $term->term_id, $taxonomy );
                    $term_link = get_term_link( $term->term_id, $taxonomy );
                    echo '<a href="' . esc_url( is_wp_error( $term_link ) ? '' : $term_link ) . '">' . wp_kses_post( $term_name ) . '</a>';
                echo '</div>';
            endif;

        endif;
    }

    /**
     * A category card.
     *
     * @param int|string|false $term_id    Term ID.
     * @param string|false     $width      Card width.
     * @param bool|string      $hide_image Hide the image.
     * @param bool|string      $hide_total Hide the recipe count.
     * @param string|false     $style      Card style.
     * @return string|false
     */
    public static function card( $term_id = false, $width = false, $hide_image = false, $hide_total = false, $style = false ) {
        if ( ! $term_id ) {
			return false;
        }

        $term = get_term( (int) $term_id );
        if ( $term && ! is_wp_error( $term ) ) :

            $args = [
                'post_type'      => 'cp_recipe',
                'posts_per_page' => -1,
                'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Counts recipes in a term.
                    [
                        'taxonomy' => $term->taxonomy,
                        'field'    => 'term_id',
                        'terms'    => [ $term_id ],
                    ],
                ],
            ];

            $recipes       = (array) \Cooked_Recipes::get( $args );
            $recent_recipe = isset( $recipes[0] ) ? $recipes[0] : null;
            $total_recipes = count( $recipes ) - 1; // Total items in array minus the single ['raw'] item.
            $thumbnail     = $recent_recipe ? get_the_post_thumbnail_url( $recent_recipe['id'], [ 450, 450 ] ) : false;
            $term_link     = get_term_link( $term );
            $term_link     = is_wp_error( $term_link ) ? '' : $term_link;
            $style_class   = ( $style ? ' cooked-recipe-card-' . esc_attr( $style ) : '' );
            $width         = ( ! $width ? '100%' : $width );
            $pixel_width   = stristr( $width, 'px', true );
            $percent_width = stristr( $width, '%', true );
            $width         = ( $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) ) );
            $term_name     = apply_filters( 'cooked_term_name', $term->name, $term->term_id, $term->taxonomy );

            ob_start();

            echo '<a href="' . esc_url( $term_link ) . '" class="cooked-recipe-taxonomy-card cooked-recipe-card' . esc_attr( $style_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

                echo ( $thumbnail && ! $hide_image ? '<span class="cooked-recipe-card-image" style="background-image:url(' . esc_url( $thumbnail ) . ');"></span>' : '' );

                echo '<span class="cooked-recipe-card-content">';

                    echo '<span class="cooked-recipe-card-title">' . wp_kses_post( $term_name ) . '</span>';

                    echo '<span class="cooked-recipe-card-sep"></span>';

                    echo '<span class="cooked-recipe-card-author">';
                        /* translators: for displaying singular or plural versions depending on the number. */
                        echo esc_html( sprintf( _n( '%s Recipe', '%s Recipes', $total_recipes, 'cooked' ), number_format( $total_recipes, 0 ) ) );
                    echo '</span>';

                echo '</span>';

            echo '</a>';

            return ob_get_clean();

        endif;

        return false;
    }

    /**
     * Points recipe category links at the Browse page.
     *
     * @param string   $url      Term link.
     * @param \WP_Term $term     Term.
     * @param string   $taxonomy Taxonomy.
     * @return string
     */
    public static function term_link_filter( $url, $term, $taxonomy ) {
        $_cooked_settings = \Cooked_Settings::get();

        // Get the translated browse page for the current language.
        $parent_page_browse_page = \Cooked_Multilingual::get_browse_page_id();
        $front_page              = get_option( 'page_on_front' );
        $cooked_taxonomies       = [ 'cp_recipe_category' ];

        if ( $parent_page_browse_page && in_array( $taxonomy, $cooked_taxonomies, true ) ) {
            $custom_cooked_tax_setting = 'recipe_category_permalink';

            if ( (int) $parent_page_browse_page !== (int) $front_page && get_option( 'permalink_structure' ) ) {
                $url = esc_url_raw( untrailingslashit( (string) get_permalink( $parent_page_browse_page ) ) . '/' . $_cooked_settings[ $custom_cooked_tax_setting ] . '/' . $term->slug );
            } elseif ( (int) $parent_page_browse_page === (int) $front_page ) {
                if ( get_option( 'permalink_structure' ) ) {
                    $url = esc_url_raw( get_home_url() . '/' . $_cooked_settings[ $custom_cooked_tax_setting ] . '/' . $term->slug );
                } else {
                    $url = esc_url_raw( get_home_url() . '?' . $taxonomy . '=' . $term->slug );
                }
            } else {
                $url = esc_url_raw( get_permalink( $parent_page_browse_page ) . '&' . $taxonomy . '=' . $term->slug );
            }
        }

        return $url;
    }
}
