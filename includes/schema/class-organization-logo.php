<?php

namespace SchemaFrameworks\Schema;

use SchemaFrameworks\Organization_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Organization_Logo implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'organization_logo';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return false;
		}

		if (
			! $this->config->get(
				'modules.organization',
				false
			)
		) {
			return false;
		}

		$organization = Organization_Resolver::resolve(
			$this->config
		);

		if (
			empty( $organization ) ||
			! is_array( $organization )
		) {
			return false;
		}

		return ! empty( $organization['logo'] );
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

		if ( empty( $organization['logo'] ) ) {
			return array();
		}

		$logo_url = esc_url_raw(
			$organization['logo']
		);

		if ( empty( $logo_url ) ) {
			return array();
		}

		$organization_id = Organization_Resolver::get_id(
			$this->config
		);

		$organization_logo_id = preg_replace(
			'/#organization$/',
			'#organization-logo',
			$organization_id
		);

		$schema = array(
			'@type'      => 'ImageObject',
			'@id'        => $organization_logo_id,
			'url'        => $logo_url,
			'contentUrl' => $logo_url,
		);

		return apply_filters(
			'schema_frameworks_organization_logo_schema',
			$schema,
			$organization,
			$context
		);
	}
}