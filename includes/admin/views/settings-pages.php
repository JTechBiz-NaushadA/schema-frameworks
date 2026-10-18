<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pages = $config->get( 'pages', array() );
$pages = is_array( $pages ) ? $pages : array();

$modules = array();

foreach ( $registry->all() as $id => $definition ) {

	$options = isset( $definition['options'] ) && is_array( $definition['options'] )
		? $definition['options']
		: array();

	if ( empty( $options['admin_managed'] ) ) {
		continue;
	}

	$modules[ $id ] = array(
		'label'       => ! empty( $options['label'] ) ? $options['label'] : $id,
		'description' => ! empty( $options['description'] ) ? $options['description'] : '',
		'enabled'     => (bool) $config->get( 'modules.' . $id, false ),
		'has_config'  => ! empty( $options['config_fields'] ) && is_array( $options['config_fields'] ),
	);
}

ksort( $modules );

$managed_pages = array();
$custom_mappings = array();

foreach ( $pages as $page ) {
	if ( ! is_array( $page ) ) {
		continue;
	}

	if ( ! empty( $page['page_id'] ) ) {
		$managed_pages[] = $page;
	} elseif ( ! empty( $page['url'] ) || ! empty( $page['path'] ) ) {
		$custom_mappings[] = $page;
	}
}
?>

<div class="wrap schema-frameworks-admin-page schema-frameworks-pages-wrap">

	<h1>Page Schema Management</h1>

	<p>
		Select WordPress pages, choose which enabled Schema Frameworks modules
		apply to each page, and configure the schema data for modules that expose
		configuration fields.
	</p>

	<p class="description">
		Schema-specific configuration is stored inside the existing page
		configuration. The Schema Manager remains responsible for matching the
		current page and activating the selected modules.
	</p>

	<form method="post" action="options.php">

		<?php settings_fields( 'schema_frameworks_settings' ); ?>

		<div id="schema-frameworks-page-mappings">

			<?php foreach ( $managed_pages as $index => $page ) : ?>

				<?php
				$page_id = absint( $page['page_id'] );
				$selected_schemas = isset( $page['schemas'] ) && is_array( $page['schemas'] )
					? $page['schemas']
					: array();
				?>

				<div class="schema-frameworks-page-mapping">

					<div class="schema-frameworks-page-header">
						<div>
							<span class="schema-frameworks-section-kicker">Page configuration</span>
							<h2>Page</h2>
							<p class="description">Choose the WordPress page where these schema rules should apply.</p>
						</div>
						<div class="schema-frameworks-page-selector">
							<?php
							wp_dropdown_pages(
								array(
									'name'               => 'schema_frameworks_config[pages][' . $index . '][page_id]',
									'id'                 => 'schema-frameworks-page-' . $index,
									'echo'               => true,
									'show_option_none'   => 'Select a page',
									'option_none_value'  => '0',
									'selected'            => $page_id,
									'class'               => 'schema-frameworks-page-select',
								)
							);
							?>
						</div>
					</div>

					<section class="schema-frameworks-section">
						<div class="schema-frameworks-section-heading">
							<div>
								<span class="schema-frameworks-section-kicker">Step 1</span>
								<h2>Enabled Schemas</h2>
								<p class="description">Select the schema types that should be active on this page.</p>
							</div>
						</div>

						<?php if ( empty( $modules ) ) : ?>
							<div class="schema-frameworks-notice schema-frameworks-notice-neutral">
								No user-manageable schema modules are registered.
						</div>
						<?php else : ?>
							<div class="schema-frameworks-module-grid">
								<?php foreach ( $modules as $module_id => $module ) : ?>
									<?php $module_selected = in_array( $module_id, $selected_schemas, true ); ?>

									<div class="schema-frameworks-module-card<?php echo $module_selected ? ' is-selected' : ''; ?>" data-module-id="<?php echo esc_attr( $module_id ); ?>">
										<label class="schema-frameworks-module-card-label">
											<input
												type="checkbox"
												class="schema-frameworks-schema-toggle"
												data-config-target="schema-frameworks-config-<?php echo esc_attr( $index . '-' . $module_id ); ?>"
												name="schema_frameworks_config[pages][<?php echo esc_attr( $index ); ?>][schemas][]"
												value="<?php echo esc_attr( $module_id ); ?>"
												<?php checked( $module_selected ); ?>
											/>
											<span class="schema-frameworks-module-card-content">
												<strong><?php echo esc_html( $module['label'] ); ?></strong>
												<?php if ( ! $module['enabled'] ) : ?>
													<span class="schema-frameworks-module-status schema-frameworks-module-status-disabled">Module disabled</span>
												<?php elseif ( $module['has_config'] ) : ?>
													<span class="schema-frameworks-module-status">Configurable</span>
												<?php endif; ?>
												<?php if ( $module['description'] ) : ?>
													<span class="schema-frameworks-module-description"><?php echo esc_html( $module['description'] ); ?></span>
												<?php endif; ?>
											</span>
										</label>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</section>

					<section class="schema-frameworks-section schema-frameworks-configuration-section">
						<div class="schema-frameworks-section-heading">
							<div>
								<span class="schema-frameworks-section-kicker">Step 2</span>
								<h2>Schema Configuration</h2>
								<p class="description">Enter the data for the selected schema types. Configuration panels appear only when a schema is enabled.</p>
							</div>
						</div>

						<div class="schema-frameworks-config-panels">
							<?php foreach ( $modules as $module_id => $module ) : ?>
								<?php
								if ( ! $module['has_config'] ) {
									continue;
								}

								$module_selected = in_array( $module_id, $selected_schemas, true );
								$module_data = isset( $page[ $module_id ] ) && is_array( $page[ $module_id ] )
									? $page[ $module_id ]
									: array();
								?>

								<div
									id="schema-frameworks-config-<?php echo esc_attr( $index . '-' . $module_id ); ?>"
									class="schema-frameworks-config-panel"
									<?php echo $module_selected ? '' : ' style="display:none;"'; ?>
								>
									<?php
									echo $schema_configuration->render(
										$module_id,
										$module_data,
										'schema_frameworks_config[pages][' . $index . '][' . $module_id . ']',
										'schema-frameworks-field-' . $index . '-' . $module_id
									);
									?>
								</div>
							<?php endforeach; ?>
						</div>
					</section>

					<div class="schema-frameworks-page-actions">
						<button type="button" class="button-link-delete schema-frameworks-remove-page">Remove page mapping</button>
					</div>
				</div>

			<?php endforeach; ?>

		</div>

		<p class="schema-frameworks-add-page-wrap">
			<button type="button" class="button button-secondary" id="schema-frameworks-add-page">Add Page</button>
		</p>

		<?php if ( ! empty( $custom_mappings ) ) : ?>
			<section class="schema-frameworks-custom-mappings">
				<h2>Existing Custom URL/Path Mappings</h2>
				<p class="description">
					These mappings were created outside the page selector interface and are preserved unchanged.
				</p>
				<table class="widefat striped">
					<thead><tr><th>URL</th><th>Path</th><th>Schemas</th></tr></thead>
					<tbody>
					<?php foreach ( $custom_mappings as $mapping ) : ?>
						<tr>
							<td><?php echo esc_html( $mapping['url'] ?? '' ); ?></td>
							<td><?php echo esc_html( $mapping['path'] ?? '' ); ?></td>
							<td><?php echo esc_html( implode( ', ', isset( $mapping['schemas'] ) && is_array( $mapping['schemas'] ) ? $mapping['schemas'] : array() ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php endif; ?>

		<?php submit_button( 'Save Changes' ); ?>

	</form>

	<template id="schema-frameworks-page-template">
		<div class="schema-frameworks-page-mapping">
			<div class="schema-frameworks-page-header">
				<div>
					<span class="schema-frameworks-section-kicker">Page configuration</span>
					<h2>Page</h2>
					<p class="description">Choose the WordPress page where these schema rules should apply.</p>
				</div>
				<div class="schema-frameworks-page-selector">
					<?php wp_dropdown_pages( array( 'name' => '__PAGE_NAME__', 'show_option_none' => 'Select a page', 'option_none_value' => '0', 'echo' => true, 'class' => 'schema-frameworks-page-select' ) ); ?>
				</div>
			</div>

			<section class="schema-frameworks-section">
				<div class="schema-frameworks-section-heading">
					<div>
						<span class="schema-frameworks-section-kicker">Step 1</span>
						<h2>Enabled Schemas</h2>
						<p class="description">Select the schema types that should be active on this page.</p>
					</div>
				</div>

				<div class="schema-frameworks-module-grid">
					<?php foreach ( $modules as $module_id => $module ) : ?>
						<div class="schema-frameworks-module-card" data-module-id="<?php echo esc_attr( $module_id ); ?>">
							<label class="schema-frameworks-module-card-label">
								<input
									type="checkbox"
									class="schema-frameworks-schema-toggle"
									data-config-target="schema-frameworks-config-__PAGE_INDEX__-<?php echo esc_attr( $module_id ); ?>"
									name="__SCHEMA_PREFIX__[schemas][]"
									value="<?php echo esc_attr( $module_id ); ?>"
								/>
								<span class="schema-frameworks-module-card-content">
									<strong><?php echo esc_html( $module['label'] ); ?></strong>
									<?php if ( ! $module['enabled'] ) : ?><span class="schema-frameworks-module-status schema-frameworks-module-status-disabled">Module disabled</span><?php elseif ( $module['has_config'] ) : ?><span class="schema-frameworks-module-status">Configurable</span><?php endif; ?>
									<?php if ( $module['description'] ) : ?><span class="schema-frameworks-module-description"><?php echo esc_html( $module['description'] ); ?></span><?php endif; ?>
								</span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="schema-frameworks-section schema-frameworks-configuration-section">
				<div class="schema-frameworks-section-heading">
					<div>
						<span class="schema-frameworks-section-kicker">Step 2</span>
						<h2>Schema Configuration</h2>
						<p class="description">Enter the data for the selected schema types. Configuration panels appear only when a schema is enabled.</p>
					</div>
				</div>

				<div class="schema-frameworks-config-panels">
					<?php foreach ( $modules as $module_id => $module ) : ?>
						<?php if ( ! $module['has_config'] ) { continue; } ?>
						<div id="schema-frameworks-config-__PAGE_INDEX__-<?php echo esc_attr( $module_id ); ?>" class="schema-frameworks-config-panel" style="display:none;">
							<?php
							echo $schema_configuration->render(
								$module_id,
								array(),
								'__SCHEMA_PREFIX__[' . $module_id . ']',
								'schema-frameworks-field-__PAGE_INDEX__-' . $module_id,
								true
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="schema-frameworks-page-actions">
				<button type="button" class="button-link-delete schema-frameworks-remove-page">Remove page mapping</button>
			</div>
		</div>
	</template>

</div>