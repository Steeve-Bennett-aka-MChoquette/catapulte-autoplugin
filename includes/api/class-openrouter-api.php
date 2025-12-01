<?php
/**
 * OpenRouter API class.
 *
 * Handles communication with the OpenRouter API.
 *
 * @package WP-Autoplugin
 * @since 1.8.0
 * @link https://wp-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WP_Autoplugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenRouter API class.
 */
class OpenRouter_API extends OpenAI_API {

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://openrouter.ai/api/v1/chat/completions';

	/**
	 * Set the model, temperature, and max tokens.
	 *
	 * @param string $model The model.
	 */
	public function set_model( $model ) {
		$this->original_model = sanitize_text_field( $model );
		$this->model          = $this->original_model;

		// Set default parameters for OpenRouter models.
		// Organized by category: Free, Ultra-Budget, Premium Affordable, Premium.
		$model_params = [
			// === GRATUITS ===
			'deepseek/deepseek-chat-v3-0324:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-r1:free'        => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-r1-0528:free'   => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-coder-480b-a35b-07-25:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 32768,
			],
			'qwen/qwen3-235b-a22b-04-28:free'  => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'meta-llama/llama-4-maverick-17b-128e-instruct:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'meta-llama/llama-3.3-70b-instruct:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'meta-llama/llama-3.1-405b-instruct:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'google/gemini-2.0-flash-exp:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'mistralai/mistral-small-3.2-24b-instruct-2506:free' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'mistralai/mistral-nemo:free'      => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'kwaipilot/kat-coder-pro:free'     => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],

			// === ULTRA-BUDGET ===
			'qwen/qwen3-coder-30b-a3b-instruct' => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-30b-a3b-04-28'         => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-32b-04-28'             => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-14b-04-28'             => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'mistralai/mistral-small-3.2-24b-instruct-2506' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'mistralai/devstral-small-2507'    => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-r1-distill-llama-70b' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'deepseek/deepseek-r1-0528-qwen3-8b' => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'google/gemma-3-27b-it'            => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'google/gemma-3-12b-it'            => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'google/gemma-3-4b-it'             => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],

			// === PREMIUM ABORDABLE ===
			'deepseek/deepseek-chat-v3-0324'   => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-v3.2'           => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-r1'             => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'deepseek/deepseek-r1-0528'        => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-coder-480b-a35b-07-25' => [
				'temperature' => 0.2,
				'max_tokens'  => 32768,
			],
			'qwen/qwen3-coder-plus'            => [
				'temperature' => 0.2,
				'max_tokens'  => 32768,
			],
			'qwen/qwen3-235b-a22b-07-25'       => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'qwen/qwen3-max'                   => [
				'temperature' => 0.2,
				'max_tokens'  => 32768,
			],
			'mistralai/codestral-2508'         => [
				'temperature' => 0.2,
				'max_tokens'  => 16384,
			],
			'mistralai/mistral-medium-3.1'     => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'mistralai/mistral-large-2411'     => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],

			// === PREMIUM via OpenRouter ===
			'anthropic/claude-sonnet-4'        => [
				'temperature' => 0.2,
				'max_tokens'  => 16000,
			],
			'anthropic/claude-3.5-sonnet'      => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'anthropic/claude-3-opus'          => [
				'temperature' => 0.2,
				'max_tokens'  => 4096,
			],
			'openai/gpt-4o'                    => [
				'temperature' => 0.2,
				'max_tokens'  => 4096,
			],
			'openai/gpt-4o-mini'               => [
				'temperature' => 0.2,
				'max_tokens'  => 4096,
			],
			'google/gemini-2.5-pro'            => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
			'google/gemini-2.5-flash'          => [
				'temperature' => 0.2,
				'max_tokens'  => 8192,
			],
		];

		if ( isset( $model_params[ $this->original_model ] ) ) {
			if ( isset( $model_params[ $this->original_model ]['temperature'] ) ) {
				$this->temperature = $model_params[ $this->original_model ]['temperature'];
			}
			if ( isset( $model_params[ $this->original_model ]['max_tokens'] ) ) {
				$this->max_tokens = $model_params[ $this->original_model ]['max_tokens'];
			}
		} else {
			// Default values for unknown models.
			$this->temperature = 0.2;
			$this->max_tokens  = 4096;
		}
	}

	/**
	 * Send a prompt to the API.
	 *
	 * @param string $prompt The prompt.
	 * @param string $system_message The system message.
	 * @param array  $override_body The override body.
	 */
	public function send_prompt( $prompt, $system_message = '', $override_body = [] ) {
		$messages = [];
		if ( ! empty( $system_message ) ) {
			$messages[] = [
				'role'    => 'system',
				'content' => $system_message,
			];
		}

		$messages[] = [
			'role'    => 'user',
			'content' => $prompt,
		];

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

		$headers = [
			'Authorization' => 'Bearer ' . $this->api_key,
			'Content-Type'  => 'application/json',
			'HTTP-Referer'  => home_url(),
			'X-Title'       => 'WP-Autoplugin',
		];

		$response = wp_remote_post(
			$this->api_url,
			[
				'timeout' => 300,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// "Continue" functionality for long responses.
		if ( isset( $data['choices'][0]['finish_reason'] ) && 'length' === $data['choices'][0]['finish_reason'] ) {
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
					'timeout' => 300,
					'headers' => $headers,
					'body'    => wp_json_encode( $body ),
				]
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body     = wp_remote_retrieve_body( $response );
			$new_data = json_decode( $body, true );

			if ( ! isset( $new_data['choices'][0]['message']['content'] ) ) {
				return new \WP_Error( 'api_error', 'Error communicating with the API.' . "\n" . print_r( $new_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			// Merge the new response with the old one.
			$data['choices'][0]['message']['content'] .= $new_data['choices'][0]['message']['content'];
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'openai' );

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return $data['choices'][0]['message']['content'];
		} else {
			return new \WP_Error( 'api_error', 'Error communicating with the API.' . "\n" . print_r( $data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}
}
