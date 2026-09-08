<?php

use PHPUnit\Framework\TestCase;

class SEOTest extends TestCase {

    private $ob_level;

    protected function setUp(): void {
        $this->ob_level = ob_get_level();
        $GLOBALS['post'] = (object) [
            'ID' => 1,
            'post_title' => 'Test Recipe',
            'post_excerpt' => '',
            'post_author' => 1,
            'post_status' => 'publish',
            'post_type' => 'cp_recipe',
        ];
        $GLOBALS['recipe_settings'] = [
            'nutrition' => ['servings' => 4],
        ];
    }

    protected function tearDown(): void {
        while ( ob_get_level() > $this->ob_level ) {
            ob_end_clean();
        }
        unset( $GLOBALS['post'] );
        unset( $GLOBALS['recipe_settings'] );
    }

    public function test_json_ld_returns_empty_when_disabled() {
        $GLOBALS['_cooked_settings']['advanced'] = [ 'disable_schema_output' ];
        $result = Cooked_SEO::json_ld( false );
        $this->assertSame( '', $result );
        $GLOBALS['_cooked_settings']['advanced'] = [];
    }

    public function test_json_ld_returns_string_when_enabled() {
        $GLOBALS['_cooked_settings']['advanced'] = [];
        $GLOBALS['_cooked_settings']['recipe_taxonomies'] = [ 'cp_recipe_category' ];
        $result = Cooked_SEO::json_ld( false );
        $this->assertIsString( $result );
        $this->assertStringContainsString( 'application/ld+json', $result );
    }

    public function test_schema_values_returns_array() {
        $GLOBALS['_cooked_settings']['advanced'] = [];
        $GLOBALS['_cooked_settings']['recipe_taxonomies'] = [ 'cp_recipe_category' ];
        $recipe = [
            'id' => 1,
            'title' => 'Test Recipe',
            'ingredients' => [
                [ 'amount' => '2', 'measurement' => 'cup', 'name' => 'Flour' ],
            ],
            'directions' => [
                [ 'content' => 'Mix well' ],
            ],
            'nutrition' => [
                'servings' => 4,
                'calories' => 200,
            ],
        ];
        $result = Cooked_SEO::schema_values( $recipe );
        $this->assertIsArray( $result );
        $this->assertSame( 'Recipe', $result['@type'] );
        $this->assertSame( 'Test Recipe', $result['name'] );
    }

    public function test_schema_values_has_required_schema_keys() {
        $GLOBALS['_cooked_settings']['advanced'] = [];
        $GLOBALS['_cooked_settings']['recipe_taxonomies'] = [ 'cp_recipe_category' ];
        $recipe = [
            'id' => 1,
            'title' => 'Test Recipe',
            'ingredients' => [],
            'directions' => [],
            'nutrition' => [],
        ];
        $result = Cooked_SEO::schema_values( $recipe );
        $this->assertArrayHasKey( '@context', $result );
        $this->assertArrayHasKey( '@type', $result );
        $this->assertArrayHasKey( 'name', $result );
        $this->assertArrayHasKey( 'recipeIngredient', $result );
        $this->assertArrayHasKey( 'recipeInstructions', $result );
        $this->assertArrayHasKey( 'nutrition', $result );
    }

    public function test_schema_values_uses_unencoded_seo_description() {
        $GLOBALS['_cooked_settings']['advanced'] = [];
        $GLOBALS['_cooked_settings']['recipe_taxonomies'] = [ 'cp_recipe_category' ];
        $recipe = [
            'id' => 1,
            'title' => 'Test Recipe',
            'seo_description' => 'you\'ll love this "recipe"',
            'ingredients' => [],
            'directions' => [],
            'nutrition' => [],
        ];
        $result = Cooked_SEO::schema_values( $recipe );
        $this->assertSame( 'you\'ll love this "recipe"', $result['description'] );
        $this->assertStringNotContainsString( '&quot;', $result['description'] );
        $this->assertStringNotContainsString( '&#039;', $result['description'] );
    }

    public function test_schema_values_decodes_encoded_seo_description() {
        $GLOBALS['_cooked_settings']['advanced'] = [];
        $GLOBALS['_cooked_settings']['recipe_taxonomies'] = [ 'cp_recipe_category' ];
        $recipe = [
            'id' => 1,
            'title' => 'Test Recipe',
            'seo_description' => 'you&#039;ll love this &quot;recipe&quot;',
            'ingredients' => [],
            'directions' => [],
            'nutrition' => [],
        ];
        $result = Cooked_SEO::schema_values( $recipe );
        $this->assertSame( 'you\'ll love this "recipe"', $result['description'] );
    }
}
