<?php
/**
 * Register Settings
 *
 * @package     Cooked
 * @subpackage  Settings
 * @since       1.0.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Settings Class
 *
 * This class handles the settings creation and contains functions for retreiving those settings.
 *
 * @since 1.0.0
 */
class Cooked_Settings {

	public function __construct(){
		add_filter( 'admin_init', array( &$this, 'init' ) );
		add_filter( 'init', array( &$this, 'init' ) );
		add_action( 'save_post', array(&$this, 'browse_page_saved'), 10, 1 );
	}

	public function browse_page_saved( $post_id ) {

		// Just a revision, don't do anything
		if ( wp_is_post_revision( $post_id ) )
			return;

		$_cooked_settings = Cooked_Settings::get();
		if ( isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] == $post_id ):
			flush_rewrite_rules(false);
		endif;

	}

	public static function init() {

		global $_cooked_settings,$list_id_counter;
		$list_id_counter = 0;
		$_cooked_settings = Cooked_Settings::get();
		register_setting( 'cooked_settings_group','cooked_settings' );
		register_setting( 'cooked_settings_group','cooked_settings_saved' );

	}

	public static function reset() {
		global $_cooked_settings;
		$_cooked_settings = Cooked_Settings::get();
	}

	public static function get() {

		$update_settings = false;
		$_cooked_settings = get_option( 'cooked_settings' );
		$cooked_settings_saved = get_option( 'cooked_settings_saved', false );
		$_cooked_settings_version = get_option( 'cooked_settings_version', '1.0.0' );
		$_og_cooked_settings = $_cooked_settings;

		$version_compare = version_compare( $_cooked_settings_version, COOKED_VERSION );

		// Get defaults for fields that are not set yet.
		$cooked_tabs_fields = self::tabs_fields();
		if ( isset($cooked_tabs_fields) && !empty($cooked_tabs_fields) ):
			foreach( $cooked_tabs_fields as $tab ):
				if ( isset($tab['fields']) && !empty($tab['fields']) ):
					foreach( $tab['fields'] as $name => $field ):

						if ( $field['type'] == 'nonce' || $field['type'] == 'misc_button' )
							continue;

						if ( $field['type'] == 'checkboxes' && $cooked_settings_saved && $version_compare >= 0 ):
							$_cooked_settings[$name] = ( isset($_cooked_settings[$name]) ? $_cooked_settings[$name] : array() );
						else:
							$_cooked_settings[$name] = ( isset($_cooked_settings[$name]) ? $_cooked_settings[$name] : ( isset( $field['default'] ) ? $field['default'] : false ) );
							$update_settings = true;
						endif;

					endforeach;
				endif;
			endforeach;
		endif;

		if ( $update_settings ): update_option( 'cooked_settings', $_cooked_settings ); endif;
		if ( $version_compare < 0 ):
			update_option( 'cooked_settings_version', COOKED_VERSION );
		endif;

		return apply_filters( 'cooked_get_settings', $_cooked_settings );

	}

	public static function tabs_fields() {

		$pages_array = self::pages_array( esc_html__('Choose a page...','cooked'), esc_html__('No pages','cooked') );
		$categories_array = self::terms_array( 'cp_recipe_category', esc_html__('No default','cooked'), esc_html__('No categories','cooked') );
		$recipes_per_page_array = self::per_page_array();

		return apply_filters('cooked_settings_tabs_fields', array(

		'recipe_settings' => array(
			'name' => esc_html__('General','cooked'),
			'icon' => 'gear',
			'fields' => array(
				'browse_page' => array(
					'title' => esc_html__('Browse/Search Recipes Page', 'cooked'),
					'desc' => sprintf( esc_html__('Create a page with the %s shortcode on it, then choose it from this dropdown.','cooked'), '[cooked-browse]' ),
					'type' => 'select',
					'default' => 0,
					'options' => $pages_array
				),
				'recipes_per_page' => array(
					'title' => esc_html__('Recipes Per Page', 'cooked'),
					'desc' => sprintf( esc_html__('Choose the default (set via the %s panel) or choose a different number here.','cooked'), '<a href="' . trailingslashit( get_admin_url() ) . 'options-reading.php">' . esc_html__( 'Settings > Reading', 'cooked' ) . '</a>' ),
					'type' => 'select',
					'default' => 9,
					'options' => $recipes_per_page_array
				),
				'recipe_taxonomies' => array(
					'title' => esc_html__('Recipe Taxonomies', 'cooked'),
					'desc' => esc_html__('Choose which taxonomies you want to enable for your recipes.','cooked'),
					'type' => 'checkboxes',
					'default' => array( 'cp_recipe_category' ),
					'options' => apply_filters( 'cooked_taxonomy_options', array(
						'cp_recipe_category' => esc_html__('Categories','cooked')
					))
				),
				'recipe_info_display_options' => array(
					'title' => esc_html__('Global Recipe Toggles', 'cooked'),
					'desc' => esc_html__('You can quickly hide or show different recipe elements (site-wide) with these checkboxes.','cooked'),
					'type' => 'checkboxes',
					'default' => apply_filters( 'cooked_recipe_info_display_options_defaults', array( 'author','taxonomies','difficulty_level','excerpt','timing_prep','timing_cook','timing_total','servings' ) ),
					'options' => apply_filters( 'cooked_recipe_info_display_options', array(
						'author' => esc_html__('Author','cooked'),
						'taxonomies' => esc_html__('Category','cooked'),
						'difficulty_level' => esc_html__('Difficulty Level','cooked'),
						'excerpt' => esc_html__('Excerpt','cooked'),
						'timing_prep' => esc_html__('Prep Time','cooked'),
						'timing_cook' => esc_html__('Cook Time','cooked'),
						'timing_total' => esc_html__('Total Time','cooked'),
						'servings' => esc_html__('Servings','cooked')
					))
				),
				'carb_format' => array(
					'title' => esc_html__('Carbs Format', 'cooked'),
					'desc' => esc_html__('You can display carbs as "Total" or "Net".','cooked'),
					'type' => 'select',
					'default' => 'total',
					'options' => apply_filters( 'cooked_settings_carb_formats', array(
						'total' => esc_html__('Total Carbs','cooked'),
						'net' => esc_html__('Net Carbs','cooked')
					))
				),
				'author_name_format' => array(
					'title' => esc_html__('Author Name Format', 'cooked'),
					'desc' => esc_html__('You can show the full author\'s name or just a part of it.','cooked'),
					'type' => 'select',
					'default' => 'full',
					'options' => apply_filters( 'cooked_settings_author_formats', array(
						'full' => esc_html__('Full name','cooked'),
						'first_last_initial' => esc_html__('Full first name w/last name initial','cooked'),
						'first_initial_last' => esc_html__('First name initial w/full last name','cooked'),
						'first_only' => esc_html__('First name only','cooked')
					))
				),
				'disable_author_links' => array(
					'title' => esc_html__('Author Links', 'cooked'),
					'desc' => esc_html__( 'If you do not want the author names to link to the author recipe listings, you can disable them here.', 'cooked' ),
					'type' => 'checkboxes',
					'color' => 'red',
					'default' => array(),
					'options' => apply_filters( 'cooked_author_link_options', array(
						'disabled' => esc_html__('Disable Author Links','cooked'),
					))
				),
				'browse_default_cp_recipe_category' => array(
					'title' => esc_html__('Default Category', 'cooked'),
					'desc' => sprintf( esc_html__('Optionally set the default recipe category for your %s shortcode display.','cooked'), '[cooked-browse]' ),
					'type' => 'select',
					'default' => 0,
					'options' => $categories_array
				),
				'browse_default_sort' => array(
					'title' => esc_html__('Default Sort Order', 'cooked'),
					'desc' => sprintf( esc_html__('Set the default sort order for your %s shortcode display.','cooked'), '[cooked-browse]' ),
					'type' => 'select',
					'default' => 'date_desc',
					'options' => apply_filters( 'cooked_settings_sort_options', array(
						'date_desc' => esc_html__('Newest First','cooked'),
						'date_asc' => esc_html__('Oldest First','cooked'),
						'title_asc' => esc_html__('Alphabetical','cooked'),
						'title_desc' => esc_html__('Alphabetical (reversed)','cooked'),
					))
				),
				'advanced' => array(
					'title' => esc_html__('Advanced Settings', 'cooked'),
					'desc' => '',
					'type' => 'checkboxes',
					'color' => 'red',
					'class' => 'cooked-danger',
					'default' => array(),
					'options' => apply_filters( 'cooked_advanced_options', array(
						'disable_public_recipes' => '<strong>' . esc_html__('Disable Public Recipes','cooked') . '</strong> &mdash; ' . sprintf( esc_html__('Only show recipes using the %s shortcode.','cooked'), '<code>[cooked-recipe]</code>' ),
						'disable_meta_tags' => '<strong>' . sprintf( esc_html__('Disable %s Tags','cooked'), 'Cooked <code>&lt;meta&gt;</code>' ) . '</strong> &mdash; ' . esc_html__('Prevents duplicates when tags already exist.','cooked'),
						'disable_servings_switcher' => '<strong>' . esc_html__('Disable "Servings Switcher"','cooked') . '</strong> &mdash; ' . esc_html__( 'Removes the servings dropdown on recipes.', 'cooked' ),
						'disable_schema_output' => '<strong>' . esc_html__('Disable Recipe Schema Output','cooked') . '</strong> &mdash; ' . esc_html__( 'You should only do this if you\'re using something else to output schema information.', 'cooked' )
					))
				),
			)
		),
		'design' => array(
			'name' => esc_html__('Design','cooked'),
			'icon' => 'pencil',
			'fields' => array(
				'dark_mode' => array(
					'title' => esc_html__('Dark Mode', 'cooked'),
					'desc' => esc_html__( 'If your site has a dark background, you should enable "Dark Mode" so that Cooked can match this style.', 'cooked' ),
					'type' => 'checkboxes',
					'default' => array(),
					'options' => apply_filters( 'cooked_dark_mode_options', array(
						'enabled' => esc_html__('Enable "Dark Mode"','cooked'),
					))
				),
				'hide_author_avatars' => array(
					'title' => esc_html__('Author Images', 'cooked'),
					'desc' => esc_html__( 'If you do not want to display the author images (avatars), you can disable them here.', 'cooked' ),
					'type' => 'checkboxes',
					'color' => 'red',
					'default' => array(),
					'options' => apply_filters( 'cooked_author_image_options', array(
						'hidden' => esc_html__('Hide Author Images','cooked'),
					))
				),
				'main_color' => array(
					'title' => esc_html__('Main Color', 'cooked'),
					'desc' => esc_html__( 'Used on buttons, cooking timer, etc.', 'cooked' ),
					'type' => 'color_field',
					'default' => '#16a780',
					'options' => '#16a780'
				),
				'main_color_hover' => array(
					'title' => esc_html__('Main Color (on hover)', 'cooked'),
					'desc' => esc_html__( 'Used when hovering over buttons.', 'cooked' ),
					'type' => 'color_field',
					'default' => '#1b9371',
					'options' => '#1b9371'
				),
				'responsive_breakpoint_1' => array(
					'title' => esc_html__( 'First Responsive Breakpoint', 'cooked' ),
					'desc' => esc_html__( 'Set the first responsive breakpoint. Best for large tablets.', 'cooked' ),
					'type' => 'number_field',
					'default' => '1000',
					'options' => ''
				),
				'responsive_breakpoint_2' => array(
					'title' => esc_html__('Second Responsive Breakpoint', 'cooked'),
					'desc' => esc_html__( 'Set the second responsive breakpoint. Best for small tablets.', 'cooked' ),
					'type' => 'number_field',
					'default' => '750',
					'options' => ''
				),
				'responsive_breakpoint_3' => array(
					'title' => esc_html__('Third Responsive Breakpoint', 'cooked'),
					'desc' => esc_html__( 'Set the third responsive breakpoint. Best for phones and other small devices.', 'cooked' ),
					'type' => 'number_field',
					'default' => '520',
					'options' => ''
				)
			)
		),
		'permalinks' => array(
			'name' => esc_html__('Permalinks','cooked'),
			'icon' => 'link-lt',
			'fields' => array(
				'recipe_permalink' => array(
					'title' => esc_html__('Recipe Permalink', 'cooked'),
					'desc' => '',
					'type' => 'permalink_field',
					'options' => esc_html__( 'recipe-name', 'cooked' ),
					'default' => 'recipes'
				),
				'recipe_author_permalink' => array(
					'title' => esc_html__('Recipe Author Permalink', 'cooked'),
					'desc' => '',
					'type' => 'permalink_field',
					'options' => esc_html__( 'author-name', 'cooked' ),
					'default' => 'recipe-author'
				),
				'recipe_category_permalink' => array(
					'title' => esc_html__('Recipe Category Permalink', 'cooked'),
					'desc' => '',
					'type' => 'permalink_field',
					'options' => esc_html__( 'recipe-category-name', 'cooked' ),
					'default' => 'recipe-category'
				)
			)
		)

		), $pages_array, $categories_array );

	}

	public static function per_page_array(){

		$counter = 0;
		$per_page_array[] = sprintf( esc_html__('WordPress Default %s','cooked'), '(' . get_option( 'posts_per_page' ) . ')' );
		do {
			$counter++;
			$per_page_array[$counter] = $counter;
		} while ( $counter < 50 );
		$per_page_array['-1'] = esc_html__('Show All (no pagination)','cooked');

		return apply_filters( 'cooked_per_page_options', $per_page_array );

	}

	public static function pages_array( $choose_text,$none_text = false ){

		$page_array = array();
		$pages = get_posts( array( 'post_type' => 'page', 'posts_per_page' => -1 ) );

		if( !empty($pages) ) :
			$page_array[0] = $choose_text;
			foreach($pages as $_page) :
				$page_array[$_page->ID] = $_page->post_title;
			endforeach;
		elseif( $none_text ):
			$page_array[0] = $none_text;
		endif;

		return apply_filters( 'cooked_settings_pages_array', $page_array );

	}

	public static function terms_array( $term, $choose_text, $none_text = false, $hide_empty = false, $parents_only = false, $child_of = false ){

		$terms_array = array();

		$args = array(
			'hide_empty' => $hide_empty
		);

		if ( $parents_only ):
			$args['parent'] = '0';
		elseif ( $child_of ):
			$_term = ( is_numeric($child_of) ? $child_of : get_term_by( 'slug', $child_of, $term ) );
			$term_id = ( is_object( $_term ) ? $_term->term_id : $_term );
			$args['parent'] = $term_id;
		endif;

		$terms = get_terms( $term, $args );

		if( !empty($terms) ) :
			if ($choose_text): $terms_array[0] = $choose_text; endif;
			foreach($terms as $_term) :
				if ( !is_array($_term) ):
					$terms_array[$_term->term_id] = $_term->name;
				endif;
			endforeach;
		elseif( $none_text ):
			$terms_array[0] = $none_text;
		endif;

		return apply_filters( 'cooked_settings_'.$term.'_array', $terms_array );

	}

	public static function field_radio( $field_name, $options ){
		global $_cooked_settings,$conditions;
		$counter = 1;
		echo '<p class="cooked-padded">';

			foreach( $options as $value => $name) :

				$is_disabled = '';
				$conditional_value = '';
				$conditional_requirement = '';

				if ( is_array($name) ):
					if ( isset($name['read_only']) && $name['read_only'] ):
						$is_disabled = ' disabled';
					endif;
					if ( isset($name['conditional_value']) && $name['conditional_value'] ):
						$conditional_value = ' v-model="' . esc_attr($name['conditional_value']) . '"';
						if ( !in_array( $name['conditional_value'], $conditions ) ):
							$conditions[$value] = esc_attr($name['conditional_requirement']);
						endif;
					endif;
					if ( isset($name['conditional_requirement']) && $name['conditional_requirement'] ):
						if ( is_array($name['conditional_requirement']) ):
							$conditional_requirement = ' v-show="' . implode( ' && ', $name['conditional_requirement'] ) . '"';
						else:
							$conditional_requirement = ' v-show="' . esc_attr($name['conditional_requirement']) . '"';
						endif;
					endif;
					$name = $name['label'];
				endif;

				$combined_extras = $is_disabled . $conditional_value;

				if ( $conditional_requirement ): echo '<transition name="fade"><span class="conditional-requirement"' . esc_attr( $conditional_requirement ) . '>'; endif;
				echo '<input' . $combined_extras . ' type="radio" id="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] == $value || isset( $_cooked_settings[$field_name][0] ) && $_cooked_settings[$field_name][0] == $value ? ' checked' : '' ) . '/>';
				echo '&nbsp;<label for="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' . wp_kses_post( $name ) . '</label>';
				echo '<br>';
				if ( $conditional_requirement ): echo '</span></transition>'; endif;

				$counter++;

			endforeach;
		echo '</p>';
	}

	public static function field_select( $field_name, $options ){
		global $_cooked_settings;
		echo '<p>';
			echo '<select name="cooked_settings[' . esc_attr( $field_name ) . ']">';
			foreach( $options as $value => $name) :
				echo '<option value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] == $value ? ' selected' : '' ) . '>' . esc_attr( $name ) . '</option>';
			endforeach;
			echo '</select>';
		echo '</p>';
	}

	public static function field_nonce( $field_name, $options ){
		wp_nonce_field( $field_name, $field_name );
	}

	// Kept here for backwards compatibility only. Removed used in Cooked Pro 1.0.1
	public static function field_misc_button( $field_name, $title ){
		global $_cooked_settings;
		echo '<p>';
			echo '<input type="submit" class="button-secondary" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $title ) . '">';
		echo '</p>';
	}
	// END

	public static function field_migrate_button( $field_name, $title ){
		global $_cooked_settings;
		$old_recipes = get_transient( 'cooked_classic_recipes' );
		if ( $old_recipes != 'complete' ):
			$total = count($old_recipes);
			if ( $total > 0 ):
				echo '<p>';
					echo '<input id="cooked-migration-button" type="button" class="button-secondary" name="begin_cooked_migration" value="' . esc_attr__( 'Begin Migration', 'cooked' ) . '">';
				echo '</p>';
				echo '<p>';
					echo '<span id="cooked-migration-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-migration-progress-text" class="cooked-progress-text">0 / ' . esc_html( $total ) . '</span>';
				echo '</p>';
				echo '<p id="cooked-migration-completed"><strong>Migration Complete!</strong> You can now <a href="' . esc_url( add_query_arg( array( 'page' => 'cooked_settings' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'reload', 'cooked' ) . '</a> the settings screen.</p>';
			endif;
		endif;
	}

	public static function field_text( $field_name, $placeholder ){
		global $_cooked_settings;
		echo '<p>';
			echo '<input id="cooked_field--' . esc_attr( $field_name ) . '" type="text"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
		echo '</p>';
	}

	public static function field_password( $field_name, $placeholder ){
		global $_cooked_settings;
		echo '<p>';
			echo '<input type="password"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
		echo '</p>';
	}

	public static function field_html( $field_name, $html ){
		global $_cooked_settings;
		echo wp_kses_post( $html );
	}

	public static function field_permalink_field( $field_name, $end_of_url ){
		global $_cooked_settings;
		echo '<p class="cooked-permalink-field-wrapper">';
			echo '<span>' . get_home_url() . '/</span><input type="text" class="cooked-permalink-field" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '"><span>/' . esc_html( $end_of_url ) . '/</span>';
		echo '</p>';
	}

	public static function field_number_field( $field_name, $options ){
		global $_cooked_settings;
		echo '<p>';
			echo '<input type="number" step="any" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
		echo '</p>';
	}

	public static function field_color_field( $field_name, $default ){
		global $_cooked_settings;
		echo '<p>';
			echo '<input class="cooked-color-field" type="text"' . ( $default ? ' data-default-color="' . esc_attr( $default ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
		echo '</p>';
	}

	public static function field_checkboxes( $field_name, $options, $color = false ){
		global $_cooked_settings,$conditions;
		echo '<p class="cooked-padded">';
			foreach( $options as $value => $name) :

				$is_disabled = '';
				$conditional_value = '';
				$conditional_requirement = '';

				if ( is_array($name) ):
					if ( isset($name['read_only']) && $name['read_only'] ):
						$is_disabled = ' disabled';
					endif;
					if ( isset($name['conditional_value']) && $name['conditional_value'] ):
						$conditional_value = ' v-model="' . esc_attr($name['conditional_value']) . '"';
						if ( !in_array( $name['conditional_value'], $conditions ) ):
							$conditions[$field_name][$name['conditional_value']] = $value;
						endif;
					endif;
					if ( isset($name['conditional_requirement']) && $name['conditional_requirement'] ):
						if ( is_array($name['conditional_requirement']) ):
							$conditional_requirement = ' v-show="' . implode( ' && ', $name['conditional_requirement'] ) . '"';
						else:
							$conditional_requirement = ' v-show="' . esc_attr($name['conditional_requirement']) . '"';
						endif;
					endif;
					$name = $name['label'];
				endif;

				$combined_extras = $is_disabled . $conditional_value;

				if ( $conditional_requirement ): echo '<transition name="fade"><span class="conditional-requirement"' . esc_attr( $conditional_requirement ) . '>'; endif;
				if ( $is_disabled ):
					echo '<input type="hidden" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) . '">';
					echo '<input' . $combined_extras . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) . '" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && !empty($_cooked_settings[$field_name]) && in_array( $value, $_cooked_settings[$field_name] ) || $is_disabled ? ' checked' : '' ) . '/>';
				else:
					echo '<input' . $combined_extras . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) . '" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && !empty($_cooked_settings[$field_name]) && is_array( $_cooked_settings[$field_name] ) && in_array( $value, $_cooked_settings[$field_name] ) || $is_disabled ? ' checked' : '' ) . '/>';
				endif;
				echo '&nbsp;<label for="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' . wp_kses_post( $name ) . '</label>';
				echo '<br>';
				if ( $conditional_requirement ): echo '</span></transition>'; endif;

			endforeach;
		echo '</p>';
	}

}
