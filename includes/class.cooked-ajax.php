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

        // Get JSON list of Recipe IDs, ready for Migration
        add_action( 'wp_ajax_cooked_get_migrate_ids', [&$this, 'get_migrate_ids'] );

        // Get JSON list of Recipe IDs, ready for Import
        add_action( 'wp_ajax_cooked_get_import_ids', [&$this, 'get_import_ids']);

        // Migrate Recipes
        add_action( 'wp_ajax_cooked_migrate_recipes', [&$this, 'migrate_recipes'] );

        // Import Recipes
        add_action( 'wp_ajax_cooked_import_recipes', [&$this, 'import_recipes']);

        // Related Recipes: get IDs for pre-calculation
        add_action( 'wp_ajax_cooked_get_related_recipes_ids', [ &$this, 'get_related_recipes_ids' ] );
        // Related Recipes: calculate for one recipe per request
        add_action( 'wp_ajax_cooked_calculate_related_recipes', [ &$this, 'calculate_related_recipes' ] );
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

    /**
     * Return all published recipe IDs for the Related Recipes pre-calculation tool.
     * Returns count and first batch to avoid memory issues with large sites.
     */
    public function get_related_recipes_ids() {
        if ( ! current_user_can( 'edit_cooked_recipes' ) ) {
            wp_die();
        }

        // Get total count first (lightweight query)
        $count_args = [
            'post_type'      => 'cp_recipe',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ];
        $count_query = new \WP_Query( $count_args );
        $total = (int) $count_query->found_posts;
        wp_reset_postdata();

        if ( $total === 0 ) {
            wp_send_json( [ 'total' => 0, 'ids' => [] ] );
            return;
        }

        // For very large sites, return first batch and let calculate_related_recipes fetch more server-side
        // This avoids sending 100,000+ IDs in a single AJAX response
        $batch_size = apply_filters( 'cooked_related_recipes_ids_batch_size', 1000 );
        
        $args = [
            'post_type'      => 'cp_recipe',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => min( $batch_size, $total ),
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];
        
        $query = new \WP_Query( $args );
        $ids = ! empty( $query->posts ) ? $query->posts : [];
        wp_reset_postdata();

        wp_send_json( [
            'total' => $total,
            'ids'   => $ids,
            'offset' => count( $ids ),
        ] );
    }

    /**
     * Process one recipe for Related Recipes pre-calculation; returns remaining IDs.
     * Fetches more IDs server-side when running low to avoid sending all IDs at once.
     */
    public function calculate_related_recipes() {
        if ( ! current_user_can( 'edit_cooked_recipes' ) ) {
            wp_die();
        }

        $total_recipes = isset( $_POST['total_recipes'] ) ? (int) $_POST['total_recipes'] : 0;
        $processed_count = isset( $_POST['processed_count'] ) ? (int) $_POST['processed_count'] : 0;

        if ( ! isset( $_POST['recipe_ids'] ) ) {
            // No IDs provided, try to fetch first batch
            $recipe_ids = $this->get_next_batch_of_recipe_ids( 0 );
        } else {
           	$recipe_ids = json_decode( stripslashes( (string) $_POST['recipe_ids'] ), true );
           	if ( ! is_array( $recipe_ids ) ) {
                $recipe_ids = [];
            }
        }

        // If we're running low on IDs and there are more to fetch, get next batch
        if ( count( $recipe_ids ) < 10 && $processed_count < $total_recipes ) {
            $next_batch = $this->get_next_batch_of_recipe_ids( $processed_count );
            $recipe_ids = array_merge( $recipe_ids, $next_batch );
        }

       	if ( empty( $recipe_ids ) ) {
           	$ts = current_time( 'timestamp' );
           	if ( $total_recipes > 0 ) {
           		update_option( 'cooked_related_calculation_last', [
           			'time'  => $ts,
           			'count' => $total_recipes,
           		] );
           	}
           	wp_send_json( [
           		'complete'       => true,
           		'count'          => $total_recipes,
           		'date_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ),
           	] );
           	return;
       	}

       	$sanitized = [];
       	foreach ( $recipe_ids as $id ) {
           	$id = (int) $id;
           	if ( $id > 0 ) {
               	$sanitized[] = $id;
           	}
       	}
       	$recipe_ids = $sanitized;

       	$current = array_shift( $recipe_ids );
       	if ( $current ) {
           	Cooked_Related_Recipes::prime_cache_for_recipe( $current );
           	$processed_count++;
       	}

       	if ( ! empty( $recipe_ids ) ) {
           	echo wp_json_encode( $recipe_ids );
       	} else {
           	// No more IDs in current batch, check if there are more to fetch
           	if ( $processed_count < $total_recipes ) {
               	// Fetch next batch
               	$next_batch = $this->get_next_batch_of_recipe_ids( $processed_count );
               	if ( ! empty( $next_batch ) ) {
                   	echo wp_json_encode( $next_batch );
               	} else {
                   	// Truly done
                   	$ts = current_time( 'timestamp' );
                   	update_option( 'cooked_related_calculation_last', [
                   		'time'  => $ts,
                   		'count' => $total_recipes,
                   	] );
                   	wp_send_json( [
                   		'complete'       => true,
                   		'count'          => $total_recipes,
                   		'date_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ),
                   	] );
               	}
           	} else {
               	// All done
               	$ts = current_time( 'timestamp' );
               	update_option( 'cooked_related_calculation_last', [
               		'time'  => $ts,
               		'count' => $total_recipes,
               	] );
               	wp_send_json( [
               		'complete'       => true,
               		'count'          => $total_recipes,
               		'date_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ),
               	] );
           	}
       	}
       	wp_die();
    }

    /**
     * Get next batch of recipe IDs for processing.
     *
     * @param int $offset Number of recipes already processed.
     * @return array Array of recipe IDs.
     */
    private function get_next_batch_of_recipe_ids( $offset = 0 ) {
        $batch_size = apply_filters( 'cooked_related_recipes_ids_batch_size', 1000 );
        
        $args = [
            'post_type'      => 'cp_recipe',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => $batch_size,
            'offset'         => $offset,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];
        
        $query = new \WP_Query( $args );
        $ids = ! empty( $query->posts ) ? $query->posts : [];
        wp_reset_postdata();
        
        return $ids;
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

    public function save_default_bulk() {
        $bulk_amount = 5;

        if (!wp_verify_nonce($_POST['nonce'], 'cooked_save_default_bulk') || !current_user_can('edit_cooked_default_template')) {
            wp_die();
        }

        if (isset($_POST['recipe_ids'])) {
            $recipe_ids = json_decode($_POST['recipe_ids'], true);
            if (is_array($recipe_ids) && !empty($recipe_ids)) {
                $_recipe_ids = [];
                foreach ($recipe_ids as $_rid) {
                    $safe_id = intval($_rid);
                    if ($safe_id) {
                        $_recipe_ids[] = $_rid;
                    }
                }
                $recipe_ids = $_recipe_ids;
            } else {
                return false;
            }

            $leftover_recipe_ids = array_slice($recipe_ids, $bulk_amount);
            $recipe_ids = array_slice($recipe_ids, 0, $bulk_amount);

            if (empty($recipe_ids)) {
                echo 'false';
                wp_die();
            } else {
                foreach ($recipe_ids as $rid) {
                    $recipe_settings = get_post_meta($rid, '_recipe_settings', true);
                    if (!empty($recipe_settings)) {
                        $recipe_settings['content'] = wp_kses_post($_POST['default_content']);
                        update_post_meta($rid, '_recipe_settings', $recipe_settings);
                    }
                }

                if (!empty($leftover_recipe_ids)) {
                    echo wp_json_encode($leftover_recipe_ids);
                    wp_die();
                } else {
                    echo 'false';
                    wp_die();
                }
            }
        }

        wp_die();
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
            echo 'No default content provided.';
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
}
