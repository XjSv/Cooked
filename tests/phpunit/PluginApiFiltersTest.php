<?php

if ( ! class_exists( 'Cooked_Roles' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-roles.php';
}
if ( ! class_exists( 'Cooked_Taxonomies' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-taxonomies.php';
}
if ( ! class_exists( 'Cooked_Post_Types' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-post-types.php';
}
if ( ! class_exists( 'Cooked_Widgets' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-widgets.php';
}
if ( ! class_exists( 'Cooked_Allergens' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-allergens.php';
}
if ( ! class_exists( 'Cooked_Delicious_Recipes' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-delicious-recipes.php';
}
if ( ! class_exists( 'Cooked_Recipe_Maker_Recipes' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-recipe-maker.php';
}
if ( ! class_exists( 'Cooked_Import' ) ) {
    require_once COOKED_DIR . 'includes/class.cooked-import.php';
}

class PluginApiFiltersTest extends FilterTestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['_cooked_settings']['recipe_taxonomies']            = [ 'cp_recipe_category' ];
        $GLOBALS['_cooked_settings']['recipe_info_display_options']  = [ 'author' ];
        $GLOBALS['_cooked_settings']['advanced']                     = [];
        $GLOBALS['_cooked_settings']['recipe_permalink']             = 'recipes';
        $GLOBALS['_cooked_settings']['recipe_author_permalink']      = 'recipe-author';
        $GLOBALS['_cooked_settings']['recipe_category_permalink']    = 'recipe-category';
        $GLOBALS['_cooked_settings']['recipe_category_permalink']    = 'recipe-category';
        $GLOBALS['post'] = (object) [
            'ID'           => 1,
            'post_title'   => 'Test Recipe',
            'post_excerpt' => '',
            'post_author'  => 1,
            'post_status'  => 'publish',
            'post_type'    => 'cp_recipe',
        ];
    }

    public function test_cooked_schema_html_can_replace_json_ld() {
        $html = $this->with_filter(
            'cooked_schema_html',
            function () {
                return '<script type="application/ld+json">{"filtered":true}</script>';
            },
            function () {
                return Cooked_SEO::json_ld(
                    [
                        'id'         => 1,
                        'title'      => 'Test Recipe',
                        'ingredients' => [],
                        'directions'  => [],
                        'nutrition'   => [ 'servings' => 1 ],
                    ]
                );
            }
        );

        $this->assertStringContainsString( '{"filtered":true}', $html );
    }

    public function test_cooked_schema_array_can_add_a_key() {
        $schema = $this->with_filter(
            'cooked_schema_array',
            function ( $value ) {
                $value['filtered'] = 'yes';
                return $value;
            },
            function () {
                return Cooked_SEO::schema_values(
                    [
                        'id'          => 1,
                        'title'       => 'Test Recipe',
                        'ingredients' => [],
                        'directions'  => [],
                        'nutrition'   => [ 'servings' => 1 ],
                    ]
                );
            }
        );

        $this->assertSame( 'yes', $schema['filtered'] );
        $this->assertSame( 'Recipe', $schema['@type'] );
    }

    public function test_cooked_format_author_name_can_replace_name() {
        $name = $this->with_filter(
            'cooked_format_author_name',
            function () {
                return 'Filtered Author';
            },
            function () {
                return Cooked_Users::format_author_name( 'John Doe', 'full' );
            }
        );

        $this->assertSame( 'Filtered Author', $name );
    }

    public function test_cooked_format_author_name_safe_array_skips_escaping() {
        $name = $this->with_filter(
            'cooked_format_author_name',
            function () {
                return [ '<em>Safe</em>', true ];
            },
            function () {
                return Cooked_Users::format_author_name( 'John Doe', 'full' );
            }
        );

        $this->assertSame( '<em>Safe</em>', $name );
    }

    public function test_cooked_recipe_editor_caps_reach_add_role() {
        $this->with_filter(
            'cooked_recipe_editor_caps',
            function ( $caps ) {
                $caps['filtered_cap'] = 1;
                return $caps;
            },
            function () {
                Cooked_Roles::add_roles();
            }
        );

        $this->assertSame(
            1,
            $GLOBALS['_cooked_test_registered_roles']['cooked_recipe_editor']['caps']['filtered_cap']
        );
    }

    public function test_cooked_taxonomy_settings_can_change_slug() {
        $taxonomies = $this->with_filter(
            'cooked_taxonomy_settings',
            function ( $value ) {
                $value['cp_recipe_category'] = 'filtered-category';
                return $value;
            },
            function () {
                return Cooked_Taxonomies::get();
            }
        );

        $this->assertSame( 'filtered-category', $taxonomies['cp_recipe_category']['rewrite']['slug'] );
    }

    public function test_cooked_taxonomies_can_add_a_taxonomy() {
        $taxonomies = $this->with_filter(
            'cooked_taxonomies',
            function ( $value ) {
                $value['cp_recipe_sentinel'] = [ 'labels' => [ 'name' => 'Sentinel' ] ];
                return $value;
            },
            function () {
                return Cooked_Taxonomies::get();
            }
        );

        $this->assertArrayHasKey( 'cp_recipe_sentinel', $taxonomies );
    }

    public function test_cooked_taxonomy_settings_update_writes_filtered_permalink() {
        $GLOBALS['_cooked_test_is_admin'] = true;
        $_GET['settings-updated']         = 'true';
        $_GET['page']                     = 'cooked_settings';
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'recipe_permalink'          => 'recipes',
            'recipe_author_permalink'   => 'recipe-author',
            'recipe_category_permalink' => 'recipe-category',
        ];

        $this->with_filter(
            'cooked_taxonomy_settings_update',
            function ( $value ) {
                $value['recipe_permalink'] = 'filtered-recipes';
                return $value;
            },
            function () {
                Cooked_Post_Types::init();
            }
        );

        $this->assertSame(
            'filtered-recipes',
            $GLOBALS['_cooked_test_options']['cooked_settings']['recipe_permalink']
        );
    }

    public function test_cooked_post_types_can_add_a_type() {
        $types = $this->with_filter(
            'cooked_post_types',
            function ( $value ) {
                $value['cp_recipe_extra'] = [ 'labels' => [ 'name' => 'Extra' ] ];
                return $value;
            },
            function () {
                return Cooked_Post_Types::get();
            }
        );

        $this->assertArrayHasKey( 'cp_recipe_extra', $types );
    }

    public function test_cooked_widgets_can_add_a_widget() {
        $widgets = new Cooked_Widgets();
        $this->with_filter(
            'cooked_widgets',
            function ( $value ) {
                $value[] = 'Cooked_Widget_Sentinel';
                return $value;
            },
            function () use ( $widgets ) {
                $widgets->register_widgets();
            }
        );

        $this->assertContains( 'Cooked_Widget_Sentinel', $GLOBALS['_cooked_test_registered_widgets'] );
    }

    public function test_cooked_can_show_recipe_hides_card_widget() {
        $widget = new Cooked_Widget_Recipe_Card();
        $args   = [
            'before_widget' => '<div class="widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        ];

        $hidden = $this->with_filter(
            'cooked_can_show_recipe',
            function () {
                return false;
            },
            function () use ( $widget, $args ) {
                return $this->capture_output(
                    function () use ( $widget, $args ) {
                        $widget->widget( $args, [ 'recipe_id' => 1, 'title' => 'Card' ] );
                    }
                );
            }
        );

        $this->assertSame( '', $hidden );

        $shown = $this->capture_output(
            function () use ( $widget, $args ) {
                $widget->widget( $args, [ 'recipe_id' => 1, 'title' => 'Card' ] );
            }
        );

        $this->assertStringContainsString( '<div class="widget">', $shown );
    }

    public function test_cooked_allergens_can_add_an_allergen() {
        $allergens = $this->with_filter(
            'cooked_allergens',
            function ( $value ) {
                $value['sentinel'] = [
                    'label' => 'Sentinel Allergen',
                    'icon'  => 'allergen-sentinel',
                ];
                return $value;
            },
            function () {
                return Cooked_Allergens::get_allergens();
            }
        );

        $this->assertArrayHasKey( 'sentinel', $allergens );
        $this->assertSame( 'Sentinel Allergen', $allergens['sentinel']['label'] );
    }

    public function test_cooked_recipe_card_allergen_hooks_can_add_a_hook() {
        $hooks = $this->with_filter(
            'cooked_recipe_card_allergen_hooks',
            function ( $value ) {
                $value[] = 'cooked_sentinel_allergen_hook';
                return $value;
            },
            function () {
                return Cooked_Allergens::get_recipe_card_hooks();
            }
        );

        $this->assertContains( 'cooked_sentinel_allergen_hook', $hooks );
    }

    public function test_cooked_import_tabs_fields_can_add_a_tab() {
        $tabs = $this->with_filter(
            'cooked_import_tabs_fields',
            function ( $value ) {
                $value['sentinel'] = [ 'name' => 'Sentinel Import' ];
                return $value;
            },
            function () {
                return Cooked_Import::tabs_fields();
            }
        );

        $this->assertArrayHasKey( 'sentinel', $tabs );
        $this->assertSame( 'Sentinel Import', $tabs['sentinel']['name'] );
    }

    public function test_cp_recipe_metabox_post_types_registers_meta_box() {
        $meta = new Cooked_Recipe_Meta();

        $this->with_filter(
            'cp_recipe_metabox_post_types',
            function () {
                return [ 'cp_recipe', 'page' ];
            },
            function () use ( $meta ) {
                $meta->add_recipe_meta_box( 'page' );
            }
        );

        $screens = array_column( $GLOBALS['_cooked_test_meta_boxes'], 'screen' );
        $this->assertContains( 'page', $screens );
    }

    public function test_cooked_recipe_admin_tabs_can_add_a_tab() {
        $GLOBALS['_cooked_test_post_meta'][1]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'ingredients'    => [],
        ];

        $html = $this->with_filter(
            'cooked_recipe_admin_tabs',
            function ( $tabs ) {
                $tabs['sentinel'] = [
                    'icon'        => 'star',
                    'name'        => 'Sentinel Tab',
                    'conditional' => false,
                    'value'       => false,
                ];
                return $tabs;
            },
            function () {
                return $this->capture_output(
                    function () {
                        cooked_render_recipe_fields( 1 );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Sentinel Tab', $html );
    }

    public function test_cooked_ingredient_field_classes_appear_in_meta() {
        $GLOBALS['_cooked_test_post_meta'][1]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'ingredients'    => [
                [ 'amount' => '1', 'measurement' => 'cup', 'name' => 'Flour' ],
            ],
        ];

        $html = $this->with_filter(
            'cooked_ingredient_field_classes',
            function ( $classes ) {
                return $classes . ' filtered-ingredient';
            },
            function () {
                return $this->capture_output(
                    function () {
                        cooked_render_recipe_fields( 1 );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'filtered-ingredient', $html );
    }

    public function test_cooked_available_info_vars_appear_in_shortcodes_tab() {
        $GLOBALS['_cooked_test_post_meta'][1]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
        ];

        $html = $this->with_filter(
            'cooked_available_info_vars',
            function ( $vars ) {
                $vars['sentinel'] = 'Sentinel Var';
                return $vars;
            },
            function () {
                return $this->capture_output(
                    function () {
                        cooked_render_recipe_fields( 1 );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'sentinel', $html );
        $this->assertStringContainsString( 'Sentinel Var', $html );
    }

    public function test_cooked_seo_recipe_content_and_should_update_on_meta_save() {
        $_POST['cooked_recipe_custom_box_nonce'] = 'test_nonce';
        $_POST['_recipe_settings']               = [
            'excerpt' => 'Saved excerpt',
            'title'   => 'Saved',
        ];

        $meta = new Cooked_Recipe_Meta();

        $this->with_filter(
            'cooked_seo_recipe_content',
            function () {
                return 'FILTERED_SEO_CONTENT';
            },
            function () use ( $meta ) {
                $this->with_filter(
                    'cooked_should_update_post_content',
                    function () {
                        return true;
                    },
                    function () use ( $meta ) {
                        $meta->save_recipe_meta_box( 7 );
                    }
                );
            }
        );

        $last = end( $GLOBALS['_cooked_test_updated_posts'] );
        $this->assertSame( 'FILTERED_SEO_CONTENT', $last['post_content'] );
        $this->assertSame( 7, $last['ID'] );

        $GLOBALS['_cooked_test_updated_posts'] = [];
        $this->with_filter(
            'cooked_should_update_post_content',
            function () {
                return false;
            },
            function () use ( $meta ) {
                $meta->save_recipe_meta_box( 8 );
            }
        );

        $last = end( $GLOBALS['_cooked_test_updated_posts'] );
        $this->assertArrayNotHasKey( 'post_content', $last );
        $this->assertSame( 8, $last['ID'] );
    }

    public function test_cooked_seo_recipe_content_and_should_update_on_csv_import() {
        $this->with_filter(
            'cooked_seo_recipe_content',
            function () {
                return 'CSV_FILTERED_SEO';
            },
            function () {
                Cooked_CSV_Import::import_recipe(
                    [
                        'title'   => 'Imported Recipe',
                        'excerpt' => 'Imported excerpt',
                    ]
                );
            }
        );

        $last = end( $GLOBALS['_cooked_test_updated_posts'] );
        $this->assertSame( 'CSV_FILTERED_SEO', $last['post_content'] );

        $GLOBALS['_cooked_test_updated_posts'] = [];
        $this->with_filter(
            'cooked_should_update_post_content',
            function () {
                return false;
            },
            function () {
                Cooked_CSV_Import::import_recipe(
                    [
                        'title'   => 'Imported Recipe Two',
                        'excerpt' => 'Excerpt',
                    ]
                );
            }
        );

        $last = end( $GLOBALS['_cooked_test_updated_posts'] );
        $this->assertArrayNotHasKey( 'post_content', $last );
    }

    public function test_cooked_timer_sound_mp3_is_localized() {
        $enqueues = new Cooked_Enqueues();
        $this->with_filter(
            'cooked_timer_sound_mp3',
            function () {
                return 'http://example.com/filtered-ding.mp3';
            },
            function () use ( $enqueues ) {
                $enqueues->enqueues( '' );
            }
        );

        $found = false;
        foreach ( $GLOBALS['_cooked_test_inline_scripts'] as $script ) {
            if ( false !== strpos( $script['data'], 'filtered-ding.mp3' ) ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue( $found );
    }

    public function test_cooked_whats_new_title_appears_in_changelog() {
        $html = $this->with_filter(
            'cooked_whats_new_title',
            function () {
                return 'Filtered Whats New';
            },
            function () {
                return Cooked_Functions::parse_readme_changelog();
            }
        );

        $this->assertStringContainsString( 'Filtered Whats New', $html );
    }

    public function test_cooked_default_print_options_check_a_box() {
        $html = $this->with_filter(
            'cooked_default_print_options',
            function ( $value ) {
                $value['print_options_info'] = 'checked';
                return $value;
            },
            function () {
                return $this->capture_output(
                    function () {
                        Cooked_Functions::print_options();
                    }
                );
            }
        );

        $this->assertMatchesRegularExpression(
            '/id="print_options_info"[^>]*checked/',
            $html
        );
    }

    public function test_cooked_version_updates_adds_a_runnable_tool() {
        $tools = $this->with_filter(
            'cooked_version_updates',
            function ( $updates ) {
                $updates['99.0.0'] = [ 'sentinel_tool' ];
                return $updates;
            },
            function () {
                return Cooked_Updates::get_runnable_tools();
            }
        );

        $ids = array_column( $tools, 'id' );
        $this->assertContains( 'sentinel_tool', $ids );
    }
}
