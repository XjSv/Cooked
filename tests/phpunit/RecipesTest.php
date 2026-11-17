<?php

use PHPUnit\Framework\TestCase;

class RecipesTest extends TestCase {

    public function test_difficulty_levels_returns_array() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertIsArray($levels);
    }

    public function test_difficulty_levels_has_three_levels() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertCount(3, $levels);
    }

    public function test_difficulty_levels_keys() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertArrayHasKey(1, $levels);
        $this->assertArrayHasKey(2, $levels);
        $this->assertArrayHasKey(3, $levels);
    }

    public function test_difficulty_levels_values() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertSame('Beginner', $levels[1]);
        $this->assertSame('Intermediate', $levels[2]);
        $this->assertSame('Advanced', $levels[3]);
    }

    public function test_default_content_returns_string() {
        $content = Cooked_Recipes::default_content();
        $this->assertIsString($content);
    }

    public function test_default_content_contains_shortcodes() {
        $content = Cooked_Recipes::default_content();
        $this->assertStringContainsString('[cooked-info', $content);
        $this->assertStringContainsString('[cooked-ingredients]', $content);
        $this->assertStringContainsString('[cooked-directions]', $content);
    }

    public function test_vendor_checks_returns_content_when_no_vendor() {
        $result = Cooked_Recipes::vendor_checks('test content');
        $this->assertSame('test content', $result);
    }

    public function test_recipe_has_fullscreen_returns_false_for_empty_content() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(1, ''));
    }

    public function test_recipe_has_fullscreen_returns_false_for_no_cooked_info() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(1, '<p>[cooked-ingredients]</p>'));
    }

    public function test_recipe_has_fullscreen_returns_false_for_invalid_id() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(0, '[cooked-info left="fullscreen"]'));
    }

    public function test_get_c2_recipe_meta_returns_empty_array_when_no_c2_meta() {
        $result = Cooked_Recipes::get_c2_recipe_meta(1);
        $this->assertSame([], $result);
    }

    public function test_filter_default_recipe_thumbnail_returns_existing_thumbnail() {
        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 42, 1 );
        $this->assertSame( 42, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_default_for_recipe_without_thumbnail() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 99,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'cp_recipe',
        ] );

        $this->assertSame( 99, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_original_for_non_recipe_post() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 99,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'post',
        ] );

        $this->assertSame( 0, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_original_when_no_default_set() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 0,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'cp_recipe',
        ] );

        $this->assertSame( 0, $result );
    }

    public function test_format_content_does_not_decode_script_entities() {
        $stored = '&lt;script type=&quot;text/javascript&quot;&gt;alert(1)&lt;/script&gt;';
        $result = Cooked_Recipes::format_content( $stored );

        $this->assertStringNotContainsString( '<script', $result );
        $this->assertStringContainsString( '&lt;script', $result );
    }

    public function test_format_content_does_not_decode_img_entities() {
        $stored = '&lt;img src="https://evil.example/px.gif"&gt;';
        $result = Cooked_Recipes::format_content( $stored );

        $this->assertStringNotContainsString( '<img', $result );
        $this->assertStringContainsString( '&lt;img', $result );
    }

    public function test_format_content_keeps_allowed_html() {
        $result = Cooked_Recipes::format_content( '<b>Yum</b>' );

        $this->assertStringContainsString( '<b>Yum</b>', $result );
    }

    public function test_sync_c2_recipe_settings_reads_serialized_ingredient_rows() {
        $settings = Cooked_Recipes::sync_c2_recipe_settings( [
            '_cp_recipe_detailed_ingredients' => serialize( [
                [
                    'type' => 'ingredient',
                    'name' => 'flour',
                    'amount' => '1',
                    'measurement' => 'cup',
                ],
            ] ),
        ], 1 );

        $this->assertContains( 'flour', array_column( $settings['ingredients'], 'name' ) );
    }

    public function test_sync_c2_recipe_settings_reads_ingredient_row_arrays() {
        $settings = Cooked_Recipes::sync_c2_recipe_settings( [
            '_cp_recipe_detailed_ingredients' => [
                [
                    'type' => 'ingredient',
                    'name' => 'flour',
                    'amount' => '1',
                    'measurement' => 'cup',
                ],
            ],
        ], 1 );

        $this->assertContains( 'flour', array_column( $settings['ingredients'], 'name' ) );
    }

    public function test_sync_c2_recipe_settings_ignores_serialized_objects() {
        $payload = new Cooked_C2_Object_Fixture();
        $settings = Cooked_Recipes::sync_c2_recipe_settings( [
            '_cp_recipe_detailed_ingredients' => serialize( $payload ),
            '_cp_recipe_detailed_directions' => serialize( $payload ),
        ], 1 );

        $ingredients = isset( $settings['ingredients'] ) ? $settings['ingredients'] : [];
        $directions = isset( $settings['directions'] ) ? $settings['directions'] : [];

        $this->assertNotContains( 'pwned-name', array_column( $ingredients, 'name' ) );
        $this->assertNotContains( 'pwned-amount', array_column( $ingredients, 'amount' ) );
        $this->assertNotContains( 'pwned-direction', array_column( $directions, 'content' ) );
        $this->assertNotContains( 'pwned-direction', array_column( $directions, 'section_heading_name' ) );
    }

    public function test_recipe_measurement_system_counts_imperial_units() {
        $system = Cooked_Recipes::recipe_measurement_system( [
            'ingredients' => [
                [ 'measurement' => 'cup' ],
                [ 'measurement' => 'tbsp' ],
            ],
        ] );

        $this->assertSame( 'imperial', $system );
    }

    public function test_recipe_measurement_system_counts_metric_units() {
        $system = Cooked_Recipes::recipe_measurement_system( [
            'ingredients' => [
                [ 'measurement' => 'g' ],
                [ 'measurement' => 'ml' ],
            ],
        ] );

        $this->assertSame( 'metric', $system );
    }

    public function test_recipe_measurement_system_uses_the_majority_including_substitutions() {
        $system = Cooked_Recipes::recipe_measurement_system( [
            'ingredients' => [
                [ 'measurement' => 'cup' ],
                [ 'measurement' => 'cup', 'sub_measurement' => 'g' ],
                [ 'measurement' => 'tsp' ],
                [ 'measurement' => 'pinch' ],
            ],
        ] );

        $this->assertSame( 'imperial', $system );
    }

    public function test_recipe_measurement_system_falls_back_to_metric_without_system_units() {
        $system = Cooked_Recipes::recipe_measurement_system( [
            'ingredients' => [
                [ 'measurement' => 'pinch' ],
                [ 'measurement' => 'stick' ],
            ],
        ] );

        $this->assertSame( 'metric', $system );
    }

    public function test_measurement_system_switcher_labels_the_authored_system() {
        $GLOBALS['_cooked_settings'] = [
            'advanced' => [ 'enable_measurement_switcher' ],
        ];
        $GLOBALS['recipe_settings'] = [
            'ingredients' => [
                [ 'measurement' => 'cup' ],
                [ 'measurement' => 'tbsp' ],
            ],
        ];
        unset( $GLOBALS['_cooked_test_query_vars']['measurement_system'] );

        ob_start();
        Cooked_Recipes::measurement_system_switcher();
        $html = ob_get_clean();

        $this->assertStringContainsString( 'Metric', $html );
        $this->assertStringContainsString( 'US Customary', $html );
        $this->assertStringNotContainsString( 'Default', $html );
        $this->assertStringContainsString( '<option value="" selected="selected">US Customary</option>', $html );
        $this->assertStringContainsString( '<option value="metric">Metric</option>', $html );
    }

    public function test_measurement_system_switcher_keeps_an_explicit_choice_selected() {
        $GLOBALS['_cooked_settings'] = [
            'advanced' => [ 'enable_measurement_switcher' ],
        ];
        $GLOBALS['recipe_settings'] = [
            'ingredients' => [
                [ 'measurement' => 'cup' ],
            ],
        ];
        $GLOBALS['_cooked_test_query_vars']['measurement_system'] = 'metric';

        ob_start();
        Cooked_Recipes::measurement_system_switcher();
        $html = ob_get_clean();

        $this->assertStringContainsString( '<option value="metric" selected="selected">Metric</option>', $html );
        $this->assertStringContainsString( '<option value="">US Customary</option>', $html );
        unset( $GLOBALS['_cooked_test_query_vars']['measurement_system'] );
    }

    public function test_measurement_switch_rounds_ounces_and_keeps_spoons() {
        $GLOBALS['recipe_settings'] = [
            'nutrition' => [ 'servings' => 4 ],
        ];
        $GLOBALS['_cooked_test_query_vars']['measurement_system'] = 'metric';

        ob_start();
        Cooked_Recipes::single_ingredient( [
            'name'            => 'mushrooms',
            'amount'          => '4',
            'measurement'     => 'oz',
            'sub_name'        => 'mushrooms',
            'sub_amount'      => '4',
            'sub_measurement' => 'oz',
        ] );
        $ounces = ob_get_clean();

        ob_start();
        Cooked_Recipes::single_ingredient( [
            'name'        => 'olive oil',
            'amount'      => '2',
            'measurement' => 'tbsp',
        ] );
        $spoons = ob_get_clean();

        ob_start();
        Cooked_Recipes::single_ingredient( [
            'name'        => 'salt',
            'amount'      => '1/2',
            'measurement' => 'tsp',
        ] );
        $half = ob_get_clean();

        unset( $GLOBALS['_cooked_test_query_vars']['measurement_system'] );

        $this->assertSame( 2, substr_count( $ounces, '>115<' ) );
        $this->assertStringContainsString( '>g<', $ounces );
        $this->assertStringNotContainsString( '113.40', $ounces );
        $this->assertStringContainsString( '>2<', $spoons );
        $this->assertStringContainsString( '>tbsp<', $spoons );
        $this->assertStringNotContainsString( 'ml', $spoons );
        $this->assertStringContainsString( '&#189;', $half );
        $this->assertStringContainsString( '>tsp<', $half );
        $this->assertStringNotContainsString( 'ml', $half );
    }

    public function test_unit_conversion_setting_can_turn_spoon_keeping_off() {
        $html = $this->render_switched_ingredient(
            [
                'name'        => 'olive oil',
                'amount'      => '1',
                'measurement' => 'tbsp',
            ],
            [ 'round_converted', 'density_cups' ]
        );

        $this->assertStringContainsString( '>15<', $html );
        $this->assertStringContainsString( '>ml<', $html );
        $this->assertStringNotContainsString( 'tbsp', $html );
    }

    public function test_unit_conversion_setting_can_turn_rounding_off() {
        $html = $this->render_switched_ingredient(
            [
                'name'        => 'mushrooms',
                'amount'      => '4',
                'measurement' => 'oz',
            ],
            [ 'keep_spoons', 'density_cups' ]
        );

        $this->assertStringContainsString( '>113.40<', $html );
        $this->assertStringContainsString( '>g<', $html );
    }

    public function test_unit_conversion_setting_can_turn_density_off() {
        $html = $this->render_switched_ingredient(
            [
                'name'        => 'all-purpose flour',
                'amount'      => '1',
                'measurement' => 'cup',
            ],
            [ 'keep_spoons', 'round_converted' ]
        );

        $this->assertStringContainsString( '>235<', $html );
        $this->assertStringContainsString( '>ml<', $html );
        $this->assertStringNotContainsString( '>125<', $html );
    }

    private function render_switched_ingredient( $ingredient, $flags ) {
        $GLOBALS['recipe_settings'] = [
            'nutrition' => [ 'servings' => 4 ],
        ];
        $GLOBALS['_cooked_settings']['unit_conversion'] = $flags;
        $GLOBALS['_cooked_test_query_vars']['measurement_system'] = 'metric';

        ob_start();
        Cooked_Recipes::single_ingredient( $ingredient );
        $html = ob_get_clean();

        unset( $GLOBALS['_cooked_test_query_vars']['measurement_system'] );
        unset( $GLOBALS['_cooked_settings']['unit_conversion'] );

        return $html;
    }
}

class Cooked_C2_Object_Fixture {
    public $name = 'pwned-name';
    public $amount = 'pwned-amount';
    public $value = 'pwned-direction';
}
