<?php

declare(strict_types=1);

/**
 * Catapulte-Autoplugin Admin AJAX class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.0
 * @link https://catapulte-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Catapulte_Autoplugin\Admin;

use Catapulte_Autoplugin\Ajax\Explainer;
use Catapulte_Autoplugin\Ajax\Extender;
use Catapulte_Autoplugin\Ajax\Fixer;
use Catapulte_Autoplugin\Ajax\Generator;
use Catapulte_Autoplugin\Ajax\Hooks_Extender;
use Catapulte_Autoplugin\Ajax\Model;
use Catapulte_Autoplugin\Ajax\Theme_Extender;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles all AJAX requests.
 */
class Ajax {

	/**
	 * The Admin object for accessing specialized model APIs.
	 */
	private readonly Admin $admin;

	/**
	 * AJAX handlers.
	 *
	 * @var array<string, object>
	 */
	private array $handlers = [];

	/**
	 * Action mappings for registration.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const ACTION_MAPPINGS = [
		'generator'      => [ 'generate_plan', 'generate_code', 'generate_file', 'review_code', 'create_plugin' ],
		'fixer'          => [ 'generate_fix_plan', 'generate_fix_code', 'fix_plugin', 'generate_fix_file' ],
		'extender'       => [ 'generate_extend_plan', 'generate_extend_code', 'extend_plugin', 'generate_extend_file' ],
		'hooks_extender' => [ 'extract_hooks', 'generate_extend_hooks_plan', 'generate_extend_hooks_code', 'generate_extend_hooks_file' ],
		'theme_extender' => [ 'extract_theme_hooks', 'generate_extend_theme_plan', 'generate_extend_theme_code', 'generate_extend_theme_file' ],
		'explainer'      => [ 'explain_plugin' ],
		'model'          => [ 'add_model', 'remove_model', 'change_model', 'change_models' ],
	];

	/**
	 * Constructor sets the Admin instance and hooks into AJAX actions.
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;

		$this->init_handlers();
		$this->register_actions();
	}

	/**
	 * Initialize AJAX handlers.
	 */
	private function init_handlers(): void {
		$this->handlers = [
			'generator'      => new Generator( $this->admin ),
			'fixer'          => new Fixer( $this->admin ),
			'extender'       => new Extender( $this->admin ),
			'hooks_extender' => new Hooks_Extender( $this->admin ),
			'theme_extender' => new Theme_Extender( $this->admin ),
			'explainer'      => new Explainer( $this->admin ),
			'model'          => new Model( $this->admin ),
		];
	}

	/**
	 * Register all needed AJAX actions.
	 */
	private function register_actions(): void {
		foreach ( self::ACTION_MAPPINGS as $methods ) {
			foreach ( $methods as $method ) {
				add_action( 'wp_ajax_catapulte_autoplugin_' . $method, [ $this, 'ajax_actions' ] );
			}
		}
	}

	/**
	 * Catch-all AJAX entry point that routes to the relevant method.
	 */
	public function ajax_actions(): never {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You are not allowed to access this page.', 'catapulte-autoplugin' ) );
		}

		$action_input = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$method_name  = str_replace( 'catapulte_autoplugin_', '', $action_input );

		$target_handler = $this->find_handler_for_method( $method_name );

		if ( $target_handler === null ) {
			wp_send_json_error( esc_html__( 'Invalid AJAX action.', 'catapulte-autoplugin' ) );
		}

		if ( $this->should_verify_shared_nonce( $target_handler ) ) {
			if ( ! check_ajax_referer( 'catapulte_autoplugin_generate', 'security', false ) ) {
				wp_send_json_error( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
			}
		}

		$target_handler->$method_name();
		exit;
	}

	/**
	 * Find the handler that has the given method.
	 */
	private function find_handler_for_method( string $method_name ): ?object {
		foreach ( $this->handlers as $handler ) {
			if ( method_exists( $handler, $method_name ) ) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * Determine whether the shared generation nonce should be verified for a handler.
	 */
	private function should_verify_shared_nonce( object $handler ): bool {
		return ! ( $handler instanceof Model );
	}
}
