<?php
/**
 * Admin view for the Settings page.
 *
 * @package Catapulte-Autoplugin
 * @since 1.0.0
 * @version 1.0.5
 * @link https://catapulte-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Catapulte_Autoplugin;

use Catapulte_Autoplugin\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Catapulte-Autoplugin Settings', 'catapulte-autoplugin' ); ?></h1>
	<?php settings_errors(); ?>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'catapulte_autoplugin_settings' );
		do_settings_sections( 'catapulte_autoplugin_settings' );
		?>
		<?php
		/**
		 * Mask an API key for display - shows only last 4 characters.
		 *
		 * @param string $key The API key to mask.
		 * @return string The masked key or empty string.
		 */
		function catapulte_autoplugin_mask_api_key( string $key ): string {
			if ( empty( $key ) ) {
				return '';
			}
			return str_repeat( '*', 8 ) . substr( $key, -4 );
		}
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'OpenAI API Key', 'catapulte-autoplugin' ); ?></th>
				<td>
					<input type="password" name="catapulte_autoplugin_openai_api_key" value="" class="large-text" autocomplete="new-password" />
					<?php
					$openai_key = get_option( 'catapulte_autoplugin_openai_api_key' );
					if ( $openai_key ) :
					?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Current: %s', 'catapulte-autoplugin' ), catapulte_autoplugin_mask_api_key( $openai_key ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Anthropic API Key', 'catapulte-autoplugin' ); ?></th>
				<td>
					<input type="password" name="catapulte_autoplugin_anthropic_api_key" value="" class="large-text" autocomplete="new-password" />
					<?php
					$anthropic_key = get_option( 'catapulte_autoplugin_anthropic_api_key' );
					if ( $anthropic_key ) :
					?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Current: %s', 'catapulte-autoplugin' ), catapulte_autoplugin_mask_api_key( $anthropic_key ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Google Gemini API Key', 'catapulte-autoplugin' ); ?></th>
				<td>
					<input type="password" name="catapulte_autoplugin_google_api_key" value="" class="large-text" autocomplete="new-password" />
					<?php
					$google_key = get_option( 'catapulte_autoplugin_google_api_key' );
					if ( $google_key ) :
					?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Current: %s', 'catapulte-autoplugin' ), catapulte_autoplugin_mask_api_key( $google_key ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'xAI API Key', 'catapulte-autoplugin' ); ?></th>
				<td>
					<input type="password" name="catapulte_autoplugin_xai_api_key" value="" class="large-text" autocomplete="new-password" />
					<?php
					$xai_key = get_option( 'catapulte_autoplugin_xai_api_key' );
					if ( $xai_key ) :
					?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Current: %s', 'catapulte-autoplugin' ), catapulte_autoplugin_mask_api_key( $xai_key ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'OpenRouter API Key', 'catapulte-autoplugin' ); ?></th>
				<td>
					<input type="password" name="catapulte_autoplugin_openrouter_api_key" value="" class="large-text" autocomplete="new-password" />
					<?php
					$openrouter_key = get_option( 'catapulte_autoplugin_openrouter_api_key' );
					if ( $openrouter_key ) :
					?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Current: %s', 'catapulte-autoplugin' ), catapulte_autoplugin_mask_api_key( $openrouter_key ) ) ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
<?php
function render_model_dropdown( $name, $selected_value ) {
	$models        = Admin::get_models();
	$custom_models = get_option( 'catapulte_autoplugin_custom_models', [] );

	echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
	echo '<option value="" ' . selected( $selected_value, '', false ) . '>' . esc_html__( 'Use Default Model', 'catapulte-autoplugin' ) . '</option>';

	foreach ( $models as $provider => $model ) {
		echo '<optgroup label="' . esc_attr( $provider ) . '">';
		foreach ( $model as $key => $value ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_value, $key, false ) . '>' . esc_html( $value ) . '</option>';
		}
		echo '</optgroup>';
	}

	if ( ! empty( $custom_models ) ) {
		echo '<optgroup label="' . esc_attr__( 'Custom Models', 'catapulte-autoplugin' ) . '">';
		foreach ( $custom_models as $model ) {
			echo '<option value="' . esc_attr( $model['name'] ) . '" ' . selected( $selected_value, $model['name'], false ) . '>' . esc_html( $model['name'] ) . '</option>';
		}
		echo '</optgroup>';
	}

	echo '</select>';
}
?>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Default Model', 'catapulte-autoplugin' ); ?></th>
				<td>
					<select name="catapulte_autoplugin_model" id="catapulte_autoplugin_model">
						<?php
						$models = Admin::get_models();
						foreach ( $models as $provider => $model ) {
							echo '<optgroup label="' . esc_attr( $provider ) . '">';
							foreach ( $model as $key => $value ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( get_option( 'catapulte_autoplugin_model' ), $key ) . '>' . esc_html( $value ) . '</option>';
							}
							echo '</optgroup>';
						}
						?>
						<optgroup label="<?php esc_attr_e( 'Custom Models', 'catapulte-autoplugin' ); ?>" id="custom-models">
							<?php
							$custom_models = get_option( 'catapulte_autoplugin_custom_models', [] );
							foreach ( $custom_models as $model ) {
								echo '<option value="' . esc_attr( $model['name'] ) . '" ' . selected( get_option( 'catapulte_autoplugin_model' ), $model['name'] ) . '>' . esc_html( $model['name'] ) . '</option>';
							}
							?>
						</optgroup>
					</select>
					<p>
						<button type="button" id="toggle-specialized-models" class="button-link"><?php esc_html_e( 'Show specialized model settings', 'catapulte-autoplugin' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></button>
					</p>
				</td>
			</tr>
		</table>
		
		<?php
		// Check if any specialized model is set.
		$planner_model          = get_option( 'catapulte_autoplugin_planner_model' );
		$coder_model            = get_option( 'catapulte_autoplugin_coder_model' );
		$reviewer_model         = get_option( 'catapulte_autoplugin_reviewer_model' );
		$has_specialized_models = ! empty( $planner_model ) || ! empty( $coder_model ) || ! empty( $reviewer_model );
		?>
		<div class="catapulte-autoplugin-per-step-models" style="<?php echo $has_specialized_models ? '' : 'display: none;'; ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Planner Model', 'catapulte-autoplugin' ); ?></th>
					<td>
						<?php render_model_dropdown( 'catapulte_autoplugin_planner_model', get_option( 'catapulte_autoplugin_planner_model' ) ); ?>
						<p class="description"><?php esc_html_e( 'Used for planning plugin extensions and analyzing hooks. Falls back to Default Model if not set.', 'catapulte-autoplugin' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Coder Model', 'catapulte-autoplugin' ); ?></th>
					<td>
						<?php render_model_dropdown( 'catapulte_autoplugin_coder_model', get_option( 'catapulte_autoplugin_coder_model' ) ); ?>
						<p class="description"><?php esc_html_e( 'Used for generating and fixing code. Falls back to Default Model if not set.', 'catapulte-autoplugin' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Reviewer Model', 'catapulte-autoplugin' ); ?></th>
					<td>
						<?php render_model_dropdown( 'catapulte_autoplugin_reviewer_model', get_option( 'catapulte_autoplugin_reviewer_model' ) ); ?>
						<p class="description"><?php esc_html_e( 'Used for explaining code and reviewing generated plugins. Falls back to Default Model if not set.', 'catapulte-autoplugin' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Generate Plugins', 'catapulte-autoplugin' ); ?></th>
				<td>
					<select name="catapulte_autoplugin_plugin_mode" id="catapulte_autoplugin_plugin_mode">
						<?php $current_mode = get_option( 'catapulte_autoplugin_plugin_mode', 'simple' ); ?>
						<option value="simple" <?php selected( $current_mode, 'simple' ); ?>><?php esc_html_e( 'Simple plugin mode (single-file plugins)', 'catapulte-autoplugin' ); ?></option>
						<option value="complex" <?php selected( $current_mode, 'complex' ); ?>><?php esc_html_e( 'Complex plugin mode (multi-file plugins)', 'catapulte-autoplugin' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Complex mode uses more tokens. For best results, use capable models.', 'catapulte-autoplugin' ); ?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Custom Models', 'catapulte-autoplugin' ); ?></th>
				<td>
					<div id="custom-models-list">
						<!-- List will be populated via JS -->
						<div class="custom-models-items"></div>
					</div>

					<div id="add-custom-model-form">
						<input type="text" id="custom-model-name" placeholder="<?php esc_attr_e( 'Model Name (User-defined Label)', 'catapulte-autoplugin' ); ?>" class="large-text">
						<input type="url" id="custom-model-url" placeholder="<?php esc_attr_e( 'API Endpoint URL', 'catapulte-autoplugin' ); ?>" class="large-text">
						<input type="text" id="custom-model-parameter" placeholder="<?php esc_attr_e( '"model" Parameter Value', 'catapulte-autoplugin' ); ?>" class="large-text">
						<input type="password" id="custom-model-api-key" placeholder="<?php esc_attr_e( 'API Key', 'catapulte-autoplugin' ); ?>" class="large-text">
						<textarea id="custom-model-headers" placeholder="<?php esc_attr_e( 'Additional Headers (one per line, name=value)', 'catapulte-autoplugin' ); ?>" rows="4" class="large-text"></textarea>
						<button type="button" id="add-custom-model" class="button"><?php esc_html_e( 'Add Custom Model', 'catapulte-autoplugin' ); ?></button>
					</div>

					<input type="hidden" name="catapulte_autoplugin_custom_models" id="catapulte_autoplugin_custom_models" value="<?php echo esc_attr( wp_json_encode( get_option( 'catapulte_autoplugin_custom_models', [] ) ) ); ?>">
					<input type="hidden" id="catapulte_autoplugin_settings_nonce" value="<?php echo esc_attr( wp_create_nonce( 'catapulte_autoplugin_nonce' ) ); ?>">

					<p class="description"><?php esc_html_e( 'Add any custom models you want to use with Catapulte-Autoplugin. These models will be available in the model selection dropdown. The API must be compatible with the OpenAI API.', 'catapulte-autoplugin' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
<script>
	jQuery(document).ready(function($) {
		let customModels = JSON.parse($('#catapulte_autoplugin_custom_models').val() || '[]');
		const nonce = $('#catapulte_autoplugin_settings_nonce').val();
		
		// Toggle specialized models section
		$('#toggle-specialized-models').on('click', function() {
			const $section = $('.catapulte-autoplugin-per-step-models');
			const $button = $(this);
			const $icon = $button.find('.dashicons');
			
			if ($section.is(':visible')) {
				$section.hide();
				$button.html('<?php esc_html_e( 'Show specialized model settings', 'catapulte-autoplugin' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span>');
			} else {
				$section.show();
				$button.html('<?php esc_html_e( 'Hide specialized model settings', 'catapulte-autoplugin' ); ?> <span class="dashicons dashicons-arrow-up-alt2"></span>');
			}
		});
		
		// Update toggle button text based on initial visibility
		if ($('.catapulte-autoplugin-per-step-models').is(':visible')) {
			$('#toggle-specialized-models').html('<?php esc_html_e( 'Hide specialized model settings', 'catapulte-autoplugin' ); ?> <span class="dashicons dashicons-arrow-up-alt2"></span>');
		}
		
		// Later this may be moved to a wp_localize_script call
		const catapulte_autoplugin_i18n = {
			details: '<?php echo esc_js( __( 'Details', 'catapulte-autoplugin' ) ); ?>',
			url: '<?php echo esc_js( __( 'URL', 'catapulte-autoplugin' ) ); ?>',
			modelParameter: '<?php echo esc_js( __( 'Model Parameter', 'catapulte-autoplugin' ) ); ?>',
			apiKey: '<?php echo esc_js( __( 'API Key', 'catapulte-autoplugin' ) ); ?>',
			headers: '<?php echo esc_js( __( 'Headers', 'catapulte-autoplugin' ) ); ?>',
			remove: '<?php echo esc_js( __( 'Remove', 'catapulte-autoplugin' ) ); ?>',
			fillOutFields: '<?php echo esc_js( __( 'Please fill out all required fields.', 'catapulte-autoplugin' ) ); ?>',
			removeModel: '<?php echo esc_js( __( 'Are you sure you want to remove this model?', 'catapulte-autoplugin' ) ); ?>',
			errorSavingModel: '<?php echo esc_js( __( 'Error saving model', 'catapulte-autoplugin' ) ); ?>',
		};

		function updateCustomModelsList() {
			const selectedDefault = $('#catapulte_autoplugin_model').val();
			const selectedPlanner = $('#catapulte_autoplugin_planner_model').val();
			const selectedCoder = $('#catapulte_autoplugin_coder_model').val();
			const selectedReviewer = $('#catapulte_autoplugin_reviewer_model').val();
			
			const $list = $('.custom-models-items').empty();
			const $optgroup = $('#custom-models').empty();
			
			// Clear custom model options from all dropdowns
			$('#catapulte_autoplugin_planner_model optgroup[label="Custom Models"]').remove();
			$('#catapulte_autoplugin_coder_model optgroup[label="Custom Models"]').remove();
			$('#catapulte_autoplugin_reviewer_model optgroup[label="Custom Models"]').remove();
			
			customModels.forEach((model, index) => {
				const $item = $('<div class="custom-model-item">')
					.append(`<strong>${model.name}</strong>`)
					.append(`<details><summary>${catapulte_autoplugin_i18n.details}</summary><p><strong>${catapulte_autoplugin_i18n.url}:</strong> ${model.url}</p><p><strong>${catapulte_autoplugin_i18n.modelParameter}:</strong> ${model.modelParameter}</p><p><strong>${catapulte_autoplugin_i18n.apiKey}:</strong> ***${model.apiKey.substr(-3)}</p><p><strong>${catapulte_autoplugin_i18n.headers}:</strong> ${model.headers.join(', ')}</p></details>`)
					.append(`<button type="button" class="button remove-model" data-index="${index}">${catapulte_autoplugin_i18n.remove}</button>`);
				$list.append($item);

				$optgroup.append(`<option value="${model.name}">${model.name}</option>`);
			});
			
			// Add custom models to specialized dropdowns if any exist
			if (customModels.length > 0) {
				const customOptgroup = `<optgroup label="<?php esc_attr_e( 'Custom Models', 'catapulte-autoplugin' ); ?>">
					${customModels.map(model => `<option value="${model.name}">${model.name}</option>`).join('')}
				</optgroup>`;
				
				$('#catapulte_autoplugin_planner_model').append(customOptgroup);
				$('#catapulte_autoplugin_coder_model').append(customOptgroup);
				$('#catapulte_autoplugin_reviewer_model').append(customOptgroup);
			}
			
			$('#catapulte_autoplugin_custom_models').val(JSON.stringify(customModels));
			
			// Restore selected values
			$('#catapulte_autoplugin_model').val(selectedDefault);
			$('#catapulte_autoplugin_planner_model').val(selectedPlanner);
			$('#catapulte_autoplugin_coder_model').val(selectedCoder);
			$('#catapulte_autoplugin_reviewer_model').val(selectedReviewer);
		}

		$('#add-custom-model').on('click', function() {
			const name = $('#custom-model-name').val();
			const url = $('#custom-model-url').val();
			const modelParameter = $('#custom-model-parameter').val();
			const apiKey = $('#custom-model-api-key').val();
			const headers = $('#custom-model-headers').val();

			if (!name || !url || !apiKey) {
				alert(catapulte_autoplugin_i18n.fillOutFields);
				return;
			}

			const model = {
				name: name,
				url: url,
				modelParameter: modelParameter,
				apiKey: apiKey,
				headers: headers.split('\n').filter(h => h.trim())
			};

			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'catapulte_autoplugin_add_model',
					model: model,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						customModels = response.data.models;
						updateCustomModelsList();
						$('#custom-model-name, #custom-model-url, #custom-model-param, #custom-model-api-key, #custom-model-headers').val('');
					} else {
						alert(response.data.message || 'Error saving model');
					}
				}
			});
		});

		$(document).on('click', '.remove-model', function() {
			const index = $(this).data('index');
			if (confirm(catapulte_autoplugin_i18n.removeModel)) {
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'catapulte_autoplugin_remove_model',
						index: index,
						nonce: nonce
					},
					success: function(response) {
						if (response.success) {
							customModels = response.data.models;
							updateCustomModelsList();
						} else {
							alert(response.data.message || catapulte_autoplugin_i18n.errorSavingModel);
						}
					}
				});
			}
		});

		updateCustomModelsList();
	});
</script>
<style>
	/* Specialized Models Toggle */
	#toggle-specialized-models {
		margin-top: 8px;
		display: block;
		color: #2271b1;
		text-decoration: none;
		padding: 0;
	}
	
	#toggle-specialized-models:focus {
		box-shadow: none;
		outline: none;
	}
	
	#toggle-specialized-models .dashicons {
		font-size: 16px;
		line-height: 1.5;
		vertical-align: middle;
	}
	
	.catapulte-autoplugin-per-step-models h3 {
		margin-top: 0;
		padding-top: 0;
	}
	
	/* Custom Models Section Styling */
	#custom-models-list {
		margin-bottom: 20px;
	}

	.custom-model-item {
		background: #fff;
		border: 1px solid #ccd0d4;
		border-radius: 4px;
		padding: 15px 15px 5px;
		margin-bottom: 15px;
		position: relative;
	}

	.custom-model-item strong {
		font-size: 14px;
		color: #1d2327;
	}

	.custom-model-item details {
		margin: 10px 0;
	}

	.custom-model-item summary {
		cursor: pointer;
		color: #2271b1;
		padding: 5px 0;
	}

	.custom-model-item summary:hover {
		color: #135e96;
	}

	.custom-model-item p {
		margin: 8px 0;
		color: #50575e;
	}

	.custom-model-item .remove-model {
		position: absolute;
		right: 15px;
		top: 12px;
		color: #b32d2e;
		border-color: #b32d2e;
	}

	.custom-model-item .remove-model:hover {
		background: #b32d2e;
		color: #fff;
	}

	/* Add Custom Model Form */
	#add-custom-model-form {
		background: #f6f7f7;
		border: 1px solid #c3c4c7;
		border-radius: 4px;
		padding: 20px;
		margin-bottom: 15px;
	}

	#add-custom-model-form input,
	#add-custom-model-form textarea {
		margin-bottom: 15px;
	}

	#add-custom-model-form .button {
		margin-top: 5px;
	}

	/* Description Text */
	.description {
		color: #646970;
		font-style: italic;
		margin-top: 15px;
	}

	/* Input Focus States */
	#add-custom-model-form input:focus,
	#add-custom-model-form textarea:focus {
		border-color: #2271b1;
		box-shadow: 0 0 0 1px #2271b1;
		outline: 2px solid transparent;
	}

	/* make it a box */
	.catapulte-autoplugin-per-step-models {
		background: #fff;
		border: 1px solid #ccd0d4;
		border-radius: 4px;
		padding: 0 2rem;
		margin-bottom: 15px;
	}
</style>
