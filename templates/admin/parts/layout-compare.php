<?php
/**
 * Settings › General: compare the classic and new admin layouts with a before/after slider
 * (Cooked_Settings::field_layout_compare()).
 *
 * Each pair is two screenshots of the same screen, the classic one clipped to the left of the
 * divider. A range input over the images moves the divider (pointer and keyboard).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$cooked_compare_pairs = [
    'settings' => [
        'label'   => __( 'Settings', 'cooked' ),
        'classic' => __( 'Cooked Settings in the classic layout', 'cooked' ),
        'new'     => __( 'Cooked Settings in the new layout', 'cooked' ),
    ],
    'editor'   => [
        'label'   => __( 'Recipe editor', 'cooked' ),
        'classic' => __( 'The recipe editor\'s Ingredients tab in the classic layout', 'cooked' ),
        'new'     => __( 'The recipe editor\'s Ingredients tab in the new layout', 'cooked' ),
    ],
];

$cooked_compare_dir = COOKED_URL . 'assets/images/layout-compare/';
$cooked_first_pair  = array_key_first( $cooked_compare_pairs );

?>
<div class="cooked-layout-compare">
    <div class="cooked-layout-compare-head">
        <div class="cooked-layout-compare-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Screen to compare', 'cooked' ); ?>">
            <?php foreach ( $cooked_compare_pairs as $cooked_slug => $cooked_pair ) : ?>
                <button type="button" role="tab" id="cooked-compare-tab-<?php echo esc_attr( $cooked_slug ); ?>" aria-controls="cooked-compare-<?php echo esc_attr( $cooked_slug ); ?>" aria-selected="<?php echo $cooked_slug === $cooked_first_pair ? 'true' : 'false'; ?>"<?php echo $cooked_slug === $cooked_first_pair ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( $cooked_pair['label'] ); ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ( $cooked_compare_pairs as $cooked_slug => $cooked_pair ) : ?>
        <div class="cooked-compare" id="cooked-compare-<?php echo esc_attr( $cooked_slug ); ?>" role="tabpanel" aria-labelledby="cooked-compare-tab-<?php echo esc_attr( $cooked_slug ); ?>"<?php echo $cooked_slug === $cooked_first_pair ? '' : ' hidden'; ?>>
            <img class="cooked-compare-new" src="<?php echo esc_url( $cooked_compare_dir . $cooked_slug . '-new.webp' ); ?>" alt="<?php echo esc_attr( $cooked_pair['new'] ); ?>" width="1120" height="700" loading="lazy" decoding="async">
            <img class="cooked-compare-classic" src="<?php echo esc_url( $cooked_compare_dir . $cooked_slug . '-classic.webp' ); ?>" alt="<?php echo esc_attr( $cooked_pair['classic'] ); ?>" width="1120" height="700" loading="lazy" decoding="async">
            <span class="cooked-compare-label is-classic" aria-hidden="true"><?php esc_html_e( 'Classic', 'cooked' ); ?></span>
            <span class="cooked-compare-label is-new" aria-hidden="true"><?php esc_html_e( 'New', 'cooked' ); ?></span>
            <span class="cooked-compare-divider" aria-hidden="true"><span class="cooked-compare-handle"></span></span>
            <input type="range" class="cooked-compare-range" min="0" max="100" value="50" step="1" aria-label="<?php esc_attr_e( 'Divider position: classic layout on the left, new layout on the right', 'cooked' ); ?>">
        </div>
    <?php endforeach; ?>
</div>
