<?php

declare(strict_types=1);
/**
 * Catapulte-Autoplugin Updater class.
 *
 * @package Catapulte-Autoplugin
 */

namespace Catapulte_Autoplugin\Admin;

use Catapulte_Autoplugin\GitHub_Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles the GitHub updater.
 */
class Updater {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'github_updater_init' ] );
	}

	/**
	 * Initialize the GitHub updater.
	 *
	 * @return void
	 */
	public function github_updater_init() {
		if ( ! is_admin() ) {
			return;
		}

		$config = [
			'slug'               => plugin_basename( CATAPULTE_AUTOPLUGIN_DIR . 'catapulte-autoplugin.php' ),
			'proper_folder_name' => dirname( plugin_basename( CATAPULTE_AUTOPLUGIN_DIR . 'catapulte-autoplugin.php' ) ),
			'api_url'            => 'https://api.github.com/repos/Catapulte-Autoplugin/catapulte-autoplugin',
			'raw_url'            => 'https://raw.githubusercontent.com/Catapulte-Autoplugin/catapulte-autoplugin/main/',
			'github_url'         => 'https://github.com/Catapulte-Autoplugin/catapulte-autoplugin',
			'zip_url'            => 'https://github.com/Catapulte-Autoplugin/catapulte-autoplugin/archive/refs/heads/main.zip',
			'requires'           => '6.0',
			'tested'             => '6.6.2',
			'description'        => esc_html__( 'A plugin that generates other plugins on-demand using AI.', 'catapulte-autoplugin' ),
			'homepage'           => 'https://github.com/Catapulte-Autoplugin/catapulte-autoplugin',
			'version'            => CATAPULTE_AUTOPLUGIN_VERSION,
		];

		// Instantiate the updater class.
		new GitHub_Updater( $config );
	}
}
