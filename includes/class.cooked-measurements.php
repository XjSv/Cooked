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
 * Cooked_Measurements Class
 *
 * This class handles the Cooked Measurements.
 *
 * @since 1.0.0
 */
class Cooked_Measurements {

    public static function get() {

        // Use the "cooked_measurements" filter to add your own measurements.
        $measurements = apply_filters('cooked_measurements', [
            'g' => [
				'singular_abbr' => _x( 'g', 'Grams Abbreviation (Singular)', 'cooked' ),
				'plural_abbr' => _x( 'g', 'Grams Abbreviation (Plural)', 'cooked' ),
				'singular' => __( 'gram', 'cooked' ),
				'plural' => __( 'grams', 'cooked' ),
				'variations' => [ 'g', 'g.', 'gram', 'grams' ],
			],
            'kg' => [
                'singular_abbr' => _x('kg', 'Kilograms Abbreviation (Singular)', 'cooked'),
                'plural_abbr' => _x('kg', 'Kilograms Abbreviation (Plural)', 'cooked'),
                'singular' => __('kilogram', 'cooked'),
                'plural' => __('kilograms', 'cooked'),
                'variations' => ['kg', 'kg.', 'kilogram', 'kilograms'],
            ],
            'mg' => [
                'singular_abbr' => __('mg', 'cooked'),
                'plural_abbr' => __('mg', 'cooked'),
                'singular' => __('milligram', 'cooked'),
                'plural' => __('milligrams', 'cooked'),
                'variations' => ['mg', 'mg.', 'milligram', 'milligrams'],
            ],
            'oz' => [
                'singular_abbr' => __('oz', 'cooked'),
                'plural_abbr' => __('oz', 'cooked'),
                'singular' => __('ounce', 'cooked'),
                'plural' => __('ounces', 'cooked'),
                'variations' => ['oz', 'oz.', 'ounce', 'ounces'],
            ],
            'floz' => [
                'singular_abbr' => __('fl oz', 'cooked'),
                'plural_abbr' => __('fl oz', 'cooked'),
                'singular' => __('fluid ounce', 'cooked'),
                'plural' => __('fluid ounces', 'cooked'),
                'variations' => ['fl oz', 'fl oz.', 'fl. oz.', 'fluid ounce', 'fluid ounces'],
            ],
            'cup' => [
                'singular_abbr' => __('cup', 'cooked'),
                'plural_abbr' => __('cups', 'cooked'),
                'singular' => __('cup', 'cooked'),
                'plural' => __('cups', 'cooked'),
                'variations' => ['c', 'c.', 'cup', 'cups'],
            ],
            'tsp' => [
                'singular_abbr' => __('tsp', 'cooked'),
                'plural_abbr' => __('tsp', 'cooked'),
                'singular' => __('teaspoon', 'cooked'),
                'plural' => __('teaspoons', 'cooked'),
                'variations' => ['t', 'tsp.', 'tsp', 'teaspoon', 'teaspoons'],
            ],
            'tbsp' => [
                'singular_abbr' => __('tbsp', 'cooked'),
                'plural_abbr' => __('tbsp', 'cooked'),
                'singular' => __('tablespoon', 'cooked'),
                'plural' => __('tablespoons', 'cooked'),
                'variations' => ['T', 'tbl.', 'tbl', 'tbs.', 'tbs', 'tbsp.', 'tbsp', 'tablespoon', 'tablespoons'],
            ],
            'dl' => [
                'singular_abbr' => __('dl', 'cooked'),
                'plural_abbr' => __('dl', 'cooked'),
                'singular' => __('deciliter', 'cooked'),
                'plural' => __('deciliters', 'cooked'),
                'variations' => ['dl', 'dl.', 'deciliter', 'deciliters'],
            ],
            'ml' => [
                'singular_abbr' => __('ml', 'cooked'),
                'plural_abbr' => __('ml', 'cooked'),
                'singular' => __('milliliter', 'cooked'),
                'plural' => __('milliliters', 'cooked'),
                'variations' => ['ml', 'ml.', 'mL', 'mL.', 'cc', 'milliliter', 'milliliters', 'millilitre', 'millilitres'],
            ],
            'l' => [
                'singular_abbr' => __('l', 'cooked'),
                'plural_abbr' => __('l', 'cooked'),
                'singular' => __('liter', 'cooked'),
                'plural' => __('liters', 'cooked'),
                'variations' => ['l', 'l.', 'L', 'L.', 'liter', 'liters', 'litre', 'litres'],
            ],
            'stick' => [
                'singular_abbr' => __('stick', 'cooked'),
                'plural_abbr' => __('sticks', 'cooked'),
                'singular' => __('stick', 'cooked'),
                'plural' => __('sticks', 'cooked'),
                'variations' => ['stick', 'sticks'],
            ],
            'lb' => [
                'singular_abbr' => __('lb', 'cooked'),
                'plural_abbr' => __('lbs', 'cooked'),
                'singular' => __('pound', 'cooked'),
                'plural' => __('pounds', 'cooked'),
                'variations' => ['lb', 'lbs', 'lb.', 'lbs.', 'pound', 'pounds'],
            ],
            'dash' => [
                'singular_abbr' => __('dash', 'cooked'),
                'plural_abbr' => __('dashes', 'cooked'),
                'singular' => __('dash', 'cooked'),
                'plural' => __('dashes', 'cooked'),
                'variations' => ['dash', 'dashes'],
            ],
            'drop' => [
                'singular_abbr' => __('drop', 'cooked'),
                'plural_abbr' => __('drops', 'cooked'),
                'singular' => __('drop', 'cooked'),
                'plural' => __('drops', 'cooked'),
                'variations' => ['drop', 'drops'],
            ],
            'gal' => [
                'singular_abbr' => __('gal', 'cooked'),
                'plural_abbr' => __('gals', 'cooked'),
                'singular' => __('gallon', 'cooked'),
                'plural' => __('gallons', 'cooked'),
                'variations' => ['G', 'G.', 'gal', 'gal.', 'gallon', 'gallons'],
            ],
            'pinch' => [
                'singular_abbr' => __('pinch', 'cooked'),
                'plural_abbr' => __('pinches', 'cooked'),
                'singular' => __('pinch', 'cooked'),
                'plural' => __('pinches', 'cooked'),
                'variations' => ['pinch', 'pinches'],
            ],
            'pt' => [
                'singular_abbr' => __('pt', 'cooked'),
                'plural_abbr' => __('pt', 'cooked'),
                'singular' => __('pint', 'cooked'),
                'plural' => __('pints', 'cooked'),
                'variations' => ['p', 'p.', 'pt', 'pt.', 'pts', 'pts.', 'fl pt', 'fl. pt.', 'pint', 'pints'],
            ],
            'qt' => [
                'singular_abbr' => __('qt', 'cooked'),
                'plural_abbr' => __('qts', 'cooked'),
                'singular' => __('quart', 'cooked'),
                'plural' => __('quarts', 'cooked'),
                'variations' => ['q', 'q.', 'qt', 'qt.', 'qts', 'qts.', 'fl qt', 'fl. qt.', 'quart', 'quarts'],
            ],
            'drizzle' => [
                'singular_abbr' => __('drizzle', 'cooked'),
                'plural_abbr' => __('drizzle', 'cooked'),
                'singular' => __('Drizzle', 'cooked'),
                'plural' => __('Drizzle', 'cooked'),
                'variations' => ['drizzle'],
            ],
            'clove' => [
                'singular_abbr' => __('clove', 'cooked'),
                'plural_abbr' => __('cloves', 'cooked'),
                'singular' => __('clove', 'cooked'),
                'plural' => __('cloves', 'cooked'),
                'variations' => [ 'clove', 'cloves' ],
            ],
            'jar' => [
                'singular_abbr' => __('jar', 'cooked'),
                'plural_abbr' => __('jars', 'cooked'),
                'singular' => __('jar', 'cooked'),
                'plural' => __('jars', 'cooked'),
                'variations' => [ 'jar', 'jars' ],
            ],
            'can' => [
                'singular_abbr' => __('can', 'cooked'),
                'plural_abbr' => __('cans', 'cooked'),
                'singular' => __('can', 'cooked'),
                'plural' => __('cans', 'cooked'),
                'variations' => [ 'can', 'cans' ],
            ],
        ]);

        return $measurements;
    }

    public static function nutrition_facts() {
        global $_cooked_settings;

        // Use the "cooked_nutrition_facts" filter to add your own nutrition facts.
        // Reference: https://www.fda.gov/food/nutrition-facts-label/daily-value-nutrition-and-supplement-facts-labels
        $nutrition_facts = apply_filters('cooked_nutrition_facts', [
            'top' => [
                'servings' => [
                    'name' => __('Servings', 'cooked'),
                    'type' => 'number'
                ],
                'serving_size' => [
                    'name' => __('Serving size', 'cooked'),
                    'type' => 'text'
                ],
            ],

            'mid' => [
                'calories' => [
                    'name' => __('Calories', 'cooked'),
                    'type' => 'number'
                ]
            ],

            'main' => [
                'fat' => [
                    'name' => __('Total Fat', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'g',
                    'pdv' => apply_filters('cooked_pdv_fat', 78),
                    'subs' => [
                        'sat_fat' => [
                            'name' => __('Saturated Fat', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g',
                            'pdv' => apply_filters('cooked_pdv_satfat', 20)
                        ],
                        'trans_fat' => [
                            'name' => __('Trans Fat', 'cooked'),
                            'nutrition_info_name' => __('<i>Trans</i> Fat', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g'
                        ],
                        'monounsaturated_fat' => [
                            'name' => __('Monounsaturated Fat', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g'
                        ],
                        'polyunsaturated_fat' => [
                            'name' => __('Polyunsaturated Fat', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g'
                        ]
                    ]
                ],
                'cholesterol' => [
                    'name' => __('Cholesterol', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_cholesterol', 300)
                ],
                'sodium' => [
                    'name' => __('Sodium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_sodium', 2300)
                ],
                'carbs' => [
                    'name' => (isset($_cooked_settings['carb_format']) && $_cooked_settings['carb_format'] == 'total' ? __('Total Carbohydrate', 'cooked') : __('Net Carbohydrate', 'cooked')),
                    'type' => 'number',
                    'measurement' => 'g',
                    'pdv' => apply_filters('cooked_pdv_carbs', 275),
                    'subs' => [
                        'fiber' => [
                            'name' => __('Dietary Fiber', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g',
                            'pdv' => apply_filters('cooked_pdv_fiber', 28)
                        ],
                        'sugars' => [
                            'name' => __('Total Sugars', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g',
                            //'pdv' => apply_filters('cooked_pdv_sugars', 28)
                        ],
                        'added_sugars' => [
                            'name' => __('Added Sugars', 'cooked'),
                            'type' => 'number',
                            'measurement' => 'g',
                            'pdv' => apply_filters('cooked_pdv_added_sugars', 50)
                        ]
                    ]
                ],
                'protein' => [
                    'name' => __('Protein', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'g',
                    //'pdv' => apply_filters('cooked_pdv_protein', 50)
                ]
            ],

            'bottom' => [
                'vitamin_a' => [
                    'name' => __('Vitamin A', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_a', 900)
                ],
                'vitamin_c' => [
                    'name' => __('Vitamin C', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_c', 90)
                ],
                'calcium' => [
                    'name' => __('Calcium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_calcium', 1300)
                ],
                'iron' => [
                    'name' => __('Iron', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_iron', 18)
                ],
                'potassium' => [
                    'name' => __('Potassium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_potassium', 4700)
                ],
                'vitamin_d' => [
                    'name' => __('Vitamin D', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_d', 20)
                ],
                'vitamin_e' => [
                    'name' => __('Vitamin E', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_e', 15)
                ],
                'vitamin_k' => [
                    'name' => __('Vitamin K', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_k', 120)
                ],
                'thiamin' => [
                    'name' => __('Thiamin', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_thiamin', 1.2)
                ],
                'riboflavin' => [
                    'name' => __('Riboflavin', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_riboflavin', 1.3)
                ],
                'niacin' => [
                    'name' => __('Niacin', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_niacin', 16)
                ],
                'vitamin_b6' => [
                    'name' => __('Vitamin B6', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_b6', 1.7)
                ],
                'folate' => [
                    'name' => __('Folate', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_folate', 400)
                ],
                'vitamin_b12' => [
                    'name' => __('Vitamin B12', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_vitamin_b12', 2.4)
                ],
                'biotin' => [
                    'name' => __('Biotin', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_biotin', 30)
                ],
                'pantothenic_acid' => [
                    'name' => __('Pantothenic Acid', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_pantothenic_acid', 5)
                ],
                'phosphorus' => [
                    'name' => __('Phosphorus', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_phosphorus', 1250)
                ],
                'iodine' => [
                    'name' => __('Iodine', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_iodine', 150)
                ],
                'magnesium' => [
                    'name' => __('Magnesium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_magnesium', 420)
                ],
                'zinc' => [
                    'name' => __('Zinc', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_zinc', 11)
                ],
                'selenium' => [
                    'name' => __('Selenium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_selenium', 55)
                ],
                'copper' => [
                    'name' => __('Copper', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_copper', 0.9)
                ],
                'manganese' => [
                    'name' => __('Manganese', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_manganese', 2.3)
                ],
                'chromium' => [
                    'name' => __('Chromium', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_chromium', 35)
                ],
                'molybdenum' => [
                    'name' => __('Molybdenum', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mcg',
                    'pdv' => apply_filters('cooked_pdv_molybdenum', 45)
                ],
                'chloride' => [
                    'name' => __('Chloride', 'cooked'),
                    'type' => 'number',
                    'measurement' => 'mg',
                    'pdv' => apply_filters('cooked_pdv_chloride', 2300)
                ]
            ]
        ]);

        return $nutrition_facts;
    }

    public static function singular_plural( $singular_text, $plural_text, $count ) {
        if ($count <= 1 ):
            return $singular_text;
        else:
            return $plural_text;
        endif;
    }

    public function cleanup_amount( $amount ) {
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

    public function locale_formatted( $amount ) {
        global $wp_locale;

        if ( isset( $wp_locale ) ) {
            $amount = str_replace( $wp_locale->number_format['decimal_point'], '.', str_replace( $wp_locale->number_format['thousands_sep'], '', $amount) );
        } else {
            $amount = str_replace( ',', '', $amount);
        }

        return $amount;
    }

    public function format_amount( $float_amount = 0, $format = 'fraction' ) {
        if ( $format == 'decimal' ):
            $amount = $float_amount ? number_format_i18n( floatval( $float_amount ), 2 ) : 0;
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

    public function math($expression = false) {
        if ($expression) {
            $expression = preg_replace( '/[^0-9\+\-\*\/\(\)\.\,]/', '', esc_html($expression) );

            $invalid = '/' == substr($expression, -1) ? true : false; // More checks can be done here if needed.

            if (!$invalid) {
                try {
                    $mathExecutor = new MathExecutor();
                    return $mathExecutor->execute($expression);
                } catch (Exception $e) {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
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
        $fraction_parts = explode('/', $fraction);
        $decimal = $fraction_parts[0] / $fraction_parts[1];

        if ($decimal < 1):
            $decimal_array = [0.125, 0.166, 0.200, 0.250, 0.333, 0.500, 0.666, 0.750, 0.875];
            $closest_decimal = self::get_closest_decimal( $decimal, $decimal_array );

            switch ($closest_decimal):
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

    public function get_fraction_array() {
        $fractions_a = [
            ['⅛', '&#8539;', '&#215B;', '&frac18;'],
            ['⅙', '&#8537;', '&#x2159;', '&frac16;'],
            ['⅕', '&#8533;', '&#x2155;', '&frac15;'],
            ['¼', '&#188;', '&#xBC;', '&frac14;'],
            ['⅓', '&#8531;', '&#2153;', '&frac13;'],
            ['½', '&#189;', '&#xBD;', '&frac12;'],
            ['⅔', '&#8532;', '&#2154;', '&frac23;'],
            ['⅝', '&#8541;', '&#x215D;', '&frac58;'],
            ['¾', '&#190;', '&#xBE;', '&frac34;'],
            ['⅞', '&#8542;', '&#215E;', '&frac78;']
        ];

        $fractions_b = [
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
        ];

        $fractions_c = [
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
        ];

        $fraction_array = ['fractions_a' => $fractions_a, 'fractions_b' => $fractions_b, 'fractions_c' => $fractions_c];
        return $fraction_array;
    }

    public static function time_format( $minutes, $format = 'default' ) {
        ob_start();

        if ( $minutes < 60 ):
            if ( $format === 'iso' ):
                return 'PT0H'.intval( $minutes ).'M';
            else:
                /* translators: singular and plural number of minutes (shorthand) */
                echo self::singular_plural( sprintf( __( '%d min','cooked' ), number_format_i18n($minutes) ), sprintf( __( '%d mins','cooked' ), number_format_i18n($minutes) ), $minutes );
            endif;
        elseif ( $minutes < 1440 ):
            $hours = floor( $minutes / 60 );
            $minutes_left = $minutes - ( $hours * 60 );
            if ( $format === 'iso' ):
                return 'PT'.intval( $hours ).'H'.( $minutes_left ? intval( $minutes_left ) : 0 ).'M';
            else:
                /* translators: singular and plural number of hours (shorthand) */
                echo self::singular_plural( sprintf( __( '%d hr','cooked' ), number_format_i18n($hours) ), sprintf( __( '%d hrs','cooked' ), number_format_i18n($hours) ), $hours );
                /* translators: singular and plural number of minutes (shorthand) */
                echo ( $minutes_left ? '&nbsp;' . self::singular_plural( sprintf( __( '%d min','cooked' ), number_format_i18n($minutes_left) ), sprintf( __( '%d mins','cooked' ), number_format_i18n($minutes_left) ), $minutes_left ) : '' );
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
                echo self::singular_plural( sprintf( __( '%d day','cooked' ), number_format_i18n($days) ), sprintf( __( '%d days','cooked' ), number_format_i18n($days) ), $days );
                /* translators: singular and plural number of hours (shorthand) */
                echo ( $hours_left ? '&nbsp;' . self::singular_plural( sprintf( __( '%d hr','cooked' ), number_format_i18n($hours_left) ), sprintf( __( '%d hrs','cooked' ), number_format_i18n($hours_left) ), $hours_left ) : '' );
                /* translators: singular and plural number of minutes (shorthand) */
                echo ( $minutes_left ? '&nbsp;' . self::singular_plural( sprintf( __( '%d min','cooked' ), number_format_i18n($minutes_left) ), sprintf( __( '%d mins','cooked' ), number_format_i18n($minutes_left) ), $minutes_left ) : '' );
            endif;
        endif;

        return ob_get_clean();
    }

}
