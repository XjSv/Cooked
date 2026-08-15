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
define( 'COOKED_FOLDER', 'cooked' );
define( 'COOKED_PLUGIN_FILE', COOKED_DIR . 'cooked.php' );
define( 'OBJECT', 'OBJECT' );

/**
 * Translation stubs
 */
function __( $text, $domain = 'default' ) { return $text; }
function _x( $text, $context, $domain = 'default' ) { return $text; }
function _n( $single, $plural, $number, $domain = 'default' ) { return $number <= 1 ? $single : $plural; }
function _e( $text, $domain = 'default' ) { echo $text; }
function esc_html__( $text, $domain = 'default' ) { return $text; }
function esc_html_e( $text, $domain = 'default' ) { echo $text; }
function esc_html_x( $text, $context, $domain = 'default' ) { return $text; }
function esc_attr__( $text, $domain = 'default' ) { return $text; }
function esc_attr_e( $text, $domain = 'default' ) { echo $text; }

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

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['_cooked_test_actions'][ $tag ][ $priority ][] = $callback;
    return true;
}
function do_action( $tag, ...$args ) {
    if ( empty( $GLOBALS['_cooked_test_actions'][ $tag ] ) ) {
        return;
    }
    ksort( $GLOBALS['_cooked_test_actions'][ $tag ] );
    foreach ( $GLOBALS['_cooked_test_actions'][ $tag ] as $callbacks ) {
        foreach ( $callbacks as $callback ) {
            call_user_func_array( $callback, $args );
        }
    }
}
function remove_action( $tag, $callback, $priority = 10 ) {
    if ( empty( $GLOBALS['_cooked_test_actions'][ $tag ][ $priority ] ) ) {
        return true;
    }
    foreach ( $GLOBALS['_cooked_test_actions'][ $tag ][ $priority ] as $index => $registered_callback ) {
        if ( $registered_callback === $callback ) {
            unset( $GLOBALS['_cooked_test_actions'][ $tag ][ $priority ][ $index ] );
        }
    }
    return true;
}

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
function esc_textarea( $text ) { return htmlspecialchars( $text ?? '', ENT_QUOTES, 'UTF-8' ); }
function wp_editor( $content, $editor_id, $settings = [] ) {
    echo '<textarea id="' . esc_attr( $editor_id ) . '">' . $content . '</textarea>';
}
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
function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
    $atts = (array) $atts;
    $out  = [];
    foreach ( $pairs as $name => $default ) {
        $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
    }
    return $out;
}
function strip_shortcodes( $content ) {
    return preg_replace( '/\[[^\]]+\]/', '', $content );
}
function shortcode_unautop( $content ) {
    return $content;
}
function shortcode_exists( $tag ) {
    return false;
}
function add_shortcode( $tag, $callback ) {
    return true;
}

/**
 * Post and attachment stubs
 */
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = [] ) {
    $GLOBALS['_cooked_test_attachment_image_calls'][] = [
        'attachment_id' => $attachment_id,
        'size'          => $size,
        'icon'          => $icon,
        'attr'          => $attr,
    ];
    if ( isset( $GLOBALS['_cooked_test_attachment_image_html'] ) ) {
        return $GLOBALS['_cooked_test_attachment_image_html'];
    }
    return $attachment_id ? '<img src="http://example.com/image-' . (int) $attachment_id . '.jpg" class="size-' . $size . '" />' : '';
}
function get_the_title( $post_id = 0 ) {
    if ( is_object( $post_id ) && isset( $post_id->post_title ) ) {
        return $post_id->post_title;
    }
    if ( ! $post_id && isset( $GLOBALS['post'] ) ) {
        $post_id = $GLOBALS['post'];
        if ( is_object( $post_id ) && isset( $post_id->post_title ) ) {
            return $post_id->post_title;
        }
        $post_id = is_object( $post_id ) ? $post_id->ID : $post_id;
    }
    if ( isset( $GLOBALS['_cooked_test_titles'][ $post_id ] ) ) {
        return $GLOBALS['_cooked_test_titles'][ $post_id ];
    }
    return 'Test Recipe';
}
function get_permalink( $post_id = 0 ) {
    $id = is_object( $post_id ) ? $post_id->ID : $post_id;
    return 'http://example.com/recipe/' . (int) $id . '/';
}
function has_post_thumbnail( $post_id = 0 ) { return false; }
function get_the_post_thumbnail( $post_id = 0, $size = 'post-thumbnail', $attr = [] ) { return ''; }
function get_the_post_thumbnail_url( $post_id = 0, $size = 'post-thumbnail' ) { return ''; }
function get_post( $post_id = null, $output = OBJECT, $filter = 'raw' ) {
    if ( is_object( $post_id ) ) {
        return $post_id;
    }
    if ( isset( $GLOBALS['_cooked_test_posts'][ $post_id ] ) ) {
        return $GLOBALS['_cooked_test_posts'][ $post_id ];
    }

    return (object) [
        'ID' => $post_id,
        'post_title' => 'Test Recipe',
        'post_excerpt' => '',
        'post_author' => 1,
        'post_status' => 'publish',
        'post_type' => 'cp_recipe',
        'post_name' => 'test-recipe',
        'post_content' => '',
    ];
}
function get_post_meta( $post_id, $key = '', $single = false ) {
    if ( $key !== '' && isset( $GLOBALS['_cooked_test_post_meta'][ $post_id ][ $key ] ) ) {
        $value = $GLOBALS['_cooked_test_post_meta'][ $post_id ][ $key ];
        return $single ? $value : (array) $value;
    }
    if ( $key === '' && isset( $GLOBALS['_cooked_test_post_meta'][ $post_id ] ) ) {
        return $GLOBALS['_cooked_test_post_meta'][ $post_id ];
    }
    return [];
}
function wp_update_post( $postarr = [], $wp_error = false, $fire_after_hooks = true ) {
    $GLOBALS['_cooked_test_updated_posts'][] = $postarr;
    return isset( $postarr['ID'] ) ? $postarr['ID'] : 0;
}

/**
 * Query stubs
 */
function get_query_var( $var, $default = '' ) {
    return isset( $GLOBALS['_cooked_test_query_vars'][ $var ] ) ? $GLOBALS['_cooked_test_query_vars'][ $var ] : $default;
}
function add_query_arg( $args, $url = '' ) {
    if ( is_array( $args ) ) {
        $query = http_build_query( $args );
        return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $query;
    }
    return $url;
}
function get_pagenum_link( $page ) { return 'http://example.com/page/' . $page; }
function paginate_links( $args = '' ) {
    $GLOBALS['_cooked_test_paginate_args'] = $args;
    return '<div class="page-numbers">1 2 3</div>';
}

/**
 * User stubs
 */
function set_transient( $transient, $value, $expiration = 0 ) { return true; }
function get_transient( $transient ) { return false; }
function delete_transient( $transient ) { return true; }

/**
 * Taxonomy stubs
 */
function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
    foreach ( isset( $GLOBALS['_cooked_test_terms'] ) ? $GLOBALS['_cooked_test_terms'] : [] as $term ) {
        if ( $taxonomy && $term->taxonomy !== $taxonomy ) {
            continue;
        }
        if ( ( $field === 'id' || $field === 'term_id' ) && (int) $term->term_id === (int) $value ) {
            return $term;
        }
        if ( $field === 'slug' && $term->slug === $value ) {
            return $term;
        }
        if ( $field === 'name' && $term->name === $value ) {
            return $term;
        }
    }
    return false;
}
function get_term( $term, $taxonomy = '' ) {
    if ( is_object( $term ) ) {
        return $term;
    }
    return get_term_by( 'id', $term, $taxonomy );
}
function get_term_link( $term, $taxonomy = '' ) {
    $term_obj = is_object( $term ) ? $term : get_term( $term, $taxonomy );
    $slug = $term_obj && isset( $term_obj->slug ) ? $term_obj->slug : $term;
    return 'http://example.com/term/' . $slug . '/';
}
function get_terms( $args = [], $deprecated = '' ) {
    $taxonomy = is_array( $args ) && isset( $args['taxonomy'] ) ? $args['taxonomy'] : $deprecated;
    $terms = [];
    foreach ( isset( $GLOBALS['_cooked_test_terms'] ) ? $GLOBALS['_cooked_test_terms'] : [] as $term ) {
        if ( ! $taxonomy || $term->taxonomy === $taxonomy ) {
            $terms[] = $term;
        }
    }
    return $terms;
}
function get_taxonomy( $taxonomy ) { return (object) [ 'label' => $taxonomy, 'name' => $taxonomy ]; }
function wp_dropdown_categories( $args = '' ) { return ''; }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }

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
    public $query = [];

    public function __construct( $query = '' ) {
        $this->query_vars = is_array( $query ) ? $query : [];
        $this->query = $this->query_vars;
        $GLOBALS['_cooked_test_last_query'] = $this->query_vars;

        $posts = isset( $GLOBALS['_cooked_test_query_posts'] ) ? $GLOBALS['_cooked_test_query_posts'] : [];

        if ( ! empty( $this->query_vars['post__in'] ) && is_array( $this->query_vars['post__in'] ) ) {
            $want     = array_map( 'intval', $this->query_vars['post__in'] );
            $filtered = [];
            foreach ( $posts as $post ) {
                $id = is_object( $post ) ? (int) $post->ID : (int) $post;
                if ( in_array( $id, $want, true ) ) {
                    $filtered[] = $post;
                }
            }
            if ( empty( $filtered ) ) {
                foreach ( $want as $id ) {
                    if ( isset( $GLOBALS['_cooked_test_posts'][ $id ] ) ) {
                        $filtered[] = $GLOBALS['_cooked_test_posts'][ $id ];
                    }
                }
            }
            $posts = $filtered;
        }

        if ( ! empty( $this->query_vars['post__not_in'] ) && is_array( $this->query_vars['post__not_in'] ) ) {
            $not   = array_map( 'intval', $this->query_vars['post__not_in'] );
            $posts = array_values(
                array_filter(
                    $posts,
                    function ( $post ) use ( $not ) {
                        $id = is_object( $post ) ? (int) $post->ID : (int) $post;
                        return ! in_array( $id, $not, true );
                    }
                )
            );
        }

        if ( isset( $this->query_vars['fields'] ) && $this->query_vars['fields'] === 'ids' ) {
            $posts = array_map(
                function ( $post ) {
                    return is_object( $post ) ? (int) $post->ID : (int) $post;
                },
                $posts
            );
        }
        $this->posts = $posts;
        $this->post_count = count( $posts );
        $this->found_posts = count( $posts );
        $this->max_num_pages = isset( $GLOBALS['_cooked_test_max_num_pages'] )
            ? (int) $GLOBALS['_cooked_test_max_num_pages']
            : ( $this->post_count ? 1 : 0 );
        $this->current_post = -1;
        $this->post = $this->post_count ? $this->posts[0] : null;
    }

    public function get( $var, $default = '' ) {
        return isset( $this->query_vars[ $var ] ) ? $this->query_vars[ $var ] : $default;
    }

    public function set( $var, $value ) {
        $this->query_vars[ $var ] = $value;
    }

    public function have_posts() {
        return ( $this->current_post + 1 ) < $this->post_count;
    }

    public function the_post() {
        $this->current_post++;
        $this->in_the_loop = true;
        $this->post = $this->posts[ $this->current_post ];
        $GLOBALS['post'] = is_object( $this->post ) ? $this->post : (object) [ 'ID' => $this->post ];
    }
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
function get_posts( $args = [] ) { return isset( $GLOBALS['_cooked_test_get_posts'] ) ? $GLOBALS['_cooked_test_get_posts'] : []; }
function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
    $GLOBALS['_cooked_test_post_meta'][ $post_id ][ $meta_key ] = $meta_value;
    return true;
}
function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
    $GLOBALS['_cooked_test_post_meta'][ $post_id ][ $meta_key ] = $meta_value;
    return true;
}
function wp_insert_post( $postarr = [], $wp_error = false, $fire_after_hooks = true ) {
    $GLOBALS['_cooked_test_inserted_posts'][] = $postarr;
    return 1;
}
function register_setting( $option_group, $option_name, $args = [] ) { return true; }
function wp_create_nonce( $action = -1 ) { return 'test_nonce'; }
function wp_verify_nonce( $nonce, $action = -1 ) { return true; }
function current_user_can( $capability, ...$args ) { return true; }
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) { return '<input type="hidden" name="' . $name . '" value="test_nonce" />'; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function is_admin() { return ! empty( $GLOBALS['_cooked_test_is_admin'] ); }
function is_page( $page = '' ) { return ! empty( $GLOBALS['_cooked_test_is_page'] ); }
function is_singular( $post_types = '' ) { return ! empty( $GLOBALS['_cooked_test_is_singular'] ); }
function is_feed() { return ! empty( $GLOBALS['_cooked_test_is_feed'] ); }
function is_main_query() { return ! isset( $GLOBALS['_cooked_test_is_main_query'] ) || $GLOBALS['_cooked_test_is_main_query']; }
function post_password_required( $post = null ) { return false; }
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
function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) {
    if ( empty( $attachment_id ) ) {
        return false;
    }
    return [ 'http://example.com/img-' . (int) $attachment_id . '.jpg', 900, 900, false ];
}
function wp_attachment_is_image( $attachment_id ) { return $attachment_id > 0; }
function taxonomy_exists( $taxonomy ) { return true; }
function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) { return true; }
function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) { return true; }
function wp_register_style( $handle, $src, $deps = [], $ver = false, $media = 'all' ) { return true; }
function wp_register_script( $handle, $src, $deps = [], $ver = false, $in_footer = false ) { return true; }
function wp_localize_script( $handle, $object_name, $l10n ) {
    $GLOBALS['_cooked_test_localized'][ $handle ][ $object_name ] = $l10n;
    return true;
}
function wp_add_inline_style( $handle, $data ) { return true; }
function wp_add_inline_script( $handle, $data, $position = 'after' ) {
    $GLOBALS['_cooked_test_inline_scripts'][] = [
        'handle'   => $handle,
        'data'     => $data,
        'position' => $position,
    ];
    return true;
}
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
$GLOBALS['_cooked_test_filters'] = [];
$GLOBALS['_cooked_test_actions'] = [];
$GLOBALS['_cooked_test_options'] = [];
$GLOBALS['_cooked_test_post_meta'] = [];
$GLOBALS['_cooked_test_posts'] = [];
$GLOBALS['_cooked_test_titles'] = [];
$GLOBALS['_cooked_test_query_vars'] = [];
$GLOBALS['_cooked_test_query_posts'] = [];
$GLOBALS['_cooked_test_terms'] = [];
$GLOBALS['_cooked_test_updated_posts'] = [];
$GLOBALS['_cooked_test_inserted_posts'] = [];
$GLOBALS['_cooked_test_attachment_image_calls'] = [];
$GLOBALS['_cooked_test_localized'] = [];
$GLOBALS['_cooked_test_inline_scripts'] = [];
$GLOBALS['_cooked_test_registered_post_types'] = [];
$GLOBALS['_cooked_test_registered_widgets'] = [];
$GLOBALS['_cooked_test_registered_roles'] = [];
$GLOBALS['_cooked_test_meta_boxes'] = [];
$GLOBALS['_cooked_test_paginate_args'] = null;
$GLOBALS['_cooked_test_last_query'] = [];

class Cooked_Test_wpdb {
    public $posts = 'wp_posts';
    public $prefix = 'wp_';

    public function prepare( $query, ...$args ) {
        foreach ( $args as $arg ) {
            $query = preg_replace( '/%s/', "'" . $arg . "'", $query, 1 );
        }
        return $query;
    }

    public function esc_like( $text ) {
        return addcslashes( $text, '_%\\' );
    }
}
$GLOBALS['wpdb'] = new Cooked_Test_wpdb();

class WP_Widget {
    public $id_base;
    public $name;
    public $widget_options;

    public function __construct( $id_base, $name, $widget_options = [] ) {
        $this->id_base = $id_base;
        $this->name = $name;
        $this->widget_options = $widget_options;
    }

    public function get_field_id( $field ) {
        return $this->id_base . '-' . $field;
    }

    public function get_field_name( $field ) {
        return $this->id_base . '[' . $field . ']';
    }
}

class WP_Post {
    public $ID;
    public $post_title;
    public $post_type = 'cp_recipe';
    public $post_status = 'publish';
    public $post_author = 1;
}

function load_template( $template, $load_once = true, $args = [] ) {
    if ( is_array( $args ) ) {
        extract( $args, EXTR_SKIP );
    }
    if ( $load_once ) {
        include_once $template;
    } else {
        include $template;
    }
}

function locate_template( $template_names, $load = false, $require_once = true, $args = [] ) {
    return '';
}

function wp_suspend_cache_addition( $suspend = true ) {
    return true;
}

function wp_reset_query() {}

function tag_escape( $tag ) {
    return preg_replace( '/[^a-zA-Z0-9_:]/', '', $tag );
}

function selected( $selected, $current = true, $echo = true ) {
    $result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
    if ( $echo ) {
        echo $result;
    }
    return $result;
}

function checked( $checked, $current = true, $echo = true ) {
    $result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
    if ( $echo ) {
        echo $result;
    }
    return $result;
}

function get_the_ID() {
    return isset( $GLOBALS['post']->ID ) ? $GLOBALS['post']->ID : 0;
}

function get_post_type( $post = null ) {
    if ( is_object( $post ) && isset( $post->post_type ) ) {
        return $post->post_type;
    }
    $p = get_post( $post );
    return $p ? $p->post_type : false;
}

function get_post_type_object( $post_type ) {
    return (object) [
        'name'   => $post_type,
        'labels' => (object) [ 'not_found' => 'No recipes found.' ],
    ];
}

function get_post_status( $post = null ) {
    $p = get_post( $post );
    return $p ? $p->post_status : false;
}

function wp_get_object_terms( $object_id, $taxonomy, $args = [] ) {
    $key = $object_id . ':' . $taxonomy;
    if ( isset( $GLOBALS['_cooked_test_object_terms'][ $key ] ) ) {
        return $GLOBALS['_cooked_test_object_terms'][ $key ];
    }
    return [];
}

function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
    if ( empty( $attachment_id ) ) {
        return false;
    }
    return 'http://example.com/wp-content/uploads/image-' . (int) $attachment_id . '.jpg';
}

function wp_get_attachment_url( $attachment_id ) {
    return $attachment_id ? 'http://example.com/wp-content/uploads/file-' . (int) $attachment_id . '.mp4' : false;
}

function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
    $GLOBALS['_cooked_test_meta_boxes'][] = [
        'id'     => $id,
        'title'  => $title,
        'screen' => $screen,
    ];
}

function register_post_type( $post_type, $args = [] ) {
    $GLOBALS['_cooked_test_registered_post_types'][ $post_type ] = $args;
    return true;
}

function register_taxonomy( $taxonomy, $object_type, $args = [] ) {
    $GLOBALS['_cooked_test_registered_taxonomies'][ $taxonomy ] = $args;
    return true;
}

function register_widget( $widget ) {
    $GLOBALS['_cooked_test_registered_widgets'][] = $widget;
    return true;
}

function add_role( $role, $display_name, $capabilities = [] ) {
    $GLOBALS['_cooked_test_registered_roles'][ $role ] = [
        'name' => $display_name,
        'caps' => $capabilities,
    ];
    return true;
}

function get_role( $role ) {
    return false;
}

function remove_role( $role ) {
    return true;
}

function register_activation_hook( $file, $callback ) {}

function add_image_size( $name, $width = 0, $height = 0, $crop = false ) {
    return true;
}

function get_adjacent_post( $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category' ) {
    $key = $previous ? 'prev' : 'next';
    return isset( $GLOBALS['_cooked_test_adjacent'][ $key ] ) ? $GLOBALS['_cooked_test_adjacent'][ $key ] : null;
}

function is_user_logged_in() {
    return ! empty( $GLOBALS['_cooked_test_logged_in'] );
}

function get_current_user_id() {
    return ! empty( $GLOBALS['_cooked_test_logged_in'] ) ? 1 : 0;
}

function wp_get_current_user() {
    return (object) [
        'ID'         => get_current_user_id(),
        'user_login' => 'admin',
        'roles'      => [ 'administrator' ],
    ];
}

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
require_once __DIR__ . '/FilterTestCase.php';
