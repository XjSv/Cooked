<?php

if ( ! class_exists( 'Cooked_Shortcodes' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-shortcodes.php';
}

class Cooked_Filter_Info_Helper {
    public static function cooked_info_sentinel( $recipe ) {
        echo '<span class="cooked-sentinel">SENTINEL</span>';
    }
}

class ShortcodesFiltersTest extends FilterTestCase {

    protected $shortcodes;

    protected function setUp(): void {
        parent::setUp();
        $this->shortcodes = new Cooked_Shortcodes();
        $GLOBALS['wp_query'] = (object) [ 'query' => [] ];
        $GLOBALS['recipe_settings'] = [
            'id'        => 1,
            'title'     => 'Test Recipe',
            'nutrition' => [ 'servings' => 4 ],
        ];
        $GLOBALS['_cooked_settings']['recipe_info_display_options'] = [
            'author',
            'difficulty_level',
            'servings',
        ];
        $GLOBALS['_cooked_settings']['browse_page'] = 10;
        $GLOBALS['_cooked_settings']['recipe_author_permalink'] = 'recipe-author';
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['_cooked_test_options']['page_on_front'] = 0;
    }

    public function test_cooked_browse_shortcode_default_attributes_reach_query() {
        $this->with_filter(
            'cooked_browse_shortcode_default_attributes',
            function ( $atts ) {
                $atts['show'] = 7;
                return $atts;
            },
            function () {
                $this->shortcodes->cooked_browse_shortcode( [] );
            }
        );

        $this->assertSame( 7, (int) $GLOBALS['_cooked_test_last_query']['posts_per_page'] );
    }

    public function test_cooked_recipe_shortcode_output_can_replace_not_found_html() {
        $output = $this->with_filter(
            'cooked_recipe_shortcode_output',
            function () {
                return '<div class="filtered-recipe">X</div>';
            },
            function () {
                return $this->shortcodes->cooked_recipe_shortcode( [ 'id' => 99 ] );
            }
        );

        $this->assertSame( '<div class="filtered-recipe">X</div>', $output );
    }

    public function test_cooked_recipe_embed_blocked_message_can_replace_text() {
        $property = new ReflectionProperty( 'Cooked_Shortcodes', 'recipe_embed_stack' );
        if ( PHP_VERSION_ID < 80100 ) {
            $property->setAccessible( true );
        }
        $property->setValue( null, [ 5 ] );

        $output = $this->with_filter(
            'cooked_recipe_embed_blocked_message',
            function () {
                return 'BLOCKED_BY_FILTER';
            },
            function () {
                return $this->shortcodes->cooked_recipe_shortcode( [ 'id' => 5 ] );
            }
        );

        $property->setValue( null, [] );
        $this->assertStringContainsString( 'BLOCKED_BY_FILTER', $output );
    }

    public function test_cooked_recipe_gallery_options_appear_in_markup() {
        $GLOBALS['recipe_settings']['gallery'] = [
            'type'      => 'cooked',
            'items'     => [ 11 ],
            'video_url' => '',
        ];

        $html = $this->with_filter(
            'cooked_recipe_gallery_options',
            function ( $options ) {
                $options['data-fit'] = 'contain';
                return $options;
            },
            function () {
                return $this->shortcodes->cooked_gallery_shortcode( [] );
            }
        );

        $this->assertStringContainsString( 'data-fit="contain"', $html );
    }

    public function test_cooked_gallery_video_last_option_moves_video() {
        $GLOBALS['recipe_settings']['gallery'] = [
            'type'      => 'cooked',
            'items'     => [ 11 ],
            'video_url' => 'https://example.com/video.mp4',
        ];

        $html = $this->with_filter(
            'cooked_gallery_video_last_option',
            function () {
                return true;
            },
            function () {
                return $this->shortcodes->cooked_gallery_shortcode( [] );
            }
        );

        $video_pos = strpos( $html, 'https://example.com/video.mp4' );
        $image_pos = strpos( $html, 'img-11.jpg' );
        $this->assertNotFalse( $video_pos );
        $this->assertNotFalse( $image_pos );
        $this->assertGreaterThan( $image_pos, $video_pos );
    }

    public function test_cooked_gallery_items_output_can_replace_items() {
        $GLOBALS['recipe_settings']['gallery'] = [
            'type'      => 'cooked',
            'items'     => [ 11 ],
            'video_url' => '',
        ];

        $html = $this->with_filter(
            'cooked_gallery_items_output',
            function () {
                return [ 22 ];
            },
            function () {
                return $this->shortcodes->cooked_gallery_shortcode( [] );
            }
        );

        $this->assertStringContainsString( 'img-22.jpg', $html );
        $this->assertStringNotContainsString( 'img-11.jpg', $html );
    }

    public function test_cooked_default_info_array_and_methods_render_custom_field() {
        $html = $this->with_filter(
            'cooked_default_info_array',
            function ( $value ) {
                $value['sentinel'] = 'Sentinel';
                return $value;
            },
            function () {
                return $this->with_filter(
                    'cooked_available_info_shortcode_methods',
                    function ( $methods ) {
                        $methods['cooked_info_sentinel'] = 'Cooked_Filter_Info_Helper';
                        return $methods;
                    },
                    function () {
                        return $this->shortcodes->cooked_info_shortcode( [ 'include' => 'sentinel' ] );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'SENTINEL', $html );
    }

    public function test_cooked_info_shortcode_output_can_replace_html() {
        $html = $this->with_filter(
            'cooked_info_shortcode_output',
            function () {
                return '<div class="filtered-info">INFO</div>';
            },
            function () {
                return $this->shortcodes->cooked_info_shortcode( [ 'include' => 'servings' ] );
            }
        );

        $this->assertSame( '<div class="filtered-info">INFO</div>', $html );
    }

    public function test_cooked_author_permalink_appears_in_author_info() {
        $GLOBALS['recipe_settings']['author'] = [
            'id'            => 3,
            'user_nicename' => 'chef',
            'name'          => 'Chef',
            'profile_photo' => '',
        ];

        $html = $this->with_filter(
            'cooked_author_permalink',
            function () {
                return 'http://example.com/filtered-author/';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Shortcodes::cooked_info_author();
                    }
                );
            }
        );

        $this->assertStringContainsString( 'http://example.com/filtered-author/', $html );
    }

    public function test_cooked_show_difficulty_level_can_replace_html() {
        $html = $this->with_filter(
            'cooked_show_difficulty_level',
            function () {
                return '<span class="filtered-difficulty">Hard</span>';
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Shortcodes::cooked_info_difficulty(
                            [ 'difficulty_level' => 3 ]
                        );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'filtered-difficulty', $html );
        $this->assertStringContainsString( 'Hard', $html );
    }

    public function test_cooked_directions_shortcode_atts_can_add_amp() {
        $GLOBALS['recipe_settings']['directions'] = [
            [ 'content' => 'Mix well', 'image' => 15 ],
        ];

        $html = $this->with_filter(
            'cooked_directions_shortcode_atts',
            function ( $atts ) {
                $atts['amp'] = true;
                return $atts;
            },
            function () {
                return $this->with_filter(
                    'cooked_direction_image_html',
                    function ( $image, $atts ) {
                        return ! empty( $atts['amp'] ) ? '<amp-img></amp-img>' : $image;
                    },
                    function () {
                        return $this->shortcodes->cooked_directions_shortcode( [] );
                    },
                    2
                );
            }
        );

        $this->assertStringContainsString( '<amp-img></amp-img>', $html );
    }

    protected function seed_related_recipe( $post_id ) {
        $post = (object) [
            'ID'           => $post_id,
            'post_title'   => 'Recipe ' . $post_id,
            'post_excerpt' => 'Excerpt ' . $post_id,
            'post_author'  => 1,
            'post_status'  => 'publish',
            'post_type'    => 'cp_recipe',
        ];
        $GLOBALS['_cooked_test_posts'][ $post_id ]  = $post;
        $GLOBALS['_cooked_test_titles'][ $post_id ] = 'Recipe ' . $post_id;
        $GLOBALS['_cooked_test_post_meta'][ $post_id ]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'title'          => 'Recipe ' . $post_id,
            'excerpt'        => 'Excerpt ' . $post_id,
            'nutrition'      => [ 'servings' => 1 ],
        ];
    }

    public function test_cooked_related_recipes_display_ids_and_output() {
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_category'] = [ 3 ];
        $this->seed_related_recipe( 1 );
        $this->seed_related_recipe( 8 );

        $output = $this->with_filter(
            'cooked_related_recipes_result',
            function () {
                return [ [ 'id' => 8 ] ];
            },
            function () {
                return $this->with_filter(
                    'cooked_related_recipes_output',
                    function () {
                        return '<div class="filtered-related">R</div>';
                    },
                    function () {
                        return $this->shortcodes->cooked_related_recipes_shortcode(
                            [
                                'id'           => 1,
                                'hide_excerpt' => true,
                                'hide_author'  => true,
                            ]
                        );
                    }
                );
            }
        );

        $this->assertSame( '<div class="filtered-related">R</div>', $output );
    }

    public function test_cooked_related_recipes_display_ids_changes_ids_passed_to_output() {
        $GLOBALS['_cooked_test_object_terms']['1:cp_recipe_category'] = [ 3 ];
        $this->seed_related_recipe( 1 );
        $this->seed_related_recipe( 99 );

        $seen_ids = null;
        $this->with_filter(
            'cooked_related_recipes_result',
            function () {
                return [ [ 'id' => 8 ] ];
            },
            function () use ( &$seen_ids ) {
                return $this->with_filter(
                    'cooked_related_recipes_display_ids',
                    function () {
                        return [ 99 ];
                    },
                    function () use ( &$seen_ids ) {
                        return $this->with_filter(
                            'cooked_related_recipes_output',
                            function ( $output, $recipe_ids ) use ( &$seen_ids ) {
                                $seen_ids = $recipe_ids;
                                return '<div>ok</div>';
                            },
                            function () {
                                return $this->shortcodes->cooked_related_recipes_shortcode(
                                    [
                                        'id'           => 1,
                                        'hide_excerpt' => true,
                                        'hide_author'  => true,
                                    ]
                                );
                            },
                            2
                        );
                    }
                );
            }
        );

        $this->assertSame( [ 99 ], $seen_ids );
    }
}
