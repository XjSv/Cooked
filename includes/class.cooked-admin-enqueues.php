<?php
/**
 * Admin Enqueues
 *
 * @package     Cooked
 * @subpackage  Admin Enqueues
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Post_Types Class
 *
 * This class handles the post type creation.
 *
 * @since 1.0.0
 */
class Cooked_Admin_Enqueues {

    public static $admin_colors;

    function __construct() {
        add_action( 'admin_enqueue_scripts', [&$this, 'admin_enqueues'], 10, 1 );
        add_action( 'admin_enqueue_scripts', [&$this, 'widget_enqueues'], 11, 1 );
        add_action( 'customize_controls_enqueue_scripts', [&$this, 'enqueue_widgets'], 10, 1 );
    }

    public static function enqueue_widgets() {
        $cooked_js_vars = [
            'rest_url' => esc_url(get_rest_url()),
        ];

        // Gonna need jQuery
        wp_enqueue_script( 'jquery' );

        // Selectize (searchable select fields)
        wp_enqueue_style( 'cooked-selectize', COOKED_URL . '/assets/admin/css/selectize/selectize.css', [], COOKED_VERSION );
        wp_enqueue_style( 'cooked-selectize-custom', COOKED_URL . '/assets/admin/css/selectize/cooked-selectize.css', [], COOKED_VERSION );
        wp_enqueue_script( 'cooked-selectize', COOKED_URL . '/assets/admin/js/selectize/selectize.min.js', ['jquery'], '0.12.6', true );
        wp_enqueue_script( 'cooked-microplugin', COOKED_URL . '/assets/admin/js/selectize/microplugin.min.js', ['jquery'], '0.0.3', true );

        // Cooked Widgets JS
        wp_register_script( 'cooked-widgets', COOKED_URL . '/assets/admin/js/cooked-widgets.js', ['jquery'], COOKED_VERSION, true );
        wp_localize_script( 'cooked-widgets', 'cooked_js_vars', $cooked_js_vars );
        wp_enqueue_script( 'cooked-widgets');
    }

    public function widget_enqueues( $hook ) {
        if ( $hook == 'widgets.php' ) {
            self::enqueue_widgets();
        }
    }

    public function admin_enqueues( $hook ) {
        global $_cooked_settings;

        $cooked_admin_hooks = [
            'index.php',
            'post-new.php',
            'post.php',
            'edit.php',
            'cooked_settings',
            'cooked_import',
            'cooked_welcome',
            'cooked_pending',
            'cooked_pro'
        ];

        $min = COOKED_DEV ? '' : '.min';

        // Required Assets for Entire Admin (icons, etc.)
        wp_enqueue_style( 'cooked-essentials', COOKED_URL . 'assets/admin/css/essentials' . $min . '.css', [], COOKED_VERSION );
        wp_enqueue_style( 'cooked-icons', COOKED_URL . 'assets/css/icons' . $min . '.css', [], COOKED_VERSION );

        $load_cooked_admin_assets = false;

        foreach ( $cooked_admin_hooks as $hook_slug ) {
            if ( strpos( $hook, $hook_slug ) || $hook_slug == $hook ) {
                $load_cooked_admin_assets = true;
            }
        }

        if ( $load_cooked_admin_assets ) {

            if (function_exists('get_current_screen')):

                $screen = get_current_screen();
                $post_type = $screen->post_type;

                if ($hook != 'post-new.php' && $hook != 'post.php' && $hook != 'index.php' && $hook != 'edit.php' || $hook === 'post-new.php' && $post_type === 'cp_recipe' || $hook === 'post.php' && $post_type === 'cp_recipe' || $hook === 'edit.php' && $post_type === 'cp_recipe' || $hook === 'index.php' || $hook === 'widgets.php'):
                    $enqueue = true;
                    add_thickbox();
                else:
                    $enqueue = false;
                endif;
            else:
                $enqueue = true;
            endif;

            if ($enqueue):

                $old_recipes = get_transient( 'cooked_classic_recipes' );
                if ( $old_recipes && $old_recipes !== 'complete' && is_array($old_recipes) ):
                    $total_old_recipes = count( $old_recipes );
                else:
                    $total_old_recipes = 0;
                endif;

                // Gonna need jQuery
                wp_enqueue_media();
                wp_enqueue_editor();
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery-ui-core' );
                wp_enqueue_script( 'jquery-ui-draggable' );
                wp_enqueue_script( 'jquery-ui-resizable' );
                wp_enqueue_script( 'jquery-ui-sortable' );
                wp_enqueue_script( 'jquery-ui-slider' );

                wp_enqueue_style( 'cooked-switchery', COOKED_URL . 'assets/admin/css/switchery/switchery.min.css', [], COOKED_VERSION );
                wp_enqueue_script( 'cooked-switchery', COOKED_URL . 'assets/admin/js/switchery/switchery.min.js', [], COOKED_VERSION, true );
                wp_enqueue_script( 'cooked-vue', COOKED_URL . 'assets/admin/js/vue/vue' . $min . '.js', [], COOKED_VERSION, false );

                $wp_editor_roles_allowed = false;
                if ( is_user_logged_in() ) {
                    $user = wp_get_current_user();
                    $user_roles = $user->roles;
                    $wp_editor_roles_allowed = isset( $_cooked_settings['recipe_wp_editor_roles'] ) && ! empty( array_intersect( $user_roles, $_cooked_settings['recipe_wp_editor_roles'] ) ) ? true : false;
                }

                $cooked_js_vars = [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'cooked_plugin_url' => COOKED_URL,
                    'time_format' => get_option('time_format', 'g:ia'),
                    'i18n_remaining' => __('remaining', 'cooked'),
                    'i18n_image_title' => __('Add Image', 'cooked'),
                    'i18n_image_change' => __('Change Image', 'cooked'),
                    'i18n_image_button' => __('Use this Image', 'cooked'),
                    'i18n_gallery_image_title' => __('Add to Gallery', 'cooked'),
                    'i18n_edit_image_title' => __('Edit Gallery Item', 'cooked'),
                    'i18n_edit_image_button' => __('Update Gallery Item', 'cooked'),
                    'i18n_saved' => __('Saved', 'cooked'),
                    'i18n_applied' => __('Applied', 'cooked'),
                    'i18n_confirm_save_default_all' => __('Are you sure you want to apply this new template to all of your recipes?', 'cooked'),
                    'i18n_confirm_load_default' => __('Are you sure you want to reset this recipe template to the Cooked plugin default?', 'cooked'),
                    /* translators: confirmation for migrating all ### recipes, where ### displays the total number for the migration. */
                    'i18n_confirm_migrate_recipes' => sprintf(__('Please confirm that you are ready to migrate all %s recipes.', 'cooked'), number_format($total_old_recipes)),
                    'i18n_confirm_import_recipes' => __('Please confirm that you are ready to import all recipes.', 'cooked'),
                    'i18n_confirm_csv_import' => __('Are you sure you want to import recipes from this CSV file?', 'cooked'),
                    'i18n_csv_no_file' => __('Please select a CSV file.', 'cooked'),
                    'i18n_csv_invalid_file' => __('Please select a valid CSV file.', 'cooked'),
                    'i18n_uploading' => __('Uploading...', 'cooked'),
                    'i18n_processing' => __('Processing...', 'cooked'),
                    'i18n_recipes_imported' => __('recipes imported', 'cooked'),
                    'i18n_errors' => __('Errors:', 'cooked'),
                    'i18n_import_failed' => __('Import failed.', 'cooked'),
                    'i18n_failed_process_csv' => __('Failed to process CSV file.', 'cooked'),
                    'i18n_failed_upload_csv' => __('Failed to upload CSV file.', 'cooked'),
                    'i18n_file_upload_failed' => __('File upload failed.', 'cooked'),
                    'i18n_something_wrong' => __('Something went wrong', 'cooked'),
                    'i18n_hrs' => __('hrs', 'cooked'),
                    'i18n_mins' => __('mins', 'cooked'),
                    'i18n_confirm_calculate_related' => __('Pre-calculate related recipes for all published recipes? This may take a while on large sites.', 'cooked'),
                    /* translators: 1: date and time, 2: number of recipes */
                    'i18n_last_calculated' => __( 'Last: %1$s · %2$s recipes', 'cooked' ),
                    'wp_editor_roles_allowed' => esc_attr($wp_editor_roles_allowed),
                ];

                // Cooked Admin Style Assets
                wp_register_script( 'cooked-functions', COOKED_URL . 'assets/admin/js/cooked-functions' . $min . '.js', ['jquery'], COOKED_VERSION, true );
                wp_register_script( 'cooked-migration', COOKED_URL . 'assets/admin/js/cooked-migration' . $min . '.js', ['jquery'], COOKED_VERSION, true );
                wp_enqueue_style( 'cooked-admin', COOKED_URL . 'assets/admin/css/style' . $min . '.css', [], COOKED_VERSION );
                wp_enqueue_style( 'wp-color-picker' );

                // Tooltipster
                wp_enqueue_script('cooked-tooltipster', COOKED_URL . 'assets/admin/js/tooltipster/tooltipster.bundle.min.js', ['jquery'], COOKED_VERSION, true );
                wp_enqueue_style('cooked-tooltipster-core', COOKED_URL . 'assets/admin/css/tooltipster/tooltipster.bundle.min.css', [], COOKED_VERSION, 'screen' );
                wp_enqueue_style('cooked-tooltipster-theme', COOKED_URL . 'assets/admin/css/tooltipster/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-cooked' . $min . '.css', [], COOKED_VERSION, 'screen' );

                // Cooked Admin Script
                wp_localize_script('cooked-functions', 'cooked_functions_js_vars', $cooked_js_vars );
                wp_localize_script('cooked-migration', 'cooked_migration_js_vars', $cooked_js_vars );
                wp_enqueue_script('cooked-functions');
                wp_enqueue_script('cooked-migration');
            endif;
        }
    }

}
