<?php

declare(strict_types=1);

/**
 * Catapulte-Autoplugin AJAX Generator class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.1
 */

namespace Catapulte_Autoplugin\Ajax;

use Catapulte_Autoplugin\Admin\Admin;
use Catapulte_Autoplugin\Plugin_Generator;
use Catapulte_Autoplugin\Plugin_Installer;
use Catapulte_Autoplugin\AI_Utils;
use Catapulte_Autoplugin\History;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles AJAX requests for generating plugins.
 */
class Generator {
	/**
	 * The Admin object for accessing specialized model APIs.
	 */
	private readonly Admin $admin;

	/**
	 * Constructor.
	 *
	 * @param Admin $admin The admin instance.
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * AJAX handler for generating a plugin plan.
	 *
	 * @return void
	 */
	public function generate_plan() {
		$plan = isset( $_POST['plugin_description'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
			? sanitize_text_field( wp_unslash( $_POST['plugin_description'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
			: '';

		$planner_api   = $this->admin->api_handler->get_planner_api();
		$generator     = new Plugin_Generator( $planner_api );
		$prompt_images = isset( $_POST['prompt_images'] ) ? AI_Utils::parse_prompt_images( $_POST['prompt_images'] ) : [];
		$plan_data     = $generator->generate_plugin_plan( $plan, $prompt_images );
		if ( is_wp_error( $plan_data ) ) {
			wp_send_json_error( $plan_data->get_error_message() );
		}

		// Strip out any code block fences like ```json ... ```.
		$plan_data  = \Catapulte_Autoplugin\AI_Utils::strip_code_fences( $plan_data, 'json' );
		$plan_array = json_decode( $plan_data, true );
		if ( ! $plan_array ) {
			wp_send_json_error( esc_html__( 'Failed to decode the generated plan: ', 'catapulte-autoplugin' ) . $plan_data );
		}

		// Get token usage from the actual API that was used.
		$token_usage = $planner_api->get_last_token_usage();

		wp_send_json_success(
			[
				'plan'        => $plan_array,
				'token_usage' => $token_usage,
			]
		);
	}

	/**
	 * AJAX handler for generating plugin code.
	 *
	 * @return void
	 */
	public function generate_code() {
		$description = isset( $_POST['plugin_plan'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
			? sanitize_text_field( wp_unslash( $_POST['plugin_plan'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
			: '';

		$coder_api = $this->admin->api_handler->get_coder_api();
		$generator = new Plugin_Generator( $coder_api );
		$code      = $generator->generate_plugin_code( $description );
		if ( is_wp_error( $code ) ) {
			wp_send_json_error( $code->get_error_message() );
		}

		// Strip out code fences like ```php ... ```.
		$code = \Catapulte_Autoplugin\AI_Utils::strip_code_fences( $code, 'php' );

		// Get token usage from the actual API that was used.
		$token_usage = $coder_api->get_last_token_usage();

		wp_send_json_success(
			[
				'code'        => $code,
				'token_usage' => $token_usage,
			]
		);
	}

	/**
	 * AJAX handler for generating a single file for complex plugins.
	 *
	 * @return void
	 */
	public function generate_file() {
		$file_index        = isset( $_POST['file_index'] ) ? intval( wp_unslash( $_POST['file_index'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
		$plugin_plan       = isset( $_POST['plugin_plan'] ) ? wp_unslash( $_POST['plugin_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.
		$project_structure = isset( $_POST['project_structure'] ) ? wp_unslash( $_POST['project_structure'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.
		$generated_files   = isset( $_POST['generated_files'] ) ? wp_unslash( $_POST['generated_files'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.

		// Decode JSON data.
		$plugin_plan_array       = json_decode( $plugin_plan, true );
		$project_structure_array = json_decode( $project_structure, true );
		$generated_files_array   = json_decode( $generated_files, true );

		if ( ! is_array( $plugin_plan_array ) || ! is_array( $project_structure_array ) || ! isset( $project_structure_array['files'] ) ) {
			wp_send_json_error( esc_html__( 'Invalid input data.', 'catapulte-autoplugin' ) );
		}

		// Ensure generated_files_array is an array (can be empty on first file).
		if ( ! is_array( $generated_files_array ) ) {
			$generated_files_array = [];
		}

		$files = $project_structure_array['files'];
		if ( ! isset( $files[ $file_index ] ) ) {
			wp_send_json_error( esc_html__( 'File index out of range.', 'catapulte-autoplugin' ) );
		}

		$file_info    = $files[ $file_index ];
		$coder_api    = $this->admin->api_handler->get_coder_api();
		$generator    = new Plugin_Generator( $coder_api );
		$file_content = $generator->generate_plugin_file( $file_info, (string) wp_json_encode( $plugin_plan_array ), $project_structure_array, $generated_files_array );

		if ( is_wp_error( $file_content ) ) {
			wp_send_json_error( $file_content->get_error_message() );
		}

		// Strip out code fences.
		$file_type    = $file_info['type'];
		$file_content = \Catapulte_Autoplugin\AI_Utils::strip_code_fences( $file_content );

		// Get token usage from the actual API that was used.
		$token_usage = $coder_api->get_last_token_usage();

		wp_send_json_success(
			[
				'file_path'    => $file_info['path'],
				'file_content' => $file_content,
				'file_type'    => $file_type,
				'token_usage'  => $token_usage,
			]
		);
	}

	/**
	 * AJAX handler for creating a plugin (i.e., writing the plugin files).
	 *
	 * @return void
	 */
	public function create_plugin() {
		$plugin_mode = get_option( 'catapulte_autoplugin_plugin_mode', 'simple' );

		if ( 'complex' === $plugin_mode ) {
			$this->create_complex_plugin();
		} else {
			$this->create_simple_plugin();
		}
	}

	/**
	 * AJAX handler for creating a simple single-file plugin.
	 *
	 * @return void
	 */
	private function create_simple_plugin() {
		$code               = isset( $_POST['plugin_code'] ) ? wp_unslash( $_POST['plugin_code'] ) : ''; // phpcs:ignore -- This cannot be sanitized, as it's the plugin code. Nonce verification is done in the parent method.
		$plugin_name        = isset( $_POST['plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
		$plugin_description = isset( $_POST['plugin_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['plugin_description'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
		$plugin_plan        = isset( $_POST['plugin_plan'] ) ? wp_unslash( $_POST['plugin_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON. Nonce verification is done in the parent method.

		$installer = Plugin_Installer::get_instance();
		$result    = $installer->install_plugin( $code, $plugin_name );
		if ( is_wp_error( $result ) ) {
			wp_send_json(
				[
					'success'    => false,
					'data'       => $result->get_error_message(),
					'error_type' => 'install_error',
				]
			);
		}

		// Save to history.
		$plan_array = json_decode( $plugin_plan, true );
		if ( $plugin_description && $plan_array ) {
			$history = History::get_instance();
			$history->add_entry(
				[
					'plugin_name'        => $plugin_name,
					'plugin_slug'        => sanitize_title( $plugin_name ),
					'plugin_description' => $plugin_description,
					'plugin_plan'        => $plan_array,
					'plugin_mode'        => 'simple',
				]
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for creating a complex multi-file plugin.
	 *
	 * @return void
	 */
	private function create_complex_plugin() {
		$plugin_name        = isset( $_POST['plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
		$plugin_description = isset( $_POST['plugin_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['plugin_description'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in the parent method.
		$plugin_plan        = isset( $_POST['plugin_plan'] ) ? wp_unslash( $_POST['plugin_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON. Nonce verification is done in the parent method.
		$project_structure  = isset( $_POST['project_structure'] ) ? wp_unslash( $_POST['project_structure'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.
		$generated_files    = isset( $_POST['generated_files'] ) ? wp_unslash( $_POST['generated_files'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.

		// Decode JSON data.
		$plan_array              = json_decode( $plugin_plan, true );
		$project_structure_array = json_decode( $project_structure, true );
		$generated_files_array   = json_decode( $generated_files, true );

		if ( ! $project_structure_array || ! $generated_files_array ) {
			wp_send_json_error( esc_html__( 'Invalid input data.', 'catapulte-autoplugin' ) );
		}

		$installer = Plugin_Installer::get_instance();
		$result    = $installer->install_complex_plugin( $plugin_name, $project_structure_array, $generated_files_array );
		if ( is_wp_error( $result ) ) {
			wp_send_json(
				[
					'success'    => false,
					'data'       => $result->get_error_message(),
					'error_type' => 'install_error',
				]
			);
		}

		// Save to history.
		if ( $plugin_description && $plan_array ) {
			$history = History::get_instance();
			$history->add_entry(
				[
					'plugin_name'        => $plugin_name,
					'plugin_slug'        => sanitize_title( $plugin_name ),
					'plugin_description' => $plugin_description,
					'plugin_plan'        => $plan_array,
					'plugin_mode'        => 'complex',
					'project_structure'  => $project_structure_array,
					'generated_files'    => $generated_files_array,
				]
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for reviewing generated code and suggesting improvements.
	 *
	 * @return void
	 */
	public function review_code() {
		$plugin_plan       = isset( $_POST['plugin_plan'] ) ? wp_unslash( $_POST['plugin_plan'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.
		$project_structure = isset( $_POST['project_structure'] ) ? wp_unslash( $_POST['project_structure'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.
		$generated_files   = isset( $_POST['generated_files'] ) ? wp_unslash( $_POST['generated_files'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cannot sanitize JSON data. Nonce verification is done in the parent method.

		// Decode JSON data.
		$project_structure_array = json_decode( $project_structure, true );
		$generated_files_array   = json_decode( $generated_files, true );

		if ( ! $project_structure_array || ! $generated_files_array ) {
			wp_send_json_error( esc_html__( 'Invalid input data.', 'catapulte-autoplugin' ) );
		}

		$reviewer_api  = $this->admin->api_handler->get_reviewer_api();
		$generator     = new Plugin_Generator( $reviewer_api );
		$review_result = $generator->review_generated_code( $plugin_plan, $project_structure_array, $generated_files_array );

		if ( is_wp_error( $review_result ) ) {
			wp_send_json_error( $review_result->get_error_message() );
		}

		// Strip out any code block fences like ```json ... ```.
		$review_result = \Catapulte_Autoplugin\AI_Utils::strip_code_fences( $review_result, 'json' );
		$review_result = json_decode( $review_result, true );
		if ( ! $review_result ) {
			wp_send_json_error( esc_html__( 'Failed to decode the review result.', 'catapulte-autoplugin' ) );
		}

		// Get token usage from the actual API that was used.
		$token_usage = $reviewer_api->get_last_token_usage();

		wp_send_json_success(
			[
				'review_data' => $review_result,
				'token_usage' => $token_usage,
			]
		);
	}
}
