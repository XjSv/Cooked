<?php
/**
 * Cooked Class Aliases
 *
 * Keeps the pre-2.0 Cooked_* class names working after a class moves to the
 * Cooked\ namespace. The aliases are public API: never remove one.
 *
 * @package     Cooked
 * @subpackage  Aliases
 * @since       2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register(
    static function ( string $class_name ): void {
        static $aliases = [
            'Cooked_Unit_Converter'       => \Cooked\Measurement\UnitConverter::class,
            'Cooked_Measurements'         => \Cooked\Measurement\Measurements::class,
            'Cooked_Roles'                => \Cooked\User\Roles::class,
            'Cooked_Users'                => \Cooked\User\Users::class,
            'Cooked_Taxonomies'           => \Cooked\Recipe\Taxonomies::class,
            'Cooked_Allergens'            => \Cooked\Recipe\Allergens::class,
            'Cooked_Import'               => \Cooked\Import\Import::class,
            'Cooked_Delicious_Recipes'    => \Cooked\Import\DeliciousRecipes::class,
            'Cooked_Recipe_Maker_Recipes' => \Cooked\Import\RecipeMaker::class,
            'Cooked_CSV_Import'           => \Cooked\Import\CsvImport::class,
            'Cooked_Gutenberg'            => \Cooked\Integrations\Gutenberg::class,
            'Cooked_Elementor'            => \Cooked\Integrations\Elementor::class,
            'Cooked_Pixwell'              => \Cooked\Integrations\Pixwell::class,
            'Cooked_Multilingual'         => \Cooked\Integrations\Multilingual::class,
            'Cooked_YoastSEO'             => \Cooked\Integrations\YoastSeo::class,
            'Cooked_RankMathSEO'          => \Cooked\Integrations\RankMathSeo::class,
            'Cooked_CLI_Tools_Command'    => \Cooked\System\Cli::class,
            'Cooked_Admin_Menus'          => \Cooked\Admin\Menus::class,
            'Cooked_Admin_Enqueues'       => \Cooked\Admin\Enqueues::class,
            'Cooked_Migration'            => \Cooked\Admin\Migration::class,
            'Cooked_Plugin_Extra'         => \Cooked\Admin\PluginExtra::class,
            'Cooked_Enqueues'             => \Cooked\Frontend\Enqueues::class,
            'Cooked_Updates'              => \Cooked\Admin\Updates::class,
            'Cooked_Settings'             => \Cooked\Admin\Settings::class,
            'Cooked_Shortcodes'           => \Cooked\Frontend\Shortcodes::class,
            'Cooked_Ajax'                 => \Cooked\System\Ajax::class,
        ];

        if ( isset( $aliases[ $class_name ] ) ) {
            class_alias( $aliases[ $class_name ], $class_name );
        }
    }
);
