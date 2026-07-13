<?php

use PHPUnit\Framework\TestCase;

class RecipeMetaTest extends TestCase {

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
}
