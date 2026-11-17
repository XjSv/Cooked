<?php
/**
 * Cooked Ingredient Density
 *
 * @package     Cooked
 * @subpackage  Ingredient Density
 * @since       1.18.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cooked_Ingredient_Density Class
 *
 * English ingredient names map to a grams-per-cup weight or to a liquid.
 * Unrecognized names, including non-English names, return null and the
 * converter keeps the volume path. Brown sugar uses the packed cup (USDA
 * 220 g). A "loose" note does not select the unpacked 145 g cup.
 *
 *     add_filter( 'cooked_ingredient_density_table', function( $table ) {
 *         $table['rye flour'] = [
 *             'g_per_cup' => 102,
 *             'target'    => 'g',
 *             'source'    => 'site',
 *         ];
 *         return $table;
 *     } );
 *
 * @since 1.18.0
 */
class Cooked_Ingredient_Density {

	/**
	 * Filtered density table.
	 *
	 * @since 1.18.0
	 * @return array
	 */
	public static function table() {
		$table = self::default_table();

		/**
		 * Filter the ingredient density table.
		 *
		 * @since 1.18.0
		 * @param array $table Keyed by lowercase English ingredient name.
		 */
		$filtered = apply_filters( 'cooked_ingredient_density_table', $table );

		if ( ! is_array( $filtered ) ) {
			return $table;
		}

		return $filtered;
	}

	/**
	 * Find the table entry for an ingredient name.
	 *
	 * @since 1.18.0
	 * @param string $name Ingredient name.
	 * @return array|null
	 */
	public static function match( $name ) {
		$name = self::normalize( $name );
		if ( '' === $name ) {
			return null;
		}

		$needles = [ $name ];
		$comma   = strpos( $name, ',' );
		if ( false !== $comma ) {
			$needles[] = trim( substr( $name, 0, $comma ) );
		}

		$table   = self::table();
		$aliases = self::aliases();
		$keys    = array_merge( array_keys( $table ), array_keys( $aliases ) );
		usort(
			$keys,
			function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);

		foreach ( $needles as $needle ) {
			foreach ( $keys as $key ) {
				if ( ! self::contains_phrase( $needle, $key ) ) {
					continue;
				}

				$canonical = isset( $aliases[ $key ] ) ? $aliases[ $key ] : $key;
				if ( isset( $table[ $canonical ] ) ) {
					return $table[ $canonical ];
				}
			}
		}

		foreach ( $needles as $needle ) {
			if ( self::contains_phrase( $needle, 'oil' ) || self::contains_phrase( $needle, 'juice' ) ) {
				return [
					'target' => 'ml',
					'liquid' => true,
				];
			}
		}

		return null;
	}

	/**
	 * Weights checked against USDA FoodData Central or King Arthur Baking.
	 * Where those sources differ by more than 5 g, the chosen one is named.
	 *
	 * @since 1.18.0
	 * @return array
	 */
	private static function default_table() {
		return [
			'all-purpose flour' => [
				'g_per_cup' => 125,
				'target'    => 'g',
				'source'    => 'USDA FDC 168894, 125 g. King Arthur lists 120 g.',
			],
			'bread flour' => [
				'g_per_cup' => 120,
				'target'    => 'g',
				'source'    => 'King Arthur, 120 g. USDA FDC 168896 lists 137 g.',
			],
			'whole wheat flour' => [
				'g_per_cup' => 120,
				'target'    => 'g',
				'source'    => 'USDA FDC 168893, 120 g. King Arthur lists 113 g.',
			],
			'cake flour' => [
				'g_per_cup' => 120,
				'target'    => 'g',
				'source'    => 'King Arthur, 120 g. USDA FDC 169723 is 137 g for an unsifted dipped cup.',
			],
			'self-raising flour' => [
				'g_per_cup' => 125,
				'target'    => 'g',
				'source'    => 'USDA FDC 168895, 125 g. King Arthur lists 113 g.',
			],
			'almond flour' => [
				'g_per_cup' => 96,
				'target'    => 'g',
				'source'    => 'King Arthur, 96 g.',
			],
			'granulated sugar' => [
				'g_per_cup' => 200,
				'target'    => 'g',
				'source'    => 'USDA FDC 169655, 200 g. King Arthur lists 198 g.',
			],
			'caster sugar' => [
				'g_per_cup' => 190,
				'target'    => 'g',
				'source'    => 'King Arthur baker\'s special / castor sugar, 190 g.',
			],
			'brown sugar' => [
				'g_per_cup' => 220,
				'target'    => 'g',
				'source'    => 'USDA FDC 168833 packed cup, 220 g. King Arthur lists 213 g. Unpacked 145 g is not used.',
			],
			'powdered sugar' => [
				'g_per_cup' => 120,
				'target'    => 'g',
				'source'    => 'USDA FDC 169656 unsifted, 120 g. King Arthur lists 113 g.',
			],
			'cocoa powder' => [
				'g_per_cup' => 86,
				'target'    => 'g',
				'source'    => 'USDA FDC 169593, 86 g. King Arthur lists 84 g.',
			],
			'cornstarch' => [
				'g_per_cup' => 128,
				'target'    => 'g',
				'source'    => 'USDA FDC 169698, 128 g. King Arthur lists 112 g.',
			],
			'rolled oats' => [
				'g_per_cup' => 81,
				'target'    => 'g',
				'source'    => 'USDA FDC 173904, 81 g. King Arthur old-fashioned oats are 89 g.',
			],
			'oat flour' => [
				'g_per_cup' => 92,
				'target'    => 'g',
				'source'    => 'King Arthur, 92 g.',
			],
			'white rice' => [
				'g_per_cup' => 185,
				'target'    => 'g',
				'source'    => 'USDA FDC 168877 long-grain raw, 185 g. King Arthur lists 198 g.',
			],
			'quinoa' => [
				'g_per_cup' => 170,
				'target'    => 'g',
				'source'    => 'USDA FDC 168874 uncooked, 170 g. King Arthur lists 177 g.',
			],
			'lentils' => [
				'g_per_cup' => 192,
				'target'    => 'g',
				'source'    => 'USDA FDC 172420 raw, 192 g.',
			],
			'butter' => [
				'g_per_cup' => 227,
				'target'    => 'g',
				'source'    => 'USDA FDC 173410, 227 g. King Arthur lists 226 g.',
			],
			'peanut butter' => [
				'g_per_cup' => 258,
				'target'    => 'g',
				'source'    => 'USDA FDC 172470 smooth, 258 g. King Arthur lists 270 g.',
			],
			'chocolate chips' => [
				'g_per_cup' => 168,
				'target'    => 'g',
				'source'    => 'USDA FDC 167976, 168 g. King Arthur lists 170 g.',
			],
			'cheddar cheese' => [
				'g_per_cup' => 113,
				'target'    => 'g',
				'source'    => 'USDA FDC 173414 shredded, 113 g. King Arthur lists 113 g.',
			],
			'honey' => [
				'g_per_cup' => 339,
				'target'    => 'g',
				'source'    => 'USDA FDC 169640, 339 g. King Arthur lists 336 g.',
			],
			'maple syrup' => [
				'g_per_cup' => 315,
				'target'    => 'g',
				'source'    => 'USDA FDC 169661, 315 g. King Arthur lists 312 g.',
			],
			'golden syrup' => [
				'g_per_cup' => 336,
				'target'    => 'g',
				'source'    => 'Lyle\'s Golden Syrup label, 21 g per tablespoon.',
			],
			'water' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'milk' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'heavy cream' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'buttermilk' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'olive oil' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'vegetable oil' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'lemon juice' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'vinegar' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'stock' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'wine' => [
				'target' => 'ml',
				'liquid' => true,
			],
			'sour cream' => [
				'target' => 'ml',
				'liquid' => true,
			],
		];
	}

	/**
	 * @since 1.18.0
	 * @return array
	 */
	private static function aliases() {
		return [
			'plain flour'            => 'all-purpose flour',
			'flour'                  => 'all-purpose flour',
			'self-rising flour'      => 'self-raising flour',
			'self raising flour'     => 'self-raising flour',
			'wholemeal flour'        => 'whole wheat flour',
			'icing sugar'            => 'powdered sugar',
			'confectioners\' sugar'  => 'powdered sugar',
			'confectioners sugar'    => 'powdered sugar',
			'sugar'                  => 'granulated sugar',
			'cocoa'                  => 'cocoa powder',
			'double cream'           => 'heavy cream',
			'broth'                  => 'stock',
		];
	}

	/**
	 * @param string $name Raw ingredient name.
	 * @return string
	 */
	private static function normalize( $name ) {
		$name = strtolower( wp_strip_all_tags( (string) $name ) );
		$name = preg_replace( '/\([^)]*\)/', ' ', $name );
		$name = preg_replace( '/\s+/', ' ', $name );

		return trim( $name );
	}

	/**
	 * @param string $haystack Normalized ingredient name.
	 * @param string $phrase   Table key or alias.
	 * @return bool
	 */
	private static function contains_phrase( $haystack, $phrase ) {
		$pattern = '/(?<![a-z0-9])' . preg_quote( $phrase, '/' ) . '(?![a-z0-9])/';

		return 1 === preg_match( $pattern, $haystack );
	}
}
