<?php

class UninstallTest extends FilterTestCase {

    private $temp_dirs = [];

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wpdb']->queries = [];
        $GLOBALS['_cooked_test_deleted_posts'] = [];
        $GLOBALS['_cooked_test_deleted_metadata'] = [];
        $GLOBALS['_cooked_test_get_posts'] = [];
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', 'cooked/cooked.php' );
        }
    }

    protected function tearDown(): void {
        unset( $GLOBALS['_cooked_test_upload_dir'] );
        foreach ( $this->temp_dirs as $dir ) {
            $this->remove_dir( $dir );
        }
        parent::tearDown();
    }

    public function test_advanced_settings_include_delete_cooked_data_option() {
        $tabs    = Cooked_Settings::tabs_fields();
        $options = $tabs['recipe_settings']['fields']['advanced']['options'];

        $this->assertArrayHasKey( 'delete_cooked_data_on_uninstall', $options );
        $this->assertStringContainsString( 'Delete Cooked Data on Uninstall', $options['delete_cooked_data_on_uninstall'] );
    }

    public function test_uninstall_keeps_data_unless_asked() {
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'advanced' => [ 'disable_public_recipes' ],
        ];
        $GLOBALS['_cooked_test_options']['cooked_pro_license_status'] = 'valid';
        $GLOBALS['_cooked_test_get_posts'] = [ 42 ];
        $this->use_temp_upload();
        $upload = $GLOBALS['_cooked_test_upload_dir']['basedir'];
        mkdir( $upload . '/cooked/images', 0777, true );
        file_put_contents( $upload . '/cooked/images/photo.jpg', 'img' );

        include COOKED_DIR . 'uninstall.php';

        $this->assertArrayHasKey( 'cooked_settings', $GLOBALS['_cooked_test_options'] );
        $this->assertSame( 'valid', $GLOBALS['_cooked_test_options']['cooked_pro_license_status'] );
        $this->assertSame( [], $GLOBALS['_cooked_test_deleted_posts'] );
        $this->assertSame( [], $GLOBALS['_cooked_test_deleted_metadata'] );
        $this->assertFileExists( $upload . '/cooked/images/photo.jpg' );
        $this->assertGreaterThan( 0, $GLOBALS['_cooked_test_flush_rewrite_count'] );
    }

    public function test_uninstall_deletes_cooked_data_when_asked() {
        $GLOBALS['_cooked_test_options']['blogname'] = 'Mimis';
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [
            'advanced' => [ 'delete_cooked_data_on_uninstall' ],
        ];
        $GLOBALS['_cooked_test_options']['cooked_pro_license_status'] = 'valid';
        $GLOBALS['_cooked_test_get_posts'] = [ 42, 43 ];
        $this->use_temp_upload();
        $upload = $GLOBALS['_cooked_test_upload_dir']['basedir'];
        mkdir( $upload . '/cooked/images/recipes', 0777, true );
        file_put_contents( $upload . '/cooked/images/recipes/photo.jpg', 'img' );

        include COOKED_DIR . 'uninstall.php';

        $this->assertArrayNotHasKey( 'cooked_settings', $GLOBALS['_cooked_test_options'] );
        $this->assertArrayNotHasKey( 'cooked_pro_license_status', $GLOBALS['_cooked_test_options'] );
        $this->assertSame( 'Mimis', $GLOBALS['_cooked_test_options']['blogname'] );
        $this->assertSame( [ 42, 43 ], $GLOBALS['_cooked_test_deleted_posts'] );
        $this->assertFileDoesNotExist( $upload . '/cooked/images/recipes/photo.jpg' );
        $this->assertDirectoryDoesNotExist( $upload . '/cooked' );

        $sql = implode( "\n", $GLOBALS['wpdb']->queries );
        $this->assertStringContainsString( "'cp_recipe_category'", $sql );
        $this->assertStringContainsString( "'cp_recipe_cuisine'", $sql );
        $this->assertStringContainsString( "'cp_recipe_cooking_method'", $sql );
        $this->assertStringContainsString( "'cp_recipe_diet'", $sql );
        $this->assertStringContainsString( "'cp_recipe_tags'", $sql );

        $deleted = $GLOBALS['_cooked_test_deleted_metadata'][0];
        $this->assertSame( 'user', $deleted['meta_type'] );
        $this->assertSame( 'cooked_user_meta', $deleted['meta_key'] );
        $this->assertTrue( $deleted['delete_all'] );
    }

    private function use_temp_upload() {
        $dir = sys_get_temp_dir() . '/cooked-uninstall-' . uniqid( '', true );
        mkdir( $dir, 0777, true );
        $this->temp_dirs[] = $dir;
        $GLOBALS['_cooked_test_upload_dir'] = [
            'basedir' => $dir,
            'baseurl' => 'http://example.com/uploads',
            'error'   => false,
        ];
    }

    private function remove_dir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $files as $file ) {
            if ( $file->isDir() ) {
                rmdir( $file->getRealPath() );
            } else {
                unlink( $file->getRealPath() );
            }
        }
        rmdir( $dir );
    }
}
