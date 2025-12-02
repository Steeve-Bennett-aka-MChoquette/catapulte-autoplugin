<?php
/**
 * Uninstall Catapulte-Autoplugin
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It cleans up all plugin options and transients from the database.
 *
 * @package Catapulte_Autoplugin
 */

// Exit if accessed directly or not in uninstall context.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options.
$options = [
	'catapulte_autoplugins',
	'catapulte_autoplugin_model',
	'catapulte_autoplugin_openai_api_key',
	'catapulte_autoplugin_anthropic_api_key',
	'catapulte_autoplugin_google_api_key',
	'catapulte_autoplugin_xai_api_key',
	'catapulte_autoplugin_openrouter_api_key',
	'catapulte_autoplugin_custom_models',
	'catapulte_autoplugin_planner_model',
	'catapulte_autoplugin_coder_model',
	'catapulte_autoplugin_reviewer_model',
	'catapulte_autoplugin_plugin_mode',
	'catapulte_autoplugin_fatal_error',
	'catapulte_autoplugin_notices',
	'catapulte_autoplugin_history_db_version',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete GitHub Updater transients.
// The transient keys are based on md5 hash of the plugin slug.
$plugin_slug    = 'catapulte-autoplugin/catapulte-autoplugin.php';
$transient_keys = [
	md5( $plugin_slug ) . '_new_version',
	md5( $plugin_slug ) . '_github_data',
	'catapulte_autoplugin_github_',
];

foreach ( $transient_keys as $key ) {
	delete_site_transient( $key );
}

// Clean up any transients that match our pattern.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'%_transient_catapulte_autoplugin_%',
		'%_transient_timeout_catapulte_autoplugin_%'
	)
);

// Drop the history table.
$table_name = $wpdb->prefix . 'catapulte_autoplugin_history';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
