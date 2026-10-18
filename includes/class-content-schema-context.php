<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Content_Schema_Context {

	private $metadata = array();
	private $selected_schemas = array();
	private $entity_properties = array();
	private $relationships = array();
	private $conditions = array();
	private $legacy = array();

	public function __construct( $configuration = array() ) {
		$configuration = is_array( $configuration ) ? $configuration : array();
		$this->legacy = $configuration;

		$metadata_keys = array( 'page_id', 'post_id', 'content_id', 'post_type', 'url', 'path', 'title' );
		$this->metadata = array_intersect_key( $configuration, array_flip( $metadata_keys ) );

		$this->selected_schemas = isset( $configuration['schemas'] ) && is_array( $configuration['schemas'] )
			? array_values( array_unique( array_filter( array_map( 'sanitize_key', $configuration['schemas'] ) ) ) )
			: array();
		$this->relationships = isset( $configuration['relationships'] ) && is_array( $configuration['relationships'] )
			? array_values( array_filter( $configuration['relationships'], 'is_array' ) )
			: array();
		$this->conditions = isset( $configuration['conditions'] ) && is_array( $configuration['conditions'] )
			? $configuration['conditions']
			: array();

		$reserved = array_merge( $metadata_keys, array( 'schemas', 'relationships', 'conditions' ) );
		foreach ( $configuration as $key => $value ) {
			if ( ! in_array( $key, $reserved, true ) && is_array( $value ) ) {
				$this->entity_properties[ sanitize_key( $key ) ] = $value;
			}
		}
	}

	public function metadata() { return $this->metadata; }
	public function selected_schemas() { return $this->selected_schemas; }
	public function has_schema( $module_id ) { return in_array( sanitize_key( $module_id ), $this->selected_schemas, true ); }
	public function entity_properties( $module_id ) {
		$module_id = sanitize_key( $module_id );
		return isset( $this->entity_properties[ $module_id ] ) ? $this->entity_properties[ $module_id ] : array();
	}
	public function explicit_entity_id( $module_id ) {
		$properties = $this->entity_properties( $module_id );
		return isset( $properties['@id'] ) && is_string( $properties['@id'] ) ? trim( $properties['@id'] ) : '';
	}
	public function relationships() { return $this->relationships; }
	public function conditions() { return $this->conditions; }
	public function to_legacy_array() { return $this->legacy; }
}