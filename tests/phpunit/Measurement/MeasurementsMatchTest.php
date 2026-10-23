<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeasurementsMatchTest extends TestCase {

    public static function units_provider(): array {
        return [
            'key'                  => [ 'cup', 'cup' ],
            'plural variation'     => [ 'cups', 'cup' ],
            'capitalised plural'   => [ 'Tablespoons', 'tbsp' ],
            'abbreviation and dot' => [ 'tbsp.', 'tbsp' ],
            'singular name'        => [ 'teaspoon', 'tsp' ],
            'upper case'           => [ 'CUP', 'cup' ],
            'surrounding spaces'   => [ '  g  ', 'g' ],
        ];
    }

    #[DataProvider( 'units_provider' )]
    public function test_match_maps_written_units_to_keys( string $unit, string $key ) {
        $this->assertSame( $key, Cooked_Measurements::match( $unit ) );
    }

    public function test_match_returns_false_for_unknown_or_empty_units() {
        $this->assertFalse( Cooked_Measurements::match( 'furlong' ) );
        $this->assertFalse( Cooked_Measurements::match( '' ) );
        $this->assertFalse( Cooked_Measurements::match( '.' ) );
    }

    public function test_import_parts_map_a_known_unit() {
        $this->assertSame(
            [ 'amount' => '1 1/2', 'measurement' => 'cup', 'name' => 'all-purpose flour' ],
            Cooked_Measurements::import_ingredient_parts( '1 1/2', 'cups', 'all-purpose flour' )
        );
    }

    public function test_import_parts_keep_an_unknown_unit_in_the_name() {
        $this->assertSame(
            [ 'amount' => '1', 'measurement' => '', 'name' => 'large egg' ],
            Cooked_Measurements::import_ingredient_parts( '1', 'large', 'egg' )
        );
    }

    public function test_import_parts_without_a_unit() {
        $this->assertSame(
            [ 'amount' => '3', 'measurement' => '', 'name' => 'ripe bananas' ],
            Cooked_Measurements::import_ingredient_parts( '3', '', ' ripe bananas ' )
        );
    }
}
