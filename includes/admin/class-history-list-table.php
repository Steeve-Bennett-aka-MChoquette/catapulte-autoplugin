<?php

declare(strict_types=1);

/**
 * Autoplugin History List Table.
 *
 * @package Catapulte-Autoplugin
 * @since 2.1.0
 */

namespace Catapulte_Autoplugin\Admin;

use Catapulte_Autoplugin\History;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * History List Table class.
 */
class History_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'history',
				'plural'   => 'histories',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Set the columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'           => '<input type="checkbox" />',
			'plugin_name'  => __( 'Plugin Name', 'catapulte-autoplugin' ),
			'description'  => __( 'Description', 'catapulte-autoplugin' ),
			'plugin_mode'  => __( 'Mode', 'catapulte-autoplugin' ),
			'created_at'   => __( 'Created', 'catapulte-autoplugin' ),
		];
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	public function get_sortable_columns(): array {
		return [
			'plugin_name' => [ 'plugin_name', false ],
			'created_at'  => [ 'created_at', true ],
		];
	}

	/**
	 * Default column renderer.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The current column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return '';
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="history_ids[]" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * Render the plugin name column.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_plugin_name( $item ): string {
		$view_url = wp_nonce_url(
			admin_url( 'admin.php?page=catapulte-autoplugin-history&action=view&id=' . $item->id ),
			'catapulte-autoplugin-history-view',
			'nonce'
		);

		$delete_url = wp_nonce_url(
			admin_url( 'admin.php?page=catapulte-autoplugin-history&action=delete&id=' . $item->id ),
			'catapulte-autoplugin-history-delete',
			'nonce'
		);

		$actions = [
			'view'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $view_url ),
				esc_html__( 'View', 'catapulte-autoplugin' )
			),
			'delete' => sprintf(
				'<a href="%s" class="delete-history" data-id="%d">%s</a>',
				esc_url( $delete_url ),
				absint( $item->id ),
				esc_html__( 'Delete', 'catapulte-autoplugin' )
			),
		];

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $view_url ),
			esc_html( $item->plugin_name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render the description column.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_description( $item ): string {
		$description = $item->plugin_description;
		if ( strlen( $description ) > 150 ) {
			$description = substr( $description, 0, 150 ) . '...';
		}
		return esc_html( $description );
	}

	/**
	 * Render the plugin mode column.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_plugin_mode( $item ): string {
		$mode = $item->plugin_mode === 'complex' ? __( 'Complex', 'catapulte-autoplugin' ) : __( 'Simple', 'catapulte-autoplugin' );
		return sprintf(
			'<span class="history-mode history-mode-%s">%s</span>',
			esc_attr( $item->plugin_mode ),
			esc_html( $mode )
		);
	}

	/**
	 * Render the created at column.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_created_at( $item ): string {
		$date = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->created_at );
		$human_diff = human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) );
		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( $date ),
			/* translators: %s: human-readable time difference */
			sprintf( esc_html__( '%s ago', 'catapulte-autoplugin' ), $human_diff )
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Delete', 'catapulte-autoplugin' ),
		];
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action(): void {
		if ( 'delete' === $this->current_action() ) {
			$ids = isset( $_REQUEST['history_ids'] ) ? array_map( 'absint', (array) $_REQUEST['history_ids'] ) : [];

			if ( ! empty( $ids ) ) {
				check_admin_referer( 'bulk-histories' );

				$history = History::get_instance();
				$deleted = $history->delete_entries( $ids );

				if ( $deleted > 0 ) {
					Notices::add_notice(
						/* translators: %d: number of deleted entries */
						sprintf( _n( '%d history entry deleted.', '%d history entries deleted.', $deleted, 'catapulte-autoplugin' ), $deleted ),
						'success'
					);
				}
			}
		}
	}

	/**
	 * Prepare the list items.
	 */
	public function prepare_items(): void {
		$this->process_bulk_action();

		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$history = History::get_instance();

		if ( ! empty( $search ) ) {
			$this->items = $history->search_entries(
				$search,
				[
					'orderby' => $orderby,
					'order'   => $order,
					'limit'   => $per_page,
					'offset'  => $offset,
				]
			);
			$total_items = count( $history->search_entries( $search, [ 'limit' => 9999 ] ) );
		} else {
			$this->items = $history->get_entries(
				[
					'orderby' => $orderby,
					'order'   => $order,
					'limit'   => $per_page,
					'offset'  => $offset,
				]
			);
			$total_items = $history->get_total_count();
		}

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Empty table message.
	 */
	public function no_items(): void {
		esc_html_e( 'No history entries found.', 'catapulte-autoplugin' );
	}
}
