<?php
/**
 * Cooked AJAX-Specific Functions
 *
 * @package     Cooked
 * @subpackage  AJAX-Specific Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Ajax Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Ajax {

    function __construct() {
        /**
         * Back-End Ajax
         */

        // Save Default Template
        add_action( 'wp_ajax_cooked_save_default', [&$this, 'save_default'] );

        // Save Default Template in Bulk
        add_action( 'wp_ajax_cooked_save_default_bulk', [&$this, 'save_default_bulk'] );

        // Load Default Template
        add_action( 'wp_ajax_cooked_load_default', [&$this, 'load_default'] );

        // Get JSON list of Recipe IDs
        add_action( 'wp_ajax_cooked_get_recipe_ids', [&$this, 'get_recipe_ids'] );

        // Get Recipe Count
        add_action( 'wp_ajax_cooked_get_recipe_count', [&$this, 'get_recipe_count'] );

        // Get JSON list of Recipe IDs, ready for Migration
        add_action( 'wp_ajax_cooked_get_migrate_ids', [&$this, 'get_migrate_ids'] );

        // Get JSON list of Recipe IDs, ready for Import
        add_action( 'wp_ajax_cooked_get_import_ids', [&$this, 'get_import_ids']);

        // Migrate Recipes
        add_action( 'wp_ajax_cooked_migrate_recipes', [&$this, 'migrate_recipes'] );

        // Import Recipes
        add_action( 'wp_ajax_cooked_import_recipes', [&$this, 'import_recipes']);

        // CSV Import - Upload file
        add_action( 'wp_ajax_cooked_upload_csv', [&$this, 'upload_csv']);

        // CSV Import - Process file
        add_action( 'wp_ajax_cooked_process_csv', [&$this, 'process_csv']);

        // Bulk Add - Parse Ingredients
        add_action( 'wp_ajax_cooked_parse_bulk_ingredients', [&$this, 'parse_bulk_ingredients'] );
        add_action( 'wp_ajax_nopriv_cooked_parse_bulk_ingredients', [&$this, 'parse_bulk_ingredients'] );
    }

    public function get_migrate_ids() {
        if (!current_user_can('edit_cooked_recipes')):
            wp_die();
        endif;

        $old_recipes = get_transient('cooked_classic_recipes');
        if ($old_recipes != 'complete'):
            $total = count($old_recipes);

            if ($total > 0):
                echo wp_json_encode($old_recipes);
            else:
                echo 'false';
            endif;
        else:
            echo 'false';
        endif;

        wp_die();
    }

    public function get_import_ids() {
        if (!current_user_can('edit_cooked_recipes')):
            wp_die();
        endif;

        $import_type = $_POST['import_type'];

        $recipes = [];

        if ($import_type === 'delicious_recipes') {
            $args = [
                'post_type' => 'recipe',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'delicious_recipes_metadata',
                        'compare' => 'EXISTS',
                    ],
                ],
            ];
        } elseif ($import_type === 'wp_recipe_maker') {
            $args = [
                'post_type' => 'wprm_recipe',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'wprm_type',
                        'value' => 'food',
                        'compare' => '=',
                    ],
                ],
            ];
        }

        $_recipes = new WP_Query( $args );

        if (!empty($_recipes->posts)) {
            foreach ($_recipes->posts as $rid) {
                $recipes[] = $rid;
            }
        }

        if (!empty($recipes)) {
            $total = count($recipes);

            if ($total > 0) {
                echo wp_json_encode($recipes);
            } else {
                echo 'false';
            }
        } else {
            echo 'false';
        }

        wp_die();
    }

    public function migrate_recipes() {
        $bulk_amount = 10;

        if (!current_user_can('edit_cooked_recipes')) {
            wp_die();
        }

        if ( isset($_POST['recipe_ids']) ) {
            // Sanitize Recipe IDs
            $recipe_ids = json_decode( $_POST['recipe_ids'], true );

            if ( is_array( $recipe_ids ) && !empty( $recipe_ids ) ) {
                $_recipe_ids = [];
                foreach ( $recipe_ids as $_rid ) {
                    $safe_id = intval( $_rid );
                    if ( $safe_id ) {
                        $_recipe_ids[] = $_rid;
                    }
                }
                $recipe_ids = $_recipe_ids;
            } else {
                return false;
            }

            $leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
            $recipe_ids = array_slice( $recipe_ids, 0, $bulk_amount );

            if ( !empty($recipe_ids) ) {
                foreach( $recipe_ids as $rid ) {

                    $recipe_settings = Cooked_Recipes::get_settings( $rid );

                    if ( !empty( $recipe_settings ) && !isset( $recipe_settings['cooked_version'] ) || !empty( $recipe_settings ) && isset( $recipe_settings['cooked_version'] ) && !$recipe_settings['cooked_version'] ) {

                        $recipe_settings['cooked_version'] = COOKED_VERSION;

                        // Migrate the recipe settings.
                        update_post_meta( $rid, '_recipe_settings', $recipe_settings );
                        $recipe_excerpt = isset($recipe_settings['excerpt']) && $recipe_settings['excerpt'] ? $recipe_settings['excerpt'] : get_the_title( $rid );

                        $seo_content = apply_filters( 'cooked_seo_recipe_content', '[cooked-excerpt]<h2>' . __('Ingredients','cooked') . '</h2>[cooked-ingredients checkboxes=false]<h2>' . __('Directions','cooked') . '</h2>[cooked-directions numbers=false]' );
                        $seo_content = do_shortcode( $seo_content );

                        wp_update_post([
                            'ID' => $rid,
                            'post_excerpt' => $recipe_excerpt,
                            'post_content' => $seo_content
                        ]);

                    }
                }

                if ( !empty( $leftover_recipe_ids ) ) {
                    echo wp_json_encode( $leftover_recipe_ids );
                    wp_die();
                }

            }

            set_transient( 'cooked_classic_recipes', 'complete', 60 * 60 * 24 * 7 );
            echo 'false';
            wp_die();

        }

        wp_die();
    }

    public function import_recipes() {
        if (!current_user_can('edit_cooked_recipes')) {
            wp_die();
        }

        require_once COOKED_DIR . 'includes/class.cooked-delicious-recipes.php';
        require_once COOKED_DIR . 'includes/class.cooked-recipe-maker.php';

        $bulk_amount = 10;

        if ( isset($_POST['recipe_ids']) ) {
            // Sanitize Recipe IDs
            $recipe_ids = json_decode( $_POST['recipe_ids'], true );

            if ( is_array( $recipe_ids ) && !empty( $recipe_ids ) ) {
                $_recipe_ids = [];
                foreach ( $recipe_ids as $_rid ) {
                    $safe_id = intval( $_rid );
                    if ( $safe_id ) {
                        $_recipe_ids[] = $_rid;
                    }
                }
                $recipe_ids = $_recipe_ids;
            } else {
                return false;
            }

            $leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
            $recipe_ids = array_slice( $recipe_ids, 0, $bulk_amount );

            $import_type = $_POST['import_type'];

            if ( !empty($recipe_ids) ) {
                foreach ( $recipe_ids as $rid ) {
                    if ($import_type === 'delicious_recipes') {
                        Cooked_Delicious_Recipes::import_recipe( $rid );
                    } elseif ($import_type === 'wp_recipe_maker') {
                        Cooked_Recipe_Maker_Recipes::import_recipe( $rid );
                    }
                }

                if ( !empty( $leftover_recipe_ids ) ) {
                    echo wp_json_encode( $leftover_recipe_ids );
                    wp_die();
                } else {
                    if ($import_type === 'delicious_recipes') {
                        update_option( 'cooked_delicious_recipes_imported', true );
                    } elseif ($import_type === 'wp_recipe_maker') {
                        update_option( 'cooked_wp_recipe_maker_imported', true );
                    }
                }
            }

            echo 'false';
            wp_die();
        }

        wp_die();
    }

    public function get_recipe_ids() {
        if (!wp_verify_nonce($_POST['nonce'], 'cooked_save_default_bulk') || !current_user_can('edit_cooked_default_template')) {
            wp_die();
        }

        $args = [
            'post_type' => 'cp_recipe',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ];

        $_recipe_ids = Cooked_Recipes::get($args, false, true);
        echo wp_json_encode($_recipe_ids);
        wp_die();
    }

    public function get_recipe_count() {
        if (!wp_verify_nonce($_POST['nonce'], 'cooked_save_default_bulk') || !current_user_can('edit_cooked_default_template')) {
            wp_die();
        }

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ];

        $query = new WP_Query( $args );
        wp_send_json_success( [ 'total' => $query->found_posts ] );
    }

    public function save_default_bulk() {
        $per_page = 20;

        if (!wp_verify_nonce($_POST['nonce'], 'cooked_save_default_bulk') || !current_user_can('edit_cooked_default_template')) {
            wp_die();
        }

        if (!isset($_POST['default_content'])) {
            wp_send_json_error( [ 'message' => __( 'No default content provided.', 'cooked' ) ] );
        }

        $page = isset($_POST['page']) ? absint($_POST['page']) : 0;
        $content = wp_kses_post($_POST['default_content']);

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => $per_page,
            'offset'         => $page * $per_page,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];

        $query = new WP_Query( $args );
        $recipe_ids = $query->posts;
        $updated = 0;

        foreach ($recipe_ids as $rid) {
            $recipe_settings = get_post_meta($rid, '_recipe_settings', true);
            if (!empty($recipe_settings)) {
                $recipe_settings['content'] = $content;
                update_post_meta($rid, '_recipe_settings', $recipe_settings);
                $updated++;
            }
        }

        $processed = ( $page * $per_page ) + count( $recipe_ids );
        $has_more  = $processed < $query->found_posts;

        wp_send_json_success( [
            'updated'  => $updated,
            'has_more' => $has_more,
        ] );
    }

    public function save_default() {
        global $_cooked_settings;

        if (!wp_verify_nonce($_POST['nonce'], 'cooked_save_default') || !current_user_can('edit_cooked_default_template')) {
            wp_die();
        }

        if (isset($_POST['default_content'])) {
            $_cooked_settings['default_content'] = wp_kses_post( $_POST['default_content'] );
            update_option('cooked_settings', $_cooked_settings);
        } else {
            echo __( 'No default content provided.', 'cooked' );
        }

        wp_die();
    }

    public function load_default() {
        global $_cooked_settings;

        if (!current_user_can('edit_cooked_recipes')) {
            wp_die();
        }

        if (isset($_cooked_settings['default_content'])) {
            $default_content = wp_unslash($_cooked_settings['default_content']);
        } else {
            $default_content = Cooked_Recipes::default_content();
        }

        echo wp_kses_post($default_content);

        wp_die();
    }

    /**
     * Handle CSV file upload
     */
    public function upload_csv() {
        if (!current_user_can('edit_cooked_recipes')) {
            wp_send_json_error(['message' => __('You do not have permission to import recipes.', 'cooked')]);
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('File upload failed.', 'cooked')]);
        }

        // Validate file type
        $file_type = wp_check_filetype($_FILES['csv_file']['name']);
        if ($file_type['ext'] !== 'csv') {
            wp_send_json_error(['message' => __('Invalid file type. Please upload a CSV file.', 'cooked')]);
        }

        // Use WordPress upload handler
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($_FILES['csv_file'], ['test_form' => false]);

        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }

        // Store file path in transient for processing
        $transient_key = 'cooked_csv_import_' . get_current_user_id() . '_' . time();
        set_transient($transient_key, $upload['file'], 3600); // 1 hour

        wp_send_json_success([
            'transient_key' => $transient_key,
            'file_path' => $upload['file']
        ]);
    }

    /**
     * Process CSV file and import recipes
     */
    public function process_csv() {
        if (!current_user_can('edit_cooked_recipes')) {
            wp_send_json_error(['message' => __('You do not have permission to import recipes.', 'cooked')]);
        }

        $transient_key = isset($_POST['transient_key']) ? sanitize_text_field($_POST['transient_key']) : '';
        $file_path = get_transient($transient_key);

        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(['message' => __('CSV file not found. Please upload again.', 'cooked')]);
        }

        // Process the CSV file
        require_once COOKED_DIR . 'includes/class.cooked-csv-import.php';
        $results = Cooked_CSV_Import::import_from_file($file_path);

        // Clean up
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        delete_transient($transient_key);

        if ($results['success'] > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully imported %d recipe(s).', 'cooked'),
                    $results['success']
                ),
                'success' => $results['success'],
                'total' => $results['total'],
                'errors' => $results['errors']
            ]);
        } else {
            wp_send_json_error([
                'message' => __('No recipes were imported.', 'cooked'),
                'errors' => $results['errors']
            ]);
        }
    }

    public function parse_bulk_ingredients() {
        if ( ! check_ajax_referer( 'cooked_bulk_add', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'cooked' ) ] );
        }

        $lines = isset( $_POST['lines'] ) ? (array) $_POST['lines'] : [];

        if ( empty( $lines ) ) {
            wp_send_json_error( [ 'message' => __( 'No ingredients provided.', 'cooked' ) ] );
        }

        $measurements = Cooked_Measurements::get();

        $variations_map = [];
        foreach ( $measurements as $key => $m ) {
            if ( ! empty( $m['variations'] ) ) {
                foreach ( $m['variations'] as $variation ) {
                    $variations_map[ $variation ] = $key;
                }
            }
        }

        // Sort variations longest-first to avoid partial matches.
        $variation_strings = array_keys( $variations_map );
        usort( $variation_strings, function( $a, $b ) {
            return strlen( $b ) - strlen( $a );
        });

        $escaped = array_map( function( $v ) {
            return preg_quote( $v, '/' );
        }, $variation_strings );

        $units_pattern = '/^(' . implode( '|', $escaped ) . ')\.?\s+/iu';

        $parsed = [];

        foreach ( $lines as $index => $line ) {
            // Do not use Cooked_Functions::sanitize_text_field() here — it runs htmlentities() and turns
            // Unicode like en dash or ½ into &ndash; / &frac12;, which breaks parsing and leaks into output.
            $line = is_string( $line ) ? $line : '';
            $line = wp_unslash( $line );
            $line = html_entity_decode( $line, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $line = trim( sanitize_text_field( $line ) );

            if ( '' === $line ) {
                $parsed[ $index ] = [ 'amount' => '', 'measurement' => '', 'name' => '' ];
                continue;
            }

            $raw = $line;
            $amount = '';
            $measurement = '';

            $raw = str_replace( "\xE2\x81\x84", '/', $raw );

            $fraction_map = [
                "\xC2\xBC" => '1/4', "\xC2\xBD" => '1/2', "\xC2\xBE" => '3/4',
                "\xE2\x85\x93" => '1/3', "\xE2\x85\x94" => '2/3',
                "\xE2\x85\x95" => '1/5', "\xE2\x85\x96" => '2/5',
                "\xE2\x85\x97" => '3/5', "\xE2\x85\x98" => '4/5',
                "\xE2\x85\x99" => '1/6', "\xE2\x85\x9A" => '5/6',
                "\xE2\x85\x9B" => '1/8', "\xE2\x85\x9C" => '3/8',
                "\xE2\x85\x9D" => '5/8', "\xE2\x85\x9E" => '7/8',
            ];
            // "1½" must become "1 1/2", not "11/2".
            foreach ( $fraction_map as $symbol => $replacement ) {
                $raw = preg_replace( '/(\d)' . preg_quote( $symbol, '/' ) . '/u', '$1 ' . $replacement, $raw );
            }
            foreach ( $fraction_map as $symbol => $replacement ) {
                $raw = str_replace( $symbol, $replacement, $raw );
            }

            // Allow en dash (U+2013) and em dash (U+2014) in amounts like "2–3".
            $amount_regex = '/^\s*([\d][\s\/\-\d.,\x{2013}\x{2014}]*)\s*/u';
            if ( preg_match( $amount_regex, $raw, $match ) ) {
                $amount = trim( $match[1] );
                $raw = trim( substr( $raw, strlen( $match[0] ) ) );
            }

            if ( preg_match( $units_pattern, $raw, $match ) ) {
                $matched_variation = trim( rtrim( $match[1], '.' ) );
                $matched_lower = strtolower( $matched_variation );

                foreach ( $variations_map as $variation => $key ) {
                    if ( strtolower( $variation ) === $matched_lower ) {
                        $measurement = $key;
                        break;
                    }
                }

                $raw = trim( substr( $raw, strlen( $match[0] ) ) );
            }

            $name = trim( $raw );

            if ( ! $name ) {
                $amount = '';
                $measurement = '';
                $name = trim( $line );
            }

            $parsed[ $index ] = [
                'amount'      => $amount,
                'measurement' => $measurement,
                'name'        => $name,
            ];
        }

        wp_send_json_success( [ 'parsed' => $parsed ] );
    }
}
