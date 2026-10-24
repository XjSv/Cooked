<?php
/**
 * Widgets
 *
 * @package     Cooked
 * @subpackage  Widgets
 * @since       1.8.8
 */

namespace Cooked\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PluginExtra Class
 *
 * This class adds links to the Plugins screen.
 *
 * @since 1.0.0
 */
class PluginExtra {
    /**
     * Registers the plugin list links.
     */
    public function __construct() {
        add_filter( 'plugin_action_links_cooked/cooked.php', [ &$this, 'cooked_plugin_action_links_action' ], 10, 1 );
    }

    /**
     * Plugin Action Links Filter
     *
     * Adds a "Upgrade to Pro" link to the plugin list page.
     *
     * @since 1.8.8
     * @param array<int|string, string> $links Plugin action links.
     * @return array<int|string, string>
     */
    public function cooked_plugin_action_links_action( $links ) {
        if ( ! class_exists( 'Cooked_Pro_Plugin' ) ) {
            return array_merge( [ '<a href="https://cooked.pro/get-cooked/" target="_blank">Upgrade to Pro</a>' ], $links );
        } else {
            array_unshift( $links, '<span style="color: #32373c">' . __( 'Required by Cooked Pro', 'cooked' ) . '</span>' );
            return $links;
        }
    }
}
