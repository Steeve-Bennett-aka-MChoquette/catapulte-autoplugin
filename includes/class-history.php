<?php

declare(strict_types=1);

/**
 * Catapulte-Autoplugin History class.
 *
 * Manages the history of generated plugins.
 *
 * @package Catapulte-Autoplugin
 * @since 2.1.0
 */

namespace Catapulte_Autoplugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that manages plugin generation history.
 */
class History {

	/**
	 * Database table name (without prefix).
	 */
	private const TABLE_NAME = 'catapulte_autoplugin_history';

	/**
	 * Database version for migrations.
	 */
	private const DB_VERSION = '1.0';

	/**
	 * Option name for database version.
	 */
	private const DB_VERSION_OPTION = 'catapulte_autoplugin_history_db_version';

	/**
	 * Singleton instance.
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {
		$this->maybe_create_table();
	}

	/**
	 * Get the full table name with prefix.
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create the history table if it doesn't exist.
	 */
	private function maybe_create_table(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION );

		if ( $installed_version === self::DB_VERSION ) {
			return;
		}

		global $wpdb;
		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			plugin_name varchar(255) NOT NULL,
			plugin_slug varchar(255) NOT NULL,
			plugin_description text NOT NULL,
			plugin_plan longtext NOT NULL,
			plugin_mode varchar(20) NOT NULL DEFAULT 'simple',
			project_structure longtext DEFAULT NULL,
			generated_files longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY plugin_slug (plugin_slug),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Add a history entry.
	 *
	 * @param array{
	 *     plugin_name: string,
	 *     plugin_slug: string,
	 *     plugin_description: string,
	 *     plugin_plan: string,
	 *     plugin_mode?: string,
	 *     project_structure?: array<string, mixed>|null,
	 *     generated_files?: array<string, string>|null
	 * } $data The history entry data.
	 * @return int|false The inserted ID or false on failure.
	 */
	public function add_entry( array $data ): int|false {
		global $wpdb;

		$insert_data = [
			'plugin_name'        => sanitize_text_field( $data['plugin_name'] ?? '' ),
			'plugin_slug'        => sanitize_title( $data['plugin_slug'] ?? '' ),
			'plugin_description' => sanitize_textarea_field( $data['plugin_description'] ?? '' ),
			'plugin_plan'        => wp_json_encode( $data['plugin_plan'] ?? '' ),
			'plugin_mode'        => sanitize_text_field( $data['plugin_mode'] ?? 'simple' ),
			'project_structure'  => isset( $data['project_structure'] ) ? wp_json_encode( $data['project_structure'] ) : null,
			'generated_files'    => isset( $data['generated_files'] ) ? wp_json_encode( $data['generated_files'] ) : null,
		];

		$result = $wpdb->insert(
			$this->get_table_name(),
			$insert_data,
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( $result === false ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a history entry by ID.
	 *
	 * @param int $id The entry ID.
	 * @return object|null The entry or null if not found.
	 */
	public function get_entry( int $id ): ?object {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()} WHERE id = %d",
				$id
			)
		);

		if ( $result !== null ) {
			$result->plugin_plan       = json_decode( $result->plugin_plan, true );
			$result->project_structure = $result->project_structure ? json_decode( $result->project_structure, true ) : null;
			$result->generated_files   = $result->generated_files ? json_decode( $result->generated_files, true ) : null;
		}

		return $result;
	}

	/**
	 * Get all history entries.
	 *
	 * @param array{
	 *     orderby?: string,
	 *     order?: string,
	 *     limit?: int,
	 *     offset?: int
	 * } $args Query arguments.
	 * @return array<int, object> The history entries.
	 */
	public function get_entries( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		];

		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = [ 'id', 'plugin_name', 'plugin_slug', 'created_at', 'updated_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		foreach ( $results as $result ) {
			$result->plugin_plan       = json_decode( $result->plugin_plan, true );
			$result->project_structure = $result->project_structure ? json_decode( $result->project_structure, true ) : null;
			$result->generated_files   = $result->generated_files ? json_decode( $result->generated_files, true ) : null;
		}

		return $results;
	}

	/**
	 * Get total count of history entries.
	 *
	 * @return int The total count.
	 */
	public function get_total_count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->get_table_name()}" );
	}

	/**
	 * Delete a history entry.
	 *
	 * @param int $id The entry ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_entry( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->get_table_name(),
			[ 'id' => $id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Delete multiple history entries.
	 *
	 * @param array<int> $ids The entry IDs.
	 * @return int The number of deleted entries.
	 */
	public function delete_entries( array $ids ): int {
		global $wpdb;

		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query        = $wpdb->prepare(
			"DELETE FROM {$this->get_table_name()} WHERE id IN ({$placeholders})",
			...$ids
		);

		return (int) $wpdb->query( $query );
	}

	/**
	 * Clear all history entries.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_all(): bool {
		global $wpdb;

		$result = $wpdb->query( "TRUNCATE TABLE {$this->get_table_name()}" );

		return $result !== false;
	}

	/**
	 * Search history entries.
	 *
	 * @param string $search The search term.
	 * @param array{
	 *     orderby?: string,
	 *     order?: string,
	 *     limit?: int,
	 *     offset?: int
	 * } $args Query arguments.
	 * @return array<int, object> The matching entries.
	 */
	public function search_entries( string $search, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		];

		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = [ 'id', 'plugin_name', 'plugin_slug', 'created_at', 'updated_at' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()}
				WHERE plugin_name LIKE %s
				OR plugin_slug LIKE %s
				OR plugin_description LIKE %s
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				$search_term,
				$search_term,
				$search_term,
				$limit,
				$offset
			)
		);

		foreach ( $results as $result ) {
			$result->plugin_plan       = json_decode( $result->plugin_plan, true );
			$result->project_structure = $result->project_structure ? json_decode( $result->project_structure, true ) : null;
			$result->generated_files   = $result->generated_files ? json_decode( $result->generated_files, true ) : null;
		}

		return $results;
	}

	/**
	 * Drop the history table (for uninstall).
	 */
	public static function drop_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		delete_option( self::DB_VERSION_OPTION );
	}
}
