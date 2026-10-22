<?php
/**
 * Cooked Import admin page
 *
 * Rendered inside the admin frame: the frame supplies the header, page tabs and the
 * section menu (one section per import tab). The imports run over AJAX, so unlike the
 * classic layout (classic/import.php) there is no options.php form around the fields.
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       1.8.2
 */

$cooked_import             = new Cooked_Import();
$cooked_import_tabs_fields = $cooked_import->tabs_fields();

?>
<div id="cooked_import_settings" class="cooked-settings-form-wrapper cooked-import">
    <?php foreach ( $cooked_import_tabs_fields as $cooked_tab_slug => $cooked_tab ) : ?>
        <div id="cooked-settings-tab-content-<?php echo esc_attr( $cooked_tab_slug ); ?>" class="cooked-settings-tab-content">
            <?php
            foreach ( $cooked_tab['fields'] as $cooked_name => $cooked_field ) :
                $cooked_field_type = 'field_' . $cooked_field['type'];
                if ( ! method_exists( $cooked_import, $cooked_field_type ) ) {
                    continue;
                }

                $cooked_notitle       = 'misc_button' === $cooked_field['type'] || 'nonce' === $cooked_field['type'];
                $cooked_class         = isset( $cooked_field['class'] ) ? ' ' . $cooked_field['class'] : '';
                $cooked_field_options = isset( $cooked_field['options'] ) ? $cooked_field['options'] : ( isset( $cooked_field['placeholder'] ) ? $cooked_field['placeholder'] : false );
                $cooked_field_options = $cooked_notitle && isset( $cooked_field['title'] ) ? $cooked_field['title'] : $cooked_field_options;
                $cooked_color         = isset( $cooked_field['color'] ) && $cooked_field['color'] ? $cooked_field['color'] : false;
                $cooked_condition     = '';
                if ( ! empty( $cooked_field['conditional_requirement'] ) ) {
                    $cooked_condition = ' data-cooked-condition="' . esc_attr( implode( ' ', (array) $cooked_field['conditional_requirement'] ) ) . '"';
                }

                echo '<div' . wp_kses( $cooked_condition, [] ) . ' class="recipe-setting-block ' . esc_attr( $cooked_field['type'] . $cooked_class ) . '">';
                    echo ! $cooked_notitle ? '<h3 class="cooked-settings-title" id="' . esc_attr( Cooked_Settings::title_id( $cooked_name ) ) . '">' . wp_kses_post( $cooked_field['title'] ) . '</h3>' : '';
                    echo isset( $cooked_field['desc'] ) && $cooked_field['desc'] ? '<div class="cooked-settings-desc">' . wp_kses_post( $cooked_field['desc'] ) . '</div>' : '';
                    $cooked_import->$cooked_field_type( $cooked_name, $cooked_field_options, $cooked_color, $cooked_field );
                echo '</div>';
            endforeach;
            ?>
        </div>
    <?php endforeach; ?>
</div>
