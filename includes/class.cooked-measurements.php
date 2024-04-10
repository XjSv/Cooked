<?php
/**
 * Cooked Measurement Functions
 *
 * @package     Cooked
 * @subpackage  Measurement Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use NXP\MathExecutor;

/**
 * Cooked_Recipe_Meta Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Measurements {

	public static function get() {

		// Use the "cooked_measurements" filter to add your own measurements.
		$measurements = apply_filters('cooked_measurements',array(
			'g' => array(
				'singular_abbr' => _x('g','Grams Abbreviation (Singular)','cooked'),
				'plural_abbr' => _x('g','Grams Abbreviation (Plural)','cooked'),
				'singular' => esc_html__('gram','cooked'),
				'plural' => esc_html__('grams','cooked'),
				'variations' => array( 'g', 'g.', 'gram', 'grams' ),
			),
			'kg' => array(
				'singular_abbr' => _x('kg','Kilograms Abbreviation (Singular)','cooked'),
				'plural_abbr' => _x('kg','Kilograms Abbreviation (Plural)','cooked'),
				'singular' => esc_html__('kilogram','cooked'),
				'plural' => esc_html__('kilograms','cooked'),
				'variations' => array( 'kg', 'kg.', 'kilogram', 'kilograms' ),
			),
			'mg' => array(
				'singular_abbr' => esc_html__('mg','cooked'),
				'plural_abbr' => esc_html__('mg','cooked'),
				'singular' => esc_html__('milligram','cooked'),
				'plural' => esc_html__('milligrams','cooked'),
				'variations' => array( 'mg', 'mg.', 'milligram', 'milligrams' ),
			),
			'oz' => array(
				'singular_abbr' => esc_html__('oz','cooked'),
				'plural_abbr' => esc_html__('oz','cooked'),
				'singular' => esc_html__('ounce','cooked'),
				'plural' => esc_html__('ounces','cooked'),
				'variations' => array( 'oz', 'oz.', 'ounce', 'ounces' ),
			),
			'floz' => array(
				'singular_abbr' => esc_html__('fl oz','cooked'),
				'plural_abbr' => esc_html__('fl oz','cooked'),
				'singular' => esc_html__('fluid ounce','cooked'),
				'plural' => esc_html__('fluid ounces','cooked'),
				'variations' => array( 'fl oz', 'fl oz.', 'fl. oz.', 'fluid ounce', 'fluid ounces' ),
			),
			'cup' => array(
				'singular_abbr' => esc_html__('cup','cooked'),
				'plural_abbr' => esc_html__('cups','cooked'),
				'singular' => esc_html__('cup','cooked'),
				'plural' => esc_html__('cups','cooked'),
				'variations' => array( 'c', 'c.', 'cup', 'cups' ),
			),
			'tsp' => array(
				'singular_abbr' => esc_html__('tsp','cooked'),
				'plural_abbr' => esc_html__('tsp','cooked'),
				'singular' => esc_html__('teaspoon','cooked'),
				'plural' => esc_html__('teaspoons','cooked'),
				'variations' => array( 't', 'tsp.', 'tsp', 'teaspoon', 'teaspoons' ),
			),
			'tbsp' => array(
				'singular_abbr' => esc_html__('tbsp','cooked'),
				'plural_abbr' => esc_html__('tbsp','cooked'),
				'singular' => esc_html__('tablespoon','cooked'),
				'plural' => esc_html__('tablespoons','cooked'),
				'variations' => array( 'T', 'tbl.', 'tbl', 'tbs.', 'tbs', 'tbsp.', 'tbsp', 'tablespoon', 'tablespoons' ),
			),
			'ml' => array(
				'singular_abbr' => esc_html__('ml','cooked'),
				'plural_abbr' => esc_html__('ml','cooked'),
				'singular' => esc_html__('milliliter','cooked'),
				'plural' => esc_html__('milliliters','cooked'),
				'variations' => array( 'ml', 'ml.', 'mL', 'mL.', 'cc', 'milliliter', 'milliliters', 'millilitre', 'millilitres' ),
			),
			'l' => array(
				'singular_abbr' => esc_html__('l','cooked'),
				'plural_abbr' => esc_html__('l','cooked'),
				'singular' => esc_html__('liter','cooked'),
				'plural' => esc_html__('liters','cooked'),
				'variations' => array( 'l', 'l.', 'L', 'L.', 'liter', 'liters', 'litre', 'litres' ),
			),
			'stick' => array(
				'singular_abbr' => esc_html__('stick','cooked'),
				'plural_abbr' => esc_html__('sticks','cooked'),
				'singular' => esc_html__('stick','cooked'),
				'plural' => esc_html__('sticks','cooked'),
				'variations' => array( 'stick', 'sticks' ),
			),
			'lb' => array(
				'singular_abbr' => esc_html__('lb','cooked'),
				'plural_abbr' => esc_html__('lbs','cooked'),
				'singular' => esc_html__('pound','cooked'),
				'plural' => esc_html__('pounds','cooked'),
				'variations' => array( 'lb', 'lbs', 'lb.', 'lbs.', 'pound', 'pounds' ),
			),
			'dash' => array(
				'singular_abbr' => esc_html__('dash','cooked'),
				'plural_abbr' => esc_html__('dashes','cooked'),
				'singular' => esc_html__('dash','cooked'),
				'plural' => esc_html__('dashes','cooked'),
				'variations' => array( 'dash', 'dashes' ),
			),
			'drop' => array(
				'singular_abbr' => esc_html__('drop','cooked'),
				'plural_abbr' => esc_html__('drops','cooked'),
				'singular' => esc_html__('drop','cooked'),
				'plural' => esc_html__('drops','cooked'),
				'variations' => array( 'drop', 'drops' ),
			),
			'gal' => array(
				'singular_abbr' => esc_html__('gal','cooked'),
				'plural_abbr' => esc_html__('gals','cooked'),
				'singular' => esc_html__('gallon','cooked'),
				'plural' => esc_html__('gallons','cooked'),
				'variations' => array( 'G', 'G.', 'gal', 'gal.', 'gallon', 'gallons' ),
			),
			'pinch' => array(
				'singular_abbr' => esc_html__('pinch','cooked'),
				'plural_abbr' => esc_html__('pinches','cooked'),
				'singular' => esc_html__('pinch','cooked'),
				'plural' => esc_html__('pinches','cooked'),
				'variations' => array( 'pinch', 'pinches' ),
			),
			'pt' => array(
				'singular_abbr' => esc_html__('pt','cooked'),
				'plural_abbr' => esc_html__('pt','cooked'),
				'singular' => esc_html__('pint','cooked'),
				'plural' => esc_html__('pints','cooked'),
				'variations' => array( 'p', 'p.', 'pt', 'pt.', 'pts', 'pts.', 'fl pt', 'fl. pt.', 'pint', 'pints' ),
			),
			'qt' => array(
				'singular_abbr' => esc_html__('qt','cooked'),
				'plural_abbr' => esc_html__('qts','cooked'),
				'singular' => esc_html__('quart','cooked'),
				'plural' => esc_html__('quarts','cooked'),
				'variations' => array( 'q', 'q.', 'qt', 'qt.', 'qts', 'qts.', 'fl qt', 'fl. qt.', 'quart', 'quarts' ),
			),
		));

		return $measurements;

	}

	public static function nutrition_facts(){

		global $_cooked_settings;

		// Use the "cooked_nutrition_facts" filter to add your own nutrition facts.
		$nutrition_facts = apply_filters('cooked_nutrition_facts',array(

			'top' => array(
				'serving_size' => array(
					'name' => esc_html__('Serving Size','cooked'),
					'type' => 'text'
				),
				'servings' => array(
					'name' => esc_html__('Servings','cooked'),
					'type' => 'number'
				)
			),

			'mid' => array(
				'calories' => array(
					'name' => esc_html__('Calories','cooked'),
					'type' => 'number'
				),
				'calories_fat' => array(
					'name' => esc_html__('Calories from Fat','cooked'),
					'type' => 'number'
				)
			),

			'main' => array(
				'fat' => array(
					'name' => esc_html__('Total Fat','cooked'),
					'type' => 'number',
					'measurement' => 'g',
					'pdv' => apply_filters( 'cooked_pdv_fat', 65 ),
					'subs' => array(
						'sat_fat' => array(
							'name' => esc_html__('Saturated Fat','cooked'),
							'type' => 'number',
							'measurement' => 'g',
							'pdv' => apply_filters( 'cooked_pdv_satfat', 20 )
						),
						'trans_fat' => array(
							'name' => esc_html__('Trans Fat','cooked'),
							'type' => 'number',
							'measurement' => 'g'
						)
					)
				),
				'cholesterol' => array(
					'name' => esc_html__('Cholesterol','cooked'),
					'type' => 'number',
					'measurement' => 'mg',
					'pdv' => apply_filters( 'cooked_pdv_cholesterol', 300 )
				),
				'sodium' => array(
					'name' => esc_html__('Sodium','cooked'),
					'type' => 'number',
					'measurement' => 'mg',
					'pdv' => apply_filters( 'cooked_pdv_sodium', 2400 )
				),
				'potassium' => array(
					'name' => esc_html__('Potassium','cooked'),
					'type' => 'number',
					'measurement' => 'mg',
					'pdv' => apply_filters( 'cooked_pdv_potassium', 3500 )
				),
				'carbs' => array(
					'name' => ( isset( $_cooked_settings['carb_format'] ) && $_cooked_settings['carb_format'] == 'total' ? esc_html__('Total Carbohydrate','cooked') : esc_html__('Net Carbohydrate','cooked') ),
					'type' => 'number',
					'measurement' => 'g',
					'pdv' => apply_filters( 'cooked_pdv_carbs', 300 ),
					'subs' => array(
						'fiber' => array(
							'name' => esc_html__('Dietary Fiber','cooked'),
							'type' => 'number',
							'measurement' => 'g',
							'pdv' => apply_filters( 'cooked_pdv_fiber', 25 )
						),
						'sugars' => array(
							'name' => esc_html__('Sugars','cooked'),
							'type' => 'number',
							'measurement' => 'g'
						)
					)
				),
				'protein' => array(
					'name' => esc_html__('Protein','cooked'),
					'type' => 'number',
					'measurement' => 'g',
					'pdv' => apply_filters( 'cooked_pdv_protein', 50 )
				)
			),

			'bottom' => array(
				'vitamin_a' => array(
					'name' => esc_html__('Vitamin A','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_c' => array(
					'name' => esc_html__('Vitamin C','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'calcium' => array(
					'name' => esc_html__('Calcium','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'iron' => array(
					'name' => esc_html__('Iron','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_d' => array(
					'name' => esc_html__('Vitamin D','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_e' => array(
					'name' => esc_html__('Vitamin E','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_k' => array(
					'name' => esc_html__('Vitamin K','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'thiamin' => array(
					'name' => esc_html__('Thiamin','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'riboflavin' => array(
					'name' => esc_html__('Riboflavin','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'niacin' => array(
					'name' => esc_html__('Niacin','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_b6' => array(
					'name' => esc_html__('Vitamin B6','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'folate' => array(
					'name' => esc_html__('Folate','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'vitamin_b12' => array(
					'name' => esc_html__('Vitamin B12','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'biotin' => array(
					'name' => esc_html__('Biotin','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'pantothenic_acid' => array(
					'name' => esc_html__('Pantothenic Acid','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'phosphorus' => array(
					'name' => esc_html__('Phosphorus','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'iodine' => array(
					'name' => esc_html__('Iodine','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'magnesium' => array(
					'name' => esc_html__('Magnesium','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'zinc' => array(
					'name' => esc_html__('Zinc','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'selenium' => array(
					'name' => esc_html__('Selenium','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'copper' => array(
					'name' => esc_html__('Copper','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'manganese' => array(
					'name' => esc_html__('Manganese','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'chromium' => array(
					'name' => esc_html__('Chromium','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'molybdenum' => array(
					'name' => esc_html__('Molybdenum','cooked'),
					'type' => 'number',
					'measurement' => '%'
				),
				'chloride' => array(
					'name' => esc_html__('Chloride','cooked'),
					'type' => 'number',
					'measurement' => '%'
				)
			)
		));

		return $nutrition_facts;

	}

	public static function singular_plural( $singular_text, $plural_text, $count ) {

		if ($count <= 1 ):
			return $singular_text;
		else:
			return $plural_text;
		endif;

	}

	public function cleanup_amount( $amount ){

		$fractions = self::get_fraction_array();

		foreach( $fractions['fractions_a'] as $key => $f ):
			$amount = str_replace( $f, $fractions['fractions_b'][$key], $amount);
		endforeach;

		$amount_parts = explode( ' ', $amount );
		if ( isset($amount_parts[1]) ):
			$amount_parts[0] = preg_replace("/[^0-9\/\.\,]/","",$amount_parts[0]);
			$amount_parts[1] = preg_replace("/[^0-9\/\.\,]/","",$amount_parts[1]);
			$amount = $amount_parts[0] . ' ' . $amount_parts[1];
		else:
			$amount = preg_replace("/[^0-9\/\.\,]/","",$amount);
		endif;

		$amount = self::locale_formatted( $amount );

		return $amount;

	}

	public function locale_formatted( $amount ){
		global $wp_locale;
		if ( isset( $wp_locale ) ) {
			$amount = str_replace( $wp_locale->number_format['decimal_point'], '.', str_replace( $wp_locale->number_format['thousands_sep'], '', $amount) );
	    } else {
	        $amount = str_replace( ',', '', $amount);
	    }
	    return $amount;
	}

	public function format_amount( $float_amount = 0, $format = 'fraction' ){

		if ( $format == 'decimal' ):

			$amount = ( $float_amount ? number_format_i18n( floatval( $float_amount ), 2 ) : 0 );

		else:

			$float_parts = explode( '.', $float_amount );
			if ( isset($float_parts[1]) ):
				$float_parts[0] = preg_replace("/[^0-9\/\.]/","",$float_parts[0]);
				$float_parts[1] = preg_replace("/[^0-9\/\.]/","",$float_parts[1]);
				$decimal_part = $float_amount - $float_parts[0];
				if ( $decimal_part > 0.075 ):
					$fraction = self::float2rat($decimal_part);
					$fraction = self::fraction_cleaner($fraction);
					$allowed_fractions = self::get_fraction_array();
					foreach( $allowed_fractions['fractions_b'] as $key => $f ):
						$fraction = str_replace( $f, $allowed_fractions['fractions_c'][$key], $fraction);
					endforeach;
					$amount = ( $float_parts[0] ? $float_parts[0] . ' ' . $fraction : $fraction );
				else:
					$amount = preg_replace("/[^0-9\/\.]/","",$float_parts[0]);
				endif;
			else:
				$amount = preg_replace("/[^0-9\/\.]/","",$float_parts[0]);
			endif;

		endif;

		return $amount;

	}

	public function math( $expression = false ) {
		if ($expression):
			$expression = preg_replace( '/[^0-9\+\-\*\/\(\)\.\,]/', '', esc_html($expression) );
			$invalid = ( '/' == substr($expression, -1) ? true : false ); // More checks can be done here if needed.
			if ( !$invalid ):
				$mathExecutor = new MathExecutor();
				$o = $mathExecutor->execute($expression);
				return $o;
			else:
				return 0;
			endif;
		else:
			return 0;
		endif;
	}

	public function calculate($amount, $type = 'decimal') {
		if ($type === 'decimal') {
			$amount_parts = explode(' ', $amount);
			$total_parts = count($amount_parts);

			if ( $total_parts === 1 ) {
				$amount = self::math( $amount );
				$amount = floatval( $amount );
			} elseif ( $total_parts === 2 ) {
				$full_part = floatval( $amount_parts[0] );
				$fractional_part = floatval( self::math( $amount_parts[1] ) );
				$amount = $full_part + $fractional_part;
				$amount = floatval( $amount );
			} else {
				$amount = floatval( $amount );
			}
		} else {
			$amount_parts = explode('.', $amount);
			$total_parts = count($amount_parts);

			if ($total_parts === 2) {
				$full_part = intval($amount_parts[0]);
				$fractional_part = self::float2rat($amount - $full_part);
				if ($full_part === 0 && $fractional_part) {
					$amount = $fractional_part;
				} elseif ($fractional_part) {
					$amount = ($fractional_part == 1) ? $full_part + $fractional_part : "$full_part $fractional_part";
				} else {
					$amount = $full_part;
				}
			} else {
				if ($total_parts !== 1) {
					$amount = self::float2rat($amount);
				}
			}

		}

		return $amount;

	}

	public function fraction_cleaner($fraction) {
		$fraction_parts = explode('/',$fraction);
		$decimal = $fraction_parts[0] / $fraction_parts[1];

		if ($decimal < 1):
			$decimal_array = array( 0.125, 0.166, 0.200, 0.250, 0.333, 0.500, 0.666, 0.750, 0.875 );
			$closest_decimal = self::get_closest_decimal( $decimal, $decimal_array );

			switch($closest_decimal):

				case 0.125:
					return '1/8';
				case 0.166:
					return '1/6';
				case 0.200:
					return '1/5';
				case 0.250:
					return '1/4';
				case 0.333:
					return '1/3';
				case 0.500:
					return '1/2';
				case 0.666:
					return '2/3';
				case 0.750:
					return '3/4';
				case 0.875:
					return '7/8';

			endswitch;
		else:
			return self::calculate($decimal, 'fraction');
		endif;

	}

	public function get_closest_decimal($search, $arr) {
		$closest = null;
		foreach ($arr as $item) {
			if ($closest === null || abs($search - $closest) > abs($item - $search)) {
				$closest = $item;
      		}
   		}
   		return $closest;
	}

	public function float2rat($n, $tolerance = 1.e-6) {

		$h1=1; $h2=0;
		$k1=0; $k2=1;
		$b = 1/$n;
		do {
			$b = 1/$b;
			$a = floor($b);
			$aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
			$aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
			$b = $b-$a;
		} while (abs($n-$h1/$k1) > $n*$tolerance);

		if ( $h1/$k1 < 0.125 ):
			return "1/8";
		else:
			return "$h1/$k1";
		endif;
	}

	public function get_fraction_array(){
		$fractions_a = array(
			array('⅛','&#8539;','&#215B;','&frac18;'),
			array('⅙','&#8537;','&#x2159;','&frac16;'),
			array('⅕','&#8533;','&#x2155;','&frac15;'),
			array('¼','&#188;','&#xBC;','&frac14;'),
			array('⅓','&#8531;','&#2153;','&frac13;'),
			array('½','&#189;','&#xBD;','&frac12;'),
			array('⅔','&#8532;','&#2154;','&frac23;'),
			array('⅝','&#8541;','&#x215D;','&frac58;'),
			array('¾','&#190;','&#xBE;','&frac34;'),
			array('⅞','&#8542;','&#215E;','&frac78;')
		);

		$fractions_b = array(
			'1/8',
			'1/6',
			'1/5',
			'1/4',
			'1/3',
			'1/2',
			'2/3',
			'5/8',
			'3/4',
			'7/8',
		);

		$fractions_c = array(
			'&#8539;',
			'&#8537;',
			'&#8533;',
			'&#188;',
			'&#8531;',
			'&#189;',
			'&#8532;',
			'&#8541;',
			'&#190;',
			'&#8542;'
		);

		$fraction_array = array( 'fractions_a' => $fractions_a, 'fractions_b' => $fractions_b, 'fractions_c' => $fractions_c );
		return $fraction_array;
	}

	public static function time_format( $minutes, $format = 'default' ){

		ob_start();

		if ( $minutes < 60 ):
			if ( $format === 'iso' ):
				return 'PT0H'.intval( $minutes ).'M';
			else:
				/* translators: singular and plural number of minutes (shorthand) */
				echo self::singular_plural( sprintf( esc_html__( '%d min','cooked' ), number_format_i18n($minutes) ), sprintf( esc_html__( '%d mins','cooked' ), number_format_i18n($minutes) ), $minutes );
			endif;
		elseif ( $minutes < 1440 ):
			$hours = floor( $minutes / 60 );
			$minutes_left = $minutes - ( $hours * 60 );
			if ( $format === 'iso' ):
				return 'PT'.intval( $hours ).'H'.( $minutes_left ? intval( $minutes_left ) : 0 ).'M';
			else:
				/* translators: singular and plural number of hours (shorthand) */
				echo self::singular_plural( sprintf( esc_html__( '%d hr','cooked' ), number_format_i18n($hours) ), sprintf( esc_html__( '%d hrs','cooked' ), number_format_i18n($hours) ), $hours );
				/* translators: singular and plural number of minutes (shorthand) */
				echo ( $minutes_left ? '&nbsp;' . self::singular_plural( sprintf( esc_html__( '%d min','cooked' ), number_format_i18n($minutes_left) ), sprintf( esc_html__( '%d mins','cooked' ), number_format_i18n($minutes_left) ), $minutes_left ) : '' );
			endif;
		else:
			$days = floor( $minutes / 24 / 60 );
			$hours_left = 0;
			$minutes_left = $minutes - ( $days * 24 * 60 );
			if ( $minutes_left > 60 ):
				$hours_left = floor( $minutes_left / 60 );
				$minutes_left = $minutes_left - ( $hours_left * 60 );
			endif;
			if ( $format === 'iso' ):
				return 'P'.intval( $days ).'DT'.( $hours_left ? intval( $hours_left ) : 0 ).'H'.( $minutes_left ? intval( $minutes_left ) : 0 ).'M';
			else:
				/* translators: singular and plural number of days */
				echo self::singular_plural( sprintf( esc_html__( '%d day','cooked' ), number_format_i18n($days) ), sprintf( esc_html__( '%d days','cooked' ), number_format_i18n($days) ), $days );
				/* translators: singular and plural number of hours (shorthand) */
				echo ( $hours_left ? '&nbsp;' . self::singular_plural( sprintf( esc_html__( '%d hr','cooked' ), number_format_i18n($hours_left) ), sprintf( esc_html__( '%d hrs','cooked' ), number_format_i18n($hours_left) ), $hours_left ) : '' );
				/* translators: singular and plural number of minutes (shorthand) */
				echo ( $minutes_left ? '&nbsp;' . self::singular_plural( sprintf( esc_html__( '%d min','cooked' ), number_format_i18n($minutes_left) ), sprintf( esc_html__( '%d mins','cooked' ), number_format_i18n($minutes_left) ), $minutes_left ) : '' );
			endif;
		endif;

		return ob_get_clean();

	}

}
