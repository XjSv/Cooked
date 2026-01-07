<?php
/**
 * CSV Import Handler
 *
 * @package     Cooked
 * @subpackage  CSV Import
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_CSV_Import Class
 *
 * This class handles the import of recipes from CSV files.
 *
 * @since 1.0.0
 */
class Cooked_CSV_Import {

    /**
     * Parse and import recipes from CSV file
     *
     * @param string $file_path Path to the CSV file
     * @return array Results array with success count and errors
     */
    public static function import_from_file( $file_path ) {
        global $_cooked_settings;

        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0
        ];

        // Ensure required classes are loaded
        if ( ! class_exists( 'Cooked_Recipes' ) ) {
            $results['errors'][] = __( 'Cooked_Recipes class not found. Plugin may not be properly loaded.', 'cooked' );
            return $results;
        }

        if ( ! class_exists( 'Cooked_Measurements' ) ) {
            $results['errors'][] = __( 'Cooked_Measurements class not found. Plugin may not be properly loaded.', 'cooked' );
            return $results;
        }

        if ( ! class_exists( 'Cooked_Recipe_Meta' ) ) {
            $results['errors'][] = __( 'Cooked_Recipe_Meta class not found. Plugin may not be properly loaded.', 'cooked' );
            return $results;
        }

        if ( ! file_exists( $file_path ) ) {
            $results['errors'][] = __( 'CSV file not found.', 'cooked' );
            return $results;
        }

        // Open and parse CSV file
        $handle = fopen( $file_path, 'r' );
        if ( $handle === false ) {
            $results['errors'][] = __( 'Could not open CSV file.', 'cooked' );
            return $results;
        }

        // Read header row
        $headers = fgetcsv( $handle );
        if ( $headers === false || empty( $headers ) ) {
            $results['errors'][] = __( 'CSV file is empty or invalid.', 'cooked' );
            fclose( $handle );
            return $results;
        }

        // Normalize headers (trim and lowercase)
        $headers = array_map( 'trim', $headers );
        $headers = array_map( 'strtolower', $headers );

        // Check for required title column
        if ( ! in_array( 'title', $headers ) ) {
            $results['errors'][] = __( 'CSV file must contain a "title" column.', 'cooked' );
            fclose( $handle );
            return $results;
        }

        $row_number = 1;
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_number++;
            $results['total']++;

            // Skip empty rows
            if ( empty( array_filter( $row ) ) ) {
                continue;
            }

            // Map row data to headers
            $data = [];
            foreach ( $headers as $index => $header ) {
                $data[ $header ] = isset( $row[ $index ] ) ? trim( $row[ $index ] ) : '';
            }

            // Import this recipe
            try {
                $import_result = self::import_recipe( $data, $row_number );
                if ( $import_result['success'] ) {
                    $results['success']++;
                } else {
                    $error_msg = isset( $import_result['error'] ) ? $import_result['error'] : __( 'Unknown error', 'cooked' );
                    $results['errors'][] = sprintf( __( 'Row %d: %s', 'cooked' ), $row_number, $error_msg );
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Cooked CSV Import Error Row ' . $row_number . ': ' . $error_msg );
                    }
                }
            } catch ( Exception $e ) {
                $error_msg = $e->getMessage();
                $results['errors'][] = sprintf( __( 'Row %d: %s', 'cooked' ), $row_number, $error_msg );
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Cooked CSV Import Exception Row ' . $row_number . ': ' . $error_msg );
                    error_log( 'Stack trace: ' . $e->getTraceAsString() );
                }
            }
        }

        fclose( $handle );
        return $results;
    }

    /**
     * Import a single recipe from CSV data
     *
     * @param array $data Recipe data from CSV row
     * @param int $row_number Row number for error reporting
     * @return array Result with success status and error message if any
     */
    public static function import_recipe( $data, $row_number = 0 ) {
        global $_cooked_settings;

        // Validate required fields
        if ( empty( $data['title'] ) ) {
            return [
                'success' => false,
                'error' => __( 'Title is required', 'cooked' )
            ];
        }

        // Get default content
        if ( isset( $_cooked_settings['default_content'] ) ) {
            $default_content = stripslashes( $_cooked_settings['default_content'] );
        } else {
            $default_content = Cooked_Recipes::default_content();
        }

        // Create new recipe post
        $new_recipe = [
            'post_type' => 'cp_recipe',
            'post_status' => 'draft',
            'post_title' => sanitize_text_field( $data['title'] ),
            'post_content' => '',
            'post_author' => get_current_user_id(),
        ];

        $recipe_id = wp_insert_post( $new_recipe );
        if ( is_wp_error( $recipe_id ) ) {
            return [
                'success' => false,
                'error' => $recipe_id->get_error_message()
            ];
        }

        // Prepare recipe meta
        $recipe_meta = [];
        $recipe_meta['cooked_version'] = COOKED_VERSION;
        $recipe_meta['content'] = $default_content;
        $recipe_meta['excerpt'] = isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : '';
        $recipe_meta['seo_description'] = isset( $data['seo_description'] ) ? sanitize_text_field( $data['seo_description'] ) : ( isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : '' );
        $recipe_meta['notes'] = isset( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '';

        // Difficulty level
        $difficulty_level = isset( $data['difficulty_level'] ) ? intval( $data['difficulty_level'] ) : 0;
        if ( $difficulty_level < 1 || $difficulty_level > 3 ) {
            $difficulty_level = 0;
        }
        $recipe_meta['difficulty_level'] = $difficulty_level;

        // Times
        $recipe_meta['prep_time'] = isset( $data['prep_time'] ) ? intval( $data['prep_time'] ) : 0;
        $recipe_meta['cook_time'] = isset( $data['cook_time'] ) ? intval( $data['cook_time'] ) : 0;
        $recipe_meta['total_time'] = $recipe_meta['prep_time'] + $recipe_meta['cook_time'];
        if ( isset( $data['total_time'] ) && ! empty( $data['total_time'] ) ) {
            $recipe_meta['total_time'] = intval( $data['total_time'] );
        }

        // Parse ingredients
        $recipe_meta['ingredients'] = [];
        if ( ! empty( $data['ingredients'] ) ) {
            $measurements = Cooked_Measurements::get();

            // Split by | to get all parts
            // Format: amount|measurement|name|amount|measurement|name||sub_amount|sub_measurement|sub_name|...
            // When we see ||, it becomes two consecutive empty strings in the array
            $all_parts = array_map( 'trim', explode( '|', $data['ingredients'] ) );
            $i = 0;
            
            while ( $i < count( $all_parts ) ) {
                // Skip empty parts (they come from || separator)
                if ( empty( $all_parts[ $i ] ) ) {
                    $i++;
                    continue;
                }
                
                $part = $all_parts[ $i ];
                
                // Check if it's a section heading (starts with #)
                if ( strpos( $part, '#' ) === 0 ) {
                    $recipe_meta['ingredients'][] = [
                        'section_heading_name' => trim( $part, '#' ),
                    ];
                    $i++;
                    continue;
                }
                
                // Collect next 3 non-empty parts for an ingredient (amount|measurement|name)
                $ingredient_parts = [];
                $j = $i;
                while ( count( $ingredient_parts ) < 3 && $j < count( $all_parts ) ) {
                    $p = trim( $all_parts[ $j ] );
                    if ( ! empty( $p ) ) {
                        $ingredient_parts[] = $p;
                    }
                    $j++;
                }
                
                if ( count( $ingredient_parts ) >= 3 ) {
                    $ingredient = self::parse_ingredient_parts( $ingredient_parts, $measurements );
                    $i = $j; // Move past the collected parts
                    
                    // Check if next parts are empty (indicating || separator for substitution)
                    // Look ahead to see if we have empty parts followed by non-empty parts
                    $next_empty_count = 0;
                    $k = $i;
                    while ( $k < count( $all_parts ) && empty( trim( $all_parts[ $k ] ) ) ) {
                        $next_empty_count++;
                        $k++;
                    }
                    
                    // If we have empty parts (from ||) and then more parts, it's a substitution
                    if ( $next_empty_count > 0 && $k < count( $all_parts ) ) {
                        // Collect substitution parts (next 3 non-empty parts)
                        $sub_parts = [];
                        $sub_i = $k;
                        while ( count( $sub_parts ) < 3 && $sub_i < count( $all_parts ) ) {
                            $sub_part = trim( $all_parts[ $sub_i ] );
                            if ( ! empty( $sub_part ) ) {
                                $sub_parts[] = $sub_part;
                            }
                            $sub_i++;
                        }
                        
                            // Parse substitution
                            if ( count( $sub_parts ) >= 3 ) {
                                $ingredient['sub_amount'] = sanitize_text_field( $sub_parts[0] );
                                $sub_measurement = sanitize_text_field( $sub_parts[1] );
                                $matched_sub_measurement = self::match_measurement( $sub_measurement, $measurements );
                                if ( $matched_sub_measurement ) {
                                    $ingredient['sub_measurement'] = $matched_sub_measurement;
                                }
                                $ingredient['sub_name'] = sanitize_text_field( $sub_parts[2] );
                            } elseif ( count( $sub_parts ) == 2 ) {
                                $ingredient['sub_amount'] = sanitize_text_field( $sub_parts[0] );
                                $ingredient['sub_name'] = sanitize_text_field( $sub_parts[1] );
                            } elseif ( count( $sub_parts ) == 1 ) {
                                $ingredient['sub_name'] = sanitize_text_field( $sub_parts[0] );
                            }
                        
                        $i = $sub_i; // Move past substitution
                    }
                    
                    $recipe_meta['ingredients'][] = $ingredient;
                } else {
                    // Not enough parts for a complete ingredient, skip
                    $i++;
                }
            }
        }

        // Parse directions
        $recipe_meta['directions'] = [];
        if ( ! empty( $data['directions'] ) ) {
            $directions = explode( '|', $data['directions'] );
            foreach ( $directions as $direction_string ) {
                $direction_string = trim( $direction_string );
                if ( empty( $direction_string ) ) {
                    continue;
                }

                // Check if it's a section heading (starts with #)
                if ( strpos( $direction_string, '#' ) === 0 ) {
                    $recipe_meta['directions'][] = [
                        'section_heading_name' => trim( $direction_string, '#' ),
                    ];
                    continue;
                }

                $recipe_meta['directions'][] = [
                    'content' => wp_kses_post( $direction_string ),
                ];
            }
        }

        // Nutrition data
        $recipe_meta['nutrition'] = [];
        if ( isset( $data['servings'] ) && ! empty( $data['servings'] ) ) {
            $recipe_meta['nutrition']['servings'] = sanitize_text_field( $data['servings'] );
        }
        if ( isset( $data['calories'] ) && ! empty( $data['calories'] ) ) {
            $recipe_meta['nutrition']['calories'] = intval( $data['calories'] );
        }

        // Save recipe meta
        $recipe_meta = Cooked_Recipe_Meta::meta_cleanup( $recipe_meta );
        update_post_meta( $recipe_id, '_recipe_settings', $recipe_meta );

        // Update post excerpt
        $recipe_excerpt = ! empty( $recipe_meta['excerpt'] ) ? $recipe_meta['excerpt'] : get_the_title( $recipe_id );
        $seo_content = apply_filters( 'cooked_seo_recipe_content', '<h2>' . wp_kses_post( $recipe_excerpt ) . '</h2><h3>' . __( 'Ingredients', 'cooked' ) . '</h3>[cooked-ingredients checkboxes=false]<h3>' . __( 'Directions', 'cooked' ) . '</h3>[cooked-directions numbers=false]' );
        $seo_content = do_shortcode( $seo_content );

        $should_update_content = apply_filters( 'cooked_should_update_post_content', true, $recipe_id );
        if ( $should_update_content ) {
            wp_update_post( [
                'ID' => $recipe_id,
                'post_excerpt' => $recipe_excerpt,
                'post_content' => $seo_content
            ] );
        } else {
            wp_update_post( [
                'ID' => $recipe_id,
                'post_excerpt' => $recipe_excerpt
            ] );
        }

        // Handle taxonomies
        if ( ! empty( $data['categories'] ) ) {
            $categories = array_map( 'trim', explode( ',', $data['categories'] ) );
            $category_ids = [];
            foreach ( $categories as $category_name ) {
                if ( ! empty( $category_name ) ) {
                    $term = get_term_by( 'name', $category_name, 'cp_recipe_category' );
                    if ( ! $term ) {
                        $term = wp_insert_term( $category_name, 'cp_recipe_category' );
                        if ( ! is_wp_error( $term ) ) {
                            $category_ids[] = $term['term_id'];
                        }
                    } else {
                        $category_ids[] = $term->term_id;
                    }
                }
            }
            if ( ! empty( $category_ids ) ) {
                wp_set_object_terms( $recipe_id, $category_ids, 'cp_recipe_category' );
            }
        }

        if ( ! empty( $data['tags'] ) ) {
            $tags = array_map( 'trim', explode( ',', $data['tags'] ) );
            $tag_ids = [];
            foreach ( $tags as $tag_name ) {
                if ( ! empty( $tag_name ) ) {
                    $term = get_term_by( 'name', $tag_name, 'cp_recipe_tags' );
                    if ( ! $term ) {
                        $term = wp_insert_term( $tag_name, 'cp_recipe_tags' );
                        if ( ! is_wp_error( $term ) ) {
                            $tag_ids[] = $term['term_id'];
                        }
                    } else {
                        $tag_ids[] = $term->term_id;
                    }
                }
            }
            if ( ! empty( $tag_ids ) ) {
                wp_set_object_terms( $recipe_id, $tag_ids, 'cp_recipe_tags' );
            }
        }

        return [
            'success' => true,
            'recipe_id' => $recipe_id
        ];
    }

    /**
     * Match a measurement string to a measurement key
     * Checks exact key match, variations, singular, and plural forms
     *
     * @param string $measurement_string The measurement string from CSV
     * @param array $measurements Full measurements array
     * @return string|false The measurement key or false if not found
     */
    private static function match_measurement( $measurement_string, $measurements ) {
        $measurement_string = strtolower( trim( $measurement_string ) );
        
        // First, check for exact key match
        if ( isset( $measurements[ $measurement_string ] ) ) {
            return $measurement_string;
        }
        
        // Check variations, singular, and plural for each measurement
        foreach ( $measurements as $key => $measurement_data ) {
            // Check variations
            if ( isset( $measurement_data['variations'] ) && is_array( $measurement_data['variations'] ) ) {
                foreach ( $measurement_data['variations'] as $variation ) {
                    if ( strtolower( $variation ) === $measurement_string ) {
                        return $key;
                    }
                }
            }
            
            // Check singular
            if ( isset( $measurement_data['singular'] ) && strtolower( $measurement_data['singular'] ) === $measurement_string ) {
                return $key;
            }
            
            // Check plural
            if ( isset( $measurement_data['plural'] ) && strtolower( $measurement_data['plural'] ) === $measurement_string ) {
                return $key;
            }
            
            // Check singular abbreviation
            if ( isset( $measurement_data['singular_abbr'] ) && strtolower( $measurement_data['singular_abbr'] ) === $measurement_string ) {
                return $key;
            }
            
            // Check plural abbreviation
            if ( isset( $measurement_data['plural_abbr'] ) && strtolower( $measurement_data['plural_abbr'] ) === $measurement_string ) {
                return $key;
            }
        }
        
        return false;
    }

    /**
     * Parse ingredient parts into ingredient array
     *
     * @param array $parts Array of ingredient parts (amount, measurement, name, etc.)
     * @param array $measurements Full measurements array
     * @return array|false Ingredient array or false on error
     */
    private static function parse_ingredient_parts( $parts, $measurements ) {
        if ( empty( $parts ) ) {
            return false;
        }

        $ingredient = [
            'amount' => '',
            'measurement' => '',
            'name' => '',
            'url' => '',
            'description' => '',
            'sub_amount' => '',
            'sub_measurement' => '',
            'sub_name' => '',
        ];

        if ( count( $parts ) >= 3 ) {
            // Format: amount|measurement|name
            $ingredient['amount'] = sanitize_text_field( $parts[0] );
            $measurement = sanitize_text_field( $parts[1] );
            $matched_measurement = self::match_measurement( $measurement, $measurements );
            if ( $matched_measurement ) {
                $ingredient['measurement'] = $matched_measurement;
            }
            $ingredient['name'] = sanitize_text_field( $parts[2] );
            if ( isset( $parts[3] ) ) {
                $ingredient['description'] = sanitize_text_field( $parts[3] );
            }
        } elseif ( count( $parts ) == 2 ) {
            // Format: amount|name (no measurement)
            $ingredient['amount'] = sanitize_text_field( $parts[0] );
            $ingredient['name'] = sanitize_text_field( $parts[1] );
        } else {
            // Format: name only
            $ingredient['name'] = sanitize_text_field( $parts[0] );
        }

        return $ingredient;
    }
}

