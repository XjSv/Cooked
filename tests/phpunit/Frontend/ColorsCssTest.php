<?php

use PHPUnit\Framework\TestCase;

class ColorsCssTest extends TestCase {

    public function test_static_stylesheet_uses_color_variables_and_dark_mode_markers() {
        $css = file_get_contents( COOKED_DIR . 'assets/css/colors.min.css' );
        $this->assertNotFalse( $css );

        $this->assertStringContainsString( 'var(--cooked-main-color', $css );
        $this->assertStringContainsString( 'var(--cooked-main-color-hover', $css );
        $this->assertStringContainsString( 'data-cooked-dark-mode', $css );
    }
}
