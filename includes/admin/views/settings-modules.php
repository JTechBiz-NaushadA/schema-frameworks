<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modules = array();

foreach ( $registry->all() as $id => $definition ) {

	$options = isset( $definition['options'] )
		&& is_array( $definition['options'] )
		? $definition['options']
		: array();

	if ( empty( $options['admin_managed'] ) || 'comment' === $id ) {
		continue;
	}

	$modules[ $id ] = array(
		'label'       => ! empty( $options['label'] )
			? $options['label']
			: $id,
		'description' => ! empty( $options['description'] )
			? $options['description']
			: '',
	);
}

ksort( $modules );
?>

<div class="wrap schema-frameworks-admin-page schema-frameworks-modules-settings">

	<h1>Schema Modules</h1>

	<p>
		Enable or disable the Schema Frameworks modules that are available
		for page-level schema configuration. Internal integration modules
		and Organization are managed separately.
	</p>

	<form method="post" action="options.php">

		<?php
		settings_fields(
			'schema_frameworks_settings'
		);
		?>

		<table class="widefat striped" role="presentation">
			<thead>
				<tr>
					<th scope="col" style="width: 80px;">Enabled</th>
					<th scope="col">Module</th>
					<th scope="col">Description</th>
				</tr>
			</thead>
			<tbody>

			<?php if ( empty( $modules ) ) : ?>

				<tr>
					<td colspan="3">
						No user-manageable schema modules are currently registered.
					</td>
				</tr>

			<?php else : ?>

				<?php foreach ( $modules as $id => $module ) : ?>

					<?php
					$enabled = $config->get(
						'modules.' . $id,
						false
					);
					?>

					<tr>
						<td>
							<input
								type="hidden"
								name="schema_frameworks_config[modules][<?php echo esc_attr( $id ); ?>]"
								value="0"
							/>

							<input
								type="checkbox"
								id="schema-frameworks-module-<?php echo esc_attr( $id ); ?>"
								name="schema_frameworks_config[modules][<?php echo esc_attr( $id ); ?>]"
								value="1"
								<?php checked( $enabled, true ); ?>
							/>
						</td>

						<th scope="row">
							<label for="schema-frameworks-module-<?php echo esc_attr( $id ); ?>">
								<?php echo esc_html( $module['label'] ); ?>
							</label>
						</th>

						<td>
							<?php echo esc_html( $module['description'] ); ?>
						</td>
					</tr>

				<?php endforeach; ?>

			<?php endif; ?>

			</tbody>
		</table>

		<p class="description">Approved Comment Schema settings are managed on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=schema-frameworks-post-mappings' ) ); ?>">Post Type Schema Mapping</a> page.</p>

		<?php submit_button(); ?>

	</form>

</div>
