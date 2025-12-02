<?php

declare(strict_types=1);
/**
 * Catapulte-Autoplugin Admin Settings class.
 *
 * @package Catapulte-Autoplugin
 */

namespace Catapulte_Autoplugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that registers plugin settings in the admin.
 */
class Settings {

	/**
	 * Constructor hooks into 'admin_init'.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the plugin settings fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// API keys - preserve existing value if empty submitted.
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_openai_api_key', [
			'type'              => 'string',
			'sanitize_callback' => fn( $value ) => $this->sanitize_api_key( $value, 'catapulte_autoplugin_openai_api_key' ),
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_anthropic_api_key', [
			'type'              => 'string',
			'sanitize_callback' => fn( $value ) => $this->sanitize_api_key( $value, 'catapulte_autoplugin_anthropic_api_key' ),
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_google_api_key', [
			'type'              => 'string',
			'sanitize_callback' => fn( $value ) => $this->sanitize_api_key( $value, 'catapulte_autoplugin_google_api_key' ),
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_xai_api_key', [
			'type'              => 'string',
			'sanitize_callback' => fn( $value ) => $this->sanitize_api_key( $value, 'catapulte_autoplugin_xai_api_key' ),
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_openrouter_api_key', [
			'type'              => 'string',
			'sanitize_callback' => fn( $value ) => $this->sanitize_api_key( $value, 'catapulte_autoplugin_openrouter_api_key' ),
		] );

		// Model settings - sanitize as text fields.
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_model', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_planner_model', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_coder_model', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_reviewer_model', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		// Plugin mode - validate against allowed values.
		register_setting( 'catapulte_autoplugin_settings', 'catapulte_autoplugin_plugin_mode', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_plugin_mode' ],
		] );
	}

	/**
	 * Sanitize the plugin mode setting.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string The sanitized value.
	 */
	public function sanitize_plugin_mode( mixed $value ): string {
		$allowed = [ 'simple', 'complex' ];
		$value   = sanitize_text_field( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : 'simple';
	}

	/**
	 * Sanitize an API key, preserving existing value if empty.
	 *
	 * @param mixed  $value      The submitted value.
	 * @param string $option_name The option name for retrieving existing value.
	 * @return string The sanitized API key.
	 */
	private function sanitize_api_key( mixed $value, string $option_name ): string {
		$value = sanitize_text_field( (string) $value );
		// If empty, preserve existing key.
		if ( empty( $value ) ) {
			return (string) get_option( $option_name, '' );
		}
		return $value;
	}
}
