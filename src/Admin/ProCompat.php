<?php
/**
 * Cooked Pro version check
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

namespace Cooked\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ProCompat Class
 *
 * Keeps a Cooked Pro older than COOKED_REQUIRES_PRO from booting against this version of Cooked,
 * where it could fatal, and asks for it to be updated. Pro stays active, so updating it is all it
 * takes to bring it back.
 *
 * @since 2.0.0
 */
class ProCompat {

    /**
     * Registers the check.
     */
    public function __construct() {
        // Pro hooks its setup to plugins_loaded (priority 10). Priority 0 is early enough whichever
        // plugin WordPress loads first.
        add_action( 'plugins_loaded', [ $this, 'check' ], 0 );
    }

    /**
     * Whether an active Cooked Pro is too old for this version of Cooked.
     *
     * @return bool
     */
    public static function is_outdated() {
        $version = self::pro_version();
        return '' !== $version && version_compare( $version, COOKED_REQUIRES_PRO, '<' );
    }

    /**
     * The active Cooked Pro's version, or '' when Pro is not active.
     *
     * @return string
     */
    private static function pro_version() {
        return defined( 'COOKED_PRO_VERSION' ) ? (string) COOKED_PRO_VERSION : '';
    }

    /**
     * Unhooks an outdated Pro's setup (its modules, enqueues and SEO integrations) and shows the notice.
     *
     * @return void
     */
    public function check() {
        if ( ! self::is_outdated() || ! class_exists( 'Cooked_Pro_Plugin' ) ) {
            return;
        }

        $pro = \Cooked_Pro_Plugin::instance();
        remove_action( 'plugins_loaded', [ $pro, 'init' ] );
        remove_action( 'init', [ $pro, 'initialize_plugin_support' ], 20 );

        add_action( 'admin_notices', [ $this, 'outdated_notice' ] );
    }

    /**
     * Admin notice asking for Cooked Pro to be updated.
     *
     * @return void
     */
    public function outdated_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $message = sprintf(
            /* translators: 1: Cooked Pro's installed version, 2: the Cooked Pro version this version of Cooked requires, 3: link to the Plugins screen */
            __( '<strong>Cooked Pro is paused.</strong> Version %1$s does not work with this version of Cooked, which requires Cooked Pro %2$s or newer. Your recipes and settings are safe; Pro\'s features come back as soon as it is updated. %3$s', 'cooked' ),
            esc_html( self::pro_version() ),
            esc_html( COOKED_REQUIRES_PRO ),
            '<a href="' . esc_url( admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '">' . esc_html__( 'Update Cooked Pro', 'cooked' ) . '</a>'
        );

        $allowed = [
            'strong' => [],
            'a'      => [ 'href' => [] ],
        ];
        printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses( $message, $allowed ) );
    }
}
