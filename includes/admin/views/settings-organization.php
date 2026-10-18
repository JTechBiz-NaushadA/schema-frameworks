<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$organization = $config->get(
	'organization',
	array()
);

$module_enabled = $config->get(
	'modules.organization',
	false
);

$address = isset( $organization['address'] )
	&& is_array( $organization['address'] )
		? $organization['address']
		: array();

?>

<div class="wrap schema-frameworks-admin-page schema-frameworks-organization-settings">

	<h1>Organization</h1>

	<form method="post" action="options.php">

		<?php
		settings_fields(
			'schema_frameworks_settings'
		);
		?>

		<h2>Module Status</h2>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-enabled">
						Enable Organization Schema
					</label>
				</th>

				<td>

					<input
						type="hidden"
						name="schema_frameworks_config[modules][organization]"
						value="0"
					/>

					<input
						type="checkbox"
						id="schema-frameworks-organization-enabled"
						name="schema_frameworks_config[modules][organization]"
						value="1"
						<?php checked( $module_enabled, true ); ?>
					/>

					<p class="description">
						Enable Organization structured data and use
						Schema Frameworks organization settings to
						enhance the Yoast SEO schema graph.
					</p>

				</td>
			</tr>

		</table>

		<hr>

		<h2>Organization Identity</h2>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-name">
						Organization Name
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-name"
						name="schema_frameworks_config[organization][name]"
						value="<?php echo esc_attr( $organization['name'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-alternate-name">
						Alternate Name
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-alternate-name"
						name="schema_frameworks_config[organization][alternate_name]"
						value="<?php echo esc_attr( $organization['alternate_name'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-legal-name">
						Legal Name
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-legal-name"
						name="schema_frameworks_config[organization][legal_name]"
						value="<?php echo esc_attr( $organization['legal_name'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-url">
						URL
					</label>
				</th>

				<td>
					<input
						type="url"
						class="regular-text"
						id="schema-frameworks-organization-url"
						name="schema_frameworks_config[organization][url]"
						value="<?php echo esc_attr( $organization['url'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-logo">
						Logo URL
					</label>
				</th>

				<td>
					<input
						type="url"
						class="regular-text"
						id="schema-frameworks-organization-logo"
						name="schema_frameworks_config[organization][logo]"
						value="<?php echo esc_attr( $organization['logo'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-description">
						Description
					</label>
				</th>

				<td>
					<textarea
						class="large-text"
						rows="4"
						id="schema-frameworks-organization-description"
						name="schema_frameworks_config[organization][description]"
					><?php echo esc_textarea( $organization['description'] ?? '' ); ?></textarea>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-founding-date">
						Founding Date
					</label>
				</th>

				<td>
					<input
						type="date"
						id="schema-frameworks-organization-founding-date"
						name="schema_frameworks_config[organization][founding_date]"
						value="<?php echo esc_attr( $organization['founding_date'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-telephone">
						Telephone
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-telephone"
						name="schema_frameworks_config[organization][telephone]"
						value="<?php echo esc_attr( $organization['telephone'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-email">
						Email
					</label>
				</th>

				<td>
					<input
						type="email"
						class="regular-text"
						id="schema-frameworks-organization-email"
						name="schema_frameworks_config[organization][email]"
						value="<?php echo esc_attr( $organization['email'] ?? '' ); ?>"
					/>
				</td>
			</tr>

		</table>

		<hr>

		<h2>Address</h2>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-street">
						Street
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-street"
						name="schema_frameworks_config[organization][address][street]"
						value="<?php echo esc_attr( $address['street'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-locality">
						Locality
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-locality"
						name="schema_frameworks_config[organization][address][locality]"
						value="<?php echo esc_attr( $address['locality'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-region">
						Region
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-region"
						name="schema_frameworks_config[organization][address][region]"
						value="<?php echo esc_attr( $address['region'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-postal-code">
						Postal Code
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-postal-code"
						name="schema_frameworks_config[organization][address][postal_code]"
						value="<?php echo esc_attr( $address['postal_code'] ?? '' ); ?>"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schema-frameworks-organization-country">
						Country
					</label>
				</th>

				<td>
					<input
						type="text"
						class="regular-text"
						id="schema-frameworks-organization-country"
						name="schema_frameworks_config[organization][address][country]"
						value="<?php echo esc_attr( $address['country'] ?? '' ); ?>"
					/>
				</td>
			</tr>

		</table>

		<hr>

		<h2>Same As</h2>

		<p class="description">
			Add public URLs that identify the same organization, such as
			official social profiles or authoritative directory listings.
		</p>

		<div id="schema-frameworks-same-as">

			<?php
			$same_as = isset( $organization['same_as'] )
				&& is_array( $organization['same_as'] )
					? $organization['same_as']
					: array();

			if ( empty( $same_as ) ) {
				$same_as = array( '' );
			}
			?>

			<?php foreach ( $same_as as $index => $url ) : ?>

				<div class="schema-frameworks-same-as-row" style="margin-bottom: 8px;">

					<input
						type="url"
						class="regular-text"
						name="schema_frameworks_config[organization][same_as][<?php echo esc_attr( $index ); ?>]"
						value="<?php echo esc_attr( $url ); ?>"
						placeholder="https://example.com/profile"
					/>

					<button
						type="button"
						class="button schema-frameworks-remove-same-as"
					>
						Remove
					</button>

				</div>

			<?php endforeach; ?>

		</div>

		<p>
			<button
				type="button"
				class="button"
				id="schema-frameworks-add-same-as"
			>
				Add URL
			</button>
		</p>

		<template id="schema-frameworks-same-as-template">
			<div class="schema-frameworks-same-as-row" style="margin-bottom: 8px;">
				<input
					type="url"
					class="regular-text"
					name="schema_frameworks_config[organization][same_as][__SAME_AS_INDEX__]"
					value=""
					placeholder="https://example.com/profile"
				/>
				<button
					type="button"
					class="button schema-frameworks-remove-same-as"
				>
					Remove
				</button>
			</div>
		</template>

		<?php submit_button(); ?>

	</form>

</div>