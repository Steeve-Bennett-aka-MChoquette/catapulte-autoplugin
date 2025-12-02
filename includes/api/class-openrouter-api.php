<?php

declare(strict_types=1);

/**
 * OpenRouter API class.
 *
 * Handles communication with the OpenRouter API.
 *
 * @package Catapulte-Autoplugin
 * @since 1.8.0
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
 * OpenRouter API class.
 */
class OpenRouter_API extends OpenAI_API {

	/**
	 * API URL.
	 */
	protected string $api_url = 'https://openrouter.ai/api/v1/chat/completions';

	/**
	 * Model parameters configuration.
	 *
	 * @return array<string, array{temperature: float, max_tokens: int}>
	 */
	protected function get_model_params(): array {
		return [
			// Budget - DeepSeek.
			'deepseek/deepseek-chat-v3-0324'         => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'deepseek/deepseek-chat-v3.1'            => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'deepseek/deepseek-r1'                   => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'deepseek/deepseek-r1-0528'              => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'deepseek/deepseek-r1-distill-llama-70b' => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],

			// Budget - Qwen.
			'qwen/qwen3-coder-480b-a35b-07-25'       => [ 'temperature' => 0.2, 'max_tokens' => 32768 ],
			'qwen/qwen3-coder-plus'                  => [ 'temperature' => 0.2, 'max_tokens' => 32768 ],
			'qwen/qwen3-coder-30b-a3b-instruct'      => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],

			// Budget - Mistral.
			'mistralai/codestral-2508'                        => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'mistralai/devstral-small-2507'                   => [ 'temperature' => 0.2, 'max_tokens' => 16384 ],
			'mistralai/mistral-small-3.2-24b-instruct-2506'   => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'mistralai/mistral-medium-3.1'                    => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'mistralai/mistral-large-2411'                    => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],

			// Premium via OpenRouter.
			'anthropic/claude-sonnet-4'              => [ 'temperature' => 0.2, 'max_tokens' => 16000 ],
			'anthropic/claude-3.5-sonnet'            => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'anthropic/claude-3-opus'                => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'openai/gpt-4o'                          => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'openai/gpt-4o-mini'                     => [ 'temperature' => 0.2, 'max_tokens' => 4096 ],
			'google/gemini-2.5-pro'                  => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
			'google/gemini-2.5-flash'                => [ 'temperature' => 0.2, 'max_tokens' => 8192 ],
		];
	}

	/**
	 * Set the model, temperature, and max tokens.
	 */
	public function set_model( string $model ): void {
		$this->original_model = sanitize_text_field( $model );
		$this->model          = $this->original_model;

		$model_params = $this->get_model_params();
		if ( isset( $model_params[ $this->original_model ] ) ) {
			$this->temperature = $model_params[ $this->original_model ]['temperature'];
			$this->max_tokens  = $model_params[ $this->original_model ]['max_tokens'];
		} else {
			// Default values for unknown models.
			$this->temperature = 0.2;
			$this->max_tokens  = 4096;
		}
	}

	/**
	 * Get request headers with OpenRouter-specific headers.
	 *
	 * @return array<string, string>
	 */
	protected function get_request_headers(): array {
		return [
			'Authorization' => 'Bearer ' . $this->api_key,
			'Content-Type'  => 'application/json',
			'HTTP-Referer'  => esc_url( home_url() ),
			'X-Title'       => 'Catapulte-Autoplugin',
		];
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
			'messages'    => $messages,
			'temperature' => $this->temperature,
			'max_tokens'  => $this->max_tokens,
		];

		// Keep only allowed keys in the override body.
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

		// Handle "Continue" functionality for long responses.
		if ( isset( $data['choices'][0]['finish_reason'] ) && $data['choices'][0]['finish_reason'] === 'length' ) {
			$data = $this->continue_openrouter_response( $data, $messages );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'openrouter' );

		return $this->extract_content( $data );
	}

	/**
	 * Continue a truncated OpenRouter response.
	 *
	 * @param array<string, mixed>                             $data
	 * @param array<int, array{role: string, content: string}> $messages
	 * @return array<string, mixed>|\WP_Error
	 */
	private function continue_openrouter_response( array $data, array $messages ): array|\WP_Error {
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
				__( 'Error communicating with the API.', 'catapulte-autoplugin' )
			);
		}

		// Merge the new response with the old one.
		$data['choices'][0]['message']['content'] .= $new_data['choices'][0]['message']['content'];

		return $data;
	}
}
