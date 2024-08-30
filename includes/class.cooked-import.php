<?php
/**
 * Register Import
 *
 * @package     Cooked
 * @subpackage  Import
 * @since       1.0.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Import Class
 *
 * This class handles the import of recipes from other plugins.
 *
 * @since 1.0.0
 */
class Cooked_Import {

    public function __construct() {
        add_filter( 'admin_init', [&$this, 'init'] );
        add_filter( 'init', [&$this, 'init'] );
        add_action( 'save_post', [&$this, 'browse_page_saved'], 10, 1 );
    }

    public static function init() {
        register_setting( 'cooked_import_group', 'cooked_import' );
        register_setting( 'cooked_import_group', 'cooked_import_saved' );
    }

    public static function tabs_fields() {
        $Cooked_Delicious_Recipes = new Cooked_Delicious_Recipes();
        $delicious_recipes = $Cooked_Delicious_Recipes::get_recipes();
        $cooked_delicious_recipes_imported = get_option( 'cooked_delicious_recipes_imported', false );

        $import_tabs = [];
        if (!empty($delicious_recipes)) {
            $total = count($delicious_recipes);

            /* translators: for displaying singular or plural versions depending on the number of recipes. */
            $html_desc = sprintf( esc_html( _n( 'There is %1$s recipe that should be imported from %2$s.', 'There are %1$s recipes that should be imported from %2$s.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong>WP Delicious (formerly Delicious Recipes)</strong>' );
            $html_desc .= '<br>';
            $html_desc .= __( 'Before you begin, please make sure you <b>backup your database</b> in case something goes wrong.', 'cooked' );
            $html_desc .= '<br>';
            $html_desc .= __( 'Click the button below to import these recipes. Here is what will happen to your recipes:', 'cooked' );
            $html_desc .= '<ul class="cooked-admin-ul">';
                $html_desc .= '<li>' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Comments and ratings data will also be imported (ratings are available in Cooked Pro).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The existing WP Delicious recipes and data will not be modified or deleted.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Certain data that is not suppoted by Cooked will not be imported (such as Cooking Temp, Estimated Cost, Recipe Keywords, etc).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'You can run the import multiple times, but keep in mind that duplicate recipes will be created.', 'cooked' ) . '</li>';
            $html_desc .= '</ul>';

            if ($total > 2000) {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Wow, you have a lot of recipes!', 'cooked' ) . '</strong><br><em style="color:#333;">' . __( 'It is definitely recommended that you get yourself a cup of coffee or tea after clicking this button.', 'cooked' ) . '</em></p>';
            } else {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'The more recipes you have, the longer this will take.', 'cooked' ) . '</p>';
            }

            if ($total > 0) {
                $import_tabs['delicious_recipes_import'] = [
                    'name' => __('WP Delicious - Import', 'cooked'),
                    'icon' => 'migrate',
                    'fields' => [
                        'cooked_import_button' => [
                            'title' => 'WP Delicious (formerly Delicious Recipes)&nbsp;&nbsp;<i class="cooked-icon cooked-icon-angle-right"></i>&nbsp;&nbsp;Cooked',
                            'desc' => $html_desc,
                            'type' => 'import_button',
                            'total' => $total,
                            'import_type' => $Cooked_Delicious_Recipes->import_type,
                        ]
                    ]
                ];
            }
        } else {
            $import_tabs['no_delicious_recipes'] = [
                'name' => __('WP Delicious - Import', 'cooked'),
                'icon' => 'migrate',
                'fields' => [
                    'cooked_no_delicious_recipes' => [
                        'title' => 'WP Delicious (formerly Delicious Recipes)',
                        'desc' => '',
                        'type' => 'message',
                        'message' => 'There are no recipes to import from WP Delicious.',
                    ]
                ]
            ];
        }

        $import_tabs['more_imports_coming_soon'] = [
            'name' => __('More Imports are Coming Soon...', 'cooked'),
            'icon' => 'migrate',
            'fields' => [
                'cooked_no_delicious_recipes' => [
                    'title' => 'More Imports are Coming Soon...',
                    'desc' => '',
                    'type' => 'message',
                    'message' => 'More Imports are Coming Soon...',
                ]
            ]
        ];

        return apply_filters('cooked_import_tabs_fields', $import_tabs);
    }

    public static function field_import_button( $name, $field_options, $color, $field ) {
        $total = $field['total'];
        $import_type = $field['import_type'];

        if ($total > 0) {
            echo '<p>';
                echo '<input id="cooked-import-button" type="button" class="button-secondary" data-import-type="' . esc_attr( $import_type ) . '" name="begin_cooked_migration" value="' . __( 'Begin Import', 'cooked' ) . '">';
            echo '</p>';
            echo '<p>';
                echo '<span id="cooked-import-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-import-progress-text" class="cooked-progress-text">0 / ' . esc_html( $total ) . '</span>';
            echo '</p>';
            echo '<p id="cooked-import-completed"><strong>Import Complete!</strong> You can now <a href="' . esc_url( add_query_arg(['page' => 'cooked_import'], admin_url( 'admin.php' ) ) ) . '">' . __( 'reload', 'cooked' ) . '</a> the import screen.</p>';
        }
    }

    public static function field_message( $name, $field_options, $color, $field ) {
        echo '<p>' . $field['message'] . '</p>';
    }

}
