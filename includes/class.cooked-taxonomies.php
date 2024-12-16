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

	public static function get() {
		global $query_var;

		$_cooked_settings = Cooked_Settings::get();

		$front_page_id = get_option( 'page_on_front' );
		$query_var = ($_cooked_settings['browse_page'] == $front_page_id) ? false : true;

		$taxonomy_permalinks = apply_filters( 'cooked_taxonomy_settings', [
			'cp_recipe_category' => (isset($_cooked_settings['recipe_category_permalink']) && $_cooked_settings['recipe_category_permalink'] ? $_cooked_settings['recipe_category_permalink'] : 'recipe-category')
		]);

		$taxonomies = apply_filters( 'cooked_taxonomies', [
			'cp_recipe_category' => [
				'hierarchical' => true,
				'labels' => [
					'name' => __('Categories', 'cooked'),
					'singular_name' => __('Category', 'cooked'),
					'search_items' => __('Search Categories', 'cooked'),
					'all_items' => __('All Categories', 'cooked'),
					'parent_item' => __('Parent Category', 'cooked'),
					'parent_item_colon' => __('Parent Category:', 'cooked'),
					'edit_item' => __('Edit Category', 'cooked'),
					'update_item' => __('Update Category', 'cooked'),
					'add_new_item' => __('Add New Category', 'cooked'),
					'new_item_name' => __('New Category Name', 'cooked'),
					'menu_name' => __('Categories', 'cooked'),
					'not_found' => __('No Categories', 'cooked')
				],
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_menu' => false,
				'show_in_rest' => true,
				'rest_base' => 'recipe_category',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
				'query_var' => $query_var,
				'rewrite' => ['slug' => $taxonomy_permalinks['cp_recipe_category']]
			]

		], $taxonomy_permalinks, $query_var );

		if ( !in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies'] ) ): unset( $taxonomies['cp_recipe_category'] ); endif;

		// Filters
		add_filter( 'term_link', ['Cooked_Taxonomies', 'term_link_filter'], 10, 3);

		return $taxonomies;
	}

	public static function single_taxonomy_block( $term_id = false, $style = "block", $taxonomy = "cp_recipe_category" ) {
		if ( !$term_id ) return;

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

	public static function card( $term_id = false, $width = false, $hide_image = false, $hide_total = false, $style = false ) {
		if ( !$term_id ) return false;

		$term = get_term( $term_id );
		if ( !empty($term) ):

			$args = [
				'post_type' => 'cp_recipe',
				'posts_per_page' => -1,
				'tax_query' => [
					[
						'taxonomy' => $term->taxonomy,
						'field' => 'term_id',
						'terms' => [$term_id]
					]
				]
			];

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

	public static function term_link_filter( $url, $term, $taxonomy ) {
		$_cooked_settings = Cooked_Settings::get();

		$parent_page_browse_page = isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] ? $_cooked_settings['browse_page'] : false;
		$front_page = get_option( 'page_on_front' );
		$cooked_taxonomies = ['cp_recipe_category'];

		if ( $parent_page_browse_page && in_array($taxonomy, $cooked_taxonomies) ) {
			if ( $taxonomy === 'cp_recipe_category' ) {
				$custom_cooked_tax_setting = 'recipe_category_permalink';
			}

			// $browse_page_id = $_cooked_settings['browse_page'];
			// $browse_page_link = get_permalink($browse_page_id);
			// $url_test = $browse_page_link . '?taxonomy=' . $taxonomy . '&term=' . $term->slug;

			if ( $parent_page_browse_page != $front_page && get_option('permalink_structure') ) {
				$url = esc_url_raw( untrailingslashit( get_permalink( $parent_page_browse_page ) ) . '/' . $_cooked_settings[$custom_cooked_tax_setting] . '/' . $term->slug );
			} elseif ( $parent_page_browse_page == $front_page ) {
				$url = esc_url_raw( get_home_url() . '?' . $taxonomy . '=' . ( isset( $term->slug ) ? $term->slug : $taxonomy ) );
			} else {
				$url = esc_url_raw( get_permalink( $parent_page_browse_page ) . '&' . $taxonomy . '=' . ( isset( $term->slug ) ? $term->slug : $taxonomy ) );
			}
		}

		return $url;
	}

}
