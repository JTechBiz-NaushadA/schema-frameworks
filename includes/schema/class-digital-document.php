<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Digital_Document implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'digital_document';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.digital_document',
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
			'digital_document',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['digital_document'] ) ||
			! is_array( $page['digital_document'] )
		) {
			return array();
		}

		$data = $page['digital_document'];

		$schema = array(
			'@type' => 'DigitalDocument',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
		);

		$allowed_properties = array(
			'name',
			'description',
			'url',
			'image',
			'contentUrl',
			'encodingFormat',
			'datePublished',
			'dateModified',
			'inLanguage',
			'author',
			'publisher',
			'about',
			'mentions',
			'keywords',
			'license',
			'version',
			'fileSize',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		return apply_filters(
			'schema_frameworks_digital_document_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}

	private function get_schema_id() {

		$page = $this->schema_manager->get_current_page_configuration();

		if ( ! empty( $page['url'] ) ) {
			return trailingslashit( $page['url'] ) . '#digitaldocument';
		}

		if ( ! empty( $page['path'] ) ) {
			return trailingslashit(
				home_url(
					'/' . trim( $page['path'], '/' ) . '/'
				)
			) . '#digitaldocument';
		}

		$url = $this->schema_manager->get_current_page_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return trailingslashit( $url ) . '#digitaldocument';
	}
}