<?php
/**
 * The Nutrition Facts label on the recipe page ([cooked-nutrition]).
 *
 * The nutrients are a table: one row each, a nutrient's parts (saturated fat, fiber, …) as
 * indented rows under it, and the % Daily Value in its own column.
 *
 * Expects:
 * - $nutrition_label array{
 *       float: string,
 *       servings: array<int, array{slug: string, name: string, value: string}>,
 *       headline: array<int, array{slug: string, name: string, value: string}>,
 *       groups: array<int, array<int, array{slug: string, name: string, amount: string, unit: string, pdv: int|null, level: int}>>,
 *       powered_by: bool
 *   }
 *
 * @package     Cooked
 * @subpackage  Templates
 * @since       2.0.0
 */

$cooked_nut_classes = 'cooked-nutrition-label' . ( $nutrition_label['float'] ? ' cooked-float-' . $nutrition_label['float'] : '' );
$cooked_nut_has_dv  = false;

foreach ( $nutrition_label['groups'] as $cooked_nut_group ) {
    foreach ( $cooked_nut_group as $cooked_nut_row ) {
        if ( null !== $cooked_nut_row['pdv'] ) {
            $cooked_nut_has_dv = true;
        }
    }
}
?>
<div class="<?php echo esc_attr( $cooked_nut_classes ); ?>">
    <p class="cooked-nutrition-title"><?php esc_html_e( 'Nutrition Facts', 'cooked' ); ?></p>

    <?php if ( $nutrition_label['servings'] ) : ?>
        <div class="cooked-nut-servings">
            <?php foreach ( $nutrition_label['servings'] as $cooked_nut_item ) : ?>
                <?php if ( 'serving_size' === $cooked_nut_item['slug'] ) : ?>
                    <p class="cooked-nut-serving-size">
                        <span><?php echo esc_html( $cooked_nut_item['name'] ); ?></span>
                        <strong class="cooked-nut-value" data-labeltype="serving_size"><?php echo esc_html( $cooked_nut_item['value'] ); ?></strong>
                    </p>
                <?php else : ?>
                    <p class="cooked-nut-servings-count">
                        <strong class="cooked-nut-value" data-labeltype="<?php echo esc_attr( $cooked_nut_item['slug'] ); ?>"><?php echo esc_html( $cooked_nut_item['value'] ); ?></strong>
                        <?php echo esc_html( strtolower( $cooked_nut_item['name'] ) ); ?>
                    </p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ( $nutrition_label['headline'] as $cooked_nut_item ) : ?>
        <p class="cooked-nut-headline">
            <span class="cooked-nut-headline-name">
                <?php echo esc_html( $cooked_nut_item['name'] ); ?>
                <span class="cooked-nut-per"><?php esc_html_e( 'per serving', 'cooked' ); ?></span>
            </span>
            <strong class="cooked-nut-value" data-labeltype="<?php echo esc_attr( $cooked_nut_item['slug'] ); ?>"><?php echo esc_html( $cooked_nut_item['value'] ); ?></strong>
        </p>
    <?php endforeach; ?>

    <?php if ( $nutrition_label['groups'] ) : ?>
        <table class="cooked-nut-table">
            <caption class="cooked-nut-sr"><?php esc_html_e( 'Nutrients per serving', 'cooked' ); ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Amount per serving', 'cooked' ); ?></th>
                    <th scope="col" class="cooked-nut-dv-head">
                        <?php if ( $cooked_nut_has_dv ) : ?>
                            <abbr title="<?php esc_attr_e( 'Daily Value', 'cooked' ); ?>"><?php esc_html_e( '% DV', 'cooked' ); ?></abbr>*
                        <?php else : ?>
                            <span class="cooked-nut-sr"><?php esc_html_e( 'Daily Value', 'cooked' ); ?></span>
                        <?php endif; ?>
                    </th>
                </tr>
            </thead>
            <?php foreach ( $nutrition_label['groups'] as $cooked_nut_group ) : ?>
                <tbody>
                    <?php foreach ( $cooked_nut_group as $cooked_nut_row ) : ?>
                        <tr class="cooked-nut-row cooked-nut-level-<?php echo esc_attr( (string) $cooked_nut_row['level'] ); ?>">
                            <th scope="row">
                                <span class="cooked-nut-name"><?php echo wp_kses( $cooked_nut_row['name'], [ 'i' => [] ] ); ?></span>
                                <span class="cooked-nut-amount" data-labeltype="<?php echo esc_attr( $cooked_nut_row['slug'] ); ?>"><?php echo esc_html( $cooked_nut_row['amount'] . $cooked_nut_row['unit'] ); ?></span>
                            </th>
                            <td>
                                <?php if ( null !== $cooked_nut_row['pdv'] ) : ?>
                                    <span class="cooked-nut-pdv" data-labeltype="<?php echo esc_attr( $cooked_nut_row['slug'] ); ?>"><?php echo esc_html( $cooked_nut_row['pdv'] . '%' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endforeach; ?>
        </table>

        <?php if ( $cooked_nut_has_dv ) : ?>
            <p class="cooked-daily-value-text">* <?php esc_html_e( 'The % Daily Value (DV) tells you how much a nutrient in a serving of food contributes to a daily diet. 2,000 calories a day is used for general nutrition advice.', 'cooked' ); ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( $nutrition_label['powered_by'] ) : ?>
        <div class="cooked-nutrition-edamam">
            <?php
            // The Edamam attribution, when the nutrition API is on: https://developer.edamam.com/attribution.
            do_action( 'cooked_nutrition_facts_powered_by_edamam' );
            ?>
        </div>
    <?php endif; ?>
</div>
