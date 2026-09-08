<?php

use PHPUnit\Framework\TestCase;

class FotoramaAssetsTest extends TestCase {

    public function test_retina_sprite_uses_hyphenated_filename() {
        $dir = COOKED_DIR . 'assets/css/fotorama/';

        $this->assertFileExists( $dir . 'fotorama-2x.png' );
        $this->assertFileDoesNotExist( $dir . 'fotorama@2x.png' );

        $css = file_get_contents( $dir . 'fotorama.min.css' );
        $this->assertStringContainsString( 'fotorama-2x.png', $css );
        $this->assertStringNotContainsString( 'fotorama@2x', $css );
    }
}
