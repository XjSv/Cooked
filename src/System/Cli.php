<?php
/**
 * WP-CLI commands for Cooked
 *
 * @package     Cooked
 * @subpackage  CLI
 * @since       1.13.0
 */

namespace Cooked\System;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Run Cooked maintenance and migration tools.
 */
class Cli {

    /**
     * Run a Cooked migration/maintenance tool by name.
     *
     * ## OPTIONS
     *
     * <tool>
     * : The tool to run. Use `wp cooked tools list` to see available tools.
     *
     * ## EXAMPLES
     *
     *     wp cooked tools run remove_recipes_from_cooked_user_meta
     *     wp cooked tools run update_rewrite_rules
     *
     * @param string[] $args Positional args. 0 = tool name.
     * @return void
     */
    public function run( $args ) {
        if ( empty( $args[0] ) ) {
            \WP_CLI::error( __( 'Please provide a tool name. Use `wp cooked tools list` to see available tools.', 'cooked' ) );
        }

        $tool_name = $args[0];
        $result    = \Cooked_Updates::run_tool( $tool_name );

        if ( is_wp_error( $result ) ) {
            \WP_CLI::error( $result->get_error_message() );
        }

        \WP_CLI::success( __( 'Tool completed successfully.', 'cooked' ) );
    }

    /**
     * List available Cooked tools that can be run on demand.
     *
     * ## EXAMPLES
     *
     *     wp cooked tools list
     *
     * @return void
     */
    public function list_tools() {
        $tools = \Cooked_Updates::get_runnable_tools();

        if ( empty( $tools ) ) {
            \WP_CLI::log( __( 'No tools available.', 'cooked' ) );
            return;
        }

        $rows = [];
        foreach ( $tools as $tool ) {
            $rows[] = [
                'id'          => $tool['id'],
                'name'        => $tool['name'],
                'description' => $tool['description'],
            ];
        }

        \WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'name', 'description' ] );
    }
}
