<?php

class UnitConverterFiltersTest extends FilterTestCase {

	public function test_cooked_unit_conversion_target_can_change_the_unit() {
		$target = $this->with_filter(
			'cooked_unit_conversion_target',
			function ( $target, $from, $amount, $name, $system ) {
				$this->assertSame( 'cup', $from );
				$this->assertSame( 'water', $name );
				$this->assertSame( 'metric', $system );
				return 'l';
			},
			function () {
				return Cooked_Unit_Converter::resolve_target_unit( 'cup', 1, 'water', 'metric' );
			}
		);

		$this->assertSame( 'l', $target );
	}

	public function test_cooked_unit_conversion_target_null_suppresses_conversion() {
		$target = $this->with_filter(
			'cooked_unit_conversion_target',
			function () {
				return null;
			},
			function () {
				return Cooked_Unit_Converter::resolve_target_unit( 'cup', 1, 'water', 'metric' );
			}
		);

		$this->assertNull( $target );
	}

	public function test_cooked_unit_conversion_rounding_can_keep_the_exact_amount() {
		$rounded = $this->with_filter(
			'cooked_unit_conversion_rounding',
			function ( $rounded, $amount, $unit ) {
				$this->assertSame( 'ml', $unit );
				return $amount;
			},
			function () {
				return Cooked_Unit_Converter::round_converted_amount( 236.588, 'ml' );
			}
		);

		$this->assertEqualsWithDelta( 236.588, $rounded, 0.0001 );
	}

	public function test_cooked_converted_ingredient_amount_replaces_the_result() {
		$result = $this->with_filter(
			'cooked_converted_ingredient_amount',
			function ( $amount, $unit, $from, $original, $name ) {
				$this->assertSame( 'ml', $unit );
				$this->assertSame( 'cup', $from );
				$this->assertEqualsWithDelta( 1, $original, 0.0001 );
				$this->assertSame( 'water', $name );
				return 123;
			},
			function () {
				return Cooked_Unit_Converter::convert_for_ingredient( 1, 'cup', 'water', 'metric' );
			}
		);

		$this->assertSame( 'ml', $result['unit'] );
		$this->assertSame( 123.0, $result['amount'] );
	}
}
