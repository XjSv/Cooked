<?php
/**
 * Post Types
 *
 * @package     Cooked
 * @subpackage  Taxonomies
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Taxonomies Class
 *
 * This class handles the taxonomy creation.
 *
 * @since 1.0.0
 */
class Cooked_Taxonomies {

	public static function get(){

		global $query_var;

		$_cooked_settings = Cooked_Settings::get();

		$front_page_id = get_option( 'page_on_front' );
		if ( $_cooked_settings['browse_page'] == $front_page_id ):
			$query_var = false;
		else:
			$query_var = true;
		endif;

		$taxonomy_permalinks = apply_filters( 'cooked_taxonomy_settings', array(
			'cp_recipe_category' => ( isset($_cooked_settings['recipe_category_permalink']) && $_cooked_settings['recipe_category_permalink'] ? $_cooked_settings['recipe_category_permalink'] : 'recipe-category' )
		));

		$taxonomies = apply_filters( 'cooked_taxonomies', array(

			'cp_recipe_category' => array(
				'hierarchical'        => true,
				'labels'              => array(
					'name'                => esc_html__('Categories', 'cooked'),
					'singular_name'       => esc_html__('Category', 'cooked'),
					'search_items'        => esc_html__('Search Categories', 'cooked'),
					'all_items'           => esc_html__('All Categories', 'cooked'),
					'parent_item'         => esc_html__('Parent Category', 'cooked'),
					'parent_item_colon'   => esc_html__('Parent Category:', 'cooked'),
					'edit_item'           => esc_html__('Edit Category', 'cooked'),
					'update_item'         => esc_html__('Update Category', 'cooked'),
					'add_new_item'        => esc_html__('Add New Category', 'cooked'),
					'new_item_name'       => esc_html__('New Category Name', 'cooked'),
					'menu_name'           => esc_html__('Categories', 'cooked'),
					'not_found'           => esc_html__('No Categories', 'cooked')
				),
				'show_ui'             => true,
				'show_admin_column'   => true,
				'show_in_menu'		  => false,
				'rest_base'             => 'recipe_category',
    			'rest_controller_class' => 'WP_REST_Terms_Controller',
				'query_var'           => $query_var,
				'rewrite'             => array( 'slug' => $taxonomy_permalinks['cp_recipe_category'] )
			)

		), $taxonomy_permalinks, $query_var );

		if ( !in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies'] ) ): unset( $taxonomies['cp_recipe_category'] ); endif;

		return $taxonomies;

	}

	public static function single_taxonomy_block( $term_id = false, $style = "block", $taxonomy = "cp_recipe_category" ){

		if ( !$term_id )
			return;

		$term = get_term( $term_id );
		if ( !empty($term) ):

			if ( $style == "block" ):
				echo '<div class="cooked-term-block cooked-col-25">';
					echo do_shortcode( '[cooked-recipe-card style="modern-centered" category="' . esc_attr( $term_id ) . '"]');
				echo '</div>';
			elseif( $style == "list" ):
				echo '<div class="cooked-term-item">';
					$term_name = apply_filters( 'cooked_term_name', $term->name, $term->term_id, $taxonomy );
					echo '<a href="' . esc_url( get_term_link( $term->term_id, $taxonomy ) ) . '">' . wp_kses_post( $term_name ) . '</a>';
				echo '</div>';
			endif;

		endif;

	}

	public static function card( $term_id = false, $width = false, $hide_image = false, $hide_total = false, $style = false ){

		global $_cooked_settings;

		if ( !$term_id )
			return false;

		$term = get_term( $term_id );
		if ( !empty($term) ):

			$args = array(
				'post_type' => 'cp_recipe',
				'posts_per_page' => -1,
				'tax_query' => array(
					array(
						'taxonomy' => $term->taxonomy,
						'field' => 'term_id',
						'terms' => array( $term_id )
					)
				)
			);

			$recipes = Cooked_Recipes::get( $args );
			$recent_recipe = $recipes[0];
			$total_recipes = count( $recipes ) - 1; // Total items in array minus the single ['raw'] item.
			$thumbnail = get_the_post_thumbnail_url( $recent_recipe['id'], array( 450,450 ) );
			$term_link = ( !empty($term) ? get_term_link( $term ) : false );
			$style_class = ( $style ? ' cooked-recipe-card-' . esc_attr($style) : '' );
			$width = ( !$width ? '100%' : $width );
			$pixel_width = stristr( $width, 'px', true );
			$percent_width = stristr( $width, '%', true );
			$width = ( $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) ) );
			$term_name = apply_filters( 'cooked_term_name', $term->name, $term->ID, $term->taxonomy );

			ob_start();

			echo '<a href="' . esc_url( $term_link ) . '" class="cooked-recipe-taxonomy-card cooked-recipe-card' . esc_attr( $style_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

				echo ( $thumbnail && !$hide_image ? '<span class="cooked-recipe-card-image" style="background-image:url(' . esc_url($thumbnail) . ');"></span>' : '' );

				echo '<span class="cooked-recipe-card-content">';

					echo '<span class="cooked-recipe-card-title">' . wp_kses_post($term_name) . '</span>';

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

}
