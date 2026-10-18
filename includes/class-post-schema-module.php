<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Schema_Module {

	private $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function is_enabled() {
		return (bool) $this->config->get( 'content_support.post_module_enabled', false );
	}

	public function get_mappings() {
		$mappings = $this->config->get( 'post_type_mappings', array() );
		return is_array( $mappings ) ? $mappings : array();
	}

	public function get_mapping( $post_type ) {
		$post_type = sanitize_key( $post_type );
		$mappings = $this->get_mappings();
		return isset( $mappings[ $post_type ] ) && is_array( $mappings[ $post_type ] ) ? $mappings[ $post_type ] : null;
	}

	public function is_generation_ready( $post_type ) {
		$mapping = $this->get_mapping( $post_type );
		$type = is_array( $mapping ) && isset( $mapping['schema_type'] ) ? $mapping['schema_type'] : '';
		$supported = in_array( $type, array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true );
		return $this->is_enabled() && is_array( $mapping ) && 'confirmed' === ( isset( $mapping['status'] ) ? $mapping['status'] : '' ) && ( $supported || ! empty( $mapping['generation_ready'] ) );
	}

	public function save_foundation_settings( $enabled, $mappings ) {
		$this->config->set( 'content_support.post_module_enabled', (bool) $enabled );
		$this->config->set( 'post_type_mappings', is_array( $mappings ) ? $mappings : array() );
		return $this->config->save();
	}
}
