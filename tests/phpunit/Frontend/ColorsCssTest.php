<?php

use PHPUnit\Framework\TestCase;

class ColorsCssTest extends TestCase {

    public function test_dark_mode_dm_arguments_do_not_contain_child_combinators() {
        $src = file_get_contents( COOKED_DIR . 'assets/css/colors.php' );
        $this->assertNotFalse( $src );

        preg_match_all( "/\\\$dm\\(\\s*'([^']*)'\\s*\\)/", $src, $matches );
        $this->assertNotEmpty( $matches[1] );

        foreach ( $matches[1] as $selectors ) {
            $this->assertStringNotContainsString(
                '>',
                $selectors,
                'Child combinators must stay outside $dm() so wp_kses does not encode them.'
            );
        }
    }
}
