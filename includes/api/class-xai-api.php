<?php

declare(strict_types=1);

/**
 * The xAI API class. Their API is compatible with OpenAI's, so this class extends OpenAI_API.
 *
 * @package Catapulte-Autoplugin
 * @since 1.1
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
 * The xAI API class.
 */
class XAI_API extends OpenAI_API {

	/**
	 * API URL.
	 */
	protected string $api_url = 'https://api.x.ai/v1/chat/completions';

	/**
	 * Max tokens parameter.
	 */
	protected int $max_tokens = 8192;

	/**
	 * A simpler model setter for xAI.
	 */
	public function set_model( string $model ): void {
		$this->model          = sanitize_text_field( $model );
		$this->original_model = $this->model;
	}

	/**
	 * Get the allowed parameters for xAI.
	 *
	 * @return array<int, string>
	 */
	protected function get_allowed_parameters(): array {
		return [
			'model',
			'temperature',
			'max_tokens',
			'messages',
		];
	}
}
