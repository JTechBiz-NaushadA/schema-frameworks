<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Image_Object implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'image_object';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.image_object',
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
			'image_object',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['image_object'] ) ||
			! is_array( $page['image_object'] )
		) {
			return array();
		}

		$data = $page['image_object'];

		if ( empty( $data['url'] ) ) {
			return array();
		}

		$image_url = esc_url_raw(
			$data['url']
		);

		if ( empty( $image_url ) ) {
			return array();
		}

		$schema = array(
			'@type'       => 'ImageObject',
			'@id'         => $image_url,
			'url'         => $image_url,
			'contentUrl'  => $image_url,
		);

		$allowed_properties = array(
			'caption',
			'inLanguage',
			'width',
			'height',
			'name',
			'description',
			'alternateName',
			'encodingFormat',
			'contentSize',
			'uploadDate',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		return apply_filters(
			'schema_frameworks_image_object_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}
}