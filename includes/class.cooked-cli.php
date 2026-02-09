<?php
/**
 * WP-CLI commands for Cooked
 *
 * @package     Cooked
 * @subpackage  CLI
 * @since       1.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Run Cooked maintenance and migration tools.
 */
class Cooked_CLI_Tools_Command {

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
     * @param array $args       Positional args. 0 = tool name.
     * @param array $assoc_args Associative args.
     */
    public function run( $args, $assoc_args ) {
        if ( empty( $args[0] ) ) {
            \WP_CLI::error( __( 'Please provide a tool name. Use `wp cooked tools list` to see available tools.', 'cooked' ) );
        }

        $tool_name = $args[0];
        $result = Cooked_Updates::run_tool( $tool_name );

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
     * @param array $args       Positional args.
     * @param array $assoc_args Associative args.
     */
    public function list_tools( $args, $assoc_args ) {
        $tools = Cooked_Updates::get_runnable_tools();

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

\WP_CLI::add_command( 'cooked tools', 'Cooked_CLI_Tools_Command' );
