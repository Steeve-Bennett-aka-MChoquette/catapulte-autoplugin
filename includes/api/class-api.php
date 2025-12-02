<?php

declare(strict_types=1);

/**
 * Main API class.
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
 * Base API class for all API providers.
 */
abstract class API {

	/**
	 * API key.
	 */
	protected string $api_key = '';

	/**
	 * Last API response token usage.
	 *
	 * @var array{input_tokens: int, output_tokens: int}
	 */
	protected array $last_token_usage = [
		'input_tokens'  => 0,
		'output_tokens' => 0,
	];

	/**
	 * Set the API key.
	 */
	public function set_api_key( string $api_key ): void {
		$this->api_key = sanitize_text_field( $api_key );
	}

	/**
	 * Get the last API response token usage.
	 *
	 * @return array{input_tokens: int, output_tokens: int} Token usage data.
	 */
	public function get_last_token_usage(): array {
		return $this->last_token_usage;
	}

	/**
	 * Send a prompt to the API.
	 *
	 * @param string $prompt        The prompt to send.
	 * @param string $system_message Optional system message.
	 * @param array  $override_body  Optional body overrides.
	 * @return string|\WP_Error The response content or error.
	 */
	abstract public function send_prompt(
		string $prompt,
		string $system_message = '',
		array $override_body = []
	): string|\WP_Error;

	/**
	 * Set the model to use.
	 */
	abstract public function set_model( string $model ): void;

	/**
	 * Extract and normalize token usage from API response.
	 *
	 * @param array  $response The API response data.
	 * @param string $provider The API provider name.
	 * @return array{input_tokens: int, output_tokens: int} Normalized token usage.
	 */
	protected function extract_token_usage( array $response, string $provider ): array {
		$default_usage = [
			'input_tokens'  => 0,
			'output_tokens' => 0,
		];

		return match ( $provider ) {
			'anthropic' => [
				'input_tokens'  => (int) ( $response['usage']['input_tokens'] ?? 0 ),
				'output_tokens' => (int) ( $response['usage']['output_tokens'] ?? 0 ),
			],
			'google' => [
				'input_tokens'  => (int) ( $response['usageMetadata']['promptTokenCount'] ?? 0 ),
				'output_tokens' => (int) ( $response['usageMetadata']['candidatesTokenCount'] ?? 0 ),
			],
			'openai', 'xai', 'openrouter', 'custom' => [
				'input_tokens'  => (int) ( $response['usage']['prompt_tokens']
					?? $response['usage']['input_tokens'] ?? 0 ),
				'output_tokens' => (int) ( $response['usage']['completion_tokens']
					?? $response['usage']['output_tokens'] ?? 0 ),
			],
			default => $default_usage,
		};
	}
}
