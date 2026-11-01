<?php
/**
 * Tests for Cooked_Measurements class.
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class MeasurementsTest extends TestCase {

    /**
     * @var Cooked_Measurements
     */
    private $measurements;

    protected function setUp(): void {
        $this->measurements = new Cooked_Measurements();
    }

    /**
     * Test singular_plural returns singular for count 0.
     */
    public function test_singular_plural_zero_returns_singular() {
        $this->assertSame( 'gram', Cooked_Measurements::singular_plural( 'gram', 'grams', 0 ) );
    }

    /**
     * Test singular_plural returns singular for count 1.
     */
    public function test_singular_plural_one_returns_singular() {
        $this->assertSame( 'gram', Cooked_Measurements::singular_plural( 'gram', 'grams', 1 ) );
    }

    /**
     * Test singular_plural returns plural for count > 1.
     */
    public function test_singular_plural_two_returns_plural() {
        $this->assertSame( 'grams', Cooked_Measurements::singular_plural( 'gram', 'grams', 2 ) );
    }

    /**
     * Test singular_plural returns plural for large count.
     */
    public function test_singular_plural_large_count_returns_plural() {
        $this->assertSame( 'cups', Cooked_Measurements::singular_plural( 'cup', 'cups', 100 ) );
    }

    /**
     * Test math evaluates addition.
     */
    public function test_math_addition() {
        $this->assertEquals( 4, $this->measurements->math( '2+2' ) );
    }

    /**
     * Test math evaluates multiplication.
     */
    public function test_math_multiplication() {
        $this->assertEquals( 12, $this->measurements->math( '3*4' ) );
    }

    /**
     * Test math evaluates division.
     */
    public function test_math_division() {
        $this->assertEquals( 5, $this->measurements->math( '10/2' ) );
    }

    /**
     * Test math evaluates subtraction.
     */
    public function test_math_subtraction() {
        $this->assertEquals( 3, $this->measurements->math( '7-4' ) );
    }

    /**
     * Test math evaluates parenthesized expression.
     */
    public function test_math_parentheses() {
        $this->assertEquals( 14, $this->measurements->math( '(2+5)*2' ) );
    }

    /**
     * Test math returns 0 for empty expression.
     */
    public function test_math_empty_returns_zero() {
        $this->assertEquals( 0, $this->measurements->math( '' ) );
    }

    /**
     * Test math returns 0 for false expression.
     */
    public function test_math_false_returns_zero() {
        $this->assertEquals( 0, $this->measurements->math( false ) );
    }

    /**
     * Test math strips non-numeric characters.
     */
    public function test_math_strips_invalid_characters() {
        $this->assertEquals( 5, $this->measurements->math( 'abc2+3xyz' ) );
    }

    /**
     * Test calculate converts mixed number to decimal.
     */
    public function test_calculate_mixed_number_to_decimal() {
        $result = $this->measurements->calculate( '1 1/2', 'decimal' );
        $this->assertEquals( 1.5, $result );
    }

    /**
     * Test calculate converts simple fraction to decimal.
     */
    public function test_calculate_fraction_to_decimal() {
        $result = $this->measurements->calculate( '1/4', 'decimal' );
        $this->assertEquals( 0.25, $result );
    }

    /**
     * Test calculate returns float for whole number string.
     */
    public function test_calculate_whole_number_to_decimal() {
        $result = $this->measurements->calculate( '5', 'decimal' );
        $this->assertEquals( 5.0, $result );
    }

    /**
     * Test calculate returns decimal string as float.
     */
    public function test_calculate_decimal_string() {
        $result = $this->measurements->calculate( '3.5', 'decimal' );
        $this->assertEquals( 3.5, $result );
    }

    /**
     * Test float2rat converts 0.5 to 1/2.
     */
    public function test_float2rat_half() {
        $result = $this->measurements->float2rat( 0.5 );
        $this->assertSame( '1/2', $result );
    }

    /**
     * Test float2rat converts 0.25 to 1/4.
     */
    public function test_float2rat_quarter() {
        $result = $this->measurements->float2rat( 0.25 );
        $this->assertSame( '1/4', $result );
    }

    /**
     * Test float2rat converts 0.75 to 3/4.
     */
    public function test_float2rat_three_quarters() {
        $result = $this->measurements->float2rat( 0.75 );
        $this->assertSame( '3/4', $result );
    }

    /**
     * Test float2rat converts 0.125 to 1/8.
     */
    public function test_float2rat_one_eighth() {
        $result = $this->measurements->float2rat( 0.125 );
        $this->assertSame( '1/8', $result );
    }

    /**
     * Test float2rat converts 0.333... to 1/3.
     */
    public function test_float2rat_one_third() {
        $result = $this->measurements->float2rat( 1 / 3 );
        $this->assertSame( '1/3', $result );
    }

    /**
     * Test float2rat converts 0.666... to 2/3.
     */
    public function test_float2rat_two_thirds() {
        $result = $this->measurements->float2rat( 2 / 3 );
        $this->assertSame( '2/3', $result );
    }

    /**
     * Test fraction_cleaner simplifies 2/4 to 1/2.
     */
    public function test_fraction_cleaner_simplifies_two_fourths() {
        $result = $this->measurements->fraction_cleaner( '2/4' );
        $this->assertSame( '1/2', $result );
    }

    /**
     * Test fraction_cleaner simplifies 3/6 to 1/2.
     */
    public function test_fraction_cleaner_simplifies_three_sixths() {
        $result = $this->measurements->fraction_cleaner( '3/6' );
        $this->assertSame( '1/2', $result );
    }

    /**
     * Test fraction_cleaner simplifies 2/8 to 1/4.
     */
    public function test_fraction_cleaner_simplifies_two_eighths() {
        $result = $this->measurements->fraction_cleaner( '2/8' );
        $this->assertSame( '1/4', $result );
    }

    /**
     * Test get_closest_decimal finds nearest value.
     */
    public function test_get_closest_decimal_exact_match() {
        $arr = [ 0.125, 0.25, 0.5, 0.75 ];
        $result = $this->measurements->get_closest_decimal( 0.25, $arr );
        $this->assertSame( 0.25, $result );
    }

    /**
     * Test get_closest_decimal finds nearest value for in-between.
     */
    public function test_get_closest_decimal_nearest() {
        $arr = [ 0.125, 0.25, 0.5, 0.75 ];
        $result = $this->measurements->get_closest_decimal( 0.2, $arr );
        $this->assertSame( 0.25, $result );
    }

    /**
     * Test get_fraction_array returns expected structure.
     */
    public function test_get_fraction_array_structure() {
        $result = $this->measurements->get_fraction_array();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'fractions_a', $result );
        $this->assertArrayHasKey( 'fractions_b', $result );
        $this->assertArrayHasKey( 'fractions_c', $result );
        $this->assertIsArray( $result['fractions_a'] );
        $this->assertIsArray( $result['fractions_b'] );
        $this->assertIsArray( $result['fractions_c'] );
    }

    /**
     * Test get_fraction_array fractions_b contains expected values.
     */
    public function test_get_fraction_array_fractions_b_values() {
        $result = $this->measurements->get_fraction_array();
        $expected = [ '1/8', '1/6', '1/5', '1/4', '1/3', '1/2', '2/3', '5/8', '3/4', '7/8' ];

        $this->assertSame( $expected, $result['fractions_b'] );
    }

    /**
     * Test cleanup_amount strips non-numeric characters.
     */
    public function test_cleanup_amount_strips_non_numeric() {
        $result = $this->measurements->cleanup_amount( '2abc' );
        $this->assertSame( '2', $result );
    }

    /**
     * Test cleanup_amount preserves digits and slashes.
     */
    public function test_cleanup_amount_preserves_fraction() {
        $result = $this->measurements->cleanup_amount( '1/2' );
        $this->assertSame( '1/2', $result );
    }

    /**
     * Test locale_formatted removes thousands separator.
     */
    public function test_locale_formatted_removes_commas() {
        $result = $this->measurements->locale_formatted( '1,234.56' );
        $this->assertSame( '1234.56', $result );
    }

    /**
     * Test get returns measurements array with expected keys.
     */
    public function test_get_returns_measurements_array() {
        $measurements = Cooked_Measurements::get();

        $this->assertIsArray( $measurements );
        $this->assertArrayHasKey( 'g', $measurements );
        $this->assertArrayHasKey( 'kg', $measurements );
        $this->assertArrayHasKey( 'cup', $measurements );
        $this->assertArrayHasKey( 'tsp', $measurements );
        $this->assertArrayHasKey( 'tbsp', $measurements );
    }

    /**
     * Test measurement entry has required keys.
     */
    public function test_measurement_entry_structure() {
        $measurements = Cooked_Measurements::get();
        $gram = $measurements['g'];

        $this->assertArrayHasKey( 'singular_abbr', $gram );
        $this->assertArrayHasKey( 'plural_abbr', $gram );
        $this->assertArrayHasKey( 'singular', $gram );
        $this->assertArrayHasKey( 'plural', $gram );
        $this->assertArrayHasKey( 'variations', $gram );
    }

    /**
     * Test nutrition_facts returns expected structure.
     */
    public function test_nutrition_facts_structure() {
        $facts = Cooked_Measurements::nutrition_facts();

        $this->assertIsArray( $facts );
        $this->assertArrayHasKey( 'top', $facts );
        $this->assertArrayHasKey( 'mid', $facts );
        $this->assertArrayHasKey( 'main', $facts );
        $this->assertArrayHasKey( 'bottom', $facts );
    }

    /**
     * Test nutrition_facts top section has servings and serving_size.
     */
    public function test_nutrition_facts_top_section() {
        $facts = Cooked_Measurements::nutrition_facts();

        $this->assertArrayHasKey( 'servings', $facts['top'] );
        $this->assertArrayHasKey( 'serving_size', $facts['top'] );
    }

    /**
     * Test nutrition_facts main section has key nutrients.
     */
    public function test_nutrition_facts_main_section() {
        $facts = Cooked_Measurements::nutrition_facts();

        $this->assertArrayHasKey( 'fat', $facts['main'] );
        $this->assertArrayHasKey( 'cholesterol', $facts['main'] );
        $this->assertArrayHasKey( 'sodium', $facts['main'] );
        $this->assertArrayHasKey( 'carbs', $facts['main'] );
        $this->assertArrayHasKey( 'protein', $facts['main'] );
    }
}
