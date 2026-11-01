<?php
/**
 * Tests for Cooked_Post_Types visibility flags.
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

class PostTypesTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        unset( $GLOBALS['_cooked_test_filters']['cooked_post_types'] );
    }

    protected function tearDown(): void {
        unset( $_GET['print'], $GLOBALS['_cooked_settings'] );
        parent::tearDown();
    }

    /**
     * A print query must not publish recipes that are disabled.
     */
    public function test_print_query_keeps_recipes_non_public() {
        $GLOBALS['_cooked_settings']['advanced'] = [ 'disable_public_recipes' ];
        $_GET['print'] = '1';

        $types = Cooked_Post_Types::get();

        $this->assertFalse( $types['cp_recipe']['public'] );
        $this->assertTrue( $types['cp_recipe']['show_in_rest'] );
    }

    /**
     * Recipes stay public when the setting is absent.
     */
    public function test_recipes_are_public_when_setting_is_absent() {
        unset( $GLOBALS['_cooked_settings'] );

        $types = Cooked_Post_Types::get();

        $this->assertTrue( $types['cp_recipe']['public'] );
        $this->assertTrue( $types['cp_recipe']['show_in_rest'] );
    }
}
