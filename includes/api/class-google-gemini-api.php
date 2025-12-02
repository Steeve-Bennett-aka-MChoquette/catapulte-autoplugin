<?php

declare(strict_types=1);

/**
 * Google Gemini API class.
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
 * Google Gemini API class.
 */
class Google_Gemini_API extends API {

	/**
	 * Base API URL.
	 */
	private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

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
	private int $max_tokens = 8192;

	/**
	 * Set the model.
	 */
	public function set_model( string $model ): void {
		$this->model = sanitize_text_field( $model );
	}

	/**
	 * Send a prompt to the API.
	 */
	public function send_prompt(
		string $prompt,
		string $system_message = '',
		array $override_body = []
	): string|\WP_Error {
		// SECURITY FIX: Use Authorization header instead of URL query parameter.
		$url = self::API_BASE_URL . $this->model . ':generateContent';

		$max_tokens = $this->get_max_tokens_for_model();

		$body = $this->build_request_body( $prompt, $max_tokens, $override_body );

		$response = wp_remote_post(
			$url,
			[
				'timeout' => self::DEFAULT_TIMEOUT,
				'headers' => [
					'Content-Type'  => 'application/json',
					'x-goog-api-key' => $this->api_key,
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || ! isset( $data['candidates'][0] ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Error communicating with the Google Gemini API.', 'wp-autoplugin' )
			);
		}

		$candidate = $data['candidates'][0];

		if ( ! isset( $candidate['content']['parts'] ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Error communicating with the Google Gemini API.', 'wp-autoplugin' )
			);
		}

		$parts     = $candidate['content']['parts'];
		$last_part = end( $parts );

		// Extract token usage.
		$this->last_token_usage = $this->extract_token_usage( $data, 'google' );

		return $last_part['text'] ?? '';
	}

	/**
	 * Get max tokens for the current model.
	 */
	private function get_max_tokens_for_model(): int {
		if (
			stripos( $this->model, 'gemini-2.5-pro' ) !== false ||
			stripos( $this->model, 'gemini-2.5-flash' ) !== false
		) {
			return 65535;
		}

		return $this->max_tokens;
	}

	/**
	 * Build the request body.
	 *
	 * @param array<string, mixed> $override_body
	 * @return array<string, mixed>
	 */
	private function build_request_body( string $prompt, int $max_tokens, array $override_body ): array {
		$safety_settings = [
			[
				'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
				'threshold' => 'BLOCK_ONLY_HIGH',
			],
		];

		$generation_config = [
			'temperature'     => $this->temperature,
			'maxOutputTokens' => $max_tokens,
		];

		// Merge override generationConfig.
		if ( isset( $override_body['generationConfig'] ) && is_array( $override_body['generationConfig'] ) ) {
			$generation_config = array_merge( $generation_config, $override_body['generationConfig'] );
			unset( $override_body['generationConfig'] );
		}

		// Override safetySettings if provided.
		if ( isset( $override_body['safetySettings'] ) && is_array( $override_body['safetySettings'] ) ) {
			$safety_settings = $override_body['safetySettings'];
			unset( $override_body['safetySettings'] );
		}

		// Determine contents.
		if ( isset( $override_body['contents'] ) && is_array( $override_body['contents'] ) ) {
			$contents = $override_body['contents'];
			unset( $override_body['contents'] );
		} else {
			$contents = [
				[
					'parts' => [
						[ 'text' => $prompt ],
					],
				],
			];
		}

		// Remove unsupported parameters.
		unset( $override_body['response_format'] );

		$body = [
			'contents'         => $contents,
			'safetySettings'   => $safety_settings,
			'generationConfig' => $generation_config,
		];

		if ( ! empty( $override_body ) ) {
			$body = array_merge( $body, $override_body );
		}

		return $body;
	}
}
