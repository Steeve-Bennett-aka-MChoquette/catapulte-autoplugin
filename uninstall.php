<?php
/**
 * Uninstall WP-Autoplugin
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It cleans up all plugin options and transients from the database.
 *
 * @package WP_Autoplugin
 */

// Exit if accessed directly or not in uninstall context.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options.
$options = [
	'wp_autoplugins',
	'wp_autoplugin_model',
	'wp_autoplugin_openai_api_key',
	'wp_autoplugin_anthropic_api_key',
	'wp_autoplugin_google_api_key',
	'wp_autoplugin_xai_api_key',
	'wp_autoplugin_openrouter_api_key',
	'wp_autoplugin_custom_models',
	'wp_autoplugin_planner_model',
	'wp_autoplugin_coder_model',
	'wp_autoplugin_reviewer_model',
	'wp_autoplugin_plugin_mode',
	'wp_autoplugin_fatal_error',
	'wp_autoplugin_notices',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete GitHub Updater transients.
// The transient keys are based on md5 hash of the plugin slug.
$plugin_slug    = 'wp-autoplugin/wp-autoplugin.php';
$transient_keys = [
	md5( $plugin_slug ) . '_new_version',
	md5( $plugin_slug ) . '_github_data',
	'wp_autoplugin_github_',
];

foreach ( $transient_keys as $key ) {
	delete_site_transient( $key );
}

// Clean up any transients that match our pattern.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'%_transient_wp_autoplugin_%',
		'%_transient_timeout_wp_autoplugin_%'
	)
);
