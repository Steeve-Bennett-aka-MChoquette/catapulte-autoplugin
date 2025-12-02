<?php

declare(strict_types=1);

/**
 * Plugin Name: WP-Autoplugin
 * Description: A plugin that generates other plugins on-demand using AI.
 * Version: 2.0.0
 * Author: Martin Choquette Scott
 * Author URI: https://catapultcommunication.com
 * Text Domain: wp-autoplugin
 * Domain Path: /languages
 * Requires PHP: 8.1
 *
 * @package WP-Autoplugin
 * @since 1.0.0
 * @version 2.0.0
 * @link https://wp-autoplugin.com
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
			__( 'WP-Autoplugin requires PHP 8.1 or higher. Your current version is %s.', 'wp-autoplugin' ),
			PHP_VERSION
		);
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	} );
	return;
}

// Define constants.
define( 'WP_AUTOPLUGIN_VERSION', '2.0.0' );
define( 'WP_AUTOPLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AUTOPLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the autoloader.
require_once WP_AUTOPLUGIN_DIR . 'vendor/autoload.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function wp_autoplugin_init() {
	load_plugin_textdomain( 'wp-autoplugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	$admin_pages = new \WP_Autoplugin\Admin\Admin();
}
add_action( 'plugins_loaded', 'wp_autoplugin_init' );
