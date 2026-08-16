<?php

class UpdatesTest extends FilterTestCase {

    public function test_get_runnable_tools_returns_array() {
        $tools = Cooked_Updates::get_runnable_tools();
        $this->assertIsArray($tools);
    }

    public function test_get_runnable_tools_has_tools() {
        $tools = Cooked_Updates::get_runnable_tools();
        $this->assertNotEmpty($tools);
    }

    public function test_get_runnable_tools_tool_has_required_keys() {
        $tools = Cooked_Updates::get_runnable_tools();
        foreach ($tools as $tool) {
            $this->assertArrayHasKey('id', $tool);
            $this->assertArrayHasKey('name', $tool);
        }
    }

    public function test_run_tool_returns_error_for_empty_tool_name() {
        $result = Cooked_Updates::run_tool('');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_run_tool_returns_error_for_unknown_tool() {
        $result = Cooked_Updates::run_tool('nonexistent_tool');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_version_bump_flushes_rewrite_rules() {
        $GLOBALS['_cooked_test_options']['cooked_settings_saved']   = true;
        $GLOBALS['_cooked_test_options']['cooked_settings_version'] = '1.15.0';

        Cooked_Updates::init();

        $this->assertSame( 1, $GLOBALS['_cooked_test_flush_rewrite_count'] );
        $this->assertContains( false, $GLOBALS['_cooked_test_flush_rewrite_hard'] );
        $this->assertSame( COOKED_VERSION, get_option( 'cooked_settings_version' ) );
    }

    public function test_matching_version_does_not_flush_rewrite_rules() {
        $GLOBALS['_cooked_test_options']['cooked_settings_saved']   = true;
        $GLOBALS['_cooked_test_options']['cooked_settings_version'] = COOKED_VERSION;

        Cooked_Updates::init();

        $this->assertSame( 0, $GLOBALS['_cooked_test_flush_rewrite_count'] );
    }

    public function test_missing_browse_rewrite_rules_queue_a_flush() {
        $GLOBALS['_cooked_settings'] = [ 'browse_page' => 66 ];
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [
            'foo/([^/]+)/?$' => 'index.php?name=$matches[1]',
        ];

        $this->assertTrue( Cooked_Updates::browse_rewrite_rules_missing() );

        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertSame( '1', get_option( 'cooked_flush_rewrite_rules' ) );

        Cooked_Updates::maybe_flush_queued_rewrite_rules();
        $this->assertSame( 1, $GLOBALS['_cooked_test_flush_rewrite_count'] );
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_present_browse_rewrite_rules_do_not_queue_a_flush() {
        $GLOBALS['_cooked_settings'] = [ 'browse_page' => 66 ];
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [
            'recipes/recipe-category/([^/]*)/?' => 'index.php?page_id=66&cp_recipe_category=$matches[1]',
        ];

        $this->assertFalse( Cooked_Updates::browse_rewrite_rules_missing() );

        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_plain_permalinks_do_not_queue_a_flush() {
        $GLOBALS['_cooked_settings'] = [ 'browse_page' => 66 ];
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '';

        $this->assertFalse( Cooked_Updates::browse_rewrite_rules_missing() );
        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_no_browse_page_does_not_queue_a_flush() {
        $GLOBALS['_cooked_settings'] = [];
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';

        $this->assertFalse( Cooked_Updates::browse_rewrite_rules_missing() );
    }

    public function test_core_deactivation_deletes_rewrite_rules() {
        require_once COOKED_DIR . 'includes/class.cooked-post-types.php';
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [ 'keep' => 'me' ];

        Cooked_Post_Types::deactivation();

        $this->assertFalse( get_option( 'rewrite_rules' ) );
    }
}
