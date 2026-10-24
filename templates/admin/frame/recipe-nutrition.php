<?php
/**
 * Recipe editor: Nutrition tab
 *
 * Included by cooked_render_recipe_fields(), which provides $post_id and $recipe_settings. The
 * fields are grouped (servings and calories, nutrients with their parts indented under them,
 * vitamins and minerals), each with its unit inside; the Nutrition Facts label beside them fills
 * in as you type. Field names and IDs match the classic layout (classic/recipe-nutrition.php),
 * so saving and the label script work with either.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$_nutrition_facts = Cooked_Measurements::nutrition_facts();

// The label's groups, as sections: top and mid together; groups added through the
// cooked_nutrition_facts filter go with the vitamins and minerals.
$cooked_nutrition_sections = [
    'serving'   => [
        'title'  => __( 'Servings & calories', 'cooked' ),
        'desc'   => __( 'The servings switcher scales ingredient amounts from Servings.', 'cooked' ),
        'groups' => [ 'top', 'mid' ],
    ],
    'nutrients' => [
        'title'  => __( 'Nutrients', 'cooked' ),
        'desc'   => __( 'Amounts per serving. A field left empty stays off the label.', 'cooked' ),
        'groups' => [ 'main' ],
    ],
    'vitamins'  => [
        'title'  => __( 'Vitamins & minerals', 'cooked' ),
        'desc'   => __( 'Amounts per serving; the label works out the % Daily Value.', 'cooked' ),
        'groups' => array_values( array_diff( array_keys( (array) $_nutrition_facts ), [ 'top', 'mid', 'main' ] ) ),
    ],
];

/**
 * One nutrition field: label, input, and its unit inside the field.
 *
 * @param string               $slug Field key.
 * @param array<string, mixed> $nf   Field definition from Cooked_Measurements::nutrition_facts().
 */
$cooked_nutrition_field = function ( $slug, $nf ) use ( $recipe_settings ) {
    $value = isset( $recipe_settings['nutrition'][ $slug ] ) ? $recipe_settings['nutrition'][ $slug ] : '';
    $type  = isset( $nf['type'] ) && 'number' === $nf['type'] ? 'number' : 'text';
    $unit  = isset( $nf['measurement'] ) ? $nf['measurement'] : '';
    ?>
    <div class="cooked-recipe-field cooked-nutrition-field">
        <label for="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nf['name'] ); ?></label>
        <span class="cooked-input-suffix<?php echo $unit ? ' has-unit' : ''; ?>">
            <input type="<?php echo esc_attr( $type ); ?>"<?php echo 'number' === $type ? ' step="any" min="0"' : ''; ?> placeholder="--" name="_recipe_settings[nutrition][<?php echo esc_attr( $slug ); ?>]" id="<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $value ); ?>">
            <?php if ( $unit ) : ?>
                <span class="cooked-input-unit" aria-hidden="true"><?php echo esc_html( $unit ); ?></span>
            <?php endif; ?>
        </span>
    </div>
    <?php
};
?>
<div class="cooked-recipe-fields cooked-nutrition-fields">

    <div class="cooked-nutrition-layout">

        <div class="cooked-nutrition-inputs">
            <?php foreach ( $cooked_nutrition_sections as $cooked_section_key => $cooked_section ) : ?>
                <?php
                $cooked_section_facts = [];
                foreach ( $cooked_section['groups'] as $cooked_group ) {
                    if ( ! empty( $_nutrition_facts[ $cooked_group ] ) ) {
                        $cooked_section_facts += (array) $_nutrition_facts[ $cooked_group ];
                    }
                }
                if ( empty( $cooked_section_facts ) ) {
                    continue;
                }
                ?>
                <section class="cooked-recipe-section cooked-nutrition-section-<?php echo esc_attr( $cooked_section_key ); ?>">
                    <div class="cooked-recipe-section-head">
                        <div class="cooked-recipe-section-title-row">
                            <h3 class="cooked-recipe-section-title"><?php echo esc_html( $cooked_section['title'] ); ?></h3>
                        </div>
                        <?php if ( 'serving' === $cooked_section_key ) : ?>
                            <div class="cooked-recipe-section-actions">
                                <button type="button" class="button cooked-nutrition-clear"><?php esc_html_e( 'Clear values', 'cooked' ); ?></button>
                            </div>
                        <?php endif; ?>
                        <p class="cooked-recipe-section-desc"><?php echo esc_html( $cooked_section['desc'] ); ?></p>
                    </div>

                    <?php if ( 'nutrients' === $cooked_section_key ) : ?>
                        <div class="cooked-nutrition-list">
                            <?php foreach ( $cooked_section_facts as $cooked_slug => $cooked_nf ) : ?>
                                <div class="cooked-nutrition-group<?php echo empty( $cooked_nf['subs'] ) ? '' : ' has-parts'; ?>">
                                    <?php $cooked_nutrition_field( $cooked_slug, $cooked_nf ); ?>
                                    <?php if ( ! empty( $cooked_nf['subs'] ) ) : ?>
                                        <div class="cooked-nutrition-parts">
                                            <?php
                                            foreach ( $cooked_nf['subs'] as $cooked_sub_slug => $cooked_sub_nf ) {
                                                $cooked_nutrition_field( $cooked_sub_slug, $cooked_sub_nf );
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="cooked-nutrition-grid">
                            <?php
                            foreach ( $cooked_section_facts as $cooked_slug => $cooked_nf ) {
                                $cooked_nutrition_field( $cooked_slug, $cooked_nf );
                                foreach ( isset( $cooked_nf['subs'] ) ? (array) $cooked_nf['subs'] : [] as $cooked_sub_slug => $cooked_sub_nf ) {
                                    $cooked_nutrition_field( $cooked_sub_slug, $cooked_sub_nf );
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <aside class="cooked-nutrition-preview" aria-labelledby="cooked-nutrition-preview-title">
            <div class="cooked-nutrition-preview-head">
                <h3 class="cooked-nutrition-preview-title" id="cooked-nutrition-preview-title"><?php esc_html_e( 'Preview', 'cooked' ); ?></h3>
                <span class="cooked-nutrition-preview-caption"><?php esc_html_e( 'Shown on the recipe page', 'cooked' ); ?></span>
            </div>
            <div class="cooked-nutrition-stage">
                <?php do_action( 'cooked_before_nutrition_information', $post_id ); ?>
                <?php include COOKED_DIR . 'templates/admin/parts/nutrition-label.php'; ?>
                <p class="cooked-nutrition-empty">
                    <i class="cooked-icon cooked-icon-heart" aria-hidden="true"></i>
                    <?php esc_html_e( 'The label builds itself as you enter servings, calories and nutrients.', 'cooked' ); ?>
                </p>
            </div>
        </aside>

    </div>

    <input type="hidden" name="_recipe_settings[nutrition][etag]" value="<?php echo ! empty( $recipe_settings['nutrition']['etag'] ) ? esc_attr( $recipe_settings['nutrition']['etag'] ) : ''; ?>" />

</div>
