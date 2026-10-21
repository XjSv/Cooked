<?php
/**
 * Tests for the deprecated placeholder files left in includes/ by the 2.0 move to src/.
 *
 * @package Cooked
 * @subpackage Tests
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeprecatedFilesTest extends TestCase {

    public static function placeholder_provider() {
        $placeholders = [];

        foreach ( glob( COOKED_DIR . 'includes/*.php' ) as $file ) {
            $code = file_get_contents( $file );
            if ( ! preg_match( "/_deprecated_file\( basename\( __FILE__ \), '([^']+)', '([^']+)' \);/", $code, $call ) ) {
                continue;
            }
            preg_match( '/^ \* (\w+) moved to /m', $code, $old );
            $placeholders[ basename( $file ) ] = [ $file, $call[1], $call[2], $old[1] ?? '' ];
        }

        return $placeholders;
    }

    public function test_placeholders_exist() {
        $this->assertNotEmpty( self::placeholder_provider() );
    }

    #[DataProvider( 'placeholder_provider' )]
    public function test_placeholder_reports_deprecation_and_old_name_still_works( $file, $version, $replacement, $old_class ) {
        $GLOBALS['_cooked_test_deprecated_files'] = [];

        require $file;

        $this->assertSame( [ [ basename( $file ), '2.0.0', $replacement ] ], $GLOBALS['_cooked_test_deprecated_files'] );
        $this->assertSame( '2.0.0', $version );
        $this->assertTrue( class_exists( $replacement ), "$replacement does not exist" );
        $this->assertTrue( class_exists( $old_class ), "$old_class is not aliased" );
        $this->assertSame( strtolower( $replacement ), strtolower( ( new ReflectionClass( $old_class ) )->getName() ) );
    }
}
