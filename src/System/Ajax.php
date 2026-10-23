<?php
/**
 * Cooked AJAX-Specific Functions
 *
 * @package     Cooked
 * @subpackage  AJAX-Specific Functions
 * @since       1.0.0
 */

namespace Cooked\System;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Ajax {

    /**
     * Registers Cooked's AJAX actions.
     */
    public function __construct() {
        /**
         * Back-End Ajax
         */

        // Save Default Template.
        add_action( 'wp_ajax_cooked_save_default', [ &$this, 'save_default' ] );

        // Save Default Template in Bulk.
        add_action( 'wp_ajax_cooked_save_default_bulk', [ &$this, 'save_default_bulk' ] );

        // Load Default Template.
        add_action( 'wp_ajax_cooked_load_default', [ &$this, 'load_default' ] );

        // Get JSON list of Recipe IDs.
        add_action( 'wp_ajax_cooked_get_recipe_ids', [ &$this, 'get_recipe_ids' ] );

        // Get Recipe Count.
        add_action( 'wp_ajax_cooked_get_recipe_count', [ &$this, 'get_recipe_count' ] );

        // Get JSON list of Recipe IDs, ready for Migration.
        add_action( 'wp_ajax_cooked_get_migrate_ids', [ &$this, 'get_migrate_ids' ] );

        // Get JSON list of Recipe IDs, ready for Import.
        add_action( 'wp_ajax_cooked_get_import_ids', [ &$this, 'get_import_ids' ] );

        // Migrate Recipes.
        add_action( 'wp_ajax_cooked_migrate_recipes', [ &$this, 'migrate_recipes' ] );

        // Import Recipes.
        add_action( 'wp_ajax_cooked_import_recipes', [ &$this, 'import_recipes' ] );

        // CSV Import - Upload file.
        add_action( 'wp_ajax_cooked_upload_csv', [ &$this, 'upload_csv' ] );

        // CSV Import - Process file.
        add_action( 'wp_ajax_cooked_process_csv', [ &$this, 'process_csv' ] );

        // What's New - Full changelog.
        add_action( 'wp_ajax_cooked_full_changelog', [ &$this, 'full_changelog' ] );

        // Bulk Add - Parse Ingredients.
        add_action( 'wp_ajax_cooked_parse_bulk_ingredients', [ &$this, 'parse_bulk_ingredients' ] );

        // Gallery tab - Preview.
        add_action( 'wp_ajax_cooked_gallery_preview', [ &$this, 'gallery_preview' ] );
    }

    /**
     * Gallery tab preview: a small page with the recipe's gallery as the front end shows it,
     * built from the (unsaved) values in the editor, for the preview dialog's iframe.
     *
     * @since 2.0.0
     * @return void
     */
    public function gallery_preview() {
        $nonce = isset( $_GET['nonce'] ) ? wp_unslash( $_GET['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_gallery_preview' ) || ! current_user_can( 'edit_cooked_recipes' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'cooked' ), 403 );
        }

        // Only image attachments, at most 50.
        $items = isset( $_GET['items'] ) ? array_slice( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['items'] ) ) ) ) ), 0, 50 ) : [];
        $items = array_values( array_filter( $items, 'wp_attachment_is_image' ) );

        $gallery = [
            'items'     => $items,
            'video_url' => isset( $_GET['video_url'] ) ? esc_url_raw( wp_unslash( $_GET['video_url'] ) ) : '',
        ];
        // The style values are validated where they are used (Cooked_Shortcodes::gallery_options()).
        foreach ( [ 'style', 'loop', 'autoplay', 'ratio', 'fit' ] as $key ) {
            if ( isset( $_GET[ $key ] ) ) {
                $gallery[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
            }
        }

        $html = \Cooked\Frontend\Shortcodes::gallery_preview_html( $gallery, __( 'Recipe video', 'cooked' ) );
        if ( '' === $html ) {
            $html = '<p class="cooked-gallery-preview-empty">' . esc_html__( 'Add images or a video to see the gallery.', 'cooked' ) . '</p>';
        }

        // The live preview on the Gallery tab: no page padding, the full width of its frame.
        $mini = ! empty( $_GET['mini'] );

        $asset = function ( $path ) {
            return esc_url( COOKED_URL . $path . '?ver=' . COOKED_VERSION );
        };

        header( 'Content-Type: text/html; charset=utf-8' );
        // phpcs:disable WordPress.WP.EnqueuedResources -- A standalone page for an iframe, not a WordPress screen.
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" href="' . $asset( 'assets/css/essentials.min.css' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '<link rel="stylesheet" href="' . $asset( 'assets/css/style.min.css' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '<link rel="stylesheet" href="' . $asset( 'assets/vendor/photoswipe/photoswipe.css' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '<style>body{margin:0;padding:20px;background:#fff;color:#1e1e1e;font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}.cooked-gallery{max-width:720px;margin:0 auto}.cooked-gallery-preview-empty{margin:60px 0;text-align:center;color:#757575}body.is-mini{padding:0;background:transparent}.is-mini .cooked-gallery{max-width:none}.is-mini .cooked-gallery-preview-empty{margin:40px 16px;font-size:13px}</style>';
        echo '</head><body' . ( $mini ? ' class="is-mini"' : '' ) . '>';
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped by Cooked_Shortcodes::cooked_gallery_html().
        echo '<script src="' . $asset( 'assets/vendor/photoswipe/photoswipe.umd.min.js' ) . '"></script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '<script src="' . $asset( 'assets/vendor/photoswipe/photoswipe-lightbox.umd.min.js' ) . '"></script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '<script src="' . $asset( 'assets/js/cooked-gallery.js' ) . '"></script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $asset().
        echo '</body></html>';
        // phpcs:enable WordPress.WP.EnqueuedResources
        exit;
    }

    /**
     * Positive recipe IDs from a JSON array.
     *
     * @param string $json JSON array of IDs.
     * @return int[]
     */
    private static function recipe_ids_from_json( $json ) {
        $decoded = json_decode( $json, true );
        if ( ! is_array( $decoded ) || empty( $decoded ) ) {
            return [];
        }

        $ids = [];
        foreach ( $decoded as $rid ) {
            $safe_id = absint( $rid );
            if ( $safe_id ) {
                $ids[] = $safe_id;
            }
        }

        return $ids;
    }

    /**
     * Accepts only the supported importer names.
     *
     * @param string $import_type Importer name.
     * @return string
     */
    private static function sanitize_import_type( $import_type ) {
        if ( ! in_array( $import_type, [ 'delicious_recipes', 'wp_recipe_maker' ], true ) ) {
            return '';
        }
        return $import_type;
    }

    /**
     * Stops the request unless the nonce is valid and the user can edit Cooked settings.
     *
     * @param string $nonce_action Nonce action.
     * @return void
     */
    private static function require_settings_ajax( $nonce_action ) {
        check_ajax_referer( $nonce_action, 'nonce' );
        if ( ! current_user_can( 'edit_cooked_settings' ) ) {
            wp_die();
        }
    }

    /**
     * Whether the user can edit a post, and it is of the given type.
     *
     * @param int    $post_id   Post ID.
     * @param string $post_type Expected post type.
     * @return bool
     */
    private static function user_can_edit_post_of_type( $post_id, $post_type ) {
        return $post_type && get_post_type( $post_id ) === $post_type && current_user_can( 'edit_post', $post_id );
    }

    /**
     * The whole readme.txt changelog as HTML, for the What's New "View Full Changelog" modal.
     *
     * @since 2.0.0
     * @return void
     */
    public function full_changelog() {
        self::require_settings_ajax( 'cooked_full_changelog' );
        wp_send_json_success( [ 'html' => wp_kses_post( Functions::parse_readme_full_changelog() ) ] );
    }

    /**
     * Sends the IDs of Cooked Classic recipes still to migrate.
     *
     * @return void
     */
    public function get_migrate_ids() {
        self::require_settings_ajax( 'cooked_migrate_recipes' );

        $old_recipes = \Cooked_Migration::pending_classic_recipes();
        echo ! empty( $old_recipes ) ? wp_json_encode( $old_recipes ) : 'false';

        wp_die();
    }

    /**
     * Sends the IDs of recipes to import from another plugin.
     *
     * @return void
     */
    public function get_import_ids() {
        self::require_settings_ajax( 'cooked_import_recipes' );

        $import_type = isset( $_POST['import_type'] ) ? sanitize_key( wp_unslash( $_POST['import_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in require_settings_ajax().
        $import_type = self::sanitize_import_type( $import_type );
        $recipes     = [];

        if ( 'delicious_recipes' === $import_type ) {
            $args = [
                'post_type'      => 'recipe',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin-only import lookup.
                    [
                        'key'     => 'delicious_recipes_metadata',
                        'compare' => 'EXISTS',
                    ],
                ],
            ];
        } elseif ( 'wp_recipe_maker' === $import_type ) {
            $args = [
                'post_type'      => 'wprm_recipe',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin-only import lookup.
                    [
                        'key'     => 'wprm_type',
                        'value'   => 'food',
                        'compare' => '=',
                    ],
                ],
            ];
        } else {
            echo 'false';
            wp_die();
        }

        $_recipes = new \WP_Query( $args );

        if ( ! empty( $_recipes->posts ) ) {
            foreach ( $_recipes->posts as $rid ) {
                $recipes[] = $rid;
            }
        }

        if ( ! empty( $recipes ) ) {
            $total = count( $recipes );

            if ( $total > 0 ) {
                echo wp_json_encode( $recipes );
            } else {
                echo 'false';
            }
        } else {
            echo 'false';
        }

        wp_die();
    }

    /**
     * Migrates a batch of Cooked Classic recipes.
     *
     * @return void
     */
    public function migrate_recipes() {
        $bulk_amount = 10;

        self::require_settings_ajax( 'cooked_migrate_recipes' );

        $recipe_ids = self::recipe_ids_from_json( isset( $_POST['recipe_ids'] ) ? wp_unslash( $_POST['recipe_ids'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- JSON decoded then absint in recipe_ids_from_json; nonce checked in require_settings_ajax().
        if ( empty( $recipe_ids ) ) {
            wp_die();
        }

        $leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
        $recipe_ids          = array_slice( $recipe_ids, 0, $bulk_amount );

        if ( ! empty( $recipe_ids ) ) {
            foreach ( $recipe_ids as $rid ) {
                if ( ! self::user_can_edit_post_of_type( $rid, 'cp_recipe' ) ) {
                    continue;
                }

                $recipe_settings = \Cooked_Recipes::get_settings( $rid );

                if ( ( ! empty( $recipe_settings ) && ! isset( $recipe_settings['cooked_version'] ) ) || ( ! empty( $recipe_settings ) && isset( $recipe_settings['cooked_version'] ) && ! $recipe_settings['cooked_version'] ) ) {
                    $recipe_settings['cooked_version'] = COOKED_VERSION;
                    update_post_meta( $rid, '_recipe_settings', $recipe_settings );
                    $recipe_excerpt = isset( $recipe_settings['excerpt'] ) && $recipe_settings['excerpt'] ? $recipe_settings['excerpt'] : get_the_title( $rid );

                    $seo_content = apply_filters( 'cooked_seo_recipe_content', '[cooked-excerpt]<h2>' . __( 'Ingredients', 'cooked' ) . '</h2>[cooked-ingredients checkboxes=false]<h2>' . __( 'Directions', 'cooked' ) . '</h2>[cooked-directions numbers=false]' );
                    $seo_content = do_shortcode( $seo_content );

                    wp_update_post(
                        [
							'ID'           => $rid,
							'post_excerpt' => $recipe_excerpt,
							'post_content' => $seo_content,
						]
                    );
                }
            }

            if ( ! empty( $leftover_recipe_ids ) ) {
                echo wp_json_encode( $leftover_recipe_ids );
                wp_die();
            }
        }

        set_transient( 'cooked_classic_recipes', 'complete', 60 * 60 * 24 * 7 );
        echo 'false';
        wp_die();
    }

    /**
     * Imports a batch of recipes from another plugin.
     *
     * @return void
     */
    public function import_recipes() {
        self::require_settings_ajax( 'cooked_import_recipes' );

        $bulk_amount = 10;
        $recipe_ids  = self::recipe_ids_from_json( isset( $_POST['recipe_ids'] ) ? wp_unslash( $_POST['recipe_ids'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- JSON decoded then absint in recipe_ids_from_json; nonce checked in require_settings_ajax().
        $import_type = isset( $_POST['import_type'] ) ? sanitize_key( wp_unslash( $_POST['import_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in require_settings_ajax().
        $import_type = self::sanitize_import_type( $import_type );

        if ( empty( $recipe_ids ) || ! $import_type ) {
            wp_die();
        }

        $expected_type       = 'delicious_recipes' === $import_type ? 'recipe' : 'wprm_recipe';
        $leftover_recipe_ids = array_slice( $recipe_ids, $bulk_amount );
        $recipe_ids          = array_slice( $recipe_ids, 0, $bulk_amount );

        if ( ! empty( $recipe_ids ) ) {
            foreach ( $recipe_ids as $rid ) {
                if ( ! self::user_can_edit_post_of_type( $rid, $expected_type ) ) {
                    continue;
                }

                if ( 'delicious_recipes' === $import_type ) {
                    \Cooked_Delicious_Recipes::import_recipe( $rid );
                } elseif ( 'wp_recipe_maker' === $import_type ) {
                    \Cooked_Recipe_Maker_Recipes::import_recipe( $rid );
                }
            }

            if ( ! empty( $leftover_recipe_ids ) ) {
                echo wp_json_encode( $leftover_recipe_ids );
                wp_die();
            }

            if ( 'delicious_recipes' === $import_type ) {
                update_option( 'cooked_delicious_recipes_imported', true );
            } elseif ( 'wp_recipe_maker' === $import_type ) {
                update_option( 'cooked_wp_recipe_maker_imported', true );
            }
        }

        echo 'false';
        wp_die();
    }

    /**
     * Sends a page of recipe IDs for "Apply to All".
     *
     * @return void
     */
    public function get_recipe_ids() {
        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_save_default_bulk' ) || ! current_user_can( 'edit_cooked_default_template' ) ) {
            wp_die();
        }

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ];

        $_recipe_ids = \Cooked_Recipes::get( $args, false, true );
        echo wp_json_encode( $_recipe_ids );
        wp_die();
    }

    /**
     * Sends the number of recipes for "Apply to All".
     *
     * @return void
     */
    public function get_recipe_count() {
        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_save_default_bulk' ) || ! current_user_can( 'edit_cooked_default_template' ) ) {
            wp_die();
        }

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ];

        $query = new \WP_Query( $args );
        wp_send_json_success( [ 'total' => $query->found_posts ] );
    }

    /**
     * Applies the default template to a batch of recipes.
     *
     * @return void
     */
    public function save_default_bulk() {
        $per_page = 20;

        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_save_default_bulk' ) || ! current_user_can( 'edit_cooked_default_template' ) ) {
            wp_die();
        }

        if ( ! isset( $_POST['default_content'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No default content provided.', 'cooked' ) ] );
        }

        $page    = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 0;
        $content = wp_kses_post( wp_unslash( $_POST['default_content'] ) );

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => $per_page,
            'offset'         => $page * $per_page,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ];

        $query      = new \WP_Query( $args );
        $recipe_ids = $query->posts;
        $updated    = 0;

        foreach ( $recipe_ids as $rid ) {
            $recipe_settings = get_post_meta( $rid, '_recipe_settings', true );
            if ( ! empty( $recipe_settings ) ) {
                $recipe_settings['content'] = $content;
                update_post_meta( $rid, '_recipe_settings', $recipe_settings );
                ++$updated;
            }
        }

        $processed = ( $page * $per_page ) + count( $recipe_ids );
        $has_more  = $processed < $query->found_posts;

        wp_send_json_success(
            [
				'updated'  => $updated,
				'has_more' => $has_more,
			]
        );
    }

    /**
     * Saves the default recipe template.
     *
     * @return void
     */
    public function save_default() {
        global $_cooked_settings;

        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_save_default' ) || ! current_user_can( 'edit_cooked_default_template' ) ) {
            wp_die();
        }

        if ( isset( $_POST['default_content'] ) ) {
            $_cooked_settings['default_content'] = wp_kses_post( wp_unslash( $_POST['default_content'] ) );
            update_option( 'cooked_settings', $_cooked_settings );
        } else {
            echo esc_html__( 'No default content provided.', 'cooked' );
        }

        wp_die();
    }

    /**
     * Sends the default recipe template.
     *
     * @return void
     */
    public function load_default() {
        global $_cooked_settings;

        check_ajax_referer( 'cooked_load_default', 'nonce' );
        if ( ! current_user_can( 'edit_cooked_recipes' ) ) {
            wp_die();
        }

        if ( isset( $_cooked_settings['default_content'] ) ) {
            $default_content = wp_unslash( $_cooked_settings['default_content'] );
        } else {
            $default_content = \Cooked_Recipes::default_content();
        }

        echo wp_kses_post( $default_content );

        wp_die();
    }

    /**
     * Handle CSV file upload
     */
    public function upload_csv() {
        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_admin_import' ) || ! current_user_can( 'edit_cooked_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to import recipes.', 'cooked' ) ] );
        }

        if ( ! isset( $_FILES['csv_file'] ) || ! isset( $_FILES['csv_file']['error'] ) || UPLOAD_ERR_OK !== (int) $_FILES['csv_file']['error'] ) {
            wp_send_json_error( [ 'message' => __( 'File upload failed.', 'cooked' ) ] );
        }

        $csv_name = isset( $_FILES['csv_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv_file']['name'] ) ) : '';
        if ( ! $csv_name ) {
            wp_send_json_error( [ 'message' => __( 'File upload failed.', 'cooked' ) ] );
        }
        $_FILES['csv_file']['name'] = $csv_name;

        // Validate file type.
        $file_type = wp_check_filetype( $csv_name );
        if ( 'csv' !== $file_type['ext'] ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file type. Please upload a CSV file.', 'cooked' ) ] );
        }

        self::delete_stale_csv_imports();

        // Use WordPress upload handler, saving into the private import folder under a random
        // name rather than the public, date-based uploads folder.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        add_filter( 'upload_dir', [ __CLASS__, 'csv_import_upload_dir' ] );
        $upload = wp_handle_upload(
            $_FILES['csv_file'],
            [
				'test_form'                => false,
				'unique_filename_callback' => function () {
					return 'cooked-import-' . wp_generate_password( 20, false ) . '.csv';
				},
			]
        );
        remove_filter( 'upload_dir', [ __CLASS__, 'csv_import_upload_dir' ] );

        if ( isset( $upload['error'] ) ) {
            wp_send_json_error( [ 'message' => $upload['error'] ] );
        }
        self::protect_csv_import_dir();

        // Store file path in transient for processing.
        $transient_key = 'cooked_csv_import_' . get_current_user_id() . '_' . time();
        set_transient( $transient_key, $upload['file'], HOUR_IN_SECONDS );

        wp_send_json_success( [ 'transient_key' => $transient_key ] );
    }

    /**
     * The private folder that uploaded CSV imports wait in until they are processed.
     *
     * @since 2.0.0
     * @return string Absolute path, without a trailing slash.
     */
    private static function csv_import_dir() {
        $uploads = wp_upload_dir( null, false );
        return trailingslashit( $uploads['basedir'] ) . 'cooked-imports';
    }

    /**
     * Points the CSV upload at the private import folder (an upload_dir filter).
     *
     * @since 2.0.0
     * @param array<string, mixed> $dirs Upload directory data.
     * @return array<string, mixed>
     */
    public static function csv_import_upload_dir( $dirs ) {
        $dirs['subdir'] = '/cooked-imports';
        $dirs['path']   = $dirs['basedir'] . '/cooked-imports';
        $dirs['url']    = $dirs['baseurl'] . '/cooked-imports';
        return $dirs;
    }

    /**
     * Denies web access to the import folder (Apache) and hides its listing; the random
     * file names cover servers that ignore .htaccess.
     *
     * @since 2.0.0
     * @return void
     */
    private static function protect_csv_import_dir() {
        $dir = self::csv_import_dir();
        if ( ! file_exists( $dir . '/.htaccess' ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Small static file in our own uploads folder.
            file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
        }
        if ( ! file_exists( $dir . '/index.php' ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Small static file in our own uploads folder.
            file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
        }
    }

    /**
     * Deletes CSV uploads that were never processed (their transient lasts an hour).
     *
     * @since 2.0.0
     * @return void
     */
    private static function delete_stale_csv_imports() {
        foreach ( (array) glob( self::csv_import_dir() . '/*.csv' ) as $file ) {
            if ( $file && filemtime( $file ) < time() - HOUR_IN_SECONDS ) {
                wp_delete_file( $file );
            }
        }
    }

    /**
     * Process CSV file and import recipes
     */
    public function process_csv() {
        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_admin_import' ) || ! current_user_can( 'edit_cooked_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to import recipes.', 'cooked' ) ] );
        }

        $transient_key = isset( $_POST['transient_key'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_key'] ) ) : '';

        // Only this user's own upload, and only a .csv inside the private import folder: the
        // file is parsed and then deleted.
        $file_path  = preg_match( '/^cooked_csv_import_' . get_current_user_id() . '_\d+$/', $transient_key ) ? get_transient( $transient_key ) : false;
        $real_path  = $file_path ? realpath( $file_path ) : false;
        $import_dir = realpath( self::csv_import_dir() );

        if ( ! $real_path || ! $import_dir || 0 !== strpos( $real_path, $import_dir . DIRECTORY_SEPARATOR ) || 'csv' !== strtolower( pathinfo( $real_path, PATHINFO_EXTENSION ) ) ) {
            wp_send_json_error( [ 'message' => __( 'CSV file not found. Please upload again.', 'cooked' ) ] );
        }
        $file_path = $real_path;

        // Process the CSV file.
        $results = \Cooked_CSV_Import::import_from_file( $file_path );

        // Clean up.
        if ( file_exists( $file_path ) ) {
            wp_delete_file( $file_path );
        }
        delete_transient( $transient_key );

        if ( $results['success'] > 0 ) {
            wp_send_json_success(
                [
					'message' => sprintf(
						/* translators: %d: number of recipes imported */
						__( 'Successfully imported %d recipe(s).', 'cooked' ),
						$results['success']
					),
					'success' => $results['success'],
					'total'   => $results['total'],
					'errors'  => $results['errors'],
				]
            );
        } else {
            wp_send_json_error(
                [
					'message' => __( 'No recipes were imported.', 'cooked' ),
					'errors'  => $results['errors'],
				]
            );
        }
    }

    /**
     * Parses Bulk Add lines into ingredients (amount, unit, name).
     *
     * @return void
     */
    public function parse_bulk_ingredients() {
        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_bulk_add' ) || ! current_user_can( 'edit_cooked_recipes' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'cooked' ) ] );
        }

        $lines = isset( $_POST['lines'] ) ? wp_unslash( (array) $_POST['lines'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each line is sanitize_text_field'd below.
        // Bound the regex work: far more than any recipe needs.
        $lines = array_map(
            function ( $line ) {
                return is_string( $line ) ? mb_substr( $line, 0, 500 ) : '';
            },
            array_slice( $lines, 0, 500 )
        );

        if ( empty( $lines ) ) {
            wp_send_json_error( [ 'message' => __( 'No ingredients provided.', 'cooked' ) ] );
        }

        $measurements = \Cooked_Measurements::get();

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
        usort(
            $variation_strings,
            function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
        );

        $escaped = array_map(
            function ( $v ) {
                return preg_quote( $v, '/' );
            },
            $variation_strings
        );

        $units_pattern = '/^(' . implode( '|', $escaped ) . ')\.?\s+/iu';

        $parsed = [];

        foreach ( $lines as $index => $line ) {
            // Do not use Cooked_Functions::sanitize_text_field() here — it runs htmlentities() and turns
            // Unicode like en dash or ½ into &ndash; / &frac12;, which breaks parsing and leaks into output.
            $line = is_string( $line ) ? $line : '';
            $line = html_entity_decode( $line, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $line = trim( sanitize_text_field( $line ) );

            if ( '' === $line ) {
                $parsed[ $index ] = [
					'amount'      => '',
					'measurement' => '',
					'name'        => '',
				];
                continue;
            }

            $raw         = $line;
            $amount      = '';
            $measurement = '';

            $raw = str_replace( "\xE2\x81\x84", '/', $raw );

            $fraction_map = [
                "\xC2\xBC"     => '1/4',
				"\xC2\xBD"     => '1/2',
				"\xC2\xBE"     => '3/4',
                "\xE2\x85\x93" => '1/3',
				"\xE2\x85\x94" => '2/3',
                "\xE2\x85\x95" => '1/5',
				"\xE2\x85\x96" => '2/5',
                "\xE2\x85\x97" => '3/5',
				"\xE2\x85\x98" => '4/5',
                "\xE2\x85\x99" => '1/6',
				"\xE2\x85\x9A" => '5/6',
                "\xE2\x85\x9B" => '1/8',
				"\xE2\x85\x9C" => '3/8',
                "\xE2\x85\x9D" => '5/8',
				"\xE2\x85\x9E" => '7/8',
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
                $raw    = trim( substr( $raw, strlen( $match[0] ) ) );
            }

            if ( preg_match( $units_pattern, $raw, $match ) ) {
                $matched_variation = trim( rtrim( $match[1], '.' ) );
                $matched_lower     = strtolower( $matched_variation );

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
                $amount      = '';
                $measurement = '';
                $name        = trim( $line );
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
