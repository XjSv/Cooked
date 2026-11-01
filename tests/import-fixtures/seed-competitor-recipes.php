<?php
/**
 * Seeds sample recipes in WP Recipe Maker, WP Delicious and Tasty Recipes (Lite), to test
 * Cooked's importers by hand. Each plugin's own API creates its recipes, so the stored data
 * matches what real sites have. Re-running replaces the previous sample recipes.
 *
 * The recipes cover what importers tend to get wrong: unit spellings Cooked must map ("cups",
 * "tbsp.", "Tablespoons", "pinch"), fractions and ranges, per-ingredient notes, titled
 * ingredient and instruction sections, and a recipe with no sections.
 *
 * Run (wp-env):  bunx wp-env run cli wp eval-file wp-content/plugins/cooked/tests/import-fixtures/seed-competitor-recipes.php
 * Plugins: wp-recipe-maker, delicious-recipes, tasty-recipes-lite (each is skipped if inactive).
 *
 * @package Cooked
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions -- A WP-CLI dev script.

$cooked_seed_marker = '_cooked_import_fixture';

/**
 * The sample recipes, in a neutral shape each plugin's seeder converts.
 *
 * @return array<int, array<string, mixed>>
 */
function cooked_seed_fixture_recipes() {
    return [
        [
            'title'        => 'Classic Banana Bread',
            'summary'      => 'Moist banana bread with a crackly top.',
            'servings'     => 8,
            'prep'         => 15,
            'cook'         => 60,
            'ingredients'  => [
                [
                    'title' => 'For the batter',
                    'items' => [
                        [ '3', '', 'ripe bananas', 'mashed' ],
                        [ '1/3', 'cup', 'melted butter', '' ],
                        [ '½', 'cups', 'brown sugar', 'packed' ],
                        [ '1', 'large', 'egg', 'beaten' ],
                        [ '1', 'tsp', 'vanilla extract', '' ],
                        [ '1 1/2', 'cups', 'all-purpose flour', '' ],
                        [ '1', 'tsp', 'baking soda', '' ],
                        [ '', 'pinch', 'salt', '' ],
                    ],
                ],
                [
                    'title' => 'For the topping',
                    'items' => [
                        [ '2', 'tbsp.', 'demerara sugar', '' ],
                        [ '1/4', 'teaspoon', 'cinnamon', '' ],
                    ],
                ],
            ],
            'instructions' => [
                [
                    'title' => 'Make the batter',
                    'steps' => [
                        'Heat the oven to 175°C (350°F) and butter a loaf pan.',
                        'Mash the bananas, then stir in the butter, sugar, egg and vanilla.',
                        'Fold in the flour, baking soda and salt until just combined.',
                    ],
                ],
                [
                    'title' => 'Bake',
                    'steps' => [
                        'Pour into the pan, sprinkle with the topping and bake 55–65 minutes.',
                        'Cool 10 minutes in the pan, then on a rack.',
                    ],
                ],
            ],
            'notes'        => 'Overripe, spotty bananas give the best flavour.',
        ],
        [
            'title'        => 'Weeknight Tomato Pasta',
            'summary'      => 'A 20-minute pantry pasta.',
            'servings'     => 4,
            'prep'         => 5,
            'cook'         => 15,
            'ingredients'  => [
                [
                    'title' => '',
                    'items' => [
                        [ '400', 'g', 'spaghetti', '' ],
                        [ '3', 'Tablespoons', 'olive oil', 'extra virgin' ],
                        [ '2-3', 'cloves', 'garlic', 'thinly sliced' ],
                        [ '1', 'can', 'crushed tomatoes', '400 g' ],
                        [ '1/2', 'tsp', 'chilli flakes', 'optional' ],
                        [ '', '', 'fresh basil', 'to serve' ],
                    ],
                ],
            ],
            'instructions' => [
                [
                    'title' => '',
                    'steps' => [
                        'Cook the spaghetti in well-salted water until al dente.',
                        'Meanwhile, warm the oil and garlic until fragrant, then add the tomatoes and chilli.',
                        'Toss the pasta with the sauce and a splash of pasta water. Top with basil.',
                    ],
                ],
            ],
            'notes'        => '',
        ],
        [
            'title'        => 'Overnight Oats',
            'summary'      => 'No-cook breakfast, ready when you wake up.',
            'servings'     => 1,
            'prep'         => 5,
            'cook'         => 0,
            'ingredients'  => [
                [
                    'title' => 'Base',
                    'items' => [
                        [ '1/2', 'cup', 'rolled oats', '' ],
                        [ '2/3', 'cup', 'milk', 'any kind' ],
                        [ '1', 'Tbsp', 'chia seeds', '' ],
                    ],
                ],
                [
                    'title' => 'Toppings',
                    'items' => [
                        [ '1', 'handful', 'berries', '' ],
                        [ '1', 'tsp', 'honey', 'or maple syrup' ],
                    ],
                ],
            ],
            'instructions' => [
                [
                    'title' => '',
                    'steps' => [
                        'Stir the oats, milk and chia together in a jar.',
                        'Cover and refrigerate overnight. Add the toppings before eating.',
                    ],
                ],
            ],
            'notes'        => 'Keeps 3 days in the fridge.',
        ],
    ];
}

/**
 * Deletes the sample recipes (and their posts) a previous run created.
 *
 * @param string $post_type Post type.
 * @return void
 */
function cooked_seed_delete_previous( $post_type ) {
    global $cooked_seed_marker;
    $ids = get_posts(
        [
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => $cooked_seed_marker, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        ]
    );
    foreach ( $ids as $id ) {
        wp_delete_post( $id, true );
    }
}

/**
 * WP Recipe Maker, through WPRM_Recipe_Saver.
 *
 * @return void
 */
function cooked_seed_wprm() {
    global $cooked_seed_marker;
    if ( ! class_exists( 'WPRM_Recipe_Saver' ) ) {
        WP_CLI::warning( 'WP Recipe Maker is not active; skipped.' );
        return;
    }
    cooked_seed_delete_previous( 'wprm_recipe' );

    foreach ( cooked_seed_fixture_recipes() as $recipe ) {
        $ingredients = [];
        foreach ( $recipe['ingredients'] as $group ) {
            $items = [];
            foreach ( $group['items'] as $item ) {
                $items[] = [
                    'amount' => $item[0],
                    'unit'   => $item[1],
                    'name'   => $item[2],
                    'notes'  => $item[3],
                ];
            }
            $ingredients[] = [
                'name'        => $group['title'],
                'ingredients' => $items,
            ];
        }

        $instructions = [];
        foreach ( $recipe['instructions'] as $group ) {
            $instructions[] = [
                'name'         => $group['title'],
                'instructions' => array_map(
                    function ( $step ) {
                        return [ 'text' => '<p>' . $step . '</p>' ];
                    },
                    $group['steps']
                ),
            ];
        }

        $data = WPRM_Recipe_Sanitizer::sanitize(
            [
                'type'         => 'food',
                'name'         => $recipe['title'],
                'summary'      => $recipe['summary'],
                'servings'     => $recipe['servings'],
                'prep_time'    => $recipe['prep'],
                'cook_time'    => $recipe['cook'],
                'total_time'   => $recipe['prep'] + $recipe['cook'],
                'ingredients'  => $ingredients,
                'instructions' => $instructions,
                'notes'        => $recipe['notes'] ? '<p>' . $recipe['notes'] . '</p>' : '',
            ]
        );
        $id   = WPRM_Recipe_Saver::create_recipe( $data );
        wp_update_post(
            [
                'ID'          => $id,
                'post_status' => 'publish',
            ]
        );
        update_post_meta( $id, $cooked_seed_marker, 1 );
        WP_CLI::log( "WP Recipe Maker: {$recipe['title']} (#{$id})" );
    }
}

/**
 * WP Delicious: a "recipe" post with its delicious_recipes_metadata.
 *
 * @return void
 */
function cooked_seed_delicious() {
    global $cooked_seed_marker;
    if ( ! post_type_exists( 'recipe' ) || ! function_exists( 'delicious_recipes_get_recipe' ) && ! class_exists( 'Delicious_Recipes' ) ) {
        WP_CLI::warning( 'WP Delicious is not active; skipped.' );
        return;
    }
    cooked_seed_delete_previous( 'recipe' );

    foreach ( cooked_seed_fixture_recipes() as $recipe ) {
        $ingredients = [];
        foreach ( $recipe['ingredients'] as $group ) {
            $ingredients[] = [
                'sectionTitle' => $group['title'],
                'ingredients'  => array_map(
                    function ( $item ) {
                        return [
                            'quantity'   => $item[0],
                            'unit'       => $item[1],
                            'ingredient' => $item[2],
                            'notes'      => $item[3],
                        ];
                    },
                    $group['items']
                ),
            ];
        }

        $instructions = [];
        foreach ( $recipe['instructions'] as $group ) {
            $instructions[] = [
                'sectionTitle' => $group['title'],
                'instruction'  => array_map(
                    function ( $step ) {
                        return [
                            'instructionTitle' => '',
                            'instruction'      => $step,
                            'image'            => '',
                            'videoURL'         => '',
                            'instructionNotes' => '',
                        ];
                    },
                    $group['steps']
                ),
            ];
        }

        $id = wp_insert_post(
            [
                'post_type'    => 'recipe',
                'post_status'  => 'publish',
                'post_title'   => $recipe['title'],
                'post_content' => '',
            ]
        );
        update_post_meta(
            $id,
            'delicious_recipes_metadata',
            [
                'recipeDescription'  => $recipe['summary'],
                'recipeNotes'        => $recipe['notes'],
                'difficultyLevel'    => 'beginner',
                'prepTime'           => (string) $recipe['prep'],
                'prepTimeUnit'       => 'min',
                'cookTime'           => (string) $recipe['cook'],
                'cookTimeUnit'       => 'min',
                'noOfServings'       => (string) $recipe['servings'],
                'ingredientTitle'    => 'Ingredients',
                'instructionsTitle'  => 'Instructions',
                'recipeIngredients'  => $ingredients,
                'recipeInstructions' => $instructions,
            ]
        );
        update_post_meta( $id, $cooked_seed_marker, 1 );
        WP_CLI::log( "WP Delicious: {$recipe['title']} (#{$id})" );
    }
}

/**
 * Tasty Recipes: a tasty_recipe (ingredients and instructions as HTML lists, sections as
 * headings), embedded in a published post with the plugin's block.
 *
 * @return void
 */
function cooked_seed_tasty() {
    global $cooked_seed_marker;
    if ( ! class_exists( 'Tasty_Recipes\Objects\Recipe' ) ) {
        WP_CLI::warning( 'Tasty Recipes is not active; skipped.' );
        return;
    }
    cooked_seed_delete_previous( 'tasty_recipe' );
    cooked_seed_delete_previous( 'post' );

    foreach ( cooked_seed_fixture_recipes() as $recipe ) {
        $ingredients = '';
        foreach ( $recipe['ingredients'] as $group ) {
            $ingredients .= $group['title'] ? '<h4>' . $group['title'] . '</h4>' : '';
            $ingredients .= '<ul>';
            foreach ( $group['items'] as $item ) {
                $line         = trim( implode( ' ', array_filter( [ $item[0], $item[1], $item[2] ] ) ) );
                $ingredients .= '<li>' . $line . ( $item[3] ? ', ' . $item[3] : '' ) . '</li>';
            }
            $ingredients .= '</ul>';
        }

        $instructions = '';
        foreach ( $recipe['instructions'] as $group ) {
            $instructions .= $group['title'] ? '<h4>' . $group['title'] . '</h4>' : '';
            $instructions .= '<ol><li>' . implode( '</li><li>', $group['steps'] ) . '</li></ol>';
        }

        $tasty = Tasty_Recipes\Objects\Recipe::create();
        $tasty->set_title( $recipe['title'] );
        $tasty->set_description( '<p>' . $recipe['summary'] . '</p>' );
        $tasty->set_ingredients( $ingredients );
        $tasty->set_instructions( $instructions );
        $tasty->set_notes( $recipe['notes'] ? '<p>' . $recipe['notes'] . '</p>' : '' );
        $tasty->set_prep_time( $recipe['prep'] . ' minutes' );
        $tasty->set_cook_time( $recipe['cook'] ? $recipe['cook'] . ' minutes' : '' );
        $tasty->set_total_time( ( $recipe['prep'] + $recipe['cook'] ) . ' minutes' );
        $tasty->set_yield( (string) $recipe['servings'] );
        $tasty_id = $tasty->get_id();
        update_post_meta( $tasty_id, $cooked_seed_marker, 1 );

        $post_id = wp_insert_post(
            [
                'post_type'    => 'post',
                'post_status'  => 'publish',
                'post_title'   => $recipe['title'] . ' (Tasty)',
                'post_content' => '<!-- wp:wp-tasty/tasty-recipe {"id":' . $tasty_id . '} /-->',
            ]
        );
        update_post_meta( $post_id, $cooked_seed_marker, 1 );
        WP_CLI::log( "Tasty Recipes: {$recipe['title']} (#{$tasty_id}, in post #{$post_id})" );
    }
}

cooked_seed_wprm();
cooked_seed_delicious();
cooked_seed_tasty();
WP_CLI::success( 'Sample recipes seeded.' );
