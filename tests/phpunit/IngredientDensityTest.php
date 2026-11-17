<?php

class IngredientDensityTest extends FilterTestCase {

	public function test_match_finds_an_exact_key() {
		$entry = Cooked_Ingredient_Density::match( 'all-purpose flour' );

		$this->assertSame( 125, $entry['g_per_cup'] );
		$this->assertSame( 'g', $entry['target'] );
	}

	public function test_match_uses_an_alias_and_strips_a_comma_note() {
		$this->assertSame( 125, Cooked_Ingredient_Density::match( 'plain flour' )['g_per_cup'] );
		$this->assertSame( 125, Cooked_Ingredient_Density::match( 'flour, sifted' )['g_per_cup'] );
	}

	public function test_match_prefers_the_longest_key() {
		$this->assertSame( 120, Cooked_Ingredient_Density::match( 'cake flour' )['g_per_cup'] );
		$this->assertSame( 220, Cooked_Ingredient_Density::match( 'brown sugar' )['g_per_cup'] );
		$this->assertSame( 200, Cooked_Ingredient_Density::match( 'sugar' )['g_per_cup'] );
	}

	public function test_match_ignores_a_key_inside_another_word() {
		$this->assertNull( Cooked_Ingredient_Density::match( 'cauliflower' ) );
	}

	public function test_match_returns_null_for_unknown_and_non_english_names() {
		$this->assertNull( Cooked_Ingredient_Density::match( 'Mehl' ) );
		$this->assertNull( Cooked_Ingredient_Density::match( 'dragonfruit, diced' ) );
	}

	public function test_match_treats_oils_juices_and_cream_aliases_as_liquids() {
		$this->assertTrue( Cooked_Ingredient_Density::match( 'sesame oil' )['liquid'] );
		$this->assertTrue( Cooked_Ingredient_Density::match( 'lime juice' )['liquid'] );
		$this->assertTrue( Cooked_Ingredient_Density::match( 'double cream' )['liquid'] );
	}

	public function test_cup_of_flour_converts_to_grams() {
		$result = Cooked_Unit_Converter::convert_for_ingredient( 1, 'cup', 'all-purpose flour', 'metric' );

		$this->assertSame( 'g', $result['unit'] );
		$this->assertSame( 125.0, $result['amount'] );
	}

	public function test_density_examples_round_after_weighing() {
		$cases = [
			[ 2, 'cup', 'granulated sugar', 'metric', 'g', 400 ],
			[ 1, 'cup', 'quinoa, raw', 'metric', 'g', 170 ],
			[ 1, 'cup', 'milk', 'metric', 'ml', 235 ],
			[ 1, 'cup', 'water', 'metric', 'ml', 235 ],
			[ 1, 'cup', 'butter', 'metric', 'g', 225 ],
			[ 1, 'cup', 'honey', 'metric', 'g', 340 ],
			[ 1, 'cup', 'rolled oats', 'metric', 'g', 80 ],
			[ 1, 'cup', 'cocoa powder', 'metric', 'g', 85 ],
			[ 8, 'floz', 'honey', 'metric', 'g', 340 ],
			[ 1, 'pt', 'honey', 'metric', 'ml', 475 ],
			[ 1, 'cup', 'Mehl', 'metric', 'ml', 235 ],
			[ 1, 'cup', 'dragonfruit, diced', 'metric', 'ml', 235 ],
		];

		foreach ( $cases as $case ) {
			$result = Cooked_Unit_Converter::convert_for_ingredient( $case[0], $case[1], $case[2], $case[3] );
			$this->assertSame( $case[4], $result['unit'], $case[2] );
			$this->assertEqualsWithDelta( $case[5], $result['amount'], 0.001, $case[2] );
		}
	}

	public function test_reverse_grams_use_cups_only_when_the_fraction_is_close() {
		$flour = Cooked_Unit_Converter::convert_for_ingredient( 500, 'g', 'all-purpose flour', 'imperial' );
		$sugar = Cooked_Unit_Converter::convert_for_ingredient( 250, 'g', 'granulated sugar', 'imperial' );
		$more  = Cooked_Unit_Converter::convert_for_ingredient( 1000, 'g', 'flour', 'imperial' );
		$butter = Cooked_Unit_Converter::convert_for_ingredient( 200, 'g', 'butter', 'imperial' );

		$this->assertSame( 'cup', $flour['unit'] );
		$this->assertSame( 4.0, $flour['amount'] );
		$this->assertSame( 'cup', $sugar['unit'] );
		$this->assertEqualsWithDelta( 1.25, $sugar['amount'], 0.001 );
		$this->assertSame( 'cup', $more['unit'] );
		$this->assertSame( 8.0, $more['amount'] );
		$this->assertSame( 'oz', $butter['unit'] );
		$this->assertSame( 7.0, $butter['amount'] );
	}

	public function test_cooked_ingredient_density_table_can_add_an_entry() {
		$result = $this->with_filter(
			'cooked_ingredient_density_table',
			function ( $table ) {
				$table['dragonfruit'] = [
					'g_per_cup' => 100,
					'target'    => 'g',
					'source'    => 'test',
				];
				return $table;
			},
			function () {
				return Cooked_Unit_Converter::convert_for_ingredient( 1, 'cup', 'dragonfruit, diced', 'metric' );
			}
		);

		$this->assertSame( 'g', $result['unit'] );
		$this->assertSame( 100.0, $result['amount'] );
	}

	public function test_single_ingredient_weighs_a_cup_of_flour() {
		$GLOBALS['recipe_settings'] = [
			'nutrition' => [ 'servings' => 1 ],
		];
		$GLOBALS['_cooked_test_query_vars']['measurement_system'] = 'metric';

		$html = $this->capture_output( function () {
			Cooked_Recipes::single_ingredient( [
				'name'        => 'all-purpose flour',
				'amount'      => '1',
				'measurement' => 'cup',
			] );
		} );

		$this->assertStringContainsString( '>125<', $html );
		$this->assertStringContainsString( '>g<', $html );
	}
}
