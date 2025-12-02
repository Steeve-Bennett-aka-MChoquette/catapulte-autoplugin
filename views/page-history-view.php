<?php
/**
 * Admin view for viewing a single history entry.
 *
 * @package Catapulte-Autoplugin
 * @since 2.1.0
 */

namespace Catapulte_Autoplugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Entry is passed from the Menu class.
$entry = $entry ?? null;

if ( ! $entry ) {
	wp_die( esc_html__( 'History entry not found.', 'catapulte-autoplugin' ) );
}

$back_url = admin_url( 'admin.php?page=catapulte-autoplugin-history' );
$delete_url = wp_nonce_url(
	admin_url( 'admin.php?page=catapulte-autoplugin-history&action=delete&id=' . $entry->id ),
	'catapulte-autoplugin-history-delete',
	'nonce'
);

$plan = $entry->plugin_plan;

?>
<div class="wrap">
	<h1>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action" style="margin-right: 10px;">&larr; <?php esc_html_e( 'Back to History', 'catapulte-autoplugin' ); ?></a>
		<?php echo esc_html( $entry->plugin_name ); ?>
	</h1>

	<div class="catapulte-autoplugin-history-view">
		<div class="history-meta">
			<span class="history-mode history-mode-<?php echo esc_attr( $entry->plugin_mode ); ?>">
				<?php echo $entry->plugin_mode === 'complex' ? esc_html__( 'Complex', 'catapulte-autoplugin' ) : esc_html__( 'Simple', 'catapulte-autoplugin' ); ?>
			</span>
			<span class="history-date">
				<?php
				printf(
					/* translators: %s: date */
					esc_html__( 'Created: %s', 'catapulte-autoplugin' ),
					esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry->created_at ) )
				);
				?>
			</span>
			<span class="history-slug">
				<?php
				printf(
					/* translators: %s: plugin slug */
					esc_html__( 'Slug: %s', 'catapulte-autoplugin' ),
					'<code>' . esc_html( $entry->plugin_slug ) . '</code>'
				);
				?>
			</span>
		</div>

		<div class="history-section">
			<h2><?php esc_html_e( 'Original Request', 'catapulte-autoplugin' ); ?></h2>
			<div class="history-description">
				<?php echo nl2br( esc_html( $entry->plugin_description ) ); ?>
			</div>
		</div>

		<?php if ( is_array( $plan ) ) : ?>
			<?php if ( isset( $plan['design_and_architecture'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'Design & Architecture', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php echo nl2br( esc_html( $plan['design_and_architecture'] ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( isset( $plan['detailed_feature_description'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'Feature Description', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php echo nl2br( esc_html( $plan['detailed_feature_description'] ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( isset( $plan['user_interface'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'User Interface', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php echo nl2br( esc_html( $plan['user_interface'] ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( isset( $plan['security_considerations'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'Security Considerations', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php echo nl2br( esc_html( $plan['security_considerations'] ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( isset( $plan['testing_plan'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'Testing Plan', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php echo nl2br( esc_html( $plan['testing_plan'] ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( isset( $plan['project_structure'] ) && is_array( $plan['project_structure'] ) ) : ?>
				<div class="history-section">
					<h2><?php esc_html_e( 'Project Structure', 'catapulte-autoplugin' ); ?></h2>
					<div class="history-content">
						<?php if ( isset( $plan['project_structure']['directories'] ) ) : ?>
							<h4><?php esc_html_e( 'Directories', 'catapulte-autoplugin' ); ?></h4>
							<ul>
								<?php foreach ( $plan['project_structure']['directories'] as $dir ) : ?>
									<li><code><?php echo esc_html( $dir ); ?></code></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php if ( isset( $plan['project_structure']['files'] ) ) : ?>
							<h4><?php esc_html_e( 'Files', 'catapulte-autoplugin' ); ?></h4>
							<ul>
								<?php foreach ( $plan['project_structure']['files'] as $file ) : ?>
									<li>
										<code><?php echo esc_html( $file['path'] ); ?></code>
										<?php if ( isset( $file['description'] ) ) : ?>
											- <?php echo esc_html( $file['description'] ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="history-actions">
			<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this history entry?', 'catapulte-autoplugin' ) ); ?>');">
				<?php esc_html_e( 'Delete Entry', 'catapulte-autoplugin' ); ?>
			</a>
		</div>
	</div>
</div>

<style>
.catapulte-autoplugin-history-view {
	max-width: 900px;
	background: #fff;
	padding: 20px;
	margin-top: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.history-meta {
	display: flex;
	gap: 20px;
	flex-wrap: wrap;
	padding-bottom: 15px;
	margin-bottom: 20px;
	border-bottom: 1px solid #eee;
	font-size: 13px;
	color: #646970;
}

.history-mode {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 500;
}

.history-mode-simple {
	background: #e7f5e7;
	color: #1e7b1e;
}

.history-mode-complex {
	background: #e7f0f5;
	color: #1e5f7b;
}

.history-section {
	margin-bottom: 25px;
}

.history-section h2 {
	font-size: 16px;
	margin: 0 0 10px 0;
	padding-bottom: 8px;
	border-bottom: 1px solid #eee;
}

.history-description {
	background: #f6f7f7;
	padding: 15px;
	border-radius: 4px;
	font-style: italic;
}

.history-content {
	line-height: 1.6;
}

.history-content ul {
	margin-left: 20px;
}

.history-content code {
	background: #f0f0f0;
	padding: 2px 6px;
	border-radius: 3px;
}

.history-actions {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #eee;
}
</style>
