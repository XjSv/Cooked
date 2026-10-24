<?php
/**
 * Recipe editor: Gallery tab
 *
 * Included by cooked_render_recipe_fields(), which provides $recipe_settings. Field names match
 * the classic layout (classic/recipe-gallery.php), so saving and the editor scripts work with
 * either.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$cooked_gallery_types  = Cooked_Recipes::gallery_types();
$cooked_gallery_type   = isset( $recipe_settings['gallery']['type'] ) && isset( $cooked_gallery_types[ $recipe_settings['gallery']['type'] ] ) ? $recipe_settings['gallery']['type'] : 'cooked';
$cooked_gallery_items  = ! empty( $recipe_settings['gallery']['items'] ) ? (array) $recipe_settings['gallery']['items'] : [];
$cooked_gallery_values = Cooked_Shortcodes::gallery_style_values( isset( $recipe_settings['gallery'] ) ? $recipe_settings['gallery'] : [] );

// Appearance: segmented controls, value => [ short label, full label (tooltip) ].
$cooked_gallery_ratios = [];
foreach ( Cooked_Shortcodes::gallery_ratios() as $cooked_ratio => $cooked_ratio_label ) {
    $cooked_gallery_ratios[ $cooked_ratio ] = [ str_replace( '/', ':', $cooked_ratio ), $cooked_ratio_label ];
}
$cooked_gallery_controls = [
    'style' => [
        'label'   => __( 'Style', 'cooked' ),
        'options' => [
            'dots'   => [ __( 'Dots', 'cooked' ), __( 'Dots under the image', 'cooked' ) ],
            'thumbs' => [ __( 'Thumbnails', 'cooked' ), __( 'A strip of small images under the image', 'cooked' ) ],
            'none'   => [ __( 'Arrows only', 'cooked' ), __( 'No dots or thumbnails', 'cooked' ) ],
        ],
    ],
    'ratio' => [
        'label'   => __( 'Shape', 'cooked' ),
        'options' => $cooked_gallery_ratios,
    ],
    'fit'   => [
        'label'   => __( 'Images', 'cooked' ),
        'options' => [
            'cover'   => [ __( 'Fill', 'cooked' ), __( 'Fill the frame; edges may be cropped', 'cooked' ) ],
            'contain' => [ __( 'Whole image', 'cooked' ), __( 'Show the whole image, with bars if needed', 'cooked' ) ],
        ],
    ],
];
?>
<div class="cooked-recipe-fields cooked-gallery-fields">

    <section class="cooked-recipe-section">
        <div class="cooked-recipe-section-head">
            <h3 class="cooked-recipe-section-title"><?php esc_html_e( 'Media', 'cooked' ); ?></h3>
            <p class="cooked-recipe-section-desc"><?php esc_html_e( 'The images and video in the recipe\'s gallery.', 'cooked' ); ?></p>
        </div>

        <?php if ( count( $cooked_gallery_types ) > 1 ) : ?>
            <div class="cooked-gallery-source">
                <label for="cooked_gallery_type"><?php esc_html_e( 'Gallery', 'cooked' ); ?></label>
                <select id="cooked_gallery_type" name="_recipe_settings[gallery][type]">
                    <?php foreach ( $cooked_gallery_types as $cooked_slug => $cooked_gtype ) : ?>
                        <option value="<?php echo esc_attr( $cooked_slug ); ?>"<?php selected( $cooked_gallery_type, $cooked_slug ); ?>><?php echo esc_html( $cooked_gtype['title'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            // Galleries from other plugins (Envira, Soliloquy, Slider Revolution): pick one of theirs.
            foreach ( $cooked_gallery_types as $cooked_slug => $cooked_gtype ) :
                if ( empty( $cooked_gtype['posts'] ) ) {
                    continue;
                }
                ?>
                <div class="cooked-gallery-source cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="<?php echo esc_attr( $cooked_slug ); ?>">
                    <label for="cooked_gallery_<?php echo esc_attr( $cooked_slug ); ?>"><?php echo esc_html( $cooked_gtype['title'] ); ?></label>
                    <select id="cooked_gallery_<?php echo esc_attr( $cooked_slug ); ?>" name="_recipe_settings[gallery][<?php echo esc_attr( $cooked_slug ); ?>]">
                        <option value=""><?php esc_html_e( 'Choose one...', 'cooked' ); ?></option>
                        <?php foreach ( $cooked_gtype['posts'] as $cooked_gid => $cooked_gtitle ) : ?>
                            <option value="<?php echo esc_attr( $cooked_gid ); ?>"<?php selected( isset( $recipe_settings['gallery'][ $cooked_slug ] ) ? (string) $recipe_settings['gallery'][ $cooked_slug ] : '', (string) $cooked_gid ); ?>><?php echo esc_html( $cooked_gtitle ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <input type="hidden" id="cooked_gallery_type" name="_recipe_settings[gallery][type]" value="cooked" />
        <?php endif; ?>

        <div class="cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">
            <?php // The add tile stays last; cooked-admin-functions.js adds images before it and sorts only the images. ?>
            <div id="cooked-recipe-image-gallery">
                <?php
                foreach ( $cooked_gallery_items as $cooked_item ) :
                    echo '<div data-attachment-id="' . esc_attr( $cooked_item ) . '" class="cooked-recipe-gallery-item">' . wp_kses_post( wp_get_attachment_image( $cooked_item, 'thumbnail' ) ) . '<span class="cooked-gallery-item-title">' . esc_html( get_the_title( $cooked_item ) ) . '</span><input type="hidden" name="_recipe_settings[gallery][items][]" value="' . esc_attr( $cooked_item ) . '" /><a href="#" data-attachment-id="' . esc_attr( $cooked_item ) . '" class="cooked-gallery-edit-button"><i class="cooked-icon cooked-icon-pencil"></i></a><a href="#" class="remove-image-button"><i class="cooked-icon cooked-icon-times"></i></a></div>';
                endforeach;
                ?>
                <button type="button" class="cooked-gallery-add-button cooked-gallery-add-tile">
                    <i class="cooked-icon cooked-icon-plus" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'Add images', 'cooked' ); ?></span>
                </button>
            </div>
            <p class="cooked-gallery-hint"><?php esc_html_e( 'Drag the images to change their order. Click one to replace it.', 'cooked' ); ?></p>

            <div class="cooked-gallery-video">
                <label for="cooked-gallery-video-url">
                    <i class="cooked-icon cooked-icon-video" aria-hidden="true"></i>
                    <?php
                    /* translators: 1: YouTube, 2: Vimeo. */
                    printf( esc_html__( 'Video from %1$s or %2$s', 'cooked' ), 'YouTube', 'Vimeo' );
                    ?>
                </label>
                <input type="text" inputmode="url" id="cooked-gallery-video-url" name="_recipe_settings[gallery][video_url]" value="<?php echo ! empty( $recipe_settings['gallery']['video_url'] ) ? esc_attr( $recipe_settings['gallery']['video_url'] ) : ''; ?>" placeholder="https://www.youtube.com/watch?v=abc123" aria-describedby="cooked-gallery-video-desc">
                <p class="cooked-gallery-hint" id="cooked-gallery-video-desc"><?php esc_html_e( 'Optional. The video plays as the first slide.', 'cooked' ); ?></p>
            </div>
        </div>
    </section>

    <section class="cooked-recipe-section cooked-conditional-hidden" data-condition="cooked_gallery_type" data-value="cooked">
        <div class="cooked-recipe-section-head">
            <h3 class="cooked-recipe-section-title"><?php esc_html_e( 'Appearance', 'cooked' ); ?></h3>
            <p class="cooked-recipe-section-desc"><?php esc_html_e( 'How the gallery looks on the recipe page.', 'cooked' ); ?></p>
        </div>

        <div class="cooked-gallery-live-stage">
            <div class="cooked-gallery-live-view">
                <iframe class="cooked-gallery-live-frame" title="<?php esc_attr_e( 'Gallery preview', 'cooked' ); ?>"></iframe>
            </div>
        </div>

        <div class="cooked-gallery-controls">
            <?php foreach ( $cooked_gallery_controls as $cooked_control => $cooked_choices ) : ?>
                <div class="cooked-gallery-control">
                    <span class="cooked-gallery-label" id="cooked-gallery-<?php echo esc_attr( $cooked_control ); ?>-label"><?php echo esc_html( $cooked_choices['label'] ); ?></span>
                    <div class="cooked-segmented" role="radiogroup" aria-labelledby="cooked-gallery-<?php echo esc_attr( $cooked_control ); ?>-label" style="--cooked-segments: <?php echo count( $cooked_choices['options'] ); ?>">
                        <?php foreach ( $cooked_choices['options'] as $cooked_value => $cooked_option ) : ?>
                            <label title="<?php echo esc_attr( $cooked_option[1] ); ?>">
                                <input type="radio" name="_recipe_settings[gallery][<?php echo esc_attr( $cooked_control ); ?>]" value="<?php echo esc_attr( $cooked_value ); ?>"<?php checked( $cooked_gallery_values[ $cooked_control ], $cooked_value ); ?> />
                                <span><?php echo esc_html( $cooked_option[0] ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cooked-gallery-control cooked-gallery-control-toggles">
                <span class="cooked-gallery-toggle">
                    <input type="hidden" name="_recipe_settings[gallery][loop]" value="0" />
                    <input type="checkbox" class="cooked-switch" id="cooked-gallery-loop" name="_recipe_settings[gallery][loop]" value="1"<?php checked( $cooked_gallery_values['loop'] ); ?> />
                    <label for="cooked-gallery-loop"><?php esc_html_e( 'Loop', 'cooked' ); ?></label>
                </span>
                <span class="cooked-gallery-toggle">
                    <input type="hidden" name="_recipe_settings[gallery][autoplay]" value="0" />
                    <input type="checkbox" class="cooked-switch" id="cooked-gallery-autoplay" name="_recipe_settings[gallery][autoplay]" value="1"<?php checked( $cooked_gallery_values['autoplay'] ); ?> />
                    <label for="cooked-gallery-autoplay"><?php esc_html_e( 'Autoplay every 5 seconds', 'cooked' ); ?></label>
                </span>
                <button type="button" class="button-link cooked-gallery-preview-button" aria-haspopup="dialog" aria-controls="cooked-gallery-preview-dialog"><?php esc_html_e( 'Full preview', 'cooked' ); ?><i class="cooked-icon cooked-icon-angle-right" aria-hidden="true"></i></button>
            </div>
        </div>
    </section>

    <?php include COOKED_DIR . 'templates/admin/parts/gallery-preview-dialog.php'; ?>

</div>
