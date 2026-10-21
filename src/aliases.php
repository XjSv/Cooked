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
            'Cooked_Unit_Converter' => \Cooked\Measurement\UnitConverter::class,
            'Cooked_Measurements'   => \Cooked\Measurement\Measurements::class,
            'Cooked_Roles'          => \Cooked\User\Roles::class,
            'Cooked_Users'          => \Cooked\User\Users::class,
            'Cooked_Taxonomies'     => \Cooked\Recipe\Taxonomies::class,
            'Cooked_Allergens'      => \Cooked\Recipe\Allergens::class,
            'Cooked_Import'         => \Cooked\Import\Import::class,
        ];

        if ( isset( $aliases[ $class_name ] ) ) {
            class_alias( $aliases[ $class_name ], $class_name );
        }
    }
);
