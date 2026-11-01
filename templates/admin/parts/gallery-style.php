<?php
/**
 * Recipe editor › Gallery, classic layout: the gallery style fields (style cards, shape, fit,
 * loop, autoplay). The new layout has its own controls (frame/recipe-gallery.php).
 *
 * Expects $recipe_settings and $cooked_gallery_style_label, the ID of the element that names the
 * style choice. The values are read by Cooked_Shortcodes::gallery_options().
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$cooked_gallery_values = Cooked_Shortcodes::gallery_style_values( isset( $recipe_settings['gallery'] ) ? $recipe_settings['gallery'] : [] );
$cooked_gallery_styles = [
    'dots'   => [ __( 'Dots', 'cooked' ), __( 'Dots under the image', 'cooked' ) ],
    'thumbs' => [ __( 'Thumbnails', 'cooked' ), __( 'A strip of small images', 'cooked' ) ],
    'none'   => [ __( 'Arrows only', 'cooked' ), __( 'No dots or thumbnails', 'cooked' ) ],
];
?>
<div class="cooked-gallery-styles" role="radiogroup" aria-labelledby="<?php echo esc_attr( $cooked_gallery_style_label ); ?>">
    <?php foreach ( $cooked_gallery_styles as $cooked_style_slug => $cooked_style ) : ?>
        <label class="cooked-gallery-style-card">
            <input type="radio" name="_recipe_settings[gallery][style]" value="<?php echo esc_attr( $cooked_style_slug ); ?>"<?php checked( $cooked_gallery_values['style'], $cooked_style_slug ); ?> />
            <span class="cooked-gallery-style-art is-<?php echo esc_attr( $cooked_style_slug ); ?>" aria-hidden="true"><span class="cooked-gallery-style-art-image"></span><span class="cooked-gallery-style-art-nav"><i></i><i></i><i></i><i></i></span></span>
            <strong><?php echo esc_html( $cooked_style[0] ); ?></strong>
            <span class="cooked-gallery-style-desc"><?php echo esc_html( $cooked_style[1] ); ?></span>
        </label>
    <?php endforeach; ?>
</div>

<div class="cooked-gallery-style-options">
    <p>
        <label for="cooked-gallery-ratio"><?php esc_html_e( 'Shape', 'cooked' ); ?></label>
        <select id="cooked-gallery-ratio" name="_recipe_settings[gallery][ratio]">
            <?php foreach ( Cooked_Shortcodes::gallery_ratios() as $cooked_ratio => $cooked_ratio_label ) : ?>
                <option value="<?php echo esc_attr( $cooked_ratio ); ?>"<?php selected( $cooked_gallery_values['ratio'], $cooked_ratio ); ?>><?php echo esc_html( $cooked_ratio_label ); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="cooked-gallery-fit"><?php esc_html_e( 'Images', 'cooked' ); ?></label>
        <select id="cooked-gallery-fit" name="_recipe_settings[gallery][fit]">
            <option value="cover"<?php selected( $cooked_gallery_values['fit'], 'cover' ); ?>><?php esc_html_e( 'Fill the frame (may crop)', 'cooked' ); ?></option>
            <option value="contain"<?php selected( $cooked_gallery_values['fit'], 'contain' ); ?>><?php esc_html_e( 'Show the whole image', 'cooked' ); ?></option>
        </select>
    </p>
    <p class="cooked-gallery-style-toggle">
        <input type="hidden" name="_recipe_settings[gallery][loop]" value="0" />
        <input type="checkbox" class="cooked-switch" id="cooked-gallery-loop" name="_recipe_settings[gallery][loop]" value="1"<?php checked( $cooked_gallery_values['loop'] ); ?> />
        <label for="cooked-gallery-loop"><?php esc_html_e( 'Loop: go from the last image back to the first', 'cooked' ); ?></label>
    </p>
    <p class="cooked-gallery-style-toggle">
        <input type="hidden" name="_recipe_settings[gallery][autoplay]" value="0" />
        <input type="checkbox" class="cooked-switch" id="cooked-gallery-autoplay" name="_recipe_settings[gallery][autoplay]" value="1"<?php checked( $cooked_gallery_values['autoplay'] ); ?> />
        <label for="cooked-gallery-autoplay"><?php esc_html_e( 'Autoplay: move to the next image every 5 seconds', 'cooked' ); ?></label>
    </p>
</div>
