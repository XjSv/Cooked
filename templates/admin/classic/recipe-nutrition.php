<?php
/**
 * Recipe editor: Nutrition tab, classic layout
 *
 * Included by cooked_render_recipe_fields(), which provides $post_id and $recipe_settings. Field
 * names and IDs match the new layout (frame/recipe-nutrition.php).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

?>

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

            <?php include COOKED_DIR . 'templates/admin/parts/nutrition-label.php'; ?>
        </div>
    </div>
</div>

<input type="hidden" name="_recipe_settings[nutrition][etag]" value="<?php echo ! empty( $recipe_settings['nutrition']['etag'] ) ? esc_attr( $recipe_settings['nutrition']['etag'] ) : ''; ?>" />

