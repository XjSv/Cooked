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
 * One substitute's fields in the recipe editor: amount, measurement, name and a remove button.
 *
 * @since 2.0.0
 * @param string|int|false                                         $ing_key      Ingredient key, or false in the template (the builder script names it).
 * @param int                                                      $index        Position in the ingredient's substitutions.
 * @param array{amount: string, measurement: string, name: string} $substitution Values.
 * @param array<string, array<string, mixed>>                      $measurements Measurement units.
 * @return void
 */
function cooked_render_substitution_row( $ing_key, $index, $substitution, $measurements ) {
    $field_name = function ( $part ) use ( $ing_key, $index ) {
        return false === $ing_key ? '' : '_recipe_settings[ingredients][' . $ing_key . '][substitutions][' . $index . '][' . $part . ']';
    };
    ?>
    <div class="cooked-substitution-row" data-or="<?php echo esc_attr_x( 'or', 'ingredient substitution', 'cooked' ); ?>">
        <div class="cooked-ingredient-amount">
            <input type="text" data-substitution-part="amount" aria-label="<?php esc_attr_e( 'Substitute amount', 'cooked' ); ?>" name="<?php echo esc_attr( $field_name( 'amount' ) ); ?>" value="<?php echo esc_attr( $substitution['amount'] ); ?>" placeholder="--">
        </div>
        <div class="cooked-ingredient-measurement">
            <select data-substitution-part="measurement" aria-label="<?php esc_attr_e( 'Substitute measurement', 'cooked' ); ?>" name="<?php echo esc_attr( $field_name( 'measurement' ) ); ?>">
                <option value="">--</option>
                <?php foreach ( $measurements as $key => $measurement ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $substitution['measurement'], (string) $key ); ?>><?php echo esc_html( $measurement['plural_abbr'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cooked-ingredient-name">
            <input type="text" data-substitution-part="name" aria-label="<?php esc_attr_e( 'Substitute item', 'cooked' ); ?>" name="<?php echo esc_attr( $field_name( 'name' ) ); ?>" value="<?php echo esc_attr( $substitution['name'] ); ?>" placeholder="<?php esc_attr_e( 'ex. Apple Sauce, Tofu, etc.', 'cooked' ); ?> ...">
        </div>
        <button type="button" class="cooked-delete-substitution" aria-label="<?php esc_attr_e( 'Remove substitute', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove substitute', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
    </div>
    <?php
}

/**
 * An ingredient's substitutes in the recipe editor, each an "or" line, and an Add button.
 *
 * @since 2.0.0
 * @param string|int|false                                                     $ing_key       Ingredient key, or false in the template.
 * @param array<int, array{amount: string, measurement: string, name: string}> $substitutions Substitutes.
 * @param array<string, array<string, mixed>>                                  $measurements  Measurement units.
 * @return void
 */
function cooked_render_substitution_fields( $ing_key, $substitutions, $measurements ) {
    ?>
    <div class="cooked-heading-element cooked-substitution-fields">
        <span class="cooked-sub-label"><?php esc_html_e( 'Substitutions:', 'cooked' ); ?></span>
        <?php
        foreach ( array_values( $substitutions ) as $index => $substitution ) {
            cooked_render_substitution_row( $ing_key, $index, $substitution, $measurements );
        }
        ?>
        <button type="button" class="cooked-add-substitution"><?php esc_html_e( 'Add substitute', 'cooked' ); ?></button>
    </div>
    <?php
}

/**
 * Recipe Fields
 *
 * @since 1.0.0
 * @param int $post_id Recipe ID.
 * @return void
 */
function cooked_render_recipe_fields( $post_id ) {
    global $_cooked_settings;
    $recipe_settings = get_post_meta( $post_id, '_recipe_settings', true );

    // Backwards Compatibility with Cooked 2.x.
    $c2_recipe_settings     = Cooked_Recipes::get_c2_recipe_meta( $post_id );
    $recipe_review_required = false;

    // Classic layout: this recipe's shortcodes at the end of the Shortcodes tab, once it is saved
    // (the new layout lists them itself, in frame/recipe-shortcodes.php).
    if ( ! empty( $recipe_settings ) && Cooked_Admin_Enqueues::use_classic_ui() ) :
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
        // New layout: arrows either side scroll the tabs when they don't fit (cooked-admin-functions.js).
        $tab_arrows = ! Cooked_Admin_Enqueues::use_classic_ui();
        if ( $tab_arrows ) :
            echo '<div class="cooked-recipe-tabs-bar">';
            echo '<button type="button" class="cooked-recipe-tabs-arrow is-left" aria-label="' . esc_attr__( 'Scroll tabs left', 'cooked' ) . '" disabled><i class="cooked-icon cooked-icon-angle-left" aria-hidden="true"></i></button>';
        endif;
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
        if ( $tab_arrows ) :
            echo '<button type="button" class="cooked-recipe-tabs-arrow is-right" aria-label="' . esc_attr__( 'Scroll tabs right', 'cooked' ) . '" disabled><i class="cooked-icon cooked-icon-angle-right" aria-hidden="true"></i></button>';
            echo '</div>';
        endif;

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
                            <?php $has_sub = ! empty( Cooked_Recipes::ingredient_substitutions( $value ) ); ?>

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
                                <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>

                                <?php cooked_render_substitution_fields( $ing_key, Cooked_Recipes::ingredient_substitutions( $value ), $measurements ); ?>

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

                                <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
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
                        <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>

                        <?php cooked_render_substitution_fields( $random_key, [], $measurements ); ?>

                    </div>

                <?php endif; ?>

            </div>

            <div class="recipe-setting-block">

                <p class="cooked-builder-buttons">
                    <?php do_action( 'cooked_ingredient_buttons_start' ); ?>
                    <button type="button" class="button cooked-add-ingredient-button"><?php esc_html_e( 'Add Ingredient', 'cooked' ); ?></button>
                    <button type="button" class="button cooked-add-heading-button"><?php esc_html_e( 'Add Section Heading', 'cooked' ); ?></button>
                    <?php do_action( 'cooked_ingredient_buttons_end' ); ?>
                    <button type="button" class="button cooked-bulk-add-button" data-type="ingredients"><?php esc_html_e( 'Bulk Add', 'cooked' ); ?></button>
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
                    <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>

                    <?php cooked_render_substitution_fields( false, [], $measurements ); ?>

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

                    <button type="button" class="cooked-delete-ingredient" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                </div>
                <div class="cooked-template cooked-substitution-row-template">
                    <?php
                    cooked_render_substitution_row(
                        false,
                        0,
                        [
                            'amount'      => '',
                            'measurement' => '',
                            'name'        => '',
                        ],
                        $measurements
                    );
                    ?>
                </div>
                <!-- END TEMPLATES -->

            </div>
        </section>

        <section class="cooked-recipe-tab-content<?php echo Cooked_Admin_Enqueues::use_classic_ui() ? '' : ' cooked-recipe-fields'; ?>" id="cooked-recipe-tab-content-directions">

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
                                    <input data-direction-part="image" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][image]" id="direction-<?php echo esc_attr( $dir_key ); ?>-image" value="<?php echo isset( $value['image'] ) ? esc_attr( $value['image'] ) : ''; ?>" />
                                    <input data-direction-part="image_button" type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="button direction-image-button" value="<?php echo ( $image_thumb ? esc_html__( 'Change Image', 'cooked' ) : esc_html__( 'Add Image', 'cooked' ) ); ?>" />
                                    <?php echo ( $image_thumb ? wp_kses_post( $image_thumb ) : '<img id="direction-' . esc_attr( $dir_key ) . '-image-src" data-direction-part="image_src" class="cooked-direction-img" src="" data-id="' . esc_attr( $dir_key ) . '">' ); ?>
                                    <div class="cooked-direction-img-placeholder"></div>
                                    <button type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="remove-image-button" aria-label="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                                </div>
                                <div class="cooked-direction-content">
                                    <textarea data-direction-part="content" aria-label="<?php esc_attr_e( 'Step', 'cooked' ); ?>" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][content]"><?php echo ! empty( $value['content'] ) ? esc_html( $value['content'] ) : ''; ?></textarea>
                                </div>
                                <div class="cooked-direction-video<?php echo ( ! empty( $value['video'] ) ? ' cooked-has-video' : '' ); ?>">
                                    <input data-direction-part="video" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][video]" value="<?php echo ! empty( $value['video'] ) ? esc_attr( $value['video'] ) : ''; ?>" />
                                    <input data-direction-part="video_button" type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="button direction-video-button" value="<?php echo ! empty( $value['video'] ) ? esc_html__( 'Change Video', 'cooked' ) : esc_html__( 'Add Video', 'cooked' ); ?>" />
                                    <?php if ( ! empty( $value['video'] ) ) : ?>
                                        <video class="cooked-direction-video-preview" src="<?php echo esc_url( wp_get_attachment_url( $value['video'] ) . '#t=0.1' ); ?>" preload="metadata" playsinline></video>
                                    <?php else : ?>
                                        <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline></video>
                                    <?php endif; ?>
                                    <div class="cooked-direction-video-placeholder"></div>
                                    <button type="button" data-id="<?php echo esc_attr( $dir_key ); ?>" class="remove-video-button" aria-label="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                                </div>
                                <button type="button" class="cooked-delete-direction" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                            </div>

                        <?php elseif ( isset( $value['section_heading_name'] ) ) : ?>

                            <div class="recipe-setting-block cooked-direction-block cooked-direction-heading cooked-clearfix">
                                <i class="cooked-icon cooked-icon-drag"></i>
                                <div class="cooked-heading-name">
                                    <input type="text" data-direction-part="section_heading_name" aria-label="<?php esc_attr_e( 'Section heading', 'cooked' ); ?>" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][section_heading_name]" value="<?php echo esc_attr( $value['section_heading_name'] ); ?>" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                                </div>

                                <div class="cooked-heading-element">
                                    <label><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                                    <select class="cooked-heading-element-select" data-direction-part="section_heading_element" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" name="_recipe_settings[directions][<?php echo esc_attr( $dir_key ); ?>][section_heading_element]">
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

                                <button type="button" class="cooked-delete-direction" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
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
                            <button type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="remove-image-button" aria-label="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
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
											'editor_height' => 150,
											'textarea_name' => '_recipe_settings[directions][' . esc_attr( $random_key ) . '][content]',
											'quicktags' => true,
										]
                                    );
                                ?>
                            <?php else : ?>
                                <textarea id="direction-<?php echo esc_attr( $random_key ); ?>-content" data-direction-part="content" aria-label="<?php esc_attr_e( 'Step', 'cooked' ); ?>" name="" data-id="<?php echo esc_attr( $random_key ); ?>"></textarea>
                            <?php endif; ?>
                        </div>
                        <div class="cooked-direction-video">
                            <input data-direction-part="video" type="hidden" name="_recipe_settings[directions][<?php echo esc_attr( $random_key ); ?>][video]" value="" />
                            <input data-direction-part="video_button" type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="button direction-video-button" value="<?php esc_attr_e( 'Add Video', 'cooked' ); ?>" />
                            <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline></video>
                            <div class="cooked-direction-video-placeholder"></div>
                            <button type="button" data-id="<?php echo esc_attr( $random_key ); ?>" class="remove-video-button" aria-label="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                        </div>
                        <button type="button" class="cooked-delete-direction" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                    </div>

                <?php endif; ?>

            </div>

            <div class="recipe-setting-block">

                <p class="cooked-builder-buttons">
                    <?php do_action( 'cooked_direction_buttons_start' ); ?>
                    <button type="button" class="button cooked-add-direction-button"><?php esc_html_e( 'Add Direction', 'cooked' ); ?></button>
                    <button type="button" class="button cooked-add-heading-button"><?php esc_html_e( 'Add Section Heading', 'cooked' ); ?></button>
                    <?php do_action( 'cooked_direction_buttons_end' ); ?>
                    <button type="button" class="button cooked-bulk-add-button" data-type="directions"><?php esc_html_e( 'Bulk Add', 'cooked' ); ?></button>
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
                        <button type="button" data-id="" class="remove-image-button" aria-label="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove image', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                    </div>
                    <div class="cooked-direction-content">
                        <textarea id="" data-direction-part="content" aria-label="<?php esc_attr_e( 'Step', 'cooked' ); ?>" name=""></textarea>
                    </div>
                    <div class="cooked-direction-video">
                        <input data-direction-part="video" type="hidden" name="" value="" />
                        <input data-direction-part="video_button" type="button" data-id="" class="button direction-video-button" value="<?php esc_attr_e( 'Add Video', 'cooked' ); ?>" />
                        <video class="cooked-direction-video-preview" src="" preload="metadata" playsinline></video>
                        <div class="cooked-direction-video-placeholder"></div>
                        <button type="button" data-id="" class="remove-video-button" aria-label="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove video', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                    </div>
                    <button type="button" class="cooked-delete-direction" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                </div>
                <div class="recipe-setting-block cooked-template cooked-heading-template cooked-clearfix">
                    <i class="cooked-icon cooked-icon-drag"></i>
                    <div class="cooked-heading-name">
                        <input type="text" data-direction-part="section_heading_name" aria-label="<?php esc_attr_e( 'Section heading', 'cooked' ); ?>" name="" value="" placeholder="<?php esc_attr_e( 'Section Heading', 'cooked' ); ?> ...">
                    </div>

                    <div class="cooked-heading-element">
                        <label><?php esc_html_e( 'Heading Element:', 'cooked' ); ?></label>
                        <select class="cooked-heading-element-select" data-direction-part="section_heading_element" aria-label="<?php esc_attr_e( 'Heading element', 'cooked' ); ?>" name="">
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

                    <button type="button" class="cooked-delete-direction" aria-label="<?php esc_attr_e( 'Remove', 'cooked' ); ?>" title="<?php esc_attr_e( 'Remove', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                </div>
                <!-- END TEMPLATES -->

            </div>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-nutrition">

            <?php
            // The Nutrition tab has a classic and a new layout (Settings > General > Admin Screens).
            include COOKED_DIR . 'templates/admin/' . ( Cooked_Admin_Enqueues::use_classic_ui() ? 'classic' : 'frame' ) . '/recipe-nutrition.php';
            ?>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-gallery">

            <?php
            // The Gallery tab has a classic and a new layout (Settings > General > Admin Screens).
            include COOKED_DIR . 'templates/admin/' . ( Cooked_Admin_Enqueues::use_classic_ui() ? 'classic' : 'frame' ) . '/recipe-gallery.php';
            ?>

        </section>

        <section class="cooked-recipe-tab-content" id="cooked-recipe-tab-content-shortcodes">

            <?php
            // The Shortcodes tab has a classic and a new layout (Settings > General > Admin Screens).
            include COOKED_DIR . 'templates/admin/' . ( Cooked_Admin_Enqueues::use_classic_ui() ? 'classic' : 'frame' ) . '/recipe-shortcodes.php';
            ?>

        </section>

        <?php do_action( 'cooked_recipe_tabs_after' ); ?>

    </div>

    <?php Cooked_Recipe_Meta::bulk_add_modal(); ?>

	<?php
}

add_action( 'cooked_recipe_fields', 'cooked_render_recipe_fields', 10 );
