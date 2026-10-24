<?php

use PHPUnit\Framework\TestCase;

class MeasurementsRemainingTest extends TestCase {

    private $measurements;
    private $ob_level;

    protected function setUp(): void {
        $this->measurements = new Cooked_Measurements();
        $this->ob_level = ob_get_level();
    }

    protected function tearDown(): void {
        while ( ob_get_level() > $this->ob_level ) {
            ob_end_clean();
        }
    }

    public function test_time_format_iso_under_one_hour() {
        $result = Cooked_Measurements::time_format( 45, 'iso' );
        $this->assertSame( 'PT0H45M', $result );
    }

    public function test_time_format_iso_exact_one_hour() {
        $result = Cooked_Measurements::time_format( 60, 'iso' );
        $this->assertSame( 'PT1H0M', $result );
    }

    public function test_time_format_iso_hours_and_minutes() {
        $result = Cooked_Measurements::time_format( 90, 'iso' );
        $this->assertSame( 'PT1H30M', $result );
    }

    public function test_time_format_iso_multiple_hours() {
        $result = Cooked_Measurements::time_format( 150, 'iso' );
        $this->assertSame( 'PT2H30M', $result );
    }

    public function test_time_format_iso_over_one_day() {
        $result = Cooked_Measurements::time_format( 1500, 'iso' );
        $this->assertSame( 'P1DT0H60M', $result );
    }

    public function test_time_format_iso_zero_minutes() {
        $result = Cooked_Measurements::time_format( 0, 'iso' );
        $this->assertSame( 'PT0H0M', $result );
    }

    public function test_normalize_minutes_reads_leading_number_from_string() {
        $this->assertSame( 5, Cooked_Measurements::normalize_minutes( '5 minutes' ) );
        $this->assertSame( 15, Cooked_Measurements::normalize_minutes( '15' ) );
        $this->assertSame( 1.5, Cooked_Measurements::normalize_minutes( '1.5' ) );
        $this->assertSame( 0, Cooked_Measurements::normalize_minutes( '' ) );
        $this->assertSame( 0, Cooked_Measurements::normalize_minutes( 'minutes' ) );
        $this->assertSame( 0, Cooked_Measurements::normalize_minutes( null ) );
    }

    public function test_time_format_accepts_string_minutes() {
        $this->assertSame( 'PT0H5M', Cooked_Measurements::time_format( '5 minutes', 'iso' ) );
        $this->assertSame( '5 mins', Cooked_Measurements::time_format( '5 minutes' ) );
    }

    public function test_format_amount_decimal_format() {
        $result = $this->measurements->format_amount( 1.5, 'decimal' );
        $this->assertSame( '1.50', $result );
    }

    public function test_format_amount_decimal_whole_number() {
        $result = $this->measurements->format_amount( 3, 'decimal' );
        $this->assertSame( '3.00', $result );
    }

    public function test_format_amount_decimal_zero() {
        $result = $this->measurements->format_amount( 0, 'decimal' );
        $this->assertSame( 0, $result );
    }

    public function test_format_amount_fraction_whole_number() {
        $result = $this->measurements->format_amount( 2, 'fraction' );
        $this->assertSame( '2', $result );
    }
}
