<?php

use PHPUnit\Framework\TestCase;

class RecipesTest extends TestCase {

    public function test_difficulty_levels_returns_array() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertIsArray($levels);
    }

    public function test_difficulty_levels_has_three_levels() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertCount(3, $levels);
    }

    public function test_difficulty_levels_keys() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertArrayHasKey(1, $levels);
        $this->assertArrayHasKey(2, $levels);
        $this->assertArrayHasKey(3, $levels);
    }

    public function test_difficulty_levels_values() {
        $levels = Cooked_Recipes::difficulty_levels();
        $this->assertSame('Beginner', $levels[1]);
        $this->assertSame('Intermediate', $levels[2]);
        $this->assertSame('Advanced', $levels[3]);
    }

    public function test_default_content_returns_string() {
        $content = Cooked_Recipes::default_content();
        $this->assertIsString($content);
    }

    public function test_default_content_contains_shortcodes() {
        $content = Cooked_Recipes::default_content();
        $this->assertStringContainsString('[cooked-info', $content);
        $this->assertStringContainsString('[cooked-ingredients]', $content);
        $this->assertStringContainsString('[cooked-directions]', $content);
    }

    public function test_vendor_checks_returns_content_when_no_vendor() {
        $result = Cooked_Recipes::vendor_checks('test content');
        $this->assertSame('test content', $result);
    }

    public function test_recipe_has_fullscreen_returns_false_for_empty_content() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(1, ''));
    }

    public function test_recipe_has_fullscreen_returns_false_for_no_cooked_info() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(1, '<p>[cooked-ingredients]</p>'));
    }

    public function test_recipe_has_fullscreen_returns_false_for_invalid_id() {
        $this->assertFalse(Cooked_Recipes::recipe_has_fullscreen(0, '[cooked-info left="fullscreen"]'));
    }

    public function test_get_c2_recipe_meta_returns_empty_array_when_no_c2_meta() {
        $result = Cooked_Recipes::get_c2_recipe_meta(1);
        $this->assertSame([], $result);
    }

    public function test_filter_default_recipe_thumbnail_returns_existing_thumbnail() {
        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 42, 1 );
        $this->assertSame( 42, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_default_for_recipe_without_thumbnail() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 99,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'cp_recipe',
        ] );

        $this->assertSame( 99, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_original_for_non_recipe_post() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 99,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'post',
        ] );

        $this->assertSame( 0, $result );
    }

    public function test_filter_default_recipe_thumbnail_returns_original_when_no_default_set() {
        unset( $GLOBALS['_cooked_settings'] );
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'default_recipe_image' => 0,
        ];

        $result = Cooked_Recipes::filter_default_recipe_thumbnail( 0, (object) [
            'ID' => 1,
            'post_type' => 'cp_recipe',
        ] );

        $this->assertSame( 0, $result );
    }
}
