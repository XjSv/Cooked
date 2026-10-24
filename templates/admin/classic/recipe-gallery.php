<?php
/**
 * Recipe editor: Gallery tab, classic layout
 *
 * Included by cooked_render_recipe_fields(), which provides $recipe_settings. Field names match
 * the new layout (frame/recipe-gallery.php).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

?>
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

        <div class="cooked-gallery-style">
            <h3 class="cooked-settings-title" id="cooked-gallery-style-title"><?php esc_html_e( 'Gallery Style', 'cooked' ); ?></h3>
            <?php
            $cooked_gallery_style_label = 'cooked-gallery-style-title';
            include COOKED_DIR . 'templates/admin/parts/gallery-style.php';
            ?>

            <button type="button" class="button cooked-gallery-preview-button" aria-haspopup="dialog" aria-controls="cooked-gallery-preview-dialog"><?php esc_html_e( 'Preview Gallery', 'cooked' ); ?></button>
        </div>

        <?php include COOKED_DIR . 'templates/admin/parts/gallery-preview-dialog.php'; ?>
    </div>
