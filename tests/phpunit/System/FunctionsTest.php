<?php
/**
 * Tests for Cooked_Functions class.
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase {

    /**
     * Test hex2rgb converts pure red.
     */
    public function test_hex2rgb_red() {
        $this->assertSame( '255,0,0', Cooked_Functions::hex2rgb( '#ff0000' ) );
    }

    /**
     * Test hex2rgb converts pure green.
     */
    public function test_hex2rgb_green() {
        $this->assertSame( '0,255,0', Cooked_Functions::hex2rgb( '#00ff00' ) );
    }

    /**
     * Test hex2rgb converts pure blue.
     */
    public function test_hex2rgb_blue() {
        $this->assertSame( '0,0,255', Cooked_Functions::hex2rgb( '#0000ff' ) );
    }

    /**
     * Test hex2rgb converts black.
     */
    public function test_hex2rgb_black() {
        $this->assertSame( '0,0,0', Cooked_Functions::hex2rgb( '#000000' ) );
    }

    /**
     * Test hex2rgb converts white.
     */
    public function test_hex2rgb_white() {
        $this->assertSame( '255,255,255', Cooked_Functions::hex2rgb( '#ffffff' ) );
    }

    /**
     * Test hex2rgb converts mixed color.
     */
    public function test_hex2rgb_mixed() {
        $this->assertSame( '22,167,128', Cooked_Functions::hex2rgb( '#16a780' ) );
    }

    /**
     * Test array_splice_assoc splices by numeric offset.
     */
    public function test_array_splice_assoc_numeric_offset() {
        $input = [ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4 ];
        Cooked_Functions::array_splice_assoc( $input, 1, 1 );

        $this->assertSame( [ 'a' => 1, 'c' => 3, 'd' => 4 ], $input );
    }

    /**
     * Test array_splice_assoc splices by string key (offset is position after the key).
     */
    public function test_array_splice_assoc_string_key() {
        $input = [ 'first' => 1, 'second' => 2, 'third' => 3 ];
        Cooked_Functions::array_splice_assoc( $input, 'first', 1 );

        $this->assertSame( [ 'first' => 1, 'third' => 3 ], $input );
    }

    /**
     * Test array_splice_assoc replaces with new elements.
     */
    public function test_array_splice_assoc_with_replacement() {
        $input = [ 'a' => 1, 'b' => 2, 'c' => 3 ];
        Cooked_Functions::array_splice_assoc( $input, 1, 1, [ 'x' => 99 ] );

        $this->assertSame( [ 'a' => 1, 'x' => 99, 'c' => 3 ], $input );
    }

    /**
     * Test array_splice_assoc handles empty replacement.
     */
    public function test_array_splice_assoc_empty_replacement() {
        $input = [ 'a' => 1, 'b' => 2, 'c' => 3 ];
        Cooked_Functions::array_splice_assoc( $input, 0, 1, [] );

        $this->assertSame( [ 'b' => 2, 'c' => 3 ], $input );
    }

    /**
     * Test array_splice_assoc does nothing on non-array input.
     */
    public function test_array_splice_assoc_non_array() {
        $input = 'not an array';
        Cooked_Functions::array_splice_assoc( $input, 0, 1 );

        $this->assertSame( 'not an array', $input );
    }

    /**
     * Test sanitize_text_field strips tags and slashes.
     */
    public function test_sanitize_text_field_strips_tags() {
        $result = Cooked_Functions::sanitize_text_field( '<script>alert(1)</script>' );

        $this->assertStringNotContainsString( '<script>', $result );
    }
}
