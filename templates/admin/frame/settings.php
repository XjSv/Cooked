<?php
/**
 * Cooked Settings admin page, new layout: only the settings form. The admin app
 * (assets/js/cooked-admin-app.js) owns the page around it: header and Update Settings button,
 * page tabs, the section menu (which shows one .cooked-settings-tab-content at a time) and the
 * notices above the frame. PHP owns the fields, so options, field types and the
 * cooked_settings_tabs_fields filter work as in the classic layout (classic/settings.php).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

$cooked_settings             = new Cooked_Settings();
$cooked_settings_tabs_fields = $cooked_settings->tabs_fields();
?>
<form action="options.php" method="post" id="cooked-settings-panel" class="cooked-settings-form">

    <input type="hidden" name="cooked_settings_saved" value="true" />
    <?php settings_fields( 'cooked_settings_group' ); ?>

    <div id="cooked_recipe_settings" class="cooked-settings-form-wrapper">

        <?php
        // Settings messages stay in the card: "inline" keeps WordPress from moving them up to the
        // page's notices (see Menus::cooked_app_page()).
        ob_start();
        settings_errors( 'cooked_settings_group' );
        $cooked_settings_errors = (string) ob_get_clean();
        ?>
        <div class="cooked-settings-notices"><?php echo preg_replace( '/class=([\'"])notice /', 'class=$1notice inline ', $cooked_settings_errors ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by settings_errors(). ?></div>

        <div class="cooked-settings-tab-content-wrapper">
            <?php include COOKED_DIR . 'templates/admin/parts/settings-fields.php'; ?>
        </div>

    </div>

    <?php
    // The header's Update Settings button clicks this one; it also makes Enter submit the form.
    submit_button( __( 'Update Settings', 'cooked' ), 'primary', 'submit', false, [ 'hidden' => 'hidden' ] );
    ?>

</form>
