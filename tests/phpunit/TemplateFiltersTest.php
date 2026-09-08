<?php

class Cooked_Filter_Template_List_Renderer {
    public static function list_style_sentinel( $atts ) {
        echo '<div class="sentinel-list-style"></div>';
    }
}

class TemplateFiltersTest extends FilterTestCase {

    protected function seed_recipe( $post_id = 1 ) {
        $GLOBALS['post'] = (object) [
            'ID'           => $post_id,
            'post_title'   => 'Test Recipe',
            'post_excerpt' => '',
            'post_author'  => 1,
            'post_status'  => 'publish',
            'post_type'    => 'cp_recipe',
        ];
        $GLOBALS['_cooked_test_posts'][ $post_id ] = $GLOBALS['post'];
        $GLOBALS['_cooked_test_post_meta'][ $post_id ]['_recipe_settings'] = [
            'cooked_version' => COOKED_VERSION,
            'id'             => $post_id,
            'title'          => 'Test Recipe',
            'content'        => '<p>[cooked-ingredients]</p>Visible copy',
            'excerpt'        => 'A short excerpt',
            'nutrition'      => [ 'servings' => 4 ],
        ];
        $GLOBALS['_cooked_test_query_posts'] = [ $GLOBALS['post'] ];
        $GLOBALS['recipe'] = [
            'id'      => $post_id,
            'title'   => 'Test Recipe',
            'excerpt' => 'A short excerpt',
            'author'  => [ 'name' => 'Test Author' ],
        ];
        $GLOBALS['recipe_settings'] = $GLOBALS['_cooked_test_post_meta'][ $post_id ]['_recipe_settings'];
        $GLOBALS['_cooked_settings']['recipe_info_display_options'] = [ 'author', 'excerpt' ];
    }

    public function test_cooked_pre_recipe_content_and_recipe_content_on_nonsingular_path() {
        $this->seed_recipe();
        $GLOBALS['_cooked_test_is_singular'] = false;
        $GLOBALS['_cooked_test_is_feed']     = false;

        $html = $this->with_filter(
            'cooked_pre_recipe_content',
            function ( $content ) {
                return $content . '<p>PRE_FILTER</p>';
            },
            function () {
                return $this->with_filter(
                    'cooked_recipe_content',
                    function ( $content ) {
                        return $content . '<p>CONTENT_FILTER</p>';
                    },
                    function () {
                        return $this->capture_output(
                            function () {
                                $recipe_seo_content = '';
                                include COOKED_DIR . 'templates/front/recipe.php';
                            }
                        );
                    }
                );
            }
        );

        $this->assertStringContainsString( 'PRE_FILTER', $html );
        $this->assertStringContainsString( 'CONTENT_FILTER', $html );
        $this->assertStringNotContainsString( '[cooked-ingredients]', $html );
    }

    public function test_cooked_author_template_override_replaces_author_heading() {
        $this->seed_recipe();
        $GLOBALS['recipes']             = [ [ 'id' => 1, 'title' => 'Test Recipe' ] ];
        $GLOBALS['recipe_args']         = [ 'author' => 1 ];
        $GLOBALS['current_recipe_page'] = 1;
        $GLOBALS['list_id_counter']     = 0;
        $GLOBALS['atts']                = $this->list_view_atts(
            [
                'search'     => false,
                'pagination' => false,
            ]
        );

        $html = $this->with_filter(
            'cooked_author_template_override',
            function () {
                return '<div class="author-override">Filtered Author</div>';
            },
            function () {
                return $this->capture_output(
                    function () {
                        include COOKED_DIR . 'templates/front/recipe-list.php';
                    }
                );
            }
        );

        $this->assertStringContainsString( 'Filtered Author', $html );
        $this->assertStringNotContainsString( 'Recipes by', $html );
    }

    public function test_cooked_recipe_list_style_changes_list_markup() {
        $this->seed_recipe();
        $GLOBALS['recipes']             = [ [ 'id' => 1, 'title' => 'Test Recipe' ] ];
        $GLOBALS['recipe_args']         = [];
        $GLOBALS['current_recipe_page'] = 1;
        $GLOBALS['list_id_counter']     = 0;
        $GLOBALS['atts']                = $this->list_view_atts(
            [
                'search'     => false,
                'pagination' => false,
                'layout'     => 'grid',
            ]
        );

        $html = $this->with_filter(
            'cooked_recipe_list_style',
            function () {
                return [ 'sentinel' => 'Cooked_Filter_Template_List_Renderer' ];
            },
            function () {
                return $this->capture_output(
                    function () {
                        include COOKED_DIR . 'templates/front/recipe-list.php';
                    }
                );
            }
        );

        $this->assertStringContainsString( 'sentinel-list-style', $html );
        $this->assertStringContainsString( 'cooked-recipe-sentinel', $html );
    }

    public function test_cooked_single_recipe_classes_appear_on_card() {
        $this->seed_recipe();
        $GLOBALS['recipe_classes'] = false;

        $html = $this->with_filter(
            'cooked_single_recipe_classes',
            function ( $classes ) {
                $classes[] = 'filtered-recipe-class';
                return $classes;
            },
            function () {
                return $this->capture_output(
                    function () {
                        include COOKED_DIR . 'templates/front/recipe-single.php';
                    }
                );
            }
        );

        $this->assertStringContainsString( 'filtered-recipe-class', $html );
    }

    public function test_cooked_welcome_banner_img_changes_src() {
        $html = $this->with_filter(
            'cooked_welcome_banner_img',
            function () {
                return 'http://example.com/filtered-banner.png';
            },
            function () {
                return $this->capture_output(
                    function () {
                        include COOKED_DIR . 'templates/admin/welcome.php';
                    }
                );
            }
        );

        $this->assertStringContainsString( 'http://example.com/filtered-banner.png', $html );
    }
}
