<?php

class SettingsFiltersTest extends FilterTestCase {

    public function test_cooked_get_settings_can_add_a_key() {
        $settings = $this->with_filter(
            'cooked_get_settings',
            function ( $value ) {
                $value['filter_sentinel'] = 'yes';
                return $value;
            },
            function () {
                return Cooked_Settings::get();
            }
        );

        $this->assertSame( 'yes', $settings['filter_sentinel'] );
    }

    /**
     * @dataProvider settings_option_filter_provider
     */
    public function test_settings_option_filters_change_tabs_fields( $hook, $path, $mode ) {
        $result = $this->with_filter(
            $hook,
            function ( $value ) use ( $mode ) {
                if ( $mode === 'string' ) {
                    return 'FILTERED DESC';
                }
                if ( $mode === 'defaults' ) {
                    $value[] = 'sentinel_default';
                    return $value;
                }
                $value['sentinel'] = 'Sentinel Option';
                return $value;
            },
            function () {
                return Cooked_Settings::tabs_fields();
            }
        );

        $current = $result;
        foreach ( $path as $segment ) {
            $this->assertArrayHasKey( $segment, $current );
            $current = $current[ $segment ];
        }

        if ( $mode === 'string' ) {
            $this->assertSame( 'FILTERED DESC', $current );
        } elseif ( $mode === 'defaults' ) {
            $this->assertContains( 'sentinel_default', $current );
        } else {
            $this->assertArrayHasKey( 'sentinel', $current );
            $this->assertSame( 'Sentinel Option', $current['sentinel'] );
        }
    }

    public function settings_option_filter_provider() {
        return [
            'cooked_taxonomy_options' => [
                'cooked_taxonomy_options',
                [ 'recipe_settings', 'fields', 'recipe_taxonomies', 'options' ],
                'options',
            ],
            'cooked_recipe_info_display_options' => [
                'cooked_recipe_info_display_options',
                [ 'recipe_settings', 'fields', 'recipe_info_display_options', 'options' ],
                'options',
            ],
            'cooked_recipe_info_display_options_defaults' => [
                'cooked_recipe_info_display_options_defaults',
                [ 'recipe_settings', 'fields', 'recipe_info_display_options', 'default' ],
                'defaults',
            ],
            'cooked_print_view_display_options' => [
                'cooked_print_view_display_options',
                [ 'recipe_settings', 'fields', 'print_view_display_options', 'options' ],
                'options',
            ],
            'cooked_settings_carb_formats' => [
                'cooked_settings_carb_formats',
                [ 'recipe_settings', 'fields', 'carb_format', 'options' ],
                'options',
            ],
            'cooked_settings_author_formats' => [
                'cooked_settings_author_formats',
                [ 'recipe_settings', 'fields', 'author_name_format', 'options' ],
                'options',
            ],
            'cooked_author_link_options' => [
                'cooked_author_link_options',
                [ 'recipe_settings', 'fields', 'disable_author_links', 'options' ],
                'options',
            ],
            'cooked_settings_sort_options' => [
                'cooked_settings_sort_options',
                [ 'recipe_settings', 'fields', 'browse_default_sort', 'options' ],
                'options',
            ],
            'cooked_settings_section_heading_default_html_tag_options' => [
                'cooked_settings_section_heading_default_html_tag_options',
                [ 'recipe_settings', 'fields', 'section_heading_default_html_tag', 'options' ],
                'options',
            ],
            'cooked_recipe_wp_editor_roles_defaults' => [
                'cooked_recipe_wp_editor_roles_defaults',
                [ 'recipe_settings', 'fields', 'recipe_wp_editor_roles', 'default' ],
                'defaults',
            ],
            'cooked_advanced_options' => [
                'cooked_advanced_options',
                [ 'recipe_settings', 'fields', 'advanced', 'options' ],
                'options',
            ],
            'cooked_dark_mode_options' => [
                'cooked_dark_mode_options',
                [ 'design', 'fields', 'dark_mode', 'options' ],
                'options',
            ],
            'cooked_dark_mode_field_desc' => [
                'cooked_dark_mode_field_desc',
                [ 'design', 'fields', 'dark_mode', 'desc' ],
                'string',
            ],
            'cooked_author_image_options' => [
                'cooked_author_image_options',
                [ 'design', 'fields', 'hide_author_avatars', 'options' ],
                'options',
            ],
        ];
    }

    public function test_cooked_settings_tabs_fields_can_add_a_tab() {
        $tabs = $this->with_filter(
            'cooked_settings_tabs_fields',
            function ( $tabs ) {
                $tabs['sentinel_tab'] = [
                    'name'   => 'Sentinel',
                    'icon'   => 'star',
                    'fields' => [],
                ];
                return $tabs;
            },
            function () {
                return Cooked_Settings::tabs_fields();
            }
        );

        $this->assertArrayHasKey( 'sentinel_tab', $tabs );
        $this->assertSame( 'Sentinel', $tabs['sentinel_tab']['name'] );
    }

    public function test_cooked_per_page_options_can_add_an_option() {
        $options = $this->with_filter(
            'cooked_per_page_options',
            function ( $value ) {
                $value['99'] = 'Ninety Nine';
                return $value;
            },
            function () {
                return Cooked_Settings::per_page_array();
            }
        );

        $this->assertSame( 'Ninety Nine', $options['99'] );
    }

    public function test_cooked_settings_pages_array_can_add_a_page() {
        $pages = $this->with_filter(
            'cooked_settings_pages_array',
            function ( $value ) {
                $value[42] = 'Filter Page (ID:42)';
                return $value;
            },
            function () {
                return Cooked_Settings::pages_array( 'Choose a page...' );
            }
        );

        $this->assertSame( 'Filter Page (ID:42)', $pages[42] );
    }

    public function test_cooked_settings_term_array_can_add_a_term() {
        $terms = $this->with_filter(
            'cooked_settings_cp_recipe_category_array',
            function ( $value ) {
                $value[7] = 'Filtered Category';
                return $value;
            },
            function () {
                return Cooked_Settings::terms_array( 'cp_recipe_category', 'Choose...' );
            }
        );

        $this->assertSame( 'Filtered Category', $terms[7] );
    }
}
