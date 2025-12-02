<?php

declare(strict_types=1);

/**
 * Custom API class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.2
 * @version 2.0.0
 * @link https://catapulte-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Catapulte_Autoplugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom API class that connects to user-defined OpenAI-compatible endpoints.
 */
class Custom_API extends OpenAI_API {

	/**
	 * Additional headers specified by the user.
	 *
	 * @var array<string, string>
	 */
	protected array $extra_headers = [];

	/**
	 * Configure the custom API with the user-defined settings.
	 *
	 * @param string               $endpoint The custom API endpoint (url).
	 * @param string               $api_key  The API key for authentication.
	 * @param string               $model    The model parameter sent to the API.
	 * @param array<int, string>   $headers  Additional headers (key/value pairs).
	 */
	public function set_custom_config(
		string $endpoint,
		string $api_key,
		string $model,
		array $headers = []
	): void {
		$this->api_url       = esc_url_raw( $endpoint );
		$this->api_key       = $api_key;
		$this->model         = $model;
		$this->original_model = $model;
		$this->extra_headers = $this->parse_extra_headers( $headers );
	}

	/**
	 * Get request headers including extra user-defined headers.
	 *
	 * @return array<string, string>
	 */
	protected function get_request_headers(): array {
		return array_merge(
			[
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			],
			$this->extra_headers
		);
	}

	/**
	 * Send a prompt to the API.
	 */
	public function send_prompt(
		string $prompt,
		string $system_message = '',
		array $override_body = []
	): string|\WP_Error {
		$messages = $this->build_messages( $prompt, $system_message );

		$body = [
			'model'       => $this->model,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
			'messages'    => $messages,
		];

		// Only keep valid keys from $override_body.
		$allowed_keys  = $this->get_allowed_parameters();
		$override_body = array_intersect_key( $override_body, array_flip( $allowed_keys ) );
		$body          = array_merge( $body, $override_body );

		$response = wp_remote_post(
			$this->api_url,
			[
				'timeout' => static::DEFAULT_TIMEOUT,
				'headers' => $this->get_request_headers(),
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Invalid response from API.', 'catapulte-autoplugin' )
			);
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'custom' );

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Error communicating with the API.', 'catapulte-autoplugin' )
			);
		}

		return $data['choices'][0]['message']['content'];
	}

	/**
	 * Convert the user's header lines into an associative array.
	 * SECURITY FIX: Validates headers against CRLF injection attacks.
	 *
	 * @param array<int, string> $headers Array of lines like ["X-Test=Value", "Accept=application/json"].
	 * @return array<string, string> Key-value pairs for use in wp_remote_post header.
	 */
	protected function parse_extra_headers( array $headers ): array {
		$parsed = [];

		foreach ( $headers as $header_line ) {
			if ( ! is_string( $header_line ) || ! str_contains( $header_line, '=' ) ) {
				continue;
			}

			[ $key, $value ] = explode( '=', $header_line, 2 );
			$key   = trim( $key );
			$value = trim( $value );

			// Skip empty keys or values.
			if ( $key === '' || $value === '' ) {
				continue;
			}

			// SECURITY: Reject headers with CRLF characters (injection prevention).
			if ( preg_match( '/[\r\n\0]/', $key . $value ) ) {
				continue;
			}

			// SECURITY: Reject headers with control characters.
			if ( preg_match( '/[\x00-\x1f\x7f]/', $key . $value ) ) {
				continue;
			}

			$parsed[ $key ] = $value;
		}

		return $parsed;
	}
}
