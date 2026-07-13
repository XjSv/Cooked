<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

    public function test_tabs_fields_returns_array() {
        $tabs = Cooked_Settings::tabs_fields();
        $this->assertIsArray($tabs);
    }

    public function test_tabs_fields_has_recipe_settings_tab() {
        $tabs = Cooked_Settings::tabs_fields();
        $this->assertArrayHasKey('recipe_settings', $tabs);
    }

    public function test_tabs_fields_recipe_settings_has_fields() {
        $tabs = Cooked_Settings::tabs_fields();
        $this->assertArrayHasKey('fields', $tabs['recipe_settings']);
        $this->assertIsArray($tabs['recipe_settings']['fields']);
    }

    public function test_tabs_fields_recipe_settings_has_key_fields() {
        $tabs = Cooked_Settings::tabs_fields();
        $fields = $tabs['recipe_settings']['fields'];
        $this->assertArrayHasKey('browse_page', $fields);
        $this->assertArrayHasKey('recipes_per_page', $fields);
        $this->assertArrayHasKey('carb_format', $fields);
        $this->assertArrayHasKey('author_name_format', $fields);
    }

    public function test_tabs_fields_has_design_tab() {
        $tabs = Cooked_Settings::tabs_fields();
        $this->assertArrayHasKey('design', $tabs);
        $this->assertArrayHasKey('main_color', $tabs['design']['fields']);
    }

    public function test_tabs_fields_design_has_default_recipe_image() {
        $tabs = Cooked_Settings::tabs_fields();
        $field = $tabs['design']['fields']['default_recipe_image'];

        $this->assertSame('image_field', $field['type']);
        $this->assertSame(0, $field['default']);
    }

    public function test_tabs_fields_has_permalinks_tab() {
        $tabs = Cooked_Settings::tabs_fields();
        $this->assertArrayHasKey('permalinks', $tabs);
        $this->assertArrayHasKey('recipe_permalink', $tabs['permalinks']['fields']);
    }

    public function test_tabs_fields_default_values() {
        $tabs = Cooked_Settings::tabs_fields();
        $fields = $tabs['recipe_settings']['fields'];

        $this->assertSame(0, $fields['browse_page']['default']);
        $this->assertSame(9, $fields['recipes_per_page']['default']);
        $this->assertSame('total', $fields['carb_format']['default']);
        $this->assertSame('full', $fields['author_name_format']['default']);
    }

    public function test_get_returns_array() {
        $settings = Cooked_Settings::get();
        $this->assertIsArray($settings);
    }
}
