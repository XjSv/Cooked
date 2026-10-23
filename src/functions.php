<?php
/**
 * Global recipe editor functions
 *
 * These stay in the global namespace because themes and add-ons call
 * cooked_recipe_shortcodes_content() and cooked_render_recipe_fields() by name.
 *
 * @package     Cooked
 * @subpackage  Recipe Meta
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the recipe editor meta box: tabs and recipe fields.
 *
 * @return void
 */
function cooked_recipe_shortcodes_content() {
    global $post_id;

    ?><div class="recipe-setting-block">

        <hr class="cooked-hr">

        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Display Recipe', 'cooked' ); ?></h3>
        <p class="cooked-bm-10"><?php esc_html_e( 'This shortcode displays the recipe in its entirety, using the "Recipe Template" field in the first tab.', 'cooked' ); ?></p>
        <div class="cooked-bm-20 cooked-block">
            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-recipe id="<?php echo intval( $post_id ); ?>"]' />
        </div>

        <hr class="cooked-hr">

        <!-- [cooked-recipe-card] -->
        <div class="cooked-clearfix">

            <div class="cooked-setting-column-23">

                <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Recipe Card', 'cooked' ); ?></h3>
                <p class="cooked-bm-10"><?php esc_html_e( 'Displays a compact linked recipe card with image, title, author, and excerpt — not the full recipe. Use this to promote this recipe elsewhere on your site.', 'cooked' ); ?></p>
                <div class="cooked-bm-20 cooked-block">
                    <input class="cooked-shortcode-field" type="text" readonly value='[cooked-recipe-card id="<?php echo intval( $post_id ); ?>"]'>
                </div>

                <div class="cooked-clearfix">
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"style"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Choose "modern" or "modern-centered".', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='style="modern"'>
                        </div>
                    </div>
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"width"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Set the card width. Defaults to 100%. Use "%" or "px" after the number.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='width="300px"'>
                        </div>
                    </div>
                </div>

                <div class="cooked-clearfix">
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"hide_excerpt"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Hide the recipe excerpt.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='hide_excerpt="true"'>
                        </div>
                    </div>
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"hide_author"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Hide the recipe author.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='hide_author="true"'>
                        </div>
                    </div>
                </div>

                <div class="cooked-clearfix">
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"hide_image"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Hide the recipe image.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='hide_image="true"'>
                        </div>
                    </div>
                    <div class="cooked-setting-column-12">
                        <p class="cooked-bm-5"><strong>"hide_title"</strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Hide the recipe title.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class="cooked-shortcode-field" type="text" readonly value='hide_title="true"'>
                        </div>
                    </div>
                </div>

            </div>

            <div class="cooked-setting-column-13">
                <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                <p class="cooked-bm-10">
                    <strong>id</strong> (<?php esc_html_e( 'Recipe ID', 'cooked' ); ?>)<br>
                    <strong>width</strong> (<?php esc_html_e( 'Card width', 'cooked' ); ?>)<br>
                    <strong>style</strong> (<?php esc_html_e( 'modern, modern-centered', 'cooked' ); ?>)<br>
                    <strong>hide_image</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                    <strong>hide_title</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                    <strong>hide_excerpt</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                    <strong>hide_author</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)
                </p>
                <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Example', 'cooked' ); ?></strong></p>
                <p class="cooked-bm-10">
                    <code>[cooked-recipe-card id="<?php echo intval( $post_id ); ?>" width="300px" style="modern"]</code>
                </p>
            </div>

        </div>

    </div>
    <?php
}

/**
 * Recipe Fields
 *
 * @since 1.0.0
 * @param int $post_id Recipe ID.
 */
function cooked_render_recipe_fields( $post_id ) {
    global $_cooked_settings;
    $recipe_settings = get_post_meta( $post_id, '_recipe_settings', true );

    // Backwards Compatibility with Cooked 2.x.
    $c2_recipe_settings     = Cooked_Recipes::get_c2_recipe_meta( $post_id );
    $recipe_review_required = false;

    // Show the Shortcodes tab if recipe is saved.
    if ( ! empty( $recipe_settings ) ) :
        add_action( 'cooked_recipe_shortcodes_after', 'cooked_recipe_shortcodes_content', 10 );
    endif;

    if ( ! isset( $recipe_settings['cooked_version'] ) && ! empty( $c2_recipe_settings ) ) :
        $recipe_review_required = true;
        $recipe_settings        = Cooked_Recipes::sync_c2_recipe_settings( $c2_recipe_settings, $post_id );
    endif;

    $recipe_tabs = apply_filters(
        'cooked_recipe_admin_tabs',
        [
			'content'     => [
				'icon'        => 'desktop',
				'name'        => __( 'Layout', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
			'ingredients' => [
				'icon'        => 'list',
				'name'        => __( 'Ingredients', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
			'directions'  => [
				'icon'        => 'directions',
				'name'        => __( 'Directions', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
			'nutrition'   => [
				'icon'        => 'heart',
				'name'        => __( 'Nutrition', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
			'gallery'     => [
				'icon'        => 'image',
				'name'        => __( 'Gallery', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
			'shortcodes'  => [
				'icon'        => 'code',
				'name'        => __( 'Shortcodes', 'cooked' ),
				'conditional' => false,
				'value'       => false,
			],
		]
    );

    $measurements = Cooked_Measurements::get();

    $wp_editor_roles_allowed = false;
    if ( is_user_logged_in() ) {
        $user                    = wp_get_current_user();
        $user_roles              = $user->roles;
        $wp_editor_roles_allowed = isset( $_cooked_settings['recipe_wp_editor_roles'] ) && ! empty( array_intersect( $user_roles, $_cooked_settings['recipe_wp_editor_roles'] ) ) ? true : false;
    }

    if ( ! empty( $recipe_tabs ) ) :
        echo '<ul id="cooked-recipe-tabs">';
        $first_tab = true;

        foreach ( $recipe_tabs as $slug => $tab ) :
            $classes = [];
            if ( $first_tab ) :
				$classes[] = 'active';
endif;
            if ( $tab['conditional'] ) :
				$classes[] = 'cooked-conditional-hidden';
endif;

            echo "<li id='cooked-recipe-tab-" . esc_attr( $slug ) . "'" . ( ! empty( $classes ) ? " class='" . esc_attr( implode( ' ', $classes ) ) . "'" : '' ) . ( $tab['conditional'] ? " data-condition='" . esc_attr( $tab['conditional'] ) . "'" : '' ) . ( $tab['value'] ? " data-value='" . esc_attr( $tab['value'] ) . "'" : '' ) . '>';
            echo ( $tab['icon'] ? "<i class='cooked-icon cooked-icon-fw cooked-icon-" . esc_attr( $tab['icon'] ) . "'></i>" : '' );
            echo esc_html( $tab['name'] );
            echo '</li>';
            $first_tab = false;

        endforeach;

        echo '</ul>';

    endif;
    ?>

    <div class="cooked-recipe-tab-content-wrapper">

        <?php do_action( 'cooked_recipe_tabs_before' ); ?>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-content">

            <?php
            // The Layout tab has a classic and a new layout (Settings > General > Admin Screens).
            include COOKED_DIR . 'templates/admin/' . ( Cooked_Admin_Enqueues::use_classic_ui() ? 'classic' : 'frame' ) . '/recipe-layout.php';
            ?>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-ingredients">

            <div class="cooked-ingredient-headers cooked-clearfix">
                <span class="cooked-ingredient-header-amount"><?php esc_html_e( 'Amount', 'cooked' ); ?></span>
                <span class="cooked-ingredient-header-measurement"><?php esc_html_e( 'Measurement', 'cooked' ); ?></span>
                <span class="cooked-ingredient-header-item"><?php esc_html_e( 'Item', 'cooked' ); ?></span>
            </div>

            <div id="cooked-ingredients-builder" class="cooked-sortable">

                <?php if ( isset( $recipe_settings['ingredients'] ) && ! empty( $recipe_settings['ingredients'] ) ) : ?>

                    <?php foreach ( $recipe_settings['ingredients'] as $ing_key => $value ) : ?>

                        <?php if ( ! isset( $value['section_heading_name'] ) ) : ?>

                            <?php $ingredient_classes = apply_filters( 'cooked_ingredient_field_classes', 'recipe-setting-block cooked-ingredient-block cooked-clearfix', $value ); ?>
                            <?php $has_sub = isset( $value['sub_name'] ) && ! empty( $value['sub_name'] ); ?>

                            <div class="
                            <?php
                            echo esc_attr( $ingredient_classes );
							echo ( $has_sub ? ' cooked-has-substitution' : '' );
							?>
                            ">

                                <i class="cooked-icon cooked-icon-drag"></i>

                                <?php do_action( 'cooked_before_ingredient_fields', $ing_key, $value ); ?>

                                <div class="cooked-ingredient-amount">
                                    <input type="text" data-ingredient-part="amount" aria-label="<?php esc_attr_e( 'Amount', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][amount]" value="<?php echo esc_attr( isset( $value['amount'] ) ? $value['amount'] : '' ); ?>" placeholder="--">
                                </div>

                                <?php do_action( 'cooked_after_ingredient_amount_field', $ing_key, $value ); ?>

                                <div class="cooked-ingredient-measurement">
                                    <select data-ingredient-part="measurement" aria-label="<?php esc_attr_e( 'Measurement', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][measurement]">
                                        <option value="">--</option>
                                        <?php
                                        foreach ( $measurements as $key => $measurement ) :
                                            echo '<option value="' . esc_attr( $key ) . '"' . ( isset( $value['measurement'] ) && $value['measurement'] === $key ? ' selected' : '' ) . '>' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                                        endforeach;
                                        ?>
                                    </select>
                                </div>

                                <?php do_action( 'cooked_after_ingredient_measurement_field', $ing_key, $value ); ?>

                                <div class="cooked-ingredient-name">
                                    <input type="text" data-ingredient-part="name" aria-label="<?php esc_attr_e( 'Item', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][name]" value="<?php echo esc_attr( isset( $value['name'] ) ? $value['name'] : '' ); ?>" placeholder="<?php esc_attr_e( 'ex. Eggs, Milk, etc.', 'cooked' ); ?> ...">
                                </div>

                                <?php do_action( 'cooked_after_ingredient_name_field', $ing_key, $value ); ?>

                                <button type="button" class="cooked-show-substitution" aria-expanded="false" aria-label="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>" title="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-reverse" aria-hidden="true"></i></button>
                                <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i></button>

                                <div class="cooked-heading-element cooked-substitution-fields">
                                    <label class="cooked-sub-label"><?php esc_html_e( 'Substitution:', 'cooked' ); ?></label>
                                    <div class="cooked-ingredient-amount">
                                        <input type="text" data-ingredient-part="sub_amount" aria-label="<?php esc_attr_e( 'Substitute amount', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][sub_amount]" value="<?php echo isset( $value['sub_amount'] ) ? esc_attr( $value['sub_amount'] ) : ''; ?>" placeholder="--">
                                    </div>
                                    <div class="cooked-ingredient-measurement">
                                        <select data-ingredient-part="sub_measurement" aria-label="<?php esc_attr_e( 'Substitute measurement', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][sub_measurement]">
                                            <option value="">--</option>
                                            <?php
                                            foreach ( $measurements as $key => $measurement ) :
                                                echo '<option value="' . esc_attr( $key ) . '"' . ( isset( $value['sub_measurement'] ) && $value['sub_measurement'] === $key ? ' selected' : '' ) . '>' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="cooked-ingredient-name">
                                        <input type="text" data-ingredient-part="sub_name" aria-label="<?php esc_attr_e( 'Substitute item', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][sub_name]" value="<?php echo isset( $value['sub_name'] ) ? esc_attr( $value['sub_name'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'ex. Apple Sauce, Tofu, etc.', 'cooked' ); ?> ...">
                                    </div>
                                </div>

                            </div>

                        <?php elseif ( isset( $value['section_heading_name'] ) ) : ?>

                            <div class="recipe-setting-block cooked-ingredient-block cooked-ingredient-heading cooked-clearfix">
                                <i class="cooked-icon cooked-icon-drag"></i>
                                <div class="cooked-heading-name">
                                    <input type="text" data-ingredient-part="section_heading_name" aria-label="<?php esc_attr_e( 'Section heading', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][section_heading_name]" value="<?php echo esc_attr( $value['section_heading_name'] ); ?>" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                                </div>

                                <div class="cooked-heading-element">
                                    <label><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                                    <select class="cooked-heading-element-select" data-ingredient-part="section_heading_element" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $ing_key ); ?>][section_heading_element]">
                                        <?php
                                        $heading_elements = [
                                            'div' => 'div (default)',
                                            'h2'  => 'h2',
                                            'h3'  => 'h3',
                                            'h4'  => 'h4',
                                            'h5'  => 'h5',
                                            'h6'  => 'h6',
                                        ];
                                        foreach ( $heading_elements as $element => $label ) :
											?>
                                            <option value="<?php echo esc_attr( $element ); ?>" <?php selected( isset( $value['section_heading_element'] ) ? $value['section_heading_element'] : 'div', $element ); ?>>
                                                <?php echo esc_html( $label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="cooked-show-heading-element" aria-expanded="false" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" title="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-pencil" aria-hidden="true"></i></button>

                                <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i></button>
                            </div>

                        <?php endif; ?>

                    <?php endforeach; ?>

					<?php
                else :

                    $random_key = wp_rand( 1000000, 9999999 );
                    ?>

                    <div class="recipe-setting-block cooked-ingredient-block cooked-clearfix">

                        <i class="cooked-icon cooked-icon-drag"></i>

                        <?php do_action( 'cooked_before_ingredient_fields', $random_key, false ); ?>

                        <div class="cooked-ingredient-amount">
                            <input type="text" data-ingredient-part="amount" aria-label="<?php esc_attr_e( 'Amount', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][amount]" value="" placeholder="--">
                        </div>

                        <?php do_action( 'cooked_after_ingredient_amount_field', $random_key, false ); ?>

                        <div class="cooked-ingredient-measurement">
                            <select data-ingredient-part="measurement" aria-label="<?php esc_attr_e( 'Measurement', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][measurement]">
                                <option value="">--</option>
                                <?php
                                foreach ( $measurements as $key => $measurement ) :
                                    echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                                endforeach;
                                ?>
                            </select>
                        </div>

                        <?php do_action( 'cooked_after_ingredient_measurement_field', $random_key, false ); ?>

                        <div class="cooked-ingredient-name">
                            <input type="text" data-ingredient-part="name" aria-label="<?php esc_attr_e( 'Item', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][name]" value="" placeholder="<?php esc_attr_e( 'ex. Eggs, Milk, etc.', 'cooked' ); ?> ...">
                        </div>

                        <?php do_action( 'cooked_after_ingredient_name_field', $random_key, false ); ?>

                        <button type="button" class="cooked-show-substitution" aria-expanded="false" aria-label="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>" title="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-reverse" aria-hidden="true"></i></button>
                        <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i></button>

                        <div class="cooked-heading-element cooked-substitution-fields">
                            <label class="cooked-sub-label"><?php esc_html_e( 'Substitution:', 'cooked' ); ?></label>
                            <div class="cooked-ingredient-amount">
                                <input type="text" data-ingredient-part="sub_amount" aria-label="<?php esc_attr_e( 'Substitute amount', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][sub_amount]" value="" placeholder="--">
                            </div>
                            <div class="cooked-ingredient-measurement">
                                <select data-ingredient-part="sub_measurement" aria-label="<?php esc_attr_e( 'Substitute measurement', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][sub_measurement]">
                                    <option value="">--</option>
                                    <?php
                                    foreach ( $measurements as $key => $measurement ) :
                                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                            <div class="cooked-ingredient-name">
                                <input type="text" data-ingredient-part="sub_name" aria-label="<?php esc_attr_e( 'Substitute item', 'cooked' ); ?>" name="_recipe_settings[ingredients][<?php echo esc_attr( $random_key ); ?>][sub_name]" value="" placeholder="<?php esc_attr_e( 'ex. Apple Sauce, Tofu, etc.', 'cooked' ); ?> ...">
                            </div>
                        </div>

                    </div>

                <?php endif; ?>

            </div>

            <?php if ( ! Cooked_Admin_Enqueues::use_classic_ui() ) : ?>
                <div class="cooked-ingredient-quick-add">
                    <input type="text" class="cooked-ingredient-quick-add-input" aria-label="<?php esc_attr_e( 'Add an ingredient', 'cooked' ); ?>" aria-describedby="cooked-ingredient-quick-add-hint" placeholder="<?php esc_attr_e( 'Add an ingredient, e.g. 2 cups flour, sifted', 'cooked' ); ?>" autocomplete="off">
                    <span class="cooked-ingredient-quick-add-hint" id="cooked-ingredient-quick-add-hint"><?php esc_html_e( 'Press Enter to add', 'cooked' ); ?></span>
                </div>
            <?php endif; ?>

            <div class="recipe-setting-block">

                <p class="cooked-builder-buttons">
                    <?php do_action( 'cooked_ingredient_buttons_start' ); ?>
                    <a href="#" class="button cooked-add-ingredient-button"><?php esc_html_e( 'Add Ingredient', 'cooked' ); ?></a>
                    <a href="#" class="button cooked-add-heading-button"><?php esc_html_e( 'Add Section Heading', 'cooked' ); ?></a>
                    <?php do_action( 'cooked_ingredient_buttons_end' ); ?>
                    <a href="#" class="button cooked-bulk-add-button" data-type="ingredients"><?php esc_html_e( 'Bulk Add', 'cooked' ); ?></a>
                </p>

                <!-- TEMPLATES -->
                <div class="recipe-setting-block cooked-template cooked-ingredient-template cooked-clearfix">

                    <i class="cooked-icon cooked-icon-drag"></i>

                    <?php do_action( 'cooked_before_ingredient_fields', false, false ); ?>

                    <div class="cooked-ingredient-amount">
                        <input type="text" data-ingredient-part="amount" aria-label="<?php esc_attr_e( 'Amount', 'cooked' ); ?>" name="" value="" placeholder="--">
                    </div>

                    <?php do_action( 'cooked_after_ingredient_amount_field', false, false ); ?>

                    <div class="cooked-ingredient-measurement">
                        <select data-ingredient-part="measurement" aria-label="<?php esc_attr_e( 'Measurement', 'cooked' ); ?>" name="">
                            <option value="">--</option>
                            <?php
                            foreach ( $measurements as $key => $measurement ) :
                                echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                            endforeach;
                            ?>
                        </select>
                    </div>

                    <?php do_action( 'cooked_after_ingredient_measurement_field', false, false ); ?>

                    <div class="cooked-ingredient-name">
                        <input type="text" data-ingredient-part="name" aria-label="<?php esc_attr_e( 'Item', 'cooked' ); ?>" name="" value="" placeholder="<?php esc_attr_e( 'ex. Eggs, Milk, etc.', 'cooked' ); ?> ...">
                    </div>

                    <?php do_action( 'cooked_after_ingredient_name_field', false, false ); ?>

                    <button type="button" class="cooked-show-substitution" aria-expanded="false" aria-label="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>" title="<?php esc_attr_e( 'Substitution', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-reverse" aria-hidden="true"></i></button>
                    <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i></button>

                    <div class="cooked-heading-element cooked-substitution-fields">
                        <label class="cooked-sub-label"><?php esc_html_e( 'Substitution:', 'cooked' ); ?></label>
                        <div class="cooked-ingredient-amount">
                            <input type="text" data-ingredient-part="sub_amount" aria-label="<?php esc_attr_e( 'Substitute amount', 'cooked' ); ?>" name="" value="" placeholder="--">
                        </div>
                        <div class="cooked-ingredient-measurement">
                            <select data-ingredient-part="sub_measurement" aria-label="<?php esc_attr_e( 'Substitute measurement', 'cooked' ); ?>" name="">
                                <option value="">--</option>
                                <?php
                                foreach ( $measurements as $key => $measurement ) :
                                    echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $measurement['plural_abbr'] ) . '</option>';
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="cooked-ingredient-name">
                            <input type="text" data-ingredient-part="sub_name" aria-label="<?php esc_attr_e( 'Substitute item', 'cooked' ); ?>" name="" value="" placeholder="<?php esc_attr_e( 'ex. Apple Sauce, Tofu, etc.', 'cooked' ); ?> ...">
                        </div>
                    </div>

                </div>
                <div class="recipe-setting-block cooked-template cooked-heading-template cooked-clearfix">
                    <i class="cooked-icon cooked-icon-drag"></i>
                    <div class="cooked-heading-name">
                        <input type="text" data-ingredient-part="section_heading_name" aria-label="<?php esc_attr_e( 'Section heading', 'cooked' ); ?>" name="" value="" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                    </div>

                    <div class="cooked-heading-element">
                        <label><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                        <select class="cooked-heading-element-select" data-ingredient-part="section_heading_element" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" name="">
                            <?php
                            $heading_elements = [
                                'div' => 'div (default)',
                                'h2'  => 'h2',
                                'h3'  => 'h3',
                                'h4'  => 'h4',
                                'h5'  => 'h5',
                                'h6'  => 'h6',
                            ];
                            foreach ( $heading_elements as $element => $label ) :
								?>
                                <option value="<?php echo esc_attr( $element ); ?>" <?php selected( 'div', $element ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="cooked-show-heading-element" aria-expanded="false" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" title="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-pencil" aria-hidden="true"></i></button>

                    <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i></button>
                </div>
                <!-- END TEMPLATES -->

            </div>
        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-directions">

            <div id="cooked-directions-builder" class="cooked-sortable">

                <?php if ( isset( $recipe_settings['directions'] ) && ! empty( $recipe_settings['directions'] ) ) : ?>

                    <?php $direction_step = 0; foreach ( $recipe_settings['directions'] as $dir_key => $value ) : ?>

                        <?php if ( ! isset( $value['section_heading_name'] ) ) : ?>

                            <?php
                            ++$direction_step; if ( isset( $value['image'] ) && $value['image'] ) {
                                $image_thumb = wp_get_attachment_image(
                                    $value['image'],
                                    'thumbnail',
                                    false,
                                    [
										'class'   => 'cooked-direction-img',
										'data-id' => esc_attr( $dir_key ),
										'data-direction-part' => 'image_src',
										'id'      => 'direction-' . esc_attr( $dir_key ) . '-image-src',
									]
                                );
                            } else {
                                $image_thumb = false;
                            }
                            ?>

                            <div class="recipe-setting-block cooked-direction-block cooked-clearfix cooked-direction-has-number<?php echo ( $direction_step > 9 ? ' cooked-direction-has-number-wide' : '' ); ?>">
                                <i class="cooked-icon cooked-icon-drag"></i>
                                <span class="cooked-direction-number"><?php echo (int) $direction_step; ?></span>
                                <div class="cooked-direction-image<?php echo ( $image_thumb ? ' cooked-has-image' : '' ); ?>">
                                    <input data-direction-part="image" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][image]" id="direction-<?php echo esc_attr( $dir_key ); ?>-image" value="
                                    <?php
                                    if ( isset( $value['image'] ) ) {
										echo esc_attr( $value['image'] );}
									?>
                                    " />
                                    <input data-direction-part="image_button" type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="button direction-image-button" value="<?php echo ( $image_thumb ? esc_html__( 'Change Image', 'cooked' ) : esc_html__( 'Add Image', 'cooked' ) ); ?>" />
                                    <?php echo ( $image_thumb ? wp_kses_post( $image_thumb ) : '<img id="direction-' . esc_attr( $dir_key ) . '-image-src" data-direction-part="image_src" class="cooked-direction-img" src="" data-id="' . esc_attr( $dir_key ) . '">' ); ?>
                                    <div class="cooked-direction-img-placeholder"></div>
                                    <a href="#" data-id="<?php echo esc_attr( $dir_key ); ?>" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
                                </div>
                                <div class="cooked-direction-content">
                                    <textarea data-direction-part="content" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][content]"><?php echo ! empty( $value['content'] ) ? esc_html( $value['content'] ) : ''; ?></textarea>
                                </div>
                                <div class="cooked-direction-video<?php echo ( ! empty( $value['video'] ) ? ' cooked-has-video' : '' ); ?>">
                                    <input data-direction-part="video" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][video]" value="<?php echo ! empty( $value['video'] ) ? esc_attr( $value['video'] ) : ''; ?>" />
                                    <input data-direction-part="video_button" type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="button direction-video-button" value="<?php echo ! empty( $value['video'] ) ? esc_html__( 'Change Video', 'cooked' ) : esc_html__( 'Add Video', 'cooked' ); ?>" />
                                    <?php if ( ! empty( $value['video'] ) ) : ?>
                                        <video class="cooked-direction-video-preview" src="<?php echo esc_url( wp_get_attachment_url( $value['video'] ) ); ?>" preload="metadata" playsinline></video>
                                    <?php else : ?>
                                        <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline style="display:none;"></video>
                                    <?php endif; ?>
                                    <div class="cooked-direction-video-placeholder"></div>
                                    <a href="#" data-id="<?php echo esc_attr( $dir_key ); ?>" class="remove-video-button"><i class="cooked-icon cooked-icon-times"></i></a>
                                </div>
                                <a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
                            </div>

                        <?php elseif ( isset( $value['section_heading_name'] ) ) : ?>

                            <div class="recipe-setting-block cooked-direction-block cooked-direction-heading cooked-clearfix">
                                <i class="cooked-icon cooked-icon-drag"></i>
                                <div class="cooked-heading-name">
                                    <input type="text" data-direction-part="section_heading_name" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][section_heading_name]" value="<?php echo esc_attr( $value['section_heading_name'] ); ?>" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                                </div>

                                <div class="cooked-heading-element">
                                    <label for="cooked-heading-element-select"><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                                    <select class="cooked-heading-element-select" data-direction-part="section_heading_element" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][section_heading_element]">
                                        <?php
                                        $heading_elements = [
                                            'div' => 'div (default)',
                                            'h2'  => 'h2',
                                            'h3'  => 'h3',
                                            'h4'  => 'h4',
                                            'h5'  => 'h5',
                                            'h6'  => 'h6',
                                        ];
                                        foreach ( $heading_elements as $element => $label ) :
											?>
                                            <option value="<?php echo esc_attr( $element ); ?>" <?php selected( isset( $value['section_heading_element'] ) ? $value['section_heading_element'] : 'div', $element ); ?>>
                                                <?php echo esc_html( $label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <span href="#" class="cooked-show-heading-element"><i class="cooked-icon cooked-icon-pencil"></i></span>

                                <a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
                            </div>

                        <?php endif; ?>

                    <?php endforeach; ?>

					<?php
                else :

                    $random_key = wp_rand( 1000000, 9999999 );
                    ?>

                    <div class="recipe-setting-block cooked-direction-block cooked-clearfix cooked-direction-has-number">
                        <i class="cooked-icon cooked-icon-drag"></i>
                        <span class="cooked-direction-number">1</span>
                        <div class="cooked-direction-image">
                            <input data-direction-part="image" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $random_key ); ?>][image]" id="direction-<?php echo esc_attr( $random_key ); ?>-image" value="" />
                            <input data-direction-part="image_button" type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="button direction-image-button" value="<?php esc_attr_e( 'Add Image', 'cooked' ); ?>" />
                            <img id="direction-<?php echo esc_attr( $random_key ); ?>-image-src" data-direction-part="image_src" class="cooked-direction-img" src="" data-id="<?php echo esc_attr( $random_key ); ?>">
                            <div class="cooked-direction-img-placeholder"></div>
                            <a href="#" data-id="<?php echo esc_attr( $random_key ); ?>" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
                        </div>
                        <div class="cooked-direction-content">
                            <?php if ( $wp_editor_roles_allowed ) : ?>
                                <?php
                                    wp_editor(
                                        '',
                                        'direction-' . esc_attr( $random_key ) . '-content',
                                        [
											'teeny'     => true,
											'media_buttons' => false,
											'wpautop'   => false,
											'editor_height' => 250,
											'textarea_name' => '_recipe_settings[directions][' . esc_attr( $random_key ) . '][content]',
											'quicktags' => true,
										]
                                    );
                                ?>
                            <?php else : ?>
                                <textarea id="direction-<?php echo esc_attr( $random_key ); ?>-content" data-direction-part="content" name="" data-id="<?php echo esc_attr( $random_key ); ?>"></textarea>
                            <?php endif; ?>
                        </div>
                        <div class="cooked-direction-video">
                            <input data-direction-part="video" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $random_key ); ?>][video]" value="" />
                            <input data-direction-part="video_button" type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="button direction-video-button" value="<?php esc_attr_e( 'Add Video', 'cooked' ); ?>" />
                            <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline style="display:none;"></video>
                            <div class="cooked-direction-video-placeholder"></div>
                            <a href="#" data-id="<?php echo esc_attr( $random_key ); ?>" class="remove-video-button"><i class="cooked-icon cooked-icon-times"></i></a>
                        </div>
                        <a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
                    </div>

                <?php endif; ?>

            </div>

            <div class="recipe-setting-block">

                <p class="cooked-builder-buttons">
                    <?php do_action( 'cooked_direction_buttons_start' ); ?>
                    <a href="#" class="button cooked-add-direction-button"><?php esc_html_e( 'Add Direction', 'cooked' ); ?></a>
                    <a href="#" class="button cooked-add-heading-button"><?php esc_html_e( 'Add Section Heading', 'cooked' ); ?></a>
                    <?php do_action( 'cooked_direction_buttons_end' ); ?>
                    <a href="#" class="button cooked-bulk-add-button" data-type="directions"><?php esc_html_e( 'Bulk Add', 'cooked' ); ?></a>
                </p>

                <!-- TEMPLATES -->
                <div class="recipe-setting-block cooked-template cooked-direction-template cooked-clearfix">
                    <i class="cooked-icon cooked-icon-drag"></i>
                    <span class="cooked-direction-number"></span>
                    <div class="cooked-direction-image">
                        <input data-direction-part="image" type="hidden" name="" value="" />
                        <input data-direction-part="image_button" data-id="" type="button" class="button direction-image-button" value="<?php esc_attr_e( 'Add Image', 'cooked' ); ?>" />
                        <img id="" data-direction-part="image_src" class="cooked-direction-img" src="">
                        <div class="cooked-direction-img-placeholder"></div>
                        <a href="#" data-id="" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a>
                    </div>
                    <div class="cooked-direction-content">
                        <textarea id="" data-direction-part="content" name=""></textarea>
                    </div>
                    <div class="cooked-direction-video">
                        <input data-direction-part="video" type="hidden" name="" value="" />
                        <input data-direction-part="video_button" type="button" data-id="" class="button direction-video-button" value="<?php esc_attr_e( 'Add Video', 'cooked' ); ?>" />
                        <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline style="display:none;"></video>
                        <div class="cooked-direction-video-placeholder"></div>
                        <a href="#" data-id="" class="remove-video-button"><i class="cooked-icon cooked-icon-times"></i></a>
                    </div>
                    <a href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></a>
                </div>
                <div class="recipe-setting-block cooked-template cooked-heading-template cooked-clearfix">
                    <i class="cooked-icon cooked-icon-drag"></i>
                    <div class="cooked-heading-name">
                        <input type="text" data-direction-part="section_heading_name" name="" value="" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                    </div>

                    <div class="cooked-heading-element">
                        <label for="cooked-heading-element-select"><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                        <select class="cooked-heading-element-select" data-direction-part="section_heading_element" name="">
                            <?php
                            $heading_elements = [
                                'div' => 'div (default)',
                                'h2'  => 'h2',
                                'h3'  => 'h3',
                                'h4'  => 'h4',
                                'h5'  => 'h5',
                                'h6'  => 'h6',
                            ];
                            foreach ( $heading_elements as $element => $label ) :
								?>
                                <option value="<?php echo esc_attr( $element ); ?>" <?php selected( 'div', $element ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span href="#" class="cooked-show-heading-element"><i class="cooked-icon cooked-icon-pencil"></i></span>

                    <span href="#" class="cooked-delete-direction"><i class="cooked-icon cooked-icon-times"></i></span>
                </div>
                <!-- END TEMPLATES -->

            </div>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-nutrition">

            <div class="recipe-setting-block">
                <h3 class="cooked-settings-title cooked-bm-10"><?php esc_html_e( 'Nutrition Information', 'cooked' ); ?></h3>
                <div class="cooked-clearfix">
                    <div class="cooked-setting-column-12">

                        <?php
                        $_nutrition_facts = Cooked_Measurements::nutrition_facts();

                        $nut_loops = 0;
                        foreach ( $_nutrition_facts as $nutrition_facts ) :
                            foreach ( $nutrition_facts as $slug => $nf ) :

                                ++$nut_loops;
                                if ( 1 === $nut_loops ) :
									echo '<p class="cooked-measurement-inputs">';
endif;
                                echo '<span class="cooked-measurement-column">';
                                    echo '<label for="' . esc_attr( $slug ) . '" class="cooked-nutrition-label">' . esc_html( $nf['name'] ) . '</label>';
                                    echo '<input type="' . esc_attr( $nf['type'] ) . '"' . ( 'number' === $nf['type'] ? ' step="any"' : '' ) . ' placeholder="--" name="_recipe_settings[nutrition][' . esc_attr( $slug ) . ']" id="' . esc_attr( $slug ) . '" value="' . ( isset( $recipe_settings['nutrition'][ $slug ] ) ? esc_attr( $recipe_settings['nutrition'][ $slug ] ) : '' ) . '" />';
                                echo '</span>';
                                if ( 2 === $nut_loops ) :
									echo '</p>';
									$nut_loops = 0;
endif;
                                if ( isset( $nf['subs'] ) ) :
                                    foreach ( $nf['subs'] as $sub_slug => $sub_nf ) :
                                        ++$nut_loops;
                                        if ( 1 === $nut_loops ) :
											echo '<p class="cooked-measurement-inputs">';
endif;
                                        echo '<span class="cooked-measurement-column">';
                                            echo '<label for="' . esc_attr( $sub_slug ) . '" class="cooked-nutrition-label">' . esc_html( $sub_nf['name'] ) . '</label>';
                                            echo '<input type="' . esc_attr( $sub_nf['type'] ) . '"' . ( 'number' === $nf['type'] ? ' step="any"' : '' ) . ' placeholder="--" name="_recipe_settings[nutrition][' . esc_attr( $sub_slug ) . ']" id="' . esc_attr( $sub_slug ) . '" value="' . ( isset( $recipe_settings['nutrition'][ $sub_slug ] ) ? esc_attr( $recipe_settings['nutrition'][ $sub_slug ] ) : '' ) . '" />';
                                        echo '</span>';
                                        if ( 2 === $nut_loops ) :
											echo '</p>';
											$nut_loops = 0;
endif;
                                    endforeach;
                                endif;
                            endforeach;
                        endforeach;

                        ?>
                    </div>
                    <div class="cooked-setting-column-12">
                        <?php do_action( 'cooked_before_nutrition_information', $post_id ); ?>

                        <section id="cooked-nutrition-label" class="cooked-nut-label-1">

                            <h2><?php esc_html_e( 'Nutrition Facts', 'cooked' ); ?></h2>

                            <ul class="cooked-nut-servings">
                                <?php
                                $nutrition_facts = $_nutrition_facts['top'];
                                foreach ( $nutrition_facts as $slug => $nf ) :
                                    if ( 'serving_size' === $slug ) :
                                        echo '<li class="cooked-serving-size"><strong>' . esc_html( $nf['name'] ) . '</strong> ';
                                            echo '<ul class="cooked-right"><li><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">___</strong></li></ul>';
                                        echo '</li>';
                                    else :
                                        echo '<p><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">___</strong> ' . esc_html( strtolower( $nf['name'] ) ) . '</p>';
                                    endif;
                                endforeach;
                                ?>
                            </ul>

                            <hr class="cooked-nut-hr" />

                            <ul>
                                <li><strong class="cooked-nut-heading"><?php esc_html_e( 'Amount per serving', 'cooked' ); ?></strong></li>

                                <?php
                                $nutrition_facts = $_nutrition_facts['mid'];
                                foreach ( $nutrition_facts as $slug => $nf ) :
                                    echo '<li class="cooked-calories no-after"><strong>' . esc_html( $nf['name'] ) . '</strong> ';
                                        echo '<ul class="cooked-right"><li><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">___</strong></li></ul>';
                                    echo '</li>';
                                endforeach;
                                ?>

                                <li class="cooked-nut-spacer"></li>
                                <li class="cooked-nut-no-border"><strong class="cooked-nut-heading cooked-nut-right"><?php esc_html_e( '% Daily Value *', 'cooked' ); ?></strong></li>

                                <?php
                                $nutrition_facts = $_nutrition_facts['main'];
                                $nut_loops       = 0;

                                foreach ( $nutrition_facts as $slug => $nf ) :
                                    echo '<li>';
                                    echo '<strong>' . esc_html( $nf['name'] ) . '</strong> <strong class="cooked-nut-label" data-labeltype="' . esc_html( $slug ) . '">___</strong>' . ( isset( $nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_html( $slug ) . '_measurement">' . esc_html( $nf['measurement'] ) . '</strong>' : '' );
                                    echo ( isset( $nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $nf['pdv'] ) . '" data-labeltype="' . esc_html( $slug ) . '">0</span>%</strong>' : '' );

                                    if ( isset( $nf['subs'] ) ) :
                                        foreach ( $nf['subs'] as $sub_slug => $sub_nf ) :
                                            echo '<ul>';
											if ( 'trans_fat' === $sub_slug ) :
												echo '<li>';
													echo wp_kses_post( $sub_nf['nutrition_info_name'] ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">___</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' );
												echo '</li>';
                                                elseif ( 'added_sugars' === $sub_slug ) :
                                                    echo '<ul><li>';
                                                        echo esc_html__( 'Includes', 'cooked' ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">___</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' ) . ' ' . esc_html( $sub_nf['name'] );
                                                        echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $sub_nf['pdv'] ) . '" data-labeltype="' . esc_attr( $sub_slug ) . '">0</span>%</strong>' : '' );
                                                    echo '</li></ul>';
                                                else :
                                                    echo '<li>';
                                                    echo esc_html( $sub_nf['name'] ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">___</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' );
                                                    echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $sub_nf['pdv'] ) . '" data-labeltype="' . esc_attr( $sub_slug ) . '">0</span>%</strong>' : '' );
                                                    echo '</li>';
                                                endif;
												echo '</ul>';
                                        endforeach;
                                    endif;
                                    echo '</li>';
                                endforeach;
                                ?>
                            </ul>

                            <hr class="cooked-nut-hr" />

                            <ul class="cooked-nut-bottom cooked-clearfix">
                                <?php
                                $nutrition_facts = $_nutrition_facts['bottom'];
                                foreach ( $nutrition_facts as $slug => $nf ) :
                                    echo '<li>';
                                        echo esc_html( $nf['name'] ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">___</strong>' . ( isset( $nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '_measurement">' . esc_html( $nf['measurement'] ) . '</strong>' : '' );
                                        echo ( isset( $nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $nf['pdv'] ) . '" data-labeltype="' . esc_attr( $slug ) . '">0</span>%</strong>' : '' );
                                    echo '</li>';
                                endforeach;
                                ?>
                            </ul>

                            <ul>
                                <li class="cooked-nut-spacer"></li>
                            </ul>

                            <p class="cooked-daily-value-text">* <?php esc_html_e( 'The % Daily Value (DV) tells you how much a nutrient in a serving of food contributes to a daily diet. 2,000 calories a day is used for general nutrition advice.', 'cooked' ); ?></p>

                        </section>
                    </div>
                </div>
            </div>

            <input type="hidden" name="_recipe_settings[nutrition][etag]" value="<?php echo ! empty( $recipe_settings['nutrition']['etag'] ) ? esc_attr( $recipe_settings['nutrition']['etag'] ) : ''; ?>" />

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-gallery">

            <?php $cooked_gallery_types = Cooked_Recipes::gallery_types(); ?>

            <div class="recipe-setting-block">
                <h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Gallery Type', 'cooked' ); ?></h3>
                <select id="cooked_gallery_type" name="_recipe_settings[gallery][type]">
                    <?php
                    foreach ( $cooked_gallery_types as $slug => $gtype ) :
                        echo '<option value="' . esc_attr( $slug ) . '"' . ( isset( $recipe_settings['gallery']['type'] ) && $recipe_settings['gallery']['type'] === $slug ? ' selected' : '' ) . '>' . esc_attr( $gtype['title'] ) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>

            <?php
            foreach ( $cooked_gallery_types as $slug => $gtype ) :
                if ( isset( $gtype['posts'] ) && ! empty( $gtype['posts'] ) ) :
					?>
                    <div class="recipe-setting-block cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="<?php echo esc_attr( $slug ); ?>">
                        <h3 class="cooked-settings-title"><?php echo esc_html( $gtype['title'] ); ?></h3>
                        <select id="cooked_gallery_type" name="_recipe_settings[gallery][<?php echo esc_attr( $slug ); ?>]">
                            <option value=""><?php esc_html_e( 'Choose one...', 'cooked' ); ?></option>
                            <?php
                            foreach ( $gtype['posts'] as $gid => $g ) :
                                echo '<option value="' . esc_attr( $gid ) . '"' . ( isset( $recipe_settings['gallery'][ $slug ] ) && (string) $recipe_settings['gallery'][ $slug ] === (string) $gid ? ' selected' : '' ) . '>' . esc_attr( $g ) . '</option>';
                            endforeach;
                            ?>
                        </select>
                    </div>
					<?php
                endif;
            endforeach;
            ?>

            <div class="recipe-setting-block cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">

                <div class="recipe-setting-block cooked-bm-15">
                    <h3 class="cooked-settings-title">
                    <?php
                        /* translators: a title for the video section of the recipe editor, where users can paste a YouToub or Vimeo URL into the field below. */
                        printf( esc_html__( '%1$s or %2$s Video', 'cooked' ), 'YouTube', 'Vimeo' );
                    ?>
                    </h3>
                    <p>
                    <?php
                        /* translators: a message describing how to display a video from YouTube or Vimeo. */
                        printf( esc_html__( 'If you would like to display a video as the first item in your gallery, you can paste a valid %1$s or %2$s URL below.', 'cooked' ), 'YouTube', 'Vimeo' );
                    ?>
                    </p>
                    <input type="text" name="_recipe_settings[gallery][video_url]" value="<?php echo ( isset( $recipe_settings['gallery']['video_url'] ) && $recipe_settings['gallery']['video_url'] ? esc_attr( $recipe_settings['gallery']['video_url'] ) : '' ); ?>" placeholder="ex. https://www.youtube.com/watch?v=abc123">
                </div>

                <h3 class="cooked-settings-title"><?php esc_html_e( 'Gallery Items', 'cooked' ); ?></h3>
                <div id="cooked-recipe-image-gallery" class="cooked-clearfix">
                <?php

                    $gallery_items = ( isset( $recipe_settings['gallery']['items'] ) && ! empty( $recipe_settings['gallery']['items'] ) ? $recipe_settings['gallery']['items'] : array() );
				if ( ! empty( $gallery_items ) ) :
					foreach ( $gallery_items as $g_item ) :
						$image_thumb = wp_get_attachment_image( $g_item, 'thumbnail' );
						$image_title = get_the_title( $g_item );

						echo '<div data-attachment-id="' . esc_attr( $g_item ) . '" class="cooked-recipe-gallery-item">' . wp_kses_post( $image_thumb ) . '<span class="cooked-gallery-item-title">' . esc_html( $image_title ) . '</span><input type="hidden" name="_recipe_settings[gallery][items][]" value="' . esc_attr( $g_item ) . '" /><a href="#" data-attachment-id="' . esc_attr( $g_item ) . '" class="cooked-gallery-edit-button"><i class="cooked-icon cooked-icon-pencil"></i></a><a href="#" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a></div>';

                        endforeach;
                    endif;

                ?>
                </div>
                <input type="button" class="button cooked-gallery-add-button" value="<?php esc_attr_e( 'Add to Gallery', 'cooked' ); ?>" />
            </div>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-shortcodes">

            <div class="recipe-setting-block">

                <?php do_action( 'cooked_recipe_shortcodes_before' ); ?>

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
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "include and exclude" section title */
                                    printf( esc_html__( '"%1$s" and "%2$s"', 'cooked' ), 'include', 'exclude' );
                                ?>
                                </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to include or exclude content from the shortcode output.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='include="author,total_time"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "left and right" section title */
                                    printf( esc_html__( '"%1$s" and "%2$s"', 'cooked' ), 'left', 'right' );
								?>
                                    </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Used like "include", but will position the content to the left or right.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='left="author" right="total_time"' />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">

                            <?php
                            $available_cooked_info_vars = apply_filters(
                                'cooked_available_info_vars',
                                [
									'author'           => __( 'Author', 'cooked' ),
									'prep_time'        => __( 'Prep Time', 'cooked' ),
									'cook_time'        => __( 'Cook Time', 'cooked' ),
									'total_time'       => __( 'Total Time', 'cooked' ),
									'difficulty_level' => __( 'Difficulty', 'cooked' ),
									'servings'         => __( 'Servings Switcher', 'cooked' ),
									'taxonomies'       => __( 'Category', 'cooked' ),
									'print'            => esc_html__( 'Print Mode', 'cooked' ),
									'fullscreen'       => __( 'Full-Screen Mode', 'cooked' ),
								]
                            );

                            foreach ( $available_cooked_info_vars as $var => $name ) :
                                echo '<strong>' . esc_html( $var ) . '</strong> (' . esc_html( $name ) . ')<br>';
                            endforeach;
                            ?>

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
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the checkboxes:', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='checkboxes=false' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>true</strong> (<?php esc_html_e( 'Show checkboxes', 'cooked' ); ?>)<br>
                            <strong>false</strong> (<?php esc_html_e( 'Hide checkboxes', 'cooked' ); ?>)
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
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the numbers:', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='numbers=false' />
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>true</strong> (<?php esc_html_e( 'Show numbers', 'cooked' ); ?>)<br>
                            <strong>false</strong> (<?php esc_html_e( 'Hide numbers', 'cooked' ); ?>)
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
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
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
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
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
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the width of the gallery.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='width="350px"' />
                                    </div>
                                </div>
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"ratio"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the image size ratio.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='ratio="500/500"' />
                                    </div>
                                </div>
                            </div>

                            <div class="cooked-clearfix">
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"nav"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Set the navigation style.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='nav="thumbs"' />
                                    </div>
                                </div>
                                <div class="cooked-setting-column-12">
                                    <p class="cooked-bm-5"><strong>"allowfullscreen"</strong></p>
                                    <p class="cooked-bm-10"><?php esc_html_e( 'Enable or disable "Full-Screen" mode.', 'cooked' ); ?></p>
                                    <div class="cooked-bm-20 cooked-block">
                                        <input class='cooked-shortcode-field' type='text' readonly value='allowfullscreen="true"' />
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="cooked-setting-column-13">
                            <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                            <p class="cooked-bm-10">
                                <strong>width</strong><br>
                                <?php
                                    /* translators: related to the width of  slideshows: "80% or 300px" section title */
                                    printf( esc_html__( 'ex: "%1$s" or "%2$s"', 'cooked' ), '80%', '300px' );
                                ?>
                                <br><br>
                                <strong>ratio</strong><br>
                                <?php
                                    /* translators: related to the image ratio for slideshows: "ex: 800/600" section title */
                                    printf( esc_html__( 'ex: "%s"', 'cooked' ), '800/600' );
                                ?>
                                <br><br>
                                <strong>nav</strong><br>
                                <?php
                                    /* translators: related to the navigation style for slideshows: "dots, thumbs or false" section title */
                                    printf( esc_html__( '"%1$s", "%2$s", or "%3$s"', 'cooked' ), 'dots', 'thumbs', 'false' );
                                ?>
                                <br><br>
                                <strong>allowfullscreen</strong><br>
                                <?php
                                    /* translators: related to allowing full screen for slideshows: "true or false" section title */
                                    printf( esc_html__( '"%1$s" or "%2$s"', 'cooked' ), 'true', 'false' );
                                ?>
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
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <em><?php esc_html_e( 'None', 'cooked' ); ?></em>
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-notes] -->
                <div class="cooked-clearfix">

                <div class="cooked-setting-column-23">

                    <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Notes', 'cooked' ); ?></h3>
                    <p class="cooked-bm-10"><?php esc_html_e( 'This will display the notes, if one is available from the "Layout & Content" tab.', 'cooked' ); ?></p>
                    <div class="cooked-bm-20 cooked-block">
                        <input class='cooked-shortcode-field' type='text' readonly value='[cooked-notes]' />
                    </div>

                    <p class="cooked-bm-5"><strong>"show_header"</strong></p>
                    <p class="cooked-bm-10"><?php esc_html_e( 'This will allow you to hide or show the header for the notes section:', 'cooked' ); ?></p>
                    <div class="cooked-bm-20 cooked-block">
                        <input class='cooked-shortcode-field' type='text' readonly value='show_header=true' />
                    </div>

                </div>

                <div class="cooked-setting-column-13">
                    <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                    <p class="cooked-bm-10">
                        <strong>true</strong> (<?php esc_html_e( 'Show header', 'cooked' ); ?>)<br>
                        <strong>false</strong> (<?php esc_html_e( 'Hide header', 'cooked' ); ?>)
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
                            <input class='cooked-shortcode-field' type='text' readonly value='<?php echo ( shortcode_exists( 'cooked-timer' ) ? '[cooked-timer minutes="5"]5 Minutes[/cooked-timer]' : '[timer minutes=5]5 Minutes[/timer]' ); ?>' />
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>
                                <?php
                                    /* translators: "seconds, minutes and hours" section title */
                                    printf( esc_html__( '"%1$s", "%2$s" and "%3$s"', 'cooked' ), 'seconds', 'minutes', 'hours' );
                                ?>
                                </strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Use just one or a combination of all three to set the timer length', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='minutes="5" seconds="30"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"desc"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Add a short description for this timer, if applicable.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='desc="Boil for 20 minutes"' />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>seconds</strong> (<?php esc_html_e( 'Time in seconds', 'cooked' ); ?>)<br>
                            <strong>minutes</strong> (<?php esc_html_e( 'Time in minutes', 'cooked' ); ?>)<br>
                            <strong>hours</strong> (<?php esc_html_e( 'Time in hours', 'cooked' ); ?>)<br>
                            <strong>desc</strong> (<?php esc_html_e( 'Timer Description', 'cooked' ); ?>)
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-next-previous] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Next & Previous Recipe Navigation', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This will display navigation links to the next and previous recipes.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-next-previous]' />
                        </div>
                        <p class="cooked-bm-10"><?php esc_html_e( 'This shortcode automatically displays the previous and next recipe links based on the current recipe page. No parameters are required.', 'cooked' ); ?></p>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Usage', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <?php esc_html_e( 'Simply add the shortcode to your recipe template or content area. The navigation will automatically show the previous and next recipes based on recipe ID order.', 'cooked' ); ?>
                        </p>
                    </div>

                </div>

                <hr class="cooked-hr">

                <!-- [cooked-related-recipes] -->
                <div class="cooked-clearfix">

                    <div class="cooked-setting-column-23">

                        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Related Recipes', 'cooked' ); ?></h3>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Displays a grid of related recipes based on shared terms in any of the recipe taxonomies (categories, cuisines, cooking methods, tags, diets), shown in random order. No cache or pre-calculation.', 'cooked' ); ?></p>
                        <div class="cooked-bm-20 cooked-block">
                            <input class='cooked-shortcode-field' type='text' readonly value='[cooked-related-recipes]' />
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"id"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Specify a recipe ID. If not provided, uses the current recipe.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='id="123"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"include_ids"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Comma-separated recipe IDs to pin at the start of the grid. Remaining slots are filled with related recipes.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='include_ids="101,102"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"title"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Set the heading text for the related recipes section.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='title="You May Also Like"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"limit"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Number of recipes to display (default: 4).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='limit="4"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"columns"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Number of columns in the grid (default: 2).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='columns="2"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_image"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card images.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_image="true"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_excerpt"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card excerpts.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_excerpt="true"' />
                                </div>
                            </div>
                        </div>

                        <div class="cooked-clearfix">
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"hide_author"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Hide recipe card authors.', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='hide_author="true"' />
                                </div>
                            </div>
                            <div class="cooked-setting-column-12">
                                <p class="cooked-bm-5"><strong>"match_*"</strong></p>
                                <p class="cooked-bm-10"><?php esc_html_e( 'Toggle which taxonomies are used: match_categories, match_cuisines, match_cooking_methods, match_tags, match_diets (default: true).', 'cooked' ); ?></p>
                                <div class="cooked-bm-20 cooked-block">
                                    <input class='cooked-shortcode-field' type='text' readonly value='match_categories="true"' />
                                </div>
                            </div>
                        </div>

                        <p class="cooked-bm-5"><strong><?php esc_html_e( 'Matching options:', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10"><?php esc_html_e( 'Set to false to exclude a taxonomy from related recipes, e.g. match_cuisines="false" to ignore cuisines.', 'cooked' ); ?></p>

                    </div>

                    <div class="cooked-setting-column-13">
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Available Variables', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <strong>id</strong> (<?php esc_html_e( 'Recipe ID', 'cooked' ); ?>)<br>
                            <strong>include_ids</strong> (<?php esc_html_e( 'Pinned recipe IDs', 'cooked' ); ?>)<br>
                            <strong>title</strong> (<?php esc_html_e( 'Section heading', 'cooked' ); ?>)<br>
                            <strong>limit</strong> (<?php esc_html_e( 'Number of recipes', 'cooked' ); ?>)<br>
                            <strong>columns</strong> (<?php esc_html_e( 'Grid columns', 'cooked' ); ?>)<br>
                            <strong>hide_image</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>hide_excerpt</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>hide_author</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)<br>
                            <strong>match_*</strong> (<?php esc_html_e( 'true/false', 'cooked' ); ?>)
                        </p>
                        <p class="cooked-bm-10 cooked-tm-10"><strong class="cooked-heading"><?php esc_html_e( 'Example', 'cooked' ); ?></strong></p>
                        <p class="cooked-bm-10">
                            <code>[cooked-related-recipes include_ids="101,102" limit="4" columns="2" title="Related Recipes"]</code>
                        </p>
                    </div>

                </div>

                <?php do_action( 'cooked_recipe_shortcodes_after' ); ?>

            </div>

        </section>

        <?php do_action( 'cooked_recipe_tabs_after' ); ?>

    </div>

    <?php Cooked_Recipe_Meta::bulk_add_modal(); ?>

	<?php
}

add_action( 'cooked_recipe_fields', 'cooked_render_recipe_fields', 10 );
