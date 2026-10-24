<?php
/**
 * Cooked Unit Converter
 *
 * @package     Cooked
 * @subpackage  Unit Converter
 * @since       1.15.0
 */

namespace Cooked\Measurement;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhpUnitsOfMeasure\PhysicalQuantity\Volume;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

/**
 * UnitConverter Class
 *
 * Converts ingredient amounts between measurement units using the
 * php-units-of-measure library.
 *
 * @since 1.15.0
 */
class UnitConverter {

    /**
     * Maps Cooked unit keys to [PhysicalQuantity class, library unit name].
     *
     * @since 1.15.0
     * @var array<string, array{0: class-string<\PhpUnitsOfMeasure\AbstractPhysicalQuantity>, 1: string}>
     */
    private static $unit_map = [
        'cup'  => [ Volume::class, 'cup' ],
        'tsp'  => [ Volume::class, 'tsp' ],
        'tbsp' => [ Volume::class, 'tbsp' ],
        'floz' => [ Volume::class, 'fl oz' ],
        'gal'  => [ Volume::class, 'gal' ],
        'pt'   => [ Volume::class, 'pt' ],
        'qt'   => [ Volume::class, 'qt' ],
        'dl'   => [ Volume::class, 'dl' ],
        'ml'   => [ Volume::class, 'ml' ],
        'l'    => [ Volume::class, 'l' ],
        'oz'   => [ Mass::class, 'oz' ],
        'lb'   => [ Mass::class, 'lb' ],
        'g'    => [ Mass::class, 'g' ],
        'kg'   => [ Mass::class, 'kg' ],
        'mg'   => [ Mass::class, 'mg' ],
    ];

    /**
     * Default opposite-system target for each unit.
     *
     * @since 1.15.0
     * @var array<string, string>
     */
    private static $default_targets = [
        'cup'  => 'ml',
        'tsp'  => 'ml',
        'tbsp' => 'ml',
        'floz' => 'ml',
        'gal'  => 'l',
        'pt'   => 'ml',
        'qt'   => 'ml',
        'dl'   => 'floz',
        'ml'   => 'floz',
        'l'    => 'cup',
        'oz'   => 'g',
        'lb'   => 'g',
        'g'    => 'oz',
        'kg'   => 'lb',
        'mg'   => 'g',
    ];

    /**
     * Convert an amount from one unit to another.
     *
     * @since 1.15.0
     * @param float  $amount The numeric amount to convert.
     * @param string $from   The measurement key to convert from.
     * @param string $to     The target measurement key.
     * @return array{amount: float, unit: string}|null The converted amount and unit, or null.
     */
    public static function convert( $amount, $from, $to ) {
        if ( ! isset( self::$unit_map[ $from ], self::$unit_map[ $to ] ) ) {
            return null;
        }

        list( $from_class, $from_unit ) = self::$unit_map[ $from ];
        list( $to_class, $to_unit )     = self::$unit_map[ $to ];

        if ( $from_class !== $to_class ) {
            return null;
        }

        return [
            'amount' => ( new $from_class( $amount, $from_unit ) )->toUnit( $to_unit ),
            'unit'   => $to,
        ];
    }

    /**
     * Get the default opposite-system target unit for a given source unit.
     *
     * @since 1.15.0
     * @param string $from_unit_key The measurement key to convert from.
     * @return string|null The target unit key, or null if no conversion exists.
     */
    public static function get_target_unit( $from_unit_key ) {
        if ( ! isset( self::$default_targets[ $from_unit_key ] ) ) {
            return null;
        }

        return self::$default_targets[ $from_unit_key ];
    }
}
