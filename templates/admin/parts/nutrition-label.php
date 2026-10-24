<?php
/**
 * Recipe editor: the Nutrition Facts label preview, filled in from the Nutrition tab's fields by
 * init_nutrition_facts() (cooked-admin-functions.js). Shared by both layouts' Nutrition tab.
 *
 * Expects $_nutrition_facts (Cooked_Measurements::nutrition_facts()) from the including template.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

?>
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
                echo '<li class="cooked-nut-servings-count"><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">___</strong> ' . esc_html( strtolower( $nf['name'] ) ) . '</li>';
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
                            echo '<li class="cooked-nut-indent">';
                                echo esc_html__( 'Includes', 'cooked' ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">___</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' ) . ' ' . esc_html( $sub_nf['name'] );
                                echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $sub_nf['pdv'] ) . '" data-labeltype="' . esc_attr( $sub_slug ) . '">0</span>%</strong>' : '' );
                            echo '</li>';
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
