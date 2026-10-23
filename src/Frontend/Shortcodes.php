<?php
/**
 * Cooked Shortcodes
 *
 * @package     Cooked
 * @subpackage  Shortcodes
 * @since       1.0.0
 */

namespace Cooked\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Class
 *
 * This class handles the settings creation and contains functions for retreiving those settings.
 *
 * @since 1.0.0
 */
class Shortcodes {

    /**
     * Recipe IDs currently being rendered by [cooked-recipe] in this request.
     *
     * @var int[]
     */
    private static $recipe_embed_stack = [];

    /**
     * Registers the shortcodes.
     */
    public function __construct() {
        // Allow shortcodes in widgets.
        add_filter( 'widget_text', 'do_shortcode' );

        // Site-Wide.
        add_shortcode( 'cooked-browse', [ $this, 'cooked_browse_shortcode' ] );
        add_shortcode( 'cooked-search', [ $this, 'cooked_search_shortcode' ] );
        add_shortcode( 'cooked-recipe', [ $this, 'cooked_recipe_shortcode' ] );
        add_shortcode( 'cooked-categories', [ $this, 'cooked_categories_shortcode' ] );
        add_shortcode( 'cooked-recipe-list', [ $this, 'cooked_recipe_list_shortcode' ] );
        add_shortcode( 'cooked-recipe-card', [ $this, 'cooked_recipe_card_shortcode' ] );
        add_shortcode( 'cooked-related-recipes', [ $this, 'cooked_related_recipes_shortcode' ] );

        if ( shortcode_exists( 'timer' ) ) :
            add_shortcode( 'cooked-timer', [ $this, 'cooked_timer' ] );
        else :
            add_shortcode( 'cooked-timer', [ $this, 'cooked_timer' ] );
            add_shortcode( 'timer', [ $this, 'cooked_timer' ] );
        endif;

        // Recipe-Only.
        add_shortcode( 'cooked-title', [ $this, 'cooked_title_shortcode' ] );
        add_shortcode( 'cooked-gallery', [ $this, 'cooked_gallery_shortcode' ] );
        add_shortcode( 'cooked-image', [ $this, 'cooked_image_shortcode' ] );
        add_shortcode( 'cooked-info', [ $this, 'cooked_info_shortcode' ] );
        add_shortcode( 'cooked-excerpt', [ $this, 'cooked_excerpt_shortcode' ] );
        add_shortcode( 'cooked-notes', [ $this, 'cooked_notes_shortcode' ] );
        add_shortcode( 'cooked-ingredients', [ $this, 'cooked_ingredients_shortcode' ] );
        add_shortcode( 'cooked-directions', [ $this, 'cooked_directions_shortcode' ] );
        add_shortcode( 'cooked-nutrition', [ $this, 'cooked_nutrition_shortcode' ] );

        // Add preprocessing filter for shortcodes for Elementor, Divi, and other builders compatibility.
        add_filter( 'pre_do_shortcode_tag', [ $this, 'preprocess_shortcode' ], 10, 3 );
    }

    /**
     * Preprocess Shortcode.
     *
     * @since 1.0.0
     * @param mixed  $output The shortcode output.
     * @param string $tag    The shortcode tag.
     * @param array  $attr   The shortcode attributes.
     * @return mixed
     */
    public function preprocess_shortcode( $output, $tag, $attr ) {
        // Tags to skip.
        $skip_tags = [
            'cooked-search',
            'cooked-browse',
            'cooked-timer',
            'cooked-recipe',
            'cooked-recipe-list',
            'cooked-recipe-card',
        ];

        // Only process for Cooked shortcodes.
        if ( is_front_page() || strpos( $tag, 'cooked-' ) === false || in_array( $tag, $skip_tags, true ) ) {
            return $output;
        }

        global $recipe_settings, $post;

        // If recipe settings are empty, try to get them.
        if ( empty( $recipe_settings ) ) {
            // Try to get recipe settings from current post.
            $post_id = isset( $post->ID ) ? $post->ID : false;
            if ( $post_id && get_post_type( $post_id ) === 'cp_recipe' ) {
                $recipe_settings = \Cooked_Recipes::get( $post_id, true );
            } else {
                // We are in the editor but not on a recipe post type. Maybe a single recipe template?
                // Uses the first recipe found in the database as a sample.
                $recipe_settings = \Cooked_Recipes::get( false, true, false, 1 );
            }

            // If still empty and we have a specific recipe ID in attributes, try to get them.
            if ( empty( $recipe_settings ) && isset( $attr['id'] ) ) {
                $recipe_settings = \Cooked_Recipes::get( intval( $attr['id'] ), true );
            }
        }

        return $output;
    }

    /**
     * [cooked-search]: the recipe search box.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_search_shortcode( $atts ) {
        // Shortcode Attributes.
        $options = shortcode_atts(
            [
                'compact'       => false,
                'hide_browse'   => false,
                'hide_sorting'  => false,
                'inline_browse' => false,
            ],
            $atts
        );

        return \Cooked_Recipes::recipe_search_box( $options );
    }

    /**
     * [timer]: a cooking timer link.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @param string|null          $content Content between the shortcode tags.
     * @return string
     */
    public function cooked_timer( $atts, $content = null ) {
        global $cooked_timer_identifier;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
                'seconds' => 0,
                'minutes' => 0,
                'length'  => 0, // Deprecated, left here for backwards compatibility with 2.x.
                'hours'   => 0,
                'desc'    => '',
            ],
            $atts
        );

        $seconds       = floatval( $atts['seconds'] );
        $minutes_value = floatval( $atts['minutes'] );
        $minutes       = $minutes_value ? $minutes_value * 60 : floatval( $atts['length'] ) * 60;
        $hours         = floatval( $atts['hours'] ) * 60 * 60;
        $seconds       = $seconds + $minutes + $hours;

        $content = (string) $content;
        $desc    = '' !== (string) $atts['desc'] ? wp_strip_all_tags( (string) $atts['desc'] ) : wp_strip_all_tags( $content );

        if ( ! $cooked_timer_identifier ) {
            $cooked_timer_identifier = 1;
        } else {
            ++$cooked_timer_identifier;
        }

        $timer_id = md5( $seconds . $desc . $content ) . '_' . $cooked_timer_identifier;

        wp_enqueue_script( 'cooked-timer' );

        return '<span class="cooked-timer"><a aria-label="' . esc_attr( $desc ) . '" data-timer-id="' . esc_attr( $timer_id ) . '" data-seconds="' . esc_attr( $seconds ) . '" data-desc="' . esc_attr( $desc ) . '"><i class="cooked-icon cooked-icon-clock"></i> ' . wp_kses_post( $content ) . '</a></span>';
    }

    /**
     * [cooked-browse]: the browsable recipe list with search.
     *
     * @param array<string, mixed> $sc_atts Shortcode attributes.
     * @return string|false
     */
    public function cooked_browse_shortcode( $sc_atts ) {
        global $_cooked_settings;

        if ( isset( $_cooked_settings['advanced'] ) && ! empty( $_cooked_settings['advanced'] ) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'], true ) ) {
            /* translators: referring to the bottom of the Settings page. */
            return current_user_can( 'edit_cooked_settings' ) ? wpautop( sprintf( __( 'Public recipes are currently disabled. You can change this at the bottom of the %s page.', 'cooked' ), '<a href="' . trailingslashit( esc_url( admin_url() ) ) . 'admin.php?page=cooked_settings" target="_blank">' . __( 'Settings', 'cooked' ) . '</a>' ) ) : false;
        }

        if ( is_admin() ) {
			return false;
        }

        $author_query_var = sanitize_key( get_query_var( 'recipe_author', false ) );
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only browse author filter.
        if ( ! $author_query_var && isset( $_GET['recipe_author'] ) ) {
            $author_query_var = sanitize_key( wp_unslash( $_GET['recipe_author'] ) );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Shortcode Attributes.
        $atts = shortcode_atts(
            apply_filters(
                'cooked_browse_shortcode_default_attributes',
                [
					'category'      => false,
					'order'         => false,
					'orderby'       => false,
					'show'          => false,
					'search'        => 'true',
					'pagination'    => 'true',
					'columns'       => 3,
					'layout'        => false,
					'author'        => $author_query_var,
					'compact'       => false,
					'hide_browse'   => false,
					'hide_sorting'  => false,
					'exclude'       => false,
					'inline_browse' => false,
					'hide_excerpt'  => false,
				]
            ),
            $sc_atts
        );

        return \Cooked_Recipes::list_view( $atts );
    }

    /**
     * [cooked-recipe-card]: a single recipe card.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_recipe_card_shortcode( $atts ) {
        if ( is_admin() ) {
			return false;
        }

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'id'           => false,
				'category'     => false,
				'width'        => false,
				'style'        => false,
				'hide_total'   => false,
				'hide_image'   => false,
				'hide_title'   => false,
				'hide_excerpt' => false,
				'hide_author'  => false,
			],
            $atts
        );

        $recipe_id    = intval( $atts['id'] );
        $category_id  = intval( $atts['category'] );
        $width        = \Cooked_Functions::sanitize_text_field( $atts['width'] );
        $valid_styles = [ 'modern', 'modern-centered' ];
        $style        = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : false;
        $hide_image   = \Cooked_Functions::sanitize_text_field( $atts['hide_image'] );
        $hide_total   = \Cooked_Functions::sanitize_text_field( $atts['hide_total'] );
        $hide_title   = \Cooked_Functions::sanitize_text_field( $atts['hide_title'] );
        $hide_excerpt = \Cooked_Functions::sanitize_text_field( $atts['hide_excerpt'] );
        $hide_author  = \Cooked_Functions::sanitize_text_field( $atts['hide_author'] );

        ob_start();

        if ( $recipe_id ) {
            echo wp_kses_post( \Cooked_Recipes::card( $recipe_id, $width, $hide_image, $hide_title, $hide_excerpt, $hide_author, $style ) );
        } elseif ( $category_id ) {
            echo wp_kses_post( \Cooked_Taxonomies::card( $category_id, $width, $hide_image, $hide_total, $style ) );
        }

        return ob_get_clean();
    }

    /**
     * [cooked-categories]: recipe category blocks or a list.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_categories_shortcode( $atts ) {
        if ( is_admin() ) {
			return false;
        }

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'hide_empty' => true,
				'child_of'   => false,
				'style'      => 'block',
			],
            $atts
        );

        $hide_empty   = \Cooked_Functions::sanitize_text_field( $atts['hide_empty'] );
        $child_of     = \Cooked_Functions::sanitize_text_field( $atts['child_of'] );
        $valid_styles = [ 'block', 'list' ];
        $style        = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'block';
        $parents_only = $child_of ? false : true;

        ob_start();

        $categories_array = \Cooked_Settings::terms_array( 'cp_recipe_category', false, false, $hide_empty, $parents_only, $child_of );
        if ( ! empty( $categories_array ) ) {
            echo 'list' === $style ? '<div class="cooked-recipe-term-list">' : '<div class="cooked-recipe-term-grid cooked-clearfix">';
			foreach ( $categories_array as $key => $val ) {
				\Cooked_Taxonomies::single_taxonomy_block( $key, $style );
			}
            echo '</div>';
        }

        return ob_get_clean();
    }

    /**
     * [cooked-recipe-list]: a compact list of recipes.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_recipe_list_shortcode( $atts ) {
        if ( is_admin() ) {
			return false;
        }

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'orderby'     => 'date',
				'width'       => '100%',
				'recipes'     => false,
				'show'        => 5,
				'hide_image'  => false,
				'hide_author' => false,
			],
			$atts
        );

        $recipes     = \Cooked_Functions::sanitize_text_field( $atts['recipes'] );
        $orderby     = \Cooked_Functions::sanitize_text_field( $atts['orderby'] );
        $show        = \Cooked_Functions::sanitize_text_field( $atts['show'] );
        $width       = \Cooked_Functions::sanitize_text_field( $atts['width'] );
        $hide_image  = \Cooked_Functions::sanitize_text_field( $atts['hide_image'] );
        $hide_author = \Cooked_Functions::sanitize_text_field( $atts['hide_author'] );

        if ( $recipes ) {
            $recipes = preg_replace( '/\s+/', '', $recipes );
            $recipes = explode( ',', $recipes );
        }

        ob_start();

        \Cooked_Recipes::recipe_list( $orderby, $show, $recipes, $width, $hide_image, $hide_author );

        return ob_get_clean();
    }

    /**
     * [cooked-recipe]: embeds a full recipe.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_recipe_shortcode( $atts ) {
        if ( is_admin() ) {
			return false;
        }

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'id' => false,
			],
            $atts
        );

        global $recipe_settings, $_cooked_content_unfiltered;

        $recipe_id = intval( $atts['id'] );

        if ( ! $recipe_id ) {
            return '';
        }

        if ( in_array( $recipe_id, self::$recipe_embed_stack, true ) ) {
            $output = self::recipe_embed_blocked_message( $recipe_id );

            if ( $output ) {
                $output = '<div id="cooked-recipe-embed-' . intval( $recipe_id ) . '" class="cooked-recipe-embed">' . $output . '</div>';
                $output = apply_filters( 'cooked_recipe_shortcode_output', $output, $recipe_id );
            }

            return $output;
        }

        $recipe_settings = \Cooked_Recipes::get( $recipe_id, true );
        if ( ! $recipe_settings || empty( $recipe_settings ) ) {
            $output = wpautop( '<strong>[cooked-recipe id="' . intval( $recipe_id ) . '"]</strong><br><em>' . __( '(recipe not found or in draft status)', 'cooked' ) . '</em>' );

            if ( $output ) {
                $output = '<div id="cooked-recipe-embed-' . intval( $recipe_id ) . '" class="cooked-recipe-embed">' . $output . '</div>';
                $output = apply_filters( 'cooked_recipe_shortcode_output', $output, $recipe_id );
            }

            return $output;
        }

        self::$recipe_embed_stack[] = $recipe_id;

        try {
            ob_start();
            load_template( COOKED_DIR . 'templates/front/recipe.php', false );
            $output = do_shortcode( ob_get_clean() );

            if ( $output ) {
                $output = '<div id="cooked-recipe-embed-' . intval( $recipe_id ) . '" class="cooked-recipe-embed">' . $output . '</div>';
                $output = apply_filters( 'cooked_recipe_shortcode_output', $output, $recipe_id );
            }

            return $output;
        } finally {
            array_pop( self::$recipe_embed_stack );
        }
    }

    /**
     * Message shown instead of a recipe that embeds itself.
     *
     * @param int $recipe_id Recipe ID.
     * @return string
     */
    private static function recipe_embed_blocked_message( $recipe_id ) {
        $shortcode = '[cooked-recipe id="' . intval( $recipe_id ) . '"]';
        $message   = '<em>' . sprintf(
            /* translators: %s: recipe embed shortcode */
            __( 'This recipe could not be displayed because it is set up to include itself (containing shortcode %s). Remove the duplicate recipe embed from the Recipe Template.', 'cooked' ),
            $shortcode
        ) . '</em>';

        $message = apply_filters( 'cooked_recipe_embed_blocked_message', $message, $recipe_id, self::$recipe_embed_stack );

        return wpautop( $message );
    }

    /**
     * [cooked-gallery]: the recipe's gallery (Cooked or a gallery plugin).
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false|void
     */
    public function cooked_gallery_shortcode( $atts ) {
        global $recipe_settings;

        $gallery_options = [];
        if ( isset( $recipe_settings['gallery'] ) && isset( $recipe_settings['gallery']['type'] ) && 'cooked' === $recipe_settings['gallery']['type'] ) {
            $gallery_options = self::gallery_options( $recipe_settings['gallery'], is_array( $atts ) ? $atts : [] );
        }

        if ( isset( $recipe_settings['gallery'] ) && isset( $recipe_settings['gallery']['type'] ) ) :

            switch ( $recipe_settings['gallery']['type'] ) :

                case 'cooked':
                    if ( ( isset( $recipe_settings['gallery']['items'] ) && ! empty( $recipe_settings['gallery']['items'] ) ) || ( isset( $recipe_settings['gallery']['video_url'] ) && $recipe_settings['gallery']['video_url'] ) ) :
                        return self::cooked_gallery_html( $recipe_settings, $gallery_options );
                    endif;

                    break;

                case 'envira':
                    return ( $recipe_settings['gallery']['envira'] ? '<div class="cooked-recipe-gallery-envira">' . do_shortcode( '[envira-gallery id="' . intval( $recipe_settings['gallery']['envira'] ) . '"]' ) . '</div>' : '' );

                case 'soliloquy':
                    return ( $recipe_settings['gallery']['soliloquy'] ? '<div class="cooked-recipe-gallery-soliloquy">' . do_shortcode( '[soliloquy id="' . intval( $recipe_settings['gallery']['soliloquy'] ) . '"]' ) . '</div>' : '' );

                case 'revslider':
                    return ( $recipe_settings['gallery']['revslider'] ? '<div class="cooked-recipe-gallery-revslider">' . do_shortcode( '[rev_slider alias="' . intval( $recipe_settings['gallery']['revslider'] ) . '"]' ) . '</div>' : '' );

            endswitch;

        endif;

        return false;
    }

    /**
     * The Cooked gallery's options: the cooked_recipe_gallery_options defaults, then the
     * recipe's own style from the Gallery tab, then the shortcode's attributes.
     *
     * @since 2.0.0
     * @param array<string, mixed> $gallery The recipe's gallery settings.
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return array<string, string>
     */
    private static function gallery_options( $gallery, $atts ) {
        $gallery_options = apply_filters(
            'cooked_recipe_gallery_options',
            [
                'data-fit'              => 'cover',
                'data-nav'              => 'dots',
                'data-width'            => '100%',
                'data-loop'             => 'true',
                'data-allowfullscreen'  => 'true',
                'data-ratio'            => '800/600',
                'data-thumbmargin'      => '10',
                'data-thumbborderwidth' => '5',
                'data-swipe'            => 'true',
                'data-thumbheight'      => '75',
                'data-thumbwidth'       => '75',
            ]
        );

        // The recipe's style (Gallery tab). Unset values keep the defaults above.
        $styles = [
            'dots'   => 'dots',
            'thumbs' => 'thumbs',
            'none'   => 'false',
        ];
        if ( isset( $gallery['style'] ) && is_string( $gallery['style'] ) && isset( $styles[ $gallery['style'] ] ) ) {
            $gallery_options['data-nav'] = $styles[ $gallery['style'] ];
        }
        if ( isset( $gallery['loop'] ) && in_array( (string) $gallery['loop'], [ '0', '1' ], true ) ) {
            $gallery_options['data-loop'] = '1' === (string) $gallery['loop'] ? 'true' : 'false';
        }
        // data-autoplay is the interval in milliseconds (cooked-gallery.js); 0 is off.
        if ( isset( $gallery['autoplay'] ) && in_array( (string) $gallery['autoplay'], [ '0', '1' ], true ) ) {
            $gallery_options['data-autoplay'] = '1' === (string) $gallery['autoplay'] ? '5000' : '0';
        }
        if ( isset( $gallery['ratio'] ) && in_array( $gallery['ratio'], array_keys( self::gallery_ratios() ), true ) ) {
            $gallery_options['data-ratio'] = $gallery['ratio'];
        }
        if ( isset( $gallery['fit'] ) && in_array( $gallery['fit'], [ 'cover', 'contain' ], true ) ) {
            $gallery_options['data-fit'] = $gallery['fit'];
        }

        // Shortcode attributes win.
        $gallery_atts = [];
        foreach ( $gallery_options as $opt_name => $opt_value ) {
            $gallery_atts[ str_replace( 'data-', '', $opt_name ) ] = $opt_value;
        }
        $atts = shortcode_atts( $gallery_atts, $atts );
        foreach ( $gallery_options as $opt_name => $opt_value ) {
            $name_sans_data = str_replace( 'data-', '', $opt_name );
            if ( isset( $atts[ $name_sans_data ] ) && $atts[ $name_sans_data ] ) {
                $gallery_options[ $opt_name ] = $atts[ $name_sans_data ];
            }
        }

        return $gallery_options;
    }

    /**
     * Gallery shapes offered on the Gallery tab, ratio => label.
     *
     * @since 2.0.0
     * @return array<string, string>
     */
    public static function gallery_ratios() {
        return [
            '4/3'  => __( 'Standard (4:3)', 'cooked' ),
            '3/2'  => __( 'Photo (3:2)', 'cooked' ),
            '16/9' => __( 'Wide (16:9)', 'cooked' ),
            '1/1'  => __( 'Square (1:1)', 'cooked' ),
        ];
    }

    /**
     * The Cooked gallery for given gallery settings, as the recipe would show it: for the
     * Gallery tab's preview.
     *
     * @since 2.0.0
     * @param array<string, mixed> $gallery Gallery settings (items, video_url and the style).
     * @param string               $title   Caption for a video slide.
     * @return string
     */
    public static function gallery_preview_html( $gallery, $title = '' ) {
        $gallery['type'] = 'cooked';
        return self::cooked_gallery_html(
            [
                'gallery' => $gallery,
                'title'   => $title,
            ],
            self::gallery_options( $gallery, [] )
        );
    }

    /**
     * Render the built-in recipe gallery: a slider with dots or thumbnails, and PhotoSwipe
     * to view images fullscreen.
     *
     * $options are the cooked_recipe_gallery_options (data-fit, data-nav, data-width,
     * data-ratio, data-loop, data-allowfullscreen, data-swipe, data-autoplay and the
     * data-thumb* sizes), already merged with the shortcode attributes. Shortcode
     * attributes come from post authors, so every option is validated here.
     *
     * @since 2.0.0
     * @param array<string, mixed>  $recipe_settings The recipe settings.
     * @param array<string, string> $options         Gallery options.
     * @return string
     */
    private static function cooked_gallery_html( $recipe_settings, $options ) {
        $option = function ( $key, $fallback ) use ( $options ) {
            return isset( $options[ 'data-' . $key ] ) ? strtolower( trim( (string) $options[ 'data-' . $key ] ) ) : $fallback;
        };

        $nav = $option( 'nav', 'dots' );
        $nav = in_array( $nav, [ 'dots', 'thumbs' ], true ) ? $nav : 'false';

        $fit = str_replace( 'scaledown', 'scale-down', $option( 'fit', 'cover' ) );
        $fit = in_array( $fit, [ 'cover', 'contain', 'scale-down', 'none' ], true ) ? $fit : 'cover';

        $ratio = $option( 'ratio', '800/600' );
        if ( preg_match( '#^(\d+(?:\.\d+)?)\s*[/:]\s*(\d+(?:\.\d+)?)$#', $ratio, $r ) && (float) $r[2] > 0 ) {
            $ratio = $r[1] . ' / ' . $r[2];
        } elseif ( is_numeric( $ratio ) && (float) $ratio > 0 ) {
            $ratio = (float) $ratio . ' / 1';
        } else {
            $ratio = '4 / 3';
        }

        $width = $option( 'width', '100%' );
        $width = preg_match( '#^\d+(?:\.\d+)?(px|%)?$#', $width, $w ) ? ( empty( $w[1] ) ? $width . 'px' : $width ) : '100%';

        $loop            = 'false' !== $option( 'loop', 'true' );
        $allowfullscreen = 'false' !== $option( 'allowfullscreen', 'true' );
        $swipe           = 'false' !== $option( 'swipe', 'true' );
        $autoplay        = $option( 'autoplay', 'false' );
        $autoplay        = ( 'true' === $autoplay ? 5000 : ( ctype_digit( $autoplay ) ? (int) $autoplay : 0 ) );

        $styles = [
            '--cooked-gallery-ratio:' . $ratio,
            '--cooked-gallery-fit:' . $fit,
            '--cooked-gallery-thumb-width:' . absint( $option( 'thumbwidth', '75' ) ) . 'px',
            '--cooked-gallery-thumb-height:' . absint( $option( 'thumbheight', '75' ) ) . 'px',
            '--cooked-gallery-thumb-margin:' . absint( $option( 'thumbmargin', '10' ) ) . 'px',
            '--cooked-gallery-thumb-border:' . absint( $option( 'thumbborderwidth', '5' ) ) . 'px',
        ];
        // Only a width the shortcode asks for: an inline max-width: 100% would override the
        // theme's content width (block themes) and stretch the gallery across the page.
        if ( '100%' !== $width ) {
            array_unshift( $styles, 'max-width:' . $width );
        }

        $slides = [];

        $video_embed = ! empty( $recipe_settings['gallery']['video_url'] ) ? self::gallery_video_embed_url( $recipe_settings['gallery']['video_url'] ) : false;
        $video_slide = false;
        if ( $video_embed ) {
            $title       = isset( $recipe_settings['title'] ) ? $recipe_settings['title'] : '';
            $video_slide = [
                'type'    => 'video',
                'embed'   => $video_embed,
                'url'     => $recipe_settings['gallery']['video_url'],
                'caption' => $title,
            ];
        }

        if ( $video_slide && ! apply_filters( 'cooked_gallery_video_last_option', false ) ) {
            $slides[] = $video_slide;
        }

        $gallery_items = ! empty( $recipe_settings['gallery']['items'] ) ? apply_filters( 'cooked_gallery_items_output', $recipe_settings['gallery']['items'] ) : [];
        foreach ( (array) $gallery_items as $item ) {
            $full = wp_get_attachment_image_src( $item, 'full' );
            if ( ! is_array( $full ) || empty( $full[0] ) ) {
                continue;
            }
            $slides[] = [
                'type'    => 'image',
                'id'      => (int) $item,
                'full'    => $full,
                'caption' => get_the_title( $item ),
            ];
        }

        if ( $video_slide && apply_filters( 'cooked_gallery_video_last_option', false ) ) {
            $slides[] = $video_slide;
        }

        if ( empty( $slides ) ) {
            return '';
        }

        $count = count( $slides );
        $html  = '<div class="cooked-gallery" role="region" aria-roledescription="' . esc_attr__( 'carousel', 'cooked' ) . '" aria-label="' . esc_attr__( 'Recipe gallery', 'cooked' ) . '"';
        $html .= ' data-nav="' . esc_attr( $nav ) . '" data-loop="' . ( $loop ? 'true' : 'false' ) . '" data-allowfullscreen="' . ( $allowfullscreen ? 'true' : 'false' ) . '"';
        $html .= ' data-swipe="' . ( $swipe ? 'true' : 'false' ) . '" data-autoplay="' . esc_attr( (string) $autoplay ) . '" style="' . esc_attr( implode( ';', $styles ) ) . '">';
        $html .= '<div class="cooked-gallery-stage">';
        $html .= '<div class="cooked-gallery-track">';

        foreach ( $slides as $index => $slide ) {
            /* translators: 1: slide number, 2: number of slides. */
            $label = sprintf( __( '%1$d of %2$d', 'cooked' ), $index + 1, $count );
            $html .= '<figure class="cooked-gallery-slide cooked-gallery-slide-' . esc_attr( $slide['type'] ) . '" role="group" aria-roledescription="' . esc_attr__( 'slide', 'cooked' ) . '" aria-label="' . esc_attr( $label ) . '"';

            if ( 'video' === $slide['type'] ) {
                $html .= ' data-video-embed="' . esc_url( $slide['embed'] ) . '">';
                $html .= '<button type="button" class="cooked-gallery-play" aria-label="' . esc_attr__( 'Play video', 'cooked' ) . '"><span class="cooked-gallery-play-icon" aria-hidden="true"></span></button>';
                $html .= '<noscript><a href="' . esc_url( $slide['url'] ) . '">' . esc_html__( 'Watch the video', 'cooked' ) . '</a></noscript>';
            } else {
                $html .= '>';
                $html .= '<a class="cooked-gallery-image" href="' . esc_url( $slide['full'][0] ) . '" data-pswp-width="' . absint( $slide['full'][1] ) . '" data-pswp-height="' . absint( $slide['full'][2] ) . '" data-caption="' . esc_attr( $slide['caption'] ) . '">';
                $html .= wp_get_attachment_image(
                    $slide['id'],
                    [ 900, 900 ],
                    false,
                    [
						'alt'     => $slide['caption'],
						'loading' => 0 === $index ? 'eager' : 'lazy',
					]
                );
                $html .= '</a>';
            }

            if ( '' !== (string) $slide['caption'] ) {
                $html .= '<figcaption class="cooked-gallery-caption">' . esc_html( $slide['caption'] ) . '</figcaption>';
            }

            $html .= '</figure>';
        }

        $html .= '</div>';

        if ( $count > 1 ) {
            $html .= '<button type="button" class="cooked-gallery-arrow cooked-gallery-prev" aria-label="' . esc_attr__( 'Previous slide', 'cooked' ) . '"><i class="cooked-icon cooked-icon-angle-left" aria-hidden="true"></i></button>';
            $html .= '<button type="button" class="cooked-gallery-arrow cooked-gallery-next" aria-label="' . esc_attr__( 'Next slide', 'cooked' ) . '"><i class="cooked-icon cooked-icon-angle-right" aria-hidden="true"></i></button>';
        }

        $html .= '</div>';

        if ( $count > 1 && 'false' !== $nav ) {
            $html .= '<div class="cooked-gallery-nav cooked-gallery-nav-' . esc_attr( $nav ) . '">';
            foreach ( $slides as $index => $slide ) {
                /* translators: %d: slide number. */
                $html .= '<button type="button" class="cooked-gallery-nav-item" data-slide="' . (int) $index . '" aria-label="' . esc_attr( sprintf( __( 'Go to slide %d', 'cooked' ), $index + 1 ) ) . '">';
                if ( 'thumbs' === $nav ) {
                    $html .= 'image' === $slide['type'] ? wp_get_attachment_image(
                        $slide['id'],
                        'thumbnail',
                        false,
                        [
							'alt'     => '',
							'loading' => 'lazy',
						]
                    ) : '<span class="cooked-gallery-play-icon" aria-hidden="true"></span>';
                }
                $html .= '</button>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        wp_enqueue_style( 'cooked-photoswipe' );
        wp_enqueue_script( 'cooked-gallery' );

        return $html;
    }

    /**
     * Turn a YouTube or Vimeo URL into its embed URL. The player is only loaded when the
     * visitor presses play.
     *
     * @since 2.0.0
     * @param string $url Video URL.
     * @return string|false Embed URL, or false for an unsupported URL.
     */
    public static function gallery_video_embed_url( $url ) {
        $parts = wp_parse_url( trim( (string) $url ) );
        if ( empty( $parts['host'] ) ) {
            return false;
        }

        $host = strtolower( (string) preg_replace( '#^(www\.|m\.)#i', '', $parts['host'] ) );
        $path = isset( $parts['path'] ) ? $parts['path'] : '';
        $id   = '';

        if ( 'youtu.be' === $host ) {
            $id = trim( $path, '/' );
        } elseif ( in_array( $host, [ 'youtube.com', 'youtube-nocookie.com' ], true ) ) {
            if ( preg_match( '#^/(?:shorts|embed|live|v)/([^/?]+)#', $path, $m ) ) {
                $id = $m[1];
            } elseif ( ! empty( $parts['query'] ) ) {
                parse_str( $parts['query'], $query );
                $id = isset( $query['v'] ) && is_string( $query['v'] ) ? $query['v'] : '';
            }
        } elseif ( in_array( $host, [ 'vimeo.com', 'player.vimeo.com' ], true ) ) {
            if ( preg_match( '#/(\d+)(?:/|$)#', $path, $m ) ) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
            return false;
        } else {
            return false;
        }

        return preg_match( '#^[A-Za-z0-9_-]{6,20}$#', $id ) ? 'https://www.youtube-nocookie.com/embed/' . $id : false;
    }

    /**
     * [cooked-info]: the recipe info bar (author, times, servings, ...).
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|void
     */
    public function cooked_info_shortcode( $atts ) {
        global $recipe, $recipe_settings, $_cooked_settings;

        if ( ! isset( $recipe_settings['id'] ) && isset( $recipe->ID ) ) :
            $recipe_settings['id'] = $recipe->ID;
        elseif ( ! isset( $recipe_settings['id'] ) ) :
            global $post;
            $recipe_settings['id'] = $post->ID;
        endif;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'left'    => false,
				'right'   => false,
				'include' => false,
				'exclude' => false,
			],
            $atts
        );

        $left    = $atts['left'] ? array_map( 'trim', explode( ',', \Cooked_Functions::sanitize_text_field( $atts['left'] ) ) ) : false;
        $right   = $atts['right'] ? array_map( 'trim', explode( ',', \Cooked_Functions::sanitize_text_field( $atts['right'] ) ) ) : false;
        $include = $atts['include'] ? array_map( 'trim', explode( ',', \Cooked_Functions::sanitize_text_field( $atts['include'] ) ) ) : false;
        $exclude = $atts['exclude'] ? array_map( 'trim', explode( ',', \Cooked_Functions::sanitize_text_field( $atts['exclude'] ) ) ) : false;

        $default_info_array = apply_filters(
            'cooked_default_info_array',
            [
				'author'           => __( 'Author', 'cooked' ),
				'difficulty_level' => __( 'Difficulty', 'cooked' ),
				'servings'         => __( 'Yields', 'cooked' ),
				'prep_time'        => __( 'Prep Time', 'cooked' ),
				'cook_time'        => __( 'Cook Time', 'cooked' ),
				'total_time'       => __( 'Total Time', 'cooked' ),
				'taxonomies'       => ! empty( $_cooked_settings['recipe_taxonomies'] ) ? $_cooked_settings['recipe_taxonomies'] : [],
			]
        );

        if ( $left ) :
            // Left Content.
            foreach ( $left as $val ) :
                $info_array['left'][ $val ] = isset( $default_info_array[ $val ] ) ? $default_info_array[ $val ] : '';
            endforeach;
            if ( $right ) :
                // Right Content.
                foreach ( $right as $val ) :
                    $info_array['right'][ $val ] = isset( $default_info_array[ $val ] ) ? $default_info_array[ $val ] : '';
                endforeach;
            endif;
        elseif ( $right ) :
            // Right Content.
            foreach ( $right as $val ) :
                $info_array['right'][ $val ] = isset( $default_info_array[ $val ] ) ? $default_info_array[ $val ] : '';
            endforeach;
        elseif ( $include ) :
            // Include Content (left).
            foreach ( $include as $val ) :
                $info_array[ $val ] = isset( $default_info_array[ $val ] ) ? $default_info_array[ $val ] : '';
            endforeach;
        elseif ( $exclude ) :
            // Exclude Content (left).
            $info_array = $default_info_array;
            foreach ( $exclude as $val ) :
                unset( $info_array[ $val ] );
            endforeach;
        else :
            $info_array = $default_info_array;
        endif;

        ob_start();

        if ( ! empty( $info_array ) ) :

            $available_methods = apply_filters(
                'cooked_available_info_shortcode_methods',
                [
					'cooked_info_author'     => 'Cooked_Recipes',
					'cooked_info_difficulty' => 'Cooked_Recipes',
					'cooked_info_servings'   => 'Cooked_Recipes',
					'cooked_info_print'      => 'Cooked_Recipes',
					'cooked_info_fullscreen' => 'Cooked_Recipes',
					'cooked_info_prep_time'  => 'Cooked_Recipes',
					'cooked_info_cook_time'  => 'Cooked_Recipes',
					'cooked_info_total_time' => 'Cooked_Recipes',
					'cooked_info_taxonomies' => 'Cooked_Recipes',
				]
            );

            if ( isset( $info_array['left'] ) && ! empty( $info_array['left'] ) ) :
                echo '<section class="cooked-left">';
				foreach ( $info_array['left'] as $name => $val ) :
					$function = 'cooked_info_' . $name;
					if ( array_key_exists( $function, $available_methods ) ) :
						$class = ( 'Cooked_Recipes' === $available_methods[ $function ] ? $this : $available_methods[ $function ] );
						if ( method_exists( $class, $function ) ) :
							$class::$function( $recipe_settings );
                            endif;
                        endif;
                    endforeach;
                echo '</section>';

                if ( isset( $info_array['right'] ) && ! empty( $info_array['right'] ) ) :
                    echo '<section class="cooked-right">';
					foreach ( $info_array['right'] as $name => $val ) :
						$function = 'cooked_info_' . $name;
						if ( array_key_exists( $function, $available_methods ) ) :
							$class = ( 'Cooked_Recipes' === $available_methods[ $function ] ? $this : $available_methods[ $function ] );
							if ( method_exists( $class, $function ) ) :
								$class::$function( $recipe_settings );
                                endif;
                            endif;
                        endforeach;
                    echo '</section>';
                endif;

            elseif ( isset( $info_array['right'] ) && ! empty( $info_array['right'] ) ) :

                echo '<section class="cooked-right">';
				foreach ( $info_array['right'] as $name => $val ) :
					$function = 'cooked_info_' . $name;
					if ( array_key_exists( $function, $available_methods ) ) :
						$class = ( 'Cooked_Recipes' === $available_methods[ $function ] ? $this : $available_methods[ $function ] );
						if ( method_exists( $class, $function ) ) :
							$class::$function( $recipe_settings );
                            endif;
                        endif;
                    endforeach;
                echo '</section>';

            else :

                foreach ( $info_array as $name => $val ) :
                    $function = 'cooked_info_' . $name;
                    if ( array_key_exists( $function, $available_methods ) ) :
                        $class = ( 'Cooked_Recipes' === $available_methods[ $function ] ? $this : $available_methods[ $function ] );
                        if ( method_exists( $class, $function ) ) :
                            $class::$function( $recipe_settings );
                        endif;
                    endif;
                endforeach;

            endif;

        endif;

        $cooked_info_html = ob_get_clean();

        if ( $cooked_info_html ) :
            // Allow the servings switcher's <select> and Cooked Pro's nutrition donut <script>
            // for this one call only; left in place they would loosen every later KSES call.
            add_filter( 'wp_kses_allowed_html', [ $this, 'cooked_kses_servings_switcher' ] );
            add_filter( 'wp_kses_allowed_html', [ $this, 'cooked_kses_cooked_donut' ] );
            $cooked_info_html = '<div class="cooked-recipe-info cooked-clearfix">' . wp_kses_post( $cooked_info_html ) . '</div>';
            remove_filter( 'wp_kses_allowed_html', [ $this, 'cooked_kses_servings_switcher' ] );
            remove_filter( 'wp_kses_allowed_html', [ $this, 'cooked_kses_cooked_donut' ] );
            $cooked_info_html = apply_filters( 'cooked_info_shortcode_output', $cooked_info_html, $recipe_settings );
            return $cooked_info_html;
        endif;
    }

    /**
     * Allows the servings switcher's <select> through KSES.
     *
     * @param array<string, mixed> $tags Allowed tags and attributes.
     * @return array<string, mixed>
     */
    public function cooked_kses_servings_switcher( $tags ) {
        $tags['select'] = [
            'name'  => true,
            'id'    => true,
            'class' => true,
        ];
        $tags['option'] = [
            'value'    => true,
            'selected' => true,
        ];
        return $tags;
    }

    /**
     * Allows Cooked Pro's nutrition donut <script> through KSES.
     *
     * @param array<string, mixed> $tags Allowed tags and attributes.
     * @return array<string, mixed>
     */
    public function cooked_kses_cooked_donut( $tags ) {
        $tags['script'] = [
            'type' => true,
        ];
        return $tags;
    }

    /**
     * Outputs the recipe author for [cooked-info].
     *
     * @return void
     */
    public static function cooked_info_author() {
        global $recipe_settings, $_cooked_settings;

        if ( in_array( 'author', $_cooked_settings['recipe_info_display_options'], true ) ) {
            $browse_page_id  = \Cooked_Multilingual::get_browse_page_id();
            $front_page_id   = get_option( 'page_on_front' );
            $browse_page_url = $browse_page_id ? get_permalink( $browse_page_id ) : false;
            $author          = ! empty( $recipe_settings['author'] ) ? $recipe_settings['author'] : false;

            if ( ! empty( $author['id'] ) && ! empty( $browse_page_id ) ) {
                // Generate author slug from user_nicename, fallback to user ID if nicename is empty.
                if ( ! empty( $author['user_nicename'] ) ) {
                    $author_slug = rawurlencode( sanitize_title( $author['user_nicename'] ) );
                } else {
                    // Fallback to user ID if user_nicename is empty or missing.
                    $author_slug = $author['id'];
                }

                $permalink = (int) $front_page_id !== (int) $browse_page_id && get_option( 'permalink_structure' ) ?
                                esc_url( untrailingslashit( $browse_page_url ) . '/' . $_cooked_settings['recipe_author_permalink'] . '/' . trailingslashit( $author_slug ) ) :
                                esc_url( trailingslashit( get_home_url() ) . 'index.php?page_id=' . $browse_page_id . '&recipe_author=' . $author['id'] );

                $permalink = apply_filters( 'cooked_author_permalink', $permalink, $author['id'], $author_slug );
            } else {
                $permalink = false;
            }

            $author_links = isset( $_cooked_settings['disable_author_links'][0] ) && 'disabled' === $_cooked_settings['disable_author_links'][0] ? false : true;
            $clickable    = ( isset( $_cooked_settings['advanced'] ) && ! empty( $_cooked_settings['advanced'] ) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'], true ) ) || ! $author_links ? false : true;
            $hide_avatars = isset( $_cooked_settings['hide_author_avatars'][0] ) && 'hidden' === $_cooked_settings['hide_author_avatars'][0] ? true : false;

            echo '<span class="cooked-author' . ( $hide_avatars ? ' cooked-no-avatar' : '' ) . '">';
                echo ! $hide_avatars ? '<span class="cooked-author-avatar">' . ( ! empty( $author ) ? wp_kses_post( $author['profile_photo'] ) : '' ) . '</span>' : '';
                echo '<strong class="cooked-meta-title">' . esc_html__( 'Author', 'cooked' ) . '</strong>' . ( $clickable && $permalink ? '<a href="' . esc_url( $permalink ) . '">' : '' ) . ( ! empty( $author ) ? esc_html( $author['name'] ) : '' ) . ( $clickable && $permalink ? '</a>' : '' );
            echo '</span>';

            wp_reset_postdata();
        }
    }

    /**
     * Outputs the difficulty level for [cooked-info].
     *
     * @param array<string, mixed> $recipe Recipe settings.
     * @return void
     */
    public static function cooked_info_difficulty( $recipe ) {
        global $_cooked_settings;

        if ( in_array( 'difficulty_level', $_cooked_settings['recipe_info_display_options'], true ) && isset( $recipe['difficulty_level'] ) && $recipe['difficulty_level'] ) {
            $dl_html = '<span class="cooked-difficulty-level"><strong class="cooked-meta-title">' . esc_html__( 'Difficulty', 'cooked' ) . '</strong>' . \Cooked_Recipes::difficulty_level( $recipe['difficulty_level'] ) . '</span>';
            echo wp_kses_post( apply_filters( 'cooked_show_difficulty_level', $dl_html, $recipe['difficulty_level'] ) );
        }
    }

    /**
     * Outputs the servings and measurement switchers for [cooked-info].
     *
     * @param array<string, mixed> $recipe Recipe settings.
     * @return void
     */
    public static function cooked_info_servings( $recipe ) {
        global $_cooked_settings;

        if ( in_array( 'servings', $_cooked_settings['recipe_info_display_options'], true ) ) {
            $servings = isset( $recipe['nutrition']['servings'] ) && $recipe['nutrition']['servings'] ? $recipe['nutrition']['servings'] : 1;
            \Cooked_Recipes::serving_size_switcher( $servings );
            \Cooked_Recipes::measurement_system_switcher();
        }
    }

    /**
     * Outputs the print button for [cooked-info].
     *
     * @return void
     */
    public static function cooked_info_print() {
        global $recipe_settings, $_cooked_settings;

        $recipe_post_url        = get_permalink( $recipe_settings['id'] );
        $query_args['print']    = 1;
        $servings               = (float) esc_html( get_query_var( 'servings', false ) );
        $query_args['servings'] = ! empty( $servings ) ? $servings : false;
        if ( \Cooked_Recipes::public_recipes_disabled() ) {
            $query_args['print_key'] = \Cooked_Recipes::print_key( $recipe_settings['id'] );
        }
        echo '<span class="cooked-print"><a aria-label="' . esc_attr__( 'Print', 'cooked' ) . '" target="_blank" rel="nofollow" href="' . esc_url( add_query_arg( $query_args, $recipe_post_url ) ) . '" class="cooked-print-icon"><i class="cooked-icon cooked-icon-print"></i></a></span>';
    }

    /**
     * Outputs the full-screen button for [cooked-info].
     *
     * @return void
     */
    public static function cooked_info_fullscreen() {
        global $recipe_settings, $_cooked_settings;

        echo '<span aria-label="' . esc_attr__( 'Fullscreen', 'cooked' ) . '" role="button" class="cooked-fsm-button" data-recipe-id="' . esc_attr( $recipe_settings['id'] ) . '"><i class="cooked-icon cooked-icon-fullscreen"></i></span>';
        wp_enqueue_script( 'cooked-nosleep' );
    }

    /**
     * Outputs the prep time for [cooked-info].
     *
     * @param array<string, mixed> $recipe Recipe settings.
     * @return void
     */
    public static function cooked_info_prep_time( $recipe ) {
        global $_cooked_settings;

        if ( ! empty( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'timing_prep', $_cooked_settings['recipe_info_display_options'], true ) ) {
            $prep_time = isset( $recipe['prep_time'] ) ? \Cooked_Measurements::normalize_minutes( $recipe['prep_time'] ) : 0;
            echo $prep_time ? '<span class="cooked-prep-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . esc_html__( 'Prep Time', 'cooked' ) . '</strong>' . wp_kses_post( \Cooked_Measurements::time_format( $prep_time ) ) . '</span>' : '';
        }
    }

    /**
     * Outputs the cook time for [cooked-info].
     *
     * @param array<string, mixed> $recipe Recipe settings.
     * @return void
     */
    public static function cooked_info_cook_time( $recipe ) {
        global $_cooked_settings;

        if ( ! empty( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'timing_cook', $_cooked_settings['recipe_info_display_options'], true ) ) {
            $cook_time = isset( $recipe['cook_time'] ) ? \Cooked_Measurements::normalize_minutes( $recipe['cook_time'] ) : 0;
            echo $cook_time ? '<span class="cooked-cook-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . esc_html__( 'Cook Time', 'cooked' ) . '</strong>' . wp_kses_post( \Cooked_Measurements::time_format( $cook_time ) ) . '</span>' : '';
        }
    }

    /**
     * Outputs the total time for [cooked-info].
     *
     * @param array<string, mixed> $recipe Recipe settings.
     * @return void
     */
    public static function cooked_info_total_time( $recipe ) {
        global $_cooked_settings;

        if ( ! empty( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'timing_total', $_cooked_settings['recipe_info_display_options'], true ) ) {
            $total_time = isset( $recipe['total_time'] ) ? \Cooked_Measurements::normalize_minutes( $recipe['total_time'] ) : 0;

            if ( $total_time ) {
                echo $total_time ? '<span class="cooked-total-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . esc_html__( 'Total Time', 'cooked' ) . '</strong>' . wp_kses_post( \Cooked_Measurements::time_format( $total_time ) ) . '</span>' : '';
            } else {
                $prep_time = isset( $recipe['prep_time'] ) ? \Cooked_Measurements::normalize_minutes( $recipe['prep_time'] ) : 0;
                $cook_time = isset( $recipe['cook_time'] ) ? \Cooked_Measurements::normalize_minutes( $recipe['cook_time'] ) : 0;

                if ( $prep_time && $cook_time ) {
                    $total_time = $prep_time + $cook_time;
                    echo $total_time ? '<span class="cooked-total-time cooked-time"><span class="cooked-time-icon"><i class="cooked-icon cooked-icon-clock"></i></span><strong class="cooked-meta-title">' . esc_html__( 'Total Time', 'cooked' ) . '</strong>' . wp_kses_post( \Cooked_Measurements::time_format( $total_time ) ) . '</span>' : '';
                }
            }
        }
    }

    /**
     * Outputs the recipe categories for [cooked-info].
     *
     * @return void
     */
    public static function cooked_info_taxonomies() {
        global $recipe_settings, $_cooked_settings, $clickable;

        $clickable = isset( $_cooked_settings['advanced'] ) && ! empty( $_cooked_settings['advanced'] ) && in_array( 'disable_public_recipes', $_cooked_settings['advanced'], true ) ? false : true;

        if ( ! empty( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'taxonomies', $_cooked_settings['recipe_info_display_options'], true ) ) :

            global $recipe_terms_list;
            $recipe_terms_list = '';

            do_action( 'cooked_info_taxonomies_shortcode_before', $recipe_settings );

            if ( ! empty( $_cooked_settings['recipe_taxonomies'] ) && in_array( 'cp_recipe_category', $_cooked_settings['recipe_taxonomies'], true ) ) :
                if ( $clickable ) :
                    $recipe_terms_list .= get_the_term_list( $recipe_settings['id'], 'cp_recipe_category', '<span class="cooked-taxonomy cooked-category"><strong class="cooked-meta-title">' . __( 'Category', 'cooked' ) . '</strong>', ', ', '</span>' );
                else :
                    $_recipe_terms_array    = [];
                    $recipe_terms_list     .= '<span class="cooked-taxonomy cooked-category"><strong class="cooked-meta-title">' . __( 'Category', 'cooked' ) . '</strong>';
                        $recipe_terms_array = wp_get_object_terms( $recipe_settings['id'], 'cp_recipe_category' );
					if ( ! empty( $recipe_terms_array ) ) :
						if ( ! is_wp_error( $recipe_terms_array ) ) :
							foreach ( $recipe_terms_array as $recipe_term ) :
								$_recipe_terms_array[] = esc_html( $recipe_term->name );
                                endforeach;
                            endif;
                        endif;
                        $recipe_terms_list .= implode( ', ', $_recipe_terms_array );
                    $recipe_terms_list     .= '</span>';
                endif;
            endif;

            do_action( 'cooked_info_taxonomies_shortcode_after', $recipe_settings );

            if ( $recipe_terms_list ) :
                echo wp_kses_post( $recipe_terms_list );
            endif;

        endif;
    }

    /**
     * [cooked-excerpt]: the recipe excerpt.
     *
     * @return string|false|void
     */
    public function cooked_excerpt_shortcode() {
        global $_cooked_settings, $recipe_settings;

        if ( isset( $_cooked_settings['recipe_info_display_options'] ) && is_array( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'excerpt', $_cooked_settings['recipe_info_display_options'], true ) ) {
            ob_start();

            if ( isset( $recipe_settings['excerpt'] ) && $recipe_settings['excerpt'] ) {
                $excerpt = \Cooked_Recipes::format_content( $recipe_settings['excerpt'] );
                echo '<div class="cooked-recipe-excerpt cooked-clearfix">' . do_shortcode( $excerpt ) . '</div>';
            }

            return ob_get_clean();
        }
    }

    /**
     * [cooked-notes]: the recipe notes.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false|void
     */
    public function cooked_notes_shortcode( $atts ) {
        global $_cooked_settings, $recipe_settings;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'show_header' => false,
			],
            $atts
        );

        $show_header = \Cooked_Functions::sanitize_text_field( $atts['show_header'] );
        $show_header = ! $show_header || 'false' === $show_header ? false : $show_header;

        if ( isset( $_cooked_settings['recipe_info_display_options'] ) && is_array( $_cooked_settings['recipe_info_display_options'] ) && in_array( 'notes', $_cooked_settings['recipe_info_display_options'], true ) ) {
            ob_start();

            if ( isset( $recipe_settings['notes'] ) && ! empty( $recipe_settings['notes'] ) ) {
                $notes       = \Cooked_Recipes::format_content( $recipe_settings['notes'] );
                $show_header = $show_header ? '<div class="cooked-heading">' . esc_html__( 'Notes', 'cooked' ) . '</div>' : '';

                echo '<div class="cooked-recipe-notes cooked-clearfix">';
                echo wp_kses_post( $show_header );
                echo do_shortcode( $notes );
                echo '</div>';
            }

            return ob_get_clean();
        }
    }

    /**
     * [cooked-title]: the recipe title.
     *
     * @return string
     */
    public function cooked_title_shortcode() {
        global $recipe, $recipe_settings;

        if ( ! isset( $recipe_settings['id'] ) ) {
            $recipe_settings['id'] = $recipe->ID;
        }

        return esc_html( get_the_title( $recipe_settings['id'] ) );
    }

    /**
     * [cooked-image]: the featured image.
     *
     * @return string|false
     */
    public function cooked_image_shortcode() {
        global $recipe, $recipe_settings;

        if ( ! isset( $recipe_settings['id'] ) ) :
            $recipe_settings['id'] = $recipe->ID;
        endif;

        $recipe = get_post( $recipe_settings['id'] );
        wp_reset_postdata();

        ob_start();

        if ( has_post_thumbnail( $recipe ) ) :
            echo '<div class="cooked-post-featured-image">';
                echo get_the_post_thumbnail( $recipe, 'cooked-large' );
            echo '</div>';
        endif;

        return ob_get_clean();
    }

    /**
     * [cooked-ingredients]: the ingredient list.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_ingredients_shortcode( $atts ) {
        global $recipe_settings;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'checkboxes' => true,
			],
            $atts
        );

        $checkboxes = \Cooked_Functions::sanitize_text_field( $atts['checkboxes'] );
        $checkboxes = ! $checkboxes || 'false' === $checkboxes ? false : $checkboxes;

        ob_start();

        do_action( 'cooked_ingredients_shortcode_before', $recipe_settings );

        if ( isset( $recipe_settings['ingredients'] ) && ! empty( $recipe_settings['ingredients'] ) ) :
            echo '<div class="cooked-recipe-ingredients">';
			foreach ( $recipe_settings['ingredients'] as $ing ) :
				\Cooked_Recipes::single_ingredient( $ing, $checkboxes );
                endforeach;
            echo '</div>';
        endif;

        do_action( 'cooked_ingredients_shortcode_after', $recipe_settings );

        return ob_get_clean();
    }

    /**
     * [cooked-directions]: the directions.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_directions_shortcode( $atts ) {
        global $recipe_settings;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            apply_filters(
                'cooked_directions_shortcode_atts',
                [
					'numbers' => true,
				]
            ),
            $atts
        );

        $step    = 0;
        $numbers = \Cooked_Functions::sanitize_text_field( $atts['numbers'] );
        $numbers = ! $numbers || 'false' === $numbers ? false : $numbers;

        ob_start();

        if ( isset( $recipe_settings['directions'] ) && ! empty( $recipe_settings['directions'] ) ) {
            echo '<div class="cooked-recipe-directions">';
			foreach ( $recipe_settings['directions'] as $dir ) {
				if ( ! isset( $dir['section_heading_name'] ) ) {
					++$step;
					$number = $numbers ? $step : false;
				} else {
					$number = 0;
				}

				\Cooked_Recipes::single_direction( $dir, $number, false, $step, $atts );
			}
            echo '</div>';
        }

        return ob_get_clean();
    }

    /**
     * [cooked-nutrition]: the nutrition facts label.
     *
     * @param array<string, mixed> $atts    Shortcode attributes.
     * @return string|false
     */
    public function cooked_nutrition_shortcode( $atts ) {
        global $_cooked_settings, $recipe_settings;

        // Shortcode Attributes.
        $atts = shortcode_atts(
            [
				'id'    => false,
				'float' => false,
			],
            $atts
        );

        if ( isset( $atts['id'] ) && $atts['id'] ) :
            $recipe_settings = \Cooked_Recipes::get( intval( $atts['id'] ), true );
        else :
            $recipe_settings = ! empty( $recipe_settings ) ? $recipe_settings : false;
        endif;

        $float = \Cooked_Functions::sanitize_text_field( $atts['float'] );

        $_nf_fields      = \Cooked_Measurements::nutrition_facts();
        $nutrition_facts = isset( $recipe_settings['nutrition'] ) && ! empty( $recipe_settings['nutrition'] ) ? $recipe_settings['nutrition'] : false;

        ob_start();

        if ( $nutrition_facts ) :

            $servings_change = (float) esc_html( get_query_var( 'servings', $recipe_settings['nutrition']['servings'] ) );

            $top_facts = $_nf_fields['top'];
            if ( ! empty( $top_facts ) ) :

                // Start output buffer for top facts.
                ob_start();

                echo '<div class="cooked-nut-servings">';
                foreach ( $top_facts as $slug => $nf ) :
                    if ( 'serving_size' === $slug ) :
                        echo '<div class="cooked-serving-size"><strong>' . esc_html( $nf['name'] ) . '</strong> ';
                            echo '<p class="cooked-right"><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">' . esc_html( isset( $nutrition_facts[ $slug ] ) ? $nutrition_facts[ $slug ] : '' ) . '</strong></p>';
                        echo '</div>';
                    else :
                        echo '<p><strong class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">' . esc_html( $servings_change ) . '</strong> ' . esc_html( strtolower( $nf['name'] ) ) . '</p>';
                    endif;
                endforeach;
                echo '</div>';

                // Get top facts content from buffer.
                $top_facts_content = ob_get_clean();

            endif;

            $mid_facts = $_nf_fields['mid'];
            if ( ! empty( $mid_facts ) ) :

                // Start output buffer for mid-facts.
                ob_start();

                foreach ( $mid_facts as $slug => $nf ) :
                    if ( ( isset( $nutrition_facts[ $slug ] ) && $nutrition_facts[ $slug ] ) || ( isset( $nutrition_facts[ $slug ] ) && '0' === $nutrition_facts[ $slug ] ) ) :
                        echo '<dt class="cooked-calories no-after"><strong>' . esc_html( $nf['name'] ) . '</strong>';
                            echo '<strong class="cooked-nut-label">' . esc_html( $nutrition_facts[ $slug ] ) . '</strong>';
                        echo '</dt>';
                    endif;
                endforeach;

                // Get mid facts content from buffer.
                $mid_facts_content = ob_get_clean();

            endif;

            $main_facts = $_nf_fields['main'];
            $nut_loops  = 0;

            if ( ! empty( $main_facts ) ) :

                // Start output buffer for main facts.
                ob_start();

                foreach ( $main_facts as $slug => $nf ) :

                    if ( ( isset( $nutrition_facts[ $slug ] ) && $nutrition_facts[ $slug ] ) || ( isset( $nutrition_facts[ $slug ] ) && '0' === $nutrition_facts[ $slug ] ) ) :

						echo '<dt>';
						echo '<strong>' . esc_html( $nf['name'] ) . '</strong> <strong class="cooked-nut-label">' . esc_html( $nutrition_facts[ $slug ] ) . '</strong>' . ( isset( $nf['measurement'] ) ? '<strong class="cooked-nut-label cooked-nut-measurement">' . esc_html( $nf['measurement'] ) . '</strong>' : '' );
						echo ( isset( $nf['pdv'] ) && $nutrition_facts[ $slug ] ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent">' . esc_html( ceil( ( $nutrition_facts[ $slug ] / $nf['pdv'] ) * 100 ) ) . '</span>%</strong>' : '' );

						if ( isset( $nf['subs'] ) ) :
							foreach ( $nf['subs'] as $sub_slug => $sub_nf ) :
								if ( ( isset( $nutrition_facts[ $sub_slug ] ) && $nutrition_facts[ $sub_slug ] ) || ( isset( $nutrition_facts[ $sub_slug ] ) && '0' === $nutrition_facts[ $sub_slug ] ) ) :
									echo '<dl>';
									if ( 'trans_fat' === $sub_slug ) :
										echo '<dt>';
                                        echo wp_kses_post( $sub_nf['nutrition_info_name'] ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">' . esc_html( $nutrition_facts[ $sub_slug ] ) . '</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' );
										echo '</dt>';
									elseif ( 'added_sugars' === $sub_slug ) :
										echo '<dl><dt>';
                                        echo esc_html__( 'Includes', 'cooked' ) . ' <strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '">' . esc_html( $nutrition_facts[ $sub_slug ] ) . '</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label" data-labeltype="' . esc_attr( $sub_slug ) . '_measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' ) . ' ' . esc_html( $sub_nf['name'] );
                                        echo ( isset( $sub_nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $sub_nf['pdv'] ) . '" data-labeltype="' . esc_attr( $sub_slug ) . '">' . esc_html( ceil( ( $nutrition_facts[ $sub_slug ] / $sub_nf['pdv'] ) * 100 ) ) . '</span>%</strong>' : '' );
										echo '</dt></dl>';
									else :
										echo '<dt>';
                                        echo esc_html( $sub_nf['name'] ) . ' <strong class="cooked-nut-label">' . esc_html( $nutrition_facts[ $sub_slug ] ) . '</strong>' . ( isset( $sub_nf['measurement'] ) ? '<strong class="cooked-nut-label cooked-nut-measurement">' . esc_html( $sub_nf['measurement'] ) . '</strong>' : '' );
                                        echo ( isset( $sub_nf['pdv'] ) && $nutrition_facts[ $sub_slug ] ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent">' . esc_html( ceil( ( $nutrition_facts[ $sub_slug ] / $sub_nf['pdv'] ) * 100 ) ) . '</span>%</strong>' : '' );
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

            if ( ! empty( $bottom_facts ) ) :

                // Start output buffer for bottom facts.
                ob_start();

                foreach ( $bottom_facts as $slug => $nf ) :
                    if ( ( isset( $nutrition_facts[ $slug ] ) && $nutrition_facts[ $slug ] ) || ( isset( $nutrition_facts[ $slug ] ) && '0' === $nutrition_facts[ $slug ] ) ) :
                        echo '<dt>';
                            echo '<strong>' . esc_html( $nf['name'] ) . '</strong> <span class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '">' . esc_html( $nutrition_facts[ $slug ] ) . '</span>' . ( isset( $nf['measurement'] ) ? '<span class="cooked-nut-label" data-labeltype="' . esc_attr( $slug ) . '_measurement">' . esc_html( $nf['measurement'] ) . '</span>' : '' );
                            echo ( isset( $nf['pdv'] ) ? '<strong class="cooked-nut-right"><span class="cooked-nut-percent" data-pdv="' . esc_attr( $nf['pdv'] ) . '" data-labeltype="' . esc_attr( $slug ) . '">' . esc_html( ceil( ( $nutrition_facts[ $slug ] / $nf['pdv'] ) * 100 ) ) . '</span>%</strong>' : '' );
                        echo '</dt>';
                    endif;
                endforeach;

                // Get bottom facts content from buffer.
                $bottom_facts_content = ob_get_clean();

            endif;

            // Start a buffer for all nutrition facts content.
            ob_start();

            if ( isset( $top_facts_content ) && $top_facts_content ) :
                echo wp_kses_post( $top_facts_content );
            endif;

            if ( ( isset( $mid_facts_content ) && $mid_facts_content ) || ( isset( $main_facts_content ) && $main_facts_content ) ) :

                echo '<hr class="cooked-nut-hr" />';
                echo '<dl>';

                    echo '<dt><strong class="cooked-nut-heading">' . esc_html__( 'Amount per serving', 'cooked' ) . '</strong></dt>';

				if ( isset( $mid_facts_content ) && $mid_facts_content ) :
					echo '<section class="cooked-clearfix">';
						echo wp_kses_post( $mid_facts_content );
					echo '</section>';
                    endif;

				if ( isset( $main_facts_content ) && $main_facts_content ) :
					echo '<dt class="cooked-nut-spacer"></dt>';
					echo '<dt class="cooked-nut-no-border"><strong class="cooked-nut-heading cooked-nut-right">' . esc_html__( '% Daily Value *', 'cooked' ) . '</strong></dt>';
					echo '<section class="cooked-clearfix">';
						echo wp_kses_post( $main_facts_content );
					echo '</section>';
                    endif;

                echo '</dl>';
                echo '<hr class="cooked-nut-hr" />';

            endif;

            if ( isset( $bottom_facts_content ) && $bottom_facts_content ) :
                echo '<dl class="cooked-nut-bottom cooked-clearfix">';
                    echo wp_kses_post( $bottom_facts_content );
                echo '</dl>';
            endif;

            $nutrition_facts_content = ob_get_clean();

            if ( isset( $nutrition_facts_content ) && $nutrition_facts_content ) :

                echo '<div class="cooked-nutrition-label' . ( $float ? ' cooked-float-' . esc_attr( $float ) : '' ) . '">';
                    echo '<div class="cooked-nutrition-title">' . esc_html__( 'Nutrition Facts', 'cooked' ) . '</div>';
                    echo wp_kses_post( $nutrition_facts_content );
				if ( ( isset( $main_facts_content ) && $main_facts_content ) || ( isset( $bottom_facts_content ) && $bottom_facts_content ) ) :
					echo '<div class="cooked-nut-spacer"></div>';
					echo '<p class="cooked-daily-value-text">* ' . esc_html__( 'The % Daily Value (DV) tells you how much a nutrient in a serving of food contributes to a daily diet. 2,000 calories a day is used for general nutrition advice.', 'cooked' ) . '</p>';
                    endif;

                    // Add the Edamam attribution "Powered by Edamam" if the Edamam API is enabled.
                    // More Information: https://developer.edamam.com/attribution.
				if ( ! empty( $_cooked_settings['enable_nutrition_api'] ) ) :
					echo '<div class="cooked-nutrition-edamam">';
						do_action( 'cooked_nutrition_facts_powered_by_edamam' );
					echo '</div>';
                    endif;

                echo '</div>';

            endif;

        endif;

        return ob_get_clean();
    }

    /**
     * Related Recipes Shortcode
     *
     * Displays related recipes based on shared terms in any recipe taxonomy (categories,
     * cuisines, cooking methods, tags, diets), random order. No scoring, ingredients, or cache.
     *
     * @param array $atts Shortcode attributes.
     * @return string Related recipes HTML.
     * @since 1.0.0
     */
    public function cooked_related_recipes_shortcode( $atts ) {
        if ( is_admin() ) {
			return false;
        }

        global $post, $_cooked_settings;

        // Shortcode Attributes.
        $atts = shortcode_atts( \Cooked_Related_Recipes::get_default_atts(), $atts );

        // Get recipe ID - validate if provided, otherwise try current post.
        $recipe_id = false;

        if ( ! empty( $atts['id'] ) ) {
            // Validate that the ID is numeric before converting.
            if ( is_numeric( $atts['id'] ) && $atts['id'] > 0 ) {
                $recipe_id = (int) $atts['id'];
            } else {
                // Invalid ID provided (e.g., "asdas", "", "0", "-5").
                return '<p class="cooked-related-recipes-error">' . __( 'Invalid recipe ID specified. Please provide a valid numeric recipe ID.', 'cooked' ) . '</p>';
            }
        } elseif ( isset( $post->ID ) && get_post_type( $post->ID ) === 'cp_recipe' ) {
            // No ID provided, use the current post.
            $recipe_id = $post->ID;
        }

        if ( ! $recipe_id ) {
            return '<p class="cooked-related-recipes-error">' . __( 'No recipe found. Please specify a recipe ID using the id attribute, or use this shortcode on a recipe page.', 'cooked' ) . '</p>';
        }

        // Get the source recipe (allow any status to support drafts/pending).
        $source_recipe = \Cooked_Recipes::get( $recipe_id, true, false, false, false, 'any' );

        if ( ! $source_recipe || empty( $source_recipe ) ) {
            $error_msg = __( 'Recipe not found.', 'cooked' );
            // Check if it's a different post type.
            $post_check = get_post( $recipe_id );
            if ( $post_check && 'cp_recipe' !== $post_check->post_type ) {
                /* translators: %d: post ID */
                $error_msg .= ' ' . sprintf( __( 'The specified ID (%d) is not a recipe.', 'cooked' ), $recipe_id );
            } elseif ( ! $post_check ) {
                /* translators: %d: post ID */
                $error_msg .= ' ' . sprintf( __( 'No post found with ID %d.', 'cooked' ), $recipe_id );
            }
            return '<p class="cooked-related-recipes-error">' . esc_html( $error_msg ) . '</p>';
        }

        // Find related recipes (on-demand query: taxonomies OR + orderby rand, no cache).
        $related_recipes = \Cooked_Related_Recipes::get_related_recipes( $recipe_id, $atts );

        if ( empty( $related_recipes ) ) {
            $empty_msg = __( 'No related recipes found.', 'cooked' );
            // Provide helpful context.
            if ( class_exists( 'Cooked_Multilingual' ) && \Cooked_Multilingual::is_multilingual_active() ) {
                $empty_msg .= ' ' . __( 'This may be because there are no other recipes in the current language with matching attributes.', 'cooked' );
            } else {
                $empty_msg .= ' ' . __( 'Try adjusting the matching criteria or ensure you have other published recipes with shared categories, tags, or ingredients.', 'cooked' );
            }
            return '<p class="cooked-related-recipes-empty">' . $empty_msg . '</p>';
        }

        // Get recipe IDs for display and filter out deleted/invalid recipes
        // Note: Language filtering is already handled by WP_Query in find_related_recipes().
        $recipe_ids = array_column( $related_recipes, 'id' );

        /**
         * Filter recipe IDs before display.
         *
         * @since 1.12.0
         *
         * @param array $recipe_ids     Array of recipe IDs to display.
         * @param array $related_recipes Full related recipes array (each item has 'id').
         * @param int   $recipe_id      Source recipe ID.
         * @param array $atts           Shortcode attributes.
         */
        $recipe_ids = apply_filters( 'cooked_related_recipes_display_ids', $recipe_ids, $related_recipes, $recipe_id, $atts );

        ob_start();

        // Display title.
        if ( $atts['title'] ) {
            echo '<h3 class="cooked-related-recipes-title">' . esc_html( $atts['title'] ) . '</h3>';
        }

        // Display recipes in grid.
        $columns      = intval( $atts['columns'] );
        $hide_image   = wp_validate_boolean( $atts['hide_image'] );
        $hide_excerpt = wp_validate_boolean( $atts['hide_excerpt'] );
        $hide_author  = wp_validate_boolean( $atts['hide_author'] );

        echo '<div class="cooked-related-recipes-grid cooked-recipe-grid cooked-columns-' . esc_attr( $columns ) . '">';

        foreach ( $recipe_ids as $rid ) {
            echo '<div class="cooked-recipe">';
            echo wp_kses_post( \Cooked_Recipes::card( $rid, false, $hide_image, false, $hide_excerpt, $hide_author ) );
            echo '</div>';
        }

        echo '</div>';

        $output = ob_get_clean();

        /**
         * Filter the related recipes shortcode output HTML.
         *
         * @since 1.12.0
         *
         * @param string $output      The shortcode HTML output.
         * @param array  $recipe_ids Array of recipe IDs being displayed.
         * @param int    $recipe_id  Source recipe ID.
         * @param array  $atts       Shortcode attributes.
         */
        return apply_filters( 'cooked_related_recipes_output', $output, $recipe_ids, $recipe_id, $atts );
    }
}
