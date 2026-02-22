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
    }

    public static function init() {
        register_setting( 'cooked_import_group', 'cooked_import' );
        register_setting( 'cooked_import_group', 'cooked_import_saved' );
    }

    public static function tabs_fields() {
        $Cooked_Delicious_Recipes = new Cooked_Delicious_Recipes();
        $delicious_recipes = $Cooked_Delicious_Recipes::get_recipes();
        $cooked_delicious_recipes_imported = get_option( 'cooked_delicious_recipes_imported', false );

        $Cooked_Recipe_Maker_Recipes = new Cooked_Recipe_Maker_Recipes();
        $wp_recipe_maker_recipes = $Cooked_Recipe_Maker_Recipes::get_recipes();
        $cooked_wp_recipe_maker_imported = get_option( 'cooked_wp_recipe_maker_imported', false );

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

        if (!empty($wp_recipe_maker_recipes)) {
            $total = count($wp_recipe_maker_recipes);

            /* translators: for displaying singular or plural versions depending on the number of recipes. */
            $html_desc = sprintf( esc_html( _n( 'There is %1$s recipe that should be imported from %2$s.', 'There are %1$s recipes that should be imported from %2$s.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong>WP Recipe Maker</strong>' );
            $html_desc .= '<br>';
            $html_desc .= __( 'Before you begin, please make sure you <b>backup your database</b> in case something goes wrong.', 'cooked' );
            $html_desc .= '<br>';
            $html_desc .= __( 'Click the button below to import these recipes. Here is what will happen to your recipes:', 'cooked' );
            $html_desc .= '<ul class="cooked-admin-ul">';
                $html_desc .= '<li>' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Comments and ratings data will also be imported (ratings are available in Cooked Pro).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The difficulty level will be set to <b>Beginner</b> since it is not supported by WP Recipe Maker.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The existing WP Recipe Maker and data will not be modified or deleted.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Certain data that is not suppoted by Cooked will not be imported (such as Cooking Temp, Estimated Cost, Recipe Keywords, etc).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'You can run the import multiple times, but keep in mind that duplicate recipes will be created.', 'cooked' ) . '</li>';
            $html_desc .= '</ul>';

            if ($total > 2000) {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Wow, you have a lot of recipes!', 'cooked' ) . '</strong><br><em style="color:#333;">' . __( 'It is definitely recommended that you get yourself a cup of coffee or tea after clicking this button.', 'cooked' ) . '</em></p>';
            } else {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'The more recipes you have, the longer this will take.', 'cooked' ) . '</p>';
            }

            if ($total > 0) {
                $import_tabs['wp_recipe_maker_import'] = [
                    'name' => __('WP Recipe Maker - Import', 'cooked'),
                    'icon' => 'migrate',
                    'fields' => [
                        'cooked_import_button' => [
                            'title' => 'WP Recipe Maker&nbsp;&nbsp;<i class="cooked-icon cooked-icon-angle-right"></i>&nbsp;&nbsp;Cooked',
                            'desc' => $html_desc,
                            'type' => 'import_button',
                            'total' => $total,
                            'import_type' => $Cooked_Recipe_Maker_Recipes->import_type,
                        ]
                    ]
                ];
            }
        } else {
            $import_tabs['no_wp_recipe_maker_recipes'] = [
                'name' => __('WP Recipe Maker - Import', 'cooked'),
                'icon' => 'migrate',
                'fields' => [
                    'cooked_no_wp_recipe_maker_recipes' => [
                        'title' => 'WP Recipe Maker',
                        'desc' => '',
                        'type' => 'message',
                        'message' => 'There are no recipes to import from WP Recipe Maker.',
                    ]
                ]
            ];
        }

        // CSV Import Tab
        $html_desc = __( 'Import recipes from a CSV file. Your CSV file should include the following columns:', 'cooked' );
        $html_desc .= '<ul class="cooked-admin-ul">';
        $html_desc .= '<li><strong>title</strong> - ' . __( 'Recipe title (required)', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>excerpt</strong> - ' . __( 'Excerpt/description', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>prep_time</strong> - ' . __( 'Prep time in minutes', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>cook_time</strong> - ' . __( 'Cook time in minutes', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>difficulty_level</strong> - ' . __( 'Difficulty level (1=Beginner, 2=Intermediate, 3=Advanced)', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>ingredients</strong> - ' . __( 'Ingredients, separated by pipe (|). Format: "amount|measurement|name" or "name" for simple ingredients. Add substitutions with double pipe (||): "amount|measurement|name||sub_amount|sub_measurement|sub_name"', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>directions</strong> - ' . __( 'Directions/instructions, separated by pipe (|)', 'cooked' ) . '</li>';
        $html_desc .= '<li><strong>notes</strong> - ' . __( 'Notes', 'cooked' ) . '</li>';
            $html_desc .= '<li><strong>category</strong> - ' . __( 'Category, separated by comma', 'cooked' ) . '</li>';
            if ( defined('COOKED_PRO_VERSION') ) {
                $html_desc .= '<li><strong>cuisine</strong> - ' . __( 'Cuisine, separated by comma', 'cooked' ) . '</li>';
                $html_desc .= '<li><strong>cooking_method</strong> - ' . __( 'Cooking method, separated by comma', 'cooked' ) . '</li>';
                $html_desc .= '<li><strong>tags</strong> - ' . __( 'Tags, separated by comma', 'cooked' ) . '</li>';
                $html_desc .= '<li><strong>diet</strong> - ' . __( 'Restricted diet type (Schema.org RestrictedDiet), separated by comma', 'cooked' ) . '</li>';
            }
            $html_desc .= '</ul>';
        $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status. After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</p>';

        $import_tabs['csv_import'] = [
            'name' => __('CSV Import', 'cooked'),
            'icon' => 'migrate',
            'fields' => [
                'cooked_csv_import' => [
                    'title' => __('Import Recipes via CSV', 'cooked'),
                    'desc' => $html_desc,
                    'type' => 'csv_upload',
                ]
            ]
        ];

        $import_tabs['more_imports_coming_soon'] = [
            'name' => __('More Imports are Coming Soon...', 'cooked'),
            'icon' => 'migrate',
            'fields' => [
                'cooked_more_imports_coming_soon' => [
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

    public static function field_csv_upload( $name, $field_options, $color, $field ) {
        echo '<form id="cooked-csv-import-form" enctype="multipart/form-data">';
        echo '<p>';
        echo '<input type="file" id="cooked-csv-file" name="csv_file" accept=".csv" required>';
        echo '</p>';
        echo '<p>';
        echo '<input id="cooked-csv-import-button" type="button" class="button-primary" value="' . __( 'Upload and Import CSV', 'cooked' ) . '">';
        echo '</p>';
        echo '<p>';
        echo '<span id="cooked-csv-import-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-csv-import-progress-text" class="cooked-progress-text"></span>';
        echo '</p>';
        echo '<p id="cooked-csv-import-completed" style="display:none;"><strong>' . __( 'Import Complete!', 'cooked' ) . '</strong> ' . __( 'You can now', 'cooked' ) . ' <a href="' . esc_url( add_query_arg(['page' => 'cooked_import'], admin_url( 'admin.php' ) ) ) . '">' . __( 'reload', 'cooked' ) . '</a> ' . __( 'the import screen or', 'cooked' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=cp_recipe' ) ) . '">' . __( 'view your recipes', 'cooked' ) . '</a>.</p>';
        echo '<div id="cooked-csv-import-errors" style="display:none; color: #d63638; margin-top: 10px;"></div>';
        echo '</form>';
    }

}
