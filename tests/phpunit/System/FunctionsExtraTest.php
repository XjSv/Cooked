<?php

use PHPUnit\Framework\TestCase;

class FunctionsExtraTest extends TestCase {

    public function test_set_transient_message_for_guest() {
        $result = Cooked_Functions::set_transient_message('Hello');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_set_transient_message_empty_returns_null() {
        $result = Cooked_Functions::set_transient_message('');
        $this->assertNull($result);
    }

    public function test_get_and_delete_transient_message_returns_false_when_none() {
        $result = Cooked_Functions::get_and_delete_transient_message();
        $this->assertFalse($result);
    }

    public function test_parse_readme_changelog_returns_string() {
        $result = Cooked_Functions::parse_readme_changelog(COOKED_DIR . 'readme.txt', 'What\'s New');
        $this->assertIsString($result);
    }

    public function test_parse_readme_changelog_contains_title() {
        $result = Cooked_Functions::parse_readme_changelog(COOKED_DIR . 'readme.txt', 'Custom Title');
        $this->assertStringContainsString('Custom Title', $result);
    }

    public function test_sanitize_text_field_encodes_html() {
        $result = Cooked_Functions::sanitize_text_field('<b>test</b>');
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    public function test_sanitize_text_field_strips_slashes() {
        $result = Cooked_Functions::sanitize_text_field("O\'Brien");
        $this->assertStringNotContainsString('\\', $result);
    }
}
