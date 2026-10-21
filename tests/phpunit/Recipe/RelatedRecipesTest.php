<?php

use PHPUnit\Framework\TestCase;

class RelatedRecipesTest extends TestCase {

    public function test_get_default_atts_returns_array() {
        $atts = Cooked_Related_Recipes::get_default_atts();
        $this->assertIsArray($atts);
    }

    public function test_get_default_atts_has_required_keys() {
        $atts = Cooked_Related_Recipes::get_default_atts();
        $this->assertArrayHasKey('id', $atts);
        $this->assertArrayHasKey('include_ids', $atts);
        $this->assertArrayHasKey('limit', $atts);
        $this->assertArrayHasKey('columns', $atts);
        $this->assertArrayHasKey('hide_image', $atts);
        $this->assertArrayHasKey('match_categories', $atts);
    }

    public function test_get_default_atts_expected_values() {
        $atts = Cooked_Related_Recipes::get_default_atts();
        $this->assertFalse($atts['id']);
        $this->assertFalse($atts['include_ids']);
        $this->assertSame(4, $atts['limit']);
        $this->assertSame(2, $atts['columns']);
        $this->assertFalse($atts['hide_image']);
        $this->assertTrue($atts['match_categories']);
    }

    public function test_get_default_atts_title_is_string() {
        $atts = Cooked_Related_Recipes::get_default_atts();
        $this->assertIsString($atts['title']);
        $this->assertNotEmpty($atts['title']);
    }

    public function test_match_cuisines_lowercase_false_omits_cuisine_clause() {
        $this->seed_source_terms();

        Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'match_cuisines' => 'false' ] )
        );

        $this->assertSame(
            [ 'cp_recipe_category', 'cp_recipe_cooking_method', 'cp_recipe_tags', 'cp_recipe_diet' ],
            $this->tax_query_taxonomies()
        );
    }

    public function test_match_cuisines_capitalized_false_omits_cuisine_clause() {
        $this->seed_source_terms();

        Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'match_cuisines' => 'False' ] )
        );

        $this->assertSame(
            [ 'cp_recipe_category', 'cp_recipe_cooking_method', 'cp_recipe_tags', 'cp_recipe_diet' ],
            $this->tax_query_taxonomies()
        );
    }

    public function test_match_cuisines_uppercase_false_omits_cuisine_clause() {
        $this->seed_source_terms();

        Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'match_cuisines' => 'FALSE' ] )
        );

        $this->assertSame(
            [ 'cp_recipe_category', 'cp_recipe_cooking_method', 'cp_recipe_tags', 'cp_recipe_diet' ],
            $this->tax_query_taxonomies()
        );
    }

    public function test_match_cuisines_true_keeps_cuisine_clause() {
        $this->seed_source_terms();

        Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'match_cuisines' => 'true' ] )
        );

        $this->assertContains( 'cp_recipe_cuisine', $this->tax_query_taxonomies() );
    }

    public function test_match_cuisines_omitted_keeps_cuisine_clause() {
        $this->seed_source_terms();

        Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            Cooked_Related_Recipes::get_default_atts()
        );

        $this->assertContains( 'cp_recipe_cuisine', $this->tax_query_taxonomies() );
    }

    public function test_include_ids_prepend_related_and_reduce_query() {
        $this->seed_source_terms();
        $GLOBALS['_cooked_test_query_posts'] = [ 8, 9 ];

        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'include_ids' => '101,102', 'limit' => 4 ] )
        );

        $this->assertSame(
            [ [ 'id' => 101 ], [ 'id' => 102 ], [ 'id' => 8 ], [ 'id' => 9 ] ],
            $result
        );
        $this->assertSame( 2, (int) $GLOBALS['_cooked_test_last_query']['posts_per_page'] );
        $this->assertSame( [ 1, 101, 102 ], $GLOBALS['_cooked_test_last_query']['post__not_in'] );
    }

    public function test_include_ids_strips_source_duplicates_and_invalid() {
        $this->seed_source_terms();
        $GLOBALS['_cooked_test_query_posts'] = [ 8 ];

        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'include_ids' => ' 102, abc, 1, 102, 0, 101 ', 'limit' => 4 ] )
        );

        $this->assertSame(
            [ [ 'id' => 102 ], [ 'id' => 101 ], [ 'id' => 8 ] ],
            $result
        );
        $this->assertSame( [ 1, 102, 101 ], $GLOBALS['_cooked_test_last_query']['post__not_in'] );
    }

    public function test_include_ids_array_preserves_order() {
        $this->seed_source_terms();
        $GLOBALS['_cooked_test_query_posts'] = [ 8 ];

        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'include_ids' => [ 101, 102 ], 'limit' => 3 ] )
        );

        $this->assertSame(
            [ [ 'id' => 101 ], [ 'id' => 102 ], [ 'id' => 8 ] ],
            $result
        );
    }

    public function test_include_ids_filling_limit_skips_related_query() {
        $this->seed_source_terms();
        $GLOBALS['_cooked_test_query_posts'] = [ 8, 9 ];

        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'include_ids' => '101,102,103', 'limit' => 2 ] )
        );

        $this->assertSame(
            [ [ 'id' => 101 ], [ 'id' => 102 ] ],
            $result
        );
        $this->assertSame( [], $GLOBALS['_cooked_test_last_query'] );
    }

    public function test_include_ids_returned_when_source_has_no_terms() {
        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            $this->atts_with( [ 'include_ids' => '101,102' ] )
        );

        $this->assertSame(
            [ [ 'id' => 101 ], [ 'id' => 102 ] ],
            $result
        );
        $this->assertSame( [], $GLOBALS['_cooked_test_last_query'] );
    }

    public function test_empty_terms_without_include_ids_returns_empty() {
        $result = Cooked_Related_Recipes::find_related_recipes(
            $this->source_recipe(),
            Cooked_Related_Recipes::get_default_atts()
        );

        $this->assertSame( [], $result );
        $this->assertSame( [], $GLOBALS['_cooked_test_last_query'] );
    }

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['_cooked_test_object_terms'] = [];
        $GLOBALS['_cooked_test_last_query']   = [];
        $GLOBALS['_cooked_test_query_posts']  = [];
    }

    protected function source_recipe() {
        return [
            'id'        => 1,
            'title'     => 'Source Recipe',
            'nutrition' => [ 'servings' => 1 ],
        ];
    }

    protected function atts_with( $overrides ) {
        return array_merge( Cooked_Related_Recipes::get_default_atts(), $overrides );
    }

    protected function seed_source_terms() {
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_category']       = [ 10 ];
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_cuisine']        = [ 20 ];
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_cooking_method'] = [ 30 ];
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_tags']           = [ 40 ];
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_diet']           = [ 50 ];
    }

    protected function tax_query_taxonomies() {
        $taxonomies = [];
        $tax_query  = isset( $GLOBALS['_cooked_test_last_query']['tax_query'] )
            ? $GLOBALS['_cooked_test_last_query']['tax_query']
            : [];

        foreach ( $tax_query as $clause ) {
            if ( is_array( $clause ) && isset( $clause['taxonomy'] ) ) {
                $taxonomies[] = $clause['taxonomy'];
            }
        }

        return $taxonomies;
    }
}
