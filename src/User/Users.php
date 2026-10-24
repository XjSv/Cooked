<?php
/**
 * Cooked User-Specific Functions
 *
 * @package     Cooked
 * @subpackage  User-Specific Functions
 * @since       1.0.0
 */

namespace Cooked\User;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cooked_User Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Users {

    /**
     * Registers the user hooks.
     */
    public function __construct() {
        add_action( 'init', [ &$this, 'recipe_author_rewrite' ], 10 );

        add_filter( 'manage_users_columns', [ &$this, 'recipe_count_column' ] );
        add_filter( 'manage_users_sortable_columns', [ &$this, 'recipe_count_column_sortable' ] );
        add_filter( 'manage_users_custom_column', [ &$this, 'recipe_count_column_value' ], 10, 3 );
        add_action( 'pre_user_query', [ &$this, 'pre_user_query' ], 1 );
    }

    /**
     * Adds rewrite rules for recipe author pages under each Browse page.
     *
     * @return void
     */
    public static function recipe_author_rewrite() {
        global $_cooked_settings;

        $front_page_id = get_option( 'page_on_front' );

        // Get all browse page translations for rewrite rules.
        $browse_pages = \Cooked_Multilingual::get_all_browse_pages();

        if ( empty( $browse_pages ) ) {
            return;
        }

        add_rewrite_tag( '%recipe_author%', '([^&]+)' );

        // Create rewrite rules for each browse page translation.
        foreach ( $browse_pages as $lang => $page_data ) {
            $browse_page_id   = $page_data['id'];
            $browse_page_slug = $page_data['slug'] ? basename( $page_data['slug'] ) : false;

            if ( ! $browse_page_slug || (int) $browse_page_id === (int) $front_page_id ) {
                continue;
            }

            add_rewrite_rule( '^' . $browse_page_slug . '/' . $_cooked_settings['recipe_author_permalink'] . '/([^/]*)/page/([^/]*)/?', 'index.php?page_id=' . esc_attr( $browse_page_id ) . '&paged=$matches[2]&recipe_author=$matches[1]', 'top' );
            add_rewrite_rule( '^' . $browse_page_slug . '/' . $_cooked_settings['recipe_author_permalink'] . '/([^/]*)/?', 'index.php?page_id=' . esc_attr( $browse_page_id ) . '&recipe_author=$matches[1]', 'top' );
        }
    }

    /**
     * A recipe author: name, profile URL and avatar.
     *
     * @param int|false    $user_id       User ID.
     * @param bool         $basic         Leave out the avatar and profile URL.
     * @param string|false $user_nicename User slug, when there is no ID.
     * @return array<string, mixed>|false
     */
    public static function get( $user_id = false, $basic = false, $user_nicename = false ) {
        if ( ! $user_id && $user_nicename ) {
            $user = get_user_by( 'slug', $user_nicename );
            if ( $user ) {
                $user_id = $user->ID;
            } else {
                return false;
            }
        }

        if ( ! $user_id ) {
            return false;
        }

        $_user      = get_userdata( (int) $user_id );
        $_user_meta = get_user_meta( (int) $user_id, 'cooked_user_meta', true );

        if ( ! empty( $_user ) ) {
            if ( isset( $_user_meta['profile_photo_id'] ) && $_user_meta['profile_photo_id'] && wp_attachment_is_image( $_user_meta['profile_photo_id'] ) ) {
                $profile_photo     = wp_get_attachment_image( $_user_meta['profile_photo_id'], 'cooked-profile-photo' );
                $profile_photo_src = wp_get_attachment_image_src( $_user_meta['profile_photo_id'], 'cooked-profile-photo' );
                $profile_photo_src = isset( $profile_photo_src[0] ) && $profile_photo_src[0] ? $profile_photo_src[0] : false;
            } else {
                $profile_photo     = get_avatar( $_user->user_email, 96 );
                $profile_photo_src = get_avatar_url( $_user->user_email, [ 'size' => 96 ] );
            }

            if ( is_array( $_user_meta ) ) {
                $_user_data = $_user_meta;
            }

            $_user_data['id']                = $user_id;
            $_user_data['user_nicename']     = $_user->user_nicename;
            $_user_data['name']              = self::format_author_name( $_user->display_name );
            $_user_data['profile_photo']     = $profile_photo;
            $_user_data['profile_photo_src'] = $profile_photo_src;

            if ( ! $basic ) {
                $_user_data['raw'] = $_user;

                $user_recipe_args = [
                    'post_type'      => 'cp_recipe',
                    'post_status'    => [ 'publish', 'pending', 'draft' ],
                    'posts_per_page' => -1,
                    'author'         => $user_id,
                ];

                $_user_data['recipes'] = \Cooked_Recipes::get( $user_recipe_args, false, true );
            }
            return $_user_data;
        } else {
            return false;
        }
    }

    /**
     * Formats an author name per the Author Name Format setting.
     *
     * @param string       $name   Full name.
     * @param string|false $format full, first_only, first_last_initial or first_initial_last.
     * @return string|false
     */
    public static function format_author_name( $name, $format = false ) {
        if ( ! $name ) {
			return false;
        }

        global $_cooked_settings;

        $_cooked_settings = empty( $_cooked_settings ) ? \Cooked_Settings::get() : $_cooked_settings;

        if ( ! $format && isset( $_cooked_settings['author_name_format'] ) && $_cooked_settings['author_name_format'] ) :
            $format = $_cooked_settings['author_name_format'];
        elseif ( ! $format ) :
            $format = 'full';
        endif;

        $filtered = apply_filters( 'cooked_format_author_name', $name, $format );

        // If the filter returns an array with a second element true, treat as safe.
        if ( is_array( $filtered ) && isset( $filtered[1] ) && true === $filtered[1] ) {
            $name = $filtered[0];
            $safe = true;
        } else {
            $name = is_array( $filtered ) ? $filtered[0] : $filtered;
            $safe = false;
        }

        $parts = explode( ' ', $name );
        switch ( $format ) {
            case 'first_last_initial':
                $formatted = isset( $parts[1] ) ? $parts[0] . ' ' . mb_substr( $parts[1], 0, 1 ) . '.' : $parts[0];
                break;
            case 'first_initial_last':
                $formatted = isset( $parts[1] ) ? mb_substr( $parts[0], 0, 1 ) . '. ' . $parts[1] : $parts[0];
                break;
            case 'first_only':
                $formatted = $parts[0];
                break;
            default: // 'full'
                $formatted = $name;
        }

        // Escaped the same way for every format, unless the filter marked its name as safe.
        return $safe ? $formatted : esc_html( $formatted );
    }

    /**
     * Adds the Recipes column to the Users screen.
     *
     * @param array<string, string> $column_headers Columns.
     * @return array<string, string>
     */
    public function recipe_count_column( $column_headers ) {
        $column_headers['cooked_recipe_count'] = __( 'Recipes', 'cooked' );

        return $column_headers;
    }

    /**
     * Makes the Recipes column sortable.
     *
     * @param array<string, string> $columns Sortable columns.
     * @return array<string, string>
     */
    public function recipe_count_column_sortable( $columns ) {
        $columns['cooked_recipe_count'] = 'cooked_recipe_count';

        return $columns;
    }

    /**
     * Outputs a user's recipe count.
     *
     * @param string $value       Column value.
     * @param string $column_name Column key.
     * @param int    $user_id     User ID.
     * @return string
     */
    public function recipe_count_column_value( $value, $column_name, $user_id ) {
        if ( 'cooked_recipe_count' === $column_name ) {
            $value = count_user_posts( $user_id, 'cp_recipe' );
        }

        return $value;
    }

    /**
     * Sorts users by recipe count.
     *
     * @param \WP_User_Query $query User query.
     * @return void
     */
    public function pre_user_query( $query ) {
        global $wpdb, $current_screen;

        // Only filter in the admin.
        if ( ! is_admin() ) {
			return;
        }

        // Only filter on the users screen.
        if ( ! ( isset( $current_screen ) && 'users' === $current_screen->id ) ) {
			return;
        }

        // Only filter if orderby is set to 'cooked_recipe_count'.
        if ( isset( $query->query_vars['orderby'] ) && ( 'cooked_recipe_count' === $query->query_vars['orderby'] ) ) {
            // We need the order - default is ASC.
            $order = isset( $query->query_vars['order'] ) && 0 === strcasecmp( $query->query_vars['order'], 'desc' ) ? 'DESC' : 'ASC';

            // Order the posts by recipe count.
            $query->query_orderby = "ORDER BY ( SELECT COUNT(*) FROM {$wpdb->posts} posts WHERE posts.post_type = 'cp_recipe' AND posts.post_status IN ('publish', 'pending', 'draft') AND posts.post_author = {$wpdb->users}.ID ) {$order}";
        }
    }
}
