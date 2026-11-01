<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

    public function test_sanitize_settings_cleans_each_field_type() {
        $clean = Cooked_Settings::sanitize_settings(
            [
                'main_color'              => 'red;}</style><script>',
                'recipe_permalink'        => 'My Recipes//Second Part',
                'carb_format'             => 'not-an-option',
                'author_name_format'      => 'first_only',
                'advanced'                => [ 'disable_meta_tags', 'not_an_option' ],
            ]
        );

        $this->assertSame( '#16a780', $clean['main_color'] ); // invalid colour -> default
        $this->assertSame( 'my-recipes/second-part', $clean['recipe_permalink'] ); // empty parts dropped
        $this->assertNotSame( 'not-an-option', $clean['carb_format'] );
        $this->assertSame( 'first_only', $clean['author_name_format'] );
        $this->assertSame( [ 'disable_meta_tags' ], $clean['advanced'] );
    }

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

    public function test_admin_and_editor_dark_modes_are_separate_per_user_selects() {
        $tabs = Cooked_Settings::tabs_fields();

        foreach (['admin_dark_mode' => 'cooked_admin_dark_mode', 'editor_dark_mode' => 'cooked_editor_dark_mode'] as $name => $meta_key) {
            $field = $tabs['design']['fields'][$name];
            $this->assertSame('user_dark_mode', $field['type']);
            $this->assertSame($meta_key, $field['meta_key']);
            $this->assertSame(['off', 'enabled', 'auto'], array_keys($field['options']));
        }
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

    public function test_is_rejected_value_by_field_type() {
        $number = [ 'type' => 'number_field' ];
        $color  = [ 'type' => 'color_field' ];
        $select = [ 'type' => 'select', 'options' => [ 'a' => 'A', 'b' => 'B' ] ];

        $this->assertFalse( Cooked_Settings::is_rejected_value( '', $number ), 'empty means default' );
        $this->assertFalse( Cooked_Settings::is_rejected_value( '750', $number ) );
        $this->assertTrue( Cooked_Settings::is_rejected_value( 'wide', $number ) );
        $this->assertTrue( Cooked_Settings::is_rejected_value( '-5', $number ) );
        $this->assertFalse( Cooked_Settings::is_rejected_value( '#16a780', $color ) );
        $this->assertTrue( Cooked_Settings::is_rejected_value( '#zzzzzz', $color ) );
        $this->assertFalse( Cooked_Settings::is_rejected_value( 'b', $select ) );
        $this->assertTrue( Cooked_Settings::is_rejected_value( 'c', $select ) );
        $this->assertFalse( Cooked_Settings::is_rejected_value( 'anything', [ 'type' => 'text' ] ) );
    }

    public function test_rejected_fields_reads_the_reported_settings_errors() {
        $GLOBALS['_cooked_test_settings_errors'] = [];
        add_settings_error( 'cooked_settings_group', 'cooked_settings_updated', 'Saved', 'updated' );
        add_settings_error( 'cooked_settings_group', 'cooked_invalid_main_color', 'Main Color: not valid', 'error' );
        add_settings_error( 'general', 'cooked_invalid_elsewhere', 'Another group', 'error' );

        $this->assertSame( [ 'main_color' ], Cooked_Settings::rejected_fields() );
        $GLOBALS['_cooked_test_settings_errors'] = [];
    }
}
