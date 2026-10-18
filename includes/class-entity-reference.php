<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Entity_Reference {

	private $source_id;
	private $predicate;
	private $target_id;
	private $target_type;
	private $target_schema_id;

	public function __construct( $source_id, $predicate, $target_schema_id, $target_id, $target_type ) {
		$this->source_id        = sanitize_key( $source_id );
		$this->predicate        = sanitize_text_field( $predicate );
		$this->target_schema_id = sanitize_key( $target_schema_id );
		$this->target_id        = trim( (string) $target_id );
		$this->target_type      = sanitize_text_field( $target_type );
	}

	public static function resolve( $source_id, $predicate, $target_schema_id, $page, $manager ) {
		if ( ! is_array( $page ) || ! is_object( $manager ) || ! method_exists( $manager, 'registry' ) || ! method_exists( $manager, 'config' ) ) {
			return null;
		}

		$page_context = $page instanceof Page_Schema_Context ? $page : new Page_Schema_Context( $page );
		$page = $page_context->to_legacy_array();

		$source_id       = sanitize_key( $source_id );
		$target_schema_id = sanitize_key( $target_schema_id );
		$predicate       = sanitize_text_field( $predicate );

		if ( ! $source_id || ! $target_schema_id || ! $predicate ) {
			return null;
		}

		$registry = $manager->registry();
		$rules    = Helpers\Relationships::rules( $registry );

		if ( empty( $rules[ $source_id ][ $predicate ] ) || ! in_array( $target_schema_id, $rules[ $source_id ][ $predicate ], true ) ) {
			return null;
		}

		$config = $manager->config();
		if ( ! $config->get( 'modules.' . $target_schema_id, false ) ) {
			return null;
		}

		if ( 'organization' !== $target_schema_id ) {
			if ( ! $page_context->has_schema( $target_schema_id ) ) {
				return null;
			}

			if ( method_exists( $manager, 'conditions_match' ) && ! $manager->conditions_match( $target_schema_id ) ) {
				return null;
			}
		}

		$target_id = Entity_Identity::for_module( $target_schema_id, $page_context, $manager );
		$target_type = Entity_Identity::type_for_module( $target_schema_id );

		if ( ! $target_id || ! $target_type ) {
			return null;
		}

		return new self( $source_id, $predicate, $target_schema_id, $target_id, $target_type );
	}

	public function to_schema_value() {
		return array( '@id' => $this->target_id );
	}

	public function to_array() {
		return array(
			'source'       => $this->source_id,
			'predicate'    => $this->predicate,
			'target'       => $this->target_schema_id,
			'target_type'  => $this->target_type,
			'target_id'    => $this->target_id,
		);
	}
}