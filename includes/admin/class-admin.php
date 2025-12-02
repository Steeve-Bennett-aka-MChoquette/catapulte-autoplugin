<?php

declare(strict_types=1);

/**
 * Catapulte-Autoplugin Admin class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.0
 * @link https://catapulte-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Catapulte_Autoplugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Admin class that brings all admin functionalities together.
 */
class Admin {

	/**
	 * The API handler instance.
	 */
	public readonly Api_Handler $api_handler;

	/**
	 * Constructor: set up API, instantiate sub-classes.
	 */
	public function __construct() {
		// Instantiate other admin components (each handles its own hooks).
		new Menu( $this );
		$this->api_handler = new Api_Handler();
		new Action_Links();
		new Updater();
		new Settings();
		new Scripts();
		new Ajax( $this );
		new Bulk_Actions();
		new Notices();
	}

	/**
	 * The built-in models.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_models(): array {
		return include CATAPULTE_AUTOPLUGIN_DIR . 'includes/config/models.php';
	}

	/**
	 * Output a simple admin footer for Catapulte-Autoplugin pages.
	 */
	public function output_admin_footer(): void {
		// Get the API handler to fetch the next task model.
		$next_task_model = $this->api_handler->get_next_task_model();
		?>
		<div id="catapulte-autoplugin-footer">
			<div class="footer-left">
				<span class="credits">
					<?php
					printf(
						// translators: %s: version number.
						esc_html__( 'Catapulte-Autoplugin v%s', 'catapulte-autoplugin' ),
						esc_html( CATAPULTE_AUTOPLUGIN_VERSION )
					);
					?>
				</span>
				<span class="separator">|</span>
				<span class="model">
					<span id="model-display">
						<?php
						$translated_model_string = wp_kses(
							// translators: %s: model name.
							__( 'Model: %s', 'catapulte-autoplugin' ),
							[ 'code' => [] ]
						);
						printf(
							$translated_model_string, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's escaped just above.
							'<code>' . esc_html( $next_task_model ) . '</code>'
						);
						?>
						<a href="#" id="change-model-link" style="text-decoration: none;"><?php esc_html_e( '(Change)', 'catapulte-autoplugin' ); ?></a>
					</span>
				</span>
			</div>
			<div class="footer-right">
				<span id="token-display" style="display: none; cursor: pointer;" title="<?php esc_attr_e( 'Click for token usage breakdown', 'catapulte-autoplugin' ); ?>">
					<span id="token-input">0</span> IN | <span id="token-output">0</span> OUT
				</span>
			</div>
		</div>

		<?php
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/footer-modal.php';
	}
}
