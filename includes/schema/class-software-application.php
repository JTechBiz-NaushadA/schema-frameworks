<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Helpers\Relationships;

class Software_Application implements Schema_Interface {

	private $config;

	private $schema_manager;

	public function __construct( $config, $schema_manager ) {

		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'software_application';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.software_application',
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
			'software_application',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['software_application'] ) ||
			! is_array( $page['software_application'] )
		) {
			return array();
		}

		$data = $page['software_application'];

		$schema = array(
			'@type' => 'SoftwareApplication',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
		);

		$allowed_properties = array(
			'name',
			'description',
			'url',
			'image',
			'applicationCategory',
			'applicationSubCategory',
			'operatingSystem',
			'softwareVersion',
			'releaseNotes',
			'featureList',
			'keywords',
			'isAccessibleForFree',
			'offers',
			'provider',
			'author',
			'publisher',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		$organization = Relationships::organization(
			$this->config
		);

		if ( ! empty( $organization ) ) {
			$schema['provider'] = $organization;
		}

		/**
		 * Allow developers to modify SoftwareApplication schema.
		 */
		return apply_filters(
			'schema_frameworks_software_application_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}

	private function get_schema_id() {

		$page = $this->schema_manager->get_current_page_configuration();

		if ( ! empty( $page['url'] ) ) {
			return trailingslashit( $page['url'] ) . '#software';
		}

		if ( ! empty( $page['path'] ) ) {
			return trailingslashit(
				home_url( '/' . trim( $page['path'], '/' ) . '/' )
			) . '#software';
		}

		$url = $this->schema_manager->get_current_page_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return trailingslashit( $url ) . '#software';
	}
}