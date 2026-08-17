<?php

use PHPUnit\Framework\TestCase;

class RecipeMetaTest extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['_cooked_test_logged_in'] );
        unset( $GLOBALS['_cooked_settings'] );
        parent::tearDown();
    }

    public function test_meta_cleanup_empty_input() {
        $result = Cooked_Recipe_Meta::meta_cleanup([]);
        $this->assertSame([], $result);
    }

    public function test_meta_cleanup_simple_field() {
        $input = ['title' => 'My Recipe'];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertArrayHasKey('title', $result);
    }

    public function test_meta_cleanup_strips_tags_from_non_content() {
        $input = ['title' => '<script>alert("xss")</script>My Recipe'];
        $result = Cooked_Recipe_Meta::meta_cleanup($input);
        $this->assertStringNotContainsString('<script>', $result['title']);
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
            'prep_time' => 'a & b',
        ] );

        $this->assertSame( 'a &amp; b', $result['prep_time'] );
        $this->assertStringNotContainsString( '&amp;amp;', $result['prep_time'] );
    }

    public function test_meta_cleanup_twice_double_encodes_plain_fields() {
        $once = Cooked_Recipe_Meta::meta_cleanup( [
            'prep_time' => 'a & b',
        ] );
        $twice = Cooked_Recipe_Meta::meta_cleanup( $once );

        $this->assertSame( 'a &amp;amp; b', $twice['prep_time'] );
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
}
