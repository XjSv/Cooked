<?php
/**
 * Plugin Name:         Cooked - Recipe Management
 * Plugin URI:          https://wordpress.org/plugins/cooked
 * Description:         A recipe plugin for WordPress.
 * Author:              Gora Tech
 * Author URI:          https://goratech.dev
 * Version:             2.0.0
 * Text Domain:         cooked
 * Domain Path:         /languages
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least:   6.8
 * Tested up to:        7.1
 * Requires PHP:        8.2
 * Contributors:        xjsv, mgiannopoulos24, boxystudio
 * Tags:                recipe, recipes, food, cooking, nutrition
 *
 * Cooked is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Cooked is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Cooked. If not, see http://www.gnu.org/licenses/.
 *
 * @package Cooked
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/aliases.php';

if ( ! defined( 'COOKED_DEV' ) ) {
    define( 'COOKED_DEV', false );
}
define( 'COOKED_VERSION', '2.0.0' );

/**
 * The main function for that returns Cooked_Plugin.
 *
 * The main function responsible for returning the Cooked_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $cooked = Cooked(); ?>
 *
 * @since 1.0.0
 * @return object|Cooked_Plugin
 */
function Cooked() {
    return Cooked_Plugin::instance();
}

// Let's get cooking!
$CookedPlugin = Cooked();
