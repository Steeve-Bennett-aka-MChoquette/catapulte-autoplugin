<?php

declare(strict_types=1);

/**
 * Plugin Name: Catapulte-Autoplugin
 * Description: A plugin that generates other plugins on-demand using AI.
 * Version: 2.0.1
 * Author: Martin Choquette Scott
 * Author URI: https://catapultcommunication.com
 * Text Domain: catapulte-autoplugin
 * Domain Path: /languages
 * Requires PHP: 8.1
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.1
 * @link https://catapultcommunication.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @wordpress-plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check PHP version.
if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
	add_action( 'admin_notices', static function (): void {
		$message = sprintf(
			/* translators: %s: PHP version */
			__( 'Catapulte-Autoplugin requires PHP 8.1 or higher. Your current version is %s.', 'catapulte-autoplugin' ),
			PHP_VERSION
		);
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	} );
	return;
}

// Define constants.
define( 'CATAPULTE_AUTOPLUGIN_VERSION', '2.0.1' );
define( 'CATAPULTE_AUTOPLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CATAPULTE_AUTOPLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the autoloader.
require_once CATAPULTE_AUTOPLUGIN_DIR . 'vendor/autoload.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function catapulte_autoplugin_init() {
	load_plugin_textdomain( 'catapulte-autoplugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	$admin_pages = new \Catapulte_Autoplugin\Admin\Admin();
}
add_action( 'plugins_loaded', 'catapulte_autoplugin_init' );
