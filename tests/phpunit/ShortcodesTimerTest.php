<?php

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'Cooked_Shortcodes' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-shortcodes.php';
}

class ShortcodesTimerTest extends TestCase {

    protected $shortcodes;

    protected function setUp(): void {
        parent::setUp();
        $this->shortcodes = new Cooked_Shortcodes();
        $GLOBALS['cooked_timer_identifier'] = 0;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['cooked_timer_identifier'] );
        parent::tearDown();
    }

    public function test_numeric_minutes_sets_seconds() {
        $output = $this->timer_without_warning( [ 'minutes' => '5' ], '5 Minutes' );
        $this->assertStringContainsString( 'data-seconds="300"', $output );
        $this->assertStringContainsString( '5 Minutes', $output );
    }

    public function test_fractional_minutes_are_preserved() {
        $output = $this->timer_without_warning( [ 'minutes' => '5.5' ], '5.5 Minutes' );
        $this->assertStringContainsString( 'data-seconds="330"', $output );
    }

    public function test_length_is_used_when_minutes_empty() {
        $output = $this->timer_without_warning( [ 'length' => '2' ], '2 Minutes' );
        $this->assertStringContainsString( 'data-seconds="120"', $output );
    }

    public function test_curly_quoted_minutes_does_not_warn() {
        $output = $this->timer_without_warning( [ 'minutes' => '”5″' ], '5 Minutes' );
        $this->assertStringContainsString( 'data-seconds="0"', $output );
        $this->assertStringContainsString( '5 Minutes', $output );
    }

    public function test_non_numeric_hours_and_seconds_do_not_warn() {
        $output = $this->timer_without_warning(
            [
                'seconds' => 'ten',
                'hours'   => '“1”',
            ],
            'Wait'
        );
        $this->assertStringContainsString( 'data-seconds="0"', $output );
    }

    /**
     * @param array<string, mixed> $atts
     */
    private function timer_without_warning( $atts, $content ) {
        $warnings = [];
        set_error_handler(
            function ( $errno, $errstr ) use ( &$warnings ) {
                $warnings[] = $errstr;
                return true;
            }
        );

        try {
            $output = $this->shortcodes->cooked_timer( $atts, $content );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings, implode( '; ', $warnings ) );

        return $output;
    }
}
