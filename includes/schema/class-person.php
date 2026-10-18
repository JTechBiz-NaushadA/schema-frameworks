<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Person implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'person';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.person',
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
			'person',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['person'] ) ||
			! is_array( $page['person'] )
		) {
			return array();
		}

		$data = $page['person'];

		if ( empty( $data['name'] ) ) {
			return array();
		}

		$schema = array(
			'@type' => 'Person',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
			'name'  => $data['name'],
		);

		if ( ! empty( $data['url'] ) ) {
			$schema['url'] = $data['url'];
		}

		if ( ! empty( $data['image'] ) ) {
			$schema['image'] = $data['image'];
		}

		$allowed_properties = array(
			'description',
			'jobTitle',
			'givenName',
			'familyName',
			'additionalName',
			'alternateName',
			'email',
			'telephone',
			'worksFor',
			'knowsAbout',
			'sameAs',
			'birthDate',
			'gender',
			'nationality',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		if ( ! empty( $data['@id'] ) ) {
			$schema['@id'] = \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager );
		}

		return apply_filters(
			'schema_frameworks_person_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}
}