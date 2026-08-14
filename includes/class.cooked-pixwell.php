<?php
/**
 * Cooked Pixwell Theme Support
 *
 * @package     Cooked
 * @subpackage  Pixwell Support
 * @since       1.16.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cooked_Pixwell Class
 *
 * Pixwell-specific dark mode compatibility (admin notice and settings tip).
 * Generic Auto dark mode CSS lives in Cooked_Settings.
 */
class Cooked_Pixwell {

    public function __construct() {
        add_action( 'admin_notices', [ $this, 'forced_dark_mode_notice' ] );
        add_filter( 'cooked_dark_mode_field_desc', [ $this, 'replace_settings_desc' ] );
    }

    /**
     * Whether Pixwell's dark mode switcher feature is enabled.
     *
     * @return bool
     */
    public static function is_pixwell_dark_mode_enabled() {
        return function_exists( 'pixwell_dark_mode' ) && (bool) pixwell_dark_mode();
    }

    /**
     * Suggest Auto when Pixwell's toggle is on and Cooked is not set to Auto.
     */
    public function forced_dark_mode_notice() {
        if ( ! self::is_pixwell_dark_mode_enabled() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $_cooked_settings = Cooked_Settings::get();
        $mode = Cooked_Settings::normalize_dark_mode(
            isset( $_cooked_settings['dark_mode'] ) ? $_cooked_settings['dark_mode'] : 'off'
        );

        if ( 'auto' === $mode ) {
            return;
        }

        $settings_url = trailingslashit( admin_url() ) . 'admin.php?page=cooked_settings#design';
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php esc_html_e( 'Pixwell\'s dark mode toggle is enabled on your site. To keep recipe styles in sync when visitors use it, go to', 'cooked' ); ?>
                <a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Cooked → Settings → Design', 'cooked' ); ?></a>
                <?php echo wp_kses( __( ' and set Dark Mode to <strong>Auto</strong>.', 'cooked' ), [ 'strong' => [] ] ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Replace the Dark Mode field description when Pixwell's toggle is active.
     *
     * @param string $desc Existing field description HTML.
     * @return string
     */
    public function replace_settings_desc( $desc ) {
        if ( ! self::is_pixwell_dark_mode_enabled() ) {
            return $desc;
        }

        return __( 'Pixwell\'s dark mode toggle is enabled. Choose <strong>Auto</strong> so Cooked follows it when visitors switch themes.', 'cooked' );
    }

}
