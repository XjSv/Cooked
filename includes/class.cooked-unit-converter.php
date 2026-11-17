<?php
/**
 * Cooked Unit Converter
 *
 * @package     Cooked
 * @subpackage  Unit Converter
 * @since       1.15.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpUnitsOfMeasure\PhysicalQuantity\Volume;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;

/**
 * Cooked_Unit_Converter Class
 *
 * Converts ingredient amounts between measurement units using the
 * php-units-of-measure library.
 *
 * @since 1.15.0
 */
class Cooked_Unit_Converter {

	/**
	 * Millilitres at or below this stay teaspoons in the imperial direction.
	 *
	 * @since 1.18.0
	 */
	const ML_TSP_MAX = 5;

	/**
	 * Millilitres below this, and at least one tablespoon, use tablespoons.
	 *
	 * @since 1.18.0
	 */
	const ML_TBSP_MAX = 60;

	/**
	 * Fluid ounces in one cup. At this size, imperial volume steps up to cups.
	 *
	 * @since 1.18.0
	 */
	const FLOZ_PER_CUP = 8;

	/**
	 * Millilitres or grams at which the target steps up to litres or kilograms.
	 *
	 * @since 1.18.0
	 */
	const STEP_UP_THRESHOLD = 1000;

	/**
	 * Grams at which a reverse mass conversion uses pounds.
	 *
	 * @since 1.18.0
	 */
	const GRAM_TO_POUND = 454;

	/**
	 * How close a gram amount must be to a cooking fraction, in cups, before it reverses to cups.
	 *
	 * 1/16 cup. A 1/8 cup window would turn 200 g of butter into 1 cup.
	 *
	 * @since 1.18.0
	 */
	const CUP_FRACTION_WINDOW = 0.0625;

	/**
	 * Maps Cooked unit keys to [PhysicalQuantity class, library unit name].
	 *
	 * @since 1.15.0
	 * @var array
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
	 * Measurement system for each convertible unit.
	 *
	 * @since 1.18.0
	 * @var array
	 */
	private static $systems = [
		'cup'  => 'imperial',
		'tsp'  => 'imperial',
		'tbsp' => 'imperial',
		'floz' => 'imperial',
		'gal'  => 'imperial',
		'pt'   => 'imperial',
		'qt'   => 'imperial',
		'oz'   => 'imperial',
		'lb'   => 'imperial',
		'dl'   => 'metric',
		'ml'   => 'metric',
		'l'    => 'metric',
		'g'    => 'metric',
		'kg'   => 'metric',
		'mg'   => 'metric',
	];

	/**
	 * Default opposite-system target for each unit.
	 *
	 * Teaspoons and tablespoons are omitted. Null from get_target_unit() means do not convert.
	 * Amount-aware targets live in resolve_target_unit().
	 *
	 * @since 1.15.0
	 * @var array
	 */
	private static $default_targets = [
		'cup'  => 'ml',
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
	 * @return array|null Array with 'amount' (float) and 'unit' (string), or null.
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

	/**
	 * Resolve the unit to display for one ingredient amount.
	 *
	 * Pure: the result depends only on the arguments. Pass $options to turn
	 * spoon-keeping or the density table off. Defaults leave both on.
	 *
	 *     add_filter( 'cooked_unit_conversion_target', function( $target, $from, $amount, $name, $system ) {
	 *         if ( 'honey' === $name ) {
	 *             return 'g';
	 *         }
	 *         return $target;
	 *     }, 10, 5 );
	 *
	 * @since 1.18.0
	 * @param string      $from             Source unit key.
	 * @param float       $amount           Amount in the source unit.
	 * @param string|null $ingredient_name  Ingredient name, or null.
	 * @param string      $target_system    metric or imperial.
	 * @param array       $options          keep_spoons, round_converted, density_cups.
	 * @return string|null Target unit key, or null when the amount should stay as written.
	 */
	public static function resolve_target_unit( $from, $amount, $ingredient_name, $target_system, $options = [] ) {
		$options = self::conversion_options( $options );
		$target  = self::resolve_target_unit_value( $from, $amount, $ingredient_name, $target_system, $options );

		/**
		 * Filter the unit chosen for a converted ingredient.
		 *
		 * @since 1.18.0
		 * @param string|null $target          Target unit key, or null to leave the amount unchanged.
		 * @param string      $from            Source unit key.
		 * @param float       $amount          Amount in the source unit.
		 * @param string|null $ingredient_name Ingredient name, or null.
		 * @param string      $target_system   metric or imperial.
		 */
		$target = apply_filters( 'cooked_unit_conversion_target', $target, $from, (float) $amount, $ingredient_name, $target_system );

		if ( ! is_string( $target ) || '' === $target ) {
			return null;
		}

		return $target;
	}

	/**
	 * Round a converted amount to a kitchen-friendly number.
	 *
	 * Author-typed amounts must not be passed here.
	 *
	 *     add_filter( 'cooked_unit_conversion_rounding', function( $rounded, $amount, $unit ) {
	 *         return $amount;
	 *     }, 10, 3 );
	 *
	 * @since 1.18.0
	 * @param float  $amount Converted amount, before rounding.
	 * @param string $unit   Unit of that amount.
	 * @return float
	 */
	public static function round_converted_amount( $amount, $unit ) {
		$amount  = (float) $amount;
		$rounded = self::round_converted_amount_value( $amount, $unit );

		/**
		 * Filter a converted amount after kitchen rounding.
		 *
		 * @since 1.18.0
		 * @param float  $rounded Rounded amount.
		 * @param float  $amount  Amount before rounding.
		 * @param string $unit    Unit of the amount.
		 */
		$filtered = apply_filters( 'cooked_unit_conversion_rounding', $rounded, $amount, $unit );

		if ( ! is_numeric( $filtered ) ) {
			return $rounded;
		}

		return (float) $filtered;
	}

	/**
	 * Format an amount that has already been converted.
	 *
	 * When $rounded is false, metric units keep two decimal places.
	 *
	 * @since 1.18.0
	 * @param float  $amount  Converted amount.
	 * @param string $unit    Unit key.
	 * @param bool   $rounded Whether kitchen rounding was applied.
	 * @return string
	 */
	public static function format_converted_amount( $amount, $unit, $rounded = true ) {
		$amount = (float) $amount;

		if ( in_array( $unit, [ 'ml', 'g', 'l', 'kg' ], true ) ) {
			if ( ! $rounded ) {
				return number_format_i18n( $amount, 2 );
			}

			if ( in_array( $unit, [ 'ml', 'g' ], true ) ) {
				return number_format_i18n( $amount, 0 );
			}

			return self::format_trimmed_decimal( $amount );
		}

		$measurements = new Cooked_Measurements();

		return (string) $measurements->format_amount( $amount, 'fraction' );
	}

	/**
	 * Convert one ingredient amount, including a density lookup when the table matches.
	 *
	 * convert() still refuses volume-to-mass. This method does that math itself.
	 *
	 *     add_filter( 'cooked_converted_ingredient_amount', function( $amount, $unit, $from, $original, $name ) {
	 *         return $amount;
	 *     }, 10, 5 );
	 *
	 * @since 1.18.0
	 * @param float       $amount          Amount in the source unit, after any servings multiplier.
	 * @param string      $from            Source unit key.
	 * @param string|null $ingredient_name Ingredient name, or null.
	 * @param string      $target_system   metric or imperial.
	 * @param array       $options         keep_spoons, round_converted, density_cups.
	 * @return array|null Amount and unit, or null when the line should stay as written.
	 */
	public static function convert_for_ingredient( $amount, $from, $ingredient_name, $target_system, $options = [] ) {
		$options  = self::conversion_options( $options );
		$original = (float) $amount;
		$target   = self::resolve_target_unit( $from, $original, $ingredient_name, $target_system, $options );

		if ( ! $target || $target === $from ) {
			return null;
		}

		$converted = self::density_amount( $original, $from, $target, $ingredient_name, $options );

		if ( null === $converted ) {
			$same = self::convert( $original, $from, $target );
			if ( ! $same ) {
				return null;
			}
			$converted = (float) $same['amount'];
		}

		if ( $options['round_converted'] ) {
			$converted = self::round_converted_amount( $converted, $target );
		}

		/**
		 * Filter the converted amount after rounding.
		 *
		 * @since 1.18.0
		 * @param float       $amount          Converted amount.
		 * @param string      $unit            Target unit key.
		 * @param string      $from            Source unit key.
		 * @param float       $original_amount Amount before conversion.
		 * @param string|null $ingredient_name Ingredient name, or null.
		 */
		$filtered = apply_filters( 'cooked_converted_ingredient_amount', $converted, $target, $from, $original, $ingredient_name );

		if ( is_numeric( $filtered ) ) {
			$converted = (float) $filtered;
		}

		return [
			'amount' => $converted,
			'unit'   => $target,
		];
	}

	/**
	 * @param array $options Requested flags.
	 * @return array
	 */
	private static function conversion_options( $options ) {
		$defaults = [
			'keep_spoons'      => true,
			'round_converted'  => true,
			'density_cups'     => true,
		];

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		return array_merge( $defaults, $options );
	}

	/**
	 * @param string      $from             Source unit key.
	 * @param float       $amount           Amount in the source unit.
	 * @param string|null $ingredient_name  Ingredient name, or null.
	 * @param string      $target_system    metric or imperial.
	 * @param array       $options          Normalized flags.
	 * @return string|null
	 */
	private static function resolve_target_unit_value( $from, $amount, $ingredient_name, $target_system, $options ) {
		if ( ! isset( self::$unit_map[ $from ], self::$systems[ $from ] ) ) {
			return null;
		}

		if ( $options['keep_spoons'] && ( 'tsp' === $from || 'tbsp' === $from ) ) {
			return null;
		}

		if ( 'mg' === $from ) {
			return 'g';
		}

		if ( self::$systems[ $from ] === $target_system ) {
			return null;
		}

		$amount = (float) $amount;

		if ( 'metric' === $target_system ) {
			return self::resolve_metric_target( $from, $amount, $ingredient_name, $options );
		}

		if ( 'imperial' === $target_system ) {
			return self::resolve_imperial_target( $from, $amount, $ingredient_name, $options );
		}

		return null;
	}

	/**
	 * @param string      $from             Source unit key.
	 * @param float       $amount           Amount in the source unit.
	 * @param string|null $ingredient_name  Ingredient name, or null.
	 * @param array       $options          Normalized flags.
	 * @return string|null
	 */
	private static function resolve_metric_target( $from, $amount, $ingredient_name, $options ) {
		if ( in_array( $from, [ 'cup', 'floz' ], true ) && $options['density_cups'] ) {
			$grams = self::grams_from_volume( $amount, $from, $ingredient_name );
			if ( null !== $grams ) {
				return $grams >= self::STEP_UP_THRESHOLD ? 'kg' : 'g';
			}
		}

		if ( in_array( $from, [ 'cup', 'floz', 'pt', 'qt', 'tsp', 'tbsp' ], true ) ) {
			$ml = self::convert( $amount, $from, 'ml' );
			if ( ! $ml ) {
				return null;
			}

			return $ml['amount'] >= self::STEP_UP_THRESHOLD ? 'l' : 'ml';
		}

		if ( 'gal' === $from ) {
			return 'l';
		}

		if ( in_array( $from, [ 'oz', 'lb' ], true ) ) {
			$grams = self::convert( $amount, $from, 'g' );
			if ( ! $grams ) {
				return null;
			}

			return $grams['amount'] >= self::STEP_UP_THRESHOLD ? 'kg' : 'g';
		}

		return null;
	}

	/**
	 * @param string      $from             Source unit key.
	 * @param float       $amount           Amount in the source unit.
	 * @param string|null $ingredient_name  Ingredient name, or null.
	 * @param array       $options          Normalized flags.
	 * @return string|null
	 */
	private static function resolve_imperial_target( $from, $amount, $ingredient_name, $options ) {
		if ( 'ml' === $from ) {
			return self::resolve_ml_imperial( $amount );
		}

		if ( 'l' === $from ) {
			return 'cup';
		}

		if ( 'dl' === $from ) {
			return 'floz';
		}

		if ( 'g' === $from ) {
			if ( $options['density_cups'] && self::grams_are_close_cup( $amount, $ingredient_name ) ) {
				return 'cup';
			}

			return $amount >= self::GRAM_TO_POUND ? 'lb' : 'oz';
		}

		if ( 'kg' === $from ) {
			return 'lb';
		}

		return null;
	}

	/**
	 * @param float $amount Millilitres.
	 * @return string
	 */
	private static function resolve_ml_imperial( $amount ) {
		if ( $amount <= self::ML_TSP_MAX ) {
			return 'tsp';
		}

		if ( $amount < self::ML_TBSP_MAX ) {
			$tbsp = self::convert( $amount, 'ml', 'tbsp' );
			if ( $tbsp && $tbsp['amount'] >= 1 ) {
				return 'tbsp';
			}

			return 'tsp';
		}

		$floz = self::convert( $amount, 'ml', 'floz' );
		if ( $floz && $floz['amount'] >= self::FLOZ_PER_CUP ) {
			return 'cup';
		}

		return 'floz';
	}

	/**
	 * @param float  $amount Amount before rounding.
	 * @param string $unit   Unit key.
	 * @return float
	 */
	private static function round_converted_amount_value( $amount, $unit ) {
		if ( in_array( $unit, [ 'ml', 'g' ], true ) ) {
			if ( $amount < 10 ) {
				return (float) round( $amount, 0, PHP_ROUND_HALF_UP );
			}

			return (float) ( round( $amount / 5, 0, PHP_ROUND_HALF_UP ) * 5 );
		}

		if ( in_array( $unit, [ 'l', 'kg' ], true ) ) {
			return (float) ( round( $amount / 0.05, 0, PHP_ROUND_HALF_UP ) * 0.05 );
		}

		if ( 'cup' === $unit ) {
			return self::snap_cooking_fraction( $amount, [ 0, 0.25, 1 / 3, 0.5, 2 / 3, 0.75, 1 ] );
		}

		if ( 'oz' === $unit || 'floz' === $unit ) {
			if ( $amount < 8 ) {
				return self::snap_cooking_fraction( $amount, [ 0, 0.5, 1 ] );
			}

			return (float) round( $amount, 0, PHP_ROUND_HALF_UP );
		}

		if ( 'tsp' === $unit ) {
			return self::snap_cooking_fraction( $amount, [ 0, 0.25, 0.5, 0.75, 1 ] );
		}

		if ( 'tbsp' === $unit ) {
			return self::snap_cooking_fraction( $amount, [ 0, 0.5, 1 ] );
		}

		return $amount;
	}

	/**
	 * @param float $amount  Amount to snap.
	 * @param array $anchors Allowed fractional parts, including 0 and 1.
	 * @return float
	 */
	private static function snap_cooking_fraction( $amount, $anchors ) {
		$negative = $amount < 0;
		$amount   = abs( $amount );
		$whole    = floor( $amount + 1.0e-9 );
		$fraction = $amount - $whole;
		$best     = $anchors[0];
		$best_diff = null;

		foreach ( $anchors as $anchor ) {
			$diff = abs( $fraction - $anchor );
			if ( null === $best_diff || $diff < $best_diff ) {
				$best      = $anchor;
				$best_diff = $diff;
			}
		}

		if ( $best >= 1 - 1.0e-9 ) {
			$whole += 1;
			$best   = 0;
		}

		$result = $whole + $best;

		return $negative ? -1 * $result : $result;
	}

	/**
	 * @param float $amount Amount in litres or kilograms.
	 * @return string
	 */
	private static function format_trimmed_decimal( $amount ) {
		$formatted = number_format_i18n( $amount, 2 );
		$decimal   = '.';

		global $wp_locale;
		if ( isset( $wp_locale->number_format['decimal_point'] ) && '' !== $wp_locale->number_format['decimal_point'] ) {
			$decimal = $wp_locale->number_format['decimal_point'];
		}

		if ( false === strpos( $formatted, $decimal ) ) {
			return $formatted;
		}

		return rtrim( rtrim( $formatted, '0' ), $decimal );
	}

	/**
	 * @param float       $amount          Source amount.
	 * @param string      $from            Source unit.
	 * @param string      $target          Target unit.
	 * @param string|null $ingredient_name Ingredient name, or null.
	 * @param array       $options         Normalized flags.
	 * @return float|null Amount in the target unit, or null to use convert().
	 */
	private static function density_amount( $amount, $from, $target, $ingredient_name, $options ) {
		if ( empty( $options['density_cups'] ) ) {
			return null;
		}

		$entry = self::density_entry( $ingredient_name );
		if ( ! $entry ) {
			return null;
		}

		if ( in_array( $from, [ 'cup', 'floz' ], true ) && in_array( $target, [ 'g', 'kg' ], true ) ) {
			$grams = self::grams_from_volume( $amount, $from, $ingredient_name );
			if ( null === $grams ) {
				return null;
			}

			return 'kg' === $target ? $grams / 1000 : $grams;
		}

		if ( 'g' === $from && 'cup' === $target ) {
			return $amount / (float) $entry['g_per_cup'];
		}

		return null;
	}

	/**
	 * @param float       $amount          Volume amount.
	 * @param string      $from            cup or floz.
	 * @param string|null $ingredient_name Ingredient name, or null.
	 * @return float|null Grams, or null when the name has no weight.
	 */
	private static function grams_from_volume( $amount, $from, $ingredient_name ) {
		$entry = self::density_entry( $ingredient_name );
		if ( ! $entry || ! in_array( $from, [ 'cup', 'floz' ], true ) ) {
			return null;
		}

		$ml     = self::convert( $amount, $from, 'ml' );
		$cup_ml = self::convert( 1, 'cup', 'ml' );
		if ( ! $ml || ! $cup_ml || ! $cup_ml['amount'] ) {
			return null;
		}

		return $ml['amount'] / $cup_ml['amount'] * (float) $entry['g_per_cup'];
	}

	/**
	 * @param float       $grams           Grams.
	 * @param string|null $ingredient_name Ingredient name, or null.
	 * @return bool
	 */
	private static function grams_are_close_cup( $grams, $ingredient_name ) {
		$entry = self::density_entry( $ingredient_name );
		if ( ! $entry || ! $entry['g_per_cup'] ) {
			return false;
		}

		$cups     = $grams / (float) $entry['g_per_cup'];
		$fraction = $cups - floor( $cups + 1.0e-9 );
		$anchors  = [ 0, 0.25, 1 / 3, 0.5, 2 / 3, 0.75, 1 ];

		foreach ( $anchors as $anchor ) {
			if ( abs( $fraction - $anchor ) <= self::CUP_FRACTION_WINDOW + 1.0e-9 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|null $ingredient_name Ingredient name, or null.
	 * @return array|null Entry with g_per_cup, or null.
	 */
	private static function density_entry( $ingredient_name ) {
		if ( ! class_exists( 'Cooked_Ingredient_Density' ) || ! is_string( $ingredient_name ) || '' === $ingredient_name ) {
			return null;
		}

		$entry = Cooked_Ingredient_Density::match( $ingredient_name );
		if ( ! is_array( $entry ) || empty( $entry['g_per_cup'] ) ) {
			return null;
		}

		return $entry;
	}
}
