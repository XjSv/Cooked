<?php

use PHPUnit\Framework\TestCase;

class FilterTestCase extends TestCase {

    private $ob_level;

    protected function setUp(): void {
        parent::setUp();
        $this->ob_level = ob_get_level();
        $this->reset_filter_test_state();
    }

    protected function tearDown(): void {
        $this->reset_filter_test_state();
        while ( ob_get_level() > $this->ob_level ) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    protected function reset_filter_test_state() {
        $GLOBALS['_cooked_test_filters'] = [];
        $GLOBALS['_cooked_test_actions'] = [];
        $GLOBALS['_cooked_test_post_meta'] = [];
        $GLOBALS['_cooked_test_posts'] = [];
        $GLOBALS['_cooked_test_titles'] = [];
        $GLOBALS['_cooked_test_query_vars'] = [];
        $GLOBALS['_cooked_test_query_posts'] = [];
        $GLOBALS['_cooked_test_terms'] = [];
        $GLOBALS['_cooked_test_updated_posts'] = [];
        $GLOBALS['_cooked_test_inserted_posts'] = [];
        $GLOBALS['_cooked_test_attachment_image_calls'] = [];
        $GLOBALS['_cooked_test_localized'] = [];
        $GLOBALS['_cooked_test_inline_scripts'] = [];
        $GLOBALS['_cooked_test_registered_post_types'] = [];
        $GLOBALS['_cooked_test_registered_widgets'] = [];
        $GLOBALS['_cooked_test_registered_roles'] = [];
        $GLOBALS['_cooked_test_registered_taxonomies'] = [];
        $GLOBALS['_cooked_test_meta_boxes'] = [];
        $GLOBALS['_cooked_test_paginate_args'] = null;
        $GLOBALS['_cooked_test_last_query'] = [];
        $GLOBALS['_cooked_test_object_terms'] = [];
        $GLOBALS['_cooked_test_max_num_pages'] = null;
        $GLOBALS['_cooked_test_options'] = [];
        $_GET  = [];
        $_POST = [];
        unset( $GLOBALS['_cooked_test_attachment_image_html'] );
        unset( $GLOBALS['_cooked_test_is_admin'] );
        unset( $GLOBALS['_cooked_test_is_singular'] );
        unset( $GLOBALS['_cooked_test_is_feed'] );
        unset( $GLOBALS['_cooked_test_is_page'] );
        unset( $GLOBALS['_cooked_test_logged_in'] );
        unset( $GLOBALS['cooked_modified_where'] );
        $GLOBALS['_cooked_settings'] = [];
    }

    protected function with_filter( $tag, $callback, $run, $accepted_args = 10 ) {
        add_filter( $tag, $callback, 10, $accepted_args );
        try {
            return $run();
        } finally {
            remove_filter( $tag, $callback );
        }
    }

    protected function capture_output( $run ) {
        ob_start();
        try {
            $run();
            return ob_get_clean();
        } catch ( \Throwable $e ) {
            ob_end_clean();
            throw $e;
        }
    }

    protected function list_view_atts( $overrides = [] ) {
        return array_merge(
            [
                'category'      => false,
                'order'         => false,
                'orderby'       => false,
                'show'          => 9,
                'search'        => 'true',
                'pagination'    => 'true',
                'columns'       => 3,
                'layout'        => false,
                'author'        => false,
                'compact'       => false,
                'hide_browse'   => false,
                'hide_sorting'  => false,
                'exclude'       => false,
                'inline_browse' => false,
                'hide_excerpt'  => false,
            ],
            $overrides
        );
    }
}
