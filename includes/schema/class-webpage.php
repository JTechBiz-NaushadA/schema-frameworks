<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WebPage implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'webpage';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.webpage',
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
			'webpage',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['webpage'] ) ||
			! is_array( $page['webpage'] )
		) {
			return array();
		}

		$data = $page['webpage'];

		$allowed_properties = array(
			'name',
			'description',
			'url',
			'image',
			'datePublished',
			'dateModified',
			'inLanguage',
			'about',
			'mainEntity',
			'keywords',
			'lastReviewed',
			'primaryImageOfPage',
			'relatedLink',
			'significantLink',
			'reviewedBy',
			'speakable',
			'isAccessibleForFree',
			'isFamilyFriendly',
			'license',
			'learningResourceType',
			'sameAs',
		);

		$schema = array();

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		return apply_filters(
			'schema_frameworks_webpage_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}
}