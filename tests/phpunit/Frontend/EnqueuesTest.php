<?php

use PHPUnit\Framework\TestCase;

class EnqueuesTest extends TestCase {

    private $enqueues;

    protected function setUp(): void {
        $this->enqueues = new Cooked_Enqueues();
    }

    protected function tearDown(): void {
        update_option( 'cooked_settings', [] );
    }

    public function test_colors_css_uses_the_accessible_main_colors() {
        $css = Cooked_Enqueues::get_colors_css(
            [
                'main_color'       => '#16a780',
                'main_color_hover' => '#1b9371',
            ]
        );

        $this->assertStringContainsString( '--cooked-main-color:#128666', $css );
        $this->assertStringContainsString( '--cooked-main-color-hover:#16765a', $css );
        $this->assertStringContainsString( '--cooked-main-color-ring:#16a780', $css );
    }

    public function test_colors_css_rejects_malformed_values() {
        $css = Cooked_Enqueues::get_colors_css(
            [
                'main_color'       => 'red;}body{display:none',
                'main_color_hover' => '',
            ]
        );

        $this->assertStringNotContainsString( 'display:none', $css );
        $this->assertStringContainsString( '--cooked-main-color:#128666', $css );
        $this->assertStringContainsString( '--cooked-main-color-hover:#16765a', $css );
    }

    public function test_language_attributes_mark_forced_and_auto_dark_modes() {
        update_option( 'cooked_settings', [ 'dark_mode' => 'enabled' ] );
        $this->assertStringContainsString(
            'data-cooked-dark-mode="enabled"',
            $this->enqueues->dark_mode_language_attributes( 'lang="en-US"' )
        );

        update_option( 'cooked_settings', [ 'dark_mode' => 'auto' ] );
        $this->assertStringContainsString(
            'data-cooked-dark-mode="auto"',
            $this->enqueues->dark_mode_language_attributes( 'lang="en-US"' )
        );

        update_option( 'cooked_settings', [ 'dark_mode' => 'off' ] );
        $this->assertSame( 'lang="en-US"', $this->enqueues->dark_mode_language_attributes( 'lang="en-US"' ) );
    }
}
