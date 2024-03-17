<?php
/**
 * Post Types
 *
 * @package     Cooked
 * @subpackage  Post Types
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Recipe_Meta Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Recipe_Meta {

	function __construct() {

		add_action( 'add_meta_boxes', array( &$this, 'add_recipe_meta_box' ) );
        add_action( 'save_post',      array( &$this, 'save_recipe_meta_box' ) );

	}

	public static function meta_cleanup( $recipe_settings ){
		
		$_recipe_settings = [];

		if (!empty($recipe_settings)):
        	foreach($recipe_settings as $key => $val):
        		if (!is_array($val)):

        			if ( $key === "content" || $key === "excerpt" ):
        				$_recipe_settings[$key] = wp_kses_post( $val );
        			else:
        				$_recipe_settings[$key] = Cooked_Functions::sanitize_text_field( $val );
        			endif;

        		else:
        			foreach($val as $subkey => $subval):
        				if (!is_array($subval)):
		        			$_recipe_settings[$key][$subkey] = Cooked_Functions::sanitize_text_field($subval);
		        		else:
		        			foreach( $subval as $sub_subkey => $sub_subval ):
		        				if ( !is_array($sub_subval) ):
		        					if ( $sub_subkey == 'content' || $key == 'ingredients' && $sub_subkey == 'name' || $key == 'ingredients' && $sub_subkey == 'section_heading_name' || $key == 'directions' && $sub_subkey == 'section_heading_name' ):
		        						$_recipe_settings[$key][$subkey][$sub_subkey] = wp_kses_post( $sub_subval );
		        					else:
				        				$_recipe_settings[$key][$subkey][$sub_subkey] = Cooked_Functions::sanitize_text_field( $sub_subval );
				        			endif;
				        		else:
				        			foreach($sub_subval as $sub_sub_subkey => $sub_sub_subval):
				        				if (!is_array($sub_sub_subval)):
						        			$_recipe_settings[$key][$subkey][$sub_subkey][$sub_sub_subkey] = Cooked_Functions::sanitize_text_field($sub_sub_subval);
						        		else:
						        			foreach($sub_sub_subval as $sub_sub_sub_subkey => $sub_sub_sub_subval):
						        				$_recipe_settings[$key][$subkey][$sub_subkey][$sub_sub_subkey][$sub_sub_sub_subkey] = Cooked_Functions::sanitize_text_field($sub_sub_sub_subval);
						        			endforeach;
						        		endif;
				        			endforeach;
				        		endif;
		        			endforeach;
		        		endif;
        			endforeach;
        		endif;
        	endforeach;
        endif;

        return $_recipe_settings;

	}

	/**
     * Adds the meta box container.
     */
	public function add_recipe_meta_box( $post_type ) {

		// Limit meta box to Cooked Recipes.
        $post_types = apply_filters( 'cp_recipe_metabox_post_types' , array( 'cp_recipe' ) );

        if ( in_array( $post_type, $post_types ) ) {
            add_meta_box( 'cooked_recipe_settings', esc_html__( 'Settings', 'cooked' ), array( &$this, 'render_recipe_meta_box' ), $post_type, 'normal', 'high' );
        }

	}

	/**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_recipe_meta_box( $post_id ) {

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( !isset( $_POST['cooked_recipe_custom_box_nonce'] ) )
            return $post_id;

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['cooked_recipe_custom_box_nonce'], 'cooked_recipe_custom_box' ) )
            return $post_id;

        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_cooked_recipes', $post_id ) )
           	return $post_id;

        global $recipe_settings;

        /* OK, it's safe for us to validate/sanitize the data now. */
        $recipe_settings = isset($_POST['_recipe_settings']) ? self::meta_cleanup( $_POST['_recipe_settings'] ) : false;

        // Update the recipe settings meta field.
        update_post_meta( $post_id, '_recipe_settings', $recipe_settings );
        $recipe_excerpt = ( isset($recipe_settings['excerpt']) && $recipe_settings['excerpt'] ? $recipe_settings['excerpt'] : get_the_title( $post_id ) );

        $seo_content = apply_filters( 'cooked_seo_recipe_content', '<h2>' . wp_kses_post( $recipe_excerpt ) . '</h2><h3>' . __('Ingredients','cooked') . '</h3>[cooked-ingredients checkboxes=false]<h3>' . __('Directions','cooked') . '</h3>[cooked-directions numbers=false]' );
        $seo_content = do_shortcode( $seo_content );

    	// Unhook this function so it doesn't loop infinitely
		remove_action( 'save_post', array( &$this, 'save_recipe_meta_box' ) );

        // Update the post, which calls save_post again
        $should_update_content = apply_filters( 'cooked_should_update_post_content', true, $post_id );
        if ( $should_update_content ):
            wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => $recipe_excerpt, 'post_content' => $seo_content ) );
        else:
            wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => $recipe_excerpt ) );
        endif;

		// Re-hook this function
		add_action( 'save_post', array( &$this, 'save_recipe_meta_box' ) );

    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_recipe_meta_box( ) {

	    global $post;

	    /*
		 * Output the recipe meta fields
		 * @since 1.0.0
		 */
		do_action( 'cooked_recipe_fields', $post->ID );

        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'cooked_recipe_custom_box', 'cooked_recipe_custom_box_nonce' );

    }

}

function cooked_recipe_shortcodes_content(){

	global $post_id;

	?><div class="recipe-setting-block">

		<hr class="cooked-hr">

		<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Display Recipe', 'cooked' ); ?></h3>
		<p class="cooked-bm-10"><?php esc_html_e( 'This shortcode displays the recipe in its entirety, using the "Recipe Template" field in the first tab.', 'cooked' ); ?></p>
		<div class="cooked-bm-20 cooked-block">
			<input class='cooked-shortcode-field' type='text' readonly value='[cooked-recipe id="<?php echo intval($post_id); ?>"]' />
		</div>

	</div><?php

}

/**
 * Recipe Fields
 *
 * @since 1.0.0
 * @param $post_id
 */
function cooked_render_recipe_fields( $post_id ) {

	global $_cooked_settings;
	$recipe_settings = get_post_meta( $post_id, '_recipe_settings', true);

	// Backwards Compatibility with Cooked 2.x
	$c2_recipe_settings = Cooked_Recipes::get_c2_recipe_meta( $post_id );
	$recipe_review_required = false;

	// Show the Shortcodes tab if recipe is saved.
	if ( !empty($recipe_settings) ):
		add_action('cooked_recipe_shortcodes_after','cooked_recipe_shortcodes_content',10);
	endif;

	if( !isset($recipe_settings['cooked_version']) && !empty($c2_recipe_settings) ):
		$recipe_review_required = true;
		$recipe_settings = Cooked_Recipes::sync_c2_recipe_settings($c2_recipe_settings,$post_id);
	endif;

	$recipe_tabs = apply_filters( 'cooked_recipe_admin_tabs', array(
		'content' => array(
			'icon' => 'desktop',
			'name' => esc_html__('Layout','cooked'),
			'conditional' => false,
			'value' => false
		),
		'ingredients' => array(
			'icon' => 'list',
			'name' => esc_html__('Ingredients','cooked'),
			'conditional' => false,
			'value' => false
		),
		'directions' => array(
			'icon' => 'directions',
			'name' => esc_html__('Directions','cooked'),
			'conditional' => false,
			'value' => false
		),
		'nutrition' => array(
			'icon' => 'heart',
			'name' => esc_html__('Nutrition','cooked'),
			'conditional' => false,
			'value' => false
		),
		'gallery' => array(
			'icon' => 'image',
			'name' => esc_html__('Gallery','cooked'),
			'conditional' => false,
			'value' => false
		),
		'shortcodes' => array(
			'icon' => 'code',
			'name' => esc_html__('Shortcodes','cooked'),
			'conditional' => false,
			'value' => false
		),
	));

	$measurements = Cooked_Measurements::get();

	$cooked_page_args = array(
		'sort_order' => 'asc',
		'sort_column' => 'post_title',
		'hierarchical' => false,
		'post_type' => 'page',
		'post_status' => 'publish'
	);
	$cooked_page_array = get_pages($cooked_page_args);

	if (!empty($recipe_tabs)):

		echo '<ul id="cooked-recipe-tabs">';
		$first_tab = true;

		foreach($recipe_tabs as $slug => $tab):

			$classes = array();
			if ($first_tab): $classes[] = 'active'; endif;
			if ($tab['conditional']): $classes[] = 'cooked-conditional-hidden'; endif;

			echo "<li id='cooked-recipe-tab-" . esc_attr($slug) . "'" . (!empty($classes) ? " class='" . esc_attr(implode(" ",$classes)) . "'" : "") . ($tab['conditional'] ? " data-condition='". esc_attr($tab['conditional'])."'" : "") . ($tab['value'] ? " data-value='" . esc_attr($tab['value']) . "'" : ""). ">";
			echo ( $tab['icon'] ? "<i class='cooked-icon cooked-icon-fw cooked-icon-" . esc_attr($tab['icon']) . "'></i>&nbsp;&nbsp;" : "" );
			echo esc_html($tab['name']);
			echo "</li>";
			$first_tab = false;

		endforeach;

		echo '</ul>';

	endif; ?>

	<div class="cooked-recipe-tab-content-wrapper">

		<?php do_action('cooked_recipe_tabs_before'); ?>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-content">

			<!-- Allows for backwards compatability features -->
			<input type="hidden" name="_recipe_settings[cooked_version]" value="<?php echo COOKED_VERSION; ?>">

			<?php if (isset($recipe_review_required) && $recipe_review_required): ?>
				<section class="cooked-alert-block" id="cooked-recipe-tab-content-migration">
					<div class="recipe-setting-block">
						<h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Review Required', 'cooked' ); ?></h3>
						<p><?php echo sprintf( esc_html__( "It looks like this recipe is from a different version of %s. Please review and click \"Update\" to save it.","cooked"), "Cooked" ); ?></p>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( isset($recipe_settings) && !empty($recipe_settings) ): ?>
				<div class="recipe-setting-block">
					<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Recipe Shortcode', 'cooked' ); ?></h3>
					<p class="cooked-bm-10"><?php esc_html_e( 'You can use the following shortcode to display your recipe anywhere:', 'cooked' ); ?></p>
					<div class="cooked-bm-30 cooked-block">
						<input style="width:100%;" class="cooked-shortcode-field" type="text" readonly="" value="[cooked-recipe id=&quot;<?php echo intval( $post_id ); ?>&quot;]">
					</div>
				</div>
			<?php endif; ?>

            <?php do_action( 'cooked_recipe_meta_before_fields', $recipe_settings ); ?>

			<div class="recipe-setting-block">

				<h3 class="cooked-settings-title cooked-bm-30-up"><?php esc_html_e( 'Recipe Template', 'cooked' ); ?><span title="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'Default Recipe Template','cooked') . '</strong>' . esc_html__( 'Choose from the options below to use this layout as the default for new recipes or for all recipes.', 'cooked') . '<span class="cooked-tooltip-buttons cooked-clearfix"><a href="#" class="cooked-save-default-new button">' . esc_html__( 'Save as Default','cooked' ) . '</a>&nbsp;&nbsp;<a href="#" class="cooked-save-default-all button-primary">' . esc_html__( 'Apply to All','cooked' ) . '</a></span><span id="cooked-template-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-template-progress-text" class="cooked-progress-text">0 / 0</span>' ); ?>" class="button cooked-layout-save-default"><?php esc_html_e( 'Save as Default', 'cooked' ); ?></span><span class="button button-cooked-reset cooked-layout-load-default"><?php esc_html_e( 'Reset', 'cooked' ); ?></span><span class="cooked-tooltip cooked-tooltip-icon" title="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'Recipe Template','cooked') . '</strong>' . esc_html__( 'Using the built-in recipe shortcodes found on the "Shortcodes" tab, you can create the layout of your recipe below. Use the "Save as Default" button to save your template.','cooked') ); ?>"><i class="cooked-icon cooked-icon-question"></i></span></h3>

				<div class="recipe-setting-block cooked-bm-30">
					<?php $recipe_content = ( isset($recipe_settings['content']) ? stripslashes( wp_specialchars_decode( $recipe_settings['content'] ) ) : ( isset( $_cooked_settings['default_content'] ) ? stripslashes( wp_specialchars_decode( $_cooked_settings['default_content'] ) ) : Cooked_Recipes::default_content() ) ); ?>
					<?php wp_editor( $recipe_content, '_recipe_settings_content', array( 'teeny' => false, 'media_buttons' => false, 'wpautop' => false, 'editor_height' => 400, 'textarea_name' => '_recipe_settings[content]', 'quicktags' => true ) ); ?>
				</div>

				<div class="recipe-setting-block">
					<h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Excerpt', 'cooked' ); ?><span class="cooked-tooltip cooked-tooltip-icon" title="<?php echo esc_attr( esc_html__( 'The excerpt is used on recipe listing templates, where the full recipe should not be displayed.','cooked') ); ?>"><i class="cooked-icon cooked-icon-question"></i></span></h3>
					<p><textarea name="_recipe_settings[excerpt]"><?php echo ( isset($recipe_settings['excerpt']) ? $recipe_settings['excerpt'] : '' ); ?></textarea></p>
				</div>

                <div class="recipe-setting-block">
                    <h3 class="cooked-settings-title"><?php esc_html_e( 'SEO Description', 'cooked' ); ?><span class="cooked-tooltip cooked-tooltip-icon" title="<?php echo esc_attr( esc_html__( 'This description is used for SEO purposes and is optional. By default, Cooked will use the Recipe Excerpt above if available or the Recipe Title if not.','cooked') ); ?>"><i class="cooked-icon cooked-icon-question"></i></span></h3>
                    <p><textarea name="_recipe_settings[seo_description]"><?php echo ( isset($recipe_settings['seo_description']) ? $recipe_settings['seo_description'] : '' ); ?></textarea></p>
                </div>

				<div class="recipe-setting-block">
					<div class="cooked-clearfix">

						<?php $difficulty_levels = Cooked_Recipes::difficulty_levels(); ?>

						<div class="cooked-setting-column-14">
							<h3 class="cooked-settings-title"><?php esc_html_e( 'Difficulty Level', 'cooked' ); ?></h3>
							<select name="_recipe_settings[difficulty_level]">
								<option value="0">--</option>
								<?php foreach($difficulty_levels as $level => $name):
									echo '<option value="' . esc_attr( $level ) . '"' . ( isset($recipe_settings['difficulty_level']) && $recipe_settings['difficulty_level'] == $level ? ' selected' : '' ) . '>' . esc_html( $name ) . '</option>';
								endforeach; ?>
							</select>
						</div>
						<div class="cooked-setting-column-14">
							<h3 class="cooked-settings-title"><?php esc_html_e( 'Prep Time', 'cooked' ); ?></h3>
							<input id="cooked-prep-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[prep_time]" value="<?php echo ( isset($recipe_settings['prep_time']) && $recipe_settings['prep_time'] ? $recipe_settings['prep_time'] : '' ); ?>" placeholder="--">
							<span class="cooked-time-picker-text"><?php esc_html_e('minutes','cooked'); ?></span>
						</div>
						<div class="cooked-setting-column-14">
							<h3 class="cooked-settings-title"><?php esc_html_e( 'Cook Time', 'cooked' ); ?></h3>
							<input id="cooked-cook-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[cook_time]" value="<?php echo ( isset($recipe_settings['cook_time']) && $recipe_settings['cook_time'] ? $recipe_settings['cook_time'] : '' ); ?>" placeholder="--">
							<span class="cooked-time-picker-text"><?php esc_html_e('minutes','cooked'); ?></span>
						</div>
                        <div class="cooked-setting-column-14">
                            <h3 class="cooked-settings-title"><?php esc_html_e( 'Total Time', 'cooked' ); ?></h3>
                            <input id="cooked-total-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[total_time]" value="<?php echo ( isset($recipe_settings['total_time']) && $recipe_settings['total_time'] ? $recipe_settings['total_time'] : '' ); ?>" placeholder="--">
                            <span class="cooked-time-picker-text"><?php esc_html_e('minutes','cooked'); ?></span>
                        </div>
					</div>
				</div>

			</div>

		</section>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-ingredients">

			<div class="cooked-ingredient-headers cooked-clearfix">
				<span class="cooked-ingredient-header-amount"><?php esc_html_e('Amount','cooked'); ?></span>
				<span class="cooked-ingredient-header-measurement"><?php esc_html_e('Measurement','cooked'); ?></span>
				<span class="cooked-ingredient-header-item"><?php esc_html_e('Item','cooked'); ?></span>
			</div>

			<div id="cooked-ingredients-builder" class="cooked-sortable">

				<?php if ( isset($recipe_settings['ingredients']) && !empty($recipe_settings['ingredients']) ): ?>

					<?php foreach($recipe_settings['ingredients'] as $ing_key => $value): ?>

						<?php if ( !isset($value['section_heading_name']) ): ?>

							<?php $ingredient_classes = apply_filters( 'cooked_ingredient_field_classes', 'recipe-setting-block cooked-ingredient-block cooked-clearfix', $value ); ?>

							<div class="<?php echo esc_attr( $ingredient_classes ); ?>">

								<i class="cooked-icon cooked-icon-drag"></i>

								<?php do_action( 'cooked_before_ingredient_fields', $ing_key, $value ); ?>

								<div class="cooked-ingredient-amount">
									<input type="text" data-ingredient-part="amount" name="_recipe_settings[ingredients][<?php echo esc_attr($ing_key); ?>][amount]" value="<?php echo esc_attr( $value['amount'] ); ?>" placeholder="--">
								</div>

								<?php do_action( 'cooked_after_ingredient_amount_field', $ing_key, $value ); ?>

								<div class="cooked-ingredient-measurement">
									<select data-ingredient-part="measurement" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][measurement]">
										<option value="">--</option>
										<?php foreach($measurements as $key => $measurement):
											echo '<option value="' . esc_attr( $key ) . '"' . ( $value['measurement'] == $key ? ' selected' : '' ) . '>' . esc_html($measurement['plural_abbr']) . '</option>';
										endforeach; ?>
									</select>
								</div>

								<?php do_action( 'cooked_after_ingredient_measurement_field', $ing_key, $value ); ?>

								<div class="cooked-ingredient-name">
									<input type="text" data-ingredient-part="name" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][name]" value="<?php echo esc_attr( $value['name'] ); ?>" placeholder="<?php esc_html_e('ex. Eggs, Milk, etc.','cooked'); ?> ...">
								</div>

								<?php do_action( 'cooked_after_ingredient_name_field', $ing_key, $value ); ?>

								<span href="#" class="cooked-delete-ingredient"><i class="cooked-icon cooked-icon-times"></i></span>

							</div>

						<?php elseif ( isset($value['section_heading_name']) ): ?>

							<div class="recipe-setting-block cooked-ingredient-block cooked-ingredient-heading cooked-clearfix">
								<i class="cooked-icon cooked-icon-drag"></i>
								<div class="cooked-heading-name">
									<input type="text" data-ingredient-part="section_heading_name" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][section_heading_name]" value="<?php echo esc_attr( $value['section_heading_name'] ); ?>" placeholder="<?php esc_html_e('Section Heading','cooked'); ?> ...">
								</div>
								<span href="#" class="cooked-delete-ingredient"><i class="cooked-icon cooked-icon-times"></i></span>
							</div>

						<?php endif; ?>

					<?php endforeach; ?>

				<?php else:

					$random_key = rand( 1000000,9999999 ); ?>

					<div class="recipe-setting-block cooked-ingredient-block cooked-clearfix">

						<i class="cooked-icon cooked-icon-drag"></i>

						<?php do_action( 'cooked_before_ingredient_fields', $random_key, false ); ?>

						<div class="cooked-ingredient-amount">
							<input type="text" data-ingredient-part="amount" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][amount]" value="" placeholder="--">
						</div>

						<?php do_action( 'cooked_after_ingredient_amount_field', $random_key, false ); ?>

						<div class="cooked-ingredient-measurement">
							<select data-ingredient-part="measurement" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][measurement]">
								<option value="">--</option>
								<?php foreach($measurements as $key => $measurement):
									echo '<option value="'.esc_attr( $key ).'">'.esc_html($measurement['plural_abbr']).'</option>';
								endforeach; ?>
							</select>
						</div>

						<?php do_action( 'cooked_after_ingredient_measurement_field', $random_key, false ); ?>

						<div class="cooked-ingredient-name">
							<input type="text" data-ingredient-part="name" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][name]" value="" placeholder="<?php esc_html_e('ex. Eggs, Milk, etc.','cooked'); ?> ...">
						</div>

						<?php do_action( 'cooked_after_ingredient_name_field', $random_key, false ); ?>

						<span href="#" class="cooked-delete-ingredient"><i class="cooked-icon cooked-icon-times"></i></span>

					</div>

				<?php endif; ?>

			</div>

			<div class="recipe-setting-block">

				<p>
                    <?php do_action( 'cooked_ingredient_buttons_start' ); ?>
					<a href="#" class="button cooked-add-ingredient-button"><?php esc_html_e('Add Ingredient','cooked'); ?></a>
					&nbsp;<a href="#" class="button cooked-add-heading-button"><?php esc_html_e('Add Section Heading','cooked'); ?></a>
                    <?php do_action( 'cooked_ingredient_buttons_end' ); ?>
				</p>

				<!-- TEMPLATES -->
				<div class="recipe-setting-block cooked-template cooked-ingredient-template cooked-clearfix">

					<i class="cooked-icon cooked-icon-drag"></i>

					<?php do_action( 'cooked_before_ingredient_fields', false, false ); ?>

					<div class="cooked-ingredient-amount">
						<input type="text" data-ingredient-part="amount" name="" value="" placeholder="--">
					</div>

					<?php do_action( 'cooked_after_ingredient_amount_field', false, false ); ?>

					<div class="cooked-ingredient-measurement">
						<select data-ingredient-part="measurement" name="">
							<option value="">--</option>
							<?php foreach($measurements as $key => $measurement):
								echo '<option value="'.esc_attr($key).'">'.esc_html($measurement['plural_abbr']).'</option>';
							endforeach; ?>
						</select>
					</div>

					<?php do_action( 'cooked_after_ingredient_measurement_field', false, false ); ?>

					<div class="cooked-ingredient-name">
						<input type="text" data-ingredient-part="name" name="" value="" placeholder="<?php esc_html_e('ex. Eggs, Milk, etc.','cooked'); ?> ...">
					</div>

					<?php do_action( 'cooked_after_ingredient_name_field', false, false ); ?>

					<span href="#" class="cooked-delete-ingredient"><i class="cooked-icon cooked-icon-times"></i></span>

				</div>
				<div class="recipe-setting-block cooked-template cooked-heading-template cooked-clearfix">
					<i class="cooked-icon cooked-icon-drag"></i>
					<div class="cooked-heading-name">
						<input type="text" data-ingredient-part="section_heading_name" name="" value="" placeholder="<?php esc_html_e('Section Heading','cooked'); ?> ...">
					</div>
					<span href="#" class="cooked-delete-ingredient"><i class="cooked-icon cooked-icon-times"></i></span>
				</div>
				<!-- END TEMPLATES -->

			</div>
		</section>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-directions">

			<div id="cooked-directions-builder" class="cooked-sortable">

				<?php if ( isset($recipe_settings['directions']) && !empty($recipe_settings['directions']) ): ?>

					<?php foreach($recipe_settings['directions'] as $dir_key => $value): ?>

						<?php if ( !isset($value['section_heading_name']) ): ?>

							<?php if ( isset($value['image']) && $value['image'] ):

								$image_thumb = wp_get_attachment_image( $value['image'], 'thumbnail', false, array(
									'class' => 'cooked-direction-img',
									'data-id' => esc_attr( $dir_key ),
									'data-direction-part' => 'image_src',
									'id' => 'direction-' . esc_attr( $dir_key ) . '-image-src' )
								);

							else:

								$image_thumb = false;

							endif; ?>

							<div class="recipe-setting-block cooked-direction-block cooked-clearfix">
								<i class="cooked-icon cooked-icon-drag"></i>
								<div class="cooked-direction-image<?php echo ( $image_thumb ? ' cooked-has-image' : '' ); ?>">
									<input data-direction-part="image" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr($dir_key); ?>][image]" id="direction-<?php echo esc_attr($dir_key); ?>-image" value="<?php if ( isset ( $value['image'] ) ) echo esc_attr( $value['image'] ); ?>" />
									<input data-direction-part="image_button" type="button" data-id="<?php echo esc_attr($dir_key); ?>" class="button direction-image-button" value="<?php echo ( $image_thumb ? esc_html__( 'Change Image', 'cooked' ) : esc_html__( 'Add Image', 'cooked' ) ); ?>" />
									<?php echo ( $image_thumb ? $image_thumb : '<img id="direction-' .esc_attr($dir_key) . '-image-src" data-direction-part="image_src" class="cooked-direction-img" src="" data-id="' . esc_attr($dir_key) . '">' ); ?>
									<div class="cooked-direction-img-placeholder"></div>
									<a href="#" data-id="<?php echo esc_attr($dir_key); ?>" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
								</div>
								<div class="cooked-direction-content">
									<textarea data-direction-part="content" name="_recipe_settings[directions][<?php echo esc_attr($dir_key); ?>][content]"><?php echo esc_html($value['content']); ?></textarea>
								</div>
								<a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
							</div>

						<?php elseif ( isset($value['section_heading_name']) ): ?>

							<div class="recipe-setting-block cooked-direction-block cooked-direction-heading cooked-clearfix">
								<i class="cooked-icon cooked-icon-drag"></i>
								<div class="cooked-heading-name">
									<input type="text" data-direction-part="section_heading_name" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][section_heading_name]" value="<?php echo esc_attr( $value['section_heading_name'] ); ?>" placeholder="<?php esc_html_e('Section Heading','cooked'); ?> ...">
								</div>
								<a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
							</div>

						<?php endif; ?>

					<?php endforeach; ?>

				<?php else:

					$random_key = rand( 1000000,9999999 ); ?>

					<div class="recipe-setting-block cooked-direction-block cooked-clearfix">
						<i class="cooked-icon cooked-icon-drag"></i>
						<div class="cooked-direction-image">
							<input data-direction-part="image" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $random_key ); ?>][image]" id="direction-<?php echo esc_attr( $random_key ); ?>-image" value="" />
							<input data-direction-part="image_button" type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="button direction-image-button" value="<?php esc_html_e( 'Add Image', 'cooked' ); ?>" />
							<img id="direction-<?php echo esc_attr( $random_key ); ?>-image-src" data-direction-part="image_src" class="cooked-direction-img" src="" data-id="<?php echo esc_attr( $random_key ); ?>">
							<div class="cooked-direction-img-placeholder"></div>
							<a href="#" data-id="<?php echo esc_attr( $random_key ); ?>" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
						</div>
						<div class="cooked-direction-content">
							<textarea data-direction-part="content" name="_recipe_settings[directions][<?php echo esc_attr( $random_key ); ?>][content]"></textarea>
						</div>
						<a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
					</div>

				<?php endif; ?>

			</div>

			<div class="recipe-setting-block">

				<p>
					<a href="#" class="button cooked-add-direction-button"><?php esc_html_e('Add Direction','cooked'); ?></a>
					&nbsp;<a href="#" class="button cooked-add-heading-button"><?php esc_html_e('Add Section Heading','cooked'); ?></a>
				</p>

				<!-- TEMPLATES -->
				<div class="recipe-setting-block cooked-template cooked-direction-template cooked-clearfix">
					<i class="cooked-icon cooked-icon-drag"></i>
					<div class="cooked-direction-image">
						<input data-direction-part="image" type="hidden" name="" value="" />
						<input data-direction-part="image_button" data-id="" type="button" class="button direction-image-button" value="<?php esc_html_e( 'Add Image', 'cooked' )?>" />
						<img id="" data-direction-part="image_src" class="cooked-direction-img" src="">
						<div class="cooked-direction-img-placeholder"></div>
						<a href="#" data-id="" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
					</div>
					<div class="cooked-direction-content">
						<textarea data-direction-part="content" name=""></textarea>
					</div>
					<a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
				</div>
				<div class="recipe-setting-block cooked-template cooked-heading-template cooked-clearfix">
					<i class="cooked-icon cooked-icon-drag"></i>
					<div class="cooked-heading-name">
						<input type="text" data-direction-part="section_heading_name" name="" value="" placeholder="<?php esc_html_e('Section Heading','cooked'); ?> ...">
					</div>
					<a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
				</div>
				<!-- END TEMPLATES -->

			</div>

		</section>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-nutrition">

			<div class="recipe-setting-block">
				<h3 class="cooked-settings-title cooked-bm-10"><?php esc_html_e( 'Nutrition Information', 'cooked' ); ?></h3>
				<div class="cooked-clearfix">
					<div class="cooked-setting-column-12">

						<?php $_nutrition_facts = Cooked_Measurements::nutrition_facts();

						$nut_loops = 0;
						foreach( $_nutrition_facts as $nutrition_facts ):
							foreach( $nutrition_facts as $slug => $nf ):

								$nut_loops++;
								if ( $nut_loops == 1 ): echo '<p class="cooked-measurement-inputs">'; endif;
								echo '<span class="cooked-measurement-column">';
									echo '<label for="' . esc_attr($slug) . '" class="cooked-nutrition-label">' . esc_html($nf['name']) . '</label>';
									echo '<input type="' . esc_attr($nf['type']) . '"' . ( $nf['type'] == 'number' ? ' step="any"' : '' ) . ' placeholder="--" name="_recipe_settings[nutrition][' . esc_attr($slug) . ']" id="' . esc_attr($slug) . '" value="' . ( isset($recipe_settings['nutrition'][$slug]) ? esc_attr($recipe_settings['nutrition'][$slug]) : '' ) . '" />';
								echo '</span>';
								if ( $nut_loops == 2 ): echo '</p>'; $nut_loops = 0; endif;
								if ( isset($nf['subs']) ):
									foreach( $nf['subs'] as $sub_slug => $sub_nf ):
										$nut_loops++;
										if ( $nut_loops == 1 ): echo '<p class="cooked-measurement-inputs">'; endif;
										echo '<span class="cooked-measurement-column">';
											echo '<label for="' . esc_attr($sub_slug) . '" class="cooked-nutrition-label">' . esc_html($sub_nf['name']) . '</label>';
											echo '<input type="' . esc_attr($sub_nf['type']) . '"' . ( $nf['type'] == 'number' ? ' step="any"' : '' ) . ' placeholder="--" name="_recipe_settings[nutrition][' . esc_attr($sub_slug) . ']" id="' . esc_attr($sub_slug) . '" value="' . ( isset($recipe_settings['nutrition'][$sub_slug]) ? esc_attr($recipe_settings['nutrition'][$sub_slug]) : '' ) . '" />';
										echo '</span>';
										if ( $nut_loops == 2 ): echo '</p>'; $nut_loops = 0; endif;
									endforeach;
								endif;
							endforeach;
						endforeach;

						?>

					</div>
					<div class="cooked-setting-column-12">
						<section id="cooked-nutrition-label" class="cooked-nut-label-1">

							<h2><?php esc_html_e('Nutrition Facts','cooked'); ?></h2>

							<?php $nutrition_facts = $_nutrition_facts['top'];
							foreach( $nutrition_facts as $slug => $nf ):
								echo '<p>' . esc_html($nf['name']) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr($slug) . '">___</strong></p>';
							endforeach; ?>

							<hr class="cooked-nut-hr" />

							<ul>
								<li><strong class="cooked-nut-heading"><?php esc_html_e('Amount Per Serving','cooked'); ?></strong></li>

								<?php $nutrition_facts = $_nutrition_facts['mid'];
								foreach( $nutrition_facts as $slug => $nf ):
									if ( $slug != 'calories_fat' ):
										echo '<li><strong>' . esc_html($nf['name']) . '</strong> <strong class="cooked-nut-label" data-labeltype="' . esc_attr($slug) . '">___</strong>';
											echo '<ul class="cooked-calories-fat cooked-right"><li>' . esc_attr($nutrition_facts['calories_fat']['name']) . ' <strong class="cooked-nut-label" data-labeltype="calories_fat">___</strong></li></ul>';
										echo '</li>';
									endif;
								endforeach; ?>

								<li class="cooked-nut-spacer"></li>
								<li class="cooked-nut-no-border"><strong class="cooked-nut-heading cooked-nut-right"><?php esc_html_e('% Daily Value *','cooked'); ?></strong></li>

								<?php $nutrition_facts = $_nutrition_facts['main'];
								$nut_loops = 0;

								foreach( $nutrition_facts as $slug => $nf ):

									echo '<li>';
									echo '<strong>' . esc_html($nf['name']) . '</strong> <strong class="cooked-nut-label" data-labeltype="' . esc_html($slug) . '">___ </strong>' . ( isset($nf['measurement']) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_html($slug) . '_measurement">' . esc_html($nf['measurement']) . '</strong>' : '' );
									echo ( isset( $nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr($nf['pdv']) . '" data-labeltype="' . esc_html($slug) . '">0</span>%</strong>' : '' );

									if ( isset($nf['subs']) ):
										foreach( $nf['subs'] as $sub_slug => $sub_nf ):
											echo '<ul><li>';
												echo '<strong>' . esc_html($sub_nf['name']) . '</strong> <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">___ </strong>' . ( isset($sub_nf['measurement']) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html($sub_nf['measurement']) . '</strong>' : '' );
												echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr($sub_nf['pdv']) . '" data-labeltype="' . esc_attr($sub_slug) . '">0</span>%</strong>' : '' );
											echo '</li></ul>';
										endforeach;
									endif;

									echo '</li>';

								endforeach; ?>
							</ul>

							<hr class="cooked-nut-hr" />

							<ul class="cooked-nut-bottom cooked-clearfix">
								<?php $nutrition_facts = $_nutrition_facts['bottom'];
								foreach( $nutrition_facts as $slug => $nf ):
									echo '<li>';
									echo '<strong>' . esc_html($nf['name']) . ' <span class="cooked-nut-right"><span class="cooked-nut-percent cooked-nut-label" data-labeltype="' . esc_attr($slug) . '">0</span>%</span></strong>';
									echo '</li>';
								endforeach; ?>
							</ul>

							<p class="cooked-daily-value-text">* <?php esc_html_e( 'Percent Daily Values are based on a 2,000 calorie diet. Your daily value may be higher or lower depending on your calorie needs.', 'cooked' ); ?></p>

						</section>
					</div>
				</div>
			</div>

		</section>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-gallery">

			<?php $cooked_gallery_types = Cooked_Recipes::gallery_types(); ?>

			<div class="recipe-setting-block">
				<h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Gallery Type', 'cooked' ); ?></h3>
				<select id="cooked_gallery_type" name="_recipe_settings[gallery][type]">
					<?php foreach( $cooked_gallery_types as $slug => $gtype ):
						echo '<option value="' . esc_attr( $slug ) . '"' . ( isset($recipe_settings['gallery']['type']) && $recipe_settings['gallery']['type'] == $slug ? ' selected' : '' ) . '>' . esc_attr( $gtype['title'] ) . '</option>';
					endforeach; ?>
				</select>
			</div>

			<?php foreach( $cooked_gallery_types as $slug => $gtype ):
				if ( isset($gtype['posts']) && !empty($gtype['posts']) ): ?>
					<div class="recipe-setting-block cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="<?php echo esc_attr( $slug ); ?>">
						<h3 class="cooked-settings-title"><?php echo esc_html( $gtype['title'] ); ?></h3>
						<select id="cooked_gallery_type" name="_recipe_settings[gallery][<?php echo esc_attr( $slug ); ?>]">
							<option value=""><?php esc_html_e('Choose one...','cooked'); ?></option>
							<?php foreach( $gtype['posts'] as $gid => $g ):
								echo '<option value="' . esc_attr( $gid ) . '"' . ( isset($recipe_settings['gallery'][$slug]) && $recipe_settings['gallery'][$slug] == $gid ? ' selected' : '' ) . '>' . esc_attr( $g ) . '</option>';
							endforeach; ?>
						</select>
					</div>
				<?php endif;
			endforeach; ?>

			<div class="recipe-setting-block cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">

				<div class="recipe-setting-block cooked-bm-15">
					<h3 class="cooked-settings-title"><?php echo sprintf( esc_html__( '%s or %s Video', 'cooked' ),'YouTube','Vimeo' ); ?></h3>
					<p><?php echo sprintf( esc_html__( "If you would like to display a video as the first item in your gallery, you can paste a valid %s or %s URL below.","cooked"),'YouTube','Vimeo' ); ?></p>
					<input type="text" name="_recipe_settings[gallery][video_url]" value="<?php echo ( isset($recipe_settings['gallery']['video_url']) && $recipe_settings['gallery']['video_url'] ? $recipe_settings['gallery']['video_url'] : '' ); ?>" placeholder="ex. https://www.youtube.com/watch?v=abc123">
				</div>

				<h3 class="cooked-settings-title"><?php esc_html_e( 'Gallery Items', 'cooked' ); ?></h3>
				<div id="cooked-recipe-image-gallery" class="cooked-clearfix"><?php

					$gallery_items = ( isset($recipe_settings['gallery']['items']) && !empty($recipe_settings['gallery']['items']) ? $recipe_settings['gallery']['items'] : array() );
					if (!empty($gallery_items)):
						foreach ($gallery_items as $g_item):

							$image_thumb = wp_get_attachment_image( $g_item, 'thumbnail' );
							$image_title = get_the_title( $g_item );
							echo '<div data-attachment-id="' . esc_attr( $g_item ) . '" class="cooked-recipe-gallery-item">' . esc_html( $image_thumb ) . '<span class="cooked-gallery-item-title">' . esc_html( $image_title ) . '</span><input type="hidden" name="_recipe_settings[gallery][items][]" value="' . esc_attr( $g_item ) . '" /><a href="#" data-attachment-id="' . esc_attr( $g_item ) . '" class="cooked-gallery-edit-button"><i class="cooked-icon cooked-icon-pencil"></i></a><a href="#" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a></div>';

						endforeach;
					endif;

				?></div>
				<input type="button" class="button cooked-gallery-add-button" value="<?php esc_html_e( 'Add to Gallery', 'cooked' ); ?>" />
			</div>

		</section>

		<section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-shortcodes">

			<div class="recipe-setting-block">

				<?php do_action('cooked_recipe_shortcodes_before'); ?>

				<!-- [cooked-info] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Recipe Information', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the recipe author, cooking times, etc.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-info]' />
						</div>

						<div class="cooked-clearfix">
							<div class="cooked-setting-column-12">
								<p class="cooked-bm-5"><strong><?php echo sprintf( esc_html__( '"%s" and "%s"','cooked' ), 'include','exclude' ); ?></strong></p>
								<p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to include or exclude content from the shortcode output.','cooked'); ?></p>
								<div class="cooked-bm-20 cooked-block">
									<input class='cooked-shortcode-field' type='text' readonly value='include="author,total_time"' />
								</div>
							</div>
							<div class="cooked-setting-column-12">
								<p class="cooked-bm-5"><strong><?php echo sprintf( esc_html__( '"%s" and "%s"','cooked' ), 'left','right' ); ?></strong></p>
								<p class="cooked-bm-10"><?php esc_html_e( 'Used like "include", but will position the content to the left or right.','cooked'); ?></p>
								<div class="cooked-bm-20 cooked-block">
									<input class='cooked-shortcode-field' type='text' readonly value='left="author" right="total_time"' />
								</div>
							</div>
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">

							<?php $available_cooked_info_vars = apply_filters( 'cooked_available_info_vars', array(
								'author' => esc_html__( 'Author','cooked' ),
								'prep_time' => esc_html__( 'Prep Time','cooked' ),
								'cook_time' => esc_html__( 'Cook Time','cooked' ),
								'total_time' => esc_html__( 'Total Time','cooked' ),
								'difficulty_level' => esc_html__( 'Difficulty','cooked' ),
								'servings' => esc_html__( 'Servings Switcher','cooked' ),
								'taxonomies' => esc_html__( 'Category','cooked' ),
								'print' => esc_html__( 'Print Mode','cooked' ),
								'fullscreen' => esc_html__( 'Full-Screen Mode','cooked' )
							));

							foreach( $available_cooked_info_vars as $var => $name ):
								echo '<strong>' . esc_html($var) . '</strong> (' . esc_html($name) . ')<br>';
							endforeach; ?>

						</p>
					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [cooked-ingredients] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Ingredients', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the list of ingredients, added via the "Ingredients" tab.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-ingredients]' />
						</div>

						<p class="cooked-bm-5"><strong>"checkboxes"</strong></p>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the checkboxes:','cooked'); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='checkboxes=false' />
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<strong>true</strong> (<?php esc_html_e( 'Show checkboxes','cooked' ); ?>)<br>
							<strong>false</strong> (<?php esc_html_e( 'Hide checkboxes','cooked' ); ?>)
						</p>
					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [cooked-directions] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Directions', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the list of directions, added via the "Directions" tab.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-directions]' />
						</div>

						<p class="cooked-bm-5"><strong>"numbers"</strong></p>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the numbers:','cooked'); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='numbers=false' />
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<strong>true</strong> (<?php esc_html_e( 'Show numbers','cooked' ); ?>)<br>
							<strong>false</strong> (<?php esc_html_e( 'Hide numbers','cooked' ); ?>)
						</p>
					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [cooked-image] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Featured Image', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the featured image, if one is set.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-image]' />
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<em><?php esc_html_e( 'None', 'cooked'); ?></em>
						</p>
					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [cooked-nutrition] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Nutrition Label', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the Nutrition Facts label, if data is present.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-nutrition]' />
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<em><?php esc_html_e( 'None', 'cooked'); ?></em>
						</p>
					</div>

				</div>

				<div class="cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">

					<hr class="cooked-hr">

					<!-- [cooked-gallery] -->
					<div class="cooked-clearfix">

						<div class="cooked-setting-column-23">

							<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Gallery', 'cooked' ); ?></h3>
							<p class="cooked-bm-10"><?php esc_html_e( 'This will display the gallery, if one is set or created from the "Gallery" tab.', 'cooked' ); ?></p>
							<div class="cooked-bm-20 cooked-block">
								<input class='cooked-shortcode-field' type='text' readonly value='[cooked-gallery]' />
							</div>

							<div class="cooked-clearfix">
								<div class="cooked-setting-column-12">
									<p class="cooked-bm-5"><strong>"width"</strong></p>
									<p class="cooked-bm-10"><?php esc_html_e( 'Set the width of the gallery.','cooked'); ?></p>
									<div class="cooked-bm-20 cooked-block">
										<input class='cooked-shortcode-field' type='text' readonly value='width="350px"' />
									</div>
								</div>
								<div class="cooked-setting-column-12">
									<p class="cooked-bm-5"><strong>"ratio"</strong></p>
									<p class="cooked-bm-10"><?php esc_html_e( 'Set the image size ratio.','cooked'); ?></p>
									<div class="cooked-bm-20 cooked-block">
										<input class='cooked-shortcode-field' type='text' readonly value='ratio="500/500"' />
									</div>
								</div>
							</div>

							<div class="cooked-clearfix">
								<div class="cooked-setting-column-12">
									<p class="cooked-bm-5"><strong>"nav"</strong></p>
									<p class="cooked-bm-10"><?php esc_html_e( 'Set the navigation style.','cooked'); ?></p>
									<div class="cooked-bm-20 cooked-block">
										<input class='cooked-shortcode-field' type='text' readonly value='nav="thumbs"' />
									</div>
								</div>
								<div class="cooked-setting-column-12">
									<p class="cooked-bm-5"><strong>"allowfullscreen"</strong></p>
									<p class="cooked-bm-10"><?php esc_html_e( 'Enable or disable "Full-Screen" mode.','cooked'); ?></p>
									<div class="cooked-bm-20 cooked-block">
										<input class='cooked-shortcode-field' type='text' readonly value='allowfullscreen="true"' />
									</div>
								</div>
							</div>

						</div>

						<div class="cooked-setting-column-13">
							<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
							<p class="cooked-bm-10">
								<strong>width</strong><br>
								<?php echo sprintf( esc_html__( 'ex: "%s" or "%s"', 'cooked'), '80%','300px' ); ?><br><br>
								<strong>ratio</strong><br>
								<?php echo sprintf( esc_html__( 'ex: "%s"', 'cooked'), '800/600' ); ?><br><br>
								<strong>nav</strong><br>
								<?php echo sprintf( esc_html__( '"%s", "%s", or "%s"', 'cooked'), 'dots','thumbs','false' ); ?><br><br>
								<strong>allowfullscreen</strong><br>
								<?php echo sprintf( esc_html__( '"%s" or "%s"', 'cooked'), 'true','false' ); ?>
							</p>
						</div>

					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [cooked-excerpt] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Excerpt', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display the excerpt, if one is available from the "Layout & Content" tab.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='[cooked-excerpt]' />
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<em><?php esc_html_e( 'None', 'cooked'); ?></em>
						</p>
					</div>

				</div>

				<hr class="cooked-hr">

				<!-- [timer] -->
				<div class="cooked-clearfix">

					<div class="cooked-setting-column-23">

						<h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Timer', 'cooked' ); ?></h3>
						<p class="cooked-bm-10"><?php esc_html_e( 'This will display a special link to start a cooking timer.', 'cooked' ); ?></p>
						<div class="cooked-bm-20 cooked-block">
							<input class='cooked-shortcode-field' type='text' readonly value='<?php echo ( shortcode_exists('cooked-timer') ? '[cooked-timer minutes="5"]5 Minutes[/cooked-timer]' : '[timer minutes=5]5 Minutes[/timer]' ); ?>' />
						</div>

						<div class="cooked-clearfix">
							<div class="cooked-setting-column-12">
								<p class="cooked-bm-5"><strong><?php echo sprintf( esc_html__( '"%s", "%s" and "%s"','cooked' ), 'seconds','minutes','hours' ); ?></strong></p>
								<p class="cooked-bm-10"><?php esc_html_e( 'Use just one or a combination of all three to set the timer length','cooked'); ?></p>
								<div class="cooked-bm-20 cooked-block">
									<input class='cooked-shortcode-field' type='text' readonly value='minutes="5" seconds="30"' />
								</div>
							</div>
							<div class="cooked-setting-column-12">
								<p class="cooked-bm-5"><strong>"desc"</strong></p>
								<p class="cooked-bm-10"><?php esc_html_e( 'Add a short description for this timer, if applicable.','cooked'); ?></p>
								<div class="cooked-bm-20 cooked-block">
									<input class='cooked-shortcode-field' type='text' readonly value='desc="Boil for 20 minutes"' />
								</div>
							</div>
						</div>

					</div>

					<div class="cooked-setting-column-13">
						<p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables','cooked' ); ?></strong></p>
						<p class="cooked-bm-10">
							<strong>seconds</strong> (<?php esc_html_e( 'Time in seconds','cooked' ); ?>)<br>
							<strong>minutes</strong> (<?php esc_html_e( 'Time in minutes','cooked' ); ?>)<br>
							<strong>hours</strong> (<?php esc_html_e( 'Time in hours','cooked' ); ?>)<br>
							<strong>desc</strong> (<?php esc_html_e( 'Timer Description','cooked' ); ?>)
						</p>
					</div>

				</div>

				<?php do_action('cooked_recipe_shortcodes_after'); ?>

			</div>

		</section>

		<?php do_action('cooked_recipe_tabs_after'); ?>

	</div>

<?php
}

add_action( 'cooked_recipe_fields', 'cooked_render_recipe_fields', 10 );
