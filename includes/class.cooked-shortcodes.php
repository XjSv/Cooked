<?php
/**
 * Cooked Shortcodes
 *
 * @package     Cooked
 * @subpackage  Shortcodes
 * @since       1.0.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Shortcodes Class
 *
 * This class handles the settings creation and contains functions for retreiving those settings.
 *
 * @since 1.0.0
 */

class Cooked_Shortcodes {

    function __construct() {
        // Allow shortcodes in widgets
        add_filter( 'widget_text', 'do_shortcode' );

        // Site-Wide
        add_shortcode('cooked-browse', [$this, 'cooked_browse_shortcode'] );
        add_shortcode('cooked-search', [$this, 'cooked_search_shortcode'] );
        add_shortcode('cooked-recipe', [$this, 'cooked_recipe_shortcode'] );
        add_shortcode('cooked-categories', [$this, 'cooked_categories_shortcode'] );
        add_shortcode('cooked-recipe-list', [$this, 'cooked_recipe_list_shortcode'] );
        add_shortcode('cooked-recipe-card', [$this, 'cooked_recipe_card_shortcode'] );
        add_shortcode('cooked-related-recipes', [$this, 'cooked_related_recipes_shortcode'] );

        if ( shortcode_exists( 'timer' ) ):
            add_shortcode('cooked-timer', [$this, 'cooked_timer'] );
        else:
            add_shortcode('cooked-timer', [$this, 'cooked_timer'] );
            add_shortcode('timer', [$this, 'cooked_timer'] );
        endif;

        // Recipe-Only
        add_shortcode('cooked-title', [$this, 'cooked_title_shortcode'] );
        add_shortcode('cooked-gallery', [$this, 'cooked_gallery_shortcode'] );
        add_shortcode('cooked-image', [$this, 'cooked_image_shortcode'] );
        add_shortcode('cooked-info', [$this, 'cooked_info_shortcode'] );
        add_shortcode('cooked-excerpt', [$this, 'cooked_excerpt_shortcode'] );
        add_shortcode('cooked-notes', [$this, 'cooked_notes_shortcode'] );
        add_shortcode('cooked-ingredients', [$this, 'cooked_ingredients_shortcode'] );
        add_shortcode('cooked-directions', [$this, 'cooked_directions_shortcode'] );
        add_shortcode('cooked-nutrition', [$this, 'cooked_nutrition_shortcode'] );

        // Add preprocessing filter for shortcodes for Elementor, Divi, and other builders compatibility.
        add_filter('pre_do_shortcode_tag', [$this, 'preprocess_shortcode'], 10, 4);
    }

    public function preprocess_shortcode($output, $tag, $attr, $m) {
        // Tags to skip
        $skip_tags = [
            'cooked-search',
            'cooked-browse',
            'cooked-timer',
            'cooked-recipe',
            'cooked-recipe-list',
            'cooked-recipe-card',
        ];

        // Only process for Cooked shortcodes
        if (is_front_page() || strpos($tag, 'cooked-') === false || in_array($tag, $skip_tags)) {
            return $output;
        }

        global $recipe_settings, $post;

        // If recipe settings are empty, try to get them.
        if (empty($recipe_settings)) {
            // Try to get recipe settings from current post.
            $post_id = isset($post->ID) ? $post->ID : false;
            if ($post_id && get_post_type( $post_id ) === 'cp_recipe') {
                $recipe_settings = Cooked_Recipes::get($post_id, true);
            } else {
                // We are in the editor but not on a recipe post type. Maybe a single recipe template?
                // Uses the first recipe found in the database as a sample.
                $recipe_settings = Cooked_Recipes::get(false, true, false, 1);
            }

            // If still empty and we have a specific recipe ID in attributes, try to get them.
            if (empty($recipe_settings) && isset($attr['id'])) {
                $recipe_settings = Cooked_Recipes::get(intval($attr['id']), true);
            }
        }

        return $output;
    }

    public function cooked_search_shortcode( $atts, $content = null ) {
        // Shortcode Attributes
        $options = shortcode_atts(
            [
                'compact' => false,
                'hide_browse' => false,
                'hide_sorting' => false,
                'inline_browse' => false,
            ], $atts
        );

        return Cooked_Recipes::recipe_search_box( $options );
    }

    public function cooked_timer( $atts, $content = null ) {
        global $cooked_timer_identifier;

        // Shortcode Attributes
        $atts = shortcode_atts(
            [
                'seconds' => 0,
                'minutes' => 0,
                'length' => 0, // Deprecated, left here for backwards compatibility with 2.x
                'hours' => 0,
                'desc' => ''
            ], $atts
        );

        $desc = esc_attr($atts['desc']);
        $seconds = $atts['seconds'];
        $minutes = $atts['minutes'] ? $atts['minutes'] * 60 : $atts['length'] * 60;
        $hours = $atts['hours'] * 60 * 60;
        $seconds = $seconds + $minutes + $hours;

        if (!$cooked_timer_identifier) {
            $cooked_timer_identifier = 1;
        } else {
            $cooked_timer_identifier++;
        }

        $timer_id = md5( $seconds . $desc . $content ) . '_' . $cooked_timer_identifier;
        $desc = $desc ? wp_strip_all_tags( $desc ) : wp_strip_all_tags( $content );

        wp_enqueue_script( 'cooked-timer' );

        return '<span class="cooked-timer"><a aria-label="' . esc_attr( $desc ) . '" data-timer-id="' . esc_attr( $timer_id ) . '" data-seconds="' . esc_attr( $seconds ) . '" data-desc="' . ( $desc ) . '"><i class="cooked-icon cooked-icon-clock"></i> ' . wp_kses_post( $content ) . '</a></span>';
    }

    public function cooked_browse_shortcode( $sc_atts, $content = null ) {
        global $_cooked_settings;

        if ( isset($_cooked_settings['advanced']) && !empty($_cooked_settings['advanced']) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'] ) ) {
            /* translators: referring to the bottom of the Settings page. */
            return current_user_can( 'edit_cooked_settings' ) ? wpautop( sprintf( __('Public recipes are currently disabled. You can change this at the bottom of the %s page.','cooked'), '<a href="' . trailingslashit( admin_url() ) . 'admin.php?page=cooked_settings" target="_blank">' . __( 'Settings', 'cooked' ) . '</a>' ) ) : false;
        }

        if ( is_admin() ) return false;

        $author_query_var = sanitize_key( get_query_var( 'recipe_author', false ) );
        if ( !$author_query_var && isset( $_GET['recipe_author'] ) ) {
            $author_query_var = sanitize_key( $_GET['recipe_author'] );
        }

        // Shortcode Attributes
        $atts = shortcode_atts( apply_filters( 'cooked_browse_shortcode_default_attributes', [
            'category' => false,
            'order' => false,
            'orderby' => false,
            'show' => false,
            'search' => 'true',
            'pagination' => 'true',
            'columns' => 3,
            'layout' => false,
            'author' => $author_query_var,
            'compact' => false,
            'hide_browse' => false,
            'hide_sorting' => false,
            'exclude' => false,
            'inline_browse' => false,
            'hide_excerpt' => false,
        ] ), $sc_atts);

        return Cooked_Recipes::list_view( $atts );
    }

    public function cooked_recipe_card_shortcode( $atts, $content = null ) {
        if ( is_admin() ) return false;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'id' => false,
            'category' => false,
            'width' => false,
            'style' => false,
            'hide_total' => false,
            'hide_image' => false,
            'hide_title' => false,
            'hide_excerpt' => false,
            'hide_author' => false
        ], $atts);

        $recipe_id = intval( $atts['id'] );
        $category_id = intval( $atts['category'] );
        $width = Cooked_Functions::sanitize_text_field( $atts['width'] );
        $style = Cooked_Functions::sanitize_text_field( $atts['style'] );
        $hide_image = Cooked_Functions::sanitize_text_field( $atts['hide_image'] );
        $hide_total = Cooked_Functions::sanitize_text_field( $atts['hide_total'] );
        $hide_title = Cooked_Functions::sanitize_text_field( $atts['hide_title'] );
        $hide_excerpt = Cooked_Functions::sanitize_text_field( $atts['hide_excerpt'] );
        $hide_author = Cooked_Functions::sanitize_text_field( $atts['hide_author'] );

        ob_start();

        if ( $recipe_id ) {
            echo Cooked_Recipes::card( $recipe_id, $width, $hide_image, $hide_title, $hide_excerpt, $hide_author, $style );
        } elseif ( $category_id ) {
            echo Cooked_Taxonomies::card( $category_id, $width, $hide_image, $hide_total, $style );
        }

        return ob_get_clean();
    }

    public function cooked_categories_shortcode( $atts, $content = null ) {
        if ( is_admin() ) return false;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'hide_empty' => true,
            'child_of' => false,
            'style' => 'block'
        ], $atts);

        $hide_empty = Cooked_Functions::sanitize_text_field( $atts['hide_empty'] );
        $child_of = Cooked_Functions::sanitize_text_field( $atts['child_of'] );
        $style = Cooked_Functions::sanitize_text_field( $atts['style'] );
        $parents_only = $child_of ? false : true;

        ob_start();

        $categories_array = Cooked_Settings::terms_array( 'cp_recipe_category', false, false, $hide_empty, $parents_only, $child_of );
        if ( !empty($categories_array) ) {
            echo $style == 'list' ? '<div class="cooked-recipe-term-list">' : '<div class="cooked-recipe-term-grid cooked-clearfix">';
                foreach ( $categories_array as $key => $val ) {
                    Cooked_Taxonomies::single_taxonomy_block( $key, $style );
                }
            echo '</div>';
        }

        return ob_get_clean();
    }

    public function cooked_recipe_list_shortcode( $atts, $content = null ) {
        if ( is_admin() ) return false;

        // Shortcode Attributes
        $atts = shortcode_atts( [
            'orderby' => 'date',
            'width' => '100%',
            'recipes' => false,
            'show' => 5,
            'hide_image' => false,
            'hide_author' => false
        ], $atts);

        $recipes = Cooked_Functions::sanitize_text_field( $atts['recipes'] );
        $orderby = Cooked_Functions::sanitize_text_field( $atts['orderby'] );
        $show = Cooked_Functions::sanitize_text_field( $atts['show'] );
        $width = Cooked_Functions::sanitize_text_field( $atts['width'] );
        $hide_image = Cooked_Functions::sanitize_text_field( $atts['hide_image'] );
        $hide_author = Cooked_Functions::sanitize_text_field( $atts['hide_author'] );

        if ( $recipes ) {
            $recipes = preg_replace( '/\s+/', '', $recipes );
            $recipes = explode( ',', $recipes );
        }

        ob_start();

        Cooked_Recipes::recipe_list( $orderby, $show, $recipes, $width, $hide_image, $hide_author );

        return ob_get_clean();
    }

    public function cooked_recipe_shortcode( $atts, $content = null ) {
        if ( is_admin() ) return false;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'id' => false,
        ], $atts);

        global $recipe_settings, $_cooked_content_unfiltered;

        ob_start();

        $recipe_id = intval( $atts['id'] );

        if ( $recipe_id ) {

            $recipe_settings = Cooked_Recipes::get( $recipe_id, true );
            if ( !$recipe_settings || $recipe_settings && empty( $recipe_settings ) ) {
                return wpautop( '<strong>[cooked-recipe id="' . intval( $recipe_id ) . '"]</strong><br><em>' . __( '(recipe not found or in draft status)', 'cooked' ) . '</em>' );
            } else {
                load_template( COOKED_DIR . 'templates/front/recipe.php', false );
            }

        }

        return do_shortcode( ob_get_clean() );
    }

    public function cooked_gallery_shortcode($atts, $content = null) {
        global $recipe_settings;

        if ( isset($recipe_settings['gallery']) && isset($recipe_settings['gallery']['type']) && $recipe_settings['gallery']['type'] == 'cooked' ) {
            $gallery_options = apply_filters( 'cooked_recipe_gallery_options', [
                'data-fit' => 'cover',
                'data-nav' => 'dots',
                'data-width' => '100%',
                'data-loop' => 'true',
                'data-allowfullscreen' => 'true',
                'data-ratio' => '800/600',
                'data-thumbmargin' => '10',
                'data-thumbborderwidth' => '5',
                'data-swipe' => 'true',
                'data-thumbheight' => '75',
                'data-thumbwidth' => '75'
            ]);

            // Generate the Shortcode Attributes
            foreach ( $gallery_options as $opt_name => $opt_value ) {
                $name_sans_data = str_replace( 'data-', '', $opt_name );
                $gallery_atts[$name_sans_data] = $opt_value;
            }

            // Set the Shortcode Attributes
            $atts = shortcode_atts(
                $gallery_atts, $atts
            );

            // Check for Shortcode Attribute Customizations
            foreach ( $gallery_options as $opt_name => $opt_value ) {
                $name_sans_data = str_replace( 'data-', '', $opt_name );
                if ( isset($atts[$name_sans_data]) && $atts[$name_sans_data] ) {
                    $gallery_options[$opt_name] = $atts[$name_sans_data];
                }
            }
        } else {
            $atts = [];
        }

        if ( isset($recipe_settings['gallery']) && isset($recipe_settings['gallery']['type']) ):

            switch( $recipe_settings['gallery']['type'] ):

                case 'cooked':

                    if ( isset($recipe_settings['gallery']['items']) && !empty($recipe_settings['gallery']['items']) || isset($recipe_settings['gallery']['video_url']) && $recipe_settings['gallery']['video_url'] ):

                        // Gallery Options
                        // Developers: Filter these to change!
                        // Full list here: http://fotorama.io/customize/options/

                        $gallery_options_array = [];

                        foreach ( $gallery_options as $data_key => $data_val ):
                            $gallery_options_array[] = $data_key . '="' . $data_val . '"';
                        endforeach;

                        $gallery_html = '<div class="cooked-recipe-gallery"' . ( !empty($gallery_options_array) ? ' ' . implode( ' ', $gallery_options_array ) : '' ) . '>';

                        $cooked_gallery_video_last_option = apply_filters( 'cooked_gallery_video_last_option', false );

                        if ( !$cooked_gallery_video_last_option && isset($recipe_settings['gallery']['video_url']) && $recipe_settings['gallery']['video_url'] ):
                            $gallery_html .= '<a href="' . esc_url($recipe_settings['gallery']['video_url']) . '" data-caption="' . esc_attr($recipe_settings['title']) . '">' . esc_html($recipe_settings['title']) . '</a>';
                        endif;

                        $gallery_items = ( isset($recipe_settings['gallery']['items']) && !empty($recipe_settings['gallery']['items']) ? apply_filters( 'cooked_gallery_items_output', $recipe_settings['gallery']['items'] ) : [] );

                        if ( isset($gallery_items) && !empty($gallery_items) ) {
                            foreach ( $gallery_items as $item ) {
                                $image_src = wp_get_attachment_image_src( $item, [900, 900] );
                                $image_title = get_the_title( $item );
                                if ( is_array($image_src) && isset($image_src[0]) ) {
                                    $gallery_html .= '<a href="' . esc_url( $image_src[0] ) . '" data-alt="'.esc_attr( $image_title ).'" data-caption="'.esc_attr( $image_title ).'">
                                        <img alt="'.esc_attr( $image_title ).'" src="' . wp_get_attachment_image_url( $item, 'thumbnail' ) . '" />
                                    </a>';
                                }
                            }
                        }

                        if ( $cooked_gallery_video_last_option && isset($recipe_settings['gallery']['video_url']) && $recipe_settings['gallery']['video_url'] ):
                            $gallery_html .= '<a href="' . esc_url($recipe_settings['gallery']['video_url']) . '" data-caption="' . esc_attr($recipe_settings['title']) . '">' . esc_html($recipe_settings['title']) . '</a>';
                        endif;

                        // Enqueue Gallery Styles & Scripts
                        wp_enqueue_style( 'cooked-fotorama' );
                        wp_enqueue_script( 'cooked-fotorama' );

                        $gallery_html .= '</div>';
                        return wp_kses_post( $gallery_html );

                    endif;

                break;

                case 'envira':
                    return ( $recipe_settings['gallery']['envira'] ? '<div class="cooked-recipe-gallery-envira">' . do_shortcode('[envira-gallery id="' . intval( $recipe_settings['gallery']['envira'] ) . '"]') . '</div>' : '' );
                break;

                case 'soliloquy':
                    return ( $recipe_settings['gallery']['soliloquy'] ? '<div class="cooked-recipe-gallery-soliloquy">' . do_shortcode('[soliloquy id="' . intval( $recipe_settings['gallery']['soliloquy'] ) . '"]') . '</div>' : '' );
                break;

                case 'revslider':
                    return ( $recipe_settings['gallery']['revslider'] ? '<div class="cooked-recipe-gallery-revslider">' . do_shortcode('[rev_slider alias="' . intval( $recipe_settings['gallery']['revslider'] ) . '"]') . '</div>' : '' );
                break;

            endswitch;

        endif;

        return false;
    }

    public function cooked_info_shortcode($atts, $content = null) {
        global $recipe, $recipe_settings, $_cooked_settings;

        if ( !isset($recipe_settings['id']) && isset($recipe->ID) ):
            $recipe_settings['id'] = $recipe->ID;
        elseif ( !isset($recipe_settings['id']) ):
            global $post;
            $recipe_settings['id'] = $post->ID;
        endif;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'left' => false,
            'right' => false,
            'include' => false,
            'exclude' => false,
        ], $atts);

        $left = $atts['left'] ? array_map( 'trim', explode( ',', Cooked_Functions::sanitize_text_field($atts['left']) ) ) : false;
        $right = $atts['right'] ? array_map( 'trim', explode( ',', Cooked_Functions::sanitize_text_field($atts['right']) ) ) : false;
        $include = $atts['include'] ? array_map( 'trim', explode( ',', Cooked_Functions::sanitize_text_field($atts['include']) ) ) : false;
        $exclude = $atts['exclude'] ? array_map( 'trim', explode( ',', Cooked_Functions::sanitize_text_field($atts['exclude']) ) ) : false;

        $default_info_array = apply_filters( 'cooked_default_info_array', [
            'author' => __('Author', 'cooked'),
            'difficulty_level' => __('Difficulty', 'cooked'),
            'servings' => __('Yields', 'cooked'),
            'prep_time' => __('Prep Time', 'cooked'),
            'cook_time' => __('Cook Time', 'cooked'),
            'total_time' => __('Total Time', 'cooked'),
            'taxonomies' => !empty($_cooked_settings['recipe_taxonomies']) ? $_cooked_settings['recipe_taxonomies'] : []
        ]);

        if ( $left ):
            // Left Content
            foreach( $left as $val ):
                $info_array['left'][$val] = isset( $default_info_array[$val] ) ? $default_info_array[$val] : '';
            endforeach;
            if ( $right ):
                // Right Content
                foreach( $right as $val ):
                    $info_array['right'][$val] = isset( $default_info_array[$val] ) ? $default_info_array[$val] : '';
                endforeach;
            endif;
        elseif ( $right ):
            // Right Content
            foreach( $right as $val ):
                $info_array['right'][$val] = isset( $default_info_array[$val] ) ? $default_info_array[$val] : '';
            endforeach;
        elseif ( $include ):
            // Include Content (left)
            foreach( $include as $val ):
                $info_array[$val] = isset( $default_info_array[$val] ) ? $default_info_array[$val] : '';
            endforeach;
        elseif ( $exclude ):
            // Exclude Content (left)
            $info_array = $default_info_array;
            foreach( $exclude as $val ):
                unset($info_array[$val]);
            endforeach;
        else:
            $info_array = $default_info_array;
        endif;

        ob_start();

        if ( !empty( $info_array ) ):

            $available_methods = apply_filters( 'cooked_available_info_shortcode_methods', [
                'cooked_info_author' => 'Cooked_Recipes',
                'cooked_info_difficulty' => 'Cooked_Recipes',
                'cooked_info_servings' => 'Cooked_Recipes',
                'cooked_info_print' => 'Cooked_Recipes',
                'cooked_info_fullscreen' => 'Cooked_Recipes',
                'cooked_info_prep_time' => 'Cooked_Recipes',
                'cooked_info_cook_time' => 'Cooked_Recipes',
                'cooked_info_total_time' => 'Cooked_Recipes',
                'cooked_info_taxonomies' => 'Cooked_Recipes'
            ]);

            if ( isset($info_array['left']) && !empty($info_array['left']) ):
                echo '<section class="cooked-left">';
                    foreach( $info_array['left'] as $name => $val ):
                        $function = 'cooked_info_' . $name;
                        if ( array_key_exists( $function, $available_methods ) ):
                            $class = ( $available_methods[$function] == 'Cooked_Recipes' ? $this : $available_methods[$function] );
                            if ( method_exists( $class, $function ) ):
                                $class::$function( $recipe_settings );
                            endif;
                        endif;
                    endforeach;
                echo '</section>';

                if ( isset($info_array['right']) && !empty($info_array['right']) ):
                    echo '<section class="cooked-right">';
                        foreach( $info_array['right'] as $name => $val ):
                            $function = 'cooked_info_' . $name;
                            if ( array_key_exists( $function, $available_methods ) ):
                                $class = ( $available_methods[$function] == 'Cooked_Recipes' ? $this : $available_methods[$function] );
                                if ( method_exists( $class, $function ) ):
                                    $class::$function( $recipe_settings );
                                endif;
                            endif;
                        endforeach;
                    echo '</section>';
                endif;

            elseif ( isset($info_array['right']) && !empty($info_array['right']) ):

                echo '<section class="cooked-right">';
                    foreach ( $info_array['right'] as $name => $val ):
                        $function = 'cooked_info_' . $name;
                        if ( array_key_exists( $function, $available_methods ) ):
                            $class = ( $available_methods[$function] == 'Cooked_Recipes' ? $this : $available_methods[$function] );
                            if ( method_exists( $class, $function ) ):
                                $class::$function( $recipe_settings );
                            endif;
                        endif;
                    endforeach;
                echo '</section>';

            else:

                foreach ( $info_array as $name => $val ):
                    $function = 'cooked_info_' . $name;
                    if ( array_key_exists( $function, $available_methods ) ):
                        $class = ( $available_methods[$function] == 'Cooked_Recipes' ? $this : $available_methods[$function] );
                        if ( method_exists( $class, $function ) ):
                            $class::$function( $recipe_settings );
                        endif;
                    endif;
                endforeach;

            endif;

        endif;

        $cooked_info_html = ob_get_clean();

        if ( $cooked_info_html ):
            add_filter('wp_kses_allowed_html', [$this, 'cooked_kses_servings_switcher']);
            add_filter('wp_kses_allowed_html', [$this, 'cooked_kses_cooked_donut']);
            $cooked_info_html = '<div class="cooked-recipe-info cooked-clearfix">' . wp_kses_post( $cooked_info_html ) . '</div>';
            $cooked_info_html = apply_filters( 'cooked_info_shortcode_output', $cooked_info_html, $recipe_settings );
            return $cooked_info_html;
            //return '<div class="cooked-recipe-info cooked-clearfix">' . $cooked_info_html . '</div>'; // @TODO: Fix this
        endif;
    }

    function cooked_kses_servings_switcher($tags) {
        $tags['select'] = [
            'name' => true,
            'id' => true,
            'class' => true,
        ];
        $tags['option'] = [
            'value' => true,
            'selected' => true
        ];
        return $tags;
    }

    function cooked_kses_cooked_donut($tags) {
        $tags['script'] = [
            'type' => true
        ];
        return $tags;
    }

    public static function cooked_info_author() {
        global $recipe_settings, $_cooked_settings;

        if (in_array('author', $_cooked_settings['recipe_info_display_options'])) {
            $browse_page_id = Cooked_Multilingual::get_browse_page_id();
            $front_page_id = get_option( 'page_on_front' );
            $browse_page_url = $browse_page_id ? get_permalink( $browse_page_id ) : false;
            $author = !empty($recipe_settings['author']) ? $recipe_settings['author'] : false;

            if ( !empty($author['id']) && !empty($browse_page_id) ) {
                // Generate author slug from user_nicename, fallback to user ID if nicename is empty
                if ( !empty($author['user_nicename']) ) {
                    $author_slug = urlencode(sanitize_title($author['user_nicename']));
                } else {
                    // Fallback to user ID if user_nicename is empty or missing
                    $author_slug = $author['id'];
                }

                $permalink = $front_page_id != $browse_page_id && get_option('permalink_structure') ?
                                esc_url( untrailingslashit( $browse_page_url ) . '/' . $_cooked_settings['recipe_author_permalink'] . '/' . trailingslashit( $author_slug ) ) :
                                esc_url( trailingslashit( get_home_url() ) . 'index.php?page_id=' . $browse_page_id . '&recipe_author=' . $author['id'] );

                $permalink = apply_filters( 'cooked_author_permalink', $permalink, $author['id'], $author_slug  );
            } else {
                $permalink = false;
            }

            $author_links = isset( $_cooked_settings['disable_author_links'][0] ) && $_cooked_settings['disable_author_links'][0] == 'disabled' ? false : true;
            $clickable = isset($_cooked_settings['advanced']) && !empty($_cooked_settings['advanced']) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'] ) || !$author_links ? false : true;
            $hide_avatars = isset( $_cooked_settings['hide_author_avatars'][0] ) && $_cooked_settings['hide_author_avatars'][0] == 'hidden' ? true : false;

            echo '<span class="cooked-author' . ( $hide_avatars ? ' cooked-no-avatar' : '' ) . '">';
                echo !$hide_avatars ? '<span class="cooked-author-avatar">' . ( !empty($author) ? wp_kses_post( $author['profile_photo'] ) : '' ) . '</span>' : '';
                echo '<strong class="cooked-meta-title">' . __('Author', 'cooked') . '</strong>' . ( $clickable && $permalink ? '<a href="' . esc_url( $permalink ) . '">' : '' ) . (!empty($author) ? $author['name'] : '') . ( $clickable && $permalink ? '</a>' : '' );
            echo '</span>';

            wp_reset_postdata();
        }
    }

    public static function cooked_info_difficulty( $recipe ) {
        global $_cooked_settings;

        if (in_array('difficulty_level', $_cooked_settings['recipe_info_display_options']) && isset($recipe['difficulty_level']) && $recipe['difficulty_level']) {
            $dl_html = '<span class="cooked-difficulty-level"><strong class="cooked-meta-title">' . __('Difficulty','cooked') . '</strong>' . Cooked_Recipes::difficulty_level( $recipe['difficulty_level'] ) . '</span>';
            echo apply_filters( 'cooked_show_difficulty_level', $dl_html, $recipe['difficulty_level'] );
        }
    }

    public static function cooked_info_servings( $recipe ) {
        global $_cooked_settings;

        if (in_array('servings', $_cooked_settings['recipe_info_display_options'])) {
            $servings = isset($recipe['nutrition']['servings']) && $recipe['nutrition']['servings'] ? $recipe['nutrition']['servings'] : 1;
            Cooked_Recipes::serving_size_switcher( $servings );
        }
    }

    public static function cooked_info_print() {
        global $recipe_settings, $_cooked_settings;

        $recipe_post_url = get_permalink( $recipe_settings['id'] );
        $query_args['print'] = 1;
        $servings = (float)esc_html( get_query_var( 'servings', false ) );
        $query_args['servings'] = !empty($servings) ? $servings : false;
        echo '<span class="cooked-print"><a aria-label="' . __('Print', 'cooked') . '" target="_blank" rel="nofollow" href="' . add_query_arg( $query_args, $recipe_post_url ) . '" class="cooked-print-icon"><i class="cooked-icon cooked-icon-print"></i></a></span>';
    }

    public static function cooked_info_fullscreen() {
        global $recipe_settings, $_cooked_settings;

        echo '<span aria-label="' . __('Fullscreen', 'cooked') . '" role="button" class="cooked-fsm-button" data-recipe-id="' . esc_attr( $recipe_settings['id'] ) . '"><i class="cooked-icon cooked-icon-fullscreen"></i></span>';
        wp_enqueue_script('cooked-nosleep');
    }

    public static function cooked_info_prep_time( $recipe ) {
        global $_cooked_settings;

        if (!empty($_cooked_settings['recipe_info_display_options']) && in_array('timing_prep',$_cooked_settings['recipe_info_display_options'])) {
            $prep_time = isset($recipe['prep_time']) ? esc_html( $recipe['prep_time'] ) : 0;
            echo $prep_time ? '<span class="cooked-prep-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . __('Prep Time','cooked') . '</strong>' . Cooked_Measurements::time_format( $prep_time ) . '</span>' : '';
        }
    }

    public static function cooked_info_cook_time( $recipe ) {
        global $_cooked_settings;

        if (!empty($_cooked_settings['recipe_info_display_options']) && in_array('timing_cook', $_cooked_settings['recipe_info_display_options'])) {
            $cook_time = isset($recipe['cook_time']) ? esc_html( $recipe['cook_time'] ) : 0;
            echo $cook_time ? '<span class="cooked-cook-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . __('Cook Time','cooked') . '</strong>' . Cooked_Measurements::time_format( $cook_time ) . '</span>' : '';
        }
    }

    public static function cooked_info_total_time( $recipe ) {
        global $_cooked_settings;

        if (!empty($_cooked_settings['recipe_info_display_options']) && in_array('timing_total',$_cooked_settings['recipe_info_display_options'])) {
            $total_time = isset($recipe['total_time']) ? esc_html( $recipe['total_time'] ) : 0;

            if ( $total_time ) {
                echo $total_time ? '<span class="cooked-total-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . __('Total Time','cooked') . '</strong>' . Cooked_Measurements::time_format( $total_time ) . '</span>' : '';
            } else {
                $prep_time = isset($recipe['prep_time']) ? esc_html( $recipe['prep_time'] ) : 0;
                $cook_time = isset($recipe['cook_time']) ? esc_html( $recipe['cook_time'] ) : 0;

                if ( $prep_time && $cook_time ) {
                    $total_time = $prep_time + $cook_time;
                    echo $total_time ? '<span class="cooked-total-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . __('Total Time','cooked') . '</strong>' . Cooked_Measurements::time_format( $total_time ) . '</span>' : '';
                }
            }
        }
    }

    public static function cooked_info_taxonomies() {
        global $recipe_settings, $_cooked_settings, $clickable;

        $clickable = isset($_cooked_settings['advanced']) && !empty($_cooked_settings['advanced']) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'] ) ? false : true;

        if (!empty($_cooked_settings['recipe_info_display_options']) && in_array('taxonomies', $_cooked_settings['recipe_info_display_options'])):

            global $recipe_terms_list;
            $recipe_terms_list = '';

            do_action( 'cooked_info_taxonomies_shortcode_before', $recipe_settings );

            if (!empty($_cooked_settings['recipe_taxonomies']) && in_array('cp_recipe_category', $_cooked_settings['recipe_taxonomies'])):
                if ( $clickable ):
                    $recipe_terms_list .= get_the_term_list( $recipe_settings['id'], 'cp_recipe_category', '<span class="cooked-taxonomy cooked-category"><strong class="cooked-meta-title">' . __('Category','cooked') . '</strong>', ', ', '</span>' );
                else:
                    $_recipe_terms_array = [];
                    $recipe_terms_list .= '<span class="cooked-taxonomy cooked-category"><strong class="cooked-meta-title">' . __('Category','cooked') . '</strong>';
                        $recipe_terms_array = wp_get_object_terms( $recipe_settings['id'], 'cp_recipe_category' );
                        if ( !empty($recipe_terms_array) ):
                            if ( ! is_wp_error( $recipe_terms_array ) ):
                                foreach( $recipe_terms_array as $recipe_term ):
                                    $_recipe_terms_array[] = esc_html( $recipe_term->name );
                                endforeach;
                            endif;
                        endif;
                        $recipe_terms_list .= implode( ', ', $_recipe_terms_array );
                    $recipe_terms_list .= '</span>';
                endif;
            endif;

            do_action( 'cooked_info_taxonomies_shortcode_after', $recipe_settings );

            if ( $recipe_terms_list ):
                echo wp_filter_post_kses( $recipe_terms_list );
            endif;

        endif;
    }

    public function cooked_excerpt_shortcode($atts, $content = null) {
        global $_cooked_settings, $recipe_settings;

        if (isset($_cooked_settings['recipe_info_display_options']) && is_array($_cooked_settings['recipe_info_display_options']) && in_array('excerpt', $_cooked_settings['recipe_info_display_options'])) {
            ob_start();

            if (isset($recipe_settings['excerpt']) && $recipe_settings['excerpt']) {
                $excerpt = Cooked_Recipes::format_content($recipe_settings['excerpt']);
                echo '<div class="cooked-recipe-excerpt cooked-clearfix">' . do_shortcode($excerpt) . '</div>';
            }

            return ob_get_clean();
        }
    }

    public function cooked_notes_shortcode($atts, $content = null) {
        global $_cooked_settings, $recipe_settings;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'show_header' => false,
        ], $atts);

        $show_header = Cooked_Functions::sanitize_text_field($atts['show_header']);
        $show_header = !$show_header || $show_header == 'false' ? false : $show_header;

        if (isset($_cooked_settings['recipe_info_display_options']) && is_array($_cooked_settings['recipe_info_display_options']) && in_array('notes', $_cooked_settings['recipe_info_display_options'])) {
            ob_start();

            if (isset($recipe_settings['notes']) && !empty($recipe_settings['notes'])) {
                $notes = Cooked_Recipes::format_content($recipe_settings['notes']);
                $show_header = $show_header ? '<div class="cooked-heading">' . __('Notes', 'cooked') . '</div>' : '';

                echo '<div class="cooked-recipe-notes cooked-clearfix">';
                echo $show_header;
                echo do_shortcode($notes);
                echo '</div>';
            }

            return ob_get_clean();
        }
    }

    public function cooked_title_shortcode($atts, $content = null) {
        global $recipe, $recipe_settings;

        if (!isset($recipe_settings['id'])) {
            $recipe_settings['id'] = $recipe->ID;
        }

        return get_the_title($recipe_settings['id']);
    }

    public function cooked_image_shortcode($atts, $content = null) {
        global $recipe, $recipe_settings;

        if ( !isset($recipe_settings['id']) ):
            $recipe_settings['id'] = $recipe->ID;
        endif;

        $recipe = get_post( $recipe_settings['id'] );
        wp_reset_postdata();

        ob_start();

        if (has_post_thumbnail($recipe)) :
            echo '<div class="cooked-post-featured-image">';
                echo get_the_post_thumbnail( $recipe, 'cooked-large' );
            echo '</div>';
        endif;

        return ob_get_clean();
    }

    public function cooked_ingredients_shortcode($atts, $content = null) {
        global $recipe_settings;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'checkboxes' => true,
        ], $atts);

        $checkboxes = Cooked_Functions::sanitize_text_field($atts['checkboxes']);
        $checkboxes = !$checkboxes || $checkboxes == 'false' ? false : $checkboxes;

        ob_start();

        do_action( 'cooked_ingredients_shortcode_before', $recipe_settings );

        if ( isset($recipe_settings['ingredients']) && !empty($recipe_settings['ingredients']) ):
            echo '<div class="cooked-recipe-ingredients">';
                foreach ( $recipe_settings['ingredients'] as $ing ):
                    Cooked_Recipes::single_ingredient( $ing, $checkboxes );
                endforeach;
            echo '</div>';
        endif;

        do_action( 'cooked_ingredients_shortcode_after', $recipe_settings );

        return ob_get_clean();
    }

    public function cooked_directions_shortcode($atts, $content = null) {
        global $recipe_settings;

        // Shortcode Attributes
        $atts = shortcode_atts(apply_filters('cooked_directions_shortcode_atts', [
            'numbers' => true,
        ]), $atts);

        $step = 0;
        $numbers = Cooked_Functions::sanitize_text_field( $atts['numbers'] );
        $numbers = !$numbers || $numbers == 'false' ? false : $numbers;

        ob_start();

        if (isset($recipe_settings['directions']) && !empty($recipe_settings['directions'])) {
            echo '<div class="cooked-recipe-directions">';
                foreach ($recipe_settings['directions'] as $dir) {
                    if (!isset($dir['section_heading_name'])) {
                        $step++;
                        $number = $numbers ? $step : false;
                    } else {
                        $number = 0;
                    }

                    Cooked_Recipes::single_direction($dir, $number, false, $step, $atts);
                }
            echo '</div>';
        }

        return ob_get_clean();
    }

    public function cooked_nutrition_shortcode($atts, $content = null) {
        global $_cooked_settings, $recipe_settings;

        // Shortcode Attributes
        $atts = shortcode_atts([
            'id' => false,
            'float' => false,
        ], $atts);

        if ( isset( $atts['id'] ) && $atts['id'] ):
            $recipe_settings = Cooked_Recipes::get( intval( $atts['id'] ), true );
        else:
            $recipe_settings = !empty($recipe_settings) ? $recipe_settings : false;
        endif;

        $float = Cooked_Functions::sanitize_text_field($atts['float']);

        $_nf_fields = Cooked_Measurements::nutrition_facts();
        $nutrition_facts = isset($recipe_settings['nutrition']) && !empty($recipe_settings['nutrition']) ? $recipe_settings['nutrition'] : false;

        ob_start();

        if ( $nutrition_facts ):

            $servings_change = (float)esc_html( get_query_var( 'servings', $recipe_settings['nutrition']['servings'] ) );

            $top_facts = $_nf_fields['top'];
            if (!empty($top_facts)):

                // Start output buffer for top facts.
                ob_start();

                echo '<div class="cooked-nut-servings">';
                foreach ( $top_facts as $slug => $nf ):
                    if ( $slug === 'serving_size' ):
                        echo '<div class="cooked-serving-size"><strong>' . esc_html($nf['name']) . '</strong> ';
                            echo '<p class="cooked-right"><strong class="cooked-nut-label" data-labeltype="' . esc_attr($slug) . '">' . esc_html( isset($nutrition_facts[$slug]) ? $nutrition_facts[$slug] : '' ) . '</strong></p>';
                        echo '</div>';
                    else:
                        echo '<p><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">' . $servings_change . '</strong> ' . esc_html(strtolower($nf['name'])) . '</p>';
                    endif;
                endforeach;
                echo '</div>';

                // Get top facts content from buffer.
                $top_facts_content = ob_get_clean();

            endif;

            $mid_facts = $_nf_fields['mid'];
            if (!empty($mid_facts)):

                // Start output buffer for mid-facts.
                ob_start();

                foreach ( $mid_facts as $slug => $nf ):
                    if ( isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] || isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] === '0' ):
                        echo '<dt class="cooked-calories no-after"><strong>' . esc_html($nf['name']) . '</strong>';
                            echo '<strong class="cooked-nut-label">' . esc_html( $nutrition_facts[$slug] ) . '</strong>';
                        echo '</dt>';
                    endif;
                endforeach;

                // Get mid facts content from buffer.
                $mid_facts_content = ob_get_clean();

            endif;

            $main_facts = $_nf_fields['main'];
            $nut_loops = 0;

            if (!empty($main_facts)):

                // Start output buffer for main facts.
                ob_start();

                foreach ( $main_facts as $slug => $nf ):

                    if ( isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] || isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] === '0' ):

                    echo '<dt>';
                    echo '<strong>' . $nf['name'] . '</strong> <strong class="cooked-nut-label">' . esc_html( $nutrition_facts[$slug] ) . '</strong>' . ( isset($nf['measurement']) ? '<strong class="cooked-nut-label cooked-nut-measurement">' . esc_html( $nf['measurement'] ) . '</strong>' : '' );
                    echo ( isset( $nf['pdv'] ) && $nutrition_facts[$slug] ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent">' . ceil( ( esc_html( $nutrition_facts[$slug] ) / $nf['pdv'] ) * 100 ) . '</span>%</strong>' : '' );

                    if ( isset($nf['subs']) ):
                        foreach( $nf['subs'] as $sub_slug => $sub_nf ):
                            if ( isset( $nutrition_facts[$sub_slug] ) && $nutrition_facts[$sub_slug] || isset( $nutrition_facts[$sub_slug] ) && $nutrition_facts[$sub_slug] === '0' ):
                                echo '<dl>';
                                if ($sub_slug === 'trans_fat'):
                                    echo '<dt>';
                                        echo $sub_nf['nutrition_info_name'] . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">' . esc_html( $nutrition_facts[$sub_slug] ) . '</strong>' . ( isset($sub_nf['measurement']) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html($sub_nf['measurement']) . '</strong>' : '' );
                                    echo '</dt>';
                                elseif ($sub_slug === 'added_sugars'):
                                    echo '<dl><dt>';
                                        echo __('Includes', 'cooked') . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">' . esc_html( $nutrition_facts[$sub_slug] ) . '</strong>' . ( isset($sub_nf['measurement']) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html($sub_nf['measurement']) . '</strong>' : '' ) . ' ' . esc_html($sub_nf['name']);
                                        echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr($sub_nf['pdv']) . '" data-labeltype="' . esc_attr($sub_slug) . '">' . ceil( ( esc_html( $nutrition_facts[$sub_slug] ) / $sub_nf['pdv'] ) * 100 ) . '</span>%</strong>' : '' );
                                    echo '</dt></dl>';
                                else:
                                    echo '<dt>';
                                        echo esc_html( $sub_nf['name'] ) . ' <strong class="cooked-nut-label">' . esc_html( $nutrition_facts[$sub_slug] ) . '</strong>' . ( isset($sub_nf['measurement']) ? '<strong class="cooked-nut-label cooked-nut-measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' );
                                        echo ( isset( $sub_nf['pdv'] ) && $nutrition_facts[$sub_slug] ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent">' . ceil( ( esc_html( $nutrition_facts[$sub_slug] ) / $sub_nf['pdv'] ) * 100 ) . '</span>%</strong>' : '' );
                                    echo '</dt>';
                                endif;
                                echo '</dl>';
                            endif;
                        endforeach;
                    endif;

                    echo '</dt>';

                    endif;

                endforeach;

                // Get main facts content from buffer.
                $main_facts_content = ob_get_clean();

            endif;

            $bottom_facts = $_nf_fields['bottom'];

            if (!empty($bottom_facts)):

                // Start output buffer for bottom facts.
                ob_start();

                foreach ( $bottom_facts as $slug => $nf ):
                    if ( isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] || isset( $nutrition_facts[$slug] ) && $nutrition_facts[$slug] === '0' ):
                        echo '<dt>';
                            echo '<strong>' . esc_html($nf['name']) . '</strong> <span class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">' . esc_html( $nutrition_facts[$slug] ) . '</span>' . ( isset($nf['measurement']) ? '<span class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '_measurement">' . esc_html($nf['measurement']) . '</span>' : '' );
                            echo ( isset( $nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr($nf['pdv']) . '" data-labeltype="' . esc_attr($slug) . '">' . ceil( ( esc_html( $nutrition_facts[$slug] ) / $nf['pdv'] ) * 100 ) . '</span>%</strong>' : '' );
                        echo '</dt>';
                    endif;
                endforeach;

                // Get bottom facts content from buffer.
                $bottom_facts_content = ob_get_clean();

            endif;

            // Start a buffer for all nutrition facts content
            ob_start();

            if ( isset($top_facts_content) && $top_facts_content ):
                echo wp_kses_post( $top_facts_content );
            endif;

            if ( isset($mid_facts_content) && $mid_facts_content || isset($main_facts_content) && $main_facts_content ):

                echo '<hr class="cooked-nut-hr" />';
                echo '<dl>';

                    echo '<dt><strong class="cooked-nut-heading">' . __('Amount per serving','cooked') . '</strong></dt>';

                    if ( isset($mid_facts_content) && $mid_facts_content ):
                        echo '<section class="cooked-clearfix">';
                            echo wp_kses_post( $mid_facts_content );
                        echo '</section>';
                    endif;

                    if ( isset($main_facts_content) && $main_facts_content ):
                        echo '<dt class="cooked-nut-spacer"></dt>';
                        echo '<dt class="cooked-nut-no-border"><strong class="cooked-nut-heading cooked-nut-right">' . __('% Daily Value *','cooked'). '</strong></dt>';
                        echo '<section class="cooked-clearfix">';
                            echo wp_kses_post( $main_facts_content );
                        echo '</section>';
                    endif;

                echo '</dl>';
                echo '<hr class="cooked-nut-hr" />';

            endif;

            if ( isset($bottom_facts_content) && $bottom_facts_content ):
                echo '<dl class="cooked-nut-bottom cooked-clearfix">';
                    echo wp_kses_post( $bottom_facts_content );
                echo '</dl>';
            endif;

            $nutrition_facts_content = ob_get_clean();

            if ( isset($nutrition_facts_content) && $nutrition_facts_content ):

                echo '<div class="cooked-nutrition-label' . ( $float ? ' cooked-float-'.esc_attr( $float ) : '' ) . '">';
                    echo '<div class="cooked-nutrition-title">' . __('Nutrition Facts', 'cooked') . '</div>';
                    echo wp_kses_post( $nutrition_facts_content );
                    if ( isset($main_facts_content) && $main_facts_content || isset($bottom_facts_content) && $bottom_facts_content ):
                        echo '<div class="cooked-nut-spacer"></div>';
                        echo '<p class="cooked-daily-value-text">* ' . __('The % Daily Value (DV) tells you how much a nutrient in a serving of food contributes to a daily diet. 2,000 calories a day is used for general nutrition advice.','cooked') . '</p>';
                    endif;

                    // Add the Edamam attribution "Powered by Edamam" if the Edamam API is enabled.
                    // More Information: https://developer.edamam.com/attribution
                    if (!empty($_cooked_settings['enable_nutrition_api'])) :
                        echo '<div class="cooked-nutrition-edamam">';
                            do_action( 'cooked_nutrition_facts_powered_by_edamam' );
                        echo '</div>';
                    endif;

                echo '</div>';

            endif;

        endif;

        return ob_get_clean();
    }
}
