<?php
/**
 * Tests for the Cooked\Measurement\UnitConverter class.
 *
 * @package Cooked
 * @subpackage Tests
 */

use Cooked\Measurement\UnitConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase {

    #[DataProvider( 'data_volume_conversions' )]
    public function test_volume_conversions( $amount, $from, $to, $expected, $tolerance ) {
        $result = UnitConverter::convert( $amount, $from, $to );
        $this->assertNotNull( $result );
        $this->assertSame( $to, $result['unit'] );
        $this->assertEqualsWithDelta( $expected, $result['amount'], $tolerance );
    }

    public static function data_volume_conversions() {
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

    #[DataProvider( 'data_weight_conversions' )]
    public function test_weight_conversions( $amount, $from, $to, $expected, $tolerance ) {
        $result = UnitConverter::convert( $amount, $from, $to );
        $this->assertNotNull( $result );
        $this->assertSame( $to, $result['unit'] );
        $this->assertEqualsWithDelta( $expected, $result['amount'], $tolerance );
    }

    public static function data_weight_conversions() {
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
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'cup' ) );
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'tsp' ) );
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'tbsp' ) );
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'floz' ) );
        $this->assertSame( 'l', UnitConverter::get_target_unit( 'gal' ) );
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'pt' ) );
        $this->assertSame( 'ml', UnitConverter::get_target_unit( 'qt' ) );
        $this->assertSame( 'floz', UnitConverter::get_target_unit( 'dl' ) );
        $this->assertSame( 'floz', UnitConverter::get_target_unit( 'ml' ) );
        $this->assertSame( 'cup', UnitConverter::get_target_unit( 'l' ) );
        $this->assertSame( 'g', UnitConverter::get_target_unit( 'oz' ) );
        $this->assertSame( 'g', UnitConverter::get_target_unit( 'lb' ) );
        $this->assertSame( 'oz', UnitConverter::get_target_unit( 'g' ) );
        $this->assertSame( 'lb', UnitConverter::get_target_unit( 'kg' ) );
        $this->assertSame( 'g', UnitConverter::get_target_unit( 'mg' ) );
    }

    public function test_neutral_units_return_null() {
        $neutral_units = [ 'stick', 'dash', 'drop', 'pinch', 'drizzle', 'clove', 'jar', 'can' ];
        foreach ( $neutral_units as $unit ) {
            $this->assertNull( UnitConverter::convert( 1, $unit, 'ml' ) );
            $this->assertNull( UnitConverter::get_target_unit( $unit ) );
        }
    }

    public function test_unknown_unit_returns_null() {
        $this->assertNull( UnitConverter::convert( 1, 'nonexistent', 'ml' ) );
        $this->assertNull( UnitConverter::get_target_unit( 'nonexistent' ) );
    }

    public function test_unknown_target_returns_null() {
        $this->assertNull( UnitConverter::convert( 1, 'cup', 'nonexistent' ) );
    }

    public function test_cross_type_conversion_returns_null() {
        $this->assertNull( UnitConverter::convert( 1, 'cup', 'g' ) );
        $this->assertNull( UnitConverter::convert( 1, 'oz', 'ml' ) );
        $this->assertNull( UnitConverter::convert( 1, 'cup', 'oz' ) );
        $this->assertNull( UnitConverter::convert( 1, 'g', 'tsp' ) );
    }

    public function test_convert_zero_amount() {
        $result = UnitConverter::convert( 0, 'cup', 'ml' );
        $this->assertNotNull( $result );
        $this->assertSame( 'ml', $result['unit'] );
        $this->assertEquals( 0, $result['amount'] );
    }

    public function test_convert_fractional_amount() {
        $result = UnitConverter::convert( 0.5, 'cup', 'ml' );
        $this->assertNotNull( $result );
        $this->assertSame( 'ml', $result['unit'] );
        $this->assertEqualsWithDelta( 118.2941, $result['amount'], 0.0001 );
    }

    public function test_convert_large_amount() {
        $result = UnitConverter::convert( 10, 'oz', 'g' );
        $this->assertNotNull( $result );
        $this->assertSame( 'g', $result['unit'] );
        $this->assertEqualsWithDelta( 283.4952, $result['amount'], 0.0001 );
    }

    public function test_pre_2_0_class_name_still_works() {
        $this->assertTrue( class_exists( 'Cooked_Unit_Converter' ) );
        $this->assertSame( UnitConverter::class, get_class( new \Cooked_Unit_Converter() ) );
        $this->assertSame( 'ml', \Cooked_Unit_Converter::get_target_unit( 'cup' ) );
    }
}
