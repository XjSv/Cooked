<?php
/**
 * Cooked Recipe-Specific Functions
 *
 * @package     Cooked
 * @subpackage  Recipe-Specific Functions
 * @since       1.0.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Recipe_Meta Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class Cooked_Recipes {

    public function __construct() {
        add_filter( 'cooked_recipe_content_filter', [&$this, 'vendor_checks'], 1, 1 );
        add_filter( 'the_content', [&$this, 'recipe_template'], 10 );

        add_filter( 'parse_query', [&$this, 'custom_taxonomy_in_query'], 10 );

        add_action( 'template_redirect', [&$this, 'print_recipe_template'], 10 );
        add_action( 'cooked_check_recipe_query', [&$this, 'check_recipe_query'], 10 );
        add_action( 'pre_get_posts', [&$this, 'cooked_pre_get_posts'], 10, 1 );
        add_action( 'restrict_manage_posts', [&$this, 'filter_recipes_by_taxonomy'], 10 );

        add_filter('get_canonical_url', [&$this, 'modify_browse_page_canonical_url'], 20, 2);
    }

    public static function get( $args = false, $single = false, $ids_only = false, $limit = false, $ids_and_titles_only = false, $post_status = 'publish' ) {
        $recipes = [];
        $counter = 0;

        // Get by Recipe ID
        if ( $args && !is_array($args) ):

            $recipe_id = $args;
            $args = [
                'post_type' => 'cp_recipe',
                'post__in' => [$recipe_id],
                'post_status' => $post_status
            ];

        // Default Query
        elseif ( !$args || !is_array($args) ):

            $args = [
                'post_type' => 'cp_recipe',
                'posts_per_page' => -1,
                'post_status' => $post_status,
                'orderby' => 'name',
                'order' => 'ASC'
            ];

            if ( $ids_only || $ids_and_titles_only ):
                $args['fields'] = 'ids';
            endif;

            if ( $limit ):
                $args['limit'] = $limit;
            endif;

        // Search Query
        elseif ( $args && isset($args['s']) && isset($args['meta_query']) ):

            $meta_query = $args['meta_query'];
            $recipe_ids = [];

            $pre_search_args = $args;
            $pre_search_args['posts_per_page'] = -1;
            $pre_search_args['fields'] = 'ids';

            unset($pre_search_args['meta_query']);

            $recipes_pre_search = new WP_Query($pre_search_args);
            if ( $recipes_pre_search->have_posts() ):
                $recipe_ids = $recipes_pre_search->posts;
            endif;

            $pre_search_args['meta_query'] = $meta_query;
            unset($pre_search_args['s']);

            $recipes_pre_search = new WP_Query($pre_search_args);
            if ( $recipes_pre_search->have_posts() ):
                $rposts = $recipes_pre_search->posts;
                $recipe_ids = !empty($recipe_ids) ? array_merge( $recipe_ids, $rposts ) : $rposts;
            endif;

            $recipe_ids = array_unique( $recipe_ids );

            if ( !empty($recipe_ids) ):
                unset($args['s']);
                unset($args['meta_query']);
                $args['post__in'] = $recipe_ids;
            endif;

        endif;

        $recipes_results = new WP_Query($args);

        if ( $recipes_results->have_posts() ) {
            if ( $ids_only ) {
                return $recipes_results->posts;
            } elseif ( $ids_and_titles_only ) {
                while ( $recipes_results->have_posts() ) {
                    $recipes_results->the_post();
                    $recipes[$counter]['id'] = $recipes_results->post->ID;
                    $recipes[$counter]['title'] = $recipes_results->post->post_title;

                    $counter++;
                }
            } else {
                while ( $recipes_results->have_posts() ) {
                    $recipes_results->the_post();
                    $recipes[$counter]['id'] = $recipes_results->post->ID;
                    $recipes[$counter]['title'] = $recipes_results->post->post_title;
                    $recipe_settings = self::get_settings($recipes_results->post->ID);

                    foreach ($recipe_settings as $key => $setting) {
                        $recipes[$counter][$key] = $setting;
                    }

                    $counter++;
                }
            }
        } else {
            wp_reset_postdata();
            return;
        }

        $recipes['raw'] = $recipes_results;

        if ( $single && isset( $recipes[0] ) ):
            $recipes = $recipes[0];
        endif;

        wp_reset_postdata();

        return $recipes;
    }

    public static function get_settings( $post_id, $bc = true ) {
        if ( !$post_id ) return;

        $recipe_settings = get_post_meta( $post_id, '_recipe_settings', true );

        if ( !is_array( $recipe_settings ) || empty($recipe_settings) ) {
            $recipe_settings = [];
        }

        $recipe_settings['title'] = get_the_title( $post_id );

        $recipe_post = get_post($post_id);
        $wp_excerpt = $recipe_post->post_excerpt;

        // Check for excerpt/content
        // if ( isset($recipe_settings['excerpt']) && !$recipe_settings['excerpt'] && !$wp_excerpt || !isset($recipe_settings['excerpt']) ) {
        //     wp_update_post(['ID' => $post_id, 'post_excerpt' => $recipe_settings['title']] );
        // }

        // Check for nutrition data
        if ( !isset($recipe_settings['nutrition']) ):
            $recipe_settings['nutrition'] = [];
            $recipe_settings['nutrition']['servings'] = 1;
        endif;

        // Backwards Compatibility with Cooked 2.x
        if ( !isset($recipe_settings['cooked_version']) && $bc ):
            $c2_recipe_settings = self::get_c2_recipe_meta( $post_id );
            $recipe_settings = self::sync_c2_recipe_settings( $c2_recipe_settings, $post_id );
        endif;

        // Get the author information
        $recipe_settings['author'] = Cooked_Users::get( $recipe_post->post_author, true );

        // Include the Post ID
        $recipe_settings['id'] = $post_id;

        // You're welcome developers!
        $recipe_settings = apply_filters( 'cooked_single_recipe_settings', $recipe_settings, $post_id );

        return $recipe_settings;
    }

    public function check_recipe_query() {
        global $_cooked_settings, $recipe_query;

        if ( !isset($recipe_query['cp_recipe_category']) ):
            $recipe_query['cp_recipe_category'] = ( isset($_GET['cp_recipe_category']) && $_GET['cp_recipe_category'] ? intval($_GET['cp_recipe_category']) : ( isset($_cooked_settings['browse_default_cp_recipe_category']) && $_cooked_settings['browse_default_cp_recipe_category'] ? $_cooked_settings['browse_default_cp_recipe_category'] : false ) );
        endif;
    }

    public static function cooked_pre_get_posts( $q ) {
        if ( $title = $q->get( '_cooked_title' ) ) {
            add_filter( 'get_meta_sql', function( $sql ) use ( $title ) {
                global $wpdb, $cooked_modified_where;

                if ( $cooked_modified_where ) return $sql;
                $cooked_modified_where = 1;

                // Modified WHERE
                $sql['where'] = sprintf(
                    " AND ( %s OR %s ) ",
                    apply_filters( 'cooked_query_where_filter', $wpdb->prepare( "{$wpdb->posts}.post_title like '%%%s%%'", $wpdb->esc_like( $title ) ) ),
                    mb_substr( $sql['where'], 5, mb_strlen( $sql['where'] ) )
                );

                return $sql;
            });
        }
    }

    public static function recipe_list( $orderby = 'date', $show = 5, $recipes = false, $width = false, $hide_image = false, $hide_author = false ) {
        global $_cooked_settings;

        $width = !$width ? '100%' : $width;
        $pixel_width = stristr( $width, 'px', true );
        $percent_width = stristr( $width, '%', true );
        $width = $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) );

        $args = [
            'post_type' => 'cp_recipe',
            'posts_per_page' => $show,
            'post_status' => 'publish',
            'orderby' => $orderby,
            'order' => 'DESC'
        ];

        if ( !empty($recipes) ) {
            $args['posts_per_page'] = -1;
            $args['orderby'] = 'post__in';
            $args['order'] = 'ASC';
            $args['post__in'] = $recipes;
        }

        $recipes = Cooked_Recipes::get( $args );

        if ( isset($recipes['raw']) ): unset( $recipes['raw'] ); endif;
        $recipe_list = [];

        if ( !empty($recipes) ):

            echo '<div class="cooked-shortcode-recipe-list">';

                foreach ( $recipes as $key => $recipe ):

                    $rid = $recipe['id'];
                    $has_image_class = has_post_thumbnail($rid) && !$hide_image ? ' cooked-srl-has-image' : '';

                    echo '<div class="cooked-srl-single' . esc_attr( $has_image_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

                        echo has_post_thumbnail($rid) && !$hide_image ? '<div class="cooked-srl-image"><a href="' . esc_url( get_permalink($rid) ) . '">' . get_the_post_thumbnail( $rid, 'thumbnail' ) . '</a></div>' : '';

                        echo '<div class="cooked-srl-content">';

                            echo '<div class="cooked-srl-title"><a href="' . esc_url( get_permalink($rid) ) . '">' . esc_html( $recipe['title'] ) . '</a></div>';

                            if ( in_array('author', $_cooked_settings['recipe_info_display_options']) && !$hide_author ):
                                echo '<div class="cooked-srl-author">';
                                    $author = $recipe['author'];
                                    /* translators: stating the recipe author with a "By" in front of it. (ex: "By John Smith")  */
                                    echo sprintf( __( 'By %s', 'cooked' ), '<strong>' . wp_kses_post( $author['name'] ) . '</strong>' );
                                echo '</div>';
                            endif;

                        echo '</div>';

                    echo '</div>';
                   endforeach;

            echo '</div>';

        endif;

    }

    public static function card( $rid, $width = false, $hide_image = false, $hide_title = false, $hide_excerpt = false, $hide_author = false, $style = false ) {
        global $_cooked_settings;

        $recipe = self::get( $rid, true );
        $style_class = $style ? ' cooked-recipe-card-' . esc_attr($style) : '';

        $width = !$width ? '100%' : $width;
        $pixel_width = stristr( $width, 'px', true );
        $percent_width = stristr( $width, '%', true );
        $width = $pixel_width ? $pixel_width . 'px' : ( $percent_width ? $percent_width . '%' : ( is_numeric( $width ) ? $width . 'px' : '100%' ) );

        ob_start();

        echo '<a href="' . esc_url( get_permalink($rid) ) . '" class="cooked-recipe-card' . esc_attr( $style_class ) . '" style="width:100%; max-width:' . esc_attr( $width ) . '">';

            do_action( 'cooked_recipe_grid_before_recipe', $recipe );

            do_action( 'cooked_recipe_grid_before_image', $recipe );

            echo has_post_thumbnail($rid) && !$hide_image ? '<span class="cooked-recipe-card-image" style="background-image:url(' . get_the_post_thumbnail_url( $recipe['id'], 'cooked-medium' ) . ');"></span>' : '';

            //do_action( 'cooked_recipe_grid_after_image', $recipe );

            echo '<span class="cooked-recipe-card-content">';

                do_action( 'cooked_recipe_grid_before_name', $recipe );

                echo !$hide_title ? '<span class="cooked-recipe-card-title">' . esc_html( $recipe['title'] ) . '</span>' : '';

                do_action( 'cooked_recipe_grid_after_name', $recipe );

                if ( $hide_excerpt && !$hide_title && !$hide_author ): echo '<span class="cooked-recipe-card-sep"></span>'; endif;

                do_action( 'cooked_recipe_grid_before_author', $recipe );

                if ( in_array('author',$_cooked_settings['recipe_info_display_options']) && !$hide_author ):
                    echo '<span class="cooked-recipe-card-author">';
                        $author = $recipe['author'];
                        /* translators: stating the recipe author with a "By" in front of it. (ex: "By John Smith")  */
                        echo sprintf( __( 'By %s', 'cooked' ), '<strong>' . $author['name'] . '</strong>' );
                    echo '</span>';
                endif;

                do_action( 'cooked_recipe_grid_after_author', $recipe );

                if ( !$hide_excerpt && !$hide_title || !$hide_excerpt && !$hide_author ): echo '<span class="cooked-recipe-card-sep"></span>'; endif;

                do_action( 'cooked_recipe_grid_before_excerpt', $recipe );

                echo !$hide_excerpt ? '<span class="cooked-recipe-card-excerpt">' . wp_kses_post( $recipe['excerpt'] ) . '</span>' : '';

                do_action( 'cooked_recipe_grid_after_excerpt', $recipe );

            echo '</span>';

            do_action( 'cooked_recipe_grid_after_recipe', $recipe );

        echo '</a>';

        return ob_get_clean();
    }

    public function print_recipe_template() {
        if ( is_singular('cp_recipe') && isset($_GET['print']) ):
            load_template( COOKED_DIR . 'templates/front/recipe-print.php', false);
            exit;
        endif;
    }

    public static function vendor_checks( $content ) {
        global $wp_query, $post;

        // WooCommerce Memberships
        if ( function_exists('wc_memberships_user_can') && function_exists('wc_memberships_is_post_content_restricted') && function_exists('wc_memberships_user_can') ):

            if ( !is_user_logged_in() && wc_memberships_is_post_content_restricted() ):
                return WC_Memberships_User_Messages::get_message_html( 'content_restricted', ['post' => $post] );
            elseif ( is_user_logged_in() ):
                $_user = wp_get_current_user();
                if ( !wc_memberships_user_can( $_user->ID, 'view', ['cp_recipe' => $post->ID] ) ):
                    return WC_Memberships_User_Messages::get_message_html( 'content_restricted', ['post' => $post] );
                endif;
            endif;

        endif;

        return $content;
    }

    public function filter_recipes_by_taxonomy() {
        global $typenow, $cooked_taxonomies_shown;
        $taxonomies = apply_filters( 'cooked_active_taxonomies', ['cp_recipe_category'] );
        if ( $typenow == 'cp_recipe' ):
            foreach ( $taxonomies as $taxonomy ):
                if ( is_array($cooked_taxonomies_shown) && !in_array( $taxonomy, $cooked_taxonomies_shown ) || !is_array($cooked_taxonomies_shown) ):
                    $cooked_taxonomies_shown[] = $taxonomy;
                    $selected = isset($_GET[$taxonomy]) ? sanitize_title($_GET[$taxonomy]) : '';
                    $info_taxonomy = get_taxonomy($taxonomy);
                    $taxonomy_label = $info_taxonomy->label;

                    /* translators: For showing "All" of a taxonomy (ex: "All Burgers")  */
                    $all_string = sprintf( __( "All %s", "cooked" ), $taxonomy_label );

                    wp_dropdown_categories([
                        'show_option_all' => $all_string,
                        'taxonomy' => $taxonomy,
                        'name' => $taxonomy,
                        'orderby' => 'name',
                        'selected' => $selected,
                        'show_count' => true,
                        'hide_empty' => true,
                    ]);
                endif;
            endforeach;
        endif;
    }

    public function custom_taxonomy_in_query($query) {
        global $pagenow;
        $taxonomies = apply_filters( 'cooked_active_taxonomies', ['cp_recipe_category'] );
        $q_vars = &$query->query_vars;

        foreach ( $taxonomies as $taxonomy ):
            if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == 'cp_recipe' && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ):
                $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
                $q_vars[$taxonomy] = $term->slug;
            endif;
        endforeach;
    }

    public static function list_view( $list_atts = false ) {
        global $wp_query, $recipe_query, $atts, $_cooked_settings, $recipes, $recipe_args, $current_recipe_page;

        // Get the attributes for this view
        $atts = array_change_key_case( (array) $list_atts, CASE_LOWER );;
        $ls_method = 'list_style_grid';
        $ls_class = 'Cooked_Recipes';

        // Change the recipe layout
        if ( $atts['layout'] ) {
            $recipe_list_style = apply_filters( 'cooked_recipe_list_style', [ 'grid' => 'Cooked_Recipes' ], $atts['layout'] );
            $list_style = esc_html( key( $recipe_list_style ) );
            $ls_method = 'list_style_' . $list_style;
            $ls_class = current( $recipe_list_style );

            $_cooked_settings['recipe_list_style'] = $list_style;
        }

        $recipe_query = $wp_query->query;
        $tax_query = [];

        do_action( 'cooked_check_recipe_query' );

        if ( isset($_cooked_settings['recipe_taxonomies']) && !empty($_cooked_settings['recipe_taxonomies']) ):
            foreach ( $_cooked_settings['recipe_taxonomies'] as $taxonomy ):
                if ( isset($recipe_query[$taxonomy]) && $recipe_query[$taxonomy] ):
                    $field_type = is_numeric($recipe_query[$taxonomy]) ? 'id' : 'slug';
                    $tax_query['relation'] = 'AND';
                    $tax_query[] = [
                        'taxonomy' => $taxonomy,
                        'field' => $field_type,
                        'terms' => array_map('trim', explode(',', esc_html($recipe_query[$taxonomy])))
                    ];
                endif;
            endforeach;

            if ( empty($tax_query) ):
                foreach ( $_cooked_settings['recipe_taxonomies'] as $taxonomy ):
                    if ( isset( $_cooked_settings['browse_default_' . $taxonomy] ) && $_cooked_settings['browse_default_' . $taxonomy] ):
                        $tax_query['relation'] = 'AND';
                        $tax_query[] = [
                            'taxonomy' => $taxonomy,
                            'field' => 'id',
                            'terms' => [esc_html($_cooked_settings['browse_default_' . $taxonomy])]
                        ];
                    endif;
                endforeach;
            endif;
        endif;

        if ( empty($tax_query) ):

            if ( $atts['category'] ):
                $tax_query['relation'] = 'AND';
                $tax_query[] = [
                    'taxonomy' => 'cp_recipe_category',
                    'field' => (is_numeric($atts['category']) ? 'id' : 'slug'),
                    'terms' => array_map('trim', explode(',', esc_html($atts['category'])))
                ];
            endif;

            $tax_query = apply_filters( 'cooked_tax_query_filter', $tax_query, $atts );

        endif;

        $sorting_type = get_query_var('cooked_browse_sort_by', isset($_cooked_settings['browse_default_sort']) && $_cooked_settings['browse_default_sort'] ? $_cooked_settings['browse_default_sort'] : 'date_desc' );
        $sorting_type = sanitize_key($sorting_type);
        $sorting_types = explode('_', $sorting_type);

        $text_search = get_query_var('cooked_search_s', '');
        $text_search = urldecode($text_search);
        $text_search = esc_html($text_search);
        $recipes_per_page = ( $atts['show'] ? $atts['show'] : ( isset($_cooked_settings['recipes_per_page']) && $_cooked_settings['recipes_per_page'] ? $_cooked_settings['recipes_per_page'] : get_option( 'posts_per_page' ) ) );
        $current_recipe_page = Cooked_Recipes::current_page();

        $orderby = $atts['orderby'] ? esc_html( $atts['orderby'] ) : $sorting_types[0];
        $meta_sort =  false;

        $post_status = 'publish';
        if ( isset($atts['public_recipes']) && $atts['public_recipes'] == false ):
            $post_status = ['publish', 'pending', 'draft'];
        endif;

        $recipe_args = [
            'paged' => $current_recipe_page,
            'post_type' => 'cp_recipe',
            'posts_per_page' => $recipes_per_page,
            'post_status' => $post_status,
            'orderby' => $orderby,
            'order' => ($atts['order'] ? esc_html($atts['order']) : $sorting_types[1])
        ];

        if ( isset($atts['exclude']) && $atts['exclude'] ):
            $exclude = explode( ',', str_replace( ' ', '', $atts['exclude'] ) );
            $recipe_args['post__not_in'] = $exclude;
        endif;

        if ( $text_search ):
            // Replace [+] [,] [;] with spaces
            $prep_text = str_replace(['+', ',', ';'], ' ', $text_search);

            // Replace duplicate spaces
            $prep_text = preg_replace('/\s+/', ' ', $prep_text);

            // Explode into an array of search terms
            $words = explode( ' ', $prep_text );

            if ( !empty($words) ):
                $meta_query['relation'] = 'AND';
                foreach ( $words as $word ):
                    $meta_query[] = [
                        'key' => '_recipe_settings',
                        'value' => $word,
                        'compare' => 'LIKE'
                    ];
                endforeach;
            else:
                $meta_query[] = [
                    'key' => '_recipe_settings',
                    'value' => $text_search,
                    'compare' => 'LIKE'
                ];
            endif;
            $recipe_args['_cooked_title'] = $prep_text;
            $recipe_args['s'] = $prep_text;
            $recipe_args['meta_query'] = $meta_query;

        endif;

        if ( !empty($tax_query) ):
            $recipe_args['tax_query'] = $tax_query;
        endif;

        if ( $atts['author'] && is_numeric( $atts['author'] ) ):
            $recipe_args['author'] = $atts['author'];
        elseif ( $atts['author'] ):
            $recipe_args['author_name'] = $atts['author'];
        endif;

        if ( isset( $atts['include'] ) && !empty($atts['include']) ):
            $recipe_args['post__in'] = $atts['include'];
        endif;

        $recipe_args = apply_filters( 'cooked_recipe_query_args', $recipe_args, $atts, $sorting_types );
        if ( !isset($atts['public_recipes']) || isset($atts['public_recipes']) && $atts['public_recipes'] ):
            $recipe_args = apply_filters( 'cooked_recipe_public_query_filters', $recipe_args );
        endif;

        wp_suspend_cache_addition(true);

        ob_start();
        $recipes = Cooked_Recipes::get( $recipe_args );
        ( $theme_template_override = locate_template( 'recipe-list.php')) ? load_template( $theme_template_override, false ) : load_template( COOKED_DIR . 'templates/front/recipe-list.php', false );
        wp_reset_postdata();
        Cooked_Settings::reset();

        wp_suspend_cache_addition(false);

        return ob_get_clean();
    }

    public static function list_style_grid($atts = []) {
        load_template(COOKED_DIR . 'templates/front/recipe-single.php', false, $atts);
    }

    public static function current_page() {
        return get_query_var( 'paged' ) ? max( 1, get_query_var('paged') ) : ( get_query_var( 'page' ) ? max( 1, get_query_var('page') ) : 1 );
    }

    public static function pagination( $recipe_query, $recipe_args ) {
        global $_cooked_settings, $current_recipe_page, $paged, $atts;

        $paged = self::current_page();
        $total_recipe_pages = $recipe_query->max_num_pages;
        $pagination = '';

        if ( $total_recipe_pages > 1) {
            do_action( 'cooked_init_pagination', $recipe_args, $current_recipe_page, $total_recipe_pages, $atts );

            $pagination_style = apply_filters( 'cooked_pagination_style', ['numbered_pagination' => 'Cooked_Recipes'] );
            $p_method = key( $pagination_style );
            $p_class = current( $pagination_style );

            $pagination = $p_class::$p_method( $current_recipe_page, $total_recipe_pages );
        }

        return $pagination;
    }

    public static function numbered_pagination( $current_recipe_page, $total_recipe_pages ) {
        if ( get_option('permalink_structure') ) {
            $format = '?paged=%#%';
        } else {
            $format = 'page/%#%/';
        }

        $big = 999999999;
        $base = $format =='?paged=%#%' ? $base = str_replace( $big, '%#%', get_pagenum_link( $big ) ) : $base = @add_query_arg('paged','%#%');

        $recipe_pagination = apply_filters( 'cooked_pagination_args', [
            'base' => $base,
            'format' => $format,
            'mid-size' => 1,
            'current' => $current_recipe_page,
            'total' => $total_recipe_pages,
            'prev_next' => true,
            'prev_text' => '<i class="cooked-icon cooked-icon-angle-left"></i>',
            'next_text' => '<i class="cooked-icon cooked-icon-angle-right"></i>',
        ]);
        return '<div class="cooked-pagination-numbered cooked-clearfix">' . paginate_links( $recipe_pagination ) . '</div>';
    }

    public static function default_content() {
        return apply_filters( 'cooked_default_content', '<p>[cooked-info left="author,taxonomies,difficulty" right="print,fullscreen"]</p><p>[cooked-excerpt]</p><p>[cooked-image]</p><p>[cooked-info left="servings" right="prep_time,cook_time,total_time"]</p><p>[cooked-ingredients]</p><p>[cooked-directions]</p><p>[cooked-notes show_header=true]</p><p>[cooked-gallery]</p>' );
    }

    public static function print_content() {
        return apply_filters( 'cooked_print_content', '<p>[cooked-info include="servings,prep_time,cook_time,total_time"]</p><p>[cooked-excerpt]</p><p>[cooked-image]</p><p>[cooked-ingredients]</p><p>[cooked-directions]</p><p>[cooked-notes show_header=true]</p><p>[cooked-nutrition]</p>' );
    }

    public static function fsm_content() {
        return apply_filters( 'cooked_fsm_content', '
            <div class="cooked-fsm-ingredients cooked-fsm-content cooked-active" data-nosnippet aria-hidden="false">
                <div class="cooked-panel"><h2>' . __('Ingredients', 'cooked') . '</h2>[cooked-ingredients]</div>
            </div>
            <div class="cooked-fsm-directions-wrap cooked-fsm-content" data-nosnippet aria-hidden="true">
                <div class="cooked-fsm-directions cooked-fsm-content">
                    <div class="cooked-panel"><h2>' . __('Directions', 'cooked') . '</h2>[cooked-directions]</div>
                </div>
                <div class="cooked-fsm-notes cooked-fsm-content" data-nosnippet aria-hidden="true">
                    <div class="cooked-panel"><h2>' . __('Notes', 'cooked') . '</h2>[cooked-notes]</div>
                </div>
            </div>
        ' );
    }

    public static function difficulty_levels() {
        return apply_filters( 'cooked_difficulty_levels', [
            1 => __('Beginner', 'cooked'),
            2 => __('Intermediate', 'cooked'),
            3 => __('Advanced', 'cooked')
        ]);
    }

    public static function get_by_slug($slug = false) {
        if ($slug):
            if (!function_exists('ctype_digit') || function_exists('ctype_digit') && !ctype_digit($slug)):
                $recipe_query = new WP_Query(['name' => $slug, 'post_type' => 'cp_recipe'] );
                if ($recipe_query->have_posts()):
                    $recipe_query->the_post();
                    return get_the_ID();
                else:
                    return false;
                endif;
            else:
                return $slug;
            endif;
        else:
            return false;
        endif;
    }

    public static function gallery_types() {

        $gallery_types = apply_filters( 'cooked_gallery_types', [
            'cooked' => [
                'title' => __('Cooked Gallery', 'cooked'),
                'required_class' => ''
            ],
            'envira' => [
                'title' => __('Envira Gallery', 'cooked'),
                'required_class' => 'Envira_Gallery'
            ],
            'soliloquy' => [
                'title' => __('Soliloquy Slider', 'cooked'),
                'required_class' => 'Soliloquy'
            ],
            'revslider' => [
                'title' => __('Slider Revolution', 'cooked'),
                'required_class' => 'RevSlider'
            ]
        ]);

        foreach ( $gallery_types as $slug => $gtype ):

            $results = [];

            if ( $gtype['required_class'] && class_exists($gtype['required_class']) ):

                if ( $slug == 'revslider' ):

                    $slider = new RevSlider();
                    $arrSliders = $slider->getArrSliders();
                    if (!empty($arrSliders)):
                        foreach($arrSliders as $slider):
                            $results[ $slider->getAlias() ] = $slider->getTitle();
                        endforeach;
                    endif;

                else:

                    $args = apply_filters( 'cooked_gallery_type_' . $slug . '_query', [
                        'post_type' => $slug,
                        'post_status' => 'publish',
                        'posts_per_page' => -1
                    ]);
                    $gallery_query = new WP_Query( $args );
                    if ( $gallery_query->have_posts() ):
                        while( $gallery_query->have_posts() ):
                            $gallery_query->the_post();
                            $results[$gallery_query->post->ID] = get_the_title();
                        endwhile;
                    endif;

                endif;

                if ( !empty($results) ):
                    $gallery_types[$slug]['posts'] = $results;
                else:
                    unset( $gallery_types[$slug] );
                endif;

            else:

                if ( $slug != 'cooked' ): unset( $gallery_types[$slug] ); endif;

            endif;

        endforeach;

        wp_reset_query();
        wp_reset_postdata();

        return $gallery_types;
    }

    public static function serving_size_switcher( $servings ) {
        global $_cooked_settings, $post;
        $switcher_disabled = ( isset( $_cooked_settings['advanced'] ) && in_array( 'disable_servings_switcher', $_cooked_settings['advanced'] ) ? true : false );
        $printing = ( is_singular('cp_recipe') && isset($_GET['print']) );

        if ( !$printing && !$switcher_disabled ):
            $default = $servings;
            $servings = (float)esc_html( get_query_var( 'servings', $servings ) );
            $servings = ( !$servings ? $default : $servings );
            $counter = 1;

            $quarter = $default / 4;
            $half = $default / 2;
            $double = $default * 2;
            $triple = $default * 3;

            /* translators: singular and plural quarter "serving" size */
            $quarter_string = sprintf( esc_html( _n('Quarter (%s Serving)','Quarter (%s Servings)',$quarter,'cooked')),$quarter );

            /* translators: singular and plural quarter "serving" size */
            $half_string = sprintf( esc_html( _n('Half (%s Serving)','Half (%s Servings)',$half,'cooked')),$half );

            /* translators: singular and plural quarter "serving" size */
            $default_string = sprintf( esc_html( _n('Default (%s Serving)','Default (%s Servings)',$default,'cooked')),$default );

            /* translators: singular and plural quarter "serving" size */
            $double_string = sprintf( __( 'Double (%s Servings)','cooked'),$double );

            /* translators: singular and plural quarter "serving" size */
            $triple_string = sprintf( __( 'Triple (%s Servings)','cooked'),$triple );

            $servings_array = apply_filters( 'cooked_servings_switcher_options', [
                'quarter' => ['name' => $quarter_string, 'value' => $quarter],
                'half' => ['name' => $half_string, 'value' => $half],
                'default' => ['name' => $default_string, 'value' => $default],
                'double' => ['name' => $double_string, 'value' => $double],
                'triple' => ['name' => $triple_string, 'value' => $triple],
            ], $quarter, $half, $default, $double, $triple );
        else:
            $servings_array = [];
        endif;

        echo '<span class="cooked-servings"><span class="cooked-servings-icon"><i class="cooked-icon cooked-icon-recipe-icon"></i></span>';
        echo '<strong class="cooked-meta-title">' . __('Yields','cooked') . '</strong>';
            if ( !$printing && !$switcher_disabled ):

                /* translators: singular and plural "serving" sizes */
                $servings_string = sprintf( esc_html( _n( '%s Serving', '%s Servings', $servings, 'cooked' ) ), $servings );

                echo '<a aria-label="' . $servings_string . '" href="#">' . $servings_string . '</a>';
                echo '<label for="cooked-servings-changer" class="screen-reader-text">' . __('Servings', 'cooked') . '</label>';
                echo '<select id="cooked-servings-changer" name="servings" class="cooked-servings-changer">';
                    foreach ( $servings_array as $stype ):
                        echo '<option value="' . $stype['value'] . '"' . ( $stype['value'] == $servings ? ' selected' : '' ) . '>' . esc_attr( $stype['name'] ) . '</option>';
                    endforeach;
                echo '</select>';
            else:
                /* translators: singular and plural "serving" sizes */
                echo '<span>' . sprintf( esc_html( _n( '%s Serving', '%s Servings', $servings, 'cooked' ) ), $servings ) . '</span>';
            endif;
        echo '</span>';

    }

    public static function single_ingredient( $ing, $checkboxes = true, $plain_text = false ) {
        global $recipe_settings;

        $Cooked_Measurements = new Cooked_Measurements();
        $measurements = $Cooked_Measurements->get();

        ob_start();

        if ( isset($ing['section_heading_name']) && $ing['section_heading_name'] ) {

            if ( $plain_text ) {
                return $ing['section_heading_name'];
            } else {
                $valid_elements = ['div', 'h2', 'h3', 'h4', 'h5', 'h6'];
                global $_cooked_settings;
                $default_element = isset($_cooked_settings['section_heading_default_html_tag']) ? $_cooked_settings['section_heading_default_html_tag'] : 'div';

                $element = (isset($ing['section_heading_element']) && in_array($ing['section_heading_element'], $valid_elements, true))
                    ? ($ing['section_heading_element'] === 'div' ? $default_element : $ing['section_heading_element'])
                    : $default_element;

                echo '<' . $element . ' class="cooked-single-ingredient cooked-heading">' . esc_html($ing['section_heading_name']) . '</' . $element . '>';
            }

        } elseif ( isset($ing['name']) && $ing['name'] ) {

            $default_serving_size = ( isset($recipe_settings['nutrition']['servings']) && $recipe_settings['nutrition']['servings'] ? $recipe_settings['nutrition']['servings'] : 1 );
            $multiplier = (float)esc_html( get_query_var( 'servings', $recipe_settings['nutrition']['servings'] ) );
            $multiplier = ( !$multiplier ? $default_serving_size : $multiplier );

            if ( !$multiplier || $multiplier == $default_serving_size ) {
                $multiplier = 1;
            } else {
                $multiplier = $multiplier / $default_serving_size;
            }

            if ($multiplier === 1) {
                $amount = ( isset($ing['amount']) && $ing['amount'] ? esc_html( $ing['amount'] ) : false );
                $amount = $Cooked_Measurements->cleanup_amount($amount);
                $format = ( strpos($amount, '/') === false ? ( strpos($amount, '.') !== false || strpos($amount, ',') !== false ? 'decimal' : 'fraction' ) : 'fraction' );
                $float_amount = $Cooked_Measurements->calculate( $amount, 'decimal' );
                $amount = $Cooked_Measurements->format_amount( $float_amount, $format );
            } else {
                $amount = ( isset($ing['amount']) && $ing['amount'] ? esc_html( $ing['amount'] ) : false );
                $amount = $Cooked_Measurements->cleanup_amount($amount);
                $format = ( strpos($amount, '/') === false ? ( strpos($amount, '.') !== false || strpos($amount, ',') !== false ? 'decimal' : 'fraction' ) : 'fraction' );
                $float_amount = $Cooked_Measurements->calculate( $amount, 'decimal' );

                if ($float_amount) {
                    $float_amount = $float_amount * $multiplier;
                    $amount = $Cooked_Measurements->format_amount( $float_amount, $format );
                }
            }

            $measurement = ( isset($ing['measurement']) && $ing['measurement'] ? esc_html( $ing['measurement'] ) : false );
            $measurement = ( $measurement && $float_amount ? $Cooked_Measurements->singular_plural( $measurements[ $measurement ]['singular_abbr'], $measurements[ $measurement ]['plural_abbr'], $float_amount ) : false );

            $name = ( isset($ing['name']) && $ing['name'] ? apply_filters( 'cooked_ingredient_name', wp_kses_post( $ing['name'] ), $ing ) : false );

            // Substitution Logic
            $sub_name = ( isset($ing['sub_name']) && $ing['sub_name'] ?  wp_kses_post( $ing['sub_name'] ) : false );
            $sub_amount = false;
            $sub_measurement = false;
            $sub_float_amount = 0;

            if ( $sub_name ) {
                if ($multiplier === 1) {
                    $sub_amount = ( isset($ing['sub_amount']) && $ing['sub_amount'] ? esc_html( $ing['sub_amount'] ) : false );
                    $sub_amount = $Cooked_Measurements->cleanup_amount($sub_amount);
                    $sub_format = ( strpos($sub_amount, '/') === false ? ( strpos($sub_amount, '.') !== false || strpos($sub_amount, ',') !== false ? 'decimal' : 'fraction' ) : 'fraction' );
                    $sub_float_amount = $Cooked_Measurements->calculate( $sub_amount, 'decimal' );
                    $sub_amount = $Cooked_Measurements->format_amount( $sub_float_amount, $sub_format );
                } else {
                    $sub_amount = ( isset($ing['sub_amount']) && $ing['sub_amount'] ? esc_html( $ing['sub_amount'] ) : false );
                    $sub_amount = $Cooked_Measurements->cleanup_amount($sub_amount);
                    $sub_format = ( strpos($sub_amount, '/') === false ? ( strpos($sub_amount, '.') !== false || strpos($sub_amount, ',') !== false ? 'decimal' : 'fraction' ) : 'fraction' );
                    $sub_float_amount = $Cooked_Measurements->calculate( $sub_amount, 'decimal' );

                    if ($sub_float_amount) {
                        $sub_float_amount = $sub_float_amount * $multiplier;
                        $sub_amount = $Cooked_Measurements->format_amount( $sub_float_amount, $sub_format );
                    }
                }

                $sub_measurement_key = ( isset($ing['sub_measurement']) && $ing['sub_measurement'] ? esc_html( $ing['sub_measurement'] ) : false );
                $sub_measurement = ( $sub_measurement_key && $sub_float_amount && isset($measurements[$sub_measurement_key]) ? $Cooked_Measurements->singular_plural( $measurements[ $sub_measurement_key ]['singular_abbr'], $measurements[ $sub_measurement_key ]['plural_abbr'], $sub_float_amount ) : false );
            }

            if ( $plain_text ) {
                $output = ( $amount ? $amount . ' ' : '' ) . ( $measurement ? $measurement . ' ' : '' ) . ( $name ? $name : '' );
                if ( $sub_name ) {
                    $output .= ' (' . __('or', 'cooked') . ' ' . ( $sub_amount ? $sub_amount . ' ' : '' ) . ( $sub_measurement ? $sub_measurement . ' ' : '' ) . $sub_name . ')';
                }
                return $output;
            } else {
                echo '<div itemprop="recipeIngredient" class="cooked-single-ingredient cooked-ingredient' . ( !$checkboxes ? ' cooked-ing-no-checkbox' : '' ) . '">';
                    echo ( $checkboxes ? '<span class="cooked-ingredient-checkbox">&nbsp;</span>' : '' );
                    do_action( 'cooked_ingredient_after_checkbox', $ing );
                    echo ( $amount ? '<span class="cooked-ing-amount" data-decimal="' . esc_html($float_amount) . '">' . wp_kses_post($amount) . '</span> <span class="cooked-ing-measurement">' . wp_kses_post( $measurement ) . '</span> ' : '' );
                    do_action( 'cooked_ingredient_after_amount', $ing );
                    echo ( $name ? '<span class="cooked-ing-name">' . wp_kses_post( $name ) . '</span>' : '' );
                    do_action( 'cooked_ingredient_after_name', $ing );

                    if ( $sub_name ) {
                        echo '<span class="cooked-ingredient-substitution">';
                            echo ' <span class="cooked-ing-sub-label">' . __('or', 'cooked') . '</span> ';
                            echo ( $sub_amount ? '<span class="cooked-ing-amount" data-decimal="' . esc_html($sub_float_amount) . '">' . wp_kses_post($sub_amount) . '</span> <span class="cooked-ing-measurement">' . wp_kses_post( $sub_measurement ) . '</span> ' : '' );
                            echo '<span class="cooked-ing-name">' . wp_kses_post( $sub_name ) . '</span>';
                        echo '</span>';
                    }
                echo '</div>';
            }
        }

        $ing_html = ob_get_clean();
        echo apply_filters( 'cooked_single_ingredient_html', $ing_html, $ing, $checkboxes, $plain_text );
    }

    public static function single_direction($dir, $number = false, $plain_text = false, $step = false, $atts = false) {
        global $recipe_settings;

        if (isset($dir['section_heading_name']) && $dir['section_heading_name']) {

            if ($plain_text) {
                return $dir['section_heading_name'];
            } else {
                $valid_elements = ['div', 'h2', 'h3', 'h4', 'h5', 'h6'];
                global $_cooked_settings;
                $default_element = isset($_cooked_settings['section_heading_default_html_tag']) ? $_cooked_settings['section_heading_default_html_tag'] : 'div';

                $element = (isset($dir['section_heading_element']) && in_array($dir['section_heading_element'], $valid_elements, true))
                    ? ($dir['section_heading_element'] === 'div' ? $default_element : $dir['section_heading_element'])
                    : $default_element;

                echo '<' . $element . ' class="cooked-single-direction cooked-heading">' . esc_html($dir['section_heading_name']) . '</' . $element . '>';
            }

        } elseif (isset($dir['content']) && $dir['content'] || isset($dir['image']) && $dir['image']) {

            $dir_image_size = apply_filters( 'cooked_direction_image_size', 'large' );
            $image = isset($dir['image']) && $dir['image'] ? wp_get_attachment_image( $dir['image'], $dir_image_size, false, ['title' => esc_attr(get_the_title($dir['image']))] ) : '';
            $content = !empty($dir['content']) ? Cooked_Recipes::format_content($dir['content']) : '';

            $image = apply_filters('cooked_direction_image_html', $image, $atts);

            if ($plain_text) {
                return $content;
            } else {
                /* translators: singular and plural "steps" */
                $step_string = sprintf( __( 'Step %d', 'cooked' ), $step );

                echo '<div id="cooked-single-direction-step-'. $number .'" class="cooked-single-direction cooked-direction' . ($image ? ' cooked-direction-has-image' : '') . ( $number ? ' cooked-direction-has-number' . ( $number > 9 ? '-wide' : '' ) : '' ) . '"' . ( $step ? ' data-step="' . $step_string . '"' : '' ) . '>';
                    echo $number ? '<span class="cooked-direction-number">' . esc_html($number) . '</span>' : '';
                    echo '<div class="cooked-dir-content">' . do_shortcode($content) . ($image ? wpautop($image) : '') . '</div>';
                echo '</div>';
            }
        }
    }

    public static function format_content($content) {
        return wpautop(wp_kses_post(html_entity_decode($content)));
    }

    public static function difficulty_level($level) {
        $level_names = self::difficulty_levels();
        $level_text = isset($level_names[$level]) ? $level_names[$level] : false;
        return $level_text ? '<span class="cooked-difficulty-level-' . esc_attr($level) . '">' . wp_kses_post($level_text) . '</span>' : '';
    }

    public static function recipe_search_box( $options = false ) {
        global $_cooked_settings, $recipe_args, $tax_col_count, $active_taxonomy;

        $tax_col_count = 0;
        $filters_set = [];
        $taxonomy_search_fields = '';

        if ( isset($recipe_args['tax_query']) ):
            foreach ( $recipe_args['tax_query'] as $query ):
                // Only process inclusion queries (default operator or explicit 'IN')
                // Skip exclusion queries ('NOT IN') and other complex operators
                $operator = isset($query['operator']) ? $query['operator'] : 'IN';
                if ( $operator === 'IN' && isset($query['taxonomy']) && isset($query['terms']) ):
                    $filters_set[$query['taxonomy']] = implode( ',', $query['terms'] );
                endif;
            endforeach;

            if ( isset($filters_set) ):
                foreach ( $filters_set as $taxonomy => $filter ):
                    $this_tax = get_term_by( 'slug', $filter, $taxonomy );
                    $this_tax = $this_tax ? $this_tax : get_term_by( 'id', $filter, $taxonomy );
                    if ( $this_tax && !is_wp_error($this_tax) ):
                        $filters_set[$taxonomy] = $this_tax->term_id;
                        $active_taxonomy = !isset($active_taxonomy) ? $this_tax->name : $active_taxonomy;
                    endif;
                endforeach;
            endif;
        endif;

        $total_taxonomies = 0;

        $inline_browse = isset($options['inline_browse']) && $options['inline_browse'] ? true : false;

        ob_start();
        if ( !empty($_cooked_settings['recipe_taxonomies']) ):

            if ( !$inline_browse ):

                echo '<div class="cooked-field-wrap cooked-field-wrap-select' . ( isset($active_taxonomy) ? ' cooked-taxonomy-selected' : '' ) . '">';
                echo '<span class="cooked-browse-select">';
                echo '<span class="cooked-field-title">' . ( isset($active_taxonomy) ? esc_html( $active_taxonomy ) : __('Browse','cooked') ) . '</span>';
                echo '<span class="cooked-browse-select-block cooked-clearfix">';

            endif;

            ob_start();

            do_action( 'cooked_before_search_filter_columns' );

            if ( isset($active_taxonomy) ):
                $recipes_page_id = Cooked_Multilingual::get_browse_page_id();
                $recipes_page_id = $recipes_page_id ? $recipes_page_id : get_the_ID();
                $view_all_recipes_url = get_permalink( $recipes_page_id );
            else:
                $view_all_recipes_url = false;
            endif;

            if ( in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies']) ):
                $terms_array = Cooked_Settings::terms_array( 'cp_recipe_category', false, __('No categories','cooked'), true, true, false );
                if ( !empty($terms_array) ):
                    echo '<span class="cooked-tax-column">';
                        echo '<span class="cooked-tax-column-title">' . __('Categories','cooked') . '</span>';
                        echo '<div class="cooked-tax-scrollable">';
                            echo ( $view_all_recipes_url ? '<a href="' . esc_url( $view_all_recipes_url ) . '">' . __( 'All Categories','cooked' ) . '</a>' : '' );
                            foreach ( $terms_array as $key => $val ):
                                if ( $key ):
                                    $term = get_term( $key );
                                    $term_link = ( !empty($term) ? get_term_link( $term ) : false );
                                    $term_name = apply_filters( 'cooked_term_name', $term->name, $term->ID, $term->taxonomy );
                                    echo ( $term_link ? ( isset($active_taxonomy) && $active_taxonomy == $val ? '<strong><i class="cooked-icon cooked-icon-angle-right"></i>&nbsp;&nbsp;' : '' ) . '<a href="' . esc_url($term_link) . '">' . esc_html($term_name) . '</a>' . ( isset($active_taxonomy) && $active_taxonomy == $val ? '</strong>' : '' ) : '' );
                                    $total_taxonomies++;
                                    $sub_terms_array = Cooked_Settings::terms_array( 'cp_recipe_category', false, false, true, false, $key );
                                    if ( !empty($sub_terms_array) ):
                                        foreach ( $sub_terms_array as $sub_key => $sub_val ):
                                            if ( $sub_key ):
                                                $sub_term = get_term( $sub_key );
                                                $sub_term_link = ( !empty($sub_term) ? get_term_link( $sub_term ) : false );
                                                $sub_term_name = apply_filters( 'cooked_term_name', $sub_term->name, $sub_term->ID, $sub_term->taxonomy );
                                                echo ( $sub_term_link ? '<span class="cooked-tax-sub-item">' . ( isset($active_taxonomy) && $active_taxonomy == $sub_val ? '<strong><i class="cooked-icon cooked-icon-angle-right"></i>&nbsp;&nbsp;' : '' ) . '<a href="' . esc_url($sub_term_link) . '">' . esc_html($sub_term_name) . '</a>' . ( isset($active_taxonomy) && $active_taxonomy == $sub_val ? '</strong>' : '' ) . '</span>' : '' );
                                                $total_taxonomies++;
                                            endif;
                                        endforeach;
                                    endif;
                                endif;
                            endforeach;
                            $tax_col_count++;
                        echo '</div>';
                    echo '</span>';
                endif;
            endif;

            do_action( 'cooked_after_search_filter_columns' );

            $browse_html = ob_get_clean();

            if ( !$inline_browse ):

                echo wp_kses_post( $browse_html );
                echo '</span></span></div>';

            endif;

        endif;

        if ( $total_taxonomies ):
            $taxonomy_search_fields = ob_get_clean();
        else:
            ob_clean();
            $taxonomy_search_fields = false;
        endif;

        $page_id = Cooked_Multilingual::get_browse_page_id();
        $page_id = $page_id ? $page_id : get_the_ID();
        $form_redirect = get_permalink($page_id);

        $cooked_search_s = get_query_var('cooked_search_s', '');
        $cooked_search_s = urldecode($cooked_search_s);
        $cooked_search_s = Cooked_Functions::sanitize_text_field( $cooked_search_s );

        ob_start();

        echo '<section class="cooked-recipe-search cooked-clearfix' . ( isset( $options['compact'] ) && $options['compact'] ? ' cooked-search-compact' : '' ) . ( isset( $options['hide_sorting'] ) && $options['hide_sorting'] ? ' cooked-search-no-sorting' : '' ) . ( isset( $options['hide_browse'] ) && $options['hide_browse'] ? ' cooked-search-no-browse' : '' ) . '">';

            echo '<form action="' . esc_url( $form_redirect ) . '" method="get">';

                echo '<input type="hidden" name="page_id" value="' . intval( $page_id ) . '">';
                if ( isset($recipe_args['tax_query'][0]['taxonomy']) ):
                    echo '<input type="hidden" name="' . esc_attr( $recipe_args['tax_query'][0]['taxonomy'] ) . '" value="' . esc_attr( $recipe_args['tax_query'][0]['terms'][0] ) . '">';
                endif;

                echo '<div class="cooked-fields-wrap cooked-' . esc_attr( $tax_col_count ) . '-search-fields">';

                    echo !$options['hide_browse'] && $taxonomy_search_fields ? $taxonomy_search_fields : '';

                    echo '<input aria-label="' . __('Find a recipe...', 'cooked') . '" class="cooked-browse-search" type="text" name="cooked_search_s" value="' . ( !empty($cooked_search_s) ? $cooked_search_s : '' ) . '" placeholder="' . __('Find a recipe...','cooked') . '" />';

                    echo '<a aria-label="' . __('Search', 'cooked') . '" href="#" class="cooked-browse-search-button"><i class="cooked-icon cooked-icon-search"></i></a>';

                echo '</div>';

                if ( isset( $recipe_args['orderby'] ) && is_array( $recipe_args['orderby'] ) ):
                    $sorting_type = key($recipe_args['orderby']) . '_' . current( $recipe_args['orderby'] );
                else:
                    $sorting_type = ( isset( $recipe_args['orderby'] ) && isset( $recipe_args['order'] ) ? $recipe_args['orderby'] . '_' . $recipe_args['order'] : 'date_desc' );
                endif;

                $sorting_types = apply_filters( 'cooked_browse_sorting_types', [
                    'date_desc' => [
                        'slug' => 'date_desc',
                        'name' => __("Newest first", "cooked")
                    ],
                    'date_asc' => [
                        'slug' => 'date_asc',
                        'name' => __("Oldest first", "cooked")
                    ],
                    'title_asc' => [
                        'slug' => 'title_asc',
                        'name' => __("Alphabetical (A-Z)", "cooked")
                    ],
                    'title_desc' => [
                        'slug' => 'title_desc',
                        'name' => __("Alphabetical (Z-A)", "cooked")
                    ]
                ], $sorting_type );

                if ( !$options['hide_sorting'] ):

                    echo '<span class="cooked-sortby-wrap"><select class="cooked-sortby-select" name="cooked_browse_sort_by">';
                        foreach ( $sorting_types as $value => $type ):
                            echo '<option value="' . esc_attr( $value ) . '"' . ( $sorting_type == $type['slug'] ? ' selected' : '' ) . '>' . esc_attr( $type['name'] ) . '</option>';
                        endforeach;
                    echo '</select></span>';

                endif;

            echo '</form>';

        echo '</section>';

        if ( $inline_browse ):
            echo '<div class="cooked-browse-select-inline-block cooked-clearfix">';
                echo wp_kses_post( $browse_html );
            echo '</div>';
        endif;

        return ob_get_clean();
    }

    public function recipe_template( $content ) {
        global $wp_query, $post, $_cooked_content_unfiltered;

        if ( post_password_required() ):
            return $content;
        endif;

        if ( is_singular('cp_recipe') && is_main_query() && $_cooked_content_unfiltered == false ):
            ob_start();

            include COOKED_DIR . 'templates/front/recipe.php';
            // load_template( COOKED_DIR . 'templates/front/recipe.php', false );

            $recipe_content = ob_get_clean();
            return shortcode_unautop( apply_filters( 'cooked_recipe_content_filter', $recipe_content, $content, $post->ID ) );

        endif;

        return $content;
    }

    /**
     * Cooked Classic 2.x Backwards Compatibility
     *
     * @since 1.0.0
     */

    // Get and return the Cooked 2.x Classic recipe meta information
    public static function get_c2_recipe_meta( $post_id ) {
        $recipe_meta = [];
        $revised_array = [];
        $recipe_cs2_meta = get_post_meta($post_id);

        if ( isset($recipe_cs2_meta['_cp_recipe_ingredients']) ):

            foreach($recipe_cs2_meta as $key => $content):
                $revised_array[$key] = $content[0];
            endforeach;

            $recipe_cs2_meta = $revised_array;

            $recipe_meta['_cp_recipe_title'] = get_the_title( $post_id );
            $recipe_meta['_cp_recipe_ingredients'] = isset($recipe_cs2_meta['_cp_recipe_ingredients']) ? $recipe_cs2_meta['_cp_recipe_ingredients'] : false;
            $recipe_meta['_cp_recipe_detailed_ingredients'] = isset($recipe_cs2_meta['_cp_recipe_detailed_ingredients']) ? $recipe_cs2_meta['_cp_recipe_detailed_ingredients'] : false;
            $recipe_meta['_cp_recipe_directions'] = isset($recipe_cs2_meta['_cp_recipe_directions']) ? $recipe_cs2_meta['_cp_recipe_directions'] : false;
            $recipe_meta['_cp_recipe_detailed_directions'] = isset($recipe_cs2_meta['_cp_recipe_detailed_directions']) ? $recipe_cs2_meta['_cp_recipe_detailed_directions'] : false;
            $recipe_meta['_cp_recipe_external_video'] = isset($recipe_cs2_meta['_cp_recipe_external_video']) ? $recipe_cs2_meta['_cp_recipe_external_video'] : false;
            $recipe_meta['_cp_recipe_short_description'] = isset($recipe_cs2_meta['_cp_recipe_short_description']) ? $recipe_cs2_meta['_cp_recipe_short_description'] : false;
            $recipe_meta['_cp_recipe_excerpt'] = isset($recipe_cs2_meta['_cp_recipe_excerpt']) ? $recipe_cs2_meta['_cp_recipe_excerpt'] : false;
            $recipe_meta['_cp_recipe_difficulty_level'] = isset($recipe_cs2_meta['_cp_recipe_difficulty_level']) ? $recipe_cs2_meta['_cp_recipe_difficulty_level'] : false;
            $recipe_meta['_cp_recipe_prep_time'] = isset($recipe_cs2_meta['_cp_recipe_prep_time']) ? $recipe_cs2_meta['_cp_recipe_prep_time'] : false;
            $recipe_meta['_cp_recipe_cook_time'] = isset($recipe_cs2_meta['_cp_recipe_cook_time']) ? $recipe_cs2_meta['_cp_recipe_cook_time'] : false;
            $recipe_meta['_cp_recipe_additional_notes'] = isset($recipe_cs2_meta['_cp_recipe_additional_notes']) ? $recipe_cs2_meta['_cp_recipe_additional_notes'] : false;
            $recipe_meta['_cp_recipe_admin_rating'] = isset($recipe_cs2_meta['_cp_recipe_admin_rating']) ? $recipe_cs2_meta['_cp_recipe_admin_rating'] : false;
            $recipe_meta['_cp_recipe_yields'] = isset($recipe_cs2_meta['_cp_recipe_yields']) ? $recipe_cs2_meta['_cp_recipe_yields'] : false;
            $recipe_meta['_cp_recipe_nutrition_servingsize'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_servingsize']) ? $recipe_cs2_meta['_cp_recipe_nutrition_servingsize'] : false;
            $recipe_meta['_cp_recipe_nutrition_calories'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_calories']) ? $recipe_cs2_meta['_cp_recipe_nutrition_calories'] : false;
            $recipe_meta['_cp_recipe_nutrition_fat'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_fat']) ? $recipe_cs2_meta['_cp_recipe_nutrition_fat'] : false;
            $recipe_meta['_cp_recipe_nutrition_satfat'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_satfat']) ? $recipe_cs2_meta['_cp_recipe_nutrition_satfat'] : false;
            $recipe_meta['_cp_recipe_nutrition_transfat'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_transfat']) ? $recipe_cs2_meta['_cp_recipe_nutrition_transfat'] : false;
            $recipe_meta['_cp_recipe_nutrition_cholesterol'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_cholesterol']) ? $recipe_cs2_meta['_cp_recipe_nutrition_cholesterol'] : false;
            $recipe_meta['_cp_recipe_nutrition_sodium'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_sodium']) ? $recipe_cs2_meta['_cp_recipe_nutrition_sodium'] : false;
            $recipe_meta['_cp_recipe_nutrition_potassium'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_potassium']) ? $recipe_cs2_meta['_cp_recipe_nutrition_potassium'] : false;
            $recipe_meta['_cp_recipe_nutrition_carbs'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_carbs']) ? $recipe_cs2_meta['_cp_recipe_nutrition_carbs'] : false;
            $recipe_meta['_cp_recipe_nutrition_fiber'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_fiber']) ? $recipe_cs2_meta['_cp_recipe_nutrition_fiber'] : false;
            $recipe_meta['_cp_recipe_nutrition_sugar'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_sugar']) ? $recipe_cs2_meta['_cp_recipe_nutrition_sugar'] : false;
            $recipe_meta['_cp_recipe_nutrition_protein'] = isset($recipe_cs2_meta['_cp_recipe_nutrition_protein']) ? $recipe_cs2_meta['_cp_recipe_nutrition_protein'] : false;

            if ( !$recipe_meta['_cp_recipe_excerpt'] ):
                $recipe_meta['_cp_recipe_excerpt'] = $recipe_meta['_cp_recipe_short_description'];
                $recipe_meta['_cp_recipe_short_description'] = false;
            endif;

            return $recipe_meta;

        endif;

        return [];
    }

    // Sync up the Cooked 2.x Classic recipe meta fields to the new Cooked 3.x meta fields
    public static function sync_c2_recipe_settings( $c2_recipe_settings, $recipe_id ) {
        $recipe_settings = [];
        $ingredients = [];
        $directions = [];

        $recipe_settings['title'] = isset($c2_recipe_settings['_cp_recipe_title']) ? $c2_recipe_settings['_cp_recipe_title'] : '';
        $recipe_settings['content'] = isset($c2_recipe_settings['_cp_recipe_short_description']) ? wpautop( $c2_recipe_settings['_cp_recipe_short_description'] . ($c2_recipe_settings['_cp_recipe_short_description'] ? '<br><br>' : '') . Cooked_Recipes::default_content() . ($c2_recipe_settings['_cp_recipe_additional_notes'] ? '<br><br>' : '') . $c2_recipe_settings['_cp_recipe_additional_notes'] ) : '';
        $recipe_settings['excerpt'] = isset($c2_recipe_settings['_cp_recipe_excerpt']) ? $c2_recipe_settings['_cp_recipe_excerpt'] : '';
        $recipe_settings['difficulty_level'] = isset($c2_recipe_settings['_cp_recipe_difficulty_level']) ? $c2_recipe_settings['_cp_recipe_difficulty_level'] : '';
        $recipe_settings['prep_time'] = isset($c2_recipe_settings['_cp_recipe_prep_time']) ? $c2_recipe_settings['_cp_recipe_prep_time'] : '';
        $recipe_settings['cook_time'] = isset($c2_recipe_settings['_cp_recipe_cook_time']) ? $c2_recipe_settings['_cp_recipe_cook_time'] : '';

        // Ingredients
        if ( !empty($c2_recipe_settings['_cp_recipe_ingredients']) && empty($c2_recipe_settings['_cp_recipe_detailed_ingredients']) ):
            $ingredients = explode("\n", $c2_recipe_settings['_cp_recipe_ingredients']);
        elseif ( !empty($c2_recipe_settings['_cp_recipe_detailed_ingredients']) ):
            $ingredients = unserialize( $c2_recipe_settings['_cp_recipe_detailed_ingredients'] );
        endif;

        if ( !empty($ingredients) ):
            foreach( $ingredients as $ing ):
                $rand_id = wp_rand( 1000000,9999999 );
                if ( isset($ing['type']) && $ing['type'] == 'ingredient' ):
                    $recipe_settings['ingredients'][$rand_id]['amount'] = $ing['amount'];
                    $recipe_settings['ingredients'][$rand_id]['measurement'] = $ing['measurement'];
                    $recipe_settings['ingredients'][$rand_id]['name'] = $ing['name'];
                elseif ( isset($ing['type']) && $ing['type'] == 'section' ):
                    $recipe_settings['ingredients'][$rand_id]['section_heading_name'] = $ing['value'];
                else:
                    if ( substr($ing, 0, 2) == '--' ):
                        $recipe_settings['ingredients'][$rand_id]['section_heading_name'] = substr($ing, 2);
                    else:
                        $recipe_settings['ingredients'][$rand_id]['amount'] = false;
                        $recipe_settings['ingredients'][$rand_id]['measurement'] = false;
                        $recipe_settings['ingredients'][$rand_id]['name'] = $ing;
                    endif;
                endif;
            endforeach;
        endif;

        // Directions
        if ( !empty($c2_recipe_settings['_cp_recipe_directions']) && empty($c2_recipe_settings['_cp_recipe_detailed_directions']) ):
            $directions = explode("\n", $c2_recipe_settings['_cp_recipe_directions']);
        elseif ( !empty($c2_recipe_settings['_cp_recipe_detailed_directions']) ):
            $directions = unserialize( $c2_recipe_settings['_cp_recipe_detailed_directions'] );
        endif;

        if ( !empty($directions) ):

            foreach( $directions as $dir ):
                $rand_id = wp_rand( 1000000,9999999 );
                if ( isset($dir['type']) && $dir['type'] == 'direction' ):
                    $recipe_settings['directions'][$rand_id]['image'] = $dir['image_id'];
                    $recipe_settings['directions'][$rand_id]['content'] = $dir['value'];
                elseif ( isset($dir['type']) && $dir['type'] == 'section' ):
                    $recipe_settings['directions'][$rand_id]['section_heading_name'] = $dir['value'];
                else:
                    if ( substr($dir, 0, 2) == '--' ):
                        $recipe_settings['directions'][$rand_id]['section_heading_name'] = substr($dir, 2);
                    else:
                        $recipe_settings['directions'][$rand_id]['image'] = false;
                        $recipe_settings['directions'][$rand_id]['content'] = $dir;
                    endif;
                endif;
            endforeach;

        endif;

        if ( isset($c2_recipe_settings['_cp_recipe_external_video']) && $c2_recipe_settings['_cp_recipe_external_video'] && wp_oembed_get( $c2_recipe_settings['_cp_recipe_external_video'] ) ):
            $recipe_settings['gallery']['video_url'] = $c2_recipe_settings['_cp_recipe_external_video'];
        else:
            $recipe_settings['gallery']['video_url'] = false;
            $recipe_settings['gallery']['type'] = 'cooked';
        endif;

        $recipe_settings['nutrition']['serving_size'] = ( isset($c2_recipe_settings['_cp_recipe_yields']) && $c2_recipe_settings['_cp_recipe_yields'] ? $c2_recipe_settings['_cp_recipe_yields'] : false );
        $recipe_settings['nutrition']['servings'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_servingsize']) && $c2_recipe_settings['_cp_recipe_nutrition_servingsize'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_servingsize']) : false );
        $recipe_settings['nutrition']['calories'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_calories']) && $c2_recipe_settings['_cp_recipe_nutrition_calories'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_calories']) : false );
        $recipe_settings['nutrition']['fat'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_fat']) && $c2_recipe_settings['_cp_recipe_nutrition_fat'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_fat']) : false );
        $recipe_settings['nutrition']['sat_fat'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_satfat']) && $c2_recipe_settings['_cp_recipe_nutrition_satfat'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_satfat']) : false );
        $recipe_settings['nutrition']['trans_fat'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_transfat']) && $c2_recipe_settings['_cp_recipe_nutrition_transfat'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_transfat']) : false );
        $recipe_settings['nutrition']['cholesterol'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_cholesterol']) && $c2_recipe_settings['_cp_recipe_nutrition_cholesterol'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_cholesterol']) : false );
        $recipe_settings['nutrition']['sodium'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_sodium']) && $c2_recipe_settings['_cp_recipe_nutrition_sodium'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_sodium']) : false );
        $recipe_settings['nutrition']['potassium'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_potassium']) && $c2_recipe_settings['_cp_recipe_nutrition_potassium'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_potassium']) : false );
        $recipe_settings['nutrition']['carbs'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_carbs']) && $c2_recipe_settings['_cp_recipe_nutrition_carbs'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_carbs']) : false );
        $recipe_settings['nutrition']['fiber'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_fiber']) && $c2_recipe_settings['_cp_recipe_nutrition_fiber'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_fiber']) : false );
        $recipe_settings['nutrition']['sugars'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_sugar']) && $c2_recipe_settings['_cp_recipe_nutrition_sugar'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_sugar']) : false );
        $recipe_settings['nutrition']['protein'] = ( isset($c2_recipe_settings['_cp_recipe_nutrition_protein']) && $c2_recipe_settings['_cp_recipe_nutrition_protein'] ? preg_replace("/[^0-9]/","",$c2_recipe_settings['_cp_recipe_nutrition_protein']) : false );

        $_nutrition_facts = Cooked_Measurements::nutrition_facts();
        foreach( $_nutrition_facts as $nutrition_facts ):
            foreach( $nutrition_facts as $slug => $nf ):

                if ( !isset($recipe_settings[$slug]) ): $recipe_settings[$slug]	= false; endif;
                if ( isset($nf['subs']) ):
                    foreach( $nf['subs'] as $sub_slug => $sub_nf ):
                        if ( !isset($recipe_settings[$sub_slug]) ): $recipe_settings[$sub_slug]	= false; endif;
                    endforeach;
                endif;
            endforeach;
        endforeach;

        return apply_filters( 'cooked_sync_c2_recipe_settings', $recipe_settings, $recipe_id );
    }

    public function modify_browse_page_canonical_url($canonical_url, $post = null) {
        global $_cooked_settings, $wp_query;

        if (!is_page()) {
            return $canonical_url;
        }

        $browse_page_id = Cooked_Multilingual::get_browse_page_id();

        // Only modify for browse page with category.
        if (is_page($browse_page_id) &&
            isset($wp_query->query['cp_recipe_category']) &&
            taxonomy_exists('cp_recipe_category') &&
            term_exists($wp_query->query['cp_recipe_category'], 'cp_recipe_category')) {

            // Build the canonical URL based on permalink structure.
            if (get_option('permalink_structure')) {
                $new_canonical = untrailingslashit(get_permalink($browse_page_id)) . '/' . $_cooked_settings['recipe_category_permalink'] . '/' . $wp_query->query['cp_recipe_category'];
            } else {
                $new_canonical = add_query_arg('cp_recipe_category', $wp_query->query['cp_recipe_category'], get_permalink($browse_page_id));
            }

            return $new_canonical;
        }

        return $canonical_url;
    }
}

global $Cooked_Recipes;
$Cooked_Recipes = new Cooked_Recipes();