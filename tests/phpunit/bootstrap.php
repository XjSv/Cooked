<?php
/**
 * PHPUnit Bootstrap for Cooked Plugin
 *
 * Stubs WordPress functions so that plugin classes can be loaded
 * and pure-logic methods can be tested without a WordPress environment.
 *
 * @package Cooked
 * @subpackage Tests
 */

define( 'ABSPATH', __DIR__ . '/../../' );
define( 'COOKED_DIR', __DIR__ . '/../../' );
define( 'COOKED_VERSION', '1.16.0' );
define( 'COOKED_DEV', false );
define( 'COOKED_URL', 'http://example.com/wp-content/plugins/cooked/' );
define( 'OBJECT', 'OBJECT' );

/**
 * Translation stubs
 */
function __( $text, $domain = 'default' ) { return $text; }
function _x( $text, $context, $domain = 'default' ) { return $text; }
function _n( $single, $plural, $number, $domain = 'default' ) { return $number <= 1 ? $single : $plural; }
function _e( $text, $domain = 'default' ) { echo $text; }
function esc_html__( $text, $domain = 'default' ) { return $text; }
function esc_html_x( $text, $context, $domain = 'default' ) { return $text; }

/**
 * Filter and action stubs
 */
$GLOBALS['_cooked_test_filters'] = [];

function apply_filters( $tag, $value, ...$args ) {
    if ( empty( $GLOBALS['_cooked_test_filters'][ $tag ] ) ) {
        return $value;
    }

    ksort( $GLOBALS['_cooked_test_filters'][ $tag ] );

    foreach ( $GLOBALS['_cooked_test_filters'][ $tag ] as $callbacks ) {
        foreach ( $callbacks as $callback ) {
            $value = call_user_func_array( $callback, array_merge( [ $value ], $args ) );
        }
    }

    return $value;
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['_cooked_test_filters'][ $tag ][ $priority ][] = $callback;
    return true;
}

function remove_filter( $tag, $callback, $priority = 10 ) {
    if ( empty( $GLOBALS['_cooked_test_filters'][ $tag ][ $priority ] ) ) {
        return false;
    }

    foreach ( $GLOBALS['_cooked_test_filters'][ $tag ][ $priority ] as $index => $registered_callback ) {
        if ( $registered_callback === $callback ) {
            unset( $GLOBALS['_cooked_test_filters'][ $tag ][ $priority ][ $index ] );
            return true;
        }
    }

    return false;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { return true; }
function do_action( $tag, ...$args ) { return; }
function remove_action( $tag, $callback, $priority = 10 ) { return true; }

/**
 * Option stubs
 */
$GLOBALS['_cooked_test_options'] = [];

function get_option( $option, $default = false ) {
    return isset( $GLOBALS['_cooked_test_options'][ $option ] )
        ? $GLOBALS['_cooked_test_options'][ $option ]
        : $default;
}

function update_option( $option, $value, $autoload = null ) {
    $GLOBALS['_cooked_test_options'][ $option ] = $value;
    return true;
}

function delete_option( $option ) {
    unset( $GLOBALS['_cooked_test_options'][ $option ] );
    return true;
}

/**
 * Formatting and escaping stubs
 */
function number_format_i18n( $number, $decimals = 0 ) { return number_format( $number, $decimals, '.', '' ); }
function esc_html( $text ) { return htmlspecialchars( $text ?? '', ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( $text ?? '', ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
function esc_url_raw( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
function wp_kses_post( $data ) { return $data; }
function sanitize_key( $key ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) ); }
function sanitize_title( $title ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '-', $title ) ); }
function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '-', $title ) ); }
function absint( $maybeint ) { return abs( (int) $maybeint ); }
function sanitize_text_field( $str ) { return strip_tags( stripslashes( $str ) ); }

/**
 * Path and URL stubs
 */
function plugin_dir_path( $file ) { return trailingslashit( dirname( $file ) ); }
function plugin_basename( $file ) { return 'cooked/cooked.php'; }
function admin_url( $path = '' ) { return 'http://example.com/wp-admin/' . ltrim( $path, '/' ); }
function get_home_url() { return 'http://example.com/'; }
function get_template_directory() { return '/fake/theme/dir'; }
function get_stylesheet_directory() { return '/fake/theme/dir'; }
function trailingslashit( $string ) { return rtrim( $string, '/' ) . '/'; }
function untrailingslashit( $string ) { return rtrim( $string, '/' ); }

/**
 * Localization stubs
 */
function get_locale() { return 'en_US'; }
function load_textdomain( $domain, $mofile ) { return true; }
function load_plugin_textdomain( $domain, $deprecated, $plugin_rel_path ) { return true; }

/**
 * Content formatting stubs
 */
function wpautop( $pee, $br = true ) { return $pee; }
function make_clickable( $text ) { return $text; }
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }

/**
 * Shortcode stubs
 */
function get_shortcode_regex( $tagnames = null ) { return '\[(\[?)(cooked-info)\b([^\]]*)\](?:([^\[]+)?(?:\[(\/)\2\])?)(\]?)'; }
function shortcode_parse_atts( $text ) {
    $atts = [];
    $pattern = '/(\w+)\s*=\s*"([^"]*)"|(\w+)\s*=\s*\'([^\']*)\'/';
    if ( preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $m ) {
            $atts[ $m[1] ?: $m[3] ] = $m[2] ?: $m[4];
        }
    }
    return $atts;
}
function do_shortcode( $content ) { return $content; }

/**
 * Post and attachment stubs
 */
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = [] ) { return ''; }
function get_the_title( $post_id = 0 ) { return 'Test Recipe'; }
function get_permalink( $post_id = 0 ) { return 'http://example.com/recipe/'; }
function has_post_thumbnail( $post_id = 0 ) { return false; }
function get_the_post_thumbnail( $post_id = 0, $size = 'post-thumbnail', $attr = [] ) { return ''; }
function get_the_post_thumbnail_url( $post_id = 0, $size = 'post-thumbnail' ) { return ''; }
function get_post( $post_id = null, $output = OBJECT, $filter = 'raw' ) {
    if ( is_object( $post_id ) ) {
        return $post_id;
    }

    return (object) [
        'ID' => $post_id,
        'post_title' => 'Test Recipe',
        'post_excerpt' => '',
        'post_author' => 1,
        'post_status' => 'publish',
        'post_type' => 'cp_recipe',
    ];
}
function get_post_meta( $post_id, $key = '', $single = false ) { return []; }
function wp_update_post( $postarr = [], $wp_error = false, $fire_after_hooks = true ) { return 0; }

/**
 * Query stubs
 */
function get_query_var( $var, $default = '' ) { return $default; }
function add_query_arg( $args, $url = '' ) { return $url; }
function get_pagenum_link( $page ) { return 'http://example.com/page/' . $page; }
function paginate_links( $args = '' ) { return ''; }

/**
 * User stubs
 */
function get_current_user_id() { return 0; }
function is_user_logged_in() { return false; }
function wp_get_current_user() { return (object) [ 'ID' => 0, 'user_login' => '' ]; }
function set_transient( $transient, $value, $expiration = 0 ) { return true; }
function get_transient( $transient ) { return false; }
function delete_transient( $transient ) { return true; }

/**
 * Taxonomy stubs
 */
function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) { return false; }
function get_terms( $args = [], $deprecated = '' ) { return []; }
function get_taxonomy( $taxonomy ) { return (object) [ 'label' => $taxonomy, 'name' => $taxonomy ]; }
function wp_dropdown_categories( $args = '' ) { return ''; }
function is_wp_error( $thing ) { return false; }

/**
 * WP_Error stub class
 */
class WP_Error {
    public $errors = [];
    public $error_data = [];

    public function __construct( $code = '', $message = '', $data = '' ) {
        if ( ! empty( $code ) ) {
            $this->errors[ $code ][] = $message;
            if ( ! empty( $data ) ) {
                $this->error_data[ $code ] = $data;
            }
        }
    }
}

/**
 * WP_Query stub class
 */
class WP_Query {
    public $posts = [];
    public $post_count = 0;
    public $found_posts = 0;
    public $max_num_pages = 0;
    public $current_post = -1;
    public $in_the_loop = false;
    public $post = null;
    public $query_vars = [];

    public function __construct( $query = '' ) {
        $this->query_vars = is_array( $query ) ? $query : [];
        $this->posts = [];
        $this->post_count = 0;
        $this->found_posts = 0;
        $this->max_num_pages = 0;
    }

    public function have_posts() { return false; }
    public function the_post() {}
}

/**
 * Register uninstall hook stub
 */
function register_uninstall_hook( $file, $callback ) {}

/**
 * Flush rewrite rules stub
 */
function flush_rewrite_rules( $hard = true ) {}

/**
 * Additional WP stubs needed by various tests
 */
function wp_rand( $min = 0, $max = 9999999 ) { return 1234567; }
function wp_strip_all_tags( $string, $remove_breaks = false ) { return strip_tags( $string ); }
function wp_specialchars_decode( $string, $quote_style = ENT_QUOTES ) { return htmlspecialchars_decode( $string, $quote_style ); }
function get_site_url() { return 'http://example.com/'; }
function home_url( $path = '' ) { return 'http://example.com/' . ltrim( $path, '/' ); }
function get_user_by( $field, $value ) { return (object) [ 'ID' => 1, 'user_login' => 'admin', 'user_nicename' => 'admin', 'display_name' => 'Admin User', 'user_email' => 'admin@example.com' ]; }
function get_userdata( $user_id ) { return (object) [ 'ID' => $user_id, 'user_login' => 'admin', 'user_nicename' => 'admin', 'display_name' => 'Admin User', 'user_email' => 'admin@example.com', 'roles' => [ 'administrator' ] ]; }
function get_user_meta( $user_id, $key = '', $single = false ) { return $key === 'cooked_user_meta' ? [] : ''; }
function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) { return true; }
function get_posts( $args = [] ) { return []; }
function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) { return true; }
function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) { return true; }
function wp_insert_post( $postarr = [], $wp_error = false, $fire_after_hooks = true ) { return 1; }
function register_setting( $option_group, $option_name, $args = [] ) { return true; }
function wp_create_nonce( $action = -1 ) { return 'test_nonce'; }
function wp_verify_nonce( $nonce, $action = -1 ) { return true; }
function current_user_can( $capability, ...$args ) { return true; }
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) { return '<input type="hidden" name="' . $name . '" value="test_nonce" />'; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function is_admin() { return false; }
function is_page( $page = '' ) { return false; }
function is_singular( $post_types = '' ) { return false; }
function wp_reset_postdata() {}
function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) { return []; }
function wp_kses( $string, $allowed_html, $allowed_protocols = [] ) { return $string; }
function wp_json_encode( $data, $options = 0, $depth = 512 ) { return json_encode( $data, $options, $depth ); }
function get_admin_url() { return 'http://example.com/wp-admin/'; }
function get_the_author_meta( $field = '', $user_id = false ) { return 'Admin User'; }
function get_the_date( $format = 'Y-m-d', $post = null ) { return '2024-01-15'; }
function get_the_terms( $post_id, $taxonomy ) { return [ (object) [ 'term_id' => 1, 'name' => 'Test Category', 'slug' => 'test-category' ] ]; }
function get_avatar( $id_or_email, $size = 96, $default = '', $alt = '' ) { return '<img src="avatar.jpg" />'; }
function get_avatar_url( $id_or_email, $args = [] ) { return 'http://example.com/avatar.jpg'; }
function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) { return false; }
function wp_attachment_is_image( $attachment_id ) { return $attachment_id > 0; }
function taxonomy_exists( $taxonomy ) { return true; }
function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) { return true; }
function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) { return true; }
function wp_register_style( $handle, $src, $deps = [], $ver = false, $media = 'all' ) { return true; }
function wp_register_script( $handle, $src, $deps = [], $ver = false, $in_footer = false ) { return true; }
function wp_localize_script( $handle, $object_name, $l10n ) { return true; }
function wp_add_inline_style( $handle, $data ) { return true; }
function wp_add_inline_script( $handle, $data, $position = 'after' ) { return true; }
function wp_style_is( $handle, $list = 'enqueued' ) { return false; }
function wp_script_is( $handle, $list = 'enqueued' ) { return false; }
function add_rewrite_tag( $tag, $regex, $query = '' ) { return true; }
function add_rewrite_rule( $regex, $query, $after = 'bottom' ) { return true; }

$GLOBALS['wp_roles'] = (object) [
    'roles' => [
        'administrator' => [ 'name' => 'Administrator' ],
        'editor' => [ 'name' => 'Editor' ],
        'subscriber' => [ 'name' => 'Subscriber' ],
    ]
];

$GLOBALS['_cooked_settings'] = [];

/**
 * Load Composer autoloader
 */
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Load plugin class files needed for testing
 */
require_once COOKED_DIR . 'includes/class.cooked-measurements.php';
require_once COOKED_DIR . 'includes/class.cooked-unit-converter.php';
require_once COOKED_DIR . 'includes/class.cooked-functions.php';
require_once COOKED_DIR . 'includes/class.cooked-recipes.php';
require_once COOKED_DIR . 'includes/class.cooked-multilingual.php';
require_once COOKED_DIR . 'includes/class.cooked-settings.php';
require_once COOKED_DIR . 'includes/class.cooked-enqueues.php';
require_once COOKED_DIR . 'includes/class.cooked-users.php';
require_once COOKED_DIR . 'includes/class.cooked-recipe-meta.php';
require_once COOKED_DIR . 'includes/class.cooked-csv-import.php';
require_once COOKED_DIR . 'includes/class.cooked-related-recipes.php';
require_once COOKED_DIR . 'includes/class.cooked-updates.php';
require_once COOKED_DIR . 'includes/class.cooked-seo.php';
