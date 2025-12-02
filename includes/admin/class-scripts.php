<?php

declare(strict_types=1);
/**
 * Catapulte-Autoplugin Admin Scripts class.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 2.0.1
 */

namespace Catapulte_Autoplugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that enqueues admin scripts and styles.
 */
class Scripts {

	/**
	 * Safely validate and check if a plugin is active.
	 *
	 * @param string $plugin_file The plugin file path from GET parameter.
	 * @return bool Whether the plugin is active.
	 */
	private function is_plugin_active_safe( string $plugin_file ): bool {
		// Sanitize and constrain plugin path inside plugins directory.
		$plugin_file  = ltrim( str_replace( [ '..\\', '../', '\\' ], '/', $plugin_file ), '/' );
		$plugin_path  = wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_file );
		$plugins_base = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) );

		// Verify the path is within the plugins directory.
		if ( strpos( $plugin_path, $plugins_base ) !== 0 || ! file_exists( $plugin_path ) ) {
			return false;
		}

		return is_plugin_active( $plugin_file );
	}

	/**
	 * Constructor hooks for scripts and inline CSS.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_head', [ $this, 'admin_css' ] );
	}

	/**
	 * Get localized messages for JavaScript.
	 *
	 * @return array
	 */
	private function get_localized_messages() {
		return [
			'empty_description'              => esc_html__( 'Please enter a plugin description.', 'catapulte-autoplugin' ),
			'generating_plan'                => esc_html__( 'Generating a plan for your plugin.', 'catapulte-autoplugin' ),
			'plan_generation_error'          => esc_html__( 'Error generating the plugin plan.', 'catapulte-autoplugin' ),
			'generating_code'                => esc_html__( 'Generating code.', 'catapulte-autoplugin' ),
			'code_generation_error'          => esc_html__( 'Error generating the plugin code.', 'catapulte-autoplugin' ),
			'plugin_creation_error'          => esc_html__( 'Error creating the plugin.', 'catapulte-autoplugin' ),
			'creating_plugin'                => esc_html__( 'Installing the plugin.', 'catapulte-autoplugin' ),
			'plugin_created'                 => esc_html__( 'Plugin successfully installed.', 'catapulte-autoplugin' ),
			'how_to_test'                    => esc_html__( 'How to test it?', 'catapulte-autoplugin' ),
			'use_fixer'                      => esc_html__( 'If you notice any issues, use the Fix button in the Autoplugins list.', 'catapulte-autoplugin' ),
			'activate'                       => esc_html__( 'Activate Plugin', 'catapulte-autoplugin' ),
			'code_updated'                   => esc_html__( 'The plugin code has been updated.', 'catapulte-autoplugin' ),
			'generating_explanation'         => esc_html__( 'Generating explanation...', 'catapulte-autoplugin' ),
			'explanation_error'              => esc_html__( 'Error generating explanation.', 'catapulte-autoplugin' ),
			'security_focus'                 => esc_html__( 'Security Analysis', 'catapulte-autoplugin' ),
			'performance_focus'              => esc_html__( 'Performance Review', 'catapulte-autoplugin' ),
			'code_quality_focus'             => esc_html__( 'Code Quality Analysis', 'catapulte-autoplugin' ),
			'usage_focus'                    => esc_html__( 'Usage Instructions', 'catapulte-autoplugin' ),
			'general_explanation'            => esc_html__( 'General Explanation', 'catapulte-autoplugin' ),
			'copied'                         => esc_html__( 'Explanation copied to clipboard!', 'catapulte-autoplugin' ),
			'copy_failed'                    => esc_html__( 'Failed to copy explanation.', 'catapulte-autoplugin' ),
			'empty_changes_description'      => esc_html__( 'Please describe the changes you want to make to the plugin.', 'catapulte-autoplugin' ),
			'plan_generation_error_dev'      => esc_html__( 'Error generating the development plan.', 'catapulte-autoplugin' ),
			'generating_extended_code'       => esc_html__( 'Generating the extended plugin code.', 'catapulte-autoplugin' ),
			'code_generation_error_extended' => esc_html__( 'Error generating the extended code.', 'catapulte-autoplugin' ),
			'plugin_creation_error_extended' => esc_html__( 'Error creating the extended plugin.', 'catapulte-autoplugin' ),
			'creating_extended_plugin'       => esc_html__( 'Creating the extension plugin.', 'catapulte-autoplugin' ),
			'plugin_activation_error'        => esc_html__( 'Error activating the plugin.', 'catapulte-autoplugin' ),
			'extracting_hooks'               => esc_html__( 'Extracting hooks, please wait...', 'catapulte-autoplugin' ),
			'no_hooks_found'                 => esc_html__( 'No hooks found in the codebase. Cannot extend the plugin.', 'catapulte-autoplugin' ),
			'drop_files_to_attach'           => esc_html__( 'Drop files to attach', 'catapulte-autoplugin' ),
		];
	}

	/**
	 * Enqueue scripts/styles depending on the current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		// A small utility script, used on multiple pages.
		wp_register_script(
			'catapulte-autoplugin-utils',
			CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/utils.js',
			[],
			CATAPULTE_AUTOPLUGIN_VERSION,
			true
		);

		// Common scripts.
		wp_enqueue_script(
			'catapulte-autoplugin-common',
			CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/common.js',
			[ 'catapulte-autoplugin-utils' ],
			CATAPULTE_AUTOPLUGIN_VERSION,
			true
		);

		$localized_data = [
			'ajax_url'               => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'                  => wp_create_nonce( 'catapulte_autoplugin_generate' ),
			'messages'               => $this->get_localized_messages(),
			'supported_image_models' => \Catapulte_Autoplugin\AI_Utils::get_supported_image_models(),
		];

		// The main list page.
		if ( $screen->id === 'toplevel_page_catapulte-autoplugin' ) {
			wp_enqueue_script(
				'catapulte-autoplugin',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/list-plugins.js',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);
			wp_enqueue_style(
				'catapulte-autoplugin',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/list-plugins.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'catapulte-autoplugin_page_catapulte-autoplugin-generate' ) {
			// Code editor (CodeMirror) for displaying plugin code.
			$settings = wp_enqueue_code_editor( [ 'type' => 'application/x-httpd-php' ] );
			if ( false !== $settings ) {
				wp_enqueue_script( 'wp-theme-plugin-editor' );
				wp_enqueue_style( 'wp-codemirror' );
			}

			wp_enqueue_script(
				'catapulte-autoplugin-generator',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/generator.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			$localized_data['fix_url']         = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin-fix&nonce=' . wp_create_nonce( 'catapulte-autoplugin-fix-plugin' ) ) );
			$localized_data['activate_url']    = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin&action=activate&nonce=' . wp_create_nonce( 'catapulte-autoplugin-activate-plugin' ) ) );
			$localized_data['testing_plan']    = '';
			$localized_data['plugin_examples'] = [
				esc_html__( 'A simple contact form with honeypot spam protection.', 'catapulte-autoplugin' ),
				esc_html__( 'A custom post type for testimonials.', 'catapulte-autoplugin' ),
				esc_html__( 'A widget that displays recent posts.', 'catapulte-autoplugin' ),
				esc_html__( 'A simple image compression tool.', 'catapulte-autoplugin' ),
			];

			wp_enqueue_style(
				'catapulte-autoplugin-generator',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/generator.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'admin_page_catapulte-autoplugin-fix' ) {
			// Code editor for Fix page.
			$settings = wp_enqueue_code_editor( [ 'type' => 'application/x-httpd-php' ] );
			if ( false !== $settings ) {
				wp_enqueue_script( 'wp-theme-plugin-editor' );
				wp_enqueue_style( 'wp-codemirror' );
			}

			$is_plugin_active = false;
			if ( isset( $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$plugin_file      = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$is_plugin_active = $this->is_plugin_active_safe( $plugin_file );
			}

			wp_enqueue_script(
				'catapulte-autoplugin-fix',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/fixer.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			$localized_data['activate_url']     = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin&action=activate&nonce=' . wp_create_nonce( 'catapulte-autoplugin-activate-plugin' ) ) );
			$localized_data['is_plugin_active'] = $is_plugin_active;

			wp_enqueue_style(
				'catapulte-autoplugin-fix',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/fixer.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);

			// Reuse generator styles for multi-file editor UI
			wp_enqueue_style(
				'catapulte-autoplugin-generator-shared',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/generator.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'admin_page_catapulte-autoplugin-extend' ) {
			// Code editor for Extend page.
			$settings = wp_enqueue_code_editor( [ 'type' => 'application/x-httpd-php' ] );
			if ( false !== $settings ) {
				wp_enqueue_script( 'wp-theme-plugin-editor' );
				wp_enqueue_style( 'wp-codemirror' );
			}

			$is_plugin_active = false;
			if ( isset( $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$plugin_file      = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$is_plugin_active = $this->is_plugin_active_safe( $plugin_file );
			}

			wp_enqueue_script(
				'catapulte-autoplugin-extend',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/extender.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			$localized_data['activate_url']     = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin&action=activate&nonce=' . wp_create_nonce( 'catapulte-autoplugin-activate-plugin' ) ) );
			$localized_data['is_plugin_active'] = $is_plugin_active;

			wp_enqueue_style(
				'catapulte-autoplugin-extend',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/extender.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);

			// Reuse generator styles for multi-file editor UI
			wp_enqueue_style(
				'catapulte-autoplugin-generator-shared',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/generator.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'admin_page_catapulte-autoplugin-explain' ) {
			// Enqueue marked.js, purify.min.js for markdown rendering.
			wp_enqueue_script(
				'catapulte-autoplugin-marked',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/marked.min.js',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);
			wp_enqueue_script(
				'catapulte-autoplugin-purify',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/purify.min.js',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			// Enqueue scripts and styles for the Explain Plugin page.
			wp_enqueue_script(
				'catapulte-autoplugin-explainer',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/explainer.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			wp_enqueue_style(
				'catapulte-autoplugin-explainer',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/explainer.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'admin_page_catapulte-autoplugin-extend-hooks' ) {
			$settings = wp_enqueue_code_editor( [ 'type' => 'application/x-httpd-php' ] );
			if ( false !== $settings ) {
				wp_enqueue_script( 'wp-theme-plugin-editor' );
				wp_enqueue_style( 'wp-codemirror' );
			}

			$is_plugin_active = false;
			if ( isset( $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$plugin_file      = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification -- Nonce verification is not needed here.
				$is_plugin_active = $this->is_plugin_active_safe( $plugin_file );
			}

			wp_enqueue_script(
				'catapulte-autoplugin-extend-hooks',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/hooks-extender.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			$localized_data['activate_url']     = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin&action=activate&nonce=' . wp_create_nonce( 'catapulte-autoplugin-activate-plugin' ) ) );
			$localized_data['is_plugin_active'] = $is_plugin_active;

			wp_enqueue_style(
				'catapulte-autoplugin-extend-hooks',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/extender.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);

			// Reuse generator styles so Project Structure table matches other flows
			wp_enqueue_style(
				'catapulte-autoplugin-generator-shared',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/generator.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		} elseif ( $screen->id === 'admin_page_catapulte-autoplugin-extend-theme' ) {
			$settings = wp_enqueue_code_editor( [ 'type' => 'application/x-httpd-php' ] );
			if ( false !== $settings ) {
				wp_enqueue_script( 'wp-theme-plugin-editor' );
				wp_enqueue_style( 'wp-codemirror' );
			}

			wp_enqueue_script(
				'catapulte-autoplugin-extend-theme',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/theme-extender.js',
				[ 'catapulte-autoplugin-utils' ],
				CATAPULTE_AUTOPLUGIN_VERSION,
				true
			);

			$localized_data['activate_url'] = esc_url( admin_url( 'admin.php?page=catapulte-autoplugin&action=activate&nonce=' . wp_create_nonce( 'catapulte-autoplugin-activate-plugin' ) ) );

			wp_enqueue_style(
				'catapulte-autoplugin-extend-theme',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/extender.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);

			// Reuse generator styles for multi-file editor UI
			wp_enqueue_style(
				'catapulte-autoplugin-generator-shared',
				CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/css/generator.css',
				[],
				CATAPULTE_AUTOPLUGIN_VERSION
			);
		}

		// Footer script with localized data.
		wp_enqueue_script(
			'catapulte-autoplugin-footer',
			CATAPULTE_AUTOPLUGIN_URL . 'assets/admin/js/footer.js',
			[ 'jquery' ],
			CATAPULTE_AUTOPLUGIN_VERSION,
			true
		);

		$api_handler = new \Catapulte_Autoplugin\Admin\API_Handler();

		wp_localize_script(
			'catapulte-autoplugin-common',
			'catapulte_autoplugin',
			$localized_data
		);

		$default_step = 'default';

		// Set default step based on page context.
		if ( $screen ) {
			switch ( $screen->id ) {
				case 'catapulte-autoplugin_page_catapulte-autoplugin-generate':
					$default_step = 'generatePlan';
					break;
				case 'admin_page_catapulte-autoplugin-fix':
					$default_step = 'generatePlan';
					break;
				case 'admin_page_catapulte-autoplugin-extend':
					$default_step = 'generatePlan';
					break;
				case 'admin_page_catapulte-autoplugin-extend-hooks':
					$default_step = 'generatePlan';
					break;
				case 'admin_page_catapulte-autoplugin-extend-theme':
					$default_step = 'generatePlan';
					break;
				case 'admin_page_catapulte-autoplugin-explain':
					$default_step = 'askQuestion';
					break;
			}
		}

		wp_localize_script(
			'catapulte-autoplugin-footer',
			'wpAutopluginFooter',
			[
				'nonce'                    => wp_create_nonce( 'catapulte_autoplugin_nonce' ),
				'models'                   => [
					'default'  => get_option( 'catapulte_autoplugin_model' ),
					'planner'  => $api_handler->get_planner_model(),
					'coder'    => $api_handler->get_coder_model(),
					'reviewer' => $api_handler->get_reviewer_model(),
				],
				'default_step'             => $default_step,
				'no_token_data'            => esc_html__( 'No token usage data available yet.', 'catapulte-autoplugin' ),
				'total_usage'              => esc_html__( 'Total Usage', 'catapulte-autoplugin' ),
				'step_breakdown'           => esc_html__( 'Step Breakdown', 'catapulte-autoplugin' ),
				'error_saving_models'      => esc_html__( 'Failed to save models.', 'catapulte-autoplugin' ),
				'error_saving_models_ajax' => esc_html__( 'An error occurred while saving models.', 'catapulte-autoplugin' ),
			]
		);
	}

	/**
	 * Add inline CSS to fix the menu icon in the admin.
	 *
	 * @return void
	 */
	public function admin_css() {
		?>
		<style>
			li.toplevel_page_catapulte-autoplugin .wp-menu-image::after {
				content: "";
				display: block;
				width: 20px;
				height: 20px;
				border: 2px solid;
				border-radius: 100px;
				position: absolute;
				top: 5px;
				left: 6px;
			}
			li.toplevel_page_catapulte-autoplugin:not(.wp-menu-open) a:not(:hover) .wp-menu-image::after {
				color: #a7aaad;
				color: rgba(240, 246, 252, 0.6);
			}
		</style>
		<?php
	}
}
