<?php

use PHPUnit\Framework\TestCase;

class IngredientSubstitutionsTest extends TestCase {

    public function test_reads_the_list() {
        $ing = [
            'name'          => 'butter',
            'substitutions' => [
                [ 'amount' => '1', 'measurement' => 'cup', 'name' => 'margarine' ],
                [ 'amount' => '3/4', 'measurement' => 'cup', 'name' => 'coconut oil' ],
            ],
        ];
        $subs = Cooked_Recipes::ingredient_substitutions( $ing );
        $this->assertCount( 2, $subs );
        $this->assertSame( 'coconut oil', $subs[1]['name'] );
    }

    public function test_reads_a_recipe_saved_before_the_list() {
        $ing  = [ 'name' => 'egg', 'sub_amount' => '1', 'sub_measurement' => '', 'sub_name' => 'flax egg' ];
        $subs = Cooked_Recipes::ingredient_substitutions( $ing );
        $this->assertSame( [ [ 'amount' => '1', 'measurement' => '', 'name' => 'flax egg' ] ], $subs );
    }

    public function test_skips_entries_without_a_name_and_bad_values() {
        $ing = [
            'substitutions' => [
                [ 'amount' => '2', 'measurement' => 'tbsp', 'name' => '  ' ],
                'not an array',
                [ 'amount' => [ 'x' ], 'name' => 'honey' ],
            ],
        ];
        $this->assertSame( [ [ 'amount' => '', 'measurement' => '', 'name' => 'honey' ] ], Cooked_Recipes::ingredient_substitutions( $ing ) );
        $this->assertSame( [], Cooked_Recipes::ingredient_substitutions( [ 'name' => 'salt' ] ) );
    }

    public function test_an_empty_list_wins_over_old_fields() {
        $ing = [ 'substitutions' => [], 'sub_name' => 'stale' ];
        $this->assertSame( [], Cooked_Recipes::ingredient_substitutions( $ing ) );
    }

    public function test_with_substitutions_mirrors_the_first_into_the_old_fields() {
        $ing = Cooked_Recipes::with_substitutions(
            [ 'name' => 'milk' ],
            [
                [ 'amount' => '1', 'measurement' => 'cup', 'name' => 'oat milk' ],
                [ 'amount' => '1', 'measurement' => 'cup', 'name' => 'soy milk' ],
            ]
        );
        $this->assertCount( 2, $ing['substitutions'] );
        $this->assertSame( 'oat milk', $ing['sub_name'] );
        $this->assertSame( 'cup', $ing['sub_measurement'] );

        $cleared = Cooked_Recipes::with_substitutions( $ing, [] );
        $this->assertSame( [], $cleared['substitutions'] );
        $this->assertSame( '', $cleared['sub_name'] );
    }

    public function test_plain_text_lists_every_substitute() {
        $GLOBALS['recipe_settings'] = [ 'nutrition' => [ 'servings' => 1 ] ];
        $text = Cooked_Recipes::single_ingredient(
            [
                'amount'        => '1',
                'measurement'   => 'cup',
                'name'          => 'butter',
                'substitutions' => [
                    [ 'amount' => '1', 'measurement' => 'cup', 'name' => 'margarine' ],
                    [ 'amount' => '', 'measurement' => '', 'name' => 'coconut oil' ],
                ],
            ],
            false,
            true
        );
        $this->assertStringContainsString( 'butter (or 1 cup margarine, or coconut oil)', $text );
    }
}
