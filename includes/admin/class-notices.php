<?php

declare(strict_types=1);
/**
 * Catapulte-Autoplugin Admin Notices class.
 *
 * @package Catapulte-Autoplugin
 */

namespace Catapulte_Autoplugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles showing various admin notices for Catapulte-Autoplugin.
 */
class Notices {

	/**
	 * Constructor hooks into 'admin_notices'.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'show_notices' ] );
	}

	/**
	 * Show any important admin notices on the Catapulte-Autoplugin pages.
	 *
	 * @return void
	 */
	public function show_notices() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_catapulte-autoplugin' ) {
			return;
		}

		// DISALLOW_FILE_MODS notice.
		if ( defined( 'DISALLOW_FILE_MODS' ) && \DISALLOW_FILE_MODS ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post(
						__(
							'The <code>DISALLOW_FILE_MODS</code> constant is defined in your wp-config.php file, which prevents Catapulte-Autoplugin from installing or updating plugins on your site.',
							'catapulte-autoplugin'
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		// Fatal error notice from plugin activation.
		$error = get_option( 'catapulte_autoplugin_fatal_error' );
		if ( $error && is_array( $error ) ) {
			$fix_url = add_query_arg(
				[
					'nonce'         => wp_create_nonce( 'catapulte-autoplugin-fix-plugin' ),
					'plugin'        => rawurlencode( $error['plugin'] ),
					'error_message' => rawurlencode( $error['error'] ),
				],
				admin_url( 'admin.php?page=catapulte-autoplugin-fix' )
			);
			?>
			<div class="notice notice-error">
				<p><?php echo esc_html__( 'The plugin could not be activated due to a fatal error.', 'catapulte-autoplugin' ); ?></p>
				<pre><?php echo esc_html( $error['error'] ); ?></pre>
				<p>
					<?php
					printf(
						/* translators: 1: fix link start tag, 2: fix link end tag */
						esc_html__( 'You can %1$sfix the error automatically%2$s.', 'catapulte-autoplugin' ),
						'<a href="' . esc_url( $fix_url ) . '">',
						'</a>'
					);
					?>
				</p>
			</div>
			<?php
			delete_option( 'catapulte_autoplugin_fatal_error' );
		}

		// Show any other notices stored in catapulte_autoplugin_notices.
		$notices = get_option( 'catapulte_autoplugin_notices', [] );
		foreach ( $notices as $notice ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
				<p><?php echo esc_html( $notice['message'] ); ?></p>
			</div>
			<?php
		}
		delete_option( 'catapulte_autoplugin_notices' );
	}

	/**
	 * Add a notice (error, warning, success, info) to be displayed.
	 *
	 * @param string $message The notice message.
	 * @param string $type    The type: error, warning, success, info.
	 *
	 * @return void
	 */
	public static function add_notice( $message, $type = 'error' ) {
		$notices   = get_option( 'catapulte_autoplugin_notices', [] );
		$notices[] = [
			'message' => $message,
			'type'    => $type,
		];
		update_option( 'catapulte_autoplugin_notices', $notices );
	}
}
