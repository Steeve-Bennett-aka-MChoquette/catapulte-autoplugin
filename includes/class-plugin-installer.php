<?php

declare(strict_types=1);

/**
 * Autoplugin Installer class.
 *
 * @package WP-Autoplugin
 * @since 1.0.0
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
 * Plugin Installer class.
 */
class Plugin_Installer {

	/**
	 * Singleton instance.
	 */
	private static ?self $instance = null;

	/**
	 * Allowed file extensions for plugin files.
	 *
	 * @var array<int, string>
	 */
	private const ALLOWED_EXTENSIONS = [ 'php', 'css', 'js' ];

	/**
	 * Default directory permissions.
	 */
	private const DIR_PERMISSIONS = 0755;

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Install a plugin from code.
	 *
	 * @param string $code        The plugin code.
	 * @param string $plugin_name The plugin name.
	 */
	public function install_plugin( string $code, string $plugin_name ): string|\WP_Error {
		if ( $this->is_file_mods_disabled() ) {
			return new \WP_Error( 'file_mods_disabled', __( 'Plugin installation is disabled.', 'wp-autoplugin' ) );
		}

		$wp_filesystem = $this->get_filesystem();

		$plugin_file = '';
		if ( str_contains( $plugin_name, '/' ) && str_ends_with( $plugin_name, '.php' ) ) {
			// Treat as update to an existing plugin file path relative to WP_PLUGIN_DIR.
			$clean_rel = wp_normalize_path( $plugin_name );
			if ( str_contains( $clean_rel, '../' ) ) {
				return new \WP_Error( 'invalid_path', __( 'Plugin path cannot contain "../".', 'wp-autoplugin' ) );
			}
			$plugin_file = WP_PLUGIN_DIR . '/' . $clean_rel;
			if ( ! $wp_filesystem->exists( $plugin_file ) ) {
				return new \WP_Error( 'file_not_found', __( 'Error updating plugin file: file does not exist.', 'wp-autoplugin' ) );
			}
		} else {
			$plugin_name = sanitize_title( $plugin_name, 'wp-autoplugin-' . md5( $code ) );
			$plugin_dir  = WP_PLUGIN_DIR . '/' . $plugin_name . '/';
			if ( ! $wp_filesystem->exists( $plugin_dir ) ) {
				$wp_filesystem->mkdir( $plugin_dir, self::DIR_PERMISSIONS, true );
			}
			$plugin_file = $plugin_dir . 'index.php';
		}

		$result = $wp_filesystem->put_contents( $plugin_file, $code, FS_CHMOD_FILE );
		if ( $result === false ) {
			return new \WP_Error( 'file_creation_error', __( 'Error creating plugin file.', 'wp-autoplugin' ) );
		}

		// Add the plugin to the list of autoplugins.
		$this->add_to_autoplugins( $plugin_name . '/index.php' );

		return $plugin_name . '/index.php';
	}

	/**
	 * Remove common directory prefix from file paths.
	 *
	 * @param array<string, mixed>          $project_structure The project structure.
	 * @param array<string, string>         $generated_files   The generated files array.
	 * @return array{0: array<string, mixed>, 1: array<string, string>}
	 */
	private function normalize_file_paths( array $project_structure, array $generated_files ): array {
		if ( ! isset( $project_structure['files'] ) || empty( $project_structure['files'] ) ) {
			return [ $project_structure, $generated_files ];
		}

		$file_paths = array_column( $project_structure['files'], 'path' );

		// Find common prefix.
		$common_prefix = '';
		if ( count( $file_paths ) > 1 ) {
			$first_path   = $file_paths[0];
			$prefix_parts = explode( '/', $first_path );

			// Check if first part is common to all paths.
			if ( str_contains( $first_path, '/' ) ) {
				$potential_prefix = $prefix_parts[0] . '/';
				$all_have_prefix  = true;

				foreach ( $file_paths as $path ) {
					if ( ! str_starts_with( $path, $potential_prefix ) ) {
						$all_have_prefix = false;
						break;
					}
				}

				if ( $all_have_prefix ) {
					$common_prefix = $potential_prefix;
				}
			}
		}

		// Remove common prefix if found.
		if ( $common_prefix !== '' ) {
			$normalized_generated_files = [];

			foreach ( $project_structure['files'] as &$file_info ) {
				$old_path          = $file_info['path'];
				$new_path          = substr( $file_info['path'], strlen( $common_prefix ) );
				$file_info['path'] = $new_path;

				// Update generated_files keys.
				if ( isset( $generated_files[ $old_path ] ) ) {
					$normalized_generated_files[ $new_path ] = $generated_files[ $old_path ];
				}
			}

			$generated_files = $normalized_generated_files;

			// Also update directories if they exist.
			if ( isset( $project_structure['directories'] ) ) {
				foreach ( $project_structure['directories'] as &$directory ) {
					if ( str_starts_with( $directory, $common_prefix ) ) {
						$directory = substr( $directory, strlen( $common_prefix ) );
					}
				}
				// Remove empty directories after prefix removal.
				$project_structure['directories'] = array_filter( $project_structure['directories'] );
			}
		}

		return [ $project_structure, $generated_files ];
	}

	/**
	 * Install a complex multi-file plugin.
	 *
	 * @param string               $plugin_name       The plugin name.
	 * @param array<string, mixed> $project_structure The project structure.
	 * @param array<string, string> $generated_files  The generated files.
	 */
	public function install_complex_plugin(
		string $plugin_name,
		array $project_structure,
		array $generated_files
	): string|\WP_Error {
		if ( $this->is_file_mods_disabled() ) {
			return new \WP_Error( 'file_mods_disabled', __( 'Plugin installation is disabled.', 'wp-autoplugin' ) );
		}

		$wp_filesystem = $this->get_filesystem();

		// Normalize file paths to remove common directory prefix.
		[ $project_structure, $generated_files ] = $this->normalize_file_paths( $project_structure, $generated_files );

		$plugin_name = sanitize_title( $plugin_name, 'wp-autoplugin-' . md5( wp_json_encode( $generated_files ) ) );
		$plugin_dir  = wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_name . '/' );

		// Create plugin directory if it doesn't exist.
		if ( ! $wp_filesystem->exists( $plugin_dir ) ) {
			$wp_filesystem->mkdir( $plugin_dir, self::DIR_PERMISSIONS, true );
		}

		// Create subdirectories.
		$result = $this->create_subdirectories( $wp_filesystem, $plugin_dir, $project_structure );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Write all files.
		$main_file = $this->write_plugin_files( $wp_filesystem, $plugin_dir, $project_structure, $generated_files );
		if ( is_wp_error( $main_file ) ) {
			return $main_file;
		}

		if ( $main_file === '' ) {
			return new \WP_Error( 'no_main_file', __( 'No main plugin file found.', 'wp-autoplugin' ) );
		}

		// Add the plugin to the list of autoplugins.
		$this->add_to_autoplugins( $plugin_name . '/' . $main_file );

		return $plugin_name . '/' . $main_file;
	}

	/**
	 * Create subdirectories for a plugin.
	 *
	 * @param \WP_Filesystem_Base  $wp_filesystem
	 * @param string               $plugin_dir
	 * @param array<string, mixed> $project_structure
	 */
	private function create_subdirectories(
		\WP_Filesystem_Base $wp_filesystem,
		string $plugin_dir,
		array $project_structure
	): true|\WP_Error {
		if ( ! isset( $project_structure['directories'] ) ) {
			return true;
		}

		foreach ( $project_structure['directories'] as $directory ) {
			$directory = wp_normalize_path( $directory );
			if ( str_contains( $directory, '../' ) ) {
				return new \WP_Error( 'invalid_path', __( 'Invalid directory path.', 'wp-autoplugin' ) );
			}
			$dir_path = $plugin_dir . $directory;
			if ( ! $wp_filesystem->exists( $dir_path ) ) {
				$wp_filesystem->mkdir( $dir_path, self::DIR_PERMISSIONS, true );
			}
		}

		return true;
	}

	/**
	 * Write plugin files.
	 *
	 * @param \WP_Filesystem_Base  $wp_filesystem
	 * @param string               $plugin_dir
	 * @param array<string, mixed> $project_structure
	 * @param array<string, string> $generated_files
	 */
	private function write_plugin_files(
		\WP_Filesystem_Base $wp_filesystem,
		string $plugin_dir,
		array $project_structure,
		array $generated_files
	): string|\WP_Error {
		$main_file = '';

		if ( ! isset( $project_structure['files'] ) ) {
			return $main_file;
		}

		foreach ( $project_structure['files'] as $file_info ) {
			$file_path = wp_normalize_path( $file_info['path'] );
			if ( str_contains( $file_path, '../' ) ) {
				return new \WP_Error( 'invalid_path', __( 'Invalid file path.', 'wp-autoplugin' ) );
			}
			$target_path  = $plugin_dir . $file_path;
			$file_content = $generated_files[ $file_info['path'] ] ?? '';

			if ( $file_content === '' ) {
				return new \WP_Error(
					'missing_file_content',
					// Translators: %s: file path.
					sprintf( __( 'Missing content for file: %s', 'wp-autoplugin' ), $file_info['path'] )
				);
			}

			// Create directory for the file if it doesn't exist.
			$file_dir = dirname( $target_path );
			if ( ! $wp_filesystem->exists( $file_dir ) ) {
				wp_mkdir_p( $file_dir );
			}

			$result = $wp_filesystem->put_contents( $target_path, $file_content, FS_CHMOD_FILE );
			if ( $result === false ) {
				return new \WP_Error(
					'file_creation_error',
					// Translators: %s: file path.
					sprintf( __( 'Error creating file: %s', 'wp-autoplugin' ), $file_info['path'] )
				);
			}

			// Identify the main plugin file (should be in root and end with .php).
			if ( ! str_contains( $file_info['path'], '/' ) && ( $file_info['type'] ?? '' ) === 'php' ) {
				$main_file = $file_info['path'];
			}
		}

		// Fallback to find the first php file in the root directory.
		if ( $main_file === '' ) {
			foreach ( $project_structure['files'] as $file_info ) {
				if ( ! str_contains( $file_info['path'], '/' ) && ( $file_info['type'] ?? '' ) === 'php' ) {
					$main_file = $file_info['path'];
					break;
				}
			}
		}

		return $main_file;
	}

	/**
	 * Update an existing plugin (directory) with multiple files.
	 *
	 * @param string               $plugin_file Main plugin file relative path (e.g., slug/slug.php).
	 * @param array<string, string> $files_map  Map of relative file paths => full contents.
	 */
	public function update_existing_plugin_files( string $plugin_file, array $files_map ): string|\WP_Error {
		if ( $this->is_file_mods_disabled() ) {
			return new \WP_Error( 'file_mods_disabled', __( 'Plugin modification is disabled.', 'wp-autoplugin' ) );
		}

		$wp_filesystem = $this->get_filesystem();

		// Sanitize and constrain plugin root inside plugins directory.
		$plugin_file = wp_normalize_path( $plugin_file );
		if ( str_contains( $plugin_file, '../' ) ) {
			return new \WP_Error( 'invalid_path', __( 'Plugin path cannot contain "../".', 'wp-autoplugin' ) );
		}
		$plugin_root_rel = dirname( $plugin_file );
		$plugin_root_abs = wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_root_rel . '/' );

		if ( ! $wp_filesystem->is_dir( $plugin_root_abs ) ) {
			return new \WP_Error( 'invalid_plugin_dir', __( 'Target plugin directory does not exist.', 'wp-autoplugin' ) );
		}

		foreach ( $files_map as $rel_path => $contents ) {
			$rel_path = wp_normalize_path( $rel_path );
			if ( str_contains( $rel_path, '../' ) ) {
				return new \WP_Error( 'invalid_path', __( 'Invalid file path.', 'wp-autoplugin' ) );
			}
			// Ensure path stays inside plugin directory.
			if ( str_starts_with( $rel_path, $plugin_root_rel . '/' ) ) {
				$rel_path = substr( $rel_path, strlen( $plugin_root_rel . '/' ) );
			}
			$target_path = $plugin_root_abs . $rel_path;

			$ext = strtolower( pathinfo( $target_path, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) ) {
				return new \WP_Error(
					'invalid_file_type',
					// Translators: %s: file path.
					sprintf( __( 'Unsupported file type for update: %s', 'wp-autoplugin' ), $rel_path )
				);
			}

			// Ensure directory exists.
			$dir = dirname( $target_path );
			if ( ! $wp_filesystem->exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			$result = $wp_filesystem->put_contents( $target_path, (string) $contents, FS_CHMOD_FILE );
			if ( $result === false ) {
				return new \WP_Error(
					'file_write_error',
					// Translators: %s: file path.
					sprintf( __( 'Failed to write file: %s', 'wp-autoplugin' ), $rel_path )
				);
			}
		}

		return $plugin_file;
	}

	/**
	 * Try to activate a plugin and catch fatal errors.
	 */
	public function activate_plugin( string $plugin ): never {
		$autoplugins = get_option( 'wp_autoplugins', [] );
		if ( ! is_array( $autoplugins ) || ! in_array( $plugin, $autoplugins, true ) ) {
			wp_send_json_error( esc_html__( 'Plugin not found.', 'wp-autoplugin' ) );
		}

		// Hide PHP errors without silencing.
		ini_set( 'display_startup_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
		error_reporting( 0 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

		ob_start();

		register_shutdown_function(
			static function () use ( $plugin ): void {
				$error = error_get_last();
				if ( $error !== null && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
					update_option(
						'wp_autoplugin_fatal_error',
						[
							'plugin' => $plugin,
							'error'  => $error['message'],
						]
					);
					echo '<meta http-equiv="refresh" content="0;url=' . esc_url( admin_url( 'admin.php?page=wp-autoplugin' ) ) . '">';
					exit;
				}
			}
		);

		try {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			set_error_handler(
				static function ( int $errno, string $errstr, string $errfile, int $errline ): bool {
					throw new \ErrorException( esc_html( $errstr ), 0, $errno, $errfile, $errline );
				}
			);

			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			activate_plugin( $plugin, admin_url( 'admin.php?page=wp-autoplugin&plugin=' . rawurlencode( $plugin ) ) );

			ob_end_clean();
			restore_error_handler();

		} catch ( \ErrorException $e ) {
			ob_end_clean();
			restore_error_handler();
			update_option( 'wp_autoplugin_fatal_error', $e->getMessage() );
			wp_safe_redirect( esc_url( admin_url( 'admin.php?page=wp-autoplugin' ) ) );
			exit;
		}

		ob_end_clean();
		restore_error_handler();

		Admin\Notices::add_notice( esc_html__( 'Plugin activated successfully.', 'wp-autoplugin' ), 'success' );
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=wp-autoplugin' ) ) );
		exit;
	}

	/**
	 * Deactivate a plugin and redirect to the autoplugins list page.
	 */
	public function deactivate_plugin( string $plugin ): never {
		$autoplugins = get_option( 'wp_autoplugins', [] );
		if ( ! is_array( $autoplugins ) || ! in_array( $plugin, $autoplugins, true ) ) {
			wp_send_json_error( esc_html__( 'Plugin not found.', 'wp-autoplugin' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $plugin );

		Admin\Notices::add_notice( esc_html__( 'Plugin deactivated successfully.', 'wp-autoplugin' ), 'success' );
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=wp-autoplugin' ) ) );
		exit;
	}

	/**
	 * Delete a plugin.
	 */
	public function delete_plugin( string $plugin ): never {
		$autoplugins = get_option( 'wp_autoplugins', [] );
		if ( ! is_array( $autoplugins ) || ! in_array( $plugin, $autoplugins, true ) ) {
			wp_send_json_error( esc_html__( 'Plugin not found.', 'wp-autoplugin' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $plugin );

		$deleted = delete_plugins( [ $plugin ] );
		if ( is_wp_error( $deleted ) ) {
			Admin\Notices::add_notice(
				// Translators: %s: error message.
				sprintf( esc_html__( 'Error deleting plugin: %s', 'wp-autoplugin' ), $deleted->get_error_message() ),
				'error'
			);
		} else {
			$autoplugins = array_diff( $autoplugins, [ $plugin ] );
			update_option( 'wp_autoplugins', $autoplugins );
			Admin\Notices::add_notice( esc_html__( 'Plugin deleted successfully.', 'wp-autoplugin' ), 'success' );
		}
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=wp-autoplugin' ) ) );
		exit;
	}

	/**
	 * Check if file modifications are disabled.
	 */
	private function is_file_mods_disabled(): bool {
		return defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS;
	}

	/**
	 * Get the WordPress filesystem instance.
	 */
	private function get_filesystem(): \WP_Filesystem_Base {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	/**
	 * Add a plugin to the autoplugins list.
	 */
	private function add_to_autoplugins( string $plugin_file ): void {
		$autoplugins   = get_option( 'wp_autoplugins', [] );
		$autoplugins   = is_array( $autoplugins ) ? $autoplugins : [];
		$autoplugins[] = $plugin_file;
		$autoplugins   = array_values( array_unique( $autoplugins ) );
		update_option( 'wp_autoplugins', $autoplugins );
	}
}
