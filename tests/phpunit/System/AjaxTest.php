<?php

use PHPUnit\Framework\TestCase;

class AjaxTest extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['_cooked_test_posts'], $GLOBALS['_cooked_test_current_user_can'] );
        parent::tearDown();
    }

    private static function call_private_method( $name, $args = [] ) {
        $ref = new ReflectionMethod( Cooked_Ajax::class, $name );
        $ref->setAccessible( true );
        return $ref->invoke( null, ...$args );
    }

    public function test_recipe_ids_from_json_returns_empty_for_invalid() {
        $this->assertSame( [], self::call_private_method( 'recipe_ids_from_json', [ '' ] ) );
        $this->assertSame( [], self::call_private_method( 'recipe_ids_from_json', [ 'not-json' ] ) );
        $this->assertSame( [], self::call_private_method( 'recipe_ids_from_json', [ '{}' ] ) );
        $this->assertSame( [], self::call_private_method( 'recipe_ids_from_json', [ '[]' ] ) );
    }

    public function test_recipe_ids_from_json_absint_and_drops_zero() {
        $this->assertSame(
            [ 11, 12 ],
            self::call_private_method( 'recipe_ids_from_json', [ '[11, 0, "12", "abc"]' ] )
        );
    }

    public function test_user_can_edit_post_of_type_allows_matching_recipe() {
        $this->assertTrue( self::call_private_method( 'user_can_edit_post_of_type', [ 5, 'cp_recipe' ] ) );
    }

    public function test_user_can_edit_post_of_type_rejects_page() {
        $GLOBALS['_cooked_test_posts'][11] = (object) [
            'ID' => 11,
            'post_type' => 'page',
            'post_title' => 'Victim Page',
            'post_excerpt' => 'excerpt',
            'post_author' => 1,
            'post_status' => 'publish',
            'post_name' => 'victim-page',
            'post_content' => 'body',
        ];

        $this->assertFalse( self::call_private_method( 'user_can_edit_post_of_type', [ 11, 'cp_recipe' ] ) );
    }

    public function test_user_can_edit_post_of_type_rejects_wrong_import_source() {
        $GLOBALS['_cooked_test_posts'][22] = (object) [
            'ID' => 22,
            'post_type' => 'page',
            'post_title' => 'Private Page',
            'post_excerpt' => 'secret',
            'post_author' => 1,
            'post_status' => 'private',
            'post_name' => 'private-page',
            'post_content' => 'secret body',
        ];

        $this->assertFalse( self::call_private_method( 'user_can_edit_post_of_type', [ 22, 'recipe' ] ) );
        $this->assertFalse( self::call_private_method( 'user_can_edit_post_of_type', [ 22, 'wprm_recipe' ] ) );
    }

    public function test_user_can_edit_post_of_type_allows_matching_import_source() {
        $GLOBALS['_cooked_test_posts'][33] = (object) [
            'ID' => 33,
            'post_type' => 'recipe',
            'post_title' => 'Delicious Recipe',
            'post_excerpt' => '',
            'post_author' => 1,
            'post_status' => 'publish',
            'post_name' => 'delicious-recipe',
            'post_content' => '',
        ];

        $this->assertTrue( self::call_private_method( 'user_can_edit_post_of_type', [ 33, 'recipe' ] ) );
    }

    public function test_user_can_edit_post_of_type_rejects_when_cannot_edit_post() {
        $GLOBALS['_cooked_test_current_user_can'] = function( $capability, ...$args ) {
            if ( 'edit_post' === $capability ) {
                return false;
            }
            return true;
        };

        $this->assertFalse( self::call_private_method( 'user_can_edit_post_of_type', [ 5, 'cp_recipe' ] ) );
    }

    public function test_user_can_edit_post_of_type_rejects_empty_type() {
        $this->assertFalse( self::call_private_method( 'user_can_edit_post_of_type', [ 5, '' ] ) );
    }
}
