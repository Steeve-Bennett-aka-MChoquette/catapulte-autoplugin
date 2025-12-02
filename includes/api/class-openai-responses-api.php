<?php

declare(strict_types=1);

/**
 * OpenAI Responses API class.
 *
 * Handles communication with the OpenAI Responses API.
 *
 * @package WP-Autoplugin
 * @since 1.7.0
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
 * OpenAI Responses API class.
 */
class OpenAI_Responses_API extends OpenAI_API {

	/**
	 * API URL.
	 */
	protected string $api_url = 'https://api.openai.com/v1/responses';

	/**
	 * Send a prompt to the API.
	 */
	public function send_prompt(
		string $prompt,
		string $system_message = '',
		array $override_body = []
	): string|\WP_Error {
		$body = [
			'model' => $this->model,
			'input' => $prompt,
		];

		if ( $system_message !== '' ) {
			$body['instructions'] = $system_message;
		}

		// Responses API uses max_output_tokens for output limits.
		if ( $this->max_tokens > 0 ) {
			$body['max_output_tokens'] = $this->max_tokens;
		}

		if ( $this->reasoning_effort !== '' ) {
			$body['reasoning'] = [
				'effort' => $this->reasoning_effort,
			];
		}

		// Remove incompatible parameters.
		unset( $override_body['response_format'] );

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
				__( 'Invalid response from API.', 'wp-autoplugin' )
			);
		}

		if ( isset( $data['error'] ) ) {
			$message = $data['error']['message']
				?? __( 'Error communicating with the API.', 'wp-autoplugin' );
			return new \WP_Error( 'api_error', $message );
		}

		// Extract token usage for reporting.
		$this->last_token_usage = $this->extract_token_usage( $data, 'openai' );

		return $this->extract_responses_content( $data );
	}

	/**
	 * Extract content from the Responses API format.
	 *
	 * @param array<string, mixed> $data
	 */
	private function extract_responses_content( array $data ): string|\WP_Error {
		// Try direct output_text first.
		if ( isset( $data['output_text'] ) && $data['output_text'] !== '' ) {
			return $data['output_text'];
		}

		// Try extracting from output array.
		if ( isset( $data['output'] ) && is_array( $data['output'] ) ) {
			$output_text = '';

			foreach ( $data['output'] as $item ) {
				if ( ! isset( $item['content'] ) || ! is_array( $item['content'] ) ) {
					continue;
				}

				foreach ( $item['content'] as $content_item ) {
					if (
						isset( $content_item['type'], $content_item['text'] )
						&& $content_item['type'] === 'output_text'
					) {
						$output_text .= $content_item['text'];
					}
				}
			}

			if ( $output_text !== '' ) {
				return $output_text;
			}
		}

		return new \WP_Error(
			'api_error',
			__( 'Error communicating with the API.', 'wp-autoplugin' )
		);
	}

	/**
	 * Get the allowed parameters for the Responses API.
	 *
	 * @return array<int, string>
	 */
	protected function get_allowed_parameters(): array {
		return [
			'model',
			'instructions',
			'input',
			'top_p',
			'max_output_tokens',
			'metadata',
			'reasoning',
			'modalities',
			'previous_response_id',
			'store',
			'text',
			'audio',
			'attachments',
			'tools',
			'tool_choice',
			'parallel_tool_calls',
		];
	}
}
