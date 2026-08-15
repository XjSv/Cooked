<?php

class MeasurementsFiltersTest extends FilterTestCase {

    public function test_cooked_measurements_can_add_a_unit() {
        $measurements = $this->with_filter(
            'cooked_measurements',
            function ( $value ) {
                $value['pinch'] = [
                    'singular_abbr' => 'pinch',
                    'plural_abbr'   => 'pinches',
                    'singular'      => 'pinch',
                    'plural'        => 'pinches',
                    'system'        => 'imperial',
                    'variations'    => [ 'pinch', 'pinches' ],
                ];
                return $value;
            },
            function () {
                return Cooked_Measurements::get();
            }
        );

        $this->assertArrayHasKey( 'pinch', $measurements );
        $this->assertSame( 'pinches', Cooked_Measurements::singular_plural( $measurements['pinch']['singular'], $measurements['pinch']['plural'], 3 ) );
    }

    public function test_cooked_nutrition_facts_can_add_a_nutrient() {
        $facts = $this->with_filter(
            'cooked_nutrition_facts',
            function ( $value ) {
                $value['bottom']['omega3'] = [
                    'name'        => 'Omega-3',
                    'type'        => 'number',
                    'measurement' => 'g',
                    'pdv'         => 1,
                ];
                return $value;
            },
            function () {
                return Cooked_Measurements::nutrition_facts();
            }
        );

        $this->assertArrayHasKey( 'omega3', $facts['bottom'] );
        $this->assertSame( 'Omega-3', $facts['bottom']['omega3']['name'] );
    }

    /**
     * @dataProvider pdv_filter_provider
     */
    public function test_cooked_pdv_filters_change_daily_values( $hook, $section, $key, $subkey ) {
        $facts = $this->with_filter(
            $hook,
            function () {
                return 12345;
            },
            function () {
                return Cooked_Measurements::nutrition_facts();
            }
        );

        if ( $subkey ) {
            $this->assertSame( 12345, $facts[ $section ][ $key ]['subs'][ $subkey ]['pdv'] );
        } else {
            $this->assertSame( 12345, $facts[ $section ][ $key ]['pdv'] );
        }
    }

    public function pdv_filter_provider() {
        return [
            'fat'              => [ 'cooked_pdv_fat', 'main', 'fat', null ],
            'satfat'           => [ 'cooked_pdv_satfat', 'main', 'fat', 'sat_fat' ],
            'cholesterol'      => [ 'cooked_pdv_cholesterol', 'main', 'cholesterol', null ],
            'sodium'           => [ 'cooked_pdv_sodium', 'main', 'sodium', null ],
            'carbs'            => [ 'cooked_pdv_carbs', 'main', 'carbs', null ],
            'fiber'            => [ 'cooked_pdv_fiber', 'main', 'carbs', 'fiber' ],
            'added_sugars'     => [ 'cooked_pdv_added_sugars', 'main', 'carbs', 'added_sugars' ],
            'vitamin_a'        => [ 'cooked_pdv_vitamin_a', 'bottom', 'vitamin_a', null ],
            'vitamin_c'        => [ 'cooked_pdv_vitamin_c', 'bottom', 'vitamin_c', null ],
            'calcium'          => [ 'cooked_pdv_calcium', 'bottom', 'calcium', null ],
            'iron'             => [ 'cooked_pdv_iron', 'bottom', 'iron', null ],
            'potassium'        => [ 'cooked_pdv_potassium', 'bottom', 'potassium', null ],
            'vitamin_d'        => [ 'cooked_pdv_vitamin_d', 'bottom', 'vitamin_d', null ],
            'vitamin_e'        => [ 'cooked_pdv_vitamin_e', 'bottom', 'vitamin_e', null ],
            'vitamin_k'        => [ 'cooked_pdv_vitamin_k', 'bottom', 'vitamin_k', null ],
            'thiamin'          => [ 'cooked_pdv_thiamin', 'bottom', 'thiamin', null ],
            'riboflavin'       => [ 'cooked_pdv_riboflavin', 'bottom', 'riboflavin', null ],
            'niacin'           => [ 'cooked_pdv_niacin', 'bottom', 'niacin', null ],
            'vitamin_b6'       => [ 'cooked_pdv_vitamin_b6', 'bottom', 'vitamin_b6', null ],
            'folate'           => [ 'cooked_pdv_folate', 'bottom', 'folate', null ],
            'vitamin_b12'      => [ 'cooked_pdv_vitamin_b12', 'bottom', 'vitamin_b12', null ],
            'biotin'           => [ 'cooked_pdv_biotin', 'bottom', 'biotin', null ],
            'pantothenic_acid' => [ 'cooked_pdv_pantothenic_acid', 'bottom', 'pantothenic_acid', null ],
            'phosphorus'       => [ 'cooked_pdv_phosphorus', 'bottom', 'phosphorus', null ],
            'iodine'           => [ 'cooked_pdv_iodine', 'bottom', 'iodine', null ],
            'magnesium'        => [ 'cooked_pdv_magnesium', 'bottom', 'magnesium', null ],
            'zinc'             => [ 'cooked_pdv_zinc', 'bottom', 'zinc', null ],
            'selenium'         => [ 'cooked_pdv_selenium', 'bottom', 'selenium', null ],
            'copper'           => [ 'cooked_pdv_copper', 'bottom', 'copper', null ],
            'manganese'        => [ 'cooked_pdv_manganese', 'bottom', 'manganese', null ],
            'chromium'         => [ 'cooked_pdv_chromium', 'bottom', 'chromium', null ],
            'molybdenum'       => [ 'cooked_pdv_molybdenum', 'bottom', 'molybdenum', null ],
            'chloride'         => [ 'cooked_pdv_chloride', 'bottom', 'chloride', null ],
        ];
    }
}
