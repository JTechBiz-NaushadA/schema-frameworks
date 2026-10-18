<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Breadcrumb implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'breadcrumb';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.breadcrumb',
			false
		) ) {
			return false;
		}

		$page = $this->schema_manager->get_current_page_configuration();

		if ( empty( $page ) ) {
			return false;
		}

		if (
			empty( $page['schemas'] ) ||
			! is_array( $page['schemas'] )
		) {
			return false;
		}

		return in_array(
			'breadcrumb',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['breadcrumb'] ) ||
			! is_array( $page['breadcrumb'] )
		) {
			return array();
		}

		$data = $page['breadcrumb'];

		$allowed_properties = array(
			'itemListElement',
		);

		$schema = array();

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		return apply_filters(
			'schema_frameworks_breadcrumb_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}
}