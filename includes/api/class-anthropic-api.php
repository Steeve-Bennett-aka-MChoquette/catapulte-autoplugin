<?php

declare(strict_types=1);

/**
 * Anthropic API class.
 *
 * @package WP-Autoplugin
 * @since 1.0.0
 * @version 2.0.0
 * @link https://wp-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WP_Autoplugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anthropic API class.
 */
class Anthropic_API extends API {

	/**
	 * API endpoint.
	 */
	private const API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Default timeout in seconds.
	 */
	private const DEFAULT_TIMEOUT = 300;

	/**
	 * Selected model.
	 */
	private string $model = '';

	/**
	 * Temperature parameter.
	 */
	private float $temperature = 0.2;

	/**
	 * Max tokens parameter.
	 */
	private int $max_tokens = 4096;

	/**
	 * Model parameters configuration.
	 *
	 * @return array<string, array{temperature: float, max_tokens: int}>
	 */
	private function get_model_params(): array {
		return [
			'claude-sonnet-4-5-20250929' => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-opus-4-20250514'     => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-sonnet-4-20250514'   => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-7-sonnet-20250219' => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-7-sonnet-latest'   => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-7-sonnet-thinking' => [ 'temperature' => 1.0, 'max_tokens' => 128000 ],
			'claude-3-5-sonnet-20240620' => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-5-sonnet-latest'   => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-5-haiku-latest'    => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-5-haiku-20241022'  => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'claude-3-opus-20240229'     => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'claude-3-sonnet-20240229'   => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'claude-3-haiku-20240307'    => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
		];
	}

	/**
	 * Set the model, and the parameters based on the model.
	 */
	public function set_model( string $model ): void {
		$this->model = sanitize_text_field( $model );

		$model_params = $this->get_model_params();
		if ( isset( $model_params[ $model ] ) ) {
			$this->temperature = $model_params[ $model ]['temperature'];
			$this->max_tokens  = $model_params[ $model ]['max_tokens'];
		}
	}

	/**
	 * Send a prompt to the API.
	 */
	public function send_prompt(
		string $prompt,
		string $system_message = '',
		array $override_body = []
	): string|\WP_Error {
		$payload = $this->build_payload( $prompt, $system_message, $override_body );
		$headers = $this->build_headers();

		// Handle thinking mode for claude-3-7-sonnet-thinking.
		if ( $this->model === 'claude-3-7-sonnet-thinking' ) {
			$payload['thinking'] = [
				'type'          => 'enabled',
				'budget_tokens' => 4096,
			];
		}

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => self::DEFAULT_TIMEOUT,
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
			]
		);

		// SECURITY FIX: Return WP_Error properly, not the error message string.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Invalid response from Anthropic API.', 'wp-autoplugin' )
			);
		}

		// Handle "Continue" functionality for truncated responses.
		if ( isset( $data['stop_reason'] ) && $data['stop_reason'] === 'max_tokens' ) {
			$data = $this->continue_response( $data, $headers );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'anthropic' );

		return $this->extract_content( $data );
	}

	/**
	 * Build the request payload.
	 *
	 * @param array<string, mixed> $override_body
	 * @return array<string, mixed>
	 */
	private function build_payload( string $prompt, string $system_message, array $override_body ): array {
		$payload = [
			'model'       => $this->model,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
			'messages'    => [
				[
					'role'    => 'user',
					'content' => [
						[
							'type' => 'text',
							'text' => $prompt,
						],
					],
				],
			],
		];

		if ( $system_message !== '' ) {
			$payload['system'] = $system_message;
		}

		if ( isset( $override_body['messages'] ) ) {
			$payload['messages'] = $override_body['messages'];
			unset( $override_body['messages'] );
		}

		if ( isset( $override_body['system'] ) ) {
			$payload['system'] = $override_body['system'];
			unset( $override_body['system'] );
		}

		// Keep only allowed keys alongside overrides.
		$allowed_keys  = [ 'model', 'temperature', 'max_tokens', 'messages', 'system', 'thinking' ];
		$override_body = array_intersect_key( $override_body, array_flip( $allowed_keys ) );

		return array_merge( $payload, $override_body );
	}

	/**
	 * Build the request headers.
	 *
	 * @return array<string, string>
	 */
	private function build_headers(): array {
		$headers = [
			'x-api-key'         => $this->api_key,
			'anthropic-version' => '2023-06-01',
			'content-type'      => 'application/json',
		];

		// Add beta headers for specific models.
		if ( $this->model === 'claude-3-5-sonnet-20240620' ) {
			$headers['anthropic-beta'] = 'max-tokens-3-5-sonnet-2024-07-15';
		} elseif ( $this->model === 'claude-3-7-sonnet-thinking' ) {
			$headers['anthropic-beta'] = 'output-128k-2025-02-19';
		}

		return $headers;
	}

	/**
	 * Continue a truncated response.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string> $headers
	 * @return array<string, mixed>|\WP_Error
	 */
	private function continue_response( array $data, array $headers ): array|\WP_Error {
		$messages = [
			[
				'role'    => 'assistant',
				'content' => $data['content'][0]['text'],
			],
			[
				'role'    => 'user',
				'content' => 'Continue exactly from where you left off.',
			],
		];

		$body = [
			'model'       => $this->model,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
			'messages'    => $messages,
		];

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => self::DEFAULT_TIMEOUT,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$new_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $new_data['content'][0]['text'] ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Error communicating with the Anthropic API.', 'wp-autoplugin' )
			);
		}

		// Merge the new response with the old one.
		$data['content'][0]['text'] .= $new_data['content'][0]['text'];

		return $data;
	}

	/**
	 * Extract content from the API response.
	 *
	 * @param array<string, mixed> $data
	 */
	private function extract_content( array $data ): string|\WP_Error {
		if ( isset( $data['content'][0]['text'] ) ) {
			return $data['content'][0]['text'];
		}

		return new \WP_Error(
			'api_error',
			__( 'Error communicating with the Anthropic API.', 'wp-autoplugin' )
		);
	}
}
