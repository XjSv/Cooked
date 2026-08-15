<?php

class RecipesFiltersTest extends FilterTestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_query'] = (object) [ 'query' => [] ];
        $GLOBALS['recipe_settings'] = [
            'id'        => 1,
            'title'     => 'Test Recipe',
            'nutrition' => [ 'servings' => 4 ],
        ];
        $GLOBALS['_cooked_test_post_meta'][1]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'title'          => 'Test Recipe',
            'nutrition'      => [ 'servings' => 4 ],
        ];
    }

    public function test_cooked_single_recipe_settings_can_add_a_key() {
        $settings = $this->with_filter(
            'cooked_single_recipe_settings',
            function ( $value, $post_id ) {
                $value['filtered'] = 'yes-' . $post_id;
                return $value;
            },
            function () {
                return Cooked_Recipes::get_settings( 1 );
            },
            2
        );

        $this->assertSame( 'yes-1', $settings['filtered'] );
        $this->assertSame( 1, $settings['id'] );
    }

    public function test_cooked_default_content_can_replace_layout() {
        $content = $this->with_filter(
            'cooked_default_content',
            function () {
                return '[cooked-filtered]';
            },
            function () {
                return Cooked_Recipes::default_content();
            }
        );

        $this->assertSame( '[cooked-filtered]', $content );
    }

    public function test_cooked_print_content_can_replace_layout() {
        $content = $this->with_filter(
            'cooked_print_content',
            function () {
                return '[cooked-print-filtered]';
            },
            function () {
                return Cooked_Recipes::print_content();
            }
        );

        $this->assertSame( '[cooked-print-filtered]', $content );
    }

    public function test_cooked_fsm_content_can_replace_layout() {
        $content = $this->with_filter(
            'cooked_fsm_content',
            function () {
                return '[cooked-fsm-filtered]';
            },
            function () {
                return Cooked_Recipes::fsm_content();
            }
        );

        $this->assertSame( '[cooked-fsm-filtered]', $content );
    }

    public function test_cooked_difficulty_levels_can_add_a_level() {
        $levels = $this->with_filter(
            'cooked_difficulty_levels',
            function ( $value ) {
                $value[4] = 'Expert';
                return $value;
            },
            function () {
                return Cooked_Recipes::difficulty_levels();
            }
        );

        $this->assertSame( 'Expert', $levels[4] );
    }

    public function test_cooked_gallery_types_can_add_a_type() {
        $GLOBALS['_cooked_test_query_posts'] = [
            (object) [
                'ID'         => 50,
                'post_title' => 'Custom Gallery Post',
            ],
        ];
        $GLOBALS['_cooked_test_titles'][50] = 'Custom Gallery Post';

        $types = $this->with_filter(
            'cooked_gallery_types',
            function ( $value ) {
                $value['custom'] = [
                    'title'          => 'Custom Gallery',
                    'required_class' => 'Cooked_Filter_Custom_Gallery',
                ];
                return $value;
            },
            function () {
                return Cooked_Recipes::gallery_types();
            }
        );

        $this->assertArrayHasKey( 'custom', $types );
        $this->assertSame( 'Custom Gallery', $types['custom']['title'] );
        $this->assertSame( 'Custom Gallery Post', $types['custom']['posts'][50] );
    }

    public function test_cooked_gallery_type_query_changes_wp_query_args() {
        $this->with_filter(
            'cooked_gallery_type_envira_query',
            function ( $args ) {
                $args['meta_key'] = 'filtered_gallery';
                return $args;
            },
            function () {
                Cooked_Recipes::gallery_types();
            }
        );

        $this->assertSame( 'filtered_gallery', $GLOBALS['_cooked_test_last_query']['meta_key'] );
    }

    public function test_cooked_servings_switcher_options_appear_in_html() {
        $html = $this->with_filter(
            'cooked_servings_switcher_options',
            function ( $value ) {
                $value['dozen'] = [ 'name' => 'Dozen Servings', 'value' => 12 ];
                return $value;
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Recipes::serving_size_switcher( 4 );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Dozen Servings', $html );
        $this->assertStringContainsString( 'value="12"', $html );
    }

    public function test_cooked_ingredient_name_appears_in_html() {
        $html = $this->with_filter(
            'cooked_ingredient_name',
            function () {
                return 'Filtered Flour';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Recipes::single_ingredient(
                            [
                                'name'        => 'Flour',
                                'amount'      => '1',
                                'measurement' => 'cup',
                            ]
                        );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Filtered Flour', $html );
        $this->assertStringNotContainsString( '>Flour<', $html );
    }

    public function test_cooked_single_ingredient_html_can_replace_markup() {
        $html = $this->with_filter(
            'cooked_single_ingredient_html',
            function () {
                return '<div class="filtered-ingredient">X</div>';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Recipes::single_ingredient(
                            [
                                'name'        => 'Flour',
                                'amount'      => '1',
                                'measurement' => 'cup',
                            ]
                        );
                    }
                );
            }
        );

        $this->assertSame( '<div class="filtered-ingredient">X</div>', $html );
    }

    public function test_cooked_direction_image_size_is_passed_to_attachment() {
        $this->with_filter(
            'cooked_direction_image_size',
            function () {
                return 'medium';
            },
            function () {
                $this->capture_output(
                    function () {
                        Cooked_Recipes::single_direction(
                            [
                                'content' => 'Mix',
                                'image'   => 15,
                            ],
                            1,
                            false,
                            1,
                            []
                        );
                    }
                );
            }
        );

        $this->assertSame( 'medium', $GLOBALS['_cooked_test_attachment_image_calls'][0]['size'] );
    }

    public function test_cooked_direction_image_html_can_rewrite_image() {
        $GLOBALS['_cooked_test_attachment_image_html'] = '<img src="orig.jpg">';

        $html = $this->with_filter(
            'cooked_direction_image_html',
            function () {
                return '<amp-img src="orig.jpg">';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Recipes::single_direction(
                            [
                                'content' => 'Mix',
                                'image'   => 15,
                            ],
                            1,
                            false,
                            1,
                            [ 'amp' => true ]
                        );
                    }
                );
            }
        );

        $this->assertStringContainsString( '<amp-img src="orig.jpg">', $html );
        $this->assertStringNotContainsString( '<img src="orig.jpg">', $html );
    }

    public function test_cooked_sync_c2_recipe_settings_can_add_a_key() {
        $settings = $this->with_filter(
            'cooked_sync_c2_recipe_settings',
            function ( $value ) {
                $value['filtered'] = true;
                return $value;
            },
            function () {
                return Cooked_Recipes::sync_c2_recipe_settings( [], 1 );
            }
        );

        $this->assertTrue( $settings['filtered'] );
    }

    public function test_cooked_browse_sorting_types_appear_in_search_box() {
        $html = $this->with_filter(
            'cooked_browse_sorting_types',
            function ( $value ) {
                $value['rating_desc'] = [
                    'slug' => 'rating_desc',
                    'name' => 'Highest Rated',
                ];
                return $value;
            },
            function () {
                $GLOBALS['recipe_args'] = [ 'orderby' => 'date', 'order' => 'desc' ];
                return $this->capture_output(
                    function () {
                        echo Cooked_Recipes::recipe_search_box(
                            [
                                'hide_sorting' => false,
                                'hide_browse'  => true,
                                'compact'      => false,
                            ]
                        );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Highest Rated', $html );
        $this->assertStringContainsString( 'rating_desc', $html );
    }

    public function test_cooked_recipe_query_args_changes_wp_query() {
        $this->with_filter(
            'cooked_recipe_query_args',
            function ( $args ) {
                $args['meta_key'] = 'filtered_key';
                return $args;
            },
            function () {
                Cooked_Recipes::list_view( $this->list_view_atts() );
            }
        );

        $this->assertSame( 'filtered_key', $GLOBALS['_cooked_test_last_query']['meta_key'] );
    }

    public function test_cooked_recipe_public_query_filters_changes_wp_query() {
        $this->with_filter(
            'cooked_recipe_public_query_filters',
            function ( $args ) {
                $args['post_status'] = 'private';
                return $args;
            },
            function () {
                Cooked_Recipes::list_view( $this->list_view_atts() );
            }
        );

        $this->assertSame( 'private', $GLOBALS['_cooked_test_last_query']['post_status'] );
    }

    public function test_cooked_tax_query_filter_is_applied_to_query() {
        $this->with_filter(
            'cooked_tax_query_filter',
            function ( $tax_query ) {
                $tax_query[] = [
                    'taxonomy' => 'cp_recipe_category',
                    'field'    => 'slug',
                    'terms'    => [ 'filtered-cat' ],
                ];
                return $tax_query;
            },
            function () {
                Cooked_Recipes::list_view( $this->list_view_atts() );
            }
        );

        $this->assertSame( 'filtered-cat', $GLOBALS['_cooked_test_last_query']['tax_query'][0]['terms'][0] );
    }

    public function test_cooked_recipe_list_style_receives_layout_and_sets_style() {
        $GLOBALS['_cooked_test_query_posts'] = [
            (object) [
                'ID'           => 1,
                'post_title'   => 'Test Recipe',
                'post_excerpt' => 'A short excerpt',
                'post_author'  => 1,
                'post_status'  => 'publish',
                'post_type'    => 'cp_recipe',
            ],
        ];

        $received = null;
        $html     = $this->with_filter(
            'cooked_recipe_list_style',
            function ( $style, $layout ) use ( &$received ) {
                $received = $layout;
                return [ 'sentinel' => 'Cooked_Filter_List_Renderer' ];
            },
            function () {
                return Cooked_Recipes::list_view(
                    $this->list_view_atts(
                        [
                            'layout'     => 'custom-layout',
                            'search'     => false,
                            'pagination' => false,
                        ]
                    )
                );
            },
            2
        );

        $this->assertSame( 'custom-layout', $received );
        $this->assertStringContainsString( 'sentinel-list-style', $html );
        $this->assertStringContainsString( 'cooked-recipe-sentinel', $html );
    }

    public function test_cooked_active_taxonomies_rewrites_numeric_term_to_slug() {
        $GLOBALS['pagenow'] = 'edit.php';
        $GLOBALS['_cooked_test_terms'][] = (object) [
            'term_id'  => 9,
            'name'     => 'Soup',
            'slug'     => 'soup',
            'taxonomy' => 'cp_recipe_tag',
            'ID'       => 9,
        ];

        $query = new WP_Query();
        $query->query_vars['post_type'] = 'cp_recipe';
        $query->query_vars['cp_recipe_tag'] = 9;

        $this->with_filter(
            'cooked_active_taxonomies',
            function () {
                return [ 'cp_recipe_tag' ];
            },
            function () use ( $query ) {
                $recipes = new Cooked_Recipes();
                $recipes->custom_taxonomy_in_query( $query );
            }
        );

        $this->assertSame( 'soup', $query->query_vars['cp_recipe_tag'] );
    }

    public function test_cooked_pagination_style_and_args_change_output() {
        $GLOBALS['current_recipe_page'] = 1;
        $GLOBALS['atts'] = [];
        $query = new WP_Query();
        $query->max_num_pages = 4;

        $html = $this->with_filter(
            'cooked_pagination_args',
            function ( $args ) {
                $args['prev_text'] = 'FILTER_PREV';
                return $args;
            },
            function () use ( $query ) {
                return Cooked_Recipes::pagination( $query, [] );
            }
        );

        $this->assertSame( 'FILTER_PREV', $GLOBALS['_cooked_test_paginate_args']['prev_text'] );
        $this->assertStringContainsString( 'page-numbers', $html );
    }

    public function test_cooked_pagination_style_can_swap_renderer() {
        $GLOBALS['current_recipe_page'] = 1;
        $GLOBALS['atts'] = [];
        $query = new WP_Query();
        $query->max_num_pages = 4;

        $html = $this->with_filter(
            'cooked_pagination_style',
            function () {
                return [ 'numbered_pagination' => 'Cooked_Recipes' ];
            },
            function () use ( $query ) {
                return Cooked_Recipes::pagination( $query, [] );
            }
        );

        $this->assertStringContainsString( 'cooked-pagination-numbered', $html );
    }

    public function test_cooked_query_where_filter_changes_meta_sql() {
        $q = new WP_Query();
        $q->set( '_cooked_title', 'pasta' );

        $sql = $this->with_filter(
            'cooked_query_where_filter',
            function () {
                return "wp_posts.post_title like '%FILTERED%'";
            },
            function () use ( $q ) {
                Cooked_Recipes::cooked_pre_get_posts( $q );
                return apply_filters(
                    'get_meta_sql',
                    [ 'where' => ' AND extra_clause' ]
                );
            }
        );

        $this->assertStringContainsString( 'FILTERED', $sql['where'] );
    }

    public function test_cooked_recipe_content_filter_changes_singular_content() {
        $GLOBALS['_cooked_content_unfiltered'] = false;
        $GLOBALS['_cooked_test_is_singular'] = true;
        $GLOBALS['post'] = get_post( 1 );
        $GLOBALS['wp_embed'] = new class {
            public function autoembed( $content ) {
                return $content;
            }
        };
        $GLOBALS['_cooked_test_post_meta'][1]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'title'          => 'Test Recipe',
            'content'        => '[cooked-ingredients]',
            'nutrition'      => [ 'servings' => 1 ],
        ];

        $recipes = new Cooked_Recipes();
        $filtered = $this->with_filter(
            'cooked_recipe_content_filter',
            function () {
                return 'FILTERED_RECIPE_CONTENT';
            },
            function () use ( $recipes ) {
                return $recipes->recipe_template( 'original' );
            }
        );

        $this->assertSame( 'FILTERED_RECIPE_CONTENT', $filtered );
    }

    public function test_cooked_term_name_appears_in_taxonomy_list() {
        if ( ! class_exists( 'Cooked_Taxonomies' ) ) {
            require_once COOKED_DIR . 'includes/class.cooked-taxonomies.php';
        }

        $GLOBALS['_cooked_test_terms'][] = (object) [
            'term_id'  => 5,
            'name'     => 'Desserts',
            'slug'     => 'desserts',
            'taxonomy' => 'cp_recipe_category',
        ];

        $html = $this->with_filter(
            'cooked_term_name',
            function () {
                return 'Filtered Desserts';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Taxonomies::single_taxonomy_block( 5, 'list' );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Filtered Desserts', $html );
        $this->assertStringNotContainsString( '>Desserts<', $html );
    }
}

class Envira_Gallery {}

class Cooked_Filter_Custom_Gallery {}

class Cooked_Filter_List_Renderer {
    public static function list_style_sentinel( $atts ) {
        echo '<div class="sentinel-list-style"></div>';
    }
}

