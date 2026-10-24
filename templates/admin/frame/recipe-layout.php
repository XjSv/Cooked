<?php
/**
 * Recipe editor: Layout tab
 *
 * Included by cooked_render_recipe_fields(), which provides $post_id, $recipe_settings,
 * $recipe_review_required and $wp_editor_roles_allowed. Field names and IDs match the classic
 * layout (classic/recipe-layout.php), so saving and the editor scripts work with either.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

global $_cooked_settings;

$cooked_show_seo = ! isset( $_cooked_settings['advanced'] ) || empty( $_cooked_settings['advanced'] ) || ! in_array( 'disable_meta_tags', $_cooked_settings['advanced'], true );

// TinyMCE styles inside the editors' iframes (Enqueues::recipe_editor_content_style()).
$cooked_editor_css   = Cooked_Admin_Enqueues::recipe_editor_content_style();
$cooked_template_css = Cooked_Admin_Enqueues::recipe_editor_content_style( true );

/**
 * Prints a help tooltip (the "?" next to a title), opened on hover or keyboard focus.
 *
 * @param string $title Tooltip heading, also its accessible name.
 * @param string $text  Tooltip text.
 */
$cooked_tooltip = function ( $title, $text ) {
    $content = '<strong class="cooked-tooltip-heading">' . esc_html( $title ) . '</strong>' . esc_html( $text );
    /* translators: %s: a field title, e.g. "Recipe Excerpt". */
    echo '<button type="button" class="cooked-tooltip cooked-tooltip-icon" data-tooltip="' . esc_attr( $content ) . '" data-positions="top" aria-label="' . esc_attr( sprintf( __( 'About %s', 'cooked' ), $title ) ) . '"><i class="cooked-icon cooked-icon-question" aria-hidden="true"></i></button>';
};

$cooked_times = [
    'prep_time'  => [
        'id'    => 'cooked-prep-time',
        'label' => __( 'Prep Time', 'cooked' ),
    ],
    'cook_time'  => [
        'id'    => 'cooked-cook-time',
        'label' => __( 'Cook Time', 'cooked' ),
    ],
    'total_time' => [
        'id'    => 'cooked-total-time',
        'label' => __( 'Total Time', 'cooked' ),
    ],
];
?>
<!-- Allows for backwards compatability features -->
<input type="hidden" name="_recipe_settings[cooked_version]" value="<?php echo esc_attr( COOKED_VERSION ); ?>">

<div class="cooked-recipe-fields">

    <?php if ( isset( $recipe_review_required ) && $recipe_review_required ) : ?>
        <div class="cooked-recipe-notice is-warning" id="cooked-recipe-tab-content-migration" role="status">
            <strong><?php esc_html_e( 'Recipe Review Required', 'cooked' ); ?></strong>
            <?php
            /* translators: %s: Cooked. */
            printf( esc_html__( 'It looks like this recipe is from a different version of %s. Please review and click "Update" to save it.', 'cooked' ), 'Cooked' );
            ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $recipe_settings ) ) : ?>
        <section class="cooked-recipe-section cooked-recipe-section-inline">
            <div class="cooked-recipe-section-head">
                <h3 class="cooked-recipe-section-title"><?php esc_html_e( 'Recipe Shortcode', 'cooked' ); ?></h3>
                <p class="cooked-recipe-section-desc"><?php esc_html_e( 'Use this shortcode to display the recipe anywhere on your site.', 'cooked' ); ?></p>
            </div>
            <div class="cooked-shortcode-copy">
                <code id="cooked-recipe-shortcode">[cooked-recipe id="<?php echo intval( $post_id ); ?>"]</code>
                <button type="button" class="button cooked-copy-code" data-target="cooked-recipe-shortcode"><?php esc_html_e( 'Copy', 'cooked' ); ?></button>
            </div>
        </section>
    <?php endif; ?>

    <?php do_action( 'cooked_recipe_meta_before_fields', $recipe_settings ); ?>

    <section class="cooked-recipe-section">
        <div class="cooked-recipe-section-head">
            <div class="cooked-recipe-section-title-row">
                <h3 class="cooked-recipe-section-title"><?php esc_html_e( 'Recipe Template', 'cooked' ); ?></h3>
                <?php $cooked_tooltip( __( 'Recipe Template', 'cooked' ), __( 'Using the built-in recipe shortcodes found on the "Shortcodes" tab, you can create the layout of your recipe below. Use the "Save as Default" button to save your template.', 'cooked' ) ); ?>
            </div>
            <?php if ( current_user_can( 'edit_cooked_default_template' ) ) : ?>
                <?php $nonce = wp_create_nonce( 'cooked_save_default' ); ?>
                <?php $nonce_bulk = wp_create_nonce( 'cooked_save_default_bulk' ); ?>
                <div class="cooked-recipe-section-actions">
                    <button type="button" class="button cooked-layout-load-default"><?php esc_html_e( 'Reset', 'cooked' ); ?></button>
                    <button type="button" class="button cooked-layout-save-default" aria-expanded="false" aria-controls="cooked-save-default-panel"><?php esc_html_e( 'Save as Default', 'cooked' ); ?></button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( current_user_can( 'edit_cooked_default_template' ) ) : ?>
            <div id="cooked-save-default-panel" class="cooked-save-default-panel" hidden>
                <strong class="cooked-tooltip-heading"><?php esc_html_e( 'Default Recipe Template', 'cooked' ); ?></strong>
                <p><?php esc_html_e( 'Choose from the options below to use this layout as the default for new recipes or for all recipes.', 'cooked' ); ?></p>
                <span class="cooked-tooltip-buttons"><button type="button" class="cooked-save-default-new button" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Save as Default', 'cooked' ); ?></button><button type="button" class="cooked-save-default-all button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-bulk-nonce="<?php echo esc_attr( $nonce_bulk ); ?>"><?php esc_html_e( 'Apply to All', 'cooked' ); ?></button></span>
                <span id="cooked-template-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span>
                <span id="cooked-template-progress-text" class="cooked-progress-text">0 / 0</span>
            </div>
        <?php endif; ?>

        <div class="cooked-recipe-editor-field">
            <?php
            $recipe_content = isset( $recipe_settings['content'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['content'] ) ) : ( isset( $_cooked_settings['default_content'] ) ? wp_unslash( wp_specialchars_decode( $_cooked_settings['default_content'] ) ) : Cooked_Recipes::default_content() );
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
                    'tinymce'       => [ 'content_style' => $cooked_template_css ],
                ]
            );
            ?>
        </div>
    </section>

    <section class="cooked-recipe-section">
        <div class="cooked-recipe-section-head">
            <h3 class="cooked-recipe-section-title"><?php esc_html_e( 'Difficulty & Timing', 'cooked' ); ?></h3>
            <p class="cooked-recipe-section-desc"><?php esc_html_e( 'Total time fills in from the prep and cook times; you can change it.', 'cooked' ); ?></p>
        </div>
        <div class="cooked-recipe-details-grid">
            <div class="cooked-recipe-field">
                <label for="cooked-difficulty-level"><?php esc_html_e( 'Difficulty Level', 'cooked' ); ?></label>
                <select id="cooked-difficulty-level" name="_recipe_settings[difficulty_level]">
                    <option value="0">--</option>
                    <?php
                    foreach ( Cooked_Recipes::difficulty_levels() as $level => $name ) :
                        echo '<option value="' . esc_attr( $level ) . '"' . ( isset( $recipe_settings['difficulty_level'] ) && (string) $recipe_settings['difficulty_level'] === (string) $level ? ' selected' : '' ) . '>' . esc_html( $name ) . '</option>';
                    endforeach;
                    ?>
                </select>
            </div>
            <?php foreach ( $cooked_times as $cooked_time_key => $cooked_time ) : ?>
                <div class="cooked-recipe-field">
                    <label for="<?php echo esc_attr( $cooked_time['id'] ); ?>"><?php echo esc_html( $cooked_time['label'] ); ?></label>
                    <span class="cooked-input-suffix">
                        <input id="<?php echo esc_attr( $cooked_time['id'] ); ?>" class="cooked-time-picker" type="number" min="0" step="any" name="_recipe_settings[<?php echo esc_attr( $cooked_time_key ); ?>]" value="<?php echo ( isset( $recipe_settings[ $cooked_time_key ] ) && $recipe_settings[ $cooked_time_key ] ? esc_attr( $recipe_settings[ $cooked_time_key ] ) : '' ); ?>" placeholder="--">
                        <span class="cooked-time-picker-text" aria-hidden="true"><?php esc_html_e( 'minutes', 'cooked' ); ?></span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="cooked-recipe-section">
        <div class="cooked-recipe-section-head">
            <div class="cooked-recipe-section-title-row">
                <label class="cooked-recipe-section-title" for="_recipe_settings_excerpt"><?php esc_html_e( 'Recipe Excerpt', 'cooked' ); ?></label>
                <?php $cooked_tooltip( __( 'Recipe Excerpt', 'cooked' ), __( 'The excerpt is used on recipe listing templates, where the full recipe should not be displayed.', 'cooked' ) ); ?>
            </div>
        </div>
        <?php if ( $wp_editor_roles_allowed ) : ?>
            <?php
            wp_editor(
                isset( $recipe_settings['excerpt'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['excerpt'] ) ) : '',
                '_recipe_settings_excerpt',
                [
                    'teeny'         => true,
                    'media_buttons' => true,
                    'wpautop'       => false,
                    'editor_height' => 100,
                    'textarea_name' => '_recipe_settings[excerpt]',
                    'quicktags'     => true,
                    'tinymce'       => [ 'content_style' => $cooked_editor_css ],
                ]
            );
            ?>
        <?php else : ?>
            <textarea id="_recipe_settings_excerpt" name="_recipe_settings[excerpt]" rows="3"><?php echo ( isset( $recipe_settings['excerpt'] ) ? esc_textarea( $recipe_settings['excerpt'] ) : '' ); ?></textarea>
        <?php endif; ?>
    </section>

    <?php if ( $cooked_show_seo ) : ?>
        <section class="cooked-recipe-section">
            <div class="cooked-recipe-section-head">
                <div class="cooked-recipe-section-title-row">
                    <label class="cooked-recipe-section-title" for="cooked-seo-description"><?php esc_html_e( 'SEO Description', 'cooked' ); ?></label>
                    <?php $cooked_tooltip( __( 'SEO Description', 'cooked' ), __( 'This description is used for SEO purposes and is optional. By default, Cooked will use the Recipe Excerpt above if available or the Recipe Title if not.', 'cooked' ) ); ?>
                </div>
            </div>
            <textarea id="cooked-seo-description" name="_recipe_settings[seo_description]" rows="3"><?php echo isset( $recipe_settings['seo_description'] ) ? esc_textarea( wp_specialchars_decode( $recipe_settings['seo_description'], ENT_QUOTES ) ) : ''; ?></textarea>
        </section>
    <?php endif; ?>

    <section class="cooked-recipe-section">
        <div class="cooked-recipe-section-head">
            <div class="cooked-recipe-section-title-row">
                <label class="cooked-recipe-section-title" for="_recipe_settings_notes"><?php esc_html_e( 'Recipe Notes', 'cooked' ); ?></label>
                <?php $cooked_tooltip( __( 'Recipe Notes', 'cooked' ), __( 'The notes are displayed in the recipe.', 'cooked' ) ); ?>
            </div>
        </div>
        <?php if ( $wp_editor_roles_allowed ) : ?>
            <?php
            wp_editor(
                isset( $recipe_settings['notes'] ) ? wp_unslash( wp_specialchars_decode( $recipe_settings['notes'] ) ) : '',
                '_recipe_settings_notes',
                [
                    'teeny'         => false,
                    'media_buttons' => true,
                    'wpautop'       => false,
                    'editor_height' => 100,
                    'textarea_name' => '_recipe_settings[notes]',
                    'quicktags'     => true,
                    'tinymce'       => [ 'content_style' => $cooked_editor_css ],
                ]
            );
            ?>
        <?php else : ?>
            <textarea id="_recipe_settings_notes" name="_recipe_settings[notes]" rows="4"><?php echo ( isset( $recipe_settings['notes'] ) ? esc_textarea( $recipe_settings['notes'] ) : '' ); ?></textarea>
        <?php endif; ?>
    </section>

</div>
