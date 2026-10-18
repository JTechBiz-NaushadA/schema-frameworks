<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Helpers\Relationships;

class Graph_Assembler {

	private $config;
	private $schema_manager;

	public function __construct( Config $config, Schema_Manager $schema_manager ) {
		$this->config = $config;
		$this->schema_manager = $schema_manager;
	}

	public function assemble_nodes( $nodes ) {
		$merged = array();

		foreach ( (array) $nodes as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$id = ! empty( $node['@id'] ) ? (string) $node['@id'] : (string) $key;
			if ( '' === $id ) {
				$merged[] = $node;
				continue;
			}

			if ( ! isset( $merged[ $id ] ) ) {
				$merged[ $id ] = $node;
				continue;
			}

			$merged[ $id ] = $this->merge_node( $merged[ $id ], $node );
		}

		return $merged;
	}

	private function merge_node( $base, $incoming ) {
		foreach ( $incoming as $property => $value ) {
			if ( ! array_key_exists( $property, $base ) || '' === $base[ $property ] || null === $base[ $property ] ) {
				$base[ $property ] = $value;
				continue;
			}

			if ( is_array( $base[ $property ] ) && is_array( $value ) && $this->is_list( $base[ $property ] ) && $this->is_list( $value ) ) {
				$base[ $property ] = array_values( array_unique( array_merge( $base[ $property ], $value ), SORT_REGULAR ) );
			}
		}

		return $base;
	}

	public function assemble( $schema, $module_id, $context = null ) {
		if ( ! is_array( $schema ) || empty( $module_id ) ) {
			return is_array( $schema ) ? $schema : array();
		}

		$page_context = $this->schema_manager->get_current_page_context();
		$page = $page_context->to_legacy_array();
		$schema = $this->ensure_identity( $schema, $module_id, $page_context );
		$schema = $this->normalize_common_values( $schema );
		$schema = Relationships::apply( $schema, $module_id, $page, $this->schema_manager );

		$validation = Graph_Validator::validate_node( $schema, $module_id );
		do_action( 'schema_frameworks_graph_validation', $validation, $schema, $module_id, $page, $context );

		return apply_filters(
			'schema_frameworks_graph_node',
			$schema,
			$module_id,
			$page,
			$context
		);
	}

	private function ensure_identity( $schema, $module_id, $page ) {
		if ( empty( $schema['@id'] ) ) {
			$id = Entity_Identity::for_module( $module_id, $page, $this->schema_manager );
			if ( $id ) {
				$schema['@id'] = $id;
			}
		}
		return $schema;
	}

	private function normalize_common_values( $schema ) {
		foreach ( array( 'offers' ) as $property ) {
			if ( empty( $schema[ $property ] ) ) {
				continue;
			}
			$value = $schema[ $property ];
			if ( is_array( $value ) && ! $this->is_list( $value ) ) {
				if ( isset( $value['price'] ) || isset( $value['priceCurrency'] ) || isset( $value['availability'] ) || isset( $value['url'] ) ) {
					$value['@type'] = 'Offer';
				}
				$schema[ $property ] = $value;
				continue;
			}
			if ( is_array( $value ) && $this->is_list( $value ) ) {
				foreach ( $value as $index => $offer ) {
					if ( is_array( $offer ) && ( isset( $offer['price'] ) || isset( $offer['priceCurrency'] ) || isset( $offer['availability'] ) || isset( $offer['url'] ) ) ) {
						$value[ $index ]['@type'] = 'Offer';
					}
				}
			}
			$schema[ $property ] = $value;
		}
		return $schema;
	}

	private function is_list( $array ) {
		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}
}