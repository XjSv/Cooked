<?php

use PHPUnit\Framework\TestCase;

class CSVImportTest extends TestCase {

    private static function call_private_method( $name, $args = [] ) {
        $ref = new ReflectionMethod( Cooked_CSV_Import::class, $name );
        $ref->setAccessible( true );
        return $ref->invoke( null, ...$args );
    }

    public function test_parse_ingredient_parts_returns_false_for_empty() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'parse_ingredient_parts', [ [], $measurements ] );
        $this->assertFalse( $result );
    }

    public function test_parse_ingredient_parts_with_three_parts() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'parse_ingredient_parts', [ [ '2', 'cups', 'Flour' ], $measurements ] );
        $this->assertIsArray( $result );
        $this->assertSame( '2', $result['amount'] );
        $this->assertSame( 'Flour', $result['name'] );
    }

    public function test_parse_ingredient_parts_with_two_parts() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'parse_ingredient_parts', [ [ '3', 'Eggs' ], $measurements ] );
        $this->assertIsArray( $result );
        $this->assertSame( '3', $result['amount'] );
        $this->assertSame( 'Eggs', $result['name'] );
        $this->assertSame( '', $result['measurement'] );
    }

    public function test_parse_ingredient_parts_with_one_part() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'parse_ingredient_parts', [ [ 'Salt' ], $measurements ] );
        $this->assertIsArray( $result );
        $this->assertSame( 'Salt', $result['name'] );
        $this->assertSame( '', $result['amount'] );
    }

    public function test_match_measurement_exact_key() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'match_measurement', [ 'cup', $measurements ] );
        $this->assertSame( 'cup', $result );
    }

    public function test_match_measurement_singular() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'match_measurement', [ 'teaspoon', $measurements ] );
        $this->assertSame( 'tsp', $result );
    }

    public function test_match_measurement_plural() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'match_measurement', [ 'teaspoons', $measurements ] );
        $this->assertSame( 'tsp', $result );
    }

    public function test_match_measurement_case_insensitive() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'match_measurement', [ 'CUP', $measurements ] );
        $this->assertSame( 'cup', $result );
    }

    public function test_match_measurement_returns_false_for_unknown() {
        $measurements = Cooked_Measurements::get();
        $result = self::call_private_method( 'match_measurement', [ 'furlong', $measurements ] );
        $this->assertFalse( $result );
    }
}
