<?php
/**
 * Register Settings
 *
 * @package     Cooked
 * @subpackage  Settings
 * @since       1.0.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Settings Class
 *
 * This class handles the settings creation and contains functions for retreiving those settings.
 *
 * @since 1.0.0
 */
class Cooked_Settings {

    public function __construct() {
        add_filter( 'admin_init', [&$this, 'init'] );
        add_filter( 'init', [&$this, 'init'] );
        add_action( 'save_post', [&$this, 'browse_page_saved'], 10, 1 );
        add_action( 'admin_notices', [&$this, 'cooked_settings_saved_admin_notice'] );
    }

    public function browse_page_saved( $post_id ) {
        // Just a revision, don't do anything
        if ( wp_is_post_revision( $post_id ) ) return;

        $_cooked_settings = Cooked_Settings::get();
        if ( isset($_cooked_settings['browse_page']) && $_cooked_settings['browse_page'] == $post_id ) {
            flush_rewrite_rules(false);
        }
    }

    public static function init() {
        global $_cooked_settings, $list_id_counter;

        $list_id_counter = 0;
        $_cooked_settings = Cooked_Settings::get();
        register_setting( 'cooked_settings_group', 'cooked_settings' );
        register_setting( 'cooked_settings_group', 'cooked_settings_saved' );
    }

    function cooked_settings_saved_admin_notice() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] && isset($_GET['page']) && $_GET['page'] === 'cooked_settings') {
            add_settings_error(
                'cooked_settings_group',
                'cooked_settings_updated',
                __( 'Cooked settings has been updated!', 'cooked' ),
                'updated'
            );
        }
    }

    public static function reset() {
        global $_cooked_settings;
        $_cooked_settings = Cooked_Settings::get();
    }

    public static function get() {
        $update_settings = false;
        $_cooked_settings = get_option( 'cooked_settings' );
        $cooked_settings_saved = get_option( 'cooked_settings_saved', false );
        $_cooked_settings_version = get_option( 'cooked_settings_version', '1.0.0' );

        $version_compare = version_compare( $_cooked_settings_version, COOKED_VERSION );

        // Get defaults for fields that are not set yet.
        $cooked_tabs_fields = self::tabs_fields();
        if ( isset($cooked_tabs_fields) && !empty($cooked_tabs_fields) ) {
            foreach ( $cooked_tabs_fields as $tab ) {
                if ( isset($tab['fields']) && !empty($tab['fields']) ) {
                    foreach ( $tab['fields'] as $name => $field ) {
                        if ( $field['type'] == 'nonce' || $field['type'] == 'misc_button' ) continue;

                        if ( !$cooked_settings_saved || ( $cooked_settings_saved && $version_compare < 0 ) ) {
                            if ( $field['type'] == 'checkboxes' ) {
                                $_cooked_settings[$name] = isset($_cooked_settings[$name]) ? $_cooked_settings[$name] : ( isset( $field['default'] ) ? $field['default'] : [] );
                            } else {
                                $_cooked_settings[$name] = isset($_cooked_settings[$name]) ? $_cooked_settings[$name] : ( isset( $field['default'] ) ? $field['default'] : false );
                            }

                            // Update the settings only if the version has changed.
                            $update_settings = true;
                        }
                    }
                }
            }
        }

        if ( $update_settings ) {
            update_option( 'cooked_settings', $_cooked_settings );

            if ( self::needs_rewrite_flush( $_cooked_settings_version ) ) {
                flush_rewrite_rules();
            }
        }

        if ( $version_compare < 0 ) {
            update_option( 'cooked_settings_version', COOKED_VERSION );
        }

        return apply_filters( 'cooked_get_settings', $_cooked_settings );
    }

    private static function needs_rewrite_flush( $old_version ) {
        // List versions that require a rewrite flush
        $versions_requiring_flush = [
            '1.9.0',  // New rewrite rules for Browse page introduced.
            '1.9.1',  // Hotfix for the permalink structure.
            '1.9.2',  // Hotfix for the permalink structure.
            '1.9.4',  // Hotfix for the permalink structure.
            '1.9.5',  // Hotfix for the permalink structure (sort & search).
        ];

        // If old version is newer than our latest flush requirement, no flush needed
        if (version_compare($old_version, end($versions_requiring_flush), '>=')) {
            return false;
        }

        // Find the next version that requires a flush after the old version
        foreach ($versions_requiring_flush as $version) {
            if (version_compare($old_version, $version, '<') &&
                version_compare(COOKED_VERSION, $version, '>=')) {
                return true;
            }
        }

        return false;
    }

    public static function tabs_fields() {
        $pages_array = self::pages_array( __('Choose a page...','cooked'), __('No pages','cooked') );
        $categories_array = self::terms_array( 'cp_recipe_category', __('No default','cooked'), __('No categories','cooked') );
        $recipes_per_page_array = self::per_page_array();

        // Dynamically load roles.
        $role_options = [];
        if (is_user_logged_in()) {
            global $wp_roles;
            $roles = $wp_roles->roles;

            if (!empty($roles)) {
                foreach ( $roles as $role => $data ) {
                    $role_options[$role] = [
                        'label' => $data['name']
                    ];
                }
            }
        }

        return apply_filters('cooked_settings_tabs_fields', [
            'recipe_settings' => [
                'name' => __('General', 'cooked'),
                'icon' => 'gear',
                'fields' => [
                    'browse_page' => [
                        'title' => __('Browse/Search Recipes Page', 'cooked'),
                        /* translators: a description on how to add the [cooked-browse] shortcode to a page */
                        'desc' => sprintf(__('Create a page with the %s shortcode on it, then choose it from this dropdown.', 'cooked'), '<code>[cooked-browse]</code>'),
                        'type' => 'select',
                        'default' => 0,
                        'options' => $pages_array
                    ],
                    'recipes_per_page' => [
                        'title' => __('Recipes Per Page', 'cooked'),
                        /* translators: a description on how to choose the default number of recipes per page. */
                        'desc' => sprintf(__('Choose the default (set via the %s panel) or choose a different number here.', 'cooked'), '<a href="' . trailingslashit(get_admin_url()) . 'options-reading.php">' . __('Settings > Reading', 'cooked') . '</a>'),
                        'type' => 'select',
                        'default' => 9,
                        'options' => $recipes_per_page_array
                    ],
                    'recipe_taxonomies' => [
                        'title' => __('Recipe Taxonomies', 'cooked'),
                        'desc' => __('Choose which taxonomies you want to enable for your recipes.', 'cooked'),
                        'type' => 'checkboxes',
                        'default' => ['cp_recipe_category'],
                        'options' => apply_filters(
                            'cooked_taxonomy_options',
                            [
                                'cp_recipe_category' => __('Categories', 'cooked')
                            ]
                        )
                    ],
                    'recipe_info_display_options' => [
                        'title' => __('Global Recipe Toggles', 'cooked'),
                        'desc' => __('You can quickly hide or show different recipe elements (site-wide) with these checkboxes.', 'cooked'),
                        'type' => 'checkboxes',
                        'default' => apply_filters('cooked_recipe_info_display_options_defaults', ['author', 'taxonomies', 'difficulty_level', 'excerpt', 'timing_prep', 'timing_cook', 'timing_total', 'servings']),
                        'options' => apply_filters(
                            'cooked_recipe_info_display_options',
                            [
                                'author' => __('Author', 'cooked'),
                                'taxonomies' => __('Category', 'cooked'),
                                'difficulty_level' => __('Difficulty Level', 'cooked'),
                                'excerpt' => __('Excerpt', 'cooked'),
                                'notes' => __('Notes', 'cooked'),
                                'timing_prep' => __('Prep Time', 'cooked'),
                                'timing_cook' => __('Cook Time', 'cooked'),
                                'timing_total' => __('Total Time', 'cooked'),
                                'servings' => __('Servings', 'cooked')
                            ]
                        )
                    ],
                    'carb_format' => [
                        'title' => __('Carbs Format', 'cooked'),
                        'desc' => __('You can display carbs as "Total" or "Net".', 'cooked'),
                        'type' => 'select',
                        'default' => 'total',
                        'options' => apply_filters(
                            'cooked_settings_carb_formats',
                            [
                                'total' => __('Total Carbs', 'cooked'),
                                'net' => __('Net Carbs', 'cooked')
                            ]
                        )
                    ],
                    'author_name_format' => [
                        'title' => __('Author Name Format', 'cooked'),
                        'desc' => __('You can show the full author\'s name or just a part of it.', 'cooked'),
                        'type' => 'select',
                        'default' => 'full',
                        'options' => apply_filters(
                            'cooked_settings_author_formats',
                            [
                                'full' => __('Full name', 'cooked'),
                                'first_last_initial' => __('Full first name w/last name initial', 'cooked'),
                                'first_initial_last' => __('First name initial w/full last name', 'cooked'),
                                'first_only' => __('First name only', 'cooked')
                            ]
                        )
                    ],
                    'disable_author_links' => [
                        'title' => __('Author Links', 'cooked'),
                        'desc' => __('If you do not want the author names to link to the author recipe listings, you can disable them here.', 'cooked'),
                        'type' => 'checkboxes',
                        'color' => 'red',
                        'default' => [],
                        'options' => apply_filters(
                            'cooked_author_link_options',
                            [
                                'disabled' => __('Disable Author Links', 'cooked'),
                            ]
                        )
                    ],
                    'browse_default_cp_recipe_category' => [
                        'title' => __('Default Category', 'cooked'),
                        /* translators: a description on how to set the default recipe category for the [cooked-browse] shortcode. */
                        'desc' => sprintf(__('Optionally set the default recipe category for your %s shortcode display.', 'cooked'), '[cooked-browse]'),
                        'type' => 'select',
                        'default' => 0,
                        'options' => $categories_array
                    ],
                    'browse_default_sort' => [
                        'title' => __('Default Sort Order', 'cooked'),
                        /* translators: a description on how to set the default sort order for the [cooked-browse] shortcode. */
                        'desc' => sprintf(__('Set the default sort order for your %s shortcode display.', 'cooked'), '[cooked-browse]'),
                        'type' => 'select',
                        'default' => 'date_desc',
                        'options' => apply_filters(
                            'cooked_settings_sort_options',
                            [
                                'date_desc' => __('Newest First', 'cooked'),
                                'date_asc' => __('Oldest First', 'cooked'),
                                'title_asc' => __('Alphabetical', 'cooked'),
                                'title_desc' => __('Alphabetical (reversed)', 'cooked'),
                            ]
                        )
                    ],
                    'recipe_wp_editor_roles' => [
                        'title' => __('WP Editor Roles', 'cooked'),
                        'desc' => __('Choose which user roles can use the WP Editor for the Excerpt, Directions & Notes fields.', 'cooked'),
                        'type' => 'checkboxes',
                        'default' => apply_filters('cooked_add_recipe_wp_editor_roles_defaults', ['administrator', 'editor', 'cooked_recipe_editor']),
                        'options' => $role_options
                    ],
                    'advanced' => [
                        'title' => __('Advanced Settings', 'cooked'),
                        'desc' => '',
                        'type' => 'checkboxes',
                        'color' => 'red',
                        'class' => 'cooked-danger',
                        'default' => [],
                        'options' => apply_filters(
                            'cooked_advanced_options',
                            [
                                /* translators: an option to only show recipes with the [cooked-recipe] shortcode. */
                                'disable_public_recipes' => '<strong>' . __('Disable Public Recipes', 'cooked') . '</strong> &mdash; ' . sprintf(__('Only show recipes using the %s shortcode.', 'cooked'), '<code>[cooked-recipe]</code>'),
                                /* translators: an option to disable "meta" tags. */
                                'disable_meta_tags' => '<strong>' . sprintf(__('Disable %s Tags', 'cooked'), 'Cooked <code>&lt;meta&gt;</code>') . '</strong> &mdash; ' . __('Prevents duplicates when tags already exist.', 'cooked'),
                                'disable_servings_switcher' => '<strong>' . __('Disable "Servings Switcher"', 'cooked') . '</strong> &mdash; ' . __('Removes the servings dropdown on recipes.', 'cooked'),
                                'disable_schema_output' => '<strong>' . __('Disable Recipe Schema Output', 'cooked') . '</strong> &mdash; ' . __('You should only do this if you\'re using something else to output schema information.', 'cooked'),
                                'disable_cp_recipe_archive' => '<strong>' . __('Disable Recipe Archive Page', 'cooked') . '</strong> &mdash; ' . __('Prevents the recipe archive from being displayed.', 'cooked')
                            ]
                        )
                    ],
                ]
            ],
            'design' => [
                'name' => __('Design', 'cooked'),
                'icon' => 'pencil',
                'fields' => [
                    'dark_mode' => [
                        'title' => __('Dark Mode', 'cooked'),
                        'desc' => __('If your site has a dark background, you should enable "Dark Mode" so that Cooked can match this style.', 'cooked'),
                        'type' => 'checkboxes',
                        'default' => [],
                        'options' => apply_filters(
                            'cooked_dark_mode_options',
                            [
                                'enabled' => __('Enable "Dark Mode"', 'cooked'),
                            ]
                        )
                    ],
                    'hide_author_avatars' => [
                        'title' => __('Author Images', 'cooked'),
                        'desc' => __('If you do not want to display the author images (avatars), you can disable them here.', 'cooked'),
                        'type' => 'checkboxes',
                        'color' => 'red',
                        'default' => [],
                        'options' => apply_filters(
                            'cooked_author_image_options',
                            [
                                'hidden' => __('Hide Author Images', 'cooked'),
                            ]
                        )
                    ],
                    'main_color' => [
                        'title' => __('Main Color', 'cooked'),
                        'desc' => __('Used on buttons, cooking timer, etc.', 'cooked'),
                        'type' => 'color_field',
                        'default' => '#16a780',
                        'options' => '#16a780'
                    ],
                    'main_color_hover' => [
                        'title' => __('Main Color (on hover)', 'cooked'),
                        'desc' => __('Used when hovering over buttons.', 'cooked'),
                        'type' => 'color_field',
                        'default' => '#1b9371',
                        'options' => '#1b9371'
                    ],
                    'responsive_breakpoint_1' => [
                        'title' => __('First Responsive Breakpoint', 'cooked'),
                        'desc' => __('Set the first responsive breakpoint. Best for large tablets.', 'cooked'),
                        'type' => 'number_field',
                        'default' => '1000',
                        'options' => ''
                    ],
                    'responsive_breakpoint_2' => [
                        'title' => __('Second Responsive Breakpoint', 'cooked'),
                        'desc' => __('Set the second responsive breakpoint. Best for small tablets.', 'cooked'),
                        'type' => 'number_field',
                        'default' => '750',
                        'options' => ''
                    ],
                    'responsive_breakpoint_3' => [
                        'title' => __('Third Responsive Breakpoint', 'cooked'),
                        'desc' => __('Set the third responsive breakpoint. Best for phones and other small devices.', 'cooked'),
                        'type' => 'number_field',
                        'default' => '520',
                        'options' => ''
                    ]
                ]
            ],
            'permalinks' => [
                'name' => __('Permalinks', 'cooked'),
                'icon' => 'link-lt',
                'fields' => [
                    'recipe_permalink' => [
                        'title' => __('Recipe Permalink', 'cooked'),
                        'desc' => '',
                        'type' => 'permalink_field',
                        'options' => __('recipe-name', 'cooked'),
                        'default' => 'recipes'
                    ],
                    'recipe_author_permalink' => [
                        'title' => __('Recipe Author Permalink', 'cooked'),
                        'desc' => '',
                        'type' => 'permalink_field',
                        'options' => __('author-name', 'cooked'),
                        'default' => 'recipe-author'
                    ],
                    'recipe_category_permalink' => [
                        'title' => __('Recipe Category Permalink', 'cooked'),
                        'desc' => '',
                        'type' => 'permalink_field',
                        'options' => __('recipe-category-name', 'cooked'),
                        'default' => 'recipe-category'
                    ]
                ]
            ]
        ], $pages_array, $categories_array);
    }

    public static function per_page_array() {
        $counter = 0;
        /* translators: posts_per_page default */
        $per_page_array[] = sprintf( __('WordPress Default %s','cooked'), '(' . get_option( 'posts_per_page' ) . ')' );
        do {
            $counter++;
            $per_page_array[$counter] = $counter;
        } while ( $counter < 50 );
        $per_page_array['-1'] = __('Show All (no pagination)','cooked');

        return apply_filters( 'cooked_per_page_options', $per_page_array );
    }

    public static function pages_array( $choose_text, $none_text = false ) {
        $page_array = [];
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1]
        );

        if( !empty($pages) ) {
            $page_array[0] = $choose_text;
            foreach ($pages as $_page) {
                $page_array[$_page->ID] = $_page->post_title . ' (ID:' . $_page->ID . ')';
            }
        } elseif ( $none_text ) {
            $page_array[0] = $none_text;
        }

        return apply_filters( 'cooked_settings_pages_array', $page_array );
    }

    public static function terms_array( $term, $choose_text, $none_text = false, $hide_empty = false, $parents_only = false, $child_of = false ) {
        $terms_array = [];

        $args = [
            'taxonomy' => $term,
            'hide_empty' => $hide_empty
        ];

        if ( $parents_only ) {
            $args['parent'] = '0';
        } elseif ( $child_of ) {
            $_term = is_numeric($child_of) ? $child_of : get_term_by( 'slug', $child_of, $term );
            $term_id = is_object( $_term ) ? $_term->term_id : $_term;
            $args['parent'] = $term_id;
        }

        $terms = get_terms( $args );

        if ( !empty($terms) ) {
            if ($choose_text) {
                $terms_array[0] = $choose_text;
            }

            foreach ($terms as $_term) {
                if ( !is_array($_term) ) {
                    $terms_array[$_term->term_id] = $_term->name;
                }
            }
        } elseif ( $none_text ) {
            $terms_array[0] = $none_text;
        }

        return apply_filters( 'cooked_settings_' . $term . '_array', $terms_array );
    }

    public static function field_radio( $field_name, $options ) {
        global $_cooked_settings, $conditions;

        $counter = 1;

        echo '<p class="cooked-padded">';
            foreach ( $options as $value => $name) {
                $is_disabled = '';
                $conditional_value = '';
                $conditional_requirement = '';

                if ( is_array($name) ):
                    if ( isset($name['read_only']) && $name['read_only'] ):
                        $is_disabled = ' disabled';
                    endif;

                    if ( isset($name['conditional_value']) && $name['conditional_value'] ):
                        $conditional_value = ' v-model="' . esc_attr($name['conditional_value']) . '"';
                        if ( !in_array( $name['conditional_value'], $conditions ) ):
                            $conditions[$value] = esc_attr($name['conditional_requirement']);
                        endif;
                    endif;

                    if ( isset($name['conditional_requirement']) && $name['conditional_requirement'] ):
                        if ( is_array($name['conditional_requirement']) ):
                            $conditional_requirement = ' v-show="' . implode( ' && ', $name['conditional_requirement'] ) . '"';
                        else:
                            $conditional_requirement = ' v-show="' . esc_attr($name['conditional_requirement']) . '"';
                        endif;
                    endif;

                    $name = $name['label'];
                endif;

                $combined_extras = $is_disabled . $conditional_value;

                if ( $conditional_requirement ): echo '<transition name="fade"><span class="conditional-requirement"' . esc_attr( $conditional_requirement ) . '>'; endif;
                echo '<input' . $combined_extras . ' type="radio" id="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] == $value || isset( $_cooked_settings[$field_name][0] ) && $_cooked_settings[$field_name][0] == $value ? ' checked' : '' ) . '/>';
                echo '&nbsp;<label for="radio-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' . wp_kses_post( $name ) . '</label>';
                echo '<br>';
                if ( $conditional_requirement ): echo '</span></transition>'; endif;

                $counter++;
            }
        echo '</p>';
    }

    public static function field_select( $field_name, $options, $color = false, $field = []) {
        global $_cooked_settings;

        $is_disabled = '';

        if ( isset($field['read_only']) && $field['read_only'] ) {
            $is_disabled = ' disabled';
        }

        echo '<p>';
            echo '<select' . $is_disabled . ' name="cooked_settings[' . esc_attr( $field_name ) . ']">';
            foreach ( $options as $value => $name) {
                echo '<option value="' . esc_attr( $value ) . '"' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] == $value ? ' selected' : '' ) . '>' . esc_attr( $name ) . '</option>';
            }
            echo '</select>';
        echo '</p>';
    }

    public static function field_nonce( $field_name, $options ) {
        wp_nonce_field( $field_name, $field_name );
    }

    // Kept here for backwards compatibility only. Removed used in Cooked Pro 1.0.1
    public static function field_misc_button( $field_name, $title ) {
        echo '<p>';
            echo '<input type="submit" class="button-secondary" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $title ) . '">';
        echo '</p>';
    }
    // END

    public static function field_migrate_button( $field_name, $title ) {
        $old_recipes = get_transient('cooked_classic_recipes');

        if ($old_recipes != 'complete') {
            $total = count($old_recipes);

            if ($total > 0) {
                echo '<p>';
                    echo '<input id="cooked-migration-button" type="button" class="button-secondary" name="begin_cooked_migration" value="' . __( 'Begin Migration', 'cooked' ) . '">';
                echo '</p>';
                echo '<p>';
                    echo '<span id="cooked-migration-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-migration-progress-text" class="cooked-progress-text">0 / ' . esc_html( $total ) . '</span>';
                echo '</p>';
                echo '<p id="cooked-migration-completed"><strong>Migration Complete!</strong> You can now <a href="' . esc_url( add_query_arg(['page' => 'cooked_settings'], admin_url( 'admin.php' ) ) ) . '">' . __( 'reload', 'cooked' ) . '</a> the settings screen.</p>';
            }
        }
    }

    public static function field_text($field_name, $placeholder) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input id="cooked_field--' . esc_attr( $field_name ) . '" type="text"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
        echo '</p>';
    }

    public static function field_password($field_name, $placeholder) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input type="password"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
        echo '</p>';
    }

    public static function field_html($field_name, $html) {
        echo wp_kses_post($html);
    }

    public static function field_permalink_field($field_name, $end_of_url) {
        global $_cooked_settings;

        $home_url = get_home_url();

        if (substr($home_url, -1) !== '/') {
            $home_url .= '/';
        }

        echo '<p class="cooked-permalink-field-wrapper">';
            echo '<span>' . $home_url . '</span><input type="text" class="cooked-permalink-field" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '"><span>/' . esc_html( $end_of_url ) . '/</span>';
        echo '</p>';
    }

    public static function field_number_field( $field_name, $options ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input type="number" step="any" name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
        echo '</p>';
    }

    public static function field_color_field( $field_name, $default ) {
        global $_cooked_settings;

        echo '<p>';
            echo '<input class="cooked-color-field" type="text"' . ( $default ? ' data-default-color="' . esc_attr( $default ) . '"' : '' ) . ' name="cooked_settings[' . esc_attr( $field_name ) . ']" value="' . ( isset( $_cooked_settings[$field_name] ) && $_cooked_settings[$field_name] ? esc_attr( $_cooked_settings[$field_name] ) : '' ) . '">';
        echo '</p>';
    }

    public static function field_checkboxes( $field_name, $options, $color = false, $field = [] ) {
        global $_cooked_settings, $conditions;

        echo '<p class="cooked-padded">';
            foreach ( $options as $value => $name)  {
                $is_disabled = '';
                $conditional_value = '';
                $conditional_requirement = '';

                if ( is_array($name) ):
                    if ( isset($name['read_only']) && $name['read_only'] ):
                        $is_disabled = ' disabled';
                    endif;

                    if ( isset($name['conditional_value']) && $name['conditional_value'] ):
                        $conditional_value = ' v-model="' . esc_attr($name['conditional_value']) . '"';
                        if ( !in_array( $name['conditional_value'], $conditions ) ):
                            $conditions[$field_name][$name['conditional_value']] = $value;
                        endif;
                    endif;

                    if ( isset($name['conditional_requirement']) && $name['conditional_requirement'] ):
                        if ( is_array($name['conditional_requirement']) ):
                            $conditional_requirement = ' v-show="' . implode( ' && ', $name['conditional_requirement'] ) . '"';
                        else:
                            $conditional_requirement = ' v-show="' . esc_attr($name['conditional_requirement']) . '"';
                        endif;
                    endif;

                    $name = $name['label'];
                endif;

                $combined_extras = $is_disabled . $conditional_value;

                if ( $conditional_requirement ):
                    echo '<transition name="fade"><span class="conditional-requirement"' . esc_attr( $conditional_requirement ) . '>';
                endif;

                if ( $is_disabled ):
                    echo '<input type="hidden" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) . '">';
                    echo '<input' . $combined_extras . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) . '" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '"' . (
                        (isset( $_cooked_settings[$field_name] ) && !empty($_cooked_settings[$field_name]) && in_array( $value, $_cooked_settings[$field_name] )) ||
                        $is_disabled ||
                        (empty($_cooked_settings[$field_name]) && isset($field['default']) && in_array($value, (array)$field['default']))
                        ? ' checked' : '' ) . '/>';
                else:
                    echo '<input' . $combined_extras . ' class="cooked-switch' . ( $color ? '-' . esc_attr( $color ) : '' ) . '" type="checkbox" id="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '" name="cooked_settings[' . esc_attr( $field_name ) . '][]" value="' . esc_attr( $value ) . '"' . (
                        (isset( $_cooked_settings[$field_name] ) && !empty($_cooked_settings[$field_name]) && is_array( $_cooked_settings[$field_name] ) && in_array( $value, $_cooked_settings[$field_name] )) ||
                        $is_disabled ||
                        (empty($_cooked_settings[$field_name]) && isset($field['default']) && in_array($value, (array)$field['default']))
                        ? ' checked' : '' ) . '/>';
                endif;

                echo '&nbsp;<label for="checkbox-group-' . esc_attr( $field_name ) . '-' . esc_attr( $value ) . '">' . wp_kses_post( $name ) . '</label>';
                echo '<br>';

                if ( $conditional_requirement ):
                    echo '</span></transition>';
                endif;

            }
        echo '</p>';
    }

}
