<?php

use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase {

    public function test_format_author_name_returns_false_for_empty() {
        $this->assertFalse(Cooked_Users::format_author_name(''));
    }

    public function test_format_author_name_full() {
        $result = Cooked_Users::format_author_name('John Doe');
        $this->assertSame('John Doe', $result);
    }

    public function test_format_author_name_first_only() {
        $result = Cooked_Users::format_author_name('John Doe', 'first_only');
        $this->assertSame('John', $result);
    }

    public function test_format_author_name_first_last_initial() {
        $result = Cooked_Users::format_author_name('John Doe', 'first_last_initial');
        $this->assertSame('John D.', $result);
    }

    public function test_format_author_name_first_initial_last() {
        $result = Cooked_Users::format_author_name('John Doe', 'first_initial_last');
        $this->assertSame('J. Doe', $result);
    }

    public function test_format_author_name_single_name_first_only() {
        $result = Cooked_Users::format_author_name('Madonna', 'first_only');
        $this->assertSame('Madonna', $result);
    }

    public function test_format_author_name_single_name_first_last_initial() {
        $result = Cooked_Users::format_author_name('Madonna', 'first_last_initial');
        $this->assertSame('Madonna', $result);
    }

    public function test_format_author_name_escapes_every_format() {
        foreach ( [ 'full', 'first_only', 'first_last_initial', 'first_initial_last' ] as $format ) {
            $result = Cooked_Users::format_author_name( '<b>Jo</b> <i>Doe</i>', $format );
            $this->assertStringNotContainsString( '<', $result, $format );
        }
    }

    public function test_format_author_name_initial_is_multibyte_safe() {
        $this->assertSame( 'Élodie D.', Cooked_Users::format_author_name( 'Élodie Doe', 'first_last_initial' ) );
        $this->assertSame( 'É. Doe', Cooked_Users::format_author_name( 'Élodie Doe', 'first_initial_last' ) );
    }
}
