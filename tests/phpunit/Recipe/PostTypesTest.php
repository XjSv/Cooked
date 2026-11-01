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
        unset( $_GET['print'], $GLOBALS['_cooked_settings'], $GLOBALS['_cooked_test_current_user_can'] );
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

    /**
     * The REST block also covers routes in another letter case, which WordPress still
     * dispatches to the recipe controller.
     */
    public function test_rest_block_ignores_route_case() {
        $GLOBALS['_cooked_settings']['advanced']  = [ 'disable_public_recipes' ];
        $GLOBALS['_cooked_test_current_user_can'] = function () {
            return false;
        };

        $post_types = new ReflectionClass( 'Cooked_Post_Types' );
        $instance   = $post_types->newInstanceWithoutConstructor();

        foreach ( [ '/wp/v2/cooked_recipe', '/wp/v2/Cooked_Recipe', '/wp/v2/COOKED_RECIPE/12' ] as $route ) {
            $request = new class( $route ) {
                private $route;
                public function __construct( $route ) {
                    $this->route = $route;
                }
                public function get_route() {
                    return $this->route;
                }
            };

            $this->assertInstanceOf( WP_Error::class, $instance->restrict_recipe_rest( null, null, $request ), $route );
        }

        $other = new class() {
            public function get_route() {
                return '/wp/v2/posts';
            }
        };
        $this->assertNull( $instance->restrict_recipe_rest( null, null, $other ) );
    }
}
