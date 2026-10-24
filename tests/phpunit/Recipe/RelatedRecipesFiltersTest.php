<?php

class RelatedRecipesFiltersTest extends FilterTestCase {

    protected function source_recipe() {
        return [
            'id'        => 1,
            'title'     => 'Source Recipe',
            'nutrition' => [ 'servings' => 1 ],
        ];
    }

    protected function related_atts() {
        return Cooked_Related_Recipes::get_default_atts();
    }

    public function test_cooked_related_recipes_default_atts_can_add_a_key() {
        $atts = $this->with_filter(
            'cooked_related_recipes_default_atts',
            function ( $value ) {
                $value['sentinel'] = 'yes';
                return $value;
            },
            function () {
                return Cooked_Related_Recipes::get_default_atts();
            }
        );

        $this->assertSame( 'yes', $atts['sentinel'] );
    }

    public function test_cooked_related_recipes_query_args_changes_wp_query() {
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_category'] = [ 3 ];

        $this->with_filter(
            'cooked_related_recipes_query_args',
            function ( $args ) {
                $args['posts_per_page'] = 11;
                $args['orderby']        = 'date';
                return $args;
            },
            function () {
                Cooked_Related_Recipes::find_related_recipes(
                    $this->source_recipe(),
                    $this->related_atts()
                );
            }
        );

        $this->assertSame( 11, (int) $GLOBALS['_cooked_test_last_query']['posts_per_page'] );
        $this->assertSame( 'date', $GLOBALS['_cooked_test_last_query']['orderby'] );
    }

    public function test_cooked_related_recipes_result_can_replace_ids() {
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_category'] = [ 3 ];
        $GLOBALS['_cooked_test_query_posts'] = [ 8, 9 ];

        $result = $this->with_filter(
            'cooked_related_recipes_result',
            function () {
                return [ [ 'id' => 42 ] ];
            },
            function () {
                return Cooked_Related_Recipes::find_related_recipes(
                    $this->source_recipe(),
                    $this->related_atts()
                );
            }
        );

        $this->assertSame( [ [ 'id' => 42 ] ], $result );
    }
}
