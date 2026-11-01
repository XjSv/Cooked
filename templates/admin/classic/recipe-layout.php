<?php
/**
 * Recipe editor: Layout tab (classic layout)
 *
 * Included by cooked_render_recipe_fields(), which provides $post_id, $recipe_settings,
 * $recipe_review_required and $wp_editor_roles_allowed.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

global $_cooked_settings;
?>
<!-- Allows for backwards compatability features -->
<input type="hidden" name="_recipe_settings[cooked_version]" value="<?php echo esc_attr( COOKED_VERSION ); ?>">

<?php if ( isset( $recipe_review_required ) && $recipe_review_required ) : ?>
    <section class="cooked-alert-block" id="cooked-recipe-tab-content-migration">
        <div class="recipe-setting-block">
            <h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Review Required', 'cooked' ); ?></h3>
            <p>
            <?php
                /* translators: for displaying singular or plural versions depending on the number of recipes. */
                printf( esc_html__( 'It looks like this recipe is from a different version of %s. Please review and click "Update" to save it.', 'cooked' ), 'Cooked' );
            ?>
            </p>
        </div>
    </section>
<?php endif; ?>

<?php if ( isset( $recipe_settings ) && ! empty( $recipe_settings ) ) : ?>
    <div class="recipe-setting-block">
        <h3 class="cooked-settings-title cooked-bm-0"><?php esc_html_e( 'Recipe Shortcode', 'cooked' ); ?></h3>
        <p class="cooked-bm-10"><?php esc_html_e( 'You can use the following shortcode to display your recipe anywhere:', 'cooked' ); ?></p>
        <div class="cooked-bm-30 cooked-block">
            <input style="width:100%;" class="cooked-shortcode-field" type="text" readonly="" value="[cooked-recipe id=&quot;<?php echo intval( $post_id ); ?>&quot;]">
        </div>
    </div>
<?php endif; ?>

<?php do_action( 'cooked_recipe_meta_before_fields', $recipe_settings ); ?>
<?php $nonce = wp_create_nonce( 'cooked_save_default' ); ?>
<?php $nonce_bulk = wp_create_nonce( 'cooked_save_default_bulk' ); ?>

<div class="recipe-setting-block">

    <?php if ( current_user_can( 'edit_cooked_default_template' ) ) : ?>
        <h3 class="cooked-settings-title cooked-bm-30-up"><?php esc_html_e( 'Recipe Template', 'cooked' ); ?><span class="button cooked-layout-save-default" role="button" tabindex="0" aria-expanded="false" aria-controls="cooked-save-default-panel"><?php esc_html_e( 'Save as Default', 'cooked' ); ?></span><span class="button button-cooked-reset cooked-layout-load-default"><?php esc_html_e( 'Reset', 'cooked' ); ?></span><span class="cooked-tooltip cooked-tooltip-icon" data-tooltip="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'Recipe Template', 'cooked' ) . '</strong>' . esc_html__( 'Using the built-in recipe shortcodes found on the "Shortcodes" tab, you can create the layout of your recipe below. Use the "Save as Default" button to save your template.', 'cooked' ) ); ?>" data-positions="top"><i class="cooked-icon cooked-icon-question"></i></span></h3>
        <div id="cooked-save-default-panel" class="cooked-save-default-panel" hidden>
            <strong class="cooked-tooltip-heading"><?php esc_html_e( 'Default Recipe Template', 'cooked' ); ?></strong>
            <p><?php esc_html_e( 'Choose from the options below to use this layout as the default for new recipes or for all recipes.', 'cooked' ); ?></p>
            <span class="cooked-tooltip-buttons"><a href="#" class="cooked-save-default-new button" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Save as Default', 'cooked' ); ?></a><a href="#" class="cooked-save-default-all button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-bulk-nonce="<?php echo esc_attr( $nonce_bulk ); ?>"><?php esc_html_e( 'Apply to All', 'cooked' ); ?></a></span>
            <span id="cooked-template-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span>
            <span id="cooked-template-progress-text" class="cooked-progress-text">0 / 0</span>
        </div>
    <?php endif; ?>

    <div class="recipe-setting-block cooked-bm-30">
        <?php $recipe_content = isset( $recipe_settings['content'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['content'] ) ) : ( isset( $_cooked_settings['default_content'] ) ? wp_unslash( wp_specialchars_decode( $_cooked_settings['default_content'] ) ) : Cooked_Recipes::default_content() ); ?>
        <?php
        wp_editor(
            $recipe_content,
            '_recipe_settings_content',
            [
                'teeny'         => false,
                'media_buttons' => false,
                'wpautop'       => false,
                'editor_height' => 400,
                'textarea_name' => '_recipe_settings[content]',
                'quicktags'     => true,
            ]
        );
        ?>
    </div>

    <div class="recipe-setting-block">
        <h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Excerpt', 'cooked' ); ?><span class="cooked-tooltip cooked-tooltip-icon" data-tooltip="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'Recipe Excerpt', 'cooked' ) . '</strong>' . esc_html__( 'The excerpt is used on recipe listing templates, where the full recipe should not be displayed.', 'cooked' ) ); ?>" data-positions="top"><i class="cooked-icon cooked-icon-question"></i></span></h3>
        <p>
            <?php if ( $wp_editor_roles_allowed ) : ?>
                <?php $recipe_excerpt = isset( $recipe_settings['excerpt'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['excerpt'] ) ) : ''; ?>
                <?php
                wp_editor(
                    $recipe_excerpt,
                    '_recipe_settings_excerpt',
                    [
                        'teeny'         => true,
                        'media_buttons' => true,
                        'wpautop'       => false,
                        'editor_height' => 100,
                        'textarea_name' => '_recipe_settings[excerpt]',
                        'quicktags'     => true,
                    ]
                );
                ?>
            <?php else : ?>
                <textarea name="_recipe_settings[excerpt]"><?php echo ( isset( $recipe_settings['excerpt'] ) ? esc_textarea( $recipe_settings['excerpt'] ) : '' ); ?></textarea>
            <?php endif; ?>
        </p>

    </div>

    <?php if ( ! isset( $_cooked_settings['advanced'] ) || empty( $_cooked_settings['advanced'] ) || ! in_array( 'disable_meta_tags', $_cooked_settings['advanced'], true ) ) : ?>
        <div class="recipe-setting-block">
            <h3 class="cooked-settings-title"><?php esc_html_e( 'SEO Description', 'cooked' ); ?><span class="cooked-tooltip cooked-tooltip-icon" data-tooltip="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'SEO Description', 'cooked' ) . '</strong>' . esc_html__( 'This description is used for SEO purposes and is optional. By default, Cooked will use the Recipe Excerpt above if available or the Recipe Title if not.', 'cooked' ) ); ?>" data-positions="top"><i class="cooked-icon cooked-icon-question"></i></span></h3>
            <p><textarea name="_recipe_settings[seo_description]"><?php echo isset( $recipe_settings['seo_description'] ) ? esc_textarea( wp_specialchars_decode( $recipe_settings['seo_description'], ENT_QUOTES ) ) : ''; ?></textarea></p>
        </div>
    <?php endif; ?>

    <div class="recipe-setting-block">
        <div class="cooked-clearfix">

            <?php $difficulty_levels = Cooked_Recipes::difficulty_levels(); ?>

            <div class="cooked-setting-column-14">
                <h3 class="cooked-settings-title"><?php esc_html_e( 'Difficulty Level', 'cooked' ); ?></h3>
                <select name="_recipe_settings[difficulty_level]">
                    <option value="0">--</option>
                    <?php
                    foreach ( $difficulty_levels as $level => $name ) :
                        echo '<option value="' . esc_attr( $level ) . '"' . ( isset( $recipe_settings['difficulty_level'] ) && (string) $recipe_settings['difficulty_level'] === (string) $level ? ' selected' : '' ) . '>' . esc_html( $name ) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="cooked-setting-column-14">
                <h3 class="cooked-settings-title"><?php esc_html_e( 'Prep Time', 'cooked' ); ?></h3>
                <input id="cooked-prep-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[prep_time]" value="<?php echo ( isset( $recipe_settings['prep_time'] ) && $recipe_settings['prep_time'] ? esc_attr( $recipe_settings['prep_time'] ) : '' ); ?>" placeholder="--">
                <span class="cooked-time-picker-text"><?php esc_html_e( 'minutes', 'cooked' ); ?></span>
            </div>
            <div class="cooked-setting-column-14">
                <h3 class="cooked-settings-title"><?php esc_html_e( 'Cook Time', 'cooked' ); ?></h3>
                <input id="cooked-cook-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[cook_time]" value="<?php echo ( isset( $recipe_settings['cook_time'] ) && $recipe_settings['cook_time'] ? esc_attr( $recipe_settings['cook_time'] ) : '' ); ?>" placeholder="--">
                <span class="cooked-time-picker-text"><?php esc_html_e( 'minutes', 'cooked' ); ?></span>
            </div>
            <div class="cooked-setting-column-14">
                <h3 class="cooked-settings-title"><?php esc_html_e( 'Total Time', 'cooked' ); ?></h3>
                <input id="cooked-total-time" class="cooked-time-picker" type="number" step="any" name="_recipe_settings[total_time]" value="<?php echo ( isset( $recipe_settings['total_time'] ) && $recipe_settings['total_time'] ? esc_attr( $recipe_settings['total_time'] ) : '' ); ?>" placeholder="--">
                <span class="cooked-time-picker-text"><?php esc_html_e( 'minutes', 'cooked' ); ?></span>
            </div>
        </div>
    </div>

    <div class="recipe-setting-block cooked-bm-30">
    <h3 class="cooked-settings-title"><?php esc_html_e( 'Recipe Notes', 'cooked' ); ?><span class="cooked-tooltip cooked-tooltip-icon" data-tooltip="<?php echo esc_attr( '<strong class="cooked-tooltip-heading">' . esc_html__( 'Recipe Notes', 'cooked' ) . '</strong>' . esc_html__( 'The notes are displayed in the recipe.', 'cooked' ) ); ?>" data-positions="top"><i class="cooked-icon cooked-icon-question"></i></span></h3>
        <?php if ( $wp_editor_roles_allowed ) : ?>
            <?php $recipe_notes = isset( $recipe_settings['notes'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['notes'] ) ) : ''; ?>
            <?php
                wp_editor(
                    $recipe_notes,
                    '_recipe_settings_notes',
                    [
                        'teeny'         => false,
                        'media_buttons' => true,
                        'wpautop'       => false,
                        'editor_height' => 100,
                        'textarea_name' => '_recipe_settings[notes]',
                        'quicktags'     => true,
                    ]
                );
            ?>
        <?php else : ?>
            <textarea name="_recipe_settings[notes]"><?php echo ( isset( $recipe_settings['notes'] ) ? esc_textarea( $recipe_settings['notes'] ) : '' ); ?></textarea>
        <?php endif; ?>
    </div>

</div>
