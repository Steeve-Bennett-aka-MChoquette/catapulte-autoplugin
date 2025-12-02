<?php

declare(strict_types=1);

/**
 * Catapulte-Autoplugin API Handler class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.0
 * @link https://catapulte-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Catapulte_Autoplugin\Admin;

use Catapulte_Autoplugin\API;
use Catapulte_Autoplugin\OpenAI_API;
use Catapulte_Autoplugin\OpenAI_Responses_API;
use Catapulte_Autoplugin\Anthropic_API;
use Catapulte_Autoplugin\Google_Gemini_API;
use Catapulte_Autoplugin\XAI_API;
use Catapulte_Autoplugin\OpenRouter_API;
use Catapulte_Autoplugin\Custom_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles the API selection.
 */
class Api_Handler {

	/**
	 * The API object.
	 */
	public ?API $ai_api = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$model        = (string) get_option( 'catapulte_autoplugin_model', '' );
		$this->ai_api = $this->get_api( $model );
	}

	/**
	 * Get the API object based on the selected model or a custom model.
	 */
	public function get_api( string $model ): ?API {
		$openai_api_key     = (string) get_option( 'catapulte_autoplugin_openai_api_key', '' );
		$anthropic_api_key  = (string) get_option( 'catapulte_autoplugin_anthropic_api_key', '' );
		$google_api_key     = (string) get_option( 'catapulte_autoplugin_google_api_key', '' );
		$xai_api_key        = (string) get_option( 'catapulte_autoplugin_xai_api_key', '' );
		$openrouter_api_key = (string) get_option( 'catapulte_autoplugin_openrouter_api_key', '' );
		$custom_models      = get_option( 'catapulte_autoplugin_custom_models', [] );

		$models = Admin::get_models();

		// Check standard providers.
		$api = $this->get_standard_api(
			$model,
			$models,
			$openai_api_key,
			$anthropic_api_key,
			$google_api_key,
			$xai_api_key,
			$openrouter_api_key
		);

		if ( $api !== null ) {
			return $api;
		}

		// Check custom models.
		if ( is_array( $custom_models ) && ! empty( $custom_models ) ) {
			foreach ( $custom_models as $custom_model ) {
				if ( ! is_array( $custom_model ) ) {
					continue;
				}
				if ( ( $custom_model['name'] ?? '' ) === $model ) {
					$api = new Custom_API();
					$api->set_custom_config(
						(string) ( $custom_model['url'] ?? '' ),
						(string) ( $custom_model['apiKey'] ?? '' ),
						(string) ( $custom_model['modelParameter'] ?? '' ),
						(array) ( $custom_model['headers'] ?? [] )
					);
					return $api;
				}
			}
		}

		return null;
	}

	/**
	 * Get API for standard providers (OpenAI, Anthropic, Google, xAI, OpenRouter).
	 *
	 * @param array<string, array<string, string>> $models
	 */
	private function get_standard_api(
		string $model,
		array $models,
		string $openai_api_key,
		string $anthropic_api_key,
		string $google_api_key,
		string $xai_api_key,
		string $openrouter_api_key
	): ?API {
		// OpenAI.
		if ( $openai_api_key !== '' && isset( $models['OpenAI'][ $model ] ) ) {
			$api = $model === 'gpt-5-codex'
				? new OpenAI_Responses_API()
				: new OpenAI_API();
			$api->set_api_key( $openai_api_key );
			$api->set_model( $model );
			return $api;
		}

		// Anthropic.
		if ( $anthropic_api_key !== '' && isset( $models['Anthropic'][ $model ] ) ) {
			$api = new Anthropic_API();
			$api->set_api_key( $anthropic_api_key );
			$api->set_model( $model );
			return $api;
		}

		// Google.
		if ( $google_api_key !== '' && isset( $models['Google'][ $model ] ) ) {
			$api = new Google_Gemini_API();
			$api->set_api_key( $google_api_key );
			$api->set_model( $model );
			return $api;
		}

		// xAI.
		if ( $xai_api_key !== '' && isset( $models['xAI'][ $model ] ) ) {
			$api = new XAI_API();
			$api->set_api_key( $xai_api_key );
			$api->set_model( $model );
			return $api;
		}

		// OpenRouter.
		if ( $openrouter_api_key !== '' && isset( $models['OpenRouter'][ $model ] ) ) {
			$api = new OpenRouter_API();
			$api->set_api_key( $openrouter_api_key );
			$api->set_model( $model );
			return $api;
		}

		return null;
	}

	/**
	 * Get the planner model or fall back to default model.
	 */
	public function get_planner_model(): string {
		$planner_model = (string) get_option( 'catapulte_autoplugin_planner_model', '' );
		return $planner_model !== '' ? $planner_model : (string) get_option( 'catapulte_autoplugin_model', '' );
	}

	/**
	 * Get the coder model or fall back to default model.
	 */
	public function get_coder_model(): string {
		$coder_model = (string) get_option( 'catapulte_autoplugin_coder_model', '' );
		return $coder_model !== '' ? $coder_model : (string) get_option( 'catapulte_autoplugin_model', '' );
	}

	/**
	 * Get the reviewer model or fall back to default model.
	 */
	public function get_reviewer_model(): string {
		$reviewer_model = (string) get_option( 'catapulte_autoplugin_reviewer_model', '' );
		return $reviewer_model !== '' ? $reviewer_model : (string) get_option( 'catapulte_autoplugin_model', '' );
	}

	/**
	 * Get the API object for planner tasks.
	 */
	public function get_planner_api(): ?API {
		return $this->get_api( $this->get_planner_model() );
	}

	/**
	 * Get the API object for coder tasks.
	 */
	public function get_coder_api(): ?API {
		return $this->get_api( $this->get_coder_model() );
	}

	/**
	 * Get the API object for reviewer tasks.
	 */
	public function get_reviewer_api(): ?API {
		return $this->get_api( $this->get_reviewer_model() );
	}

	/**
	 * Get the model that will be used for the next task based on current page.
	 */
	public function get_next_task_model(): string {
		$screen = get_current_screen();
		if ( $screen === null ) {
			return (string) get_option( 'catapulte_autoplugin_model', '' );
		}

		return match ( $screen->id ) {
			'catapulte-autoplugin_page_catapulte-autoplugin-generate',
			'admin_page_catapulte-autoplugin-fix',
			'admin_page_catapulte-autoplugin-extend',
			'admin_page_catapulte-autoplugin-extend-hooks',
			'admin_page_catapulte-autoplugin-extend-theme' => $this->get_planner_model(),
			'admin_page_catapulte-autoplugin-explain'      => $this->get_reviewer_model(),
			default                                 => (string) get_option( 'catapulte_autoplugin_model', '' ),
		};
	}
}
