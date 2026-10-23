<?php
/**
 * Register Import
 *
 * @package     Cooked
 * @subpackage  Import
 * @since       1.8.2
 */

namespace Cooked\Import;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import Class
 *
 * This class handles the import of recipes from other plugins.
 *
 * @since 1.8.2
 */
class Import {

    /**
     * Registers the import settings hooks.
     */
    public function __construct() {
        add_action( 'admin_init', [ &$this, 'init' ] );
        add_action( 'init', [ &$this, 'init' ] );
    }

    /**
     * Registers the import settings with the Settings API.
     *
     * @return void
     */
    public static function init() {
        register_setting( 'cooked_import_group', 'cooked_import', [ 'sanitize_callback' => [ 'Cooked_Import', 'sanitize_saved_flag' ] ] );
        register_setting( 'cooked_import_group', 'cooked_import_saved', [ 'sanitize_callback' => [ 'Cooked_Import', 'sanitize_saved_flag' ] ] );
    }

    /**
     * Sanitizes the "import saved" flag.
     *
     * @param mixed $value Submitted value.
     * @return bool
     */
    public static function sanitize_saved_flag( $value ) {
        return rest_sanitize_boolean( $value );
    }

    /**
     * The Import tabs (one per importer) and their fields.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function tabs_fields() {
        $Cooked_Delicious_Recipes          = new \Cooked_Delicious_Recipes();
        $delicious_recipes                 = $Cooked_Delicious_Recipes::get_recipes();
        $cooked_delicious_recipes_imported = get_option( 'cooked_delicious_recipes_imported', false );

        $Cooked_Recipe_Maker_Recipes     = new \Cooked_Recipe_Maker_Recipes();
        $wp_recipe_maker_recipes         = $Cooked_Recipe_Maker_Recipes::get_recipes();
        $cooked_wp_recipe_maker_imported = get_option( 'cooked_wp_recipe_maker_imported', false );

        $import_tabs = [];
        if ( ! empty( $delicious_recipes ) ) {
            $total = count( $delicious_recipes );

            /* translators: for displaying singular or plural versions depending on the number of recipes. */
            $html_desc      = sprintf( esc_html( _n( 'There is %1$s recipe that should be imported from %2$s.', 'There are %1$s recipes that should be imported from %2$s.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong>WP Delicious (formerly Delicious Recipes)</strong>' );
            $html_desc     .= '<br>';
            $html_desc     .= __( 'Before you begin, please make sure you <b>backup your database</b> in case something goes wrong.', 'cooked' );
            $html_desc     .= '<br>';
            $html_desc     .= __( 'Click the button below to import these recipes. Here is what will happen to your recipes:', 'cooked' );
            $html_desc     .= '<ul class="cooked-admin-ul">';
                $html_desc .= '<li>' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Comments and ratings data will also be imported (ratings are available in Cooked Pro).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The existing WP Delicious recipes and data will not be modified or deleted.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Certain data that is not suppoted by Cooked will not be imported (such as Cooking Temp, Estimated Cost, Recipe Keywords, etc).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'You can run the import multiple times, but keep in mind that duplicate recipes will be created.', 'cooked' ) . '</li>';
            $html_desc     .= '</ul>';

            if ( $total > 2000 ) {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Wow, you have a lot of recipes!', 'cooked' ) . '</strong><br><em style="color:#333;">' . __( 'It is definitely recommended that you get yourself a cup of coffee or tea after clicking this button.', 'cooked' ) . '</em></p>';
            } else {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'The more recipes you have, the longer this will take.', 'cooked' ) . '</p>';
            }

            if ( $total > 0 ) {
                $import_tabs['delicious_recipes_import'] = [
                    'name'   => __( 'WP Delicious - Import', 'cooked' ),
                    'icon'   => 'migrate',
                    'fields' => [
                        'cooked_import_button' => [
                            'title'       => 'WP Delicious (formerly Delicious Recipes) <i class="cooked-icon cooked-icon-angle-right"></i> Cooked',
                            'desc'        => $html_desc,
                            'type'        => 'import_button',
                            'total'       => $total,
                            'import_type' => $Cooked_Delicious_Recipes->import_type,
                        ],
                    ],
                ];
            }
        } else {
            $import_tabs['no_delicious_recipes'] = [
                'name'   => __( 'WP Delicious - Import', 'cooked' ),
                'icon'   => 'migrate',
                'fields' => [
                    'cooked_no_delicious_recipes' => [
                        'title'   => 'WP Delicious (formerly Delicious Recipes)',
                        'desc'    => '',
                        'type'    => 'message',
                        'message' => 'There are no recipes to import from WP Delicious.',
                    ],
                ],
            ];
        }

        if ( ! empty( $wp_recipe_maker_recipes ) ) {
            $total = count( $wp_recipe_maker_recipes );

            /* translators: for displaying singular or plural versions depending on the number of recipes. */
            $html_desc      = sprintf( esc_html( _n( 'There is %1$s recipe that should be imported from %2$s.', 'There are %1$s recipes that should be imported from %2$s.', $total, 'cooked' ) ), '<strong>' . number_format( $total ) . '</strong>', '<strong>WP Recipe Maker</strong>' );
            $html_desc     .= '<br>';
            $html_desc     .= __( 'Before you begin, please make sure you <b>backup your database</b> in case something goes wrong.', 'cooked' );
            $html_desc     .= '<br>';
            $html_desc     .= __( 'Click the button below to import these recipes. Here is what will happen to your recipes:', 'cooked' );
            $html_desc     .= '<ul class="cooked-admin-ul">';
                $html_desc .= '<li>' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Comments and ratings data will also be imported (ratings are available in Cooked Pro).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The difficulty level will be set to <b>Beginner</b> since it is not supported by WP Recipe Maker.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'The existing WP Recipe Maker and data will not be modified or deleted.', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'Certain data that is not suppoted by Cooked will not be imported (such as Cooking Temp, Estimated Cost, Recipe Keywords, etc).', 'cooked' ) . '</li>';
                $html_desc .= '<li>' . __( 'You can run the import multiple times, but keep in mind that duplicate recipes will be created.', 'cooked' ) . '</li>';
            $html_desc     .= '</ul>';

            if ( $total > 2000 ) {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Wow, you have a lot of recipes!', 'cooked' ) . '</strong><br><em style="color:#333;">' . __( 'It is definitely recommended that you get yourself a cup of coffee or tea after clicking this button.', 'cooked' ) . '</em></p>';
            } else {
                $html_desc .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'The more recipes you have, the longer this will take.', 'cooked' ) . '</p>';
            }

            if ( $total > 0 ) {
                $import_tabs['wp_recipe_maker_import'] = [
                    'name'   => __( 'WP Recipe Maker - Import', 'cooked' ),
                    'icon'   => 'migrate',
                    'fields' => [
                        'cooked_import_button' => [
                            'title'       => 'WP Recipe Maker <i class="cooked-icon cooked-icon-angle-right"></i> Cooked',
                            'desc'        => $html_desc,
                            'type'        => 'import_button',
                            'total'       => $total,
                            'import_type' => $Cooked_Recipe_Maker_Recipes->import_type,
                        ],
                    ],
                ];
            }
        } else {
            $import_tabs['no_wp_recipe_maker_recipes'] = [
                'name'   => __( 'WP Recipe Maker - Import', 'cooked' ),
                'icon'   => 'migrate',
                'fields' => [
                    'cooked_no_wp_recipe_maker_recipes' => [
                        'title'   => 'WP Recipe Maker',
                        'desc'    => '',
                        'type'    => 'message',
                        'message' => 'There are no recipes to import from WP Recipe Maker.',
                    ],
                ],
            ];
        }

        // CSV Import Tab.
        $html_desc      = __( 'Import recipes from a CSV file. Your CSV file should include the following columns:', 'cooked' );
        $html_desc     .= '<ul class="cooked-admin-ul">';
        $html_desc     .= '<li><strong>title</strong> - ' . __( 'Recipe title (required)', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>excerpt</strong> - ' . __( 'Excerpt/description', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>prep_time</strong> - ' . __( 'Prep time in minutes', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>cook_time</strong> - ' . __( 'Cook time in minutes', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>difficulty_level</strong> - ' . __( 'Difficulty level (1=Beginner, 2=Intermediate, 3=Advanced)', 'cooked' ) . '</li>';
        $html_desc     .= '<li style="line-height: 1.6;"><strong>ingredients</strong> - ' . __( 'Ingredients, separated by pipe <code>(|)</code>. <br>Format: <code>"amount|measurement|name"</code> or <code>"name"</code> for simple ingredients.<br>Add substitutions with double pipe <code>(||)</code>: <code>"amount|measurement|name||sub_amount|sub_measurement|sub_name"</code>. Add more with another double pipe.', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>directions</strong> - ' . __( 'Directions/instructions, separated by pipe <code>(|)</code>', 'cooked' ) . '</li>';
        $html_desc     .= '<li><strong>notes</strong> - ' . __( 'Notes', 'cooked' ) . '</li>';
            $html_desc .= '<li><strong>category</strong> - ' . __( 'Category, separated by comma', 'cooked' ) . '</li>';
		if ( defined( 'COOKED_PRO_VERSION' ) ) {
			$html_desc .= '<li><strong>cuisine</strong> - ' . __( 'Cuisine, separated by comma', 'cooked' ) . '</li>';
			$html_desc .= '<li><strong>cooking_method</strong> - ' . __( 'Cooking method, separated by comma', 'cooked' ) . '</li>';
			$html_desc .= '<li><strong>tags</strong> - ' . __( 'Tags, separated by comma', 'cooked' ) . '</li>';
			$html_desc .= '<li><strong>diet</strong> - ' . __( 'Restricted diet type (Schema.org RestrictedDiet), separated by comma', 'cooked' ) . '</li>';
		}
            $html_desc .= '</ul>';
        $html_desc     .= '<p class="cooked-import-note"><strong>' . __( 'Note:', 'cooked' ) . '</strong> ' . __( 'Recipes will be imported with the <b>\'Draft\'</b> status. After the import is complete, you can bulk edit the recipes and change their status to <b>\'Published\'</b>.', 'cooked' ) . '</p>';

        $import_tabs['csv_import'] = [
            'name'   => __( 'CSV Import', 'cooked' ),
            'icon'   => 'migrate',
            'fields' => [
                'cooked_csv_import' => [
                    'title' => __( 'Import Recipes via CSV', 'cooked' ),
                    'desc'  => $html_desc,
                    'type'  => 'csv_upload',
                ],
            ],
        ];

        $import_tabs['more_imports_coming_soon'] = [
            'name'   => __( 'More Imports are Coming Soon...', 'cooked' ),
            'icon'   => 'migrate',
            'fields' => [
                'cooked_more_imports_coming_soon' => [
                    'title'   => 'More Imports are Coming Soon...',
                    'desc'    => '',
                    'type'    => 'message',
                    'message' => 'More Imports are Coming Soon...',
                ],
            ],
        ];

        return apply_filters( 'cooked_import_tabs_fields', $import_tabs );
    }

    /**
     * Renders the "Begin Import" button and progress bar for an importer.
     *
     * @param string               $name          Field key.
     * @param mixed                $field_options Unused.
     * @param mixed                $color         Unused.
     * @param array<string, mixed> $field         Field definition.
     * @return void
     */
    public static function field_import_button( $name, $field_options, $color, $field ) {
        $total       = $field['total'];
        $import_type = $field['import_type'];

        if ( $total > 0 ) {
            echo '<p>';
                echo '<input id="cooked-import-button" type="button" class="button-secondary" data-import-type="' . esc_attr( $import_type ) . '" name="begin_cooked_migration" value="' . esc_attr__( 'Begin Import', 'cooked' ) . '">';
            echo '</p>';
            echo '<p>';
                echo '<span id="cooked-import-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-import-progress-text" class="cooked-progress-text">0 / ' . esc_html( $total ) . '</span>';
            echo '</p>';
            echo '<p id="cooked-import-completed"><strong>Import Complete!</strong> You can now <a href="' . esc_url( add_query_arg( [ 'page' => 'cooked_import' ], esc_url( admin_url( 'admin.php' ) ) ) ) . '">' . esc_html__( 'reload', 'cooked' ) . '</a> the import screen.</p>';
        }
    }

    /**
     * Outputs a message field.
     *
     * @param string               $name          Field key.
     * @param mixed                $field_options Unused.
     * @param mixed                $color         Unused.
     * @param array<string, mixed> $field         Field definition.
     * @return void
     */
    public static function field_message( $name, $field_options, $color, $field ) {
        echo '<p>' . wp_kses_post( $field['message'] ) . '</p>';
    }

    /**
     * Renders the CSV upload form and the sample files.
     *
     * @param string $name Field key.
     * @return void
     */
    public static function field_csv_upload( $name ) {
        $samples = [
            'recipes-small.csv'  => __( 'Small (1 recipe)', 'cooked' ),
            'recipes-medium.csv' => __( 'Medium (3 recipes)', 'cooked' ),
            'recipes-large.csv'  => __( 'Large (10 recipes)', 'cooked' ),
        ];
        $links   = [];
        foreach ( $samples as $file => $label ) {
            // Downloads; in the admin frame a click opens a preview instead (cooked-admin-app.js).
            $links[] = '<a href="' . esc_url( COOKED_URL . 'sample-data/' . $file ) . '" class="cooked-csv-sample" download="' . esc_attr( $file ) . '">' . esc_html( $label ) . '</a>';
        }
        $label = \Cooked\Admin\Enqueues::use_classic_ui() ? __( 'Download sample CSV files:', 'cooked' ) : __( 'Preview a sample CSV file:', 'cooked' );
        echo '<p class="cooked-import-note cooked-csv-samples"><strong>' . esc_html( $label ) . '</strong> ' . implode( ', ', $links ) . '</p>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
        echo '<form id="cooked-csv-import-form" enctype="multipart/form-data">';
        echo '<p>';
        echo '<input type="file" id="cooked-csv-file" name="csv_file" accept=".csv" required aria-labelledby="' . esc_attr( \Cooked_Settings::title_id( $name ) ) . '">';
        echo '</p>';
        echo '<p>';
        echo '<input id="cooked-csv-import-button" type="button" class="button-primary" value="' . esc_attr__( 'Upload and Import CSV', 'cooked' ) . '">';
        echo '</p>';
        echo '<p>';
        echo '<span id="cooked-csv-import-progress" class="cooked-progress"><span class="cooked-progress-bar"></span></span><span id="cooked-csv-import-progress-text" class="cooked-progress-text"></span>';
        echo '</p>';
        echo '<p id="cooked-csv-import-completed" style="display:none;"><strong>' . esc_html__( 'Import Complete!', 'cooked' ) . '</strong> ' . esc_html__( 'You can now', 'cooked' ) . ' <a href="' . esc_url( add_query_arg( [ 'page' => 'cooked_import' ], admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'reload', 'cooked' ) . '</a> ' . esc_html__( 'the import screen or', 'cooked' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=cp_recipe' ) ) . '">' . esc_html__( 'view your recipes', 'cooked' ) . '</a>.</p>';
        echo '<div id="cooked-csv-import-errors" style="display:none; color: #d63638; margin-top: 10px;"></div>';
        echo '</form>';
    }
}
