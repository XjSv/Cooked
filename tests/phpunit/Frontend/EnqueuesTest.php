<?php

use PHPUnit\Framework\TestCase;

class EnqueuesTest extends TestCase {

    private $enqueues;

    protected function setUp(): void {
        $this->enqueues = new Cooked_Enqueues();
    }

    public function test_compress_css_removes_newlines() {
        $css = ".foo {\n  color: red;\n}";
        $result = $this->enqueues->compress_css($css);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function test_compress_css_removes_tabs() {
        $css = ".foo {\tcolor: red;\t}";
        $result = $this->enqueues->compress_css($css);
        $this->assertStringNotContainsString("\t", $result);
    }

    public function test_compress_css_removes_carriage_returns() {
        $css = ".foo {\r\n  color: red;\r\n}";
        $result = $this->enqueues->compress_css($css);
        $this->assertStringNotContainsString("\r", $result);
    }

    public function test_compress_css_removes_double_spaces() {
        $css = ".foo  {  color:  red;  }";
        $result = $this->enqueues->compress_css($css);
        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_compress_css_preserves_content() {
        $css = ".foo { color: red; }";
        $result = $this->enqueues->compress_css($css);
        $this->assertStringContainsString('.foo', $result);
        $this->assertStringContainsString('color: red;', $result);
    }

    public function test_compress_css_empty_string() {
        $result = $this->enqueues->compress_css('');
        $this->assertSame('', $result);
    }
}
