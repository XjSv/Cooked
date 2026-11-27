<?php

class SettingsCacheTest extends FilterTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->reset_settings_cache();
        $GLOBALS['_cooked_test_blog_id'] = 1;
    }

    protected function tearDown(): void {
        $this->reset_settings_cache();
        $GLOBALS['_cooked_test_blog_id'] = 1;
        parent::tearDown();
    }

    public function test_get_rebuilds_tabs_fields_before_the_cache_opens() {
        $calls = 0;
        add_filter( 'cooked_settings_tabs_fields', function ( $tabs ) use ( &$calls ) {
            $calls++;
            return $tabs;
        } );

        Cooked_Settings::get();
        Cooked_Settings::get();

        $this->assertSame( 2, $calls );
    }

    public function test_get_builds_tabs_fields_once_after_init() {
        $calls = 0;
        add_filter( 'cooked_settings_tabs_fields', function ( $tabs ) use ( &$calls ) {
            $calls++;
            return $tabs;
        } );

        new Cooked_Settings();
        do_action( 'init' );
        $calls = 0;

        Cooked_Settings::get();
        Cooked_Settings::get();
        Cooked_Settings::get();

        $this->assertSame( 1, $calls );
    }

    public function test_update_option_flushes_cached_settings() {
        new Cooked_Settings();
        do_action( 'init' );

        update_option( 'cooked_settings', [ 'browse_page' => 4 ] );
        $first = Cooked_Settings::get();
        $GLOBALS['_cooked_settings'] = $first;

        update_option( 'cooked_settings', [ 'browse_page' => 9 ] );

        $this->assertSame( 4, $GLOBALS['_cooked_settings']['browse_page'] );
        $this->assertSame( 9, Cooked_Settings::get()['browse_page'] );
    }

    public function test_settings_save_redirect_keeps_the_values_the_form_renders() {
        new Cooked_Settings();
        do_action( 'init' );

        update_option( 'cooked_settings', [
            'browse_page' => 15,
            'main_color'  => '#112233',
        ] );

        Cooked_Settings::init();

        $GLOBALS['_cooked_test_is_admin'] = true;
        $_GET['settings-updated']         = 'true';
        $_GET['page']                     = 'cooked_settings';

        Cooked_Post_Types::init();

        $this->assertSame( 15, $GLOBALS['_cooked_settings']['browse_page'] );
        $this->assertSame( '#112233', $GLOBALS['_cooked_settings']['main_color'] );
        $this->assertSame( '#112233', get_option( 'cooked_settings' )['main_color'] );
        $this->assertSame( 15, Cooked_Settings::get()['browse_page'] );
    }

    public function test_delete_option_flushes_cached_settings() {
        new Cooked_Settings();
        do_action( 'init' );

        update_option( 'cooked_settings', [ 'browse_page' => 4 ] );
        Cooked_Settings::get();

        delete_option( 'cooked_settings' );

        $this->assertSame( 0, Cooked_Settings::get()['browse_page'] );
    }

    public function test_cooked_get_settings_filter_applies_after_the_cache_is_built() {
        new Cooked_Settings();
        do_action( 'init' );

        Cooked_Settings::get();

        $callback = function ( $settings ) {
            $settings['from_filter'] = 'kept';
            return $settings;
        };
        add_filter( 'cooked_get_settings', $callback );

        $this->assertSame( 'kept', Cooked_Settings::get()['from_filter'] );

        remove_filter( 'cooked_get_settings', $callback );

        $this->assertArrayNotHasKey( 'from_filter', Cooked_Settings::get() );
    }

    public function test_prefix_dark_mode_css_uses_a_passed_scope() {
        $this->assertSame( '.cooked-fsm', Cooked_Settings::prefix_dark_mode_css( '.cooked-fsm', '' ) );
        $this->assertFalse( Cooked_Settings::prefix_dark_mode_css( '.cooked-fsm', false ) );
        $this->assertSame(
            '[data-theme="dark"] .cooked-fsm, [data-theme="dark"] .cooked-recipe-card',
            Cooked_Settings::prefix_dark_mode_css( '.cooked-fsm, .cooked-recipe-card', '[data-theme="dark"]' )
        );
    }

    public function test_cache_is_keyed_by_blog_id() {
        new Cooked_Settings();
        do_action( 'init' );

        $GLOBALS['_cooked_test_blog_id'] = 1;
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [ 'browse_page' => 11 ];
        $first = Cooked_Settings::get();

        $GLOBALS['_cooked_test_blog_id'] = 2;
        $GLOBALS['_cooked_test_options']['cooked_settings'] = [ 'browse_page' => 22 ];
        $second = Cooked_Settings::get();

        $GLOBALS['_cooked_test_blog_id'] = 1;
        $again = Cooked_Settings::get();

        $this->assertSame( 11, $first['browse_page'] );
        $this->assertSame( 22, $second['browse_page'] );
        $this->assertSame( 11, $again['browse_page'] );
    }

    public function test_pages_array_reuses_pages_per_blog_until_flush() {
        $GLOBALS['_cooked_test_get_posts'] = [
            (object) [ 'ID' => 5, 'post_title' => 'Browse' ],
        ];

        $first = Cooked_Settings::pages_array( 'Choose a page...', 'No pages' );
        $GLOBALS['_cooked_test_get_posts'] = [
            (object) [ 'ID' => 9, 'post_title' => 'Other' ],
        ];
        $second = Cooked_Settings::pages_array( 'Pick a page...', 'None' );

        $this->assertSame( 'Choose a page...', $first[0] );
        $this->assertSame( 'Browse (ID:5)', $first[5] );
        $this->assertSame( 'Pick a page...', $second[0] );
        $this->assertSame( 'Browse (ID:5)', $second[5] );
        $this->assertArrayNotHasKey( 9, $second );

        $GLOBALS['_cooked_test_blog_id'] = 2;
        $other_blog = Cooked_Settings::pages_array( 'Choose a page...', 'No pages' );
        $this->assertSame( 'Other (ID:9)', $other_blog[9] );

        $GLOBALS['_cooked_test_blog_id'] = 1;
        Cooked_Settings::flush_cache();
        $GLOBALS['_cooked_test_get_posts'] = [
            (object) [ 'ID' => 7, 'post_title' => 'Fresh' ],
        ];
        $after_flush = Cooked_Settings::pages_array( 'Choose a page...', 'No pages' );
        $this->assertSame( 'Fresh (ID:7)', $after_flush[7] );
    }

    private function reset_settings_cache() {
        $ready = new ReflectionProperty( Cooked_Settings::class, 'ready' );
        $cache = new ReflectionProperty( Cooked_Settings::class, 'cache' );
        $pages = new ReflectionProperty( Cooked_Settings::class, 'pages' );

        if ( PHP_VERSION_ID < 80100 ) {
            $ready->setAccessible( true );
            $cache->setAccessible( true );
            $pages->setAccessible( true );
        }

        $ready->setValue( null, false );
        $cache->setValue( null, [] );
        $pages->setValue( null, [] );
    }
}
