<?php
/**
 * Cooked Allergens
 *
 * @package     Cooked
 * @subpackage  Allergens
 * @since       1.15.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cooked_Allergens Class
 *
 * Handles allergen storage, admin UI, recipe card icons,
 * and the `allergens` field for the [cooked-info] shortcode.
 *
 * @since 1.15.0
 */
class Cooked_Allergens {

    /**
     * Initialize allergen hooks.
     *
     * @since 1.15.0
     */
    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_box' ] );

        foreach ( self::get_recipe_card_hooks() as $hook ) {
            add_action( $hook, [ __CLASS__, 'render_from_recipe' ], 10 );
        }

        add_filter( 'cooked_settings_tabs_fields', [ __CLASS__, 'settings_tabs_fields' ], 11 );

        // [cooked-info] integration
        add_filter( 'cooked_default_info_array', [ __CLASS__, 'register_info_field' ] );
        add_filter( 'cooked_available_info_shortcode_methods', [ __CLASS__, 'register_info_method' ] );
        add_filter( 'cooked_available_info_vars', [ __CLASS__, 'register_info_var' ] );
    }

    /**
     * Recipe card hooks used by Cooked list styles.
     *
     * @since 1.15.0
     * @return array
     */
    public static function get_recipe_card_hooks() {
        return apply_filters( 'cooked_recipe_card_allergen_hooks', [
            'cooked_recipe_grid_after_name',
            'cooked_recipe_modern_after_name',
            'cooked_recipe_full_after_name',
        ] );
    }

    /**
     * Add allergen display settings to the General settings tab.
     *
     * @since 1.15.0
     * @param array $settings Settings tabs and fields.
     * @return array
     */
    public static function settings_tabs_fields( $settings ) {
        if ( ! isset( $settings['recipe_settings']['fields'] ) ) {
            return $settings;
        }

        $after = isset( $settings['recipe_settings']['fields']['recipe_list_style'] )
            ? 'recipe_list_style'
            : 'browse_page';

        Cooked_Functions::array_splice_assoc( $settings['recipe_settings']['fields'], $after, 0, [
            'recipe_list_allergens' => [
                'title' => __( 'Recipe List Allergens', 'cooked' ),
                'desc'  => __( 'Show allergen icons on recipe cards in browse and list views.', 'cooked' ),
                'type'  => 'checkboxes',
                'default' => [
                    'enabled' => false,
                ],
                'options' => [
                    'enabled' => __( 'Show Allergens on Recipe Cards', 'cooked' ),
                ],
            ],
        ] );

        return $settings;
    }

    /**
     * Whether allergen icons should display on recipe list cards.
     *
     * @since 1.15.0
     * @return bool
     */
    public static function show_on_recipe_cards() {
        global $_cooked_settings;

        return ! empty( $_cooked_settings['recipe_list_allergens'] )
            && in_array( 'enabled', (array) $_cooked_settings['recipe_list_allergens'], true );
    }

    /**
     * Register the allergens meta box for the sidebar.
     *
     * @since 1.15.0
     */
    public static function register_meta_box() {
        add_meta_box(
            'cooked_allergens',
            __( 'Allergens', 'cooked' ),
            [ __CLASS__, 'render_meta_box' ],
            'cp_recipe',
            'side',
            'default'
        );
    }

    /**
     * Render the allergens meta box content.
     *
     * @since 1.15.0
     * @param WP_Post $post The post object.
     */
    public static function render_meta_box( $post ) {
        $recipe_settings = get_post_meta( $post->ID, '_recipe_settings', true );
        $selected = isset( $recipe_settings['allergens'] ) && is_array( $recipe_settings['allergens'] )
            ? $recipe_settings['allergens']
            : [];
        self::render_field_checkboxes( $selected );
    }

    /**
     * Render allergen checkbox field markup (admin metabox and front-end form).
     *
     * @since 1.15.0
     * @param array $selected Selected allergen storage keys.
     */
    public static function render_field_checkboxes( $selected = [] ) {
        if ( ! is_array( $selected ) ) {
            $selected = [];
        }

        $allergens = self::get_allergens();
        ?>
        <div class="cooked-allergens-checkboxes">
            <?php foreach ( $allergens as $key => $allergen ) : ?>
                <label class="cooked-allergen-checkbox">
                    <input type="checkbox"
                        name="_recipe_settings[allergens][]"
                        value="<?php echo esc_attr( $key ); ?>"
                        <?php checked( in_array( $key, $selected, true ) ); ?>>
                    <span class="cooked-allergen cooked-allergen-<?php echo esc_attr( $key ); ?>">
                        <i class="cooked-icon <?php echo esc_attr( self::get_icon_class( $allergen ) ); ?>" aria-hidden="true"></i>
                    </span>
                    <span class="cooked-allergen-label"><?php echo esc_html( $allergen['label'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Whitelist selected allergen keys against known definitions.
     *
     * @since 1.15.0
     * @param array $selected Submitted allergen keys.
     * @return array
     */
    public static function sanitize_selected( $selected ) {
        if ( ! is_array( $selected ) ) {
            return [];
        }

        $valid_keys = array_keys( self::get_allergens() );

        return array_values( array_intersect( $selected, $valid_keys ) );
    }

    /**
     * Get the list of major allergens (FDA + EU).
     *
     * @since 1.15.0
     * @return array
     */
    public static function get_allergens() {
        return apply_filters( 'cooked_allergens', [
            'celery' => [
                'label' => __( 'Celery', 'cooked' ),
                'icon'  => 'allergen-celery',
            ],
            'shellfish' => [
                'label' => __( 'Crustacean shellfish', 'cooked' ),
                'icon'  => 'allergen-shellfish',
            ],
            'eggs' => [
                'label' => __( 'Eggs', 'cooked' ),
                'icon'  => 'allergen-egg',
            ],
            'fish' => [
                'label' => __( 'Fish', 'cooked' ),
                'icon'  => 'allergen-fish',
            ],
            'lupin' => [
                'label' => __( 'Lupin', 'cooked' ),
                'icon'  => 'allergen-lupin',
            ],
            'milk' => [
                'label' => __( 'Milk', 'cooked' ),
                'icon'  => 'allergen-milk',
            ],
            'molluscs' => [
                'label' => __( 'Molluscs', 'cooked' ),
                'icon'  => 'allergen-molluscs',
            ],
            'mustard' => [
                'label' => __( 'Mustard', 'cooked' ),
                'icon'  => 'allergen-mustard',
            ],
            'peanuts' => [
                'label' => __( 'Peanuts', 'cooked' ),
                'icon'  => 'allergen-peanut',
            ],
            'sesame' => [
                'label' => __( 'Sesame', 'cooked' ),
                'icon'  => 'allergen-sesame',
            ],
            'soybeans' => [
                'label' => __( 'Soybeans', 'cooked' ),
                'icon'  => 'allergen-soy',
            ],
            'sulphites' => [
                'label' => __( 'Sulphur dioxide and sulphites', 'cooked' ),
                'icon'  => 'allergen-sulphites',
            ],
            'tree_nuts' => [
                'label' => __( 'Tree nuts', 'cooked' ),
                'icon'  => 'allergen-tree-nut',
            ],
            'wheat' => [
                'label' => __( 'Wheat', 'cooked' ),
                'icon'  => 'allergen-wheat',
            ],
        ] );
    }

    /**
     * Get the CookedIcons class for an allergen.
     *
     * @since 1.15.0
     * @param array $allergen Allergen definition.
     * @return string
     */
    public static function get_icon_class( $allergen ) {
        return 'cooked-icon-' . $allergen['icon'];
    }

    /**
     * Render allergen badges from recipe array (used in hooks).
     *
     * @since 1.15.0
     * @param array $recipe Recipe data array with 'id' key.
     */
    public static function render_from_recipe( $recipe ) {
        if ( ! self::show_on_recipe_cards() ) {
            return;
        }

        if ( empty( $recipe['id'] ) ) {
            return;
        }

        $recipe_settings = Cooked_Recipes::get_settings( $recipe['id'] );
        echo wp_kses_post( self::render( $recipe_settings ) );
    }

    /**
     * Render allergen badges HTML.
     *
     * @since 1.15.0
     * @param array $recipe_settings Recipe settings array.
     * @return string HTML output.
     */
    public static function render( $recipe_settings ) {
        if ( empty( $recipe_settings['allergens'] ) || ! is_array( $recipe_settings['allergens'] ) ) {
            return '';
        }

        $allergens = self::get_allergens();
        $selected = $recipe_settings['allergens'];
        $output = '<span class="cooked-allergens">';

        foreach ( $selected as $key ) {
            if ( ! isset( $allergens[ $key ] ) ) {
                continue;
            }

            $allergen = $allergens[ $key ];
            $output .= sprintf(
                '<span class="cooked-allergen cooked-allergen-%1$s" title="%2$s"><i class="cooked-icon %3$s" aria-hidden="true"></i></span>',
                esc_attr( $key ),
                /* translators: %s: allergen name */
                esc_attr( sprintf( __( 'Contains %s', 'cooked' ), $allergen['label'] ) ),
                esc_attr( self::get_icon_class( $allergen ) )
            );
        }

        $output .= '</span>';

        return $output;
    }

    /**
     * Register `allergens` in the [cooked-info] default info array.
     *
     * @since 1.15.0
     * @param array $info Default info array.
     * @return array
     */
    public static function register_info_field( $info ) {
        $info['allergens'] = __( 'Allergens', 'cooked' );
        return $info;
    }

    /**
     * Register the handler that renders the `allergens` info field.
     *
     * @since 1.15.0
     * @param array $methods Method map keyed by function name.
     * @return array
     */
    public static function register_info_method( $methods ) {
        $methods['cooked_info_allergens'] = __CLASS__;
        return $methods;
    }

    /**
     * Add `allergens` to the documented variables in the Shortcodes tab.
     *
     * @since 1.15.0
     * @param array $vars Documented variables.
     * @return array
     */
    public static function register_info_var( $vars ) {
        $vars['allergens'] = __( 'Allergens', 'cooked' );
        return $vars;
    }

    /**
     * [cooked-info] handler for the `allergens` field.
     *
     * Outputs nothing when no allergens are selected on the recipe.
     *
     * @since 1.15.0
     * @param array $recipe_settings Recipe settings array.
     */
    public static function cooked_info_allergens( $recipe_settings ) {
        $html = self::render( $recipe_settings );
        if ( ! $html ) {
            return;
        }

        echo '<span class="cooked-allergens-info">';
        echo '<strong class="cooked-meta-title">' . esc_html__( 'Allergens', 'cooked' ) . '</strong>';
        echo wp_kses_post( $html );
        echo '</span>';
    }

}

add_action( 'init', [ 'Cooked_Allergens', 'init' ] );
