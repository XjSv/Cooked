<?php

use PHPUnit\Framework\TestCase;

class AjaxTest extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['_cooked_test_posts'], $GLOBALS['_cooked_test_current_user_can'], $GLOBALS['_cooked_test_logged_in'] );
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

    public function test_csv_import_transient_key_accepts_this_users_key() {
        $this->assertTrue( self::call_private_method( 'is_csv_import_transient_key', [ 'cooked_csv_import_4_1710000000', 4 ] ) );
    }

    public function test_csv_import_transient_key_rejects_foreign_and_malformed_keys() {
        $this->assertFalse( self::call_private_method( 'is_csv_import_transient_key', [ 'some_other_plugin_cached_path', 4 ] ) );
        $this->assertFalse( self::call_private_method( 'is_csv_import_transient_key', [ 'cooked_csv_import_4_', 4 ] ) );
        $this->assertFalse( self::call_private_method( 'is_csv_import_transient_key', [ 'cooked_csv_import_9_1710000000', 4 ] ) );
        $this->assertFalse( self::call_private_method( 'is_csv_import_transient_key', [ 'cooked_csv_import_4_1710000000abc', 4 ] ) );
    }

    public function test_csv_import_file_path_allows_temp_file_with_cooked_prefix() {
        $temp_dir = sys_get_temp_dir();
        $path = rtrim( $temp_dir, '/\\' ) . '/cooked-csv-phpunit-' . uniqid() . '.tmp';
        file_put_contents( $path, 'title' );

        try {
            $allowed = self::call_private_method( 'csv_import_file_path', [ $path, $temp_dir ] );
            $this->assertSame( str_replace( '\\', '/', realpath( $path ) ), $allowed );
        } finally {
            if ( file_exists( $path ) ) {
                unlink( $path );
            }
        }
    }

    public function test_csv_import_file_path_rejects_uploads_and_other_prefixes() {
        $temp_dir = sys_get_temp_dir();
        $other = rtrim( $temp_dir, '/\\' ) . '/other-prefix-' . uniqid() . '.tmp';
        file_put_contents( $other, 'title' );

        try {
            $this->assertFalse( self::call_private_method( 'csv_import_file_path', [ '/cooked-uploads-basedir/import.csv', $temp_dir ] ) );
            $this->assertFalse( self::call_private_method( 'csv_import_file_path', [ $other, $temp_dir ] ) );
            $this->assertFalse( self::call_private_method( 'csv_import_file_path', [ [ 'not-a-string' ], $temp_dir ] ) );
        } finally {
            if ( file_exists( $other ) ) {
                unlink( $other );
            }
        }
    }

    public function test_bulk_ingredient_parse_allows_logged_in_users_and_not_anonymous_without_guest_mode() {
        $this->assertFalse( self::call_private_method( 'current_user_can_parse_bulk_ingredients' ) );

        $GLOBALS['_cooked_test_logged_in'] = true;
        $this->assertTrue( self::call_private_method( 'current_user_can_parse_bulk_ingredients' ) );
    }

    public function test_bulk_ingredient_lines_cap_count_and_length() {
        $long = str_repeat( 'a', 600 );
        $limited = self::call_private_method( 'limit_bulk_ingredient_lines', [ [ $long ] ] );

        $this->assertSame( 500, strlen( $limited[0] ) );
        $this->assertFalse( self::call_private_method( 'limit_bulk_ingredient_lines', [ array_fill( 0, 301, '1 cup flour' ) ] ) );
    }
}
