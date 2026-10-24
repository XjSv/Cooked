<?php
/**
 * Register Settings
 *
 * @package     Cooked
 * @subpackage  Settings
 * @since       1.0.0
 */

namespace Cooked\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Class
 *
 * This class handles the settings creation and contains functions for retreiving those settings.
 *
 * @since 1.0.0
 */
class Settings {

    /**
     * Registers the settings hooks.
     */
    public function __construct() {
        add_action( 'admin_init', [ &$this, 'init' ] );
        add_action( 'init', [ &$this, 'init' ] );
        add_action( 'save_post', [ &$this, 'browse_page_saved' ], 10, 1 );
        add_action( 'admin_notices', [ &$this, 'cooked_settings_saved_admin_notice' ] );
        add_action( 'admin_notices', [ &$this, 'browse_page_missing_notice' ] );
        // After the settings' own sanitize callback (priority 10).
        add_filter( 'sanitize_option_cooked_settings', [ 'Cooked_Settings', 'save_admin_dark_mode' ], 20 );
    }

    /**
     * Flushes rewrite rules when the Browse page, or a translation of it, is saved.
     *
     * @param int $post_id Saved post ID.
     * @return void
     */
    public function browse_page_saved( $post_id ) {
        // Just a revision, don't do anything.
        if ( wp_is_post_revision( $post_id ) ) {
			return;
        }

        $_cooked_settings    = \Cooked_Settings::get();
        $main_browse_page_id = isset( $_cooked_settings['browse_page'] ) ? $_cooked_settings['browse_page'] : false;

        // Check if this is the main browse page.
        if ( (int) $main_browse_page_id === (int) $post_id ) {
            flush_rewrite_rules( false );
            return;
        }

        // Also flush if this is a translation of the browse page.
        $browse_pages = \Cooked_Multilingual::get_all_browse_pages();
        foreach ( $browse_pages as $lang => $page_data ) {
            if ( (int) $page_data['id'] === (int) $post_id ) {
                flush_rewrite_rules( false );
                return;
            }
        }
    }

    /**
     * Registers the settings with the Settings API.
     *
     * @return void
     */
    public static function init() {
        global $_cooked_settings, $list_id_counter;

        $list_id_counter  = 0;
        $_cooked_settings = \Cooked_Settings::get();
        register_setting( 'cooked_settings_group', 'cooked_settings', [ 'sanitize_callback' => [ 'Cooked_Settings', 'sanitize_settings' ] ] );
        register_setting( 'cooked_settings_group', 'cooked_settings_saved', [ 'sanitize_callback' => [ 'Cooked_Settings', 'sanitize_saved_flag' ] ] );
    }

    /**
     * Sanitizes the "settings saved" flag.
     *
     * @param mixed $value Submitted value.
     * @return bool
     */
    public static function sanitize_saved_flag( $value ) {
        return rest_sanitize_boolean( $value );
    }

    // Add this new method to handle settings sanitization.
    /**
     * Sanitizes one submitted setting by its field type. Choice fields keep only values
     * that are real options of the field; invalid values fall back to the field default.
     *
     * @since 2.0.0
     * @param mixed                $value Submitted value.
     * @param array<string, mixed> $field Field definition from tabs_fields().
     * @return mixed
     */
    private static function sanitize_field_value( $value, $field ) {
        $options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : null;
        $default = isset( $field['default'] ) ? $field['default'] : '';

        switch ( $field['type'] ) {
            case 'checkboxes':
                $value = array_map( 'strval', (array) $value );
                return null === $options ? array_values( array_map( 'sanitize_text_field', $value ) ) : array_values(
                    array_filter(
                        $value,
                        function ( $choice ) use ( $options ) {
                            return array_key_exists( $choice, $options );
                        }
                    )
                );

            case 'dark_mode':
                return self::normalize_dark_mode( $value );

            case 'select':
            case 'radio':
                $value = is_scalar( $value ) ? (string) $value : '';
                if ( null !== $options && '' !== $value && ! array_key_exists( $value, $options ) ) {
                    return $default;
                }
                return sanitize_text_field( $value );

            case 'number_field':
                // Stored as a string, like the defaults and existing data.
                return is_numeric( $value ) ? (string) absint( $value ) : $default;

            case 'color_field':
                $color = sanitize_hex_color( is_string( $value ) ? $value : '' );
                return $color ? $color : $default;

            case 'permalink_field':
                $parts = array_filter( array_map( 'sanitize_title', explode( '/', is_string( $value ) ? $value : '' ) ) );
                return $parts ? implode( '/', $parts ) : $default;

            case 'html':
                return is_string( $value ) ? wp_kses_post( $value ) : '';

            case 'text':
            case 'password':
                return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
        }

        return $value;
    }

    /**
     * Whether a submitted value can't be kept and will fall back to the field's default: a
     * number field that isn't a number, a colour that isn't a hex colour, a choice that isn't
     * one of the field's options. (Empty values are fine: they mean "use the default".)
     *
     * @since 2.0.0
     * @param mixed                $value Submitted value.
     * @param array<string, mixed> $field Field definition from tabs_fields().
     * @return bool
     */
    public static function is_rejected_value( $value, $field ) {
        if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
            return false;
        }
        $value = trim( (string) $value );

        switch ( $field['type'] ) {
            case 'number_field':
                return ! is_numeric( $value ) || (float) $value < 0;
            case 'color_field':
                return ! sanitize_hex_color( $value );
            case 'select':
            case 'radio':
                return isset( $field['options'] ) && is_array( $field['options'] ) && ! array_key_exists( $value, $field['options'] );
        }

        return false;
    }

    /**
     * Tells the person saving that a value was not kept (shown in the Settings card after the
     * save, the row marked; templates/admin/parts/settings-fields.php). Once per field, though
     * WordPress can run the sanitize callback twice on a first save.
     *
     * @since 2.0.0
     * @param string               $field_name Setting key.
     * @param array<string, mixed> $field      Field definition.
     * @param mixed                $value      Submitted value.
     * @return void
     */
    private static function report_rejected_value( $field_name, $field, $value ) {
        static $reported = [];
        if ( isset( $reported[ $field_name ] ) ) {
            return;
        }
        $reported[ $field_name ] = true;

        add_settings_error(
            'cooked_settings_group',
            'cooked_invalid_' . $field_name,
            sprintf(
                /* translators: 1: setting title, 2: the value that was entered */
                __( '%1$s: "%2$s" is not a valid value, so the setting was set back to its default.', 'cooked' ),
                wp_strip_all_tags( isset( $field['title'] ) ? (string) $field['title'] : $field_name ),
                is_scalar( $value ) ? (string) $value : ''
            ),
            'error'
        );
    }

    /**
     * Setting keys whose submitted value was not kept on the last save (see report_rejected_value()).
     *
     * @since 2.0.0
     * @return string[]
     */
    public static function rejected_fields() {
        $fields = [];
        foreach ( get_settings_errors( 'cooked_settings_group' ) as $error ) {
            if ( isset( $error['code'] ) && 0 === strpos( (string) $error['code'], 'cooked_invalid_' ) ) {
                $fields[] = substr( (string) $error['code'], strlen( 'cooked_invalid_' ) );
            }
        }
        return $fields;
    }

    /**
     * Sanitizes the submitted settings field by field.
     *
     * @param array<string, mixed> $settings Submitted settings.
     * @return array<string, mixed>
     */
    public static function sanitize_settings( $settings ) {
        $cooked_tabs_fields = self::tabs_fields();

        // Process each field.
        foreach ( $cooked_tabs_fields as $tab ) {
            if ( isset( $tab['fields'] ) && ! empty( $tab['fields'] ) ) {
                foreach ( $tab['fields'] as $field_name => $field ) {
                    // Handle checkbox fields specifically.
                    if ( 'checkboxes' === $field['type'] ) {
                        // A missing or malformed field saves as an empty array; otherwise drop empty values.
                        if ( ! isset( $settings[ $field_name ] ) || ! is_array( $settings[ $field_name ] ) ) {
                            $settings[ $field_name ] = [];
                        } else {
                            $settings[ $field_name ] = array_filter(
                                $settings[ $field_name ],
                                function ( $value ) {
                                    return '' !== $value;
                                }
                            );
                        }
                    } elseif ( 'image_field' === $field['type'] ) {
                        $image_id = isset( $settings[ $field_name ] ) ? absint( $settings[ $field_name ] ) : 0;

                        if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
                            $image_id = 0;
                        }

                        $settings[ $field_name ] = $image_id;
                    }

                    if ( isset( $settings[ $field_name ] ) ) {
                        if ( self::is_rejected_value( $settings[ $field_name ], $field ) ) {
                            self::report_rejected_value( $field_name, $field, $settings[ $field_name ] );
                        }
                        $settings[ $field_name ] = self::sanitize_field_value( $settings[ $field_name ], $field );
                    }
                }
            }
        }

        if ( isset( $settings['dark_mode'] ) ) {
            $settings['dark_mode'] = self::normalize_dark_mode( $settings['dark_mode'] );
        }

        // Per-user preferences kept in user meta (save_admin_dark_mode()), not site settings.
        unset( $settings['admin_dark_mode'], $settings['editor_dark_mode'] );

        return $settings;
    }

    /**
     * Adds the "settings updated" notice after saving.
     *
     * @return void
     */
    public function cooked_settings_saved_admin_notice() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only Settings API query vars.
        $page             = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $settings_updated = isset( $_GET['settings-updated'] ) ? rest_sanitize_boolean( sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) : false;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( $settings_updated && 'cooked_settings' === $page ) {
            add_settings_error(
                'cooked_settings_group',
                'cooked_settings_updated',
                __( 'Cooked settings has been updated!', 'cooked' ),
                'updated'
            );
        }
    }

    /**
     * Warns, outside the Settings screen, when no Browse page is set.
     *
     * @return void
     */
    public function browse_page_missing_notice() {
        // Only show on admin pages, not on the Cooked settings page itself.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin page query var.
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'cooked_settings' === $page ) {
            return;
        }

        // Get Cooked settings.
        $_cooked_settings = self::get();

        // Check if browse page is set.
        if ( empty( $_cooked_settings['browse_page'] ) ) {
            $class   = 'notice notice-warning is-dismissible';
            $message = sprintf(
                '<strong>' . __( 'Cooked Plugin Setup', 'cooked' ) . '</strong> ' .
                /* translators: %s: Browse/Search Recipes Page link */
                __( 'To display your recipes properly, please set up your %s.', 'cooked' ),
                '<a href="' . trailingslashit( esc_url( admin_url() ) ) . 'admin.php?page=cooked_settings#recipe_settings">' . __( 'Browse/Search Recipes Page', 'cooked' ) . '</a>'
            );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
        }
    }

    /**
     * Deletes all Cooked settings.
     *
     * @return void
     */
    public static function reset() {
        global $_cooked_settings;
        $_cooked_settings = \Cooked_Settings::get();
    }

    /**
     * The saved settings, with defaults for fields that have never been saved.
     *
     * @return array<string, mixed>
     */
    public static function get() {
        $_cooked_settings = get_option( 'cooked_settings', [] );

        // Get defaults for fields that are not set yet.
        $cooked_tabs_fields = self::tabs_fields();

        if ( isset( $cooked_tabs_fields ) && ! empty( $cooked_tabs_fields ) ) {
            foreach ( $cooked_tabs_fields as $tab ) {
                if ( isset( $tab['fields'] ) && ! empty( $tab['fields'] ) ) {
                    foreach ( $tab['fields'] as $name => $field ) {
                        if ( 'nonce' === $field['type'] || 'misc_button' === $field['type'] ) {
							continue;
                        }

                        if ( 'checkboxes' === $field['type'] ) {
                            $_cooked_settings[ $name ] = isset( $_cooked_settings[ $name ] ) ? $_cooked_settings[ $name ] : ( isset( $field['default'] ) ? $field['default'] : [] );
                        } else {
                            $_cooked_settings[ $name ] = isset( $_cooked_settings[ $name ] ) ? $_cooked_settings[ $name ] : ( isset( $field['default'] ) ? $field['default'] : false );
                        }
                    }
                }
            }
        }

        // The breakpoints are fixed in the stylesheet since 2.0. Cooked Pro's templates still read them.
        $_cooked_settings['responsive_breakpoint_1'] = '1000';
        $_cooked_settings['responsive_breakpoint_2'] = '750';
        $_cooked_settings['responsive_breakpoint_3'] = '520';

        if ( isset( $_cooked_settings['dark_mode'] ) ) {
            $_cooked_settings['dark_mode'] = self::normalize_dark_mode( $_cooked_settings['dark_mode'] );
        }

        return apply_filters( 'cooked_get_settings', $_cooked_settings );
    }

    /**
     * The current user's dark mode for Cooked's admin screens: off, enabled, or auto (follow
     * the operating system's light/dark setting). A personal preference, stored per user.
     *
     * @since 2.0.0
     * @return string
     */
    public static function admin_dark_mode() {
        return self::normalize_dark_mode( get_user_meta( get_current_user_id(), 'cooked_admin_dark_mode', true ) );
    }

    /**
     * The current user's dark mode for the recipe editor (new layout): off, enabled, or auto.
     * A personal preference, stored per user, separate from the admin screens' one.
     *
     * @since 2.0.0
     * @return string
     */
    public static function editor_dark_mode() {
        return self::normalize_dark_mode( get_user_meta( get_current_user_id(), 'cooked_editor_dark_mode', true ) );
    }

    /**
     * Saves the per-user dark mode selects (Admin Dark Mode and Recipe Editor Dark Mode; user
     * preferences, not site settings) when the Settings form is submitted. Runs on
     * sanitize_option_cooked_settings; checks the form's nonce and the user's capability itself,
     * because the filter also runs when the option is updated outside the form.
     *
     * @since 2.0.0
     * @param mixed $value The sanitized cooked_settings value, returned unchanged.
     * @return mixed
     */
    public static function save_admin_dark_mode( $value ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce is verified in the condition below.
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

        if ( isset( $_POST['option_page'] ) && 'cooked_settings_group' === $_POST['option_page'] && wp_verify_nonce( $nonce, 'cooked_settings_group-options' ) && current_user_can( 'edit_cooked_settings' ) ) {
            foreach ( [ 'cooked_admin_dark_mode', 'cooked_editor_dark_mode' ] as $meta_key ) {
                if ( isset( $_POST[ $meta_key ] ) ) {
                    update_user_meta( get_current_user_id(), $meta_key, self::normalize_dark_mode( sanitize_key( wp_unslash( $_POST[ $meta_key ] ) ) ) );
                }
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        return $value;
    }

    /**
     * Normalize dark mode setting to off|enabled|auto.
     *
     * Accepts legacy checkbox arrays (e.g. ['enabled']) and the current select string.
     *
     * @param mixed $mode Raw dark_mode setting value.
     * @return string One of: off, enabled, auto.
     */
    public static function normalize_dark_mode( $mode ) {
        if ( is_array( $mode ) ) {
            if ( in_array( 'auto', $mode, true ) ) {
                return 'auto';
            }
            if ( in_array( 'enabled', $mode, true ) ) {
                return 'enabled';
            }
            return 'off';
        }

        $mode = is_string( $mode ) ? $mode : 'off';

        if ( in_array( $mode, [ 'off', 'enabled', 'auto' ], true ) ) {
            return $mode;
        }

        return 'off';
    }

    /**
     * The settings tabs and their fields (filterable, so Cooked Pro can add its own).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function tabs_fields() {
        $pages_array            = self::pages_array( __( 'Choose a page...', 'cooked' ), __( 'No pages', 'cooked' ) );
        $categories_array       = self::terms_array( 'cp_recipe_category', __( 'No default', 'cooked' ), __( 'No categories', 'cooked' ) );
        $recipes_per_page_array = self::per_page_array();
        $recipe_archive_slug    = sanitize_title_with_dashes( __( 'Recipe Archive', 'cooked' ) );
        $recipe_archive_url     = home_url( '/' . $recipe_archive_slug . '/' );

        // Dynamically load roles.
        $role_options = [];
        if ( is_user_logged_in() ) {
            global $wp_roles;
            $roles = $wp_roles->roles;

            if ( ! empty( $roles ) ) {
                foreach ( $roles as $role => $data ) {
                    $role_options[ $role ] = [
                        'label' => $data['name'],
                    ];
                }
            }
        }

        return apply_filters(
            'cooked_settings_tabs_fields',
            [
				'recipe_settings' => [
					'name'   => __( 'General', 'cooked' ),
					'icon'   => 'gear',
					'fields' => [
						'browse_page'                      => [
							'title'   => __( 'Browse/Search Recipes Page', 'cooked' ),
							/* translators: a description on how to add the [cooked-browse] shortcode to a page */
							'desc'    => sprintf( __( 'Create a page with the %s shortcode on it, then choose it from this dropdown.', 'cooked' ), '<code>[cooked-browse]</code>' ) . '<br><em>' . __( '<b>Note:</b> This setting is required for the plugin to function properly.', 'cooked' ) . '</em>',
							'type'    => 'select',
							'default' => 0,
							'options' => $pages_array,
						],
						'recipes_per_page'                 => [
							'title'   => __( 'Recipes Per Page', 'cooked' ),
							/* translators: a description on how to choose the default number of recipes per page. */
							'desc'    => sprintf( __( 'Choose the default (set via the %s panel) or choose a different number here.', 'cooked' ), '<a href="' . trailingslashit( esc_url( get_admin_url() ) ) . 'options-reading.php">' . __( 'Settings > Reading', 'cooked' ) . '</a>' ),
							'type'    => 'select',
							'default' => 9,
							'options' => $recipes_per_page_array,
						],
						'recipe_taxonomies'                => [
							'title'   => __( 'Recipe Taxonomies', 'cooked' ),
							'desc'    => __( 'Choose which taxonomies you want to enable for your recipes.', 'cooked' ),
							'type'    => 'checkboxes',
							'default' => [ 'cp_recipe_category' ],
							'options' => apply_filters(
								'cooked_taxonomy_options',
								[
									'cp_recipe_category' => __( 'Categories', 'cooked' ),
								]
							),
						],
						'recipe_info_display_options'      => [
							'title'   => __( 'Global Recipe Toggles', 'cooked' ),
							'desc'    => __( 'You can quickly hide or show different recipe elements (site-wide) with these checkboxes.', 'cooked' ),
							'type'    => 'checkboxes',
							'default' => apply_filters(
								'cooked_recipe_info_display_options_defaults',
								[
									'author',
									'taxonomies',
									'difficulty_level',
									'excerpt',
									'timing_prep',
									'timing_cook',
									'timing_total',
									'servings',
								]
							),
							'options' => apply_filters(
								'cooked_recipe_info_display_options',
								[
									'author'           => __( 'Author', 'cooked' ),
									'taxonomies'       => __( 'Category', 'cooked' ),
									'difficulty_level' => __( 'Difficulty Level', 'cooked' ),
									'excerpt'          => __( 'Excerpt', 'cooked' ),
									'notes'            => __( 'Notes', 'cooked' ),
									'timing_prep'      => __( 'Prep Time', 'cooked' ),
									'timing_cook'      => __( 'Cook Time', 'cooked' ),
									'timing_total'     => __( 'Total Time', 'cooked' ),
									'servings'         => __( 'Servings', 'cooked' ),
								]
							),
						],
						'print_view_display_options'       => [
							'title'   => __( 'Print View', 'cooked' ),
							'desc'    => esc_html__( 'When enabled, the website logo will appear at the top of the recipe print screen.', 'cooked' ),
							'type'    => 'checkboxes',
							'default' => [],
							'options' => apply_filters(
								'cooked_print_view_display_options',
								[
									'site_logo' => __( 'Show Website Logo', 'cooked' ),
								]
							),
						],
						'carb_format'                      => [
							'title'   => __( 'Carbs Format', 'cooked' ),
							'desc'    => __( 'You can display carbs as "Total" or "Net".', 'cooked' ),
							'type'    => 'select',
							'default' => 'total',
							'options' => apply_filters(
								'cooked_settings_carb_formats',
								[
									'total' => __( 'Total Carbs', 'cooked' ),
									'net'   => __( 'Net Carbs', 'cooked' ),
								]
							),
						],
						'author_name_format'               => [
							'title'   => __( 'Author Name Format', 'cooked' ),
							'desc'    => __( 'You can show the full author\'s name or just a part of it.', 'cooked' ),
							'type'    => 'select',
							'default' => 'full',
							'options' => apply_filters(
								'cooked_settings_author_formats',
								[
									'full'               => __( 'Full name', 'cooked' ),
									'first_last_initial' => __( 'Full first name w/last name initial', 'cooked' ),
									'first_initial_last' => __( 'First name initial w/full last name', 'cooked' ),
									'first_only'         => __( 'First name only', 'cooked' ),
								]
							),
						],
						'disable_author_links'             => [
							'title'   => __( 'Author Links', 'cooked' ),
							'desc'    => __( 'If you do not want the author names to link to the author recipe listings, you can disable them here.', 'cooked' ) . '<br><em>' . __( '<b>Note:</b> Author links require the Browse/Search Recipes Page to be set up correctly to function properly.', 'cooked' ) . '</em>',
							'type'    => 'checkboxes',
							'color'   => 'red',
							'default' => [],
							'options' => apply_filters(
								'cooked_author_link_options',
								[
									'disabled' => __( 'Disable Author Links', 'cooked' ),
								]
							),
						],
						'browse_default_cp_recipe_category' => [
							'title'   => __( 'Default Category', 'cooked' ),
							/* translators: a description on how to set the default recipe category for the [cooked-browse] shortcode. */
							'desc'    => sprintf( __( 'Optionally set the default recipe category for your %s shortcode display.', 'cooked' ), '<code>[cooked-browse]</code>' ),
							'type'    => 'select',
							'default' => 0,
							'options' => $categories_array,
						],
						'browse_default_sort'              => [
							'title'   => __( 'Default Sort Order', 'cooked' ),
							/* translators: a description on how to set the default sort order for the [cooked-browse] shortcode. */
							'desc'    => sprintf( __( 'Set the default sort order for your %s shortcode display.', 'cooked' ), '<code>[cooked-browse]</code>' ),
							'type'    => 'select',
							'default' => 'date_desc',
							'options' => apply_filters(
								'cooked_settings_sort_options',
								[
									'date_desc'  => __( 'Newest First', 'cooked' ),
									'date_asc'   => __( 'Oldest First', 'cooked' ),
									'title_asc'  => __( 'Alphabetical', 'cooked' ),
									'title_desc' => __( 'Alphabetical (reversed)', 'cooked' ),
								]
							),
						],
						'section_heading_default_html_tag' => [
							'title'   => __( 'Section Heading Default HTML Tag', 'cooked' ),
							/* translators: a description on how to set the default sort order for the [cooked-browse] shortcode. */
							'desc'    => __( 'Set the default HTML tag for your section headings.', 'cooked' ),
							'type'    => 'select',
							'default' => 'div',
							'options' => apply_filters(
								'cooked_settings_section_heading_default_html_tag_options',
								[
									'div' => __( 'div', 'cooked' ),
									'h2'  => __( 'h2', 'cooked' ),
									'h3'  => __( 'h3', 'cooked' ),
									'h4'  => __( 'h4', 'cooked' ),
									'h5'  => __( 'h5', 'cooked' ),
									'h6'  => __( 'h6', 'cooked' ),
								]
							),
						],
						'recipe_wp_editor_roles'           => [
							'title'   => __( 'WP Editor Roles', 'cooked' ),
							'desc'    => __( 'Choose which user roles can use the WP Editor for the Excerpt, Directions & Notes fields.', 'cooked' ),
							'type'    => 'checkboxes',
							'default' => apply_filters( 'cooked_recipe_wp_editor_roles_defaults', [ 'administrator', 'editor', 'cooked_recipe_editor' ] ),
							'options' => $role_options,
						],
						'admin_interface'                  => [
							'title'         => __( 'Admin Screens', 'cooked' ),
							'desc'          => __( 'Cooked 2.0 shows its Settings, Import, What\'s New and Upgrade to Pro screens, and the recipe editor, in a new layout. Switch to the classic layout if you prefer the previous one.', 'cooked' ),
							'type'          => 'checkboxes',
							'default'       => [],
							'options'       => [
								'classic' => __( 'Use the Classic Layout', 'cooked' ),
							],
							'after_control' => [ __CLASS__, 'layout_compare_button' ],
						],
						'advanced'                         => [
							'title'   => __( 'Advanced Settings', 'cooked' ),
							'desc'    => '',
							'type'    => 'checkboxes',
							'color'   => 'red',
							'class'   => 'cooked-danger',
							'default' => [],
							'options' => apply_filters(
								'cooked_advanced_options',
								[
									/* translators: an option to only show recipes with the [cooked-recipe] shortcode. */
									'disable_public_recipes' => '<strong>' . __( 'Disable Public Recipes', 'cooked' ) . '</strong> &mdash; ' . sprintf( __( 'Only show recipes using the %s shortcode.', 'cooked' ), '<code>[cooked-recipe]</code>' ),
									/* translators: an option to disable "meta" tags. */
									'disable_meta_tags' => '<strong>' . sprintf( __( 'Disable %s Tags', 'cooked' ), 'Cooked <code>&lt;meta&gt;</code>' ) . '</strong> &mdash; ' . __( 'Prevents duplicates when tags already exist.', 'cooked' ),
									'disable_servings_switcher' => '<strong>' . __( 'Disable "Servings Switcher"', 'cooked' ) . '</strong> &mdash; ' . __( 'Removes the servings dropdown on recipes.', 'cooked' ),
									'enable_measurement_switcher' => '<strong>' . __( 'Enable "Measurement System Switcher"', 'cooked' ) . '</strong> &mdash; ' . __( 'Shows the Metric/Imperial toggle on recipes.', 'cooked' ),
									'disable_schema_output' => '<strong>' . __( 'Disable Recipe Schema Output', 'cooked' ) . '</strong> &mdash; ' . __( 'You should only do this if you\'re using something else to output schema information.', 'cooked' ),
									'disable_cp_recipe_archive' => '<strong>' . __( 'Disable Recipe Archive Page', 'cooked' ) . '</strong> &mdash; ' . sprintf(
										/* translators: %s: recipe archive URL, e.g. https://example.com/recipe-archive/ */
										__( 'Prevents the recipe archive from being displayed at %s.', 'cooked' ),
										'<code>' . esc_html( untrailingslashit( $recipe_archive_url ) ) . '/</code>'
									),
								]
							),
						],
					],
				],
				'design'          => [
					'name'   => __( 'Design', 'cooked' ),
					'icon'   => 'pencil',
					'fields' => [
						'dark_mode'            => [
							'title'   => __( 'Dark Mode', 'cooked' ),
							'desc'    => apply_filters(
								'cooked_dark_mode_field_desc',
								__( 'For your recipes on the site. Use <strong>Auto</strong> if your theme has a visitor dark mode toggle. Cooked will then match that choice. Otherwise leave this Off.', 'cooked' )
							),
							'type'    => 'select',
							'default' => 'off',
							'options' => apply_filters(
								'cooked_dark_mode_options',
								[
									'off'     => __( 'Off', 'cooked' ),
									'enabled' => __( 'On', 'cooked' ),
									'auto'    => __( 'Auto (match theme dark mode)', 'cooked' ),
								]
							),
						],
						// Per-user preferences, saved to user meta (save_admin_dark_mode()), not to cooked_settings.
						'admin_dark_mode'      => [
							'title'    => __( 'Admin Dark Mode', 'cooked' ),
							'desc'     => __( 'Your own preference for the Settings, Import, What\'s New and Upgrade to Pro screens. <strong>Auto</strong> follows your computer\'s light or dark setting.', 'cooked' ),
							'type'     => 'user_dark_mode',
							'meta_key' => 'cooked_admin_dark_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- A user meta name, not a query.
							'default'  => 'off',
							'options'  => [
								'off'     => __( 'Off', 'cooked' ),
								'enabled' => __( 'On', 'cooked' ),
								'auto'    => __( 'Auto (match your system)', 'cooked' ),
							],
						],
						'editor_dark_mode'     => [
							'title'    => __( 'Recipe Editor Dark Mode', 'cooked' ),
							'desc'     => __( 'Your own preference for the recipe editor. <strong>Auto</strong> follows your computer\'s light or dark setting.', 'cooked' ),
							'type'     => 'user_dark_mode',
							'meta_key' => 'cooked_editor_dark_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- A user meta name, not a query.
							'default'  => 'off',
							'options'  => [
								'off'     => __( 'Off', 'cooked' ),
								'enabled' => __( 'On', 'cooked' ),
								'auto'    => __( 'Auto (match your system)', 'cooked' ),
							],
						],
						'hide_author_avatars'  => [
							'title'   => __( 'Author Images', 'cooked' ),
							'desc'    => __( 'If you do not want to display the author images (avatars), you can disable them here.', 'cooked' ),
							'type'    => 'checkboxes',
							'color'   => 'red',
							'default' => [],
							'options' => apply_filters(
								'cooked_author_image_options',
								[
									'hidden' => __( 'Hide Author Images', 'cooked' ),
								]
							),
						],
						'default_recipe_image' => [
							'title'   => __( 'Default Recipe Image', 'cooked' ),
							'desc'    => __( 'Used as the featured image for recipes that do not have one set.', 'cooked' ),
							'type'    => 'image_field',
							'default' => 0,
						],
						'main_color'           => [
							'title'   => __( 'Main Color', 'cooked' ),
							'desc'    => __( 'Used on buttons, cooking timer, etc.', 'cooked' ),
							'type'    => 'color_field',
							'default' => '#16a780',
							'options' => '#16a780',
						],
						'main_color_hover'     => [
							'title'   => __( 'Main Color (on hover)', 'cooked' ),
							'desc'    => __( 'Used when hovering over buttons.', 'cooked' ),
							'type'    => 'color_field',
							'default' => '#1b9371',
							'options' => '#1b9371',
						],
					],
				],
				'permalinks'      => [
					'name'   => __( 'Permalinks', 'cooked' ),
					'icon'   => 'link-lt',
					'fields' => [
						'recipe_permalink'          => [
							'title'   => __( 'Recipe Permalink', 'cooked' ),
							'desc'    => '',
							'type'    => 'permalink_field',
							'options' => __( 'recipe-name', 'cooked' ),
							'default' => 'recipes',
						],
						'recipe_author_permalink'   => [
							'title'   => __( 'Recipe Author Permalink', 'cooked' ),
							'desc'    => '',
							'type'    => 'permalink_field',
							'options' => __( 'author-name', 'cooked' ),
							'default' => 'recipe-author',
						],
						'recipe_category_permalink' => [
							'title'   => __( 'Recipe Category Permalink', 'cooked' ),
							'desc'    => '',
							'type'    => 'permalink_field',
							'options' => __( 'recipe-category-name', 'cooked' ),
							'default' => 'recipe-category',
						],
					],
				],
			],
            $pages_array,
            $categories_array
        );
    }

    /**
     * Choices for "Recipes Per Page".
     *
     * @return array<int|string, string>
     */
    public static function per_page_array() {
        $counter = 0;
        /* translators: posts_per_page default */
        $per_page_array[] = sprintf( __( 'WordPress Default %s', 'cooked' ), '(' . get_option( 'posts_per_page' ) . ')' );
        do {
            ++$counter;
            $per_page_array[ $counter ] = $counter;
        } while ( $counter < 50 );
        $per_page_array['-1'] = __( 'Show All (no pagination)', 'cooked' );

        return apply_filters( 'cooked_per_page_options', $per_page_array );
    }

    /**
     * Published pages, for page select fields.
     *
     * @param string|false $choose_text Label for the empty "choose" option, if any.
     * @param string|false $none_text   Label shown when there are no pages, if any.
     * @return array<int|string, string>
     */
    public static function pages_array( $choose_text, $none_text = false ) {
        $page_array = [];
        $pages      = get_posts(
            [
				'post_type'      => 'page',
				'posts_per_page' => -1,
			]
        );

        if ( ! empty( $pages ) ) {
            $page_array[0] = $choose_text;
            foreach ( $pages as $_page ) {
                $page_array[ $_page->ID ] = $_page->post_title . ' (ID:' . $_page->ID . ')';
            }
        } elseif ( $none_text ) {
            $page_array[0] = $none_text;
        }

        return apply_filters( 'cooked_settings_pages_array', $page_array );
    }

    /**
     * Terms of a taxonomy, for select fields.
     *
     * @param string           $term         Taxonomy name.
     * @param string|false     $choose_text  Label for the empty "choose" option, if any.
     * @param string|false     $none_text    Label shown when there are no terms, if any.
     * @param bool|string      $hide_empty   Whether to leave out terms without recipes.
     * @param bool             $parents_only Whether to list top-level terms only.
     * @param int|string|false $child_of     Only list children of this term (ID or slug).
     * @return array<int|string, string>
     */
    public static function terms_array( $term, $choose_text, $none_text = false, $hide_empty = false, $parents_only = false, $child_of = false ) {
        $terms_array = [];

        $args = [
            'taxonomy'   => $term,
            // Shortcode attributes arrive as strings, so hide_empty="false" must not count as true.
            'hide_empty' => rest_sanitize_boolean( $hide_empty ),
        ];

        if ( $parents_only ) {
            $args['parent'] = 0;
        } elseif ( $child_of ) {
            $_term          = is_numeric( $child_of ) ? null : get_term_by( 'slug', $child_of, $term );
            $args['parent'] = is_numeric( $child_of ) ? (int) $child_of : ( $_term instanceof \WP_Term ? $_term->term_id : 0 );
        }

        $terms = get_terms( $args );

        // get_terms() returns a WP_Error when the taxonomy isn't registered (e.g. a Pro taxonomy).
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            if ( $choose_text ) {
                $terms_array[0] = $choose_text;
            }

            foreach ( $terms as $_term ) {
                if ( ! is_array( $_term ) ) {
                    $terms_array[ $_term->term_id ] = $_term->name;
                }
            }
        } elseif ( $none_text ) {
            $terms_array[0] = $none_text;
        }

        return apply_filters( 'cooked_settings_' . $term . '_array', $terms_array );
    }

    /**
     * Renders a radio button group.
     *
     * @param string                   $field_name Setting key.
     * @param array<int|string, mixed> $options    Choices, value => label (or label settings).
     * @return void
     */
    public static function field_radio( $field_name, $options ) {
        global $_cooked_settings;

        $counter = 1;

        echo '<div class="cooked-option-list">';
		foreach ( $options as $value => $name ) {
			$is_disabled             = '';
			$conditional_value       = '';
			$conditional_requirement = '';

			if ( is_array( $name ) ) :
				if ( isset( $name['read_only'] ) && $name['read_only'] ) :
					$is_disabled = ' disabled';
                    endif;

				if ( isset( $name['conditional_value'] ) && $name['conditional_value'] ) :
					$conditional_value = ' data-cooked-condition-value="' . esc_attr( $name['conditional_value'] ) . '"';
                    endif;

				if ( isset( $name['conditional_requirement'] ) && $name['conditional_requirement'] ) :
					$conditional_requirement = ' data-cooked-condition="' . esc_attr( implode( ' ', (array) $name['conditional_requirement'] ) ) . '"';
                    endif;

				$name = $name['label'];
                endif;

			$combined_extras = $is_disabled . $conditional_value;

			echo '<div class="cooked-option"' . wp_kses( $conditional_requirement, [] ) . '>';
			echo '<input' . wp_kses( $combined_extras, array() ) . ' type="radio" id="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . esc_attr( $value ) . '"' . ( ( isset( $_cooked_settings[ $field_name ] ) && is_scalar( $_cooked_settings[ $field_name ] ) && (string) $_cooked_settings[ $field_name ] === (string) $value ) || ( isset( $_cooked_settings[ $field_name ][0] ) && (string) $_cooked_settings[ $field_name ][0] === (string) $value ) ? ' checked' : '' ) . '/>';
			echo '<label for="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' . wp_kses_post( $name ) . '</label>';
			echo '</div>';

			++$counter;
		}
        echo '</div>';
    }

    /**
     * Renders a per-user dark mode select (Admin Dark Mode, Recipe Editor Dark Mode): the current
     * user's preference from the field's meta_key, saved by save_admin_dark_mode().
     *
     * @since 2.0.0
     * @param string                    $field_name Setting key.
     * @param array<int|string, string> $options    Choices, value => label.
     * @param string|false              $color      Unused.
     * @param array<string, mixed>      $field      Field definition, with its user meta key.
     * @return void
     */
    public static function field_user_dark_mode( $field_name, $options, $color = false, $field = [] ) {
        $meta_key = isset( $field['meta_key'] ) ? (string) $field['meta_key'] : '';
        if ( ! in_array( $meta_key, [ 'cooked_admin_dark_mode', 'cooked_editor_dark_mode' ], true ) ) {
            return;
        }
        $current = self::normalize_dark_mode( get_user_meta( get_current_user_id(), $meta_key, true ) );
        echo '<p><select id="' . esc_attr( str_replace( '_', '-', $meta_key ) ) . '" name="' . esc_attr( $meta_key ) . '" aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '">';
        foreach ( $options as $value => $label ) {
            echo '<option value="' . esc_attr( (string) $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></p>';
    }

    /**
     * ID of a setting's title (the <h3> the settings templates print above each field).
     *
     * @since 2.0.0
     * @param string $field_name Setting key.
     * @return string
     */
    public static function title_id( $field_name ) {
        return 'cooked-setting-title-' . sanitize_html_class( $field_name );
    }

    /**
     * Renders a select field.
     *
     * @param string                    $field_name Setting key.
     * @param array<int|string, string> $options    Choices, value => label.
     * @param string|false              $color      Unused.
     * @param array<string, mixed>      $field      Field definition.
     * @return void
     */
    public static function field_select( $field_name, $options, $color = false, $field = [] ) {
        global $_cooked_settings;

        $is_disabled = '';

        if ( isset( $field['read_only'] ) && $field['read_only'] ) {
            $is_disabled = ' disabled';
        }

        echo '<p>';
            echo '<select aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '"' . esc_attr( $is_disabled ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']">';
		foreach ( $options as $value => $name ) {
			echo '<option value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[ $field_name ] ) && is_scalar( $_cooked_settings[ $field_name ] ) && (string) $_cooked_settings[ $field_name ] === (string) $value ? ' selected' : '' ) . '>' . esc_attr( $name ) . '</option>';
		}
            echo '</select>';
        echo '</p>';
    }

    /**
     * The Admin Screens setting's "Compare layouts" button and the dialog it opens: a slider
     * over screenshots of the classic and new layouts (display only: nothing is saved).
     *
     * @since 2.0.0
     * @return void
     */
    public static function layout_compare_button() {
        ?>
        <button type="button" class="button cooked-layout-compare-open" aria-haspopup="dialog" aria-controls="cooked-layout-compare-dialog"><?php esc_html_e( 'Compare layouts', 'cooked' ); ?></button>
        <dialog id="cooked-layout-compare-dialog" class="cooked-dialog cooked-layout-compare-dialog" aria-labelledby="cooked-layout-compare-title">
            <div class="cooked-dialog-header">
                <h2 id="cooked-layout-compare-title"><?php esc_html_e( 'Classic and new layouts', 'cooked' ); ?></h2>
                <button type="button" class="cooked-dialog-close" aria-label="<?php esc_attr_e( 'Close', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
            </div>
            <div class="cooked-dialog-body">
                <p class="cooked-layout-compare-intro"><?php esc_html_e( 'Drag the slider to see the same screen in the classic layout (left) and the new one (right).', 'cooked' ); ?></p>
                <?php include COOKED_DIR . 'templates/admin/parts/layout-compare.php'; ?>
            </div>
        </dialog>
        <?php
    }

    /**
     * Outputs a nonce field.
     *
     * @param string $field_name Setting key.
     * @return void
     */
    public static function field_nonce( $field_name ) {
        wp_nonce_field( $field_name, $field_name );
    }

    /**
     * Renders a submit button. Kept for backwards compatibility; Cooked Pro stopped using it in 1.0.1.
     *
     * @param string $field_name Button name.
     * @param string $title      Button label.
     * @return void
     */
    public static function field_misc_button( $field_name, $title ) {
        echo '<p>';
            echo '<input type="submit" class="button-secondary" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $title ) . '">';
        echo '</p>';
    }

    /**
     * Renders the Begin Migration button for classic recipes.
     *
     * @return void
     */
    public static function field_migrate_button() {
        $total = count( \Cooked_Migration::pending_classic_recipes() );

        if ( $total > 0 ) {
            echo '<p>';
                echo '<input id="cooked-migration-button" type="button" class="button-secondary" name="begin_cooked_migration" value="' . esc_attr__( 'Begin Migration', 'cooked' ) . '">';
            echo '</p>';
            echo '<p>';
                echo '<span id="cooked-migration-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-migration-progress-text" class="cooked-progress-text">0 / ' . esc_html( $total ) . '</span>';
            echo '</p>';
            echo '<p id="cooked-migration-completed"><strong>Migration Complete!</strong> You can now <a href="' . esc_url( add_query_arg( [ 'page' => 'cooked_settings' ], esc_url( admin_url( 'admin.php' ) ) ) ) . '">' . esc_html__( 'reload', 'cooked' ) . '</a> the settings screen.</p>';
        }
    }

    /**
     * Renders a text field.
     *
     * @param string       $field_name  Setting key.
     * @param string|false $placeholder Placeholder text, if any.
     * @return void
     */
    public static function field_text( $field_name, $placeholder ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '" id="cooked_field--' . esc_attr( $field_name ) . '" type="text"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[ $field_name ] ) && $_cooked_settings[ $field_name ] ? esc_attr( $_cooked_settings[ $field_name ] ) : '' ) . '">';
        echo '</p>';
    }

    /**
     * Renders a password field.
     *
     * @param string       $field_name  Setting key.
     * @param string|false $placeholder Placeholder text, if any.
     * @return void
     */
    public static function field_password( $field_name, $placeholder ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '" type="password"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[ $field_name ] ) && $_cooked_settings[ $field_name ] ? esc_attr( $_cooked_settings[ $field_name ] ) : '' ) . '">';
        echo '</p>';
    }

    /**
     * Outputs a block of HTML (no input).
     *
     * @param string $field_name Setting key.
     * @param string $html       HTML to output.
     * @return void
     */
    public static function field_html( $field_name, $html ) {
        echo wp_kses_post( $html );
    }

    /**
     * Renders a permalink slug field after the Browse page URL.
     *
     * @param string $field_name Setting key.
     * @param string $end_of_url Example shown after the slug.
     * @return void
     */
    public static function field_permalink_field( $field_name, $end_of_url ) {
        global $_cooked_settings;

        $browse_page_id  = $_cooked_settings['browse_page'];
        $browse_page_url = '';

        if ( ! empty( $browse_page_id ) && 'recipe_permalink' !== $field_name ) {
            $browse_page_url = get_permalink( $browse_page_id );
        }

        if ( empty( $browse_page_url ) ) {
            $browse_page_url = get_home_url();
        }

        if ( substr( $browse_page_url, -1 ) !== '/' ) {
            $browse_page_url .= '/';
        }

        echo '<p class="cooked-permalink-field-wrapper">';
            echo '<span>' . esc_url( $browse_page_url ) . '</span><input aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '" type="text" class="cooked-permalink-field" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[ $field_name ] ) && $_cooked_settings[ $field_name ] ? esc_attr( $_cooked_settings[ $field_name ] ) : '' ) . '"><span>/' . esc_html( $end_of_url ) . '/</span>';
        echo '</p>';
    }

    /**
     * Renders a number field.
     *
     * @param string $field_name Setting key.
     * @return void
     */
    public static function field_number_field( $field_name ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '" type="number" min="0" step="any" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[ $field_name ] ) && $_cooked_settings[ $field_name ] ? esc_attr( $_cooked_settings[ $field_name ] ) : '' ) . '">';
        echo '</p>';
    }

    /**
     * Renders a colour picker.
     *
     * @param string $field_name    Setting key.
     * @param string $default_color Default colour.
     * @return void
     */
    public static function field_color_field( $field_name, $default_color ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input aria-labelledby="' . esc_attr( self::title_id( $field_name ) ) . '" class="cooked-color-field" type="text"' . ( $default_color ? ' data-default-color="' . esc_attr( $default_color ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[ $field_name ] ) && $_cooked_settings[ $field_name ] ? esc_attr( $_cooked_settings[ $field_name ] ) : '' ) . '">';
        echo '</p>';
    }

    /**
     * Renders an image picker (Media Library).
     *
     * @param string $field_name Setting key.
     * @return void
     */
    public static function field_image_field( $field_name ) {
        global $_cooked_settings;

        $attachment_id = isset( $_cooked_settings[ $field_name ] ) ? absint( $_cooked_settings[ $field_name ] ) : 0;
        $has_image     = $attachment_id && wp_attachment_is_image( $attachment_id );
        $preview_id    = 'cooked-settings-image-' . esc_attr( $field_name ) . '-src';

        echo '<div class="cooked-settings-image-field' . ( $has_image ? ' cooked-has-image' : '' ) . '">';
            echo '<input type="hidden" class="cooked-settings-image-input" name="cooked_settings[' . esc_attr( $field_name ) . ']" id="cooked-settings-image-' . esc_attr( $field_name ) . '" value="' . esc_attr( $attachment_id ) . '" />';
            echo '<input type="button" class="button cooked-settings-image-button" value="' . esc_attr( $has_image ? esc_html__( 'Change Image', 'cooked' ) : esc_html__( 'Add Image', 'cooked' ) ) . '" />';

		if ( $has_image ) {
			echo wp_get_attachment_image(
                $attachment_id,
                'thumbnail',
                false,
                [
					'class' => 'cooked-settings-image-preview-img',
					'id'    => $preview_id,
				]
            );
		} else {
			echo '<img class="cooked-settings-image-preview-img" id="' . esc_attr( $preview_id ) . '" src="" alt="" />';
		}

            echo '<button type="button" class="cooked-settings-image-remove"><i class="cooked-icon cooked-icon-times" aria-hidden="true"></i><span class="screen-reader-text">' . esc_html__( 'Remove image', 'cooked' ) . '</span></button>';
        echo '</div>';
    }

    /**
     * Renders a group of toggle switches.
     *
     * @param string                   $field_name Setting key.
     * @param array<int|string, mixed> $options    Choices, value => label (or label settings).
     * @param string|false             $color      Switch colour variant (red, yellow, gray), if any.
     * @param array<string, mixed>     $field      Field definition.
     * @return void
     */
    public static function field_checkboxes( $field_name, $options, $color = false, $field = [] ) {
        global $_cooked_settings;

        echo '<div class="cooked-option-list">';
		foreach ( $options as $value => $name ) {
			$is_disabled             = '';
			$conditional_value       = '';
			$conditional_requirement = '';

			if ( is_array( $name ) ) {
				if ( isset( $name['read_only'] ) && $name['read_only'] ) :
					$is_disabled = ' disabled';
                    endif;

				if ( isset( $name['conditional_value'] ) && $name['conditional_value'] ) :
					$conditional_value = ' data-cooked-condition-value="' . esc_attr( $name['conditional_value'] ) . '"';
                    endif;

				if ( isset( $name['conditional_requirement'] ) && $name['conditional_requirement'] ) :
					$conditional_requirement = ' data-cooked-condition="' . esc_attr( implode( ' ', (array) $name['conditional_requirement'] ) ) . '"';
                    endif;

				$name = $name['label'];
			}

			$combined_extras = $is_disabled . $conditional_value;

			echo '<div class="cooked-option"' . wp_kses( $conditional_requirement, [] ) . '>';

			// Check if the setting exists at all.
			$setting_exists = isset( $_cooked_settings[ $field_name ] ) && is_array( $_cooked_settings[ $field_name ] );

			// Only use default if setting doesn't exist.
			$use_default = ! $setting_exists && isset( $field['default'] );

			// Determine if checkbox should be checked.
			// Option keys can be ints; saved values are strings. Compare both as strings.
			$is_checked = ( $setting_exists && in_array( (string) $value, array_map( 'strval', $_cooked_settings[ $field_name ] ), true ) ) ||
							( $use_default && in_array( (string) $value, array_map( 'strval', (array) $field['default'] ), true ) ) ||
							$is_disabled;

			if ( $is_disabled ) {
				echo '<input type="hidden" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) . '">';
				echo '<input' . wp_kses( $combined_extras, array() ) . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) .
					'" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) .
					'"' . ( $is_checked ? ' checked' : '' ) . '/>';
			} else {
				echo '<input' . wp_kses( $combined_extras, array() ) . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) .
					'" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) .
					'" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) .
					'"' . ( $is_checked ? ' checked' : '' ) . '/>';
			}

			echo '<label for="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' .
				wp_kses_post( $name ) . '</label>';
			echo '</div>';
		}
        echo '</div>';
    }
}
