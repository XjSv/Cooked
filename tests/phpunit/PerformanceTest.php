<?php

use PHPUnit\Framework\TestCase;

require_once COOKED_DIR . 'includes/class.cooked-migration.php';
require_once COOKED_DIR . 'includes/class.cooked-taxonomies.php';
require_once COOKED_DIR . 'includes/class.cooked-shortcodes.php';

class PerformanceTest extends TestCase {

    private function list_atts( $show ) {
        return [
            'layout' => '',
            'show' => $show,
            'category' => '',
            'orderby' => '',
            'order' => '',
            'author' => '',
            'search' => '',
            'pagination' => '',
        ];
    }

    public function test_complete_migration_transient_is_not_rewritten() {
        require_once COOKED_DIR . 'includes/class.cooked-migration.php';

        $GLOBALS['_cooked_test_transients'] = [
            'cooked_classic_recipes' => 'complete',
        ];
        $GLOBALS['_cooked_test_transient_sets'] = [];

        $this->assertSame( 'complete', Cooked_Migration::get_cooked_classic_recipes() );
        $this->assertSame( [], $GLOBALS['_cooked_test_transient_sets'] );

        $GLOBALS['_cooked_test_transients']['cooked_classic_recipes'] = [ 4, 9 ];
        $this->assertSame( [ 4, 9 ], Cooked_Migration::get_cooked_classic_recipes() );
        $this->assertSame( [], $GLOBALS['_cooked_test_transient_sets'] );
    }

    public function test_tabs_fields_skips_option_queries_until_the_settings_screen() {
        Cooked_Settings::flush_cache();
        $posts_before = isset( $GLOBALS['_cooked_test_get_posts_calls'] ) ? $GLOBALS['_cooked_test_get_posts_calls'] : 0;
        $terms_before = isset( $GLOBALS['_cooked_test_get_terms_calls'] ) ? $GLOBALS['_cooked_test_get_terms_calls'] : 0;

        $defaults = Cooked_Settings::tabs_fields( false );

        $this->assertArrayHasKey( 'recipe_settings', $defaults );
        $this->assertSame( $posts_before, isset( $GLOBALS['_cooked_test_get_posts_calls'] ) ? $GLOBALS['_cooked_test_get_posts_calls'] : 0 );
        $this->assertSame( $terms_before, isset( $GLOBALS['_cooked_test_get_terms_calls'] ) ? $GLOBALS['_cooked_test_get_terms_calls'] : 0 );

        Cooked_Settings::flush_cache();
        $posts_before = isset( $GLOBALS['_cooked_test_get_posts_calls'] ) ? $GLOBALS['_cooked_test_get_posts_calls'] : 0;
        $terms_before = isset( $GLOBALS['_cooked_test_get_terms_calls'] ) ? $GLOBALS['_cooked_test_get_terms_calls'] : 0;

        $screen = Cooked_Settings::tabs_fields( true );

        $this->assertArrayHasKey( 'recipe_settings', $screen );
        $this->assertGreaterThan( $posts_before, $GLOBALS['_cooked_test_get_posts_calls'] );
        $this->assertGreaterThan( $terms_before, $GLOBALS['_cooked_test_get_terms_calls'] );
        $this->assertTrue( Cooked_Settings::includes_setting_options() );
    }

    public function test_card_settings_reuses_a_full_recipe_row() {
        $row = [
            'id' => 9,
            'title' => 'Soup',
            'author' => [
                'id' => 2,
                'name' => 'Ada',
            ],
        ];
        $before = isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0;

        $this->assertSame( $row, Cooked_Recipes::card_settings( $row ) );
        $this->assertSame( $before, isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0 );

        $GLOBALS['_cooked_test_posts'][ 4 ] = (object) [
            'ID' => 4,
            'post_title' => 'Bread',
            'post_excerpt' => '',
            'post_author' => 1,
            'post_status' => 'publish',
            'post_type' => 'cp_recipe',
        ];
        $GLOBALS['_cooked_test_query_posts'] = [ $GLOBALS['_cooked_test_posts'][ 4 ] ];

        $loaded = Cooked_Recipes::card_settings( 4 );

        $this->assertGreaterThan( $before, $GLOBALS['_cooked_test_query_count'] );
        $this->assertSame( 4, $loaded['id'] );
        $this->assertArrayHasKey( 'author', $loaded );
    }

    public function test_list_view_suspends_cache_only_for_unbounded_lists() {
        $GLOBALS['wp_query'] = (object) [ 'query' => [] ];
        $GLOBALS['_cooked_settings'] = [
            'recipe_taxonomies' => [],
            'recipes_per_page' => 12,
            'recipe_info_display_options' => [],
        ];
        $GLOBALS['_cooked_test_query_posts'] = [];
        $GLOBALS['_cooked_test_suspend_cache'] = false;
        $GLOBALS['_cooked_test_suspend_cache_calls'] = [];

        Cooked_Recipes::list_view( $this->list_atts( 12 ) );

        $suspended = array_values( array_filter(
            $GLOBALS['_cooked_test_suspend_cache_calls'],
            function ( $call ) {
                return true === $call;
            }
        ) );
        $this->assertSame( [], $suspended );

        $GLOBALS['_cooked_test_suspend_cache_calls'] = [];
        Cooked_Recipes::list_view( $this->list_atts( -1 ) );
        $this->assertContains( true, $GLOBALS['_cooked_test_suspend_cache_calls'] );
        $this->assertFalse( wp_suspend_cache_addition() );

        $keep = function () {
            return false;
        };
        add_filter( 'cooked_suspend_cache_during_list', $keep );
        $GLOBALS['_cooked_test_suspend_cache_calls'] = [];
        Cooked_Recipes::list_view( $this->list_atts( -1 ) );
        remove_filter( 'cooked_suspend_cache_during_list', $keep );

        $suspended = array_values( array_filter(
            $GLOBALS['_cooked_test_suspend_cache_calls'],
            function ( $call ) {
                return true === $call;
            }
        ) );
        $this->assertSame( [], $suspended );
    }

    public function test_taxonomy_card_uses_one_limited_query() {
        $GLOBALS['_cooked_test_terms'] = [
            (object) [
                'term_id' => 3,
                'name' => 'Dinner',
                'slug' => 'dinner',
                'taxonomy' => 'cp_recipe_category',
                'parent' => 0,
            ],
        ];
        $GLOBALS['_cooked_test_query_posts'] = [];

        $warnings = [];
        set_error_handler( function ( $errno, $errstr ) use ( &$warnings ) {
            $warnings[] = $errstr;
            return true;
        } );
        $empty = Cooked_Taxonomies::card( 3 );
        restore_error_handler();

        $this->assertSame( [], $warnings );
        $this->assertIsString( $empty );
        $this->assertSame( 1, $GLOBALS['_cooked_test_last_query']['posts_per_page'] );
        $this->assertStringContainsString( '0 Recipe', $empty );

        $GLOBALS['_cooked_test_query_posts'] = [
            (object) [ 'ID' => 8, 'post_title' => 'A' ],
            (object) [ 'ID' => 9, 'post_title' => 'B' ],
        ];
        $filled = Cooked_Taxonomies::card( 3 );

        $this->assertSame( 1, $GLOBALS['_cooked_test_last_query']['posts_per_page'] );
        $this->assertStringContainsString( '2 Recipes', $filled );
    }

    public function test_recipe_card_template_does_not_reload_a_full_row() {
        $GLOBALS['_cooked_settings'] = [
            'recipe_info_display_options' => [ 'author' ],
            'recipe_taxonomies' => [],
        ];
        $recipe = [
            'id' => 9,
            'title' => 'Soup',
            'excerpt' => '',
            'author' => [
                'id' => 2,
                'name' => 'Ada',
            ],
        ];
        $GLOBALS['recipe'] = $recipe;
        $GLOBALS['recipe_classes'] = [];
        $GLOBALS['recipe_settings'] = [];
        $before = isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0;

        ob_start();
        include COOKED_DIR . 'templates/front/recipe-single.php';
        $html = ob_get_clean();

        $this->assertStringContainsString( 'Soup', $html );
        $this->assertSame( $before, isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0 );
    }

    public function test_taxonomy_shortcodes_do_not_load_a_sample_recipe() {
        $shortcodes = ( new ReflectionClass( 'Cooked_Shortcodes' ) )->newInstanceWithoutConstructor();
        $before = isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0;

        $result = $shortcodes->preprocess_shortcode( 'kept', 'cooked-categories', [], [] );

        $this->assertSame( 'kept', $result );
        $this->assertSame( $before, isset( $GLOBALS['_cooked_test_query_count'] ) ? $GLOBALS['_cooked_test_query_count'] : 0 );
    }
}
