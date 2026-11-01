<?php
/**
 * Recipe editor: Shortcodes tab
 *
 * Included by cooked_render_recipe_fields(), which provides $post_id and $recipe_settings. Two
 * lists: the shortcodes that show this recipe anywhere on the site (once it is saved), and the
 * parts of a recipe the Recipe Template is built from. Each row copies its shortcode; its options
 * open below it with an example. The 1.x reference is classic/recipe-shortcodes.php.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$cooked_recipe_id = intval( $post_id );
$cooked_true      = __( 'true or false', 'cooked' );

// [cooked-info] places these; add-ons add theirs through the filter (as on the classic tab).
$cooked_info_vars   = apply_filters(
    'cooked_available_info_vars',
    [
        'author'           => __( 'Author', 'cooked' ),
        'prep_time'        => __( 'Prep Time', 'cooked' ),
        'cook_time'        => __( 'Cook Time', 'cooked' ),
        'total_time'       => __( 'Total Time', 'cooked' ),
        'difficulty_level' => __( 'Difficulty', 'cooked' ),
        'servings'         => __( 'Servings Switcher', 'cooked' ),
        'taxonomies'       => __( 'Category', 'cooked' ),
        'print'            => esc_html__( 'Print Mode', 'cooked' ),
        'fullscreen'       => __( 'Full-Screen Mode', 'cooked' ),
    ]
);
$cooked_info_values = __( 'detail names, comma-separated', 'cooked' );

$cooked_timer_tag = shortcode_exists( 'cooked-timer' ) ? 'cooked-timer' : 'timer';

/*
 * Each shortcode: icon, title, description, the shortcode to copy, and optional options
 * (attribute => [ what it does, its values ]) with an example, and names its options take
 * ([ label, [ name => what it is ] ]).
 */
$cooked_shortcode_groups = [];

if ( ! empty( $recipe_settings ) ) {
    $cooked_shortcode_groups['recipe'] = [
        'title' => __( 'Show this recipe anywhere', 'cooked' ),
        'desc'  => __( 'Paste one into a post, a page or a widget.', 'cooked' ),
        'items' => [
            'cooked-recipe'          => [
                'icon'  => 'recipe-icon',
                'title' => __( 'Full recipe', 'cooked' ),
                'desc'  => __( 'The whole recipe, laid out by its Recipe Template on the Layout tab.', 'cooked' ),
                'code'  => '[cooked-recipe id="' . $cooked_recipe_id . '"]',
            ],
            'cooked-recipe-card'     => [
                'icon'    => 'desktop',
                'title'   => __( 'Recipe card', 'cooked' ),
                'desc'    => __( 'A compact card linking to the recipe: image, title, author and excerpt.', 'cooked' ),
                'code'    => '[cooked-recipe-card id="' . $cooked_recipe_id . '"]',
                'options' => [
                    'style'        => [ __( 'Card style.', 'cooked' ), 'modern, modern-centered' ],
                    'width'        => [ __( 'Card width, in px or %. Defaults to 100%.', 'cooked' ), '300px, 50%' ],
                    'hide_image'   => [ __( 'Hide the image.', 'cooked' ), $cooked_true ],
                    'hide_title'   => [ __( 'Hide the title.', 'cooked' ), $cooked_true ],
                    'hide_excerpt' => [ __( 'Hide the excerpt.', 'cooked' ), $cooked_true ],
                    'hide_author'  => [ __( 'Hide the author.', 'cooked' ), $cooked_true ],
                ],
                'example' => '[cooked-recipe-card id="' . $cooked_recipe_id . '" style="modern" width="300px"]',
            ],
            'cooked-related-recipes' => [
                'icon'    => 'link',
                'title'   => __( 'Related recipes', 'cooked' ),
                'desc'    => __( 'A grid of recipes that share a category, cuisine, cooking method, tag or diet with this one, in random order.', 'cooked' ),
                'code'    => '[cooked-related-recipes id="' . $cooked_recipe_id . '"]',
                'options' => [
                    'id'           => [ __( 'The recipe to match. Leave it out on a recipe page to use that recipe.', 'cooked' ), __( 'a recipe ID', 'cooked' ) ],
                    'include_ids'  => [ __( 'Recipes to pin at the start of the grid; related recipes fill the rest.', 'cooked' ), __( 'comma-separated recipe IDs', 'cooked' ) ],
                    'title'        => [ __( 'The heading above the grid.', 'cooked' ), __( 'any text', 'cooked' ) ],
                    'limit'        => [ __( 'How many recipes to show. Defaults to 4.', 'cooked' ), __( 'a number', 'cooked' ) ],
                    'columns'      => [ __( 'Grid columns. Defaults to 2.', 'cooked' ), __( 'a number', 'cooked' ) ],
                    'hide_image'   => [ __( 'Hide the card images.', 'cooked' ), $cooked_true ],
                    'hide_excerpt' => [ __( 'Hide the card excerpts.', 'cooked' ), $cooked_true ],
                    'hide_author'  => [ __( 'Hide the card authors.', 'cooked' ), $cooked_true ],
                    'match_*'      => [ __( 'Set one to false to ignore that taxonomy: match_categories, match_cuisines, match_cooking_methods, match_tags, match_diets.', 'cooked' ), $cooked_true ],
                ],
                'example' => '[cooked-related-recipes id="' . $cooked_recipe_id . '" include_ids="101,102" limit="4" columns="2" title="' . esc_attr__( 'You May Also Like', 'cooked' ) . '"]',
            ],
        ],
    ];
}

$cooked_template_items = [
    'cooked-info'        => [
        'icon'    => 'clock',
        'title'   => __( 'Recipe information', 'cooked' ),
        'desc'    => __( 'A row of details: author, times, difficulty, servings, category, and print and full-screen buttons.', 'cooked' ),
        'code'    => '[cooked-info]',
        'options' => [
            'include' => [ __( 'Show only these details.', 'cooked' ), $cooked_info_values ],
            'exclude' => [ __( 'Show every detail but these.', 'cooked' ), $cooked_info_values ],
            'left'    => [ __( 'Details on the left of the row.', 'cooked' ), $cooked_info_values ],
            'right'   => [ __( 'Details on the right of the row.', 'cooked' ), $cooked_info_values ],
        ],
        'example' => '[cooked-info left="author,difficulty_level" right="print,fullscreen"]',
        'names'   => [ __( 'Details', 'cooked' ), (array) $cooked_info_vars ],
    ],
    'cooked-image'       => [
        'icon'  => 'image',
        'title' => __( 'Featured image', 'cooked' ),
        'desc'  => __( 'The recipe\'s featured image, if it has one.', 'cooked' ),
        'code'  => '[cooked-image]',
    ],
    'cooked-excerpt'     => [
        'icon'  => 'bars',
        'title' => __( 'Excerpt', 'cooked' ),
        'desc'  => __( 'The Recipe Excerpt from the Layout tab.', 'cooked' ),
        'code'  => '[cooked-excerpt]',
    ],
    'cooked-ingredients' => [
        'icon'    => 'list',
        'title'   => __( 'Ingredients', 'cooked' ),
        'desc'    => __( 'The ingredient list from the Ingredients tab.', 'cooked' ),
        'code'    => '[cooked-ingredients]',
        'options' => [
            'checkboxes' => [ __( 'Checkboxes to tick off each ingredient. Shown by default.', 'cooked' ), $cooked_true ],
        ],
        'example' => '[cooked-ingredients checkboxes="false"]',
    ],
    'cooked-directions'  => [
        'icon'    => 'directions',
        'title'   => __( 'Directions', 'cooked' ),
        'desc'    => __( 'The steps from the Directions tab.', 'cooked' ),
        'code'    => '[cooked-directions]',
        'options' => [
            'numbers' => [ __( 'Step numbers. Shown by default.', 'cooked' ), $cooked_true ],
        ],
        'example' => '[cooked-directions numbers="false"]',
    ],
    'cooked-notes'       => [
        'icon'    => 'pencil',
        'title'   => __( 'Notes', 'cooked' ),
        'desc'    => __( 'The Recipe Notes from the Layout tab.', 'cooked' ),
        'code'    => '[cooked-notes]',
        'options' => [
            'show_header' => [ __( 'A "Notes" heading above them. Hidden by default.', 'cooked' ), $cooked_true ],
        ],
        'example' => '[cooked-notes show_header="true"]',
    ],
    'cooked-nutrition'   => [
        'icon'    => 'heart',
        'title'   => __( 'Nutrition label', 'cooked' ),
        'desc'    => __( 'The Nutrition Facts label, when the Nutrition tab has values.', 'cooked' ),
        'code'    => '[cooked-nutrition]',
        'options' => [
            'float' => [ __( 'Float the label so text wraps beside it.', 'cooked' ), 'left, right' ],
        ],
        'example' => '[cooked-nutrition float="right"]',
    ],
    'cooked-gallery'     => [
        'icon'    => 'image',
        'title'   => __( 'Gallery', 'cooked' ),
        'desc'    => __( 'The gallery from the Gallery tab. Its style there is the default; these options override it.', 'cooked' ),
        'code'    => '[cooked-gallery]',
        'options' => [
            'nav'             => [ __( 'Dots, thumbnails or arrows only.', 'cooked' ), 'dots, thumbs, false' ],
            'ratio'           => [ __( 'The shape of the slides.', 'cooked' ), '4/3, 3/2, 16/9, 1/1' ],
            'fit'             => [ __( 'Fill the frame (may crop) or show the whole image.', 'cooked' ), 'cover, contain' ],
            'width'           => [ __( 'Gallery width, in px or %.', 'cooked' ), '600px, 100%' ],
            'loop'            => [ __( 'Go from the last image back to the first.', 'cooked' ), $cooked_true ],
            'allowfullscreen' => [ __( 'Open images full screen on click.', 'cooked' ), $cooked_true ],
        ],
        'example' => '[cooked-gallery nav="thumbs" ratio="16/9"]',
    ],
    $cooked_timer_tag    => [
        'icon'    => 'clock',
        'title'   => __( 'Timer', 'cooked' ),
        'desc'    => __( 'A link in the text that starts a cooking timer. Use it inside a direction step.', 'cooked' ),
        'code'    => '[' . $cooked_timer_tag . ' minutes="5"]' . __( '5 minutes', 'cooked' ) . '[/' . $cooked_timer_tag . ']',
        'options' => [
            'hours'   => [ __( 'Timer length; combine with minutes and seconds.', 'cooked' ), __( 'a number', 'cooked' ) ],
            'minutes' => [ __( 'Timer length; combine with hours and seconds.', 'cooked' ), __( 'a number', 'cooked' ) ],
            'seconds' => [ __( 'Timer length; combine with hours and minutes.', 'cooked' ), __( 'a number', 'cooked' ) ],
            'desc'    => [ __( 'What the timer is for, shown with it. Defaults to the linked text.', 'cooked' ), __( 'any text', 'cooked' ) ],
        ],
        'example' => '[' . $cooked_timer_tag . ' minutes="20" desc="' . esc_attr__( 'Boil the pasta', 'cooked' ) . '"]' . __( '20 minutes', 'cooked' ) . '[/' . $cooked_timer_tag . ']',
    ],
];

// Cooked Pro's next / previous recipe links.
if ( shortcode_exists( 'cooked-next-previous' ) ) {
    $cooked_template_items['cooked-next-previous'] = [
        'icon'  => 'angle-right',
        'title' => __( 'Next and previous recipe', 'cooked' ),
        'desc'  => __( 'Links to the recipes before and after this one.', 'cooked' ),
        'code'  => '[cooked-next-previous]',
    ];
}

$cooked_shortcode_groups['template'] = [
    'title' => __( 'Recipe Template parts', 'cooked' ),
    'desc'  => __( 'The pieces the Recipe Template on the Layout tab is built from. Arrange them there to change how every part of the recipe is laid out.', 'cooked' ),
    'items' => $cooked_template_items,
];

/**
 * Filters the Shortcodes tab's lists (new layout). Groups are keyed, each with a title,
 * a description and items keyed by shortcode tag (icon, title, desc, code, options, example, names).
 *
 * @since 2.0.0
 * @param array<string, array<string, mixed>> $cooked_shortcode_groups Groups.
 * @param int                                 $cooked_recipe_id        Recipe ID.
 */
$cooked_shortcode_groups = apply_filters( 'cooked_recipe_editor_shortcodes', $cooked_shortcode_groups, $cooked_recipe_id );
?>
<div class="cooked-recipe-fields cooked-shortcodes-fields">

    <?php do_action( 'cooked_recipe_shortcodes_before' ); ?>

    <?php foreach ( $cooked_shortcode_groups as $cooked_group_key => $cooked_group ) : ?>
        <section class="cooked-recipe-section">
            <div class="cooked-recipe-section-head">
                <h3 class="cooked-recipe-section-title"><?php echo esc_html( $cooked_group['title'] ); ?></h3>
                <?php if ( ! empty( $cooked_group['desc'] ) ) : ?>
                    <p class="cooked-recipe-section-desc"><?php echo esc_html( $cooked_group['desc'] ); ?></p>
                <?php endif; ?>
            </div>

            <ul class="cooked-shortcode-list">
                <?php
                foreach ( $cooked_group['items'] as $cooked_tag => $cooked_item ) :
                    $cooked_code_id = 'cooked-sc-' . sanitize_html_class( $cooked_group_key . '-' . $cooked_tag );
                    ?>
                    <li class="cooked-shortcode-item">
                        <div class="cooked-shortcode-main">
                            <span class="cooked-shortcode-icon" aria-hidden="true"><i class="cooked-icon cooked-icon-<?php echo esc_attr( isset( $cooked_item['icon'] ) ? $cooked_item['icon'] : 'code' ); ?>"></i></span>
                            <div class="cooked-shortcode-text">
                                <h4 class="cooked-shortcode-title"><?php echo esc_html( $cooked_item['title'] ); ?></h4>
                                <p class="cooked-shortcode-desc"><?php echo esc_html( $cooked_item['desc'] ); ?></p>
                            </div>
                            <div class="cooked-shortcode-copy">
                                <code id="<?php echo esc_attr( $cooked_code_id ); ?>"><?php echo esc_html( $cooked_item['code'] ); ?></code>
                                <button type="button" class="button cooked-copy-code" data-target="<?php echo esc_attr( $cooked_code_id ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: shortcode name, e.g. "Ingredients" */ __( 'Copy the %s shortcode', 'cooked' ), $cooked_item['title'] ) ); ?>"><?php esc_html_e( 'Copy', 'cooked' ); ?></button>
                            </div>
                        </div>

                        <?php if ( ! empty( $cooked_item['options'] ) ) : ?>
                            <details class="cooked-shortcode-options">
                                <summary>
                                    <i class="cooked-icon cooked-icon-angle-right" aria-hidden="true"></i>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: %d: number of shortcode options */
                                            _n( '%d option', '%d options', count( $cooked_item['options'] ), 'cooked' ),
                                            count( $cooked_item['options'] )
                                        )
                                    );
                                    ?>
                                </summary>
                                <div class="cooked-shortcode-options-body">
                                    <table class="cooked-shortcode-options-table">
                                        <thead>
                                            <tr>
                                                <th scope="col"><?php esc_html_e( 'Option', 'cooked' ); ?></th>
                                                <th scope="col"><?php esc_html_e( 'What it does', 'cooked' ); ?></th>
                                                <th scope="col"><?php esc_html_e( 'Values', 'cooked' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $cooked_item['options'] as $cooked_option => $cooked_option_info ) : ?>
                                                <tr>
                                                    <th scope="row"><code><?php echo esc_html( $cooked_option ); ?></code></th>
                                                    <td><?php echo esc_html( $cooked_option_info[0] ); ?></td>
                                                    <td class="cooked-shortcode-values"><?php echo esc_html( $cooked_option_info[1] ); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if ( ! empty( $cooked_item['names'][1] ) ) : ?>
                                        <div class="cooked-shortcode-names">
                                            <span class="cooked-shortcode-example-label"><?php echo esc_html( $cooked_item['names'][0] ); ?></span>
                                            <ul>
                                                <?php foreach ( $cooked_item['names'][1] as $cooked_name => $cooked_name_label ) : ?>
                                                    <li><code><?php echo esc_html( $cooked_name ); ?></code> <?php echo esc_html( $cooked_name_label ); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $cooked_item['example'] ) ) : ?>
                                        <div class="cooked-shortcode-example">
                                            <span class="cooked-shortcode-example-label"><?php esc_html_e( 'Example', 'cooked' ); ?></span>
                                            <div class="cooked-shortcode-copy">
                                                <code id="<?php echo esc_attr( $cooked_code_id ); ?>-example"><?php echo esc_html( $cooked_item['example'] ); ?></code>
                                                <button type="button" class="button cooked-copy-code" data-target="<?php echo esc_attr( $cooked_code_id ); ?>-example" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: shortcode name, e.g. "Ingredients" */ __( 'Copy the %s example', 'cooked' ), $cooked_item['title'] ) ); ?>"><?php esc_html_e( 'Copy', 'cooked' ); ?></button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>

    <?php do_action( 'cooked_recipe_shortcodes_after' ); ?>

</div>
