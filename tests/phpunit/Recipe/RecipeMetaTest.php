<?php

use PHPUnit\Framework\TestCase;

class RecipeMetaTest extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['_cooked_test_logged_in'] );
        unset( $GLOBALS['_cooked_settings'] );
        unset( $GLOBALS['_cooked_test_filters']['cooked_recipe_settings_keys'] );
        parent::tearDown();
    }

    public function test_meta_cleanup_empty_input() {
        $result = Cooked_Recipe_Meta::meta_cleanup([]);
        $this->assertSame([], $result);
    }

    public function test_meta_cleanup_simple_field() {
        $input = ['guest_name' => 'My Recipe'];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertArrayHasKey('guest_name', $result);
    }

    public function test_meta_cleanup_strips_tags_from_non_content() {
        $input = ['guest_name' => '<script>alert("xss")</script>My Recipe'];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertStringNotContainsString('<script>', $result['guest_name']);
    }

    public function test_meta_cleanup_processes_nested_directions() {
        $input = [
            'directions' => [
                ['content' => 'Mix ingredients'],
                ['section_heading_name' => 'Step 1'],
            ]
        ];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertArrayHasKey('directions', $result);
    }

    public function test_meta_cleanup_processes_deeply_nested_arrays() {
        $input = [
            'ingredients' => [
                'rand1' => [
                    'amount' => '2',
                    'measurement' => 'cups',
                    'name' => 'Flour',
                ]
            ]
        ];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertArrayHasKey('ingredients', $result);
    }

    public function test_meta_cleanup_preserves_direction_video_field() {
        $input = [
            'directions' => [
                ['content' => 'Mix ingredients', 'video' => '42'],
            ]
        ];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertSame('42', $result['directions'][0]['video']);
    }

    public function test_meta_cleanup_sanitizes_direction_video_field() {
        $input = [
            'directions' => [
                ['content' => 'Mix', 'video' => '<script>alert(1)</script>'],
            ]
        ];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertStringNotContainsString('<script>', $result['directions'][0]['video']);
    }

    public function test_meta_cleanup_encodes_plain_fields_once() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'guest_name' => 'a & b',
        ] );

        $this->assertSame( 'a &amp; b', $result['guest_name'] );
        $this->assertStringNotContainsString( '&amp;amp;', $result['guest_name'] );
    }

    public function test_meta_cleanup_twice_double_encodes_plain_fields() {
        $once = Cooked_Recipe_Meta::meta_cleanup( [
            'guest_name' => 'a & b',
        ] );
        $twice = Cooked_Recipe_Meta::meta_cleanup( $once );

        $this->assertSame( 'a &amp;amp; b', $twice['guest_name'] );
    }

    public function test_meta_cleanup_normalizes_string_times() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'cook_time'  => '5 minutes',
            'prep_time'  => '15',
            'total_time' => '',
        ] );

        $this->assertSame( 5, $result['cook_time'] );
        $this->assertSame( 15, $result['prep_time'] );
        $this->assertSame( '', $result['total_time'] );
    }

    public function test_meta_cleanup_strips_excerpt_html_without_editor_role() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'excerpt' => '<b>Yum</b> & more',
        ] );

        $this->assertSame( 'Yum & more', $result['excerpt'] );
        $this->assertStringNotContainsString( '<b>', $result['excerpt'] );
        $this->assertStringNotContainsString( '&amp;', $result['excerpt'] );
    }

    public function test_meta_cleanup_keeps_excerpt_html_for_editor_role() {
        $GLOBALS['_cooked_test_logged_in'] = true;
        $GLOBALS['_cooked_settings']       = [
            'recipe_wp_editor_roles' => [ 'administrator' ],
        ];

        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'excerpt' => '<b>Yum</b>',
        ] );

        $this->assertSame( '<b>Yum</b>', $result['excerpt'] );
    }

    public function test_meta_cleanup_post_title_does_not_htmlentities() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'post_title' => 'Grandma\'s "Pie"',
        ] );

        $this->assertSame( 'Grandma\'s "Pie"', $result['post_title'] );
        $this->assertStringNotContainsString( '&quot;', $result['post_title'] );
        $this->assertStringNotContainsString( '&amp;', $result['post_title'] );
    }

    public function test_meta_cleanup_seo_description_does_not_htmlentities() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'seo_description' => 'you\'ll love this "recipe"',
        ] );

        $this->assertSame( 'you\'ll love this "recipe"', $result['seo_description'] );
        $this->assertStringNotContainsString( '&quot;', $result['seo_description'] );
        $this->assertStringNotContainsString( '&#039;', $result['seo_description'] );
        $this->assertStringNotContainsString( '&amp;', $result['seo_description'] );
    }

    public function test_meta_cleanup_seo_description_decodes_existing_entities() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'seo_description' => 'you&#039;ll love this &quot;recipe&quot;',
        ] );

        $this->assertSame( 'you\'ll love this "recipe"', $result['seo_description'] );
    }

    public function test_meta_cleanup_seo_description_strips_tags() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'seo_description' => '<b>Yum</b> & more',
        ] );

        $this->assertSame( 'Yum & more', $result['seo_description'] );
        $this->assertStringNotContainsString( '<b>', $result['seo_description'] );
    }

    public function test_meta_cleanup_drops_unknown_top_level_keys() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'excerpt'  => 'Kept',
            'whatever' => 'Dropped',
            'nested'   => [ 'a' => 'b' ],
        ] );

        $this->assertSame( [ 'excerpt' ], array_keys( $result ) );
    }

    public function test_meta_cleanup_keeps_keys_added_through_the_filter() {
        add_filter( 'cooked_recipe_settings_keys', function ( $keys ) {
            $keys[] = 'my_addon_field';
            return $keys;
        } );

        $result = Cooked_Recipe_Meta::meta_cleanup( [ 'my_addon_field' => 'Kept' ] );

        $this->assertSame( 'Kept', $result['my_addon_field'] );
    }

    public function test_meta_cleanup_sanitizes_nested_keys() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'nutrition' => [ 'Calories<b>' => '200' ],
        ] );

        $this->assertSame( [ 'caloriesb' ], array_keys( $result['nutrition'] ) );
    }

    public function test_meta_cleanup_strips_double_encoded_html_without_editor_role() {
        $result = Cooked_Recipe_Meta::meta_cleanup( [
            'notes'      => '&amp;lt;a href="https://example.com"&amp;gt;link&amp;lt;/a&amp;gt;',
            'directions' => [
                [ 'content' => '&amp;amp;lt;img src=x&amp;amp;gt;Mix' ],
            ],
        ] );

        // Rendering decodes once more before KSES (Cooked_Recipes::format_content()): nothing
        // may come back as a tag.
        $this->assertStringNotContainsString( '<', html_entity_decode( $result['notes'] ) );
        $this->assertSame( 'link', $result['notes'] );
        $this->assertSame( 'Mix', $result['directions'][0]['content'] );
    }

    public function test_strip_all_html_keeps_plain_text() {
        $this->assertSame( 'Salt & pepper, 2 > 1', Cooked_Recipe_Meta::strip_all_html( 'Salt &amp; pepper, 2 &gt; 1' ) );
    }

    public function test_save_skips_post_types_without_the_box() {
        $_POST['cooked_recipe_custom_box_nonce'] = 'test_nonce';
        $_POST['_recipe_settings']               = [ 'excerpt' => 'Should not save' ];
        $GLOBALS['_cooked_test_posts'][901]      = (object) [ 'ID' => 901, 'post_type' => 'post' ];

        ( new Cooked_Recipe_Meta() )->save_recipe_meta_box( 901 );

        $this->assertArrayNotHasKey( 901, $GLOBALS['_cooked_test_post_meta'] );
        unset( $GLOBALS['_cooked_test_posts'][901], $_POST['cooked_recipe_custom_box_nonce'], $_POST['_recipe_settings'] );
    }

    public function test_save_checks_edit_post_for_the_post() {
        $_POST['cooked_recipe_custom_box_nonce']  = 'test_nonce';
        $_POST['_recipe_settings']                = [ 'excerpt' => 'Should not save' ];
        $GLOBALS['_cooked_test_current_user_can'] = function ( $capability, ...$args ) {
            return ! ( 'edit_post' === $capability && 902 === ( $args[0] ?? null ) );
        };

        ( new Cooked_Recipe_Meta() )->save_recipe_meta_box( 902 );

        $this->assertArrayNotHasKey( 902, $GLOBALS['_cooked_test_post_meta'] );
        unset( $GLOBALS['_cooked_test_current_user_can'], $_POST['cooked_recipe_custom_box_nonce'], $_POST['_recipe_settings'] );
    }
}
