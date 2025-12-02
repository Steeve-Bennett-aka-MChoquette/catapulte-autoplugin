<?php

declare(strict_types=1);
/**
 * Catapulte-Autoplugin Menu class.
 *
 * @package Catapulte-Autoplugin
 */

namespace Catapulte_Autoplugin\Admin;

use Catapulte_Autoplugin\History;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles the admin menu.
 */
class Menu {

	/**
	 * The Admin instance.
	 *
	 * @var Admin
	 */
	protected $admin;

	/**
	 * Constructor.
	 */
	public function __construct( $admin ) {
		$this->admin = $admin;
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_history_actions' ] );
	}

	/**
	 * Initialize the admin menu pages.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'Catapulte-Autoplugin', 'catapulte-autoplugin' ),
			esc_html__( 'Catapulte-Autoplugin', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin',
			[ $this, 'render_list_plugins_page' ],
			'dashicons-admin-plugins',
			100
		);

		add_submenu_page(
			'catapulte-autoplugin',
			esc_html__( 'Generate New Plugin', 'catapulte-autoplugin' ),
			esc_html__( 'Generate New Plugin', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-generate',
			[ $this, 'render_generate_plugin_page' ]
		);

		add_submenu_page(
			'catapulte-autoplugin',
			esc_html__( 'History', 'catapulte-autoplugin' ),
			esc_html__( 'History', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-history',
			[ $this, 'render_history_page' ]
		);

		add_submenu_page(
			'catapulte-autoplugin',
			esc_html__( 'Settings', 'catapulte-autoplugin' ),
			esc_html__( 'Settings', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-settings',
			[ $this, 'render_settings_page' ]
		);

		// Extend and Fix pages (they don't appear in the menu).
		add_submenu_page(
			'options.php',
			esc_html__( 'Extend Plugin', 'catapulte-autoplugin' ),
			esc_html__( 'Extend Plugin', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-extend',
			[ $this, 'render_extend_plugin_page' ]
		);

		add_submenu_page(
			'options.php',
			esc_html__( 'Fix Plugin', 'catapulte-autoplugin' ),
			esc_html__( 'Fix Plugin', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-fix',
			[ $this, 'render_fix_plugin_page' ]
		);

		add_submenu_page(
			'options.php',
			esc_html__( 'Explain Plugin', 'catapulte-autoplugin' ),
			esc_html__( 'Explain Plugin', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-explain',
			[ $this, 'render_explain_plugin_page' ]
		);

		add_submenu_page(
			'options.php',
			esc_html__( 'Create Extension', 'catapulte-autoplugin' ),
			esc_html__( 'Create Extension', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-extend-hooks',
			[ $this, 'render_extend_hooks_page' ]
		);

		add_submenu_page(
			'options.php',
			esc_html__( 'Extend Theme', 'catapulte-autoplugin' ),
			esc_html__( 'Extend Theme', 'catapulte-autoplugin' ),
			'manage_options',
			'catapulte-autoplugin-extend-theme',
			[ $this, 'render_extend_theme_page' ]
		);
	}

	/**
	 * Display the list of Autoplugins.
	 *
	 * @return void
	 */
	public function render_list_plugins_page() {
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-list-plugins.php';
	}

	/**
	 * Display the plugin generation page.
	 *
	 * @return void
	 */
	public function render_generate_plugin_page() {
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-generate-plugin.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-settings.php';
	}

	/**
	 * Display the extend plugin page.
	 *
	 * @return void
	 */
	public function render_extend_plugin_page() {
		$this->validate_plugin( 'catapulte-autoplugin-extend-plugin' );
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-extend-plugin.php';
	}

	/**
	 * Display the fix plugin page.
	 *
	 * @return void
	 */
	public function render_fix_plugin_page() {
		$this->validate_plugin( 'catapulte-autoplugin-fix-plugin' );
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-fix-plugin.php';
	}

	/**
	 * Display the explain plugin page.
	 *
	 * @return void
	 */
	public function render_explain_plugin_page() {
		$this->validate_plugin( 'catapulte-autoplugin-explain-plugin' );
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-explain-plugin.php';
	}

	/**
	 * Display the extend plugin with hooks page.
	 *
	 * @return void
	 */
	public function render_extend_hooks_page() {
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'catapulte-autoplugin' ) );
		}

		// Required params and nonce.
		if ( ! isset( $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( esc_html__( 'No plugin specified.', 'catapulte-autoplugin' ) );
		}
		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, 'catapulte-autoplugin-extend-hooks' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
		}

		// Sanitize and constrain plugin path inside plugins directory.
		$plugin_file  = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$plugin_file  = ltrim( str_replace( [ '..\\', '../', '\\' ], '/', $plugin_file ), '/' );
		$plugin_path  = wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_file );
		$plugins_base = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) );
		if ( strpos( $plugin_path, $plugins_base ) !== 0 || ! file_exists( $plugin_path ) ) {
			wp_die( esc_html__( 'The specified plugin does not exist.', 'catapulte-autoplugin' ) );
		}

		$plugin_data = get_plugin_data( $plugin_path );
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-extend-hooks.php';
	}

	/**
	 * Display the extend theme page.
	 *
	 * @return void
	 */
	public function render_extend_theme_page() {
		$this->validate_theme( 'catapulte-autoplugin-extend-theme' );
		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-extend-theme.php';
	}

	/**
	 * Validate plugin access and existence.
	 *
	 * @param string $nonce_action Nonce action name.
	 * @return void
	 */
	protected function validate_plugin( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'catapulte-autoplugin' ) );
		}

		if ( ! isset( $_GET['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( esc_html__( 'No plugin specified.', 'catapulte-autoplugin' ) );
		}
		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
		}

		$plugin_file  = sanitize_text_field( wp_unslash( $_GET['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$plugin_file  = ltrim( str_replace( [ '..\\', '../', '\\' ], '/', $plugin_file ), '/' );
		$plugin_path  = wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_file );
		$plugins_base = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) );
		if ( strpos( $plugin_path, $plugins_base ) !== 0 || ! file_exists( $plugin_path ) ) {
			wp_die( esc_html__( 'The specified plugin does not exist.', 'catapulte-autoplugin' ) );
		}
	}

	/**
	 * Validate theme access and existence.
	 *
	 * @param string $nonce_action Nonce action name.
	 * @return void
	 */
	protected function validate_theme( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'catapulte-autoplugin' ) );
		}

		if ( ! isset( $_GET['theme'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( esc_html__( 'No theme specified.', 'catapulte-autoplugin' ) );
		}
		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
		}

		$theme_slug = sanitize_text_field( wp_unslash( $_GET['theme'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$theme      = wp_get_theme( $theme_slug );
		if ( ! $theme->exists() ) {
			wp_die( esc_html__( 'The specified theme does not exist.', 'catapulte-autoplugin' ) );
		}
	}

	/**
	 * Display the history page.
	 *
	 * @return void
	 */
	public function render_history_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'catapulte-autoplugin' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'view' === $action ) {
			$this->render_history_view_page();
			return;
		}

		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-history.php';
	}

	/**
	 * Display a single history entry.
	 *
	 * @return void
	 */
	protected function render_history_view_page() {
		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, 'catapulte-autoplugin-history-view' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid history entry ID.', 'catapulte-autoplugin' ) );
		}

		$history = History::get_instance();
		$entry   = $history->get_entry( $id );

		if ( ! $entry ) {
			wp_die( esc_html__( 'History entry not found.', 'catapulte-autoplugin' ) );
		}

		include CATAPULTE_AUTOPLUGIN_DIR . 'views/page-history-view.php';
	}

	/**
	 * Handle history page actions (delete).
	 *
	 * @return void
	 */
	public function handle_history_actions() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'catapulte-autoplugin-history' !== $page ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		if ( 'delete' !== $action ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'catapulte-autoplugin' ) );
		}

		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, 'catapulte-autoplugin-history-delete' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'catapulte-autoplugin' ) );
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid history entry ID.', 'catapulte-autoplugin' ) );
		}

		$history = History::get_instance();
		$deleted = $history->delete_entry( $id );

		if ( $deleted ) {
			Notices::add_notice( esc_html__( 'History entry deleted successfully.', 'catapulte-autoplugin' ), 'success' );
		} else {
			Notices::add_notice( esc_html__( 'Failed to delete history entry.', 'catapulte-autoplugin' ), 'error' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=catapulte-autoplugin-history' ) );
		exit;
	}
}
