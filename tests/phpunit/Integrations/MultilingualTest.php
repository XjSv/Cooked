<?php

use PHPUnit\Framework\TestCase;

class MultilingualTest extends TestCase {

    public function test_is_polylang_active_returns_false_by_default() {
        $this->assertFalse(Cooked_Multilingual::is_polylang_active());
    }

    public function test_is_wpml_active_returns_false_by_default() {
        $this->assertFalse(Cooked_Multilingual::is_wpml_active());
    }

    public function test_is_multilingual_active_returns_false_when_none_active() {
        $this->assertFalse(Cooked_Multilingual::is_multilingual_active());
    }

    public function test_get_browse_page_id_returns_false_when_no_settings() {
        $this->assertFalse(Cooked_Multilingual::get_browse_page_id());
    }

    public function test_get_browse_page_id_uses_provided_id() {
        $result = Cooked_Multilingual::get_browse_page_id(42);
        $this->assertSame(42, $result);
    }

    public function test_get_browse_page_id_returns_false_for_falsy_id() {
        $this->assertFalse(Cooked_Multilingual::get_browse_page_id(0));
        $this->assertFalse(Cooked_Multilingual::get_browse_page_id(false));
    }
}
