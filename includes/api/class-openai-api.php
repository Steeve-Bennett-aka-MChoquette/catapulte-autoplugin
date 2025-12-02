<?php

declare(strict_types=1);

/**
 * OpenAI API class.
 *
 * Handles communication with the OpenAI API.
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
 * OpenAI API class.
 */
class OpenAI_API extends API {

	/**
	 * Reasoning models that use reasoning_effort parameter.
	 */
	private const REASONING_MODELS = [ 'o3-mini', 'o3', 'o4-mini' ];

	/**
	 * Models that use max_completion_tokens instead of max_tokens.
	 */
	private const COMPLETION_TOKEN_MODELS = [ 'o1', 'o1-preview', 'gpt-5', 'gpt-5-mini', 'gpt-5-nano', 'gpt-5-codex' ];

	/**
	 * Default API timeout in seconds.
	 */
	protected const DEFAULT_TIMEOUT = 300;

	/**
	 * Selected model.
	 */
	protected string $model = '';

	/**
	 * Original model name for special cases like o3-mini variants.
	 */
	protected string $original_model = '';

	/**
	 * Temperature parameter.
	 */
	protected float $temperature = 0.2;

	/**
	 * Max tokens parameter.
	 */
	protected int $max_tokens = 4096;

	/**
	 * Reasoning effort for o3-mini models.
	 */
	protected string $reasoning_effort = '';

	/**
	 * API URL.
	 */
	protected string $api_url = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Model parameters configuration.
	 *
	 * @return array<string, array{temperature?: float, max_tokens: int, reasoning_effort?: string}>
	 */
	protected function get_model_params(): array {
		return [
			'o3-mini-low'       => [ 'max_tokens' => 100000, 'reasoning_effort' => 'low' ],
			'o3-mini-medium'    => [ 'max_tokens' => 100000, 'reasoning_effort' => 'medium' ],
			'o3-mini-high'      => [ 'max_tokens' => 100000, 'reasoning_effort' => 'high' ],
			'o3-low'            => [ 'max_tokens' => 100000, 'reasoning_effort' => 'low' ],
			'o3-medium'         => [ 'max_tokens' => 100000, 'reasoning_effort' => 'medium' ],
			'o3-high'           => [ 'max_tokens' => 100000, 'reasoning_effort' => 'high' ],
			'o4-mini-low'       => [ 'max_tokens' => 100000, 'reasoning_effort' => 'low' ],
			'o4-mini-medium'    => [ 'max_tokens' => 100000, 'reasoning_effort' => 'medium' ],
			'o4-mini-high'      => [ 'max_tokens' => 100000, 'reasoning_effort' => 'high' ],
			'o1'                => [ 'max_tokens' => 32000 ],
			'o1-preview'        => [ 'max_tokens' => 32000 ],
			'gpt-4o'            => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'gpt-4.1'           => [ 'temperature' => 0.2, 'max_tokens' => 32768 ],
			'gpt-4.1-mini'      => [ 'temperature' => 0.2, 'max_tokens' => 32768 ],
			'gpt-4.1-nano'      => [ 'temperature' => 0.2, 'max_tokens' => 32768 ],
			'chatgpt-4o-latest' => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'gpt-4o-mini'       => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'gpt-4-turbo'       => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'gpt-3.5-turbo'     => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'gpt-5'             => [ 'max_tokens' => 128000 ],
			'gpt-5-mini'        => [ 'max_tokens' => 128000 ],
			'gpt-5-nano'        => [ 'max_tokens' => 128000 ],
			'gpt-5-codex'       => [ 'max_tokens' => 128000 ],
		];
	}

	/**
	 * Set the model, temperature, and max tokens.
	 */
	public function set_model( string $model ): void {
		$this->original_model = sanitize_text_field( $model );

		// Handle reasoning model variants (o3-mini-low, o3-high, o4-mini-medium, etc.).
		$reasoning_variants = [
			'o3-mini-low', 'o3-mini-medium', 'o3-mini-high',
			'o3-low', 'o3-medium', 'o3-high',
			'o4-mini-low', 'o4-mini-medium', 'o4-mini-high',
		];

		if ( in_array( $model, $reasoning_variants, true ) ) {
			$this->model            = (string) preg_replace( '/-low|-medium|-high$/', '', $model );
			$parts                  = explode( '-', $model );
			$this->reasoning_effort = end( $parts );
		} else {
			$this->model = $this->original_model;
		}

		$model_params = $this->get_model_params();
		if ( isset( $model_params[ $this->original_model ] ) ) {
			$params = $model_params[ $this->original_model ];
			$this->temperature = $params['temperature'] ?? $this->temperature;
			$this->max_tokens  = $params['max_tokens'];
			$this->reasoning_effort = $params['reasoning_effort'] ?? $this->reasoning_effort;
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
		$messages = $this->build_messages( $prompt, $system_message );
		$body     = $this->build_request_body( $messages );

		// Keep only allowed keys in the override body.
		$allowed_keys  = $this->get_allowed_parameters();
		$override_body = array_intersect_key( $override_body, array_flip( $allowed_keys ) );
		$body          = array_merge( $body, $override_body );

		$response = $this->make_api_request( $body );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $this->parse_response( $response );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Handle "Continue" functionality for truncated responses.
		if ( $this->is_response_truncated( $data ) ) {
			$data = $this->continue_response( $data, $messages, $body );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'openai' );

		return $this->extract_content( $data );
	}

	/**
	 * Build messages array for the API request.
	 *
	 * @return array<int, array{role: string, content: string}>
	 */
	protected function build_messages( string $prompt, string $system_message ): array {
		$messages = [];

		if ( $system_message !== '' ) {
			$messages[] = [
				'role'    => 'system',
				'content' => $system_message,
			];
		}

		$messages[] = [
			'role'    => 'user',
			'content' => $prompt,
		];

		return $messages;
	}

	/**
	 * Build the request body based on model type.
	 *
	 * @param array<int, array{role: string, content: string}> $messages
	 * @return array<string, mixed>
	 */
	protected function build_request_body( array $messages ): array {
		$body = [
			'model'    => $this->model,
			'messages' => $messages,
		];

		if ( in_array( $this->model, self::REASONING_MODELS, true ) ) {
			$body['max_completion_tokens'] = $this->max_tokens;
			$body['reasoning_effort']      = $this->reasoning_effort;
		} elseif ( in_array( $this->model, self::COMPLETION_TOKEN_MODELS, true ) ) {
			$body['max_completion_tokens'] = $this->max_tokens;
		} else {
			$body['temperature'] = $this->temperature;
			$body['max_tokens']  = $this->max_tokens;
		}

		return $body;
	}

	/**
	 * Make the API request.
	 *
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>|\WP_Error
	 */
	protected function make_api_request( array $body ): array|\WP_Error {
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

		return [ 'raw_response' => $response ];
	}

	/**
	 * Get request headers.
	 *
	 * @return array<string, string>
	 */
	protected function get_request_headers(): array {
		return [
			'Authorization' => 'Bearer ' . $this->api_key,
			'Content-Type'  => 'application/json',
		];
	}

	/**
	 * Parse the API response.
	 *
	 * @param array<string, mixed> $response
	 * @return array<string, mixed>|\WP_Error
	 */
	protected function parse_response( array $response ): array|\WP_Error {
		$body = wp_remote_retrieve_body( $response['raw_response'] );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Invalid response from API.', 'wp-autoplugin' )
			);
		}

		return $data;
	}

	/**
	 * Check if the response was truncated.
	 *
	 * @param array<string, mixed> $data
	 */
	protected function is_response_truncated( array $data ): bool {
		return isset( $data['choices'][0]['finish_reason'] )
			&& $data['choices'][0]['finish_reason'] === 'length';
	}

	/**
	 * Continue a truncated response.
	 *
	 * @param array<string, mixed>                             $data
	 * @param array<int, array{role: string, content: string}> $messages
	 * @param array<string, mixed>                             $original_body
	 * @return array<string, mixed>|\WP_Error
	 */
	protected function continue_response( array $data, array $messages, array $original_body ): array|\WP_Error {
		$messages[] = [
			'role'    => 'assistant',
			'content' => $data['choices'][0]['message']['content'],
		];

		$body = [
			'model'       => $this->model,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
			'messages'    => $messages,
		];

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

		$new_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $new_data['choices'][0]['message']['content'] ) ) {
			return new \WP_Error(
				'api_error',
				__( 'Error communicating with the API.', 'wp-autoplugin' )
			);
		}

		// Merge the new response with the old one.
		$data['choices'][0]['message']['content'] .= $new_data['choices'][0]['message']['content'];

		return $data;
	}

	/**
	 * Extract content from the API response.
	 *
	 * @param array<string, mixed> $data
	 */
	protected function extract_content( array $data ): string|\WP_Error {
		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return $data['choices'][0]['message']['content'];
		}

		return new \WP_Error(
			'api_error',
			__( 'Error communicating with the API.', 'wp-autoplugin' )
		);
	}

	/**
	 * Get the allowed parameters.
	 *
	 * @return array<int, string>
	 */
	protected function get_allowed_parameters(): array {
		return [
			'model',
			'temperature',
			'max_tokens',
			'max_completion_tokens',
			'max_output_tokens',
			'reasoning_effort',
			'messages',
			'response_format',
		];
	}

	/**
	 * Get the originally selected model name.
	 */
	public function get_selected_model(): string {
		return $this->original_model !== '' ? $this->original_model : $this->model;
	}
}
