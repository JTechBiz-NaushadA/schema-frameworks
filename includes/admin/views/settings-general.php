<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled = $config->get(
	'general.enabled',
	true
);

$yoast_available =
	defined( 'WPSEO_VERSION' )
	&& class_exists(
		'\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece'
	);
?>

<div class="wrap schema-frameworks-admin-page schema-frameworks-general-settings">

	<h1>Schema Frameworks</h1>

	<form method="post" action="options.php">

		<?php
		settings_fields(
			'schema_frameworks_settings'
		);
		?>

		<h2>Framework Status</h2>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row">
					<label for="schema-frameworks-enabled">
						Enable Schema Frameworks
					</label>
				</th>

				<td>

					<input
						type="hidden"
						name="schema_frameworks_config[general][enabled]"
						value="0"
					/>

					<input
						type="checkbox"
						id="schema-frameworks-enabled"
						name="schema_frameworks_config[general][enabled]"
						value="1"
						<?php checked( $enabled, true ); ?>
					/>

					<p class="description">
						Enable or disable Schema Frameworks
						schema generation across the website.
					</p>

				</td>
			</tr>

		</table>

		<hr>

		<h2>Integration Status</h2>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row">
					Yoast SEO
				</th>

				<td>

					<?php if ( $yoast_available ) : ?>

						<span style="color: #008a00; font-weight: 600;">
							✓ Detected
						</span>

						<?php if ( defined( 'WPSEO_VERSION' ) ) : ?>

							<p class="description">
								Yoast SEO version:
								<?php echo esc_html( WPSEO_VERSION ); ?>
							</p>

						<?php endif; ?>

					<?php else : ?>

						<span style="color: #b32d2e; font-weight: 600;">
							✕ Not available
						</span>

						<p class="description">
							Yoast SEO and its Schema API are required
							for Schema Frameworks integration.
						</p>

					<?php endif; ?>

				</td>
			</tr>

			<tr>
				<th scope="row">
					Schema API
				</th>

				<td>

					<?php if ( $yoast_available ) : ?>

						<span style="color: #008a00; font-weight: 600;">
							✓ Available
						</span>

						<p class="description">
							Schema Frameworks can register custom
							schema graph pieces with Yoast SEO.
						</p>

					<?php else : ?>

						<span style="color: #b32d2e; font-weight: 600;">
							✕ Unavailable
						</span>

					<?php endif; ?>

				</td>
			</tr>

		</table>

		<?php submit_button(); ?>

	</form>

</div>