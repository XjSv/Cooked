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

    public function test_dark_mode_options_include_auto() {
        $tabs = Cooked_Settings::tabs_fields();
        $field = $tabs['design']['fields']['dark_mode'];
        $options = $field['options'];

        $this->assertSame('select', $field['type']);
        $this->assertSame('off', $field['default']);
        $this->assertArrayHasKey('off', $options);
        $this->assertArrayHasKey('enabled', $options);
        $this->assertArrayHasKey('auto', $options);
    }

    public function test_normalize_dark_mode_accepts_legacy_arrays() {
        $this->assertSame('off', Cooked_Settings::normalize_dark_mode([]));
        $this->assertSame('enabled', Cooked_Settings::normalize_dark_mode(['enabled']));
        $this->assertSame('auto', Cooked_Settings::normalize_dark_mode(['auto']));
        $this->assertSame('auto', Cooked_Settings::normalize_dark_mode(['enabled', 'auto']));
    }

    public function test_get_dark_mode_scope_returns_false_when_off() {
        update_option('cooked_settings', ['dark_mode' => 'off']);

        $this->assertFalse(Cooked_Settings::get_dark_mode_scope());
    }

    public function test_get_dark_mode_scope_returns_empty_string_when_forced_on() {
        update_option('cooked_settings', ['dark_mode' => 'enabled']);

        $this->assertSame('', Cooked_Settings::get_dark_mode_scope());
    }

    public function test_get_dark_mode_scope_returns_default_selector_when_auto() {
        update_option('cooked_settings', ['dark_mode' => 'auto']);

        $this->assertSame('[data-theme="dark"]', Cooked_Settings::get_dark_mode_scope());
    }

    public function test_prefix_dark_mode_css_returns_false_when_off() {
        update_option('cooked_settings', ['dark_mode' => 'off']);

        $this->assertFalse(Cooked_Settings::prefix_dark_mode_css('.cooked-fsm'));
    }

    public function test_prefix_dark_mode_css_returns_selector_when_forced_on() {
        update_option('cooked_settings', ['dark_mode' => 'enabled']);

        $this->assertSame('.cooked-fsm', Cooked_Settings::prefix_dark_mode_css('.cooked-fsm'));
    }

    public function test_prefix_dark_mode_css_prefixes_with_default_auto_scope() {
        update_option('cooked_settings', ['dark_mode' => 'auto']);

        $prefixed = Cooked_Settings::prefix_dark_mode_css('.cooked-fsm, .cooked-recipe-card');

        $this->assertSame(
            '[data-theme="dark"] .cooked-fsm, [data-theme="dark"] .cooked-recipe-card',
            $prefixed
        );
    }

    public function test_prefix_dark_mode_css_respects_auto_scope_filter() {
        update_option('cooked_settings', ['dark_mode' => 'auto']);

        $callback = function () {
            return 'body.dark-mode';
        };

        add_filter('cooked_dark_mode_auto_scope', $callback);

        $prefixed = Cooked_Settings::prefix_dark_mode_css('.cooked-fsm, .cooked-recipe-card');

        remove_filter('cooked_dark_mode_auto_scope', $callback);

        $this->assertSame(
            'body.dark-mode .cooked-fsm, body.dark-mode .cooked-recipe-card',
            $prefixed
        );
    }
}
