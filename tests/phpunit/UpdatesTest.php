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

    public function test_missing_registered_rewrite_rules_queue_a_flush() {
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['wp_rewrite'] = (object) [
            'extra_rules_top' => [
                'recipes/recipe-category/([^/]*)/?' => 'index.php?page_id=66&cp_recipe_category=$matches[1]',
                'profile/([^/]*)/?' => 'index.php?page_id=10&cooked_author=$matches[1]',
            ],
            'extra_rules' => [],
        ];
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [
            'recipes/recipe-category/([^/]*)/?' => 'index.php?page_id=66&cp_recipe_category=$matches[1]',
        ];

        $this->assertTrue( Cooked_Updates::registered_rewrite_rules_missing() );

        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertSame( '1', get_option( 'cooked_flush_rewrite_rules' ) );

        Cooked_Updates::maybe_flush_queued_rewrite_rules();
        $this->assertSame( 1, $GLOBALS['_cooked_test_flush_rewrite_count'] );
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_present_registered_rewrite_rules_do_not_queue_a_flush() {
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['wp_rewrite'] = (object) [
            'extra_rules_top' => [
                'recipes/recipe-category/([^/]*)/?' => 'index.php?page_id=66&cp_recipe_category=$matches[1]',
                'profile/([^/]*)/?' => 'index.php?page_id=10&cooked_author=$matches[1]',
            ],
            'extra_rules' => [],
        ];
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [
            'recipes/recipe-category/([^/]*)/?' => 'index.php?page_id=66&cp_recipe_category=$matches[1]',
            'profile/([^/]*)/?' => 'index.php?page_id=10&cooked_author=$matches[1]',
        ];

        $this->assertFalse( Cooked_Updates::registered_rewrite_rules_missing() );

        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_plain_permalinks_do_not_queue_a_flush() {
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '';
        $GLOBALS['wp_rewrite'] = (object) [
            'extra_rules_top' => [
                'profile/([^/]*)/?' => 'index.php?page_id=10&cooked_author=$matches[1]',
            ],
            'extra_rules' => [],
        ];

        $this->assertFalse( Cooked_Updates::registered_rewrite_rules_missing() );
        Cooked_Updates::maybe_queue_rewrite_flush();
        $this->assertFalse( get_option( 'cooked_flush_rewrite_rules' ) );
    }

    public function test_no_registered_rewrite_rules_do_not_queue_a_flush() {
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['wp_rewrite'] = (object) [
            'extra_rules_top' => [],
            'extra_rules' => [],
        ];

        $this->assertFalse( Cooked_Updates::registered_rewrite_rules_missing() );
    }

    public function test_heal_flushes_when_registered_rewrite_rules_are_missing() {
        $GLOBALS['_cooked_test_options']['permalink_structure'] = '/%postname%/';
        $GLOBALS['wp_rewrite'] = (object) [
            'extra_rules_top' => [
                'profile/([^/]*)/?' => 'index.php?page_id=10&cooked_author=$matches[1]',
            ],
            'extra_rules' => [],
        ];
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [
            'foo/([^/]+)/?$' => 'index.php?name=$matches[1]',
        ];

        Cooked_Updates::maybe_heal_rewrite_rules();

        $this->assertSame( 1, $GLOBALS['_cooked_test_flush_rewrite_count'] );
        $this->assertContains( false, $GLOBALS['_cooked_test_flush_rewrite_hard'] );
    }

    public function test_updates_heals_rewrite_rules_late_on_init() {
        $GLOBALS['_cooked_test_actions'] = [];

        new Cooked_Updates();

        $this->assertNotEmpty( $GLOBALS['_cooked_test_actions']['init'][99] );
    }

    public function test_core_activation_flushes_rewrite_rules() {
        require_once COOKED_DIR . 'includes/class.cooked-roles.php';
        require_once COOKED_DIR . 'includes/class.cooked-taxonomies.php';
        require_once COOKED_DIR . 'includes/class.cooked-post-types.php';

        unset( $GLOBALS['wp_roles'] );

        Cooked_Post_Types::activation();

        $this->assertSame( 1, $GLOBALS['_cooked_test_flush_rewrite_count'] );
    }

    public function test_core_deactivation_deletes_rewrite_rules() {
        require_once COOKED_DIR . 'includes/class.cooked-post-types.php';
        $GLOBALS['_cooked_test_options']['rewrite_rules'] = [ 'keep' => 'me' ];

        Cooked_Post_Types::deactivation();

        $this->assertFalse( get_option( 'rewrite_rules' ) );
    }

    public function test_core_registers_activation_and_deactivation_hooks() {
        require_once COOKED_DIR . 'includes/class.cooked-post-types.php';

        $GLOBALS['_cooked_test_activation_hooks']   = [];
        $GLOBALS['_cooked_test_deactivation_hooks'] = [];

        new Cooked_Post_Types();

        $this->assertContains( COOKED_PLUGIN_FILE, $GLOBALS['_cooked_test_activation_hooks'] );
        $this->assertContains( COOKED_PLUGIN_FILE, $GLOBALS['_cooked_test_deactivation_hooks'] );
    }
}
