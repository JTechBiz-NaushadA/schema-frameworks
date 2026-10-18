<?php

namespace SchemaFrameworks\Schema;

use SchemaFrameworks\Organization_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Organization implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct(
		$config,
		$schema_manager
	) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'organization';
	}

	public function is_needed( $context = null ) {

		return (bool) $this->config->get(
			'modules.organization',
			false
		);
	}

	public function generate( $context = null ) {

		$organization = Organization_Resolver::resolve(
			$this->config
		);

		if (
			empty( $organization ) ||
			! is_array( $organization )
		) {
			return array();
		}

		if ( empty( $organization['name'] ) ) {
			return array();
		}

		$schema = array(
			'@type' => 'Organization',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( 'organization', array(), $this->schema_manager ),
			'name'  => $organization['name'],
		);

		if ( ! empty( $organization['alternate_name'] ) ) {

			$schema['alternateName'] =
				$organization['alternate_name'];
		}

		if ( ! empty( $organization['url'] ) ) {

			$organization_url = trailingslashit(
				esc_url_raw(
					$organization['url']
				)
			);


			$schema['url'] =
				$organization_url;
		}

		$property_map = array(
			'legal_name'    => 'legalName',
			'logo'          => 'logo',
			'description'   => 'description',
			'founding_date' => 'foundingDate',
			'telephone'     => 'telephone',
			'email'         => 'email',
		);

		foreach (
			$property_map as $config_key => $schema_property
		) {

			if (
				array_key_exists(
					$config_key,
					$organization
				) &&
				'' !== $organization[ $config_key ]
			) {

				$schema[ $schema_property ] =
					$organization[ $config_key ];
			}
		}

		if (
			! empty( $organization['address'] ) &&
			is_array( $organization['address'] )
		) {

			$address = $organization['address'];

			$postal_address = array(
				'@type' => 'PostalAddress',
			);

			$address_map = array(
				'street'      => 'streetAddress',
				'locality'    => 'addressLocality',
				'region'      => 'addressRegion',
				'postal_code' => 'postalCode',
				'country'     => 'addressCountry',
			);

			foreach (
				$address_map as $config_key => $schema_property
			) {

				if (
					array_key_exists(
						$config_key,
						$address
					) &&
					'' !== $address[ $config_key ]
				) {

					$postal_address[ $schema_property ] =
						$address[ $config_key ];
				}
			}

			if ( count( $postal_address ) > 1 ) {
				$schema['address'] =
					$postal_address;
			}
		}

		if (
			! empty( $organization['same_as'] ) &&
			is_array( $organization['same_as'] )
		) {

			$same_as = array();

			foreach (
				$organization['same_as'] as $url
			) {

				$url = esc_url_raw( $url );

				if ( ! empty( $url ) ) {
					$same_as[] = $url;
				}
			}

			if ( ! empty( $same_as ) ) {
				$schema['sameAs'] = $same_as;
			}
		}

		return apply_filters(
			'schema_frameworks_organization_schema',
			$schema,
			$organization,
			$context
		);
	}
}