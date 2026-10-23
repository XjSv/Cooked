<?php
/**
 * Cooked Settings admin page
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       1.0.0
 */

?>
<div id="cooked-settings-prewrap"><div id="cooked-settings-wrap">

    <form action="options.php" method="post">

    <div class="cooked-settings-update-button">
        <?php submit_button( __( 'Update Settings', 'cooked' ), 'primary', 'submit', false ); ?>
    </div>

    <div class="cooked-settings-header">
        <i class="cooked-icon cooked-icon-cooked-icon"></i><?php esc_html_e( 'Cooked Settings', 'cooked' ); ?>
    </div>
    <div id="cooked-settings-panel" class="wrap">
    <?php

        global $_cooked_settings;
        $Cooked_Settings             = new Cooked_Settings();
        $cooked_settings_tabs_fields = $Cooked_Settings->tabs_fields();
        $parent_page_slug            = ( isset( $_cooked_settings['browse_page'] ) && $_cooked_settings['browse_page'] ? basename( get_permalink( $_cooked_settings['browse_page'] ) ) : false );

        $tab_counter = 1;
        echo '<ul id="cooked-settings-tabs" class="cooked-clearfix">';
    foreach ( $cooked_settings_tabs_fields as $tab_slug => $tab ) :
        echo '<li id="cooked-settings-tab-' . esc_attr( $tab_slug ) . '"' . ( 1 === $tab_counter ? ' class="active"' : '' ) . '><a href="#' . esc_attr( $tab_slug ) . '"><i class="cooked-icon cooked-icon-' . esc_attr( $tab['icon'] ) . '"></i>' . esc_attr( $tab['name'] ) . '</a></li>';
        ++$tab_counter;
        endforeach;
        echo '</ul>';

    ?>

        <div id="cooked_recipe_settings" class="cooked-settings-form-wrapper">

                <input type="hidden" name="cooked_settings_saved" value="true" />

                <?php settings_fields( 'cooked_settings_group' ); ?>

                <div class="cooked-settings-notices"><?php settings_errors( 'cooked_settings_group' ); ?></div>

                <div class="cooked-settings-tab-content-wrapper">

                    <?php

                    $tab_counter   = 1;
                    $stacked_types = [ 'html', 'permalink_field', 'migrate_button', 'misc_button', 'nonce' ];

                    foreach ( $cooked_settings_tabs_fields as $tab_slug => $tab ) :
                        echo '<div id="cooked-settings-tab-content-' . esc_attr( $tab_slug ) . '" class="cooked-settings-tab-content">';

                        // Add a hidden H2 tag for the messages, if any, to show up below it.
                        if ( 1 === $tab_counter ) :
                            echo '<h2 style="display:none;"></h2>';
                        endif;
                        foreach ( $tab['fields'] as $name => $field ) :

                            $notitle                 = false;
                            $conditional_requirement = '';
                            $has_desc                = isset( $field['desc'] ) && $field['desc'] && empty( $field['desc_in_field'] );

                            $class         = ( isset( $field['class'] ) ? ' ' . $field['class'] : '' );
                            $current_val   = ( isset( $_cooked_settings[ $name ] ) ? $_cooked_settings[ $name ] : null );
                            $field_type    = 'field_' . esc_attr( $field['type'] );
                            $field_hook    = 'cooked_settings_field_' . sanitize_key( $field['type'] );
                            $has_renderer  = method_exists( $Cooked_Settings, $field_type );
                            $field_options = ( isset( $field['options'] ) ? $field['options'] : false );
                            $field_options = ( ! $field_options ? ( isset( $field['placeholder'] ) ? $field['placeholder'] : $field_options ) : $field_options );
                            $color         = ( isset( $field['color'] ) && $field['color'] ? $field['color'] : false );

                            // Types without a field_*() renderer are drawn by whatever hooks their action.
                            if ( $has_renderer || has_action( $field_hook ) ) :

                                if ( 'misc_button' === $field['type'] || 'nonce' === $field['type'] ) :
                                    $notitle       = true;
                                    $field_options = ( isset( $field['title'] ) ? $field['title'] : false );
                                    endif;

                                if ( isset( $field['conditional_requirement'] ) && $field['conditional_requirement'] ) :
                                    if ( is_array( $field['conditional_requirement'] ) ) :
                                        $conditional_requirement = ' data-cooked-condition="' . esc_attr( implode( ' ', $field['conditional_requirement'] ) ) . '"';
                                        else :
                                            $conditional_requirement = ' data-cooked-condition="' . esc_attr( $field['conditional_requirement'] ) . '"';
                                        endif;
                                    endif;

                                // Long and grouped fields read better on one full-width row; short fields
                                // pair the heading with its control.
                                $is_stacked = $notitle
                                    || in_array( $field['type'], $stacked_types, true )
                                    || ( in_array( $field['type'], [ 'checkboxes', 'radio' ], true ) && is_array( $field_options ) && count( $field_options ) > 2 );

                                echo '<div' . wp_kses( $conditional_requirement, array() ) . ' class="recipe-setting-block cooked-setting-row ' . esc_attr( $field['type'] ) . ( $is_stacked ? ' is-stacked' : '' ) . esc_attr( $class ) . '">';
                                // The grid lives on an inner wrapper: conditional fields toggle the row's display.
                                echo '<div class="cooked-setting-row-inner">';
                                if ( ! $notitle || $has_desc ) :
                                    echo '<div class="cooked-setting-head">';
                                    echo ( ! $notitle ? '<h3 class="cooked-settings-title" id="' . esc_attr( Cooked_Settings::title_id( $name ) ) . '">' . wp_kses_post( $field['title'] ) . '</h3>' : '' );
                                    echo ( $has_desc ? '<div class="cooked-settings-desc">' . wp_kses_post( $field['desc'] ) . '</div>' : '' );
                                    echo '</div>';
                                endif;
                                echo '<div class="cooked-setting-control">';
                                if ( $has_renderer ) :
                                    $Cooked_Settings->$field_type( $name, $field_options, $color, $field );
                                    // Optional extra output under the control (e.g. Admin Screens' Compare button).
                                    if ( ! empty( $field['after_control'] ) && is_callable( $field['after_control'] ) ) :
                                        call_user_func( $field['after_control'], $name, $field );
                                    endif;
                                else :
                                    /**
                                     * Renders a settings field of a type Cooked has no renderer for.
                                     *
                                     * @since 2.0.0
                                     * @param string               $name        Setting key.
                                     * @param array<string, mixed> $field       Field definition.
                                     * @param mixed                $current_val Saved value, or null.
                                     */
                                    do_action( $field_hook, $name, $field, $current_val );
                                endif;
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';

                                endif;

                            endforeach;
                            ++$tab_counter;

                        echo '</div>';
                    endforeach;

                    // The classic layout has no Developer tab.
                    if ( COOKED_DEV && ! Cooked_Admin_Enqueues::use_classic_ui() ) :
                        echo '<div id="cooked-settings-tab-content-developer" class="cooked-settings-tab-content">';
                        Cooked_Functions::wpml_xml( $cooked_settings_tabs_fields );
                        echo '</div>';
                    endif;
                    ?>

                </div>

        </div>

    </div>
    </form>

</div></div>
