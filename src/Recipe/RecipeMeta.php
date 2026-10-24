<?php
/**
 * Post Types
 *
 * @package     Cooked
 * @subpackage  Post Types
 * @since       1.0.0
 */

namespace Cooked\Recipe;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecipeMeta Class
 *
 * This class handles the Cooked Recipe Meta Box creation.
 *
 * @since 1.0.0
 */
class RecipeMeta {
    /**
     * Registers the recipe editor hooks.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ &$this, 'add_recipe_meta_box' ] );
        add_action( 'save_post', [ &$this, 'save_recipe_meta_box' ] );
        add_action( 'admin_notices', [ &$this, 'recipe_embed_shortcode_admin_notice' ] );
    }

    /**
     * The post types that get the Cooked Settings box (filterable; recipes by default).
     *
     * @since 2.0.0
     * @return string[]
     */
    public static function post_types() {
        return (array) apply_filters(
            'cooked_recipe_metabox_post_types',
            apply_filters( 'cp_recipe_metabox_post_types', [ 'cp_recipe' ] )
        );
    }

    /**
     * The top-level keys a saved recipe keeps; meta_cleanup() drops any other submitted key.
     * Add-ons that store their own fields in _recipe_settings add them through the filter.
     *
     * @since 2.0.0
     * @return string[]
     */
    public static function allowed_keys() {
        return (array) apply_filters(
            'cooked_recipe_settings_keys',
            [
                'content',
                'excerpt',
                'notes',
                'seo_description',
                'post_title',
                'difficulty_level',
                'prep_time',
                'cook_time',
                'total_time',
                'ingredients',
                'directions',
                'nutrition',
                'gallery',
                'allergens',
                'cooked_version',
                // Cooked Pro's front-end submissions.
                'guest_name',
                'guest_email',
            ]
        );
    }

    /**
     * Plain text from a value that may hold HTML, however many times its entities are encoded.
     * Decodes and strips until nothing changes, so the decode in Cooked_Recipes::format_content()
     * cannot turn what is left back into a tag (e.g. "&amp;lt;a&amp;gt;").
     *
     * @since 2.0.0
     * @param string $value Submitted value.
     * @return string
     */
    public static function strip_all_html( $value ) {
        $value = (string) $value;
        for ( $i = 0; $i < 10; $i++ ) {
            $stripped = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            if ( $stripped === $value ) {
                break;
            }
            $value = $stripped;
        }
        return $value;
    }

    /**
     * Sanitizes the recipe settings submitted from the editor.
     *
     * @param array<string, mixed> $recipe_settings Submitted recipe settings.
     * @return array<string, mixed>
     */
    public static function meta_cleanup( $recipe_settings ) {
        global $_cooked_settings;
        $_recipe_settings = [];

        $wp_editor_roles_allowed = false;
        if ( is_user_logged_in() ) {
            $user                    = wp_get_current_user();
            $user_roles              = $user->roles;
            $wp_editor_roles_allowed = isset( $_cooked_settings['recipe_wp_editor_roles'] ) && ! empty( array_intersect( $user_roles, $_cooked_settings['recipe_wp_editor_roles'] ) ) ? true : false;
        }

        $allowed_keys = self::allowed_keys();

        if ( ! empty( $recipe_settings ) ) {
            foreach ( $recipe_settings as $key => $val ) {
                if ( ! in_array( $key, $allowed_keys, true ) ) {
                    continue;
                }
                if ( ! is_array( $val ) ) {
                    if ( 'content' === $key || 'excerpt' === $key || 'notes' === $key ) {
                        if ( $wp_editor_roles_allowed ) {
                            // Decode HTML entities first so wp_kses_post can see actual HTML tags.
                            $decoded_val              = wp_specialchars_decode( $val, ENT_QUOTES );
                            $_recipe_settings[ $key ] = wp_kses_post( $decoded_val );
                        } else {
                            $_recipe_settings[ $key ] = self::strip_all_html( $val );
                        }
                    } elseif ( 'seo_description' === $key ) {
                        $decoded_val              = wp_specialchars_decode( $val, ENT_QUOTES );
                        $_recipe_settings[ $key ] = wp_strip_all_tags( $decoded_val );
                    } elseif ( in_array( $key, [ 'prep_time', 'cook_time', 'total_time' ], true ) ) {
                        $minutes                  = \Cooked_Measurements::normalize_minutes( $val );
                        $_recipe_settings[ $key ] = $minutes ? $minutes : '';
                    } elseif ( 'post_title' === $key ) {
                            // Decode HTML entities first so wp_kses_post can see actual HTML tags.
                            $decoded_val              = wp_specialchars_decode( $val, ENT_QUOTES );
                            $_recipe_settings[ $key ] = wp_kses_post( $decoded_val );
					} else {
						$_recipe_settings[ $key ] = \Cooked_Functions::sanitize_text_field( $val );
                    }
                } elseif ( ! empty( $val ) ) {
                    $clean = self::clean_values( $val, $key, 1, $wp_editor_roles_allowed );
                    if ( ! empty( $clean ) ) {
                        $_recipe_settings[ $key ] = $clean;
                    }
                }
            }
        }

        return $_recipe_settings;
    }

    /**
     * Sanitizes a nested recipe setting (ingredients, directions, nutrition, gallery...), up to
     * four levels deep. Keys go through sanitize_key(). A step's or ingredient's text keeps HTML
     * for the editor roles (wp_kses_post) and is stripped to plain text for everyone else; every
     * other value is a text field. Empty arrays are left out.
     *
     * @since 2.0.0
     * @param array<mixed> $values  Submitted values.
     * @param string       $setting Top-level setting key, e.g. "directions".
     * @param int          $depth   1 for the setting's own items.
     * @param bool         $html    Whether the user may store HTML.
     * @return array<string, mixed>
     */
    private static function clean_values( $values, $setting, $depth, $html ) {
        $clean = [];

        foreach ( $values as $key => $value ) {
            $key = sanitize_key( (string) $key );

            if ( is_array( $value ) ) {
                if ( $depth < 4 && ! empty( $value ) ) {
                    $nested = self::clean_values( $value, $setting, $depth + 1, $html );
                    if ( ! empty( $nested ) ) {
                        $clean[ $key ] = $nested;
                    }
                }
                continue;
            }

            if ( 2 === $depth && self::is_rich_text( $setting, $key ) ) {
                // Decode entities first so wp_kses_post sees the actual tags.
                $clean[ $key ] = $html ? wp_kses_post( wp_specialchars_decode( (string) $value, ENT_QUOTES ) ) : self::strip_all_html( (string) $value );
            } else {
                $clean[ $key ] = \Cooked_Functions::sanitize_text_field( $value );
            }
        }

        return $clean;
    }

    /**
     * Whether a field of an ingredient or direction (or any item's "content") is rich text.
     *
     * @since 2.0.0
     * @param string $setting Top-level setting key.
     * @param string $field   Field key inside one item.
     * @return bool
     */
    private static function is_rich_text( $setting, $field ) {
        if ( 'content' === $field ) {
            return true;
        }
        if ( 'ingredients' === $setting ) {
            return in_array( $field, [ 'name', 'section_heading_name', 'section_heading_element' ], true );
        }
        if ( 'directions' === $setting ) {
            return in_array( $field, [ 'section_heading_name', 'section_heading_element' ], true );
        }
        return false;
    }

    /**
     * Adds the meta box container.
     *
     * @param string $post_type Current post type.
     * @return void
     */
    public function add_recipe_meta_box( $post_type ) {
        // Limit meta box to Cooked Recipes.
        if ( in_array( $post_type, self::post_types(), true ) ) {
            add_meta_box( 'cooked_recipe_settings', __( 'Cooked Settings', 'cooked' ), [ &$this, 'render_recipe_meta_box' ], $post_type, 'normal', 'high' );
            add_filter( 'get_user_option_meta-box-order_' . $post_type, [ __CLASS__, 'keep_meta_box_in_main_column' ] );
        }
    }

    /**
     * Moves the Cooked Settings box out of a saved sidebar position: it is too wide for the
     * sidebar. The editor script also stops it being dragged or moved there.
     *
     * @since 2.0.0
     * @param array<string, string>|false $order Saved box order, context => comma-separated IDs.
     * @return array<string, string>|false
     */
    public static function keep_meta_box_in_main_column( $order ) {
        if ( ! is_array( $order ) || empty( $order['side'] ) ) {
            return $order;
        }

        $side = explode( ',', $order['side'] );
        if ( ! in_array( 'cooked_recipe_settings', $side, true ) ) {
            return $order;
        }

        $order['side']   = implode( ',', array_diff( $side, [ 'cooked_recipe_settings' ] ) );
        $normal          = isset( $order['normal'] ) && '' !== $order['normal'] ? explode( ',', $order['normal'] ) : [];
        $order['normal'] = implode( ',', array_merge( [ 'cooked_recipe_settings' ], $normal ) );

        return $order;
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_recipe_meta_box( $post_id ) {
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['cooked_recipe_custom_box_nonce'] ) ) {
			return $post_id;
        }

        $nonce = wp_unslash( $_POST['cooked_recipe_custom_box_nonce'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verify nonce after unslash; do not sanitize_text_field a nonce.
        if ( ! wp_verify_nonce( $nonce, 'cooked_recipe_custom_box' ) ) {
			return $post_id;
        }

        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
        }

        // Only the post types with the box, and only a post this user can edit (edit_cooked_recipes
        // is a general capability, so it says nothing about this post).
        if ( ! in_array( get_post_type( $post_id ), self::post_types(), true ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
        }

        global $recipe_settings;

        // OK, it's safe for us to validate/sanitize the data now.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in meta_cleanup().
        $recipe_settings = isset( $_POST['_recipe_settings'] ) ? self::meta_cleanup( wp_unslash( $_POST['_recipe_settings'] ) ) : [];

        if ( isset( $recipe_settings['content'] ) ) {
            $recipe_settings['content'] = str_replace( [ "\r\n", "\r" ], "\n", $recipe_settings['content'] );
        }
        if ( isset( $recipe_settings['excerpt'] ) ) {
            $recipe_settings['excerpt'] = str_replace( [ "\r\n", "\r" ], "\n", $recipe_settings['excerpt'] );
        }
        if ( isset( $recipe_settings['notes'] ) ) {
            $recipe_settings['notes'] = str_replace( [ "\r\n", "\r" ], "\n", $recipe_settings['notes'] );
        }
        if ( isset( $recipe_settings['seo_description'] ) ) {
            $recipe_settings['seo_description'] = str_replace( [ "\r\n", "\r" ], "\n", $recipe_settings['seo_description'] );
        }

        // Ingredients: each one's substitutes as a list, the first also in the pre-2.0 fields.
        if ( isset( $recipe_settings['ingredients'] ) && is_array( $recipe_settings['ingredients'] ) ) {
            foreach ( $recipe_settings['ingredients'] as $key => $ingredient ) {
                if ( is_array( $ingredient ) && ! isset( $ingredient['section_heading_name'] ) ) {
                    $recipe_settings['ingredients'][ $key ] = \Cooked_Recipes::with_substitutions( $ingredient, \Cooked_Recipes::ingredient_substitutions( $ingredient ) );
                }
            }
        }

        // Directions.
        if ( isset( $recipe_settings['directions'] ) ) {
            foreach ( $recipe_settings['directions'] as $key => $direction ) {
                if ( isset( $direction['content'] ) ) {
                    $recipe_settings['directions'][ $key ]['content'] = str_replace( [ "\r\n", "\r" ], "\n", $direction['content'] );
                }
            }
        }

        // Update the recipe settings meta field.
        update_post_meta( $post_id, '_recipe_settings', $recipe_settings );
        $recipe_excerpt = isset( $recipe_settings['excerpt'] ) && $recipe_settings['excerpt'] ? $recipe_settings['excerpt'] : get_the_title( $post_id );

        $seo_content = apply_filters( 'cooked_seo_recipe_content', '<h2>' . wp_kses_post( $recipe_excerpt ) . '</h2><h3>' . __( 'Ingredients', 'cooked' ) . '</h3>[cooked-ingredients checkboxes=false]<h3>' . __( 'Directions', 'cooked' ) . '</h3>[cooked-directions numbers=false]' );
        $seo_content = do_shortcode( $seo_content );

        // Unhook this function so it doesn't loop infinitely.
        remove_action( 'save_post', [ &$this, 'save_recipe_meta_box' ] );

        // Update the post, which calls save_post again. Integrations such as Elementor can
        // opt out through the cooked_should_update_post_content filter.
        $should_update_content = apply_filters( 'cooked_should_update_post_content', true, $post_id );
        if ( $should_update_content ) {
            wp_update_post(
                [
					'ID'           => $post_id,
					'post_excerpt' => $recipe_excerpt,
					'post_content' => $seo_content,
				]
            );
        } else {
            wp_update_post(
                [
					'ID'           => $post_id,
					'post_excerpt' => $recipe_excerpt,
				]
            );
        }

        // Re-hook this function.
        add_action( 'save_post', [ &$this, 'save_recipe_meta_box' ] );
    }

    /**
     * Warns in the editor when a recipe embeds itself.
     *
     * @return void
     */
    public function recipe_embed_shortcode_admin_notice() {
        $screen = get_current_screen();
        if ( ! $screen || 'cp_recipe' !== $screen->post_type || 'post' !== $screen->base ) {
            return;
        }

        global $post;
        if ( ! $post || ! isset( $post->ID ) ) {
            return;
        }

        $recipe_settings = get_post_meta( $post->ID, '_recipe_settings', true );
        if ( ! \Cooked_Recipes::settings_have_self_recipe_embed_shortcode( $recipe_settings, $post->ID ) ) {
            return;
        }

        $shortcode = '[cooked-recipe id="' . intval( $post->ID ) . '"]';

        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            wp_kses_post(
                sprintf(
                    /* translators: %s: recipe embed shortcode */
                    __( 'This recipe is set up to include itself in the Recipe Template (containing shortcode %s), which can break the page. Remove the embed that references this same recipe.', 'cooked' ),
                    '<code>' . esc_html( $shortcode ) . '</code>'
                )
            )
        );
    }

    /**
     * Render Meta Box content.
     *
     * @return void
     */
    public function render_recipe_meta_box() {
        global $post;

        /*
         * Output the recipe meta fields
         * @since 1.0.0
         */
        do_action( 'cooked_recipe_fields', $post->ID );

        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'cooked_recipe_custom_box', 'cooked_recipe_custom_box_nonce' );
    }

    /**
     * Prints the Bulk Add modal for ingredients and directions.
     *
     * @return void
     */
    public static function bulk_add_modal() {
        // Frontend submit form uses cooked-button + colors.php; wp-admin uses core .button styles.
        if ( is_admin() ) {
            $bulk_cancel_class = 'button cooked-bulk-add-cancel';
            $bulk_submit_class = 'button button-primary cooked-bulk-add-submit';
        } else {
            $bulk_cancel_class = 'cooked-button cooked-secondary-button cooked-bulk-add-cancel';
            $bulk_submit_class = 'cooked-button cooked-bulk-add-submit';
        }
        ?>
        <div id="cooked-bulk-add-overlay" class="cooked-bulk-add-overlay" style="display:none;">
            <div class="cooked-bulk-add-modal" role="dialog" aria-modal="true" aria-labelledby="cooked-bulk-add-title">
                <div class="cooked-bulk-add-header">
                    <h2 id="cooked-bulk-add-title"></h2>
                    <button type="button" class="cooked-bulk-add-close" aria-label="<?php esc_attr_e( 'Close', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
                </div>
                <div class="cooked-bulk-add-body">
                    <p class="cooked-bulk-add-help"><?php esc_html_e( 'Enter one item per line. Use the checkboxes below to mark section headings.', 'cooked' ); ?></p>
                    <textarea id="cooked-bulk-add-textarea" rows="8" placeholder=""></textarea>
                    <div id="cooked-bulk-add-preview" class="cooked-bulk-add-preview" style="display:none;" data-bulk-type="">
                        <p class="cooked-bulk-add-preview-label"><strong><?php esc_html_e( 'Preview', 'cooked' ); ?></strong></p>
                        <div id="cooked-bulk-add-preview-list" class="cooked-bulk-add-preview-list"></div>
                        <p class="cooked-bulk-add-preview-notice cooked-bulk-add-preview-notice-ingredients" role="note">
                            <span class="cooked-bulk-add-preview-notice-mark" aria-hidden="true">*</span>
                            <span class="cooked-bulk-add-preview-notice-text"><?php echo esc_html__( 'Please review the parsed fields before adding. Automatic parsing may misread amounts—for example, a range written as 2-3 could appear as 23 unless you correct it.', 'cooked' ); ?></span>
                        </p>
                    </div>
                </div>
                <div class="cooked-bulk-add-footer">
                    <button type="button" class="<?php echo esc_attr( $bulk_cancel_class ); ?>"><?php esc_html_e( 'Cancel', 'cooked' ); ?></button>
                    <button type="button" class="<?php echo esc_attr( $bulk_submit_class ); ?>" disabled></button>
                    <span class="cooked-bulk-add-spinner spinner" style="display:none;"></span>
                </div>
                <input type="hidden" id="cooked-bulk-add-type" value="">
            </div>
        </div>
        <?php
    }
}
