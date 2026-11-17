<?php
/**
 * Tests for Cooked_Unit_Converter class.
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase {

	/**
	 * @dataProvider data_volume_conversions
	 */
	public function test_volume_conversions( $amount, $from, $to, $expected, $tolerance ) {
		$result = Cooked_Unit_Converter::convert( $amount, $from, $to );
		$this->assertNotNull( $result );
		$this->assertSame( $to, $result['unit'] );
		$this->assertEqualsWithDelta( $expected, $result['amount'], $tolerance );
	}

	public function data_volume_conversions() {
		return [
			'cup to ml'   => [ 1, 'cup', 'ml', 236.5882, 0.0001 ],
			'tsp to ml'   => [ 1, 'tsp', 'ml', 4.92892, 0.00001 ],
			'tbsp to ml'  => [ 1, 'tbsp', 'ml', 14.78676, 0.00001 ],
			'tbsp to tsp' => [ 1, 'tbsp', 'tsp', 3, 0.001 ],
			'floz to ml'  => [ 1, 'floz', 'ml', 29.57353, 0.00001 ],
			'gal to l'    => [ 1, 'gal', 'l', 3.78541, 0.00001 ],
			'pt to ml'    => [ 1, 'pt', 'ml', 473.17648, 0.0001 ],
			'qt to ml'    => [ 1, 'qt', 'ml', 946.35295, 0.0001 ],
			'dl to floz'  => [ 1, 'dl', 'floz', 3.3814, 0.0001 ],
			'l to cup'    => [ 1, 'l', 'cup', 4.22675, 0.0001 ],
		];
	}

	/**
	 * @dataProvider data_weight_conversions
	 */
	public function test_weight_conversions( $amount, $from, $to, $expected, $tolerance ) {
		$result = Cooked_Unit_Converter::convert( $amount, $from, $to );
		$this->assertNotNull( $result );
		$this->assertSame( $to, $result['unit'] );
		$this->assertEqualsWithDelta( $expected, $result['amount'], $tolerance );
	}

	public function data_weight_conversions() {
		return [
			'oz to g'  => [ 1, 'oz', 'g', 28.34952, 0.0001 ],
			'g to oz'  => [ 1, 'g', 'oz', 0.035274, 0.000001 ],
			'lb to g'  => [ 1, 'lb', 'g', 453.59237, 0.0001 ],
			'kg to lb' => [ 1, 'kg', 'lb', 2.20462, 0.0001 ],
			'mg to g'  => [ 1, 'mg', 'g', 0.001, 0.0001 ],
			'g to kg'  => [ 1000, 'g', 'kg', 1, 0.0001 ],
			'oz to lb' => [ 16, 'oz', 'lb', 1, 0.001 ],
			'kg to g'  => [ 1, 'kg', 'g', 1000, 0.001 ],
			'lb to oz' => [ 1, 'lb', 'oz', 16, 0.001 ],
		];
	}

	public function test_get_target_unit() {
		$this->assertSame( 'ml', Cooked_Unit_Converter::get_target_unit( 'cup' ) );
		$this->assertNull( Cooked_Unit_Converter::get_target_unit( 'tsp' ) );
		$this->assertNull( Cooked_Unit_Converter::get_target_unit( 'tbsp' ) );
		$this->assertSame( 'ml', Cooked_Unit_Converter::get_target_unit( 'floz' ) );
		$this->assertSame( 'l', Cooked_Unit_Converter::get_target_unit( 'gal' ) );
		$this->assertSame( 'ml', Cooked_Unit_Converter::get_target_unit( 'pt' ) );
		$this->assertSame( 'ml', Cooked_Unit_Converter::get_target_unit( 'qt' ) );
		$this->assertSame( 'floz', Cooked_Unit_Converter::get_target_unit( 'dl' ) );
		$this->assertSame( 'floz', Cooked_Unit_Converter::get_target_unit( 'ml' ) );
		$this->assertSame( 'cup', Cooked_Unit_Converter::get_target_unit( 'l' ) );
		$this->assertSame( 'g', Cooked_Unit_Converter::get_target_unit( 'oz' ) );
		$this->assertSame( 'g', Cooked_Unit_Converter::get_target_unit( 'lb' ) );
		$this->assertSame( 'oz', Cooked_Unit_Converter::get_target_unit( 'g' ) );
		$this->assertSame( 'lb', Cooked_Unit_Converter::get_target_unit( 'kg' ) );
		$this->assertSame( 'g', Cooked_Unit_Converter::get_target_unit( 'mg' ) );
	}

	public function test_neutral_units_return_null() {
		$neutral_units = [ 'stick', 'dash', 'drop', 'pinch', 'drizzle', 'clove', 'jar', 'can' ];
		foreach ( $neutral_units as $unit ) {
			$this->assertNull( Cooked_Unit_Converter::convert( 1, $unit, 'ml' ) );
			$this->assertNull( Cooked_Unit_Converter::get_target_unit( $unit ) );
		}
	}

	public function test_unknown_unit_returns_null() {
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'nonexistent', 'ml' ) );
		$this->assertNull( Cooked_Unit_Converter::get_target_unit( 'nonexistent' ) );
	}

	public function test_unknown_target_returns_null() {
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'cup', 'nonexistent' ) );
	}

	public function test_cross_type_conversion_returns_null() {
		// Density conversion lives in convert_for_ingredient(), not convert().
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'cup', 'g' ) );
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'oz', 'ml' ) );
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'cup', 'oz' ) );
		$this->assertNull( Cooked_Unit_Converter::convert( 1, 'g', 'tsp' ) );
	}

	public function test_convert_zero_amount() {
		$result = Cooked_Unit_Converter::convert( 0, 'cup', 'ml' );
		$this->assertNotNull( $result );
		$this->assertSame( 'ml', $result['unit'] );
		$this->assertEquals( 0, $result['amount'] );
	}

	public function test_convert_fractional_amount() {
		$result = Cooked_Unit_Converter::convert( 0.5, 'cup', 'ml' );
		$this->assertNotNull( $result );
		$this->assertSame( 'ml', $result['unit'] );
		$this->assertEqualsWithDelta( 118.2941, $result['amount'], 0.0001 );
	}

	public function test_convert_large_amount() {
		$result = Cooked_Unit_Converter::convert( 10, 'oz', 'g' );
		$this->assertNotNull( $result );
		$this->assertSame( 'g', $result['unit'] );
		$this->assertEqualsWithDelta( 283.4952, $result['amount'], 0.0001 );
	}

	/**
	 * @dataProvider data_resolve_target_unit
	 */
	public function test_resolve_target_unit( $from, $amount, $name, $system, $expected ) {
		$this->assertSame(
			$expected,
			Cooked_Unit_Converter::resolve_target_unit( $from, $amount, $name, $system )
		);
	}

	public function data_resolve_target_unit() {
		return [
			'tsp stays'              => [ 'tsp', 0.5, 'salt', 'metric', null ],
			'tbsp stays'             => [ 'tbsp', 2, 'olive oil', 'metric', null ],
			'cup without a name'     => [ 'cup', 1, null, 'metric', 'ml' ],
			'large cups step up'     => [ 'cup', 8, null, 'metric', 'l' ],
			'one pound stays grams'  => [ 'lb', 1, null, 'metric', 'g' ],
			'four pounds step up'    => [ 'lb', 4, null, 'metric', 'kg' ],
			'five ml is a teaspoon'  => [ 'ml', 5, null, 'imperial', 'tsp' ],
			'fifteen ml is a tablespoon' => [ 'ml', 15, null, 'imperial', 'tbsp' ],
			'thirty ml is tablespoons' => [ 'ml', 30, null, 'imperial', 'tbsp' ],
			'sixty ml is fluid ounces' => [ 'ml', 60, null, 'imperial', 'floz' ],
			'two hundred forty ml is a cup' => [ 'ml', 240, null, 'imperial', 'cup' ],
			'plain grams are ounces' => [ 'g', 100, null, 'imperial', 'oz' ],
			'unknown unit'           => [ 'nonexistent', 1, null, 'metric', null ],
		];
	}

	/**
	 * @dataProvider data_round_converted_amount
	 */
	public function test_round_converted_amount( $amount, $unit, $expected ) {
		$this->assertEqualsWithDelta(
			$expected,
			Cooked_Unit_Converter::round_converted_amount( $amount, $unit ),
			0.0001
		);
	}

	public function data_round_converted_amount() {
		return [
			'under ten ml'           => [ 9.9, 'ml', 10 ],
			'twelve ml'              => [ 12, 'ml', 10 ],
			'thirteen ml'            => [ 13, 'ml', 15 ],
			'one cup in ml'          => [ 236.588, 'ml', 235 ],
			'eight cups in litres'   => [ 1.89271, 'l', 1.9 ],
			'four ounces in grams'   => [ 113.398, 'g', 115 ],
			'four pounds in kilograms' => [ 1.81437, 'kg', 1.8 ],
			'cup fraction stays'     => [ 1.25, 'cup', 1.25 ],
			'seven ounces'           => [ 7.05, 'oz', 7 ],
			'three and a half ounces' => [ 3.527, 'oz', 3.5 ],
		];
	}
}
