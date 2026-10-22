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

    public function test_parse_readme_changelog_titles_the_newest_release() {
        $file = sys_get_temp_dir() . '/cooked-changelog-' . uniqid() . '.txt';
        file_put_contents( $file, "== Changelog ==\n\n= 1.2.3 =\n* **FIX:** Newest.\n\n= 1.2.2 =\n* **FIX:** Older.\n" );
        $result = Cooked_Functions::parse_readme_changelog( $file );
        unlink( $file );
        $this->assertStringContainsString( 'Cooked 1.2.3', $result );
        $this->assertStringContainsString( 'Newest.', $result );
        $this->assertStringNotContainsString( 'Older.', $result );
    }

    public function test_parse_readme_full_changelog_converts_markdown_links() {
        $file = sys_get_temp_dir() . '/cooked-changelog-' . uniqid() . '.txt';
        file_put_contents( $file, "== Changelog ==\n\n= 1.0.0 =\n* **NEW:** CSV import (thanks to [@someone](https://github.com/someone)).\n" );
        $result = Cooked_Functions::parse_readme_full_changelog( $file );
        unlink( $file );
        $this->assertStringContainsString( '<a href="https://github.com/someone" target="_blank" rel="noopener noreferrer">@someone</a>', $result );
        $this->assertStringNotContainsString( '](', $result );
    }

    public function test_safe_unserialize_refuses_objects() {
        $result = Cooked_Functions::safe_unserialize( serialize( [ 'a' => new ArrayObject( [ 1 ] ) ] ) );
        $this->assertIsArray( $result );
        $this->assertInstanceOf( '__PHP_Incomplete_Class', $result['a'] );
    }

    public function test_safe_unserialize_passes_through_plain_values() {
        $this->assertSame( 'plain text', Cooked_Functions::safe_unserialize( 'plain text' ) );
        $this->assertSame( [ 'x' => 1 ], Cooked_Functions::safe_unserialize( serialize( [ 'x' => 1 ] ) ) );
    }

    public function test_parse_readme_changelog_reads_only_txt_files() {
        $file = sys_get_temp_dir() . '/cooked-changelog-' . uniqid() . '.php';
        file_put_contents( $file, "== Changelog ==\n\n= 1.0.0 =\n* <?php echo 'executed'; ?>\n" );
        $result = Cooked_Functions::parse_readme_changelog( $file );
        unlink( $file );
        $this->assertSame( '', $result );
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
