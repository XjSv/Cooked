<?php
/**
 * Cooked Recipe-Specific Functions
 *
 * @package     Cooked
 * @subpackage  Recipe-Specific Functions
 * @since       1.0.0
 */

namespace Cooked\Recipe;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cooked_Recipe_Meta Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Recipes {

    /**
     * Registers the recipe display hooks.
     */
    public function __construct() {
        add_filter( 'cooked_recipe_content_filter', [ &$this, 'vendor_checks' ], 1, 1 );
        add_filter( 'the_content', [ &$this, 'recipe_template' ], 10 );

        add_action( 'parse_query', [ &$this, 'custom_taxonomy_in_query' ], 10 );

        add_action( 'template_redirect', [ &$this, 'print_recipe_template' ], 10 );
        add_filter( 'rest_cp_recipe_query', [ &$this, 'rest_hide_private_recipes' ], 10, 1 );
        add_filter( 'rest_prepare_cp_recipe', [ &$this, 'rest_hide_private_recipe' ], 10, 1 );
        add_action( 'cooked_check_recipe_query', [ &$this, 'check_recipe_query' ], 10 );
        add_action( 'pre_get_posts', [ &$this, 'cooked_pre_get_posts' ], 10, 1 );
        add_action( 'restrict_manage_posts', [ &$this, 'filter_recipes_by_taxonomy' ], 10 );

        add_filter( 'get_canonical_url', [ &$this, 'modify_browse_page_canonical_url' ], 20 );
        add_filter( 'post_thumbnail_id', [ 'Cooked_Recipes', 'filter_default_recipe_thumbnail' ], 10, 2 );
    }

    /**
     * Uses the default recipe image for recipes without a featured image.
     *
     * @param int|string        $thumbnail_id Thumbnail ID.
     * @param int|\WP_Post|null $post         Post.
     * @return int|string
     */
    public static function filter_default_recipe_thumbnail( $thumbnail_id, $post ) {
        if ( $thumbnail_id ) {
            return $thumbnail_id;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            return $thumbnail_id;
        }

        $post = get_post( $post );

        if ( ! $post || 'cp_recipe' !== $post->post_type ) {
            return $thumbnail_id;
        }

        global $_cooked_settings;

        if ( empty( $_cooked_settings ) ) {
            $_cooked_settings = \Cooked_Settings::get();
        }

        $default_image_id = isset( $_cooked_settings['default_recipe_image'] ) ? absint( $_cooked_settings['default_recipe_image'] ) : 0;

        if ( $default_image_id && wp_attachment_is_image( $default_image_id ) ) {
            return $default_image_id;
        }

        return $thumbnail_id;
    }

    /**
     * Queries recipes.
     *
     * @param array<string, mixed>|int|string|false $args                WP_Query arguments, or a recipe ID.
     * @param bool                                  $single              Return only the first recipe.
     * @param bool                                  $ids_only            Return recipe IDs only.
     * @param int|false                             $limit               Maximum number of recipes.
     * @param bool                                  $ids_and_titles_only Return IDs and titles only.
     * @param string|array<int, string>             $post_status         Post status(es) to include.
     * @return mixed Recipe IDs, recipes with their settings (or the first one), or null when none match.
     */
    public static function get( $args = false, $single = false, $ids_only = false, $limit = false, $ids_and_titles_only = false, $post_status = 'publish' ) {
        $recipes = [];
        $counter = 0;

        // Get by Recipe ID.
        if ( $args && ! is_array( $args ) ) :

            $recipe_id = $args;
            $args      = [
                'post_type'   => 'cp_recipe',
                'post__in'    => [ $recipe_id ],
                'post_status' => $post_status,
            ];

			// Default Query.
        elseif ( ! $args ) :

            $args = [
                'post_type'      => 'cp_recipe',
                'posts_per_page' => -1,
                'post_status'    => $post_status,
                'orderby'        => 'name',
                'order'          => 'ASC',
            ];

            if ( $ids_only || $ids_and_titles_only ) :
                $args['fields'] = 'ids';
            endif;

            if ( $limit ) :
                $args['posts_per_page'] = $limit;
            endif;

			// Search Query.
        elseif ( isset( $args['s'] ) && isset( $args['meta_query'] ) ) :

            $meta_query = $args['meta_query'];
            $recipe_ids = [];

            $pre_search_args                   = $args;
            $pre_search_args['posts_per_page'] = -1;
            $pre_search_args['fields']         = 'ids';

            unset( $pre_search_args['meta_query'] );

            $recipes_pre_search = new \WP_Query( $pre_search_args );
            if ( $recipes_pre_search->have_posts() ) :
                $recipe_ids = (array) $recipes_pre_search->posts;
            endif;

            $pre_search_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Recipe search filters need meta queries.
            unset( $pre_search_args['s'] );

            $recipes_pre_search = new \WP_Query( $pre_search_args );
            if ( $recipes_pre_search->have_posts() ) :
                $rposts     = (array) $recipes_pre_search->posts;
                $recipe_ids = ! empty( $recipe_ids ) ? array_merge( $recipe_ids, $rposts ) : $rposts;
            endif;

            $recipe_ids = array_unique(
                array_map(
                    function ( $id ) {
                        return $id instanceof \WP_Post ? $id->ID : (int) $id;
                    },
                    $recipe_ids
                )
            );

            if ( ! empty( $recipe_ids ) ) :
                unset( $args['s'] );
                unset( $args['meta_query'] );
                $args['post__in'] = $recipe_ids;
            endif;

        endif;

        $recipes_results = new \WP_Query( $args );

        if ( $recipes_results->have_posts() ) {
            if ( $ids_only ) {
                return $recipes_results->posts;
            } elseif ( $ids_and_titles_only ) {
                while ( $recipes_results->have_posts() ) {
                    $recipes_results->the_post();
                    $recipe_post = $recipes_results->post;
                    if ( ! $recipe_post ) {
                        continue;
                    }
                    $recipes[ $counter ]['id']    = $recipe_post->ID;
                    $recipes[ $counter ]['title'] = $recipe_post->post_title;

                    ++$counter;
                }
            } else {
                while ( $recipes_results->have_posts() ) {
                    $recipes_results->the_post();
                    $recipe_post = $recipes_results->post;
                    if ( ! $recipe_post ) {
                        continue;
                    }
                    $recipes[ $counter ]['id']    = $recipe_post->ID;
                    $recipes[ $counter ]['title'] = $recipe_post->post_title;
                    $recipe_settings              = (array) self::get_settings( $recipe_post->ID );

                    foreach ( $recipe_settings as $key => $setting ) {
                        $recipes[ $counter ][ $key ] = $setting;
                    }

                    ++$counter;
                }
            }
        } else {
            wp_reset_postdata();
            return;
        }

        $recipes['raw'] = $recipes_results;

        if ( $single && isset( $recipes[0] ) ) :
            $recipes = $recipes[0];
        endif;

        wp_reset_postdata();

        return $recipes;
    }

    /**
     * A recipe's settings, including title, author and ID.
     *
     * @param int  $post_id Recipe ID.
     * @param bool $bc      Convert settings saved by Cooked 2.x.
     * @return array<string, mixed>|null Settings, an empty array if the recipe does not exist, or null without an ID.
     */
    public static function get_settings( $post_id, $bc = true ) {
        if ( ! $post_id ) {
			return null;
        }

        $recipe_post = get_post( $post_id );
        // The recipe may have been deleted (e.g. mid-request, or a stale ID in a shortcode).
        if ( ! $recipe_post ) {
            return [];
        }

        $recipe_settings = get_post_meta( $post_id, '_recipe_settings', true );

        if ( ! is_array( $recipe_settings ) || empty( $recipe_settings ) ) {
            $recipe_settings = [];
        }

        $recipe_settings['title'] = get_the_title( $post_id );

        // Check for nutrition data.
        if ( ! isset( $recipe_settings['nutrition'] ) ) :
            $recipe_settings['nutrition']             = [];
            $recipe_settings['nutrition']['servings'] = 1;
        endif;

        // Backwards Compatibility with Cooked 2.x.
        if ( ! isset( $recipe_settings['cooked_version'] ) && $bc ) :
            $c2_recipe_settings = self::get_c2_recipe_meta( $post_id );
            $recipe_settings    = self::sync_c2_recipe_settings( $c2_recipe_settings, $post_id );
        endif;

        // Get the author information.
        $recipe_settings['author'] = \Cooked_Users::get( (int) $recipe_post->post_author, true );

        // Include the Post ID.
        $recipe_settings['id'] = $post_id;

        $recipe_settings = apply_filters( 'cooked_single_recipe_settings', $recipe_settings, $post_id );

        return $recipe_settings;
    }

    /**
     * Sets the requested (or default) category for the recipe query.
     *
     * @return void
     */
    public function check_recipe_query() {
        global $_cooked_settings, $recipe_query;

        if ( ! isset( $recipe_query['cp_recipe_category'] ) ) :
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only browse category filter.
            $requested_category                 = isset( $_GET['cp_recipe_category'] ) ? absint( wp_unslash( $_GET['cp_recipe_category'] ) ) : 0;
            $recipe_query['cp_recipe_category'] = $requested_category ? $requested_category : ( isset( $_cooked_settings['browse_default_cp_recipe_category'] ) && $_cooked_settings['browse_default_cp_recipe_category'] ? $_cooked_settings['browse_default_cp_recipe_category'] : false );
        endif;
    }

    /**
     * Adds recipe title search to the _cooked_title query variable.
     *
     * @param \WP_Query $q Query.
     * @return void
     */
    public static function cooked_pre_get_posts( $q ) {
        $title = $q->get( '_cooked_title' );
        if ( $title ) {
            add_filter(
                'get_meta_sql',
                function ( $sql ) use ( $title ) {
					global $wpdb, $cooked_modified_where;

					if ( $cooked_modified_where ) {
						return $sql;
					}
					$cooked_modified_where = 1;

					// Modified WHERE.
					$sql['where'] = sprintf(
                        ' AND ( %s OR %s ) ',
                        apply_filters( 'cooked_query_where_filter', $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $title ) . '%' ) ),
                        mb_substr( $sql['where'], 5, mb_strlen( $sql['where'] ) )
					);

					return $sql;
				}
            );
        }
    }

    /**
     * Outputs a compact list of recipes.
     *
     * @param string                              $orderby     date or ids.
     * @param int|string                          $show        Number of recipes.
     * @param array<int, int|string>|string|false $recipes     Recipe IDs, when ordering by ID.
     * @param string|false                        $width       List width.
     * @param bool|string                         $hide_image  Hide the images.
     * @param bool|string                         $hide_author Hide the authors.
     * @return void
     */
    public static function recipe_list( $orderby = 'date', $show = 5, $recipes = false, $width = false, $hide_image = false, $hide_author = false ) {
        global $_cooked_settings;

        $width         = ! $width ? '100%' : $width;
        $pixel_width   = stristr( $width, 'px', true );
        $percent_width = stristr( $width, '%', true );
        $width         = $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) );

        $args = [
            'post_type'      => 'cp_recipe',
            'posts_per_page' => $show,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'order'          => 'DESC',
        ];

        if ( ! empty( $recipes ) ) {
            $args['posts_per_page'] = -1;
            $args['orderby']        = 'post__in';
            $args['order']          = 'ASC';
            $args['post__in']       = $recipes;
        }

        $recipes = \Cooked_Recipes::get( $args );

        if ( isset( $recipes['raw'] ) ) :
			unset( $recipes['raw'] );
endif;
        $recipe_list = [];

        if ( ! empty( $recipes ) ) :

            echo '<div class="cooked-shortcode-recipe-list">';

			foreach ( $recipes as $key => $recipe ) :

				$rid             = $recipe['id'];
				$has_image_class = has_post_thumbnail( (int) $rid ) && ! $hide_image ? ' cooked-srl-has-image' : '';

				echo '<div class="cooked-srl-single' . esc_attr( $has_image_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

					echo has_post_thumbnail( (int) $rid ) && ! $hide_image ? '<div class="cooked-srl-image"><a href="' . esc_url( (string) get_permalink( (int) $rid ) ) . '">' . wp_kses_post( get_the_post_thumbnail( $rid, 'thumbnail' ) ) . '</a></div>' : '';

					echo '<div class="cooked-srl-content">';

						echo '<div class="cooked-srl-title"><a href="' . esc_url( (string) get_permalink( (int) $rid ) ) . '">' . esc_html( $recipe['title'] ) . '</a></div>';

				if ( in_array( 'author', $_cooked_settings['recipe_info_display_options'], true ) && ! $hide_author ) :
					echo '<div class="cooked-srl-author">';
					$author = $recipe['author'];
					/* translators: stating the recipe author with a "By" in front of it. (ex: "By John Smith")  */
					printf( esc_html__( 'By %s', 'cooked' ), '<strong>' . wp_kses_post( $author['name'] ) . '</strong>' );
					echo '</div>';
						endif;

					echo '</div>';

                    echo '</div>';
                    endforeach;

            echo '</div>';

        endif;
    }

    /**
     * A recipe card.
     *
     * @param int|string   $rid          Recipe ID.
     * @param string|false $width        Card width.
     * @param bool|string  $hide_image   Hide the image.
     * @param bool|string  $hide_title   Hide the title.
     * @param bool|string  $hide_excerpt Hide the excerpt.
     * @param bool|string  $hide_author  Hide the author.
     * @param string|false $style        Card style.
     * @return string|false
     */
    public static function card( $rid, $width = false, $hide_image = false, $hide_title = false, $hide_excerpt = false, $hide_author = false, $style = false ) {
        global $_cooked_settings;

        $recipe      = self::get( $rid, true );
        $style_class = $style ? ' cooked-recipe-card-' . esc_attr( $style ) : '';

        $width         = ! $width ? '100%' : $width;
        $pixel_width   = stristr( $width, 'px', true );
        $percent_width = stristr( $width, '%', true );
        $width         = $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) );

        ob_start();

        echo '<a href="' . esc_url( (string) get_permalink( (int) $rid ) ) . '" class="cooked-recipe-card' . esc_attr( $style_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

            do_action( 'cooked_recipe_grid_before_recipe', $recipe );

            do_action( 'cooked_recipe_grid_before_image', $recipe );

            echo has_post_thumbnail( (int) $rid ) && ! $hide_image ? '<span class="cooked-recipe-card-image" style="background-image:url(' . esc_url( (string) get_the_post_thumbnail_url( $recipe['id'], 'cooked-medium' ) ) . ');"></span>' : '';

            echo '<span class="cooked-recipe-card-content">';

                do_action( 'cooked_recipe_grid_before_name', $recipe );

                echo ! $hide_title ? '<span class="cooked-recipe-card-title">' . esc_html( $recipe['title'] ) . '</span>' : '';

                do_action( 'cooked_recipe_grid_after_name', $recipe );

		if ( $hide_excerpt && ! $hide_title && ! $hide_author ) :
			echo '<span class="cooked-recipe-card-sep"></span>';
endif;

                do_action( 'cooked_recipe_grid_before_author', $recipe );

		if ( in_array( 'author', $_cooked_settings['recipe_info_display_options'], true ) && ! $hide_author ) :
			echo '<span class="cooked-recipe-card-author">';
				$author = $recipe['author'];
				/* translators: stating the recipe author with a "By" in front of it. (ex: "By John Smith")  */
				echo wp_kses_post( sprintf( __( 'By %s', 'cooked' ), '<strong>' . esc_html( $author['name'] ) . '</strong>' ) );
			echo '</span>';
                endif;

                do_action( 'cooked_recipe_grid_after_author', $recipe );

		if ( ( ! $hide_excerpt && ! $hide_title ) || ( ! $hide_excerpt && ! $hide_author ) ) :
			echo '<span class="cooked-recipe-card-sep"></span>';
endif;

                do_action( 'cooked_recipe_grid_before_excerpt', $recipe );

                echo ! $hide_excerpt ? '<span class="cooked-recipe-card-excerpt">' . wp_kses_post( $recipe['excerpt'] ) . '</span>' : '';

                do_action( 'cooked_recipe_grid_after_excerpt', $recipe );

            echo '</span>';

            do_action( 'cooked_recipe_grid_after_recipe', $recipe );

        echo '</a>';

        return ob_get_clean();
    }

    /**
     * Shows the print view for ?print requests.
     *
     * @return void
     */
    public function print_recipe_template() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only print view flag.
        if ( is_singular( 'cp_recipe' ) && isset( $_GET['print'] ) ) :
            // With public recipes disabled, only print links from a [cooked-recipe] embed (which
            // carry the recipe's print key) or editors may open the print view.
            if ( self::public_recipes_disabled() && ! current_user_can( 'edit_cooked_recipes' ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checked against the recipe's HMAC below.
                $key = isset( $_GET['print_key'] ) ? sanitize_key( wp_unslash( $_GET['print_key'] ) ) : '';
                if ( ! hash_equals( self::print_key( get_queried_object_id() ), $key ) ) {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header( 404 );
                    nocache_headers();
                    return;
                }
            }

            load_template( COOKED_DIR . 'templates/front/recipe-print.php', false );
            exit;
        endif;
    }

    /**
     * Whether "Disable Public Recipes" is on (Settings > General > Advanced Settings).
     *
     * @since 2.0.0
     * @return bool
     */
    public static function public_recipes_disabled() {
        global $_cooked_settings;
        return isset( $_cooked_settings['advanced'] ) && in_array( 'disable_public_recipes', (array) $_cooked_settings['advanced'], true );
    }

    /**
     * Per-recipe key that lets a [cooked-recipe] embed's print link open the print view
     * while public recipes are disabled.
     *
     * @since 2.0.0
     * @param int $recipe_id Recipe post ID.
     * @return string
     */
    public static function print_key( $recipe_id ) {
        return substr( wp_hash( 'cooked_print_' . (int) $recipe_id ), 0, 20 );
    }

    /**
     * With public recipes disabled, the REST recipe collection is empty for anyone who
     * can't edit recipes (the post type stays in REST for the editor).
     *
     * @since 2.0.0
     * @param array<string, mixed> $args WP_Query arguments for the REST collection.
     * @return array<string, mixed>
     */
    public function rest_hide_private_recipes( $args ) {
        if ( self::public_recipes_disabled() && ! current_user_can( 'edit_cooked_recipes' ) ) {
            $args['post__in'] = [ 0 ];
        }
        return $args;
    }

    /**
     * With public recipes disabled, single REST recipes are refused to anyone who can't
     * edit recipes.
     *
     * @since 2.0.0
     * @param \WP_REST_Response $response The prepared recipe.
     * @return \WP_REST_Response|\WP_Error
     */
    public function rest_hide_private_recipe( $response ) {
        if ( self::public_recipes_disabled() && ! current_user_can( 'edit_cooked_recipes' ) ) {
            return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to view this recipe.', 'cooked' ), [ 'status' => rest_authorization_required_code() ] );
        }
        return $response;
    }

    /**
     * Prints the site logo in the print view, if enabled.
     *
     * @return void
     */
    public static function print_logo() {
        global $_cooked_settings;

        $print_view_options = isset( $_cooked_settings['print_view_display_options'] ) && is_array( $_cooked_settings['print_view_display_options'] )
            ? $_cooked_settings['print_view_display_options']
            : [];

        if ( ! in_array( 'site_logo', $print_view_options, true ) ) {
            return;
        }

        echo '<div id="cooked-print-logo">';

        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            echo wp_get_attachment_image(
                $logo_id,
                'full',
                false,
                [
                    'alt'   => get_bloginfo( 'name' ),
                    'class' => 'cooked-print-logo-image',
                ]
            );
        } else {
            echo '<span class="cooked-print-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
        }

        echo '</div>';
    }

    /**
     * Hides recipe content restricted by WooCommerce Memberships.
     *
     * @param string $content Recipe content.
     * @return string
     */
    public static function vendor_checks( $content ) {
        global $wp_query, $post;

        // WooCommerce Memberships.
        if ( function_exists( 'wc_memberships_user_can' ) && function_exists( 'wc_memberships_is_post_content_restricted' ) && function_exists( 'wc_memberships_user_can' ) ) :

            if ( ! is_user_logged_in() && wc_memberships_is_post_content_restricted() ) :
                return \WC_Memberships_User_Messages::get_message_html( 'content_restricted', [ 'post' => $post ] );
            elseif ( is_user_logged_in() ) :
                $_user = wp_get_current_user();
                if ( ! wc_memberships_user_can( $_user->ID, 'view', [ 'cp_recipe' => $post->ID ] ) ) :
                    return \WC_Memberships_User_Messages::get_message_html( 'content_restricted', [ 'post' => $post ] );
                endif;
            endif;

        endif;

        return $content;
    }

    /**
     * Adds category filters to the recipe list screen.
     *
     * @return void
     */
    public function filter_recipes_by_taxonomy() {
        global $typenow, $cooked_taxonomies_shown;
        $taxonomies = apply_filters( 'cooked_active_taxonomies', [ 'cp_recipe_category' ] );
        if ( 'cp_recipe' === $typenow ) :
            foreach ( $taxonomies as $taxonomy ) :
                if ( ( is_array( $cooked_taxonomies_shown ) && ! in_array( $taxonomy, $cooked_taxonomies_shown, true ) ) || ! is_array( $cooked_taxonomies_shown ) ) :
                    $cooked_taxonomies_shown[] = $taxonomy;
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin taxonomy filter.
                    $selected       = isset( $_GET[ $taxonomy ] ) ? absint( wp_unslash( $_GET[ $taxonomy ] ) ) : '';
                    $info_taxonomy  = get_taxonomy( $taxonomy );
                    $taxonomy_label = $info_taxonomy ? $info_taxonomy->label : $taxonomy;

                    /* translators: For showing "All" of a taxonomy (ex: "All Burgers")  */
                    $all_string = sprintf( __( 'All %s', 'cooked' ), $taxonomy_label );

                    wp_dropdown_categories(
                        [
							'show_option_all' => $all_string,
							'taxonomy'        => $taxonomy,
							'name'            => $taxonomy,
							'orderby'         => 'name',
							'selected'        => $selected,
							'show_count'      => true,
							'hide_empty'      => true,
						]
                    );
                endif;
            endforeach;
        endif;
    }

    /**
     * Filters the recipe list screen by the chosen category.
     *
     * @param \WP_Query $query Query.
     * @return void
     */
    public function custom_taxonomy_in_query( $query ) {
        global $pagenow;
        $taxonomies = apply_filters( 'cooked_active_taxonomies', [ 'cp_recipe_category' ] );
        $q_vars     = &$query->query_vars;

        foreach ( $taxonomies as $taxonomy ) :
            if ( 'edit.php' === $pagenow && isset( $q_vars['post_type'] ) && 'cp_recipe' === $q_vars['post_type'] && isset( $q_vars[ $taxonomy ] ) && is_numeric( $q_vars[ $taxonomy ] ) && 0 !== (int) $q_vars[ $taxonomy ] ) :
                $term = get_term_by( 'id', (int) $q_vars[ $taxonomy ], $taxonomy );
                if ( $term ) {
                    $q_vars[ $taxonomy ] = $term->slug;
                }
            endif;
        endforeach;
    }

    /**
     * Normalizes a taxonomy query value (a slug, a comma-separated list or an array).
     *
     * @param mixed $value Query value.
     * @return array<int, string>
     */
    public static function taxonomy_query_terms( $value ) {
        if ( is_array( $value ) ) {
            $values = $value;
        } else {
            $values = explode( ',', (string) $value );
        }

        $terms = [];

        foreach ( $values as $term ) {
            if ( ! is_scalar( $term ) ) {
                continue;
            }

            $term = trim( sanitize_text_field( (string) $term ) );

            if ( '' !== $term ) {
                $terms[] = $term;
            }
        }

        return array_values( array_unique( $terms ) );
    }

    /**
     * Whether a list of taxonomy terms should be queried by ID or by slug.
     *
     * @param array<int, string> $terms Normalized terms.
     * @return string
     */
    public static function taxonomy_query_field( $terms ) {
        $numeric = array_filter(
            $terms,
            static function ( $term ) {
                return is_numeric( $term );
            }
        );

        return count( $numeric ) === count( $terms ) ? 'id' : 'slug';
    }

    /**
     * Resolves normalized taxonomy query terms to term objects.
     *
     * @param string             $taxonomy Taxonomy.
     * @param array<int, string> $terms    Normalized terms.
     * @param string             $field    Query field, id or slug.
     * @return array<int, \WP_Term>
     */
    public static function resolve_taxonomy_terms( $taxonomy, $terms, $field ) {
        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ];

        if ( 'id' === $field ) {
            $args['include'] = array_map( 'absint', $terms );
        } else {
            $args['slug'] = array_map( 'sanitize_title', $terms );
        }

        $term_objects = get_terms( $args );

        return is_wp_error( $term_objects ) ? [] : $term_objects;
    }

    /**
     * The recipe list for [cooked-browse] and friends.
     *
     * @param array<string, mixed>|false $list_atts List attributes.
     * @return string|false
     */
    public static function list_view( $list_atts = false ) {
        global $wp_query, $recipe_query, $atts, $_cooked_settings, $recipes, $recipe_args, $current_recipe_page;

        // Get the attributes for this view.
        $atts = array_change_key_case( (array) $list_atts, CASE_LOWER );

        $ls_method = 'list_style_grid';
        $ls_class  = 'Cooked_Recipes';

        // Change the recipe layout.
        if ( $atts['layout'] ) {
            $recipe_list_style = apply_filters( 'cooked_recipe_list_style', [ 'grid' => 'Cooked_Recipes' ], $atts['layout'] );
            $list_style        = esc_html( key( $recipe_list_style ) );
            $ls_method         = 'list_style_' . $list_style;
            $ls_class          = current( $recipe_list_style );

            $_cooked_settings['recipe_list_style'] = $list_style;
        }

        $recipe_query = $wp_query->query;

        // The browse checkboxes use their own query var so WordPress does not treat the
        // array as a taxonomy archive query before the recipe list is built.
        if ( empty( $recipe_query['cp_recipe_category'] ) && ! empty( $recipe_query['cooked_category_filter'] ) ) {
            $recipe_query['cp_recipe_category'] = $recipe_query['cooked_category_filter'];
        }

        $tax_query = [];

        do_action( 'cooked_check_recipe_query' );

        if ( isset( $_cooked_settings['recipe_taxonomies'] ) && ! empty( $_cooked_settings['recipe_taxonomies'] ) ) :
            foreach ( $_cooked_settings['recipe_taxonomies'] as $taxonomy ) :
                if ( isset( $recipe_query[ $taxonomy ] ) && $recipe_query[ $taxonomy ] ) :
                    $terms = self::taxonomy_query_terms( $recipe_query[ $taxonomy ] );

                    if ( ! empty( $terms ) ) :
                        $tax_query['relation'] = 'AND';
                        $tax_query[]           = [
                            'taxonomy' => $taxonomy,
                            'field'    => self::taxonomy_query_field( $terms ),
                            'terms'    => $terms,
                        ];
                    endif;
                endif;
            endforeach;

            if ( empty( $tax_query ) ) :
                foreach ( $_cooked_settings['recipe_taxonomies'] as $taxonomy ) :
                    if ( isset( $_cooked_settings[ 'browse_default_' . $taxonomy ] ) && $_cooked_settings[ 'browse_default_' . $taxonomy ] ) :
                        $tax_query['relation'] = 'AND';
                        $tax_query[]           = [
                            'taxonomy' => $taxonomy,
                            'field'    => 'id',
                            'terms'    => [ esc_html( $_cooked_settings[ 'browse_default_' . $taxonomy ] ) ],
                        ];
                    endif;
                endforeach;
            endif;
        endif;

        if ( empty( $tax_query ) ) :

            if ( $atts['category'] ) :
                $terms = self::taxonomy_query_terms( $atts['category'] );

                if ( ! empty( $terms ) ) :
                    $tax_query['relation'] = 'AND';
                    $tax_query[]           = [
                        'taxonomy' => 'cp_recipe_category',
                        'field'    => self::taxonomy_query_field( $terms ),
                        'terms'    => $terms,
                    ];
                endif;
            endif;

            $tax_query = apply_filters( 'cooked_tax_query_filter', $tax_query, $atts );

        endif;

        $sorting_type  = get_query_var( 'cooked_browse_sort_by', isset( $_cooked_settings['browse_default_sort'] ) && $_cooked_settings['browse_default_sort'] ? $_cooked_settings['browse_default_sort'] : 'date_desc' );
        $sorting_type  = sanitize_key( $sorting_type );
        $sorting_types = explode( '_', $sorting_type );

        $text_search         = get_query_var( 'cooked_search_s', '' );
        $text_search         = urldecode( $text_search );
        $text_search         = esc_html( $text_search );
        $recipes_per_page    = ( $atts['show'] ? $atts['show'] : ( isset( $_cooked_settings['recipes_per_page'] ) && $_cooked_settings['recipes_per_page'] ? $_cooked_settings['recipes_per_page'] : get_option( 'posts_per_page' ) ) );
        $current_recipe_page = \Cooked_Recipes::current_page();

        $orderby   = $atts['orderby'] ? esc_html( $atts['orderby'] ) : $sorting_types[0];
        $meta_sort = false;

        $post_status = 'publish';
        if ( isset( $atts['public_recipes'] ) && ! $atts['public_recipes'] ) :
            $post_status = [ 'publish', 'pending', 'draft' ];
        endif;

        $recipe_args = [
            'paged'          => $current_recipe_page,
            'post_type'      => 'cp_recipe',
            'posts_per_page' => $recipes_per_page,
            'post_status'    => $post_status,
            'orderby'        => $orderby,
            'order'          => ( $atts['order'] ? esc_html( $atts['order'] ) : $sorting_types[1] ),
        ];

        if ( isset( $atts['exclude'] ) && $atts['exclude'] ) :
            $exclude                     = explode( ',', str_replace( ' ', '', $atts['exclude'] ) );
            $recipe_args['post__not_in'] = $exclude;
        endif;

        if ( $text_search ) :
            // Treat plus signs, commas and semicolons as spaces.
            $prep_text = str_replace( [ '+', ',', ';' ], ' ', $text_search );

            // Replace duplicate spaces.
            $prep_text = preg_replace( '/\s+/', ' ', $prep_text );

            // Explode into an array of search terms.
            $words = explode( ' ', (string) $prep_text );

            if ( ! empty( $words ) ) :
                $meta_query['relation'] = 'AND';
                foreach ( $words as $word ) :
                    $meta_query[] = [
                        'key'     => '_recipe_settings',
                        'value'   => $word,
                        'compare' => 'LIKE',
                    ];
                endforeach;
            else :
                $meta_query[] = [
                    'key'     => '_recipe_settings',
                    'value'   => $text_search,
                    'compare' => 'LIKE',
                ];
            endif;
            $recipe_args['_cooked_title'] = $prep_text;
            $recipe_args['s']             = $prep_text;
            $recipe_args['meta_query']    = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Recipe list filters need meta queries.

        endif;

        if ( ! empty( $tax_query ) ) :
            $recipe_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Recipe list filters by taxonomy.
        endif;

        if ( $atts['author'] && is_numeric( $atts['author'] ) ) :
            $recipe_args['author'] = $atts['author'];
        elseif ( $atts['author'] ) :
            $recipe_args['author_name'] = $atts['author'];
        endif;

        if ( isset( $atts['include'] ) && ! empty( $atts['include'] ) ) :
            $recipe_args['post__in'] = $atts['include'];
        endif;

        $recipe_args = apply_filters( 'cooked_recipe_query_args', $recipe_args, $atts, $sorting_types );
        if ( ! isset( $atts['public_recipes'] ) || ( isset( $atts['public_recipes'] ) && $atts['public_recipes'] ) ) :
            $recipe_args = apply_filters( 'cooked_recipe_public_query_filters', $recipe_args );
        endif;

        wp_suspend_cache_addition( true );

        ob_start();
        $recipes                 = \Cooked_Recipes::get( $recipe_args );
        $theme_template_override = locate_template( 'recipe-list.php' );
        $theme_template_override ? load_template( $theme_template_override, false ) : load_template( COOKED_DIR . 'templates/front/recipe-list.php', false );
        wp_reset_postdata();
        \Cooked_Settings::reset();

        wp_suspend_cache_addition( false );

        return ob_get_clean();
    }

    /**
     * Outputs the grid layout of a recipe list.
     *
     * @param array<string, mixed> $atts List attributes.
     * @return void
     */
    public static function list_style_grid( $atts = [] ) {
        load_template( COOKED_DIR . 'templates/front/recipe-single.php', false, $atts );
    }

    /**
     * The current page of a paginated recipe list.
     *
     * @return int
     */
    public static function current_page() {
        return get_query_var( 'paged' ) ? max( 1, get_query_var( 'paged' ) ) : ( get_query_var( 'page' ) ? max( 1, get_query_var( 'page' ) ) : 1 );
    }

    /**
     * Pagination for a recipe list.
     *
     * @param \WP_Query            $recipe_query Recipe query.
     * @param array<string, mixed> $recipe_args  Query arguments.
     * @return string
     */
    public static function pagination( $recipe_query, $recipe_args ) {
        global $_cooked_settings, $current_recipe_page, $paged, $atts;

        $paged              = self::current_page();
        $total_recipe_pages = $recipe_query->max_num_pages;
        $pagination         = '';

        if ( $total_recipe_pages > 1 ) {
            do_action( 'cooked_init_pagination', $recipe_args, $current_recipe_page, $total_recipe_pages, $atts );

            $pagination_style = apply_filters( 'cooked_pagination_style', [ 'numbered_pagination' => 'Cooked_Recipes' ] );
            $p_method         = key( $pagination_style );
            $p_class          = current( $pagination_style );

            $pagination = $p_class::$p_method( $current_recipe_page, $total_recipe_pages );
        }

        return $pagination;
    }

    /**
     * Numbered page links.
     *
     * @param int $current_recipe_page Current page.
     * @param int $total_recipe_pages  Number of pages.
     * @return string
     */
    public static function numbered_pagination( $current_recipe_page, $total_recipe_pages ) {
        if ( get_option( 'permalink_structure' ) ) {
            $format = '?paged=%#%';
        } else {
            $format = 'page/%#%/';
        }

        $big  = 999999999;
        $base = '?paged=%#%' === $format ? $base = str_replace( (string) $big, '%#%', get_pagenum_link( $big ) ) : $base = add_query_arg( 'paged', '%#%' );

        $recipe_pagination = apply_filters(
            'cooked_pagination_args',
            [
				'base'      => $base,
				'format'    => $format,
				'mid-size'  => 1,
				'current'   => $current_recipe_page,
				'total'     => $total_recipe_pages,
				'prev_next' => true,
				'prev_text' => '<i class="cooked-icon cooked-icon-angle-left"></i>',
				'next_text' => '<i class="cooked-icon cooked-icon-angle-right"></i>',
			]
        );
        $links             = paginate_links( $recipe_pagination );
        return '<div class="cooked-pagination-numbered cooked-clearfix">' . ( is_string( $links ) ? $links : '' ) . '</div>';
    }

    /**
     * Whether content embeds the given recipe with [cooked-recipe].
     *
     * @param string $content   Content.
     * @param int    $recipe_id Recipe ID.
     * @return bool
     */
    public static function content_has_self_recipe_embed_shortcode( $content, $recipe_id ) {
        if ( ! is_string( $content ) || ! $recipe_id ) {
            return false;
        }

        $recipe_id = intval( $recipe_id );

        if ( ! preg_match_all( '#\[cooked-recipe\b[^\]]*\bid\s*=\s*["\']?(\d+)#i', $content, $matches ) ) {
            return false;
        }

        foreach ( $matches[1] as $embedded_id ) {
            if ( intval( $embedded_id ) === $recipe_id ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a recipe's fields embed the recipe itself.
     *
     * @param array<string, mixed> $recipe_settings Recipe settings.
     * @param int                  $post_id         Recipe ID.
     * @return bool
     */
    public static function settings_have_self_recipe_embed_shortcode( $recipe_settings, $post_id ) {
        if ( ! is_array( $recipe_settings ) || empty( $recipe_settings ) || ! $post_id ) {
            return false;
        }

        $fields = [ 'content', 'notes', 'excerpt' ];
        foreach ( $fields as $field ) {
            if ( ! empty( $recipe_settings[ $field ] ) && self::content_has_self_recipe_embed_shortcode( $recipe_settings[ $field ], $post_id ) ) {
                return true;
            }
        }

        if ( ! empty( $recipe_settings['directions'] ) && is_array( $recipe_settings['directions'] ) ) {
            foreach ( $recipe_settings['directions'] as $direction ) {
                if ( ! empty( $direction['content'] ) && self::content_has_self_recipe_embed_shortcode( $direction['content'], $post_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The default recipe template (filterable).
     *
     * @return string
     */
    public static function default_content() {
        return apply_filters( 'cooked_default_content', '<p>[cooked-info left="author,taxonomies,difficulty" right="print,fullscreen"]</p><p>[cooked-excerpt]</p><p>[cooked-image]</p><p>[cooked-info left="servings" right="prep_time,cook_time,total_time"]</p><p>[cooked-info left="allergens"]</p><p>[cooked-ingredients]</p><p>[cooked-directions]</p><p>[cooked-notes show_header=true]</p><p>[cooked-gallery]</p>' );
    }

    /**
     * The print view template (filterable).
     *
     * @return string
     */
    public static function print_content() {
        return apply_filters( 'cooked_print_content', '<p>[cooked-info include="servings,prep_time,cook_time,total_time"]</p><p>[cooked-excerpt]</p><p>[cooked-image]</p><p>[cooked-ingredients]</p><p>[cooked-directions]</p><p>[cooked-notes show_header=true]</p><p>[cooked-nutrition]</p>' );
    }

    /**
     * The full-screen mode template (filterable).
     *
     * @return string
     */
    public static function fsm_content() {
        return apply_filters(
            'cooked_fsm_content',
            '
            <div class="cooked-fsm-ingredients cooked-fsm-content cooked-active" data-nosnippet aria-hidden="false">
                <div class="cooked-panel"><h2>' . __( 'Ingredients', 'cooked' ) . '</h2>[cooked-ingredients]</div>
            </div>
            <div class="cooked-fsm-directions-wrap cooked-fsm-content" data-nosnippet aria-hidden="true">
                <div class="cooked-fsm-directions cooked-fsm-content">
                    <div class="cooked-panel"><h2>' . __( 'Directions', 'cooked' ) . '</h2>[cooked-directions]</div>
                </div>
                <div class="cooked-fsm-notes cooked-fsm-content" data-nosnippet aria-hidden="true">
                    <div class="cooked-panel"><h2>' . __( 'Notes', 'cooked' ) . '</h2>[cooked-notes]</div>
                </div>
            </div>
        '
        );
    }

    /**
     * Returns the fullscreen modal (FSM) markup for a recipe.
     * Used by the recipe template and by the Elementor filter when appending FSM.
     *
     * @param int                  $recipe_id Recipe post ID.
     * @param array<string, mixed> $settings  Recipe settings (from get_settings() or global $recipe_settings).
     * @return string FSM HTML block.
     */
    public static function get_fsm_markup( $recipe_id, array $settings ) {
        $recipe_id = intval( $recipe_id );
        if ( ! $recipe_id || empty( $settings['title'] ) ) {
            return '';
        }
        global $recipe_settings;
        $recipe_settings = $settings;
        $markup          = '<div id="cooked-fsm-' . $recipe_id . '" class="cooked-fsm" data-recipe-id="' . $recipe_id . '">';
        $markup         .= do_shortcode( self::fsm_content() );
        $markup         .= '<div class="cooked-fsm-top">' . esc_html( $settings['title'] ) . '<a href="#" class="cooked-close-fsm"><i class="cooked-icon cooked-icon-close"></i></a></div>';
        $markup         .= '<div class="cooked-fsm-mobile-nav">';
        $markup         .= '<a href="#ingredients" data-nav-id="ingredients" class="cooked-fsm-nav-ingredients cooked-active">' . __( 'Ingredients', 'cooked' ) . '</a>';
        $markup         .= '<a href="#directions" data-nav-id="directions" class="cooked-fsm-nav-directions">' . __( 'Directions', 'cooked' ) . '</a>';
        $markup         .= '</div>';
        $markup         .= '</div>';
        return $markup;
    }

    /**
     * Checks whether the recipe content includes the fullscreen option in a [cooked-info] shortcode.
     * Looks at left, right, and include attributes.
     *
     * @param int    $recipe_id Recipe post ID.
     * @param string $content   Raw recipe layout content (shortcodes).
     * @return bool True if fullscreen is present in at least one [cooked-info] shortcode.
     */
    public static function recipe_has_fullscreen( $recipe_id, $content ) {
        $recipe_id = intval( $recipe_id );
        if ( ! $recipe_id ) {
            return false;
        }
        if ( empty( $content ) || strpos( $content, 'cooked-info' ) === false ) {
            return false;
        }
        $regex = get_shortcode_regex( [ 'cooked-info' ] );
        if ( ! preg_match_all( '/' . $regex . '/s', $content, $matches, PREG_SET_ORDER ) ) {
            return false;
        }
        $check_values = function ( $attr_string ) {
            if ( empty( $attr_string ) ) {
                return false;
            }
            $atts = shortcode_parse_atts( $attr_string );
            if ( ! is_array( $atts ) ) {
                return false;
            }
            foreach ( [ 'left', 'right', 'include' ] as $key ) {
                if ( empty( $atts[ $key ] ) ) {
                    continue;
                }
                $parts = array_map( 'trim', explode( ',', $atts[ $key ] ) );
                if ( in_array( 'fullscreen', $parts, true ) ) {
                    return true;
                }
            }
            return false;
        };
        foreach ( $matches as $m ) {
            $attr_string = isset( $m[3] ) ? $m[3] : '';
            if ( $check_values( $attr_string ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Difficulty levels, level => label (filterable).
     *
     * @return array<int, string>
     */
    public static function difficulty_levels() {
        return apply_filters(
            'cooked_difficulty_levels',
            [
				1 => __( 'Beginner', 'cooked' ),
				2 => __( 'Intermediate', 'cooked' ),
				3 => __( 'Advanced', 'cooked' ),
			]
        );
    }

    /**
     * The recipe ID for a slug (numeric slugs are returned as IDs).
     *
     * @param string|false $slug Recipe slug or ID.
     * @return int|string|false
     */
    public static function get_by_slug( $slug = false ) {
        if ( $slug ) :
            if ( ! function_exists( 'ctype_digit' ) || ( function_exists( 'ctype_digit' ) && ! ctype_digit( $slug ) ) ) :
                $recipe_query = new \WP_Query(
                    [
						'name'      => $slug,
						'post_type' => 'cp_recipe',
					]
                );
                if ( $recipe_query->have_posts() ) :
                    $recipe_query->the_post();
                    return get_the_ID();
                else :
                    return false;
                endif;
            else :
                return $slug;
            endif;
        else :
            return false;
        endif;
    }

    /**
     * Gallery sources for recipes (Cooked and supported gallery plugins).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function gallery_types() {

        $gallery_types = apply_filters(
            'cooked_gallery_types',
            [
				'cooked'    => [
					'title'          => __( 'Cooked Gallery', 'cooked' ),
					'required_class' => '',
				],
				'envira'    => [
					'title'          => __( 'Envira Gallery', 'cooked' ),
					'required_class' => 'Envira_Gallery',
				],
				'soliloquy' => [
					'title'          => __( 'Soliloquy Slider', 'cooked' ),
					'required_class' => 'Soliloquy',
				],
				'revslider' => [
					'title'          => __( 'Slider Revolution', 'cooked' ),
					'required_class' => 'RevSlider',
				],
			]
        );

        foreach ( $gallery_types as $slug => $gtype ) :

            $results = [];

            if ( $gtype['required_class'] && class_exists( $gtype['required_class'] ) ) :

                if ( 'revslider' === $slug ) :

                    $slider     = new \RevSlider();
                    $arrSliders = $slider->getArrSliders();
                    if ( ! empty( $arrSliders ) ) :
                        foreach ( $arrSliders as $slider ) :
                            $results[ $slider->getAlias() ] = $slider->getTitle();
                        endforeach;
                    endif;

                else :

                    $args          = apply_filters(
                        'cooked_gallery_type_' . $slug . '_query',
                        [
							'post_type'      => $slug,
							'post_status'    => 'publish',
							'posts_per_page' => -1,
						]
                    );
                    $gallery_query = new \WP_Query( $args );
                    if ( $gallery_query->have_posts() ) :
                        while ( $gallery_query->have_posts() ) :
                            $gallery_query->the_post();
                            $results[ get_the_ID() ] = get_the_title();
                        endwhile;
                    endif;

                endif;

                if ( ! empty( $results ) ) :
                    $gallery_types[ $slug ]['posts'] = $results;
                else :
                    unset( $gallery_types[ $slug ] );
                endif;

            elseif ( 'cooked' !== $slug ) :

					unset( $gallery_types[ $slug ] );

            endif;

        endforeach;

        wp_reset_postdata();

        return $gallery_types;
    }

    /**
     * Outputs the Metric/Imperial switcher, if enabled.
     *
     * @return void
     */
    public static function measurement_system_switcher() {
        global $_cooked_settings, $post;
        $switcher_enabled = ( isset( $_cooked_settings['advanced'] ) && in_array( 'enable_measurement_switcher', $_cooked_settings['advanced'], true ) ? true : false );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only print view flag.
        $printing = ( is_singular( 'cp_recipe' ) && isset( $_GET['print'] ) );

        if ( ! $printing && $switcher_enabled ) :
            $current       = esc_html( get_query_var( 'measurement_system', '' ) );
            $labels        = [
                ''         => __( 'Default', 'cooked' ),
                'metric'   => __( 'Metric', 'cooked' ),
                'imperial' => __( 'Imperial', 'cooked' ),
            ];
            $current_label = isset( $labels[ $current ] ) ? $labels[ $current ] : $labels[''];
            echo '<span class="cooked-measurement-system"><span class="cooked-measurement-system-icon"><i class="cooked-icon cooked-icon-gear"></i></span>';
                echo '<strong class="cooked-meta-title">' . esc_html__( 'Units', 'cooked' ) . '</strong>';
                echo '<a aria-label="' . esc_attr( $current_label ) . '" href="#">' . esc_html( $current_label ) . '</a>';
                echo '<label for="cooked-measurement-system-changer" class="screen-reader-text">' . esc_html__( 'Measurement System', 'cooked' ) . '</label>';
                echo '<select id="cooked-measurement-system-changer" name="measurement_system" class="cooked-measurement-system-changer">';
                    echo '<option value=""' . selected( $current, '', false ) . '>' . esc_html__( 'Default', 'cooked' ) . '</option>';
                    echo '<option value="metric"' . selected( $current, 'metric', false ) . '>' . esc_html__( 'Metric', 'cooked' ) . '</option>';
                    echo '<option value="imperial"' . selected( $current, 'imperial', false ) . '>' . esc_html__( 'Imperial', 'cooked' ) . '</option>';
                echo '</select>';
            echo '</span>';
        endif;
    }

    /**
     * Outputs the servings switcher.
     *
     * @param float|int|string $servings Default servings.
     * @return void
     */
    public static function serving_size_switcher( $servings ) {
        global $_cooked_settings, $post;
        $switcher_disabled = ( isset( $_cooked_settings['advanced'] ) && in_array( 'disable_servings_switcher', $_cooked_settings['advanced'], true ) ? true : false );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only print view flag.
        $printing = ( is_singular( 'cp_recipe' ) && isset( $_GET['print'] ) );

        if ( ! $printing && ! $switcher_disabled ) :
            $default  = (float) $servings;
            $servings = (float) esc_html( (string) get_query_var( 'servings', $servings ) );
            $servings = ( ! $servings ? $default : $servings );
            $counter  = 1;

            $quarter = $default / 4;
            $half    = $default / 2;
            $double  = $default * 2;
            $triple  = $default * 3;

            // Fractional servings round up for the plural form, so 1.5 reads "Servings".
            /* translators: %s: number of servings, a quarter of the default */
            $quarter_string = sprintf( esc_html( _n( 'Quarter (%s Serving)', 'Quarter (%s Servings)', (int) ceil( $quarter ), 'cooked' ) ), $quarter );

            /* translators: %s: number of servings, half of the default */
            $half_string = sprintf( esc_html( _n( 'Half (%s Serving)', 'Half (%s Servings)', (int) ceil( $half ), 'cooked' ) ), $half );

            /* translators: %s: default number of servings */
            $default_string = sprintf( esc_html( _n( 'Default (%s Serving)', 'Default (%s Servings)', (int) ceil( $default ), 'cooked' ) ), $default );

            /* translators: %s: number of servings, double the default */
            $double_string = sprintf( __( 'Double (%s Servings)', 'cooked' ), $double );

            /* translators: %s: number of servings, triple the default */
            $triple_string = sprintf( __( 'Triple (%s Servings)', 'cooked' ), $triple );

            $servings_array = apply_filters(
                'cooked_servings_switcher_options',
                [
					'quarter' => [
						'name'  => $quarter_string,
						'value' => $quarter,
					],
					'half'    => [
						'name'  => $half_string,
						'value' => $half,
					],
					'default' => [
						'name'  => $default_string,
						'value' => $default,
					],
					'double'  => [
						'name'  => $double_string,
						'value' => $double,
					],
					'triple'  => [
						'name'  => $triple_string,
						'value' => $triple,
					],
				],
				$quarter,
				$half,
				$default,
				$double,
				$triple
            );
        else :
            $servings_array = [];
        endif;

        echo '<span class="cooked-servings"><span class="cooked-servings-icon"><i class="cooked-icon cooked-icon-recipe-icon"></i></span>';
        echo '<strong class="cooked-meta-title">' . esc_html__( 'Yields', 'cooked' ) . '</strong>';
		if ( ! $printing && ! $switcher_disabled ) :

			/* translators: singular and plural "serving" sizes */
			$servings_string = sprintf( esc_html( _n( '%s Serving', '%s Servings', (int) ceil( (float) $servings ), 'cooked' ) ), $servings );

			// Desktop gets Tom Select (cooked-functions.js); touch devices keep the native picker.
			wp_enqueue_style( 'cooked-tom-select' );
			wp_enqueue_script( 'cooked-tom-select' );

			echo '<a aria-label="' . esc_attr( $servings_string ) . '" href="#">' . esc_html( $servings_string ) . '</a>';
			echo '<label for="cooked-servings-changer" class="screen-reader-text">' . esc_html__( 'Servings', 'cooked' ) . '</label>';
			// Tom Select points the label at its own control, so the select keeps its name here too.
			echo '<select id="cooked-servings-changer" name="servings" class="cooked-servings-changer" aria-label="' . esc_attr__( 'Servings', 'cooked' ) . '">';
			foreach ( $servings_array as $stype ) :
				echo '<option value="' . esc_attr( $stype['value'] ) . '"' . ( (float) $stype['value'] === (float) $servings ? ' selected' : '' ) . '>' . esc_html( $stype['name'] ) . '</option>';
				endforeach;
                echo '</select>';
            else :
                /* translators: singular and plural "serving" sizes */
                echo '<span>' . esc_html( sprintf( _n( '%s Serving', '%s Servings', (int) ceil( (float) $servings ), 'cooked' ), $servings ) ) . '</span>';
            endif;
			echo '</span>';
    }

    /**
     * An ingredient's substitutes, in order: its `substitutions` list, or for recipes saved
     * before 2.0 the single `sub_amount` / `sub_measurement` / `sub_name`. Entries without a
     * name are skipped.
     *
     * @since 2.0.0
     * @param array<string, mixed> $ing Ingredient.
     * @return array<int, array{amount: string, measurement: string, name: string}>
     */
    public static function ingredient_substitutions( $ing ) {
        if ( isset( $ing['substitutions'] ) && is_array( $ing['substitutions'] ) ) {
            $entries = $ing['substitutions'];
        } else {
            $entries = [
                [
                    'amount'      => isset( $ing['sub_amount'] ) ? $ing['sub_amount'] : '',
                    'measurement' => isset( $ing['sub_measurement'] ) ? $ing['sub_measurement'] : '',
                    'name'        => isset( $ing['sub_name'] ) ? $ing['sub_name'] : '',
                ],
            ];
        }

        $substitutions = [];
        foreach ( $entries as $entry ) {
            if ( ! is_array( $entry ) || ! isset( $entry['name'] ) || ! is_scalar( $entry['name'] ) || '' === trim( (string) $entry['name'] ) ) {
                continue;
            }
            $substitutions[] = [
                'amount'      => isset( $entry['amount'] ) && is_scalar( $entry['amount'] ) ? (string) $entry['amount'] : '',
                'measurement' => isset( $entry['measurement'] ) && is_scalar( $entry['measurement'] ) ? (string) $entry['measurement'] : '',
                'name'        => (string) $entry['name'],
            ];
        }

        return $substitutions;
    }

    /**
     * Sets an ingredient's substitutes: the `substitutions` list, with the first also in the
     * pre-2.0 `sub_amount` / `sub_measurement` / `sub_name` fields that add-ons may read.
     *
     * @since 2.0.0
     * @param array<string, mixed>                                                 $ing           Ingredient.
     * @param array<int, array{amount: string, measurement: string, name: string}> $substitutions Substitutes.
     * @return array<string, mixed>
     */
    public static function with_substitutions( $ing, $substitutions ) {
        $substitutions = array_values( $substitutions );
        $first         = isset( $substitutions[0] ) ? $substitutions[0] : [
            'amount'      => '',
            'measurement' => '',
            'name'        => '',
        ];

        $ing['substitutions']   = $substitutions;
        $ing['sub_amount']      = $first['amount'];
        $ing['sub_measurement'] = $first['measurement'];
        $ing['sub_name']        = $first['name'];

        return $ing;
    }

    /**
     * Outputs (or returns, as plain text) one ingredient or section heading.
     *
     * @param array<string, mixed> $ing        Ingredient.
     * @param bool|string          $checkboxes Show a checkbox.
     * @param bool                 $plain_text Return plain text instead of printing HTML.
     * @return string|void
     */
    public static function single_ingredient( $ing, $checkboxes = true, $plain_text = false ) {
        global $recipe_settings;

        $Cooked_Measurements = new \Cooked_Measurements();
        $measurements        = $Cooked_Measurements->get();

        ob_start();

        if ( isset( $ing['section_heading_name'] ) && $ing['section_heading_name'] ) {

            if ( $plain_text ) {
                ob_end_clean(); // Plain text is returned, not buffered.
                return $ing['section_heading_name'];
            } else {
                $valid_elements = [ 'div', 'h2', 'h3', 'h4', 'h5', 'h6' ];
                global $_cooked_settings;
                $default_element = isset( $_cooked_settings['section_heading_default_html_tag'] ) ? $_cooked_settings['section_heading_default_html_tag'] : 'div';

                $element = ( isset( $ing['section_heading_element'] ) && in_array( $ing['section_heading_element'], $valid_elements, true ) )
                    ? ( 'div' === $ing['section_heading_element'] ? $default_element : $ing['section_heading_element'] )
                    : $default_element;

                echo '<' . tag_escape( $element ) . ' class="cooked-single-ingredient cooked-heading">' . esc_html( $ing['section_heading_name'] ) . '</' . tag_escape( $element ) . '>';
            }
		} elseif ( isset( $ing['name'] ) && $ing['name'] ) {

            $default_serving_size = ( isset( $recipe_settings['nutrition']['servings'] ) && $recipe_settings['nutrition']['servings'] ? $recipe_settings['nutrition']['servings'] : 1 );
            $multiplier           = (float) esc_html( get_query_var( 'servings', $recipe_settings['nutrition']['servings'] ) );
            $multiplier           = ( ! $multiplier ? $default_serving_size : $multiplier );

            if ( ! $multiplier || $multiplier === (float) $default_serving_size ) {
                $multiplier = 1;
			} else {
                $multiplier = $multiplier / (float) $default_serving_size;
			}

			if ( 1 === $multiplier ) {
				$amount       = ( isset( $ing['amount'] ) && $ing['amount'] ? esc_html( $ing['amount'] ) : false );
				$amount       = (string) $Cooked_Measurements->cleanup_amount( $amount );
				$format       = ( strpos( $amount, '/' ) === false ? ( strpos( $amount, '.' ) !== false || strpos( $amount, ',' ) !== false ? 'decimal' : 'fraction' ) : 'fraction' );
				$float_amount = $Cooked_Measurements->calculate( $amount, 'decimal' );
				$amount       = $Cooked_Measurements->format_amount( $float_amount, $format );
			} else {
				$amount       = ( isset( $ing['amount'] ) && $ing['amount'] ? esc_html( $ing['amount'] ) : false );
				$amount       = (string) $Cooked_Measurements->cleanup_amount( $amount );
				$format       = ( strpos( $amount, '/' ) === false ? ( strpos( $amount, '.' ) !== false || strpos( $amount, ',' ) !== false ? 'decimal' : 'fraction' ) : 'fraction' );
				$float_amount = $Cooked_Measurements->calculate( $amount, 'decimal' );

				if ( $float_amount ) {
					$float_amount = (float) $float_amount * $multiplier;
					$amount       = $Cooked_Measurements->format_amount( $float_amount, $format );
				}
			}

            $measurement_key = ( isset( $ing['measurement'] ) && $ing['measurement'] ? esc_html( $ing['measurement'] ) : false );

            $measurement_system = get_query_var( 'measurement_system', '' );
			if ( $measurement_system && $measurement_key && $float_amount && isset( $measurements[ $measurement_key ]['system'] ) ) {
				$source_system = $measurements[ $measurement_key ]['system'];
				if ( $source_system && $source_system !== $measurement_system ) {
					$target = \Cooked_Unit_Converter::get_target_unit( $measurement_key );
					$result = $target ? \Cooked_Unit_Converter::convert( (float) $float_amount, $measurement_key, $target ) : null;
					if ( $result ) {
						$float_amount    = $result['amount'];
						$measurement_key = $result['unit'];
						if ( $float_amount > 10 ) {
							$format = 'decimal';
						}
						$amount = $Cooked_Measurements->format_amount( $float_amount, $format );
					}
				}
			}

            $measurement = ( $measurement_key && $float_amount && isset( $measurements[ $measurement_key ] ) ? $Cooked_Measurements->singular_plural( $measurements[ $measurement_key ]['singular_abbr'], $measurements[ $measurement_key ]['plural_abbr'], (float) $float_amount ) : false );

            $name = ( ! empty( $ing['name'] ) ? apply_filters( 'cooked_ingredient_name', wp_kses_post( $ing['name'] ), $ing ) : false );

            // Substitutes, each scaled and converted like the ingredient itself.
            $substitutions = [];
            foreach ( self::ingredient_substitutions( $ing ) as $substitution ) {
                $sub_name         = wp_kses_post( $substitution['name'] );
                $sub_amount       = $substitution['amount'] ? esc_html( $substitution['amount'] ) : false;
                $sub_amount       = (string) $Cooked_Measurements->cleanup_amount( $sub_amount );
                $sub_format       = ( strpos( $sub_amount, '/' ) === false ? ( strpos( $sub_amount, '.' ) !== false || strpos( $sub_amount, ',' ) !== false ? 'decimal' : 'fraction' ) : 'fraction' );
                $sub_float_amount = $Cooked_Measurements->calculate( $sub_amount, 'decimal' );

                if ( 1 === $multiplier ) {
                    $sub_amount = $Cooked_Measurements->format_amount( $sub_float_amount, $sub_format );
                } elseif ( $sub_float_amount ) {
                    $sub_float_amount = (float) $sub_float_amount * $multiplier;
                    $sub_amount       = $Cooked_Measurements->format_amount( $sub_float_amount, $sub_format );
                }

                $sub_measurement_key = $substitution['measurement'] ? esc_html( $substitution['measurement'] ) : false;

                if ( $measurement_system && $sub_measurement_key && $sub_float_amount && isset( $measurements[ $sub_measurement_key ]['system'] ) ) {
                    $sub_source_system = $measurements[ $sub_measurement_key ]['system'];
                    if ( $sub_source_system && $sub_source_system !== $measurement_system ) {
                        $sub_target = \Cooked_Unit_Converter::get_target_unit( $sub_measurement_key );
                        $sub_result = $sub_target ? \Cooked_Unit_Converter::convert( (float) $sub_float_amount, $sub_measurement_key, $sub_target ) : null;
                        if ( $sub_result ) {
                            $sub_float_amount    = $sub_result['amount'];
                            $sub_measurement_key = $sub_result['unit'];
                            if ( $sub_float_amount > 10 ) {
                                $sub_format = 'decimal';
                            }
                            $sub_amount = $Cooked_Measurements->format_amount( $sub_float_amount, $sub_format );
                        }
                    }
                }

                $substitutions[] = [
                    'name'         => $sub_name,
                    'amount'       => $sub_amount,
                    'float_amount' => $sub_float_amount,
                    'measurement'  => ( $sub_measurement_key && $sub_float_amount && isset( $measurements[ $sub_measurement_key ] ) ? $Cooked_Measurements->singular_plural( $measurements[ $sub_measurement_key ]['singular_abbr'], $measurements[ $sub_measurement_key ]['plural_abbr'], (float) $sub_float_amount ) : false ),
                ];
            }

			if ( $plain_text ) {
				$output = ( $amount ? $amount . ' ' : '' ) . ( $measurement ? $measurement . ' ' : '' ) . ( $name ? $name : '' );
				if ( $substitutions ) {
					$alternatives = [];
					foreach ( $substitutions as $sub ) {
						$alternatives[] = __( 'or', 'cooked' ) . ' ' . ( $sub['amount'] ? $sub['amount'] . ' ' : '' ) . ( $sub['measurement'] ? $sub['measurement'] . ' ' : '' ) . $sub['name'];
					}
					$output .= ' (' . implode( ', ', $alternatives ) . ')';
				}
				ob_end_clean(); // Plain text is returned, not buffered.
				return $output;
			} else {
				echo '<div itemprop="recipeIngredient" class="cooked-single-ingredient cooked-ingredient' . ( ! $checkboxes ? ' cooked-ing-no-checkbox' : '' ) . '">';
					echo ( $checkboxes ? '<span class="cooked-ingredient-checkbox"></span>' : '' );
					do_action( 'cooked_ingredient_after_checkbox', $ing );
					echo ( $amount ? '<span class="cooked-ing-amount" data-decimal="' . esc_attr( (string) $float_amount ) . '">' . wp_kses_post( (string) $amount ) . '</span> <span class="cooked-ing-measurement">' . wp_kses_post( (string) $measurement ) . '</span> ' : '' );
					do_action( 'cooked_ingredient_after_amount', $ing );
					echo ( $name ? '<span class="cooked-ing-name">' . wp_kses_post( $name ) . '</span>' : '' );
					do_action( 'cooked_ingredient_after_name', $ing );

				foreach ( $substitutions as $sub ) {
					echo '<span class="cooked-ingredient-substitution">';
						echo ' <span class="cooked-ing-sub-label">' . esc_html__( 'or', 'cooked' ) . '</span> ';
						echo ( $sub['amount'] ? '<span class="cooked-ing-amount" data-decimal="' . esc_attr( (string) $sub['float_amount'] ) . '">' . wp_kses_post( (string) $sub['amount'] ) . '</span> <span class="cooked-ing-measurement">' . wp_kses_post( (string) $sub['measurement'] ) . '</span> ' : '' );
						echo '<span class="cooked-ing-name">' . wp_kses_post( $sub['name'] ) . '</span>';
					echo '</span>';
				}
				echo '</div>';
			}
        }

        $ing_html = ob_get_clean();
        echo wp_kses_post( apply_filters( 'cooked_single_ingredient_html', $ing_html, $ing, $checkboxes, $plain_text ) );
    }

    /**
     * Outputs (or returns, as plain text) one direction step or section heading.
     *
     * @param array<string, mixed>       $dir        Direction.
     * @param int|false                  $number     Step number.
     * @param bool                       $plain_text Return plain text instead of printing HTML.
     * @param int|false                  $step       Step index, for anchors.
     * @param array<string, mixed>|false $atts       Shortcode attributes.
     * @return string|void
     */
    public static function single_direction( $dir, $number = false, $plain_text = false, $step = false, $atts = false ) {
        global $recipe_settings;

        if ( isset( $dir['section_heading_name'] ) && $dir['section_heading_name'] ) {

            if ( $plain_text ) {
                return $dir['section_heading_name'];
            } else {
                $valid_elements = [ 'div', 'h2', 'h3', 'h4', 'h5', 'h6' ];
                global $_cooked_settings;
                $default_element = isset( $_cooked_settings['section_heading_default_html_tag'] ) ? $_cooked_settings['section_heading_default_html_tag'] : 'div';

                $element = ( isset( $dir['section_heading_element'] ) && in_array( $dir['section_heading_element'], $valid_elements, true ) )
                    ? ( 'div' === $dir['section_heading_element'] ? $default_element : $dir['section_heading_element'] )
                    : $default_element;

                echo '<' . tag_escape( $element ) . ' class="cooked-single-direction cooked-heading">' . esc_html( $dir['section_heading_name'] ) . '</' . tag_escape( $element ) . '>';
            }
		} elseif ( ! empty( $dir['content'] ) || ! empty( $dir['image'] ) || ! empty( $dir['video'] ) ) {

            $dir_image_size = apply_filters( 'cooked_direction_image_size', 'large' );
            $image          = ! empty( $dir['image'] ) ? wp_get_attachment_image( $dir['image'], $dir_image_size, false, [ 'title' => esc_attr( get_the_title( $dir['image'] ) ) ] ) : '';
            $content        = ! empty( $dir['content'] ) ? \Cooked_Recipes::format_content( $dir['content'] ) : '';
            $video          = ! empty( $dir['video'] ) ? wp_get_attachment_url( $dir['video'] ) : '';

            $image = apply_filters( 'cooked_direction_image_html', $image, $atts );

            if ( $plain_text ) {
                return $content;
			} else {
                /* translators: singular and plural "steps" */
                $step_string = sprintf( __( 'Step %d', 'cooked' ), $step );

                echo '<div id="cooked-single-direction-step-' . esc_attr( (string) $number ) . '" class="cooked-single-direction cooked-direction' . ( $image ? ' cooked-direction-has-image' : '' ) . ( $number ? ' cooked-direction-has-number' . ( $number > 9 ? '-wide' : '' ) : '' ) . '"' . ( $step ? ' data-step="' . esc_attr( $step_string ) . '"' : '' ) . '>';
				echo $number ? '<span class="cooked-direction-number">' . esc_html( $number ) . '</span>' : '';
				echo '<div class="cooked-dir-content">' . do_shortcode( $content ) . ( $image ? wp_kses_post( wpautop( $image ) ) : '' ) . ( $video ? '<video class="cooked-direction-video" src="' . esc_url( $video ) . '" controls preload="metadata" playsinline></video>' : '' ) . '</div>';
                echo '</div>';
			}
        }
    }

    /**
     * Formats recipe text as safe HTML paragraphs.
     *
     * @param string $content Text.
     * @return string
     */
    public static function format_content( $content ) {
        return wpautop( wp_kses_post( html_entity_decode( $content ) ) );
    }

    /**
     * The difficulty level label.
     *
     * @param int|string $level Level.
     * @return string
     */
    public static function difficulty_level( $level ) {
        $level_names = self::difficulty_levels();
        $level_text  = isset( $level_names[ $level ] ) ? $level_names[ $level ] : false;
        return $level_text ? '<span class="cooked-difficulty-level-' . esc_attr( $level ) . '">' . wp_kses_post( $level_text ) . '</span>' : '';
    }

    /**
     * The recipe search box.
     *
     * @param array<string, mixed>|false $options Search options.
     * @return string|false
     */
    public static function recipe_search_box( $options = false ) {
        global $_cooked_settings, $recipe_args, $tax_col_count, $active_taxonomy;

        $tax_col_count          = 0;
        $selected_terms         = [];
        $selected_category_ids  = [];
        $selected_filter_count  = 0;
        $taxonomy_search_fields = '';
        $options                = is_array( $options ) ? $options : [];

        if ( isset( $recipe_args['tax_query'] ) && is_array( $recipe_args['tax_query'] ) ) :
            foreach ( $recipe_args['tax_query'] as $query ) :
                // Only process inclusion queries (default operator or explicit 'IN')
                // Skip exclusion queries ('NOT IN') and other complex operators.
                $operator = isset( $query['operator'] ) ? $query['operator'] : 'IN';

                if ( 'IN' !== $operator || empty( $query['taxonomy'] ) || empty( $query['terms'] ) ) {
                    continue;
                }

                $terms = self::taxonomy_query_terms( $query['terms'] );

                if ( empty( $terms ) ) {
                    continue;
                }

                $field        = isset( $query['field'] ) ? $query['field'] : self::taxonomy_query_field( $terms );
                $term_objects = self::resolve_taxonomy_terms( $query['taxonomy'], $terms, $field );

                if ( empty( $term_objects ) ) {
                    continue;
                }

                $selected_terms[ $query['taxonomy'] ] = $term_objects;
                $selected_filter_count               += count( $term_objects );

                if ( 'cp_recipe_category' === $query['taxonomy'] ) {
                    $selected_category_ids = array_merge( $selected_category_ids, wp_list_pluck( $term_objects, 'term_id' ) );
                }

                if ( ! isset( $active_taxonomy ) ) {
                    $active_taxonomy = $term_objects[0]->name;
                }
            endforeach;

            $selected_category_ids = array_values( array_unique( array_map( 'absint', $selected_category_ids ) ) );
        endif;

        $page_id       = (int) \Cooked_Multilingual::get_browse_page_id();
        $page_id       = $page_id ? $page_id : (int) get_the_ID();
        $form_redirect = $page_id ? get_permalink( $page_id ) : false;
        $form_redirect = $form_redirect ? $form_redirect : home_url( '/' );

        $total_taxonomies = 0;
        $browse_html      = '';
        $inline_browse    = ! empty( $options['inline_browse'] );

        if ( ! empty( $_cooked_settings['recipe_taxonomies'] ) ) :

            ob_start();

            do_action( 'cooked_before_search_filter_columns' );

            if ( in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies'], true ) ) :
                $category_options = self::category_filter_options( 0, $selected_category_ids, $total_taxonomies );

                if ( '' !== $category_options ) :
                    echo '<span class="cooked-tax-column cooked-tax-column-categories">';
                        echo '<div class="cooked-tax-column-head">';
                            echo '<span class="cooked-tax-column-title">' . esc_html__( 'Categories', 'cooked' ) . '</span>';
                            echo '<a class="cooked-filter-all' . ( empty( $selected_category_ids ) ? ' cooked-filter-all-hidden' : '' ) . '" href="' . esc_url( $form_redirect ) . '">' . esc_html__( 'Clear filters', 'cooked' ) . '</a>';
                        echo '</div>';
                        echo '<div class="cooked-tax-scrollable">';
                            echo '<div class="cooked-filter-options">';
                                echo $category_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while the options are built.
                            echo '</div>';
                        echo '</div>';
                    echo '</span>';
                    ++$tax_col_count;
                endif;
            endif;

            do_action( 'cooked_after_search_filter_columns' );

            $browse_html = ob_get_clean();

            if ( ! is_string( $browse_html ) ) {
                $browse_html = '';
            }

        endif;

        $browse_allowed_html          = wp_kses_allowed_html( 'post' );
        $browse_allowed_html['input'] = array_fill_keys(
            [ 'type', 'name', 'value', 'checked', 'class', 'id', 'disabled', 'aria-label', 'aria-labelledby' ],
            true
        );

        $taxonomy_search_fields = false;
        $browse_label           = __( 'Browse', 'cooked' );

        if ( 1 === $selected_filter_count && ! empty( $selected_terms ) ) {
            $first_terms  = reset( $selected_terms );
            $browse_label = $first_terms[0]->name;
        } elseif ( $selected_filter_count > 1 ) {
            $browse_label = sprintf(
                /* translators: %s: number of selected recipe filters. */
                _n( '%s filter', '%s filters', $selected_filter_count, 'cooked' ),
                number_format_i18n( $selected_filter_count )
            );
        }

        $browse_aria_label = $selected_filter_count
            ? sprintf(
                /* translators: 1: visible browse label, 2: number of selected recipe filters. */
                __( '%1$s, browse recipe filters, %2$s selected', 'cooked' ),
                $browse_label,
                number_format_i18n( $selected_filter_count )
            )
            : __( 'Browse recipe filters', 'cooked' );

        if ( $total_taxonomies && ! $inline_browse ) :
            $browse_panel_id = wp_unique_id( 'cooked-browse-panel-' );

            $taxonomy_search_fields  = '<div class="cooked-field-wrap cooked-field-wrap-select' . ( $selected_filter_count ? ' cooked-taxonomy-selected' : '' ) . '">';
            $taxonomy_search_fields .= '<button type="button" class="cooked-browse-select" aria-expanded="false" aria-controls="' . esc_attr( $browse_panel_id ) . '" aria-label="' . esc_attr( $browse_aria_label ) . '">';
            $taxonomy_search_fields .= '<span class="cooked-field-title">' . esc_html( $browse_label ) . '</span>';
            $taxonomy_search_fields .= '</button>';
            $taxonomy_search_fields .= '<div class="cooked-browse-select-block" id="' . esc_attr( $browse_panel_id ) . '">' . wp_kses( $browse_html, $browse_allowed_html ) . '</div>';
            $taxonomy_search_fields .= '</div>';
        endif;

        $show_browse                  = empty( $options['hide_browse'] );
        $category_checkboxes_rendered = ( $show_browse && $taxonomy_search_fields ) || ( $inline_browse && $total_taxonomies );

        $cooked_search_s = get_query_var( 'cooked_search_s', '' );
        $cooked_search_s = urldecode( $cooked_search_s );
        $cooked_search_s = \Cooked_Functions::sanitize_text_field( $cooked_search_s );

        ob_start();

        echo '<section class="cooked-recipe-search cooked-clearfix' . ( ! empty( $options['compact'] ) ? ' cooked-search-compact' : '' ) . ( ! empty( $options['hide_sorting'] ) ? ' cooked-search-no-sorting' : '' ) . ( ! $show_browse ? ' cooked-search-no-browse' : '' ) . '">';

            echo '<form action="' . esc_url( $form_redirect ) . '" method="get">';

                echo '<input type="hidden" name="page_id" value="' . intval( $page_id ) . '">';

        if ( isset( $recipe_args['tax_query'] ) && is_array( $recipe_args['tax_query'] ) ) :
            foreach ( $recipe_args['tax_query'] as $query ) :
                if ( empty( $query['taxonomy'] ) || empty( $query['terms'] ) ) {
                    continue;
                }

                $operator = isset( $query['operator'] ) ? $query['operator'] : 'IN';

                if ( 'IN' !== $operator || ( 'cp_recipe_category' === $query['taxonomy'] && $category_checkboxes_rendered ) ) {
                    continue;
                }

                $terms = self::taxonomy_query_terms( $query['terms'] );

                if ( ! empty( $terms ) ) {
                    echo '<input type="hidden" name="' . esc_attr( $query['taxonomy'] ) . '" value="' . esc_attr( implode( ',', $terms ) ) . '">';
                }
            endforeach;
        endif;

                echo '<div class="cooked-fields-wrap cooked-' . esc_attr( $tax_col_count ) . '-search-fields">';

                    echo $show_browse && $taxonomy_search_fields ? $taxonomy_search_fields : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while the markup is built above.

                    echo '<div class="cooked-search-field">';

                        echo '<input aria-label="' . esc_attr__( 'Find a recipe...', 'cooked' ) . '" class="cooked-browse-search" type="text" name="cooked_search_s" value="' . ( ! empty( $cooked_search_s ) ? esc_attr( $cooked_search_s ) : '' ) . '" placeholder="' . esc_attr__( 'Find a recipe...', 'cooked' ) . '" />';

                        echo '<button type="submit" aria-label="' . esc_attr__( 'Search', 'cooked' ) . '" class="cooked-browse-search-button"><i class="cooked-icon cooked-icon-search" aria-hidden="true"></i></button>';

                    echo '</div>';

                echo '</div>';

		if ( isset( $recipe_args['orderby'] ) && is_array( $recipe_args['orderby'] ) ) :
			$sorting_type = key( $recipe_args['orderby'] ) . '_' . current( $recipe_args['orderby'] );
                else :
                    $sorting_type = ( isset( $recipe_args['orderby'] ) && isset( $recipe_args['order'] ) ? $recipe_args['orderby'] . '_' . $recipe_args['order'] : 'date_desc' );
                endif;

                $sorting_types = apply_filters(
                    'cooked_browse_sorting_types',
                    [
						'date_desc'  => [
							'slug' => 'date_desc',
							'name' => __( 'Newest first', 'cooked' ),
						],
						'date_asc'   => [
							'slug' => 'date_asc',
							'name' => __( 'Oldest first', 'cooked' ),
						],
						'title_asc'  => [
							'slug' => 'title_asc',
							'name' => __( 'Alphabetical (A-Z)', 'cooked' ),
						],
						'title_desc' => [
							'slug' => 'title_desc',
							'name' => __( 'Alphabetical (Z-A)', 'cooked' ),
						],
					],
					$sorting_type
                );

		if ( ! $options['hide_sorting'] ) :

			echo '<span class="cooked-sortby-wrap"><select class="cooked-sortby-select" name="cooked_browse_sort_by" aria-label="' . esc_attr__( 'Sort recipes by', 'cooked' ) . '">';
			foreach ( $sorting_types as $value => $type ) :
					echo '<option value="' . esc_attr( $value ) . '"' . ( $sorting_type === $type['slug'] ? ' selected' : '' ) . '>' . esc_attr( $type['name'] ) . '</option>';
				endforeach;
                    echo '</select></span>';

                endif;

        if ( $inline_browse && $total_taxonomies ) :
            echo '<div class="cooked-browse-select-inline-block">';
                echo wp_kses( $browse_html, $browse_allowed_html );
            echo '</div>';
        endif;

            echo '</form>';

            echo '<p class="cooked-browse-status" role="status" aria-live="polite"></p>';

        echo '</section>';

        return ob_get_clean();
    }

    /**
     * The form field name used to filter by a taxonomy.
     *
     * Categories use their own field so an array value is not parsed as a
     * taxonomy archive query by WordPress.
     *
     * @param string $taxonomy Taxonomy.
     * @return string
     */
    private static function taxonomy_filter_field( $taxonomy ) {
        return 'cp_recipe_category' === $taxonomy ? 'cooked_category_filter' : $taxonomy;
    }

    /**
     * Renders the category checkbox options for the browse filter.
     *
     * @param int             $parent_id Parent term ID, or 0 for the top level.
     * @param array<int, int> $selected  Selected term IDs.
     * @param int             $total     Running count of rendered terms.
     * @param bool            $is_child  Whether these options are nested.
     * @return string
     */
    private static function category_filter_options( $parent_id, $selected, &$total, $is_child = false ) {
        $terms_array = \Cooked_Settings::terms_array(
            'cp_recipe_category',
            false,
            false,
            true,
            ! $is_child,
            $is_child ? $parent_id : false
        );

        if ( empty( $terms_array ) ) {
            return '';
        }

        $html = $is_child ? '<div class="cooked-filter-sub-options">' : '';

        foreach ( $terms_array as $term_id => $term_name ) {
            $term_id = (int) $term_id;
            if ( ! $term_id ) {
                continue;
            }

            $term = get_term( $term_id );

            if ( empty( $term ) || is_wp_error( $term ) ) {
                continue;
            }

            $name = apply_filters( 'cooked_term_name', $term_name, $term->term_id, $term->taxonomy );

            $html .= '<label class="cooked-filter-option">';
            $html .= '<input type="checkbox" name="' . esc_attr( self::taxonomy_filter_field( 'cp_recipe_category' ) ) . '[]" value="' . esc_attr( $term->slug ) . '"' . checked( in_array( (int) $term->term_id, $selected, true ), true, false ) . '>';
            $html .= '<span class="cooked-filter-option-label">' . wp_kses_post( $name ) . '</span>';
            $html .= '</label>';

            ++$total;

            $children = self::category_filter_options( $term->term_id, $selected, $total, true );

            if ( '' !== $children ) {
                $html .= $children;
            }
        }

        if ( $is_child ) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Renders the active taxonomy filters as removable chips.
     *
     * @param array<string, mixed> $recipe_args Recipe query arguments.
     * @param array<string, mixed> $atts        Recipe list attributes.
     * @return string
     */
    public static function filter_chips( $recipe_args, $atts ) {
        if ( empty( $recipe_args['tax_query'] ) || ! isset( $atts['search'] ) || 'true' !== (string) $atts['search'] ) {
            return '';
        }

        $entries = [];

        foreach ( $recipe_args['tax_query'] as $query ) {
            if ( ! is_array( $query ) || empty( $query['taxonomy'] ) || empty( $query['terms'] ) ) {
                continue;
            }

            $operator = isset( $query['operator'] ) ? $query['operator'] : 'IN';

            if ( 'IN' !== $operator ) {
                continue;
            }

            $terms = self::taxonomy_query_terms( $query['terms'] );

            if ( empty( $terms ) ) {
                continue;
            }

            $field   = isset( $query['field'] ) ? $query['field'] : self::taxonomy_query_field( $terms );
            $objects = self::resolve_taxonomy_terms( $query['taxonomy'], $terms, $field );

            if ( ! empty( $objects ) ) {
                $entries[] = [
                    'taxonomy' => $query['taxonomy'],
                    'terms'    => $objects,
                ];
            }
        }

        if ( empty( $entries ) ) {
            return '';
        }

        $page_id  = (int) \Cooked_Multilingual::get_browse_page_id();
        $page_id  = $page_id ? $page_id : (int) get_the_ID();
        $base_url = get_permalink( $page_id );

        if ( ! $base_url ) {
            return '';
        }

        $search = \Cooked_Functions::sanitize_text_field( urldecode( (string) get_query_var( 'cooked_search_s', '' ) ) );
        $sort   = sanitize_key( get_query_var( 'cooked_browse_sort_by', '' ) );
        $author = sanitize_key( get_query_var( 'recipe_author', '' ) );
        $chips  = '';

        foreach ( $entries as $entry ) {
            foreach ( $entry['terms'] as $term ) {
                $query_args = [];

                foreach ( $entries as $other ) {
                    $slugs = [];

                    foreach ( $other['terms'] as $other_term ) {
                        if ( $other['taxonomy'] === $entry['taxonomy'] && $other_term->term_id === $term->term_id ) {
                            continue;
                        }

                        $slugs[] = $other_term->slug;
                    }

                    if ( ! empty( $slugs ) ) {
                        $query_args[ self::taxonomy_filter_field( $other['taxonomy'] ) ] = implode( ',', $slugs );
                    }
                }

                if ( $search ) {
                    $query_args['cooked_search_s'] = $search;
                }

                if ( $sort ) {
                    $query_args['cooked_browse_sort_by'] = $sort;
                }

                if ( $author ) {
                    $query_args['recipe_author'] = $author;
                }

                $remove_url = add_query_arg( $query_args, $base_url );
                $name       = apply_filters( 'cooked_term_name', $term->name, $term->term_id, $term->taxonomy );
                $aria_label = sprintf(
                    /* translators: %s: recipe filter term name. */
                    __( 'Remove %s filter', 'cooked' ),
                    wp_strip_all_tags( $name )
                );

                $chips .= '<a class="cooked-filter-chip" href="' . esc_url( $remove_url ) . '" data-cooked-filter-chip data-taxonomy="' . esc_attr( $entry['taxonomy'] ) . '" data-field="' . esc_attr( self::taxonomy_filter_field( $entry['taxonomy'] ) ) . '" data-term="' . esc_attr( $term->slug ) . '" aria-label="' . esc_attr( $aria_label ) . '">';
                $chips .= '<span class="cooked-filter-chip-label">' . wp_kses_post( $name ) . '</span>';
                $chips .= '<i class="cooked-icon cooked-icon-close" aria-hidden="true"></i>';
                $chips .= '</a>';
            }
        }

        return '<div class="cooked-filter-chips">' . $chips . '</div>';
    }

    /**
     * Replaces single recipe content with the recipe template.
     *
     * @param string $content Post content.
     * @return string
     */
    public function recipe_template( $content ) {
        global $wp_query, $post, $_cooked_content_unfiltered;

        if ( post_password_required() ) :
            return $content;
        endif;

        if ( is_singular( 'cp_recipe' ) && is_main_query() && ! $_cooked_content_unfiltered ) :
            ob_start();

            include COOKED_DIR . 'templates/front/recipe.php';

            $recipe_content = ob_get_clean();
            global $cooked_recipe_layout_content;
            $layout_content = isset( $cooked_recipe_layout_content ) ? $cooked_recipe_layout_content : null;
            return shortcode_unautop( apply_filters( 'cooked_recipe_content_filter', $recipe_content, $content, $post->ID, $layout_content ) );

        endif;

        return $content;
    }

    /**
     * Cooked Classic 2.x Backwards Compatibility
     *
     * @since 1.0.0
     */

    /**
     * Get and return the Cooked 2.x Classic recipe meta information.
     *
     * @param int $post_id Recipe ID.
     * @return array<string, mixed>
     */
    public static function get_c2_recipe_meta( $post_id ) {
        $recipe_meta     = [];
        $revised_array   = [];
        $recipe_cs2_meta = get_post_meta( $post_id );

        if ( isset( $recipe_cs2_meta['_cp_recipe_ingredients'] ) ) :

            foreach ( $recipe_cs2_meta as $key => $content ) :
                $revised_array[ $key ] = $content[0];
            endforeach;

            $recipe_cs2_meta = $revised_array;

            $recipe_meta['_cp_recipe_title']                 = get_the_title( $post_id );
            $recipe_meta['_cp_recipe_ingredients']           = isset( $recipe_cs2_meta['_cp_recipe_ingredients'] ) ? $recipe_cs2_meta['_cp_recipe_ingredients'] : false;
            $recipe_meta['_cp_recipe_detailed_ingredients']  = isset( $recipe_cs2_meta['_cp_recipe_detailed_ingredients'] ) ? $recipe_cs2_meta['_cp_recipe_detailed_ingredients'] : false;
            $recipe_meta['_cp_recipe_directions']            = isset( $recipe_cs2_meta['_cp_recipe_directions'] ) ? $recipe_cs2_meta['_cp_recipe_directions'] : false;
            $recipe_meta['_cp_recipe_detailed_directions']   = isset( $recipe_cs2_meta['_cp_recipe_detailed_directions'] ) ? $recipe_cs2_meta['_cp_recipe_detailed_directions'] : false;
            $recipe_meta['_cp_recipe_external_video']        = isset( $recipe_cs2_meta['_cp_recipe_external_video'] ) ? $recipe_cs2_meta['_cp_recipe_external_video'] : false;
            $recipe_meta['_cp_recipe_short_description']     = isset( $recipe_cs2_meta['_cp_recipe_short_description'] ) ? $recipe_cs2_meta['_cp_recipe_short_description'] : false;
            $recipe_meta['_cp_recipe_excerpt']               = isset( $recipe_cs2_meta['_cp_recipe_excerpt'] ) ? $recipe_cs2_meta['_cp_recipe_excerpt'] : false;
            $recipe_meta['_cp_recipe_difficulty_level']      = isset( $recipe_cs2_meta['_cp_recipe_difficulty_level'] ) ? $recipe_cs2_meta['_cp_recipe_difficulty_level'] : false;
            $recipe_meta['_cp_recipe_prep_time']             = isset( $recipe_cs2_meta['_cp_recipe_prep_time'] ) ? $recipe_cs2_meta['_cp_recipe_prep_time'] : false;
            $recipe_meta['_cp_recipe_cook_time']             = isset( $recipe_cs2_meta['_cp_recipe_cook_time'] ) ? $recipe_cs2_meta['_cp_recipe_cook_time'] : false;
            $recipe_meta['_cp_recipe_additional_notes']      = isset( $recipe_cs2_meta['_cp_recipe_additional_notes'] ) ? $recipe_cs2_meta['_cp_recipe_additional_notes'] : false;
            $recipe_meta['_cp_recipe_admin_rating']          = isset( $recipe_cs2_meta['_cp_recipe_admin_rating'] ) ? $recipe_cs2_meta['_cp_recipe_admin_rating'] : false;
            $recipe_meta['_cp_recipe_yields']                = isset( $recipe_cs2_meta['_cp_recipe_yields'] ) ? $recipe_cs2_meta['_cp_recipe_yields'] : false;
            $recipe_meta['_cp_recipe_nutrition_servingsize'] = isset( $recipe_cs2_meta['_cp_recipe_nutrition_servingsize'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_servingsize'] : false;
            $recipe_meta['_cp_recipe_nutrition_calories']    = isset( $recipe_cs2_meta['_cp_recipe_nutrition_calories'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_calories'] : false;
            $recipe_meta['_cp_recipe_nutrition_fat']         = isset( $recipe_cs2_meta['_cp_recipe_nutrition_fat'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_fat'] : false;
            $recipe_meta['_cp_recipe_nutrition_satfat']      = isset( $recipe_cs2_meta['_cp_recipe_nutrition_satfat'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_satfat'] : false;
            $recipe_meta['_cp_recipe_nutrition_transfat']    = isset( $recipe_cs2_meta['_cp_recipe_nutrition_transfat'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_transfat'] : false;
            $recipe_meta['_cp_recipe_nutrition_cholesterol'] = isset( $recipe_cs2_meta['_cp_recipe_nutrition_cholesterol'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_cholesterol'] : false;
            $recipe_meta['_cp_recipe_nutrition_sodium']      = isset( $recipe_cs2_meta['_cp_recipe_nutrition_sodium'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_sodium'] : false;
            $recipe_meta['_cp_recipe_nutrition_potassium']   = isset( $recipe_cs2_meta['_cp_recipe_nutrition_potassium'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_potassium'] : false;
            $recipe_meta['_cp_recipe_nutrition_carbs']       = isset( $recipe_cs2_meta['_cp_recipe_nutrition_carbs'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_carbs'] : false;
            $recipe_meta['_cp_recipe_nutrition_fiber']       = isset( $recipe_cs2_meta['_cp_recipe_nutrition_fiber'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_fiber'] : false;
            $recipe_meta['_cp_recipe_nutrition_sugar']       = isset( $recipe_cs2_meta['_cp_recipe_nutrition_sugar'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_sugar'] : false;
            $recipe_meta['_cp_recipe_nutrition_protein']     = isset( $recipe_cs2_meta['_cp_recipe_nutrition_protein'] ) ? $recipe_cs2_meta['_cp_recipe_nutrition_protein'] : false;

            if ( ! $recipe_meta['_cp_recipe_excerpt'] ) :
                $recipe_meta['_cp_recipe_excerpt']           = $recipe_meta['_cp_recipe_short_description'];
                $recipe_meta['_cp_recipe_short_description'] = false;
            endif;

            return $recipe_meta;

        endif;

        return [];
    }

    /**
     * Sync up the Cooked 2.x Classic recipe meta fields to the new Cooked 3.x meta fields.
     *
     * @param array<string, mixed> $c2_recipe_settings Classic recipe settings.
     * @param int                  $recipe_id          Recipe ID.
     * @return array<string, mixed>
     */
    public static function sync_c2_recipe_settings( $c2_recipe_settings, $recipe_id ) {
        $recipe_settings = [];
        $ingredients     = [];
        $directions      = [];

        $recipe_settings['title']            = isset( $c2_recipe_settings['_cp_recipe_title'] ) ? $c2_recipe_settings['_cp_recipe_title'] : '';
        $recipe_settings['content']          = isset( $c2_recipe_settings['_cp_recipe_short_description'] ) ? wpautop( $c2_recipe_settings['_cp_recipe_short_description'] . ( $c2_recipe_settings['_cp_recipe_short_description'] ? '<br><br>' : '' ) . \Cooked_Recipes::default_content() . ( $c2_recipe_settings['_cp_recipe_additional_notes'] ? '<br><br>' : '' ) . $c2_recipe_settings['_cp_recipe_additional_notes'] ) : '';
        $recipe_settings['excerpt']          = isset( $c2_recipe_settings['_cp_recipe_excerpt'] ) ? $c2_recipe_settings['_cp_recipe_excerpt'] : '';
        $recipe_settings['difficulty_level'] = isset( $c2_recipe_settings['_cp_recipe_difficulty_level'] ) ? $c2_recipe_settings['_cp_recipe_difficulty_level'] : '';
        $recipe_settings['prep_time']        = isset( $c2_recipe_settings['_cp_recipe_prep_time'] ) ? $c2_recipe_settings['_cp_recipe_prep_time'] : '';
        $recipe_settings['cook_time']        = isset( $c2_recipe_settings['_cp_recipe_cook_time'] ) ? $c2_recipe_settings['_cp_recipe_cook_time'] : '';

        // Ingredients.
        if ( ! empty( $c2_recipe_settings['_cp_recipe_ingredients'] ) && empty( $c2_recipe_settings['_cp_recipe_detailed_ingredients'] ) ) :
            $ingredients = explode( "\n", $c2_recipe_settings['_cp_recipe_ingredients'] );
        elseif ( ! empty( $c2_recipe_settings['_cp_recipe_detailed_ingredients'] ) ) :
            $ingredients = \Cooked_Functions::safe_unserialize( $c2_recipe_settings['_cp_recipe_detailed_ingredients'] );
        endif;

        if ( ! empty( $ingredients ) && is_array( $ingredients ) ) :
            foreach ( $ingredients as $ing ) :
                $rand_id = wp_rand( 1000000, 9999999 );
                if ( isset( $ing['type'] ) && 'ingredient' === $ing['type'] ) :
                    $recipe_settings['ingredients'][ $rand_id ]['amount']          = $ing['amount'];
                    $recipe_settings['ingredients'][ $rand_id ]['measurement']     = $ing['measurement'];
                    $recipe_settings['ingredients'][ $rand_id ]['name']            = $ing['name'];
                    $recipe_settings['ingredients'][ $rand_id ]['sub_amount']      = '';
                    $recipe_settings['ingredients'][ $rand_id ]['sub_measurement'] = '';
                    $recipe_settings['ingredients'][ $rand_id ]['sub_name']        = '';
                elseif ( isset( $ing['type'] ) && 'section' === $ing['type'] ) :
                    $recipe_settings['ingredients'][ $rand_id ]['section_heading_name'] = $ing['value'];
                elseif ( '--' === substr( $ing, 0, 2 ) ) :
                        $recipe_settings['ingredients'][ $rand_id ]['section_heading_name'] = substr( $ing, 2 );
                    else :
                        $recipe_settings['ingredients'][ $rand_id ]['amount']          = false;
                        $recipe_settings['ingredients'][ $rand_id ]['measurement']     = false;
                        $recipe_settings['ingredients'][ $rand_id ]['name']            = $ing;
                        $recipe_settings['ingredients'][ $rand_id ]['sub_amount']      = '';
                        $recipe_settings['ingredients'][ $rand_id ]['sub_measurement'] = '';
                        $recipe_settings['ingredients'][ $rand_id ]['sub_name']        = '';
                endif;
            endforeach;
        endif;

        // Directions.
        if ( ! empty( $c2_recipe_settings['_cp_recipe_directions'] ) && empty( $c2_recipe_settings['_cp_recipe_detailed_directions'] ) ) :
            $directions = explode( "\n", $c2_recipe_settings['_cp_recipe_directions'] );
        elseif ( ! empty( $c2_recipe_settings['_cp_recipe_detailed_directions'] ) ) :
            $directions = \Cooked_Functions::safe_unserialize( $c2_recipe_settings['_cp_recipe_detailed_directions'] );
        endif;

        if ( ! empty( $directions ) && is_array( $directions ) ) :

            foreach ( $directions as $dir ) :
                $rand_id = wp_rand( 1000000, 9999999 );
                if ( isset( $dir['type'] ) && 'direction' === $dir['type'] ) :
                    $recipe_settings['directions'][ $rand_id ]['image']   = $dir['image_id'];
                    $recipe_settings['directions'][ $rand_id ]['content'] = $dir['value'];
                elseif ( isset( $dir['type'] ) && 'section' === $dir['type'] ) :
                    $recipe_settings['directions'][ $rand_id ]['section_heading_name'] = $dir['value'];
                elseif ( '--' === substr( $dir, 0, 2 ) ) :
                        $recipe_settings['directions'][ $rand_id ]['section_heading_name'] = substr( $dir, 2 );
                    else :
                        $recipe_settings['directions'][ $rand_id ]['image']   = false;
                        $recipe_settings['directions'][ $rand_id ]['content'] = $dir;
                endif;
            endforeach;

        endif;

        if ( isset( $c2_recipe_settings['_cp_recipe_external_video'] ) && $c2_recipe_settings['_cp_recipe_external_video'] && wp_oembed_get( $c2_recipe_settings['_cp_recipe_external_video'] ) ) :
            $recipe_settings['gallery']['video_url'] = $c2_recipe_settings['_cp_recipe_external_video'];
        else :
            $recipe_settings['gallery']['video_url'] = false;
            $recipe_settings['gallery']['type']      = 'cooked';
        endif;

        $recipe_settings['nutrition']['serving_size'] = ( isset( $c2_recipe_settings['_cp_recipe_yields'] ) && $c2_recipe_settings['_cp_recipe_yields'] ? $c2_recipe_settings['_cp_recipe_yields'] : false );
        $recipe_settings['nutrition']['servings']     = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_servingsize'] ) && $c2_recipe_settings['_cp_recipe_nutrition_servingsize'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_servingsize'] ) : false );
        $recipe_settings['nutrition']['calories']     = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_calories'] ) && $c2_recipe_settings['_cp_recipe_nutrition_calories'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_calories'] ) : false );
        $recipe_settings['nutrition']['fat']          = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_fat'] ) && $c2_recipe_settings['_cp_recipe_nutrition_fat'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_fat'] ) : false );
        $recipe_settings['nutrition']['sat_fat']      = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_satfat'] ) && $c2_recipe_settings['_cp_recipe_nutrition_satfat'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_satfat'] ) : false );
        $recipe_settings['nutrition']['trans_fat']    = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_transfat'] ) && $c2_recipe_settings['_cp_recipe_nutrition_transfat'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_transfat'] ) : false );
        $recipe_settings['nutrition']['cholesterol']  = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_cholesterol'] ) && $c2_recipe_settings['_cp_recipe_nutrition_cholesterol'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_cholesterol'] ) : false );
        $recipe_settings['nutrition']['sodium']       = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_sodium'] ) && $c2_recipe_settings['_cp_recipe_nutrition_sodium'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_sodium'] ) : false );
        $recipe_settings['nutrition']['potassium']    = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_potassium'] ) && $c2_recipe_settings['_cp_recipe_nutrition_potassium'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_potassium'] ) : false );
        $recipe_settings['nutrition']['carbs']        = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_carbs'] ) && $c2_recipe_settings['_cp_recipe_nutrition_carbs'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_carbs'] ) : false );
        $recipe_settings['nutrition']['fiber']        = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_fiber'] ) && $c2_recipe_settings['_cp_recipe_nutrition_fiber'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_fiber'] ) : false );
        $recipe_settings['nutrition']['sugars']       = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_sugar'] ) && $c2_recipe_settings['_cp_recipe_nutrition_sugar'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_sugar'] ) : false );
        $recipe_settings['nutrition']['protein']      = ( isset( $c2_recipe_settings['_cp_recipe_nutrition_protein'] ) && $c2_recipe_settings['_cp_recipe_nutrition_protein'] ? preg_replace( '/[^0-9]/', '', $c2_recipe_settings['_cp_recipe_nutrition_protein'] ) : false );

        $_nutrition_facts = \Cooked_Measurements::nutrition_facts();
        foreach ( $_nutrition_facts as $nutrition_facts ) :
            foreach ( $nutrition_facts as $slug => $nf ) :

                if ( ! isset( $recipe_settings[ $slug ] ) ) :
					$recipe_settings[ $slug ] = false;
endif;
                if ( isset( $nf['subs'] ) ) :
                    foreach ( $nf['subs'] as $sub_slug => $sub_nf ) :
                        if ( ! isset( $recipe_settings[ $sub_slug ] ) ) :
							$recipe_settings[ $sub_slug ] = false;
endif;
                    endforeach;
                endif;
            endforeach;
        endforeach;

        return apply_filters( 'cooked_sync_c2_recipe_settings', $recipe_settings, $recipe_id );
    }

    /**
     * Points the canonical URL of Browse page filters at the filtered URL.
     *
     * @param string $canonical_url Canonical URL.
     * @return string
     */
    public function modify_browse_page_canonical_url( $canonical_url ) {
        global $_cooked_settings, $wp_query;

        if ( ! is_page() ) {
            return $canonical_url;
        }

        $browse_page_id = \Cooked_Multilingual::get_browse_page_id();

        // Only modify for browse page with category.
        if ( $browse_page_id && is_page( $browse_page_id ) &&
            isset( $wp_query->query['cp_recipe_category'] ) &&
            taxonomy_exists( 'cp_recipe_category' ) &&
            term_exists( $wp_query->query['cp_recipe_category'], 'cp_recipe_category' ) ) {

            // Build the canonical URL based on permalink structure.
            if ( get_option( 'permalink_structure' ) ) {
                $new_canonical = untrailingslashit( (string) get_permalink( $browse_page_id ) ) . '/' . $_cooked_settings['recipe_category_permalink'] . '/' . $wp_query->query['cp_recipe_category'];
            } else {
                $new_canonical = add_query_arg( 'cp_recipe_category', $wp_query->query['cp_recipe_category'], (string) get_permalink( $browse_page_id ) );
            }

            return $new_canonical;
        }

        return $canonical_url;
    }
}
