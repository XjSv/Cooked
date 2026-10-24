<?php
/**
 * Cooked Settings admin page, classic layout: the 1.x shell (header, tabs, Update Settings button)
 * around the shared fields.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       1.0.0
 */

$cooked_settings             = new Cooked_Settings();
$cooked_settings_tabs_fields = $cooked_settings->tabs_fields();
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
        $cooked_tab_counter = 1;
        echo '<ul id="cooked-settings-tabs" class="cooked-clearfix">';
        foreach ( $cooked_settings_tabs_fields as $cooked_tab_slug => $cooked_tab ) :
            echo '<li id="cooked-settings-tab-' . esc_attr( $cooked_tab_slug ) . '"' . ( 1 === $cooked_tab_counter ? ' class="active"' : '' ) . '><a href="#' . esc_attr( $cooked_tab_slug ) . '"><i class="cooked-icon cooked-icon-' . esc_attr( $cooked_tab['icon'] ) . '"></i>' . esc_html( $cooked_tab['name'] ) . '</a></li>';
            ++$cooked_tab_counter;
        endforeach;
        echo '</ul>';
        ?>

        <div id="cooked_recipe_settings" class="cooked-settings-form-wrapper">

            <input type="hidden" name="cooked_settings_saved" value="true" />

            <?php settings_fields( 'cooked_settings_group' ); ?>

            <div class="cooked-settings-notices"><?php settings_errors( 'cooked_settings_group' ); ?></div>

            <div class="cooked-settings-tab-content-wrapper">
                <?php
                // WordPress moves notices to just after the first heading in .wrap: this one.
                echo '<h2 style="display:none;"></h2>';
                include COOKED_DIR . 'templates/admin/parts/settings-fields.php';
                ?>
            </div>

        </div>

    </div>
    </form>

</div></div>
