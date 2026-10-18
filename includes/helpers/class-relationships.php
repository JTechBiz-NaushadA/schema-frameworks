<?php

namespace SchemaFrameworks\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Config;
use SchemaFrameworks\Entity_Reference;
use SchemaFrameworks\Organization_Resolver;
use SchemaFrameworks\Registry;

class Relationships {

	public static function rules( Registry $registry ) {
		$rules = array();

		foreach ( $registry->all() as $module_id => $definition ) {
			$capabilities = $registry->get_relationship_capabilities( $module_id );

			foreach ( $capabilities as $capability ) {
				if ( ! is_array( $capability ) || empty( $capability['property'] ) || empty( $capability['targets'] ) || ! is_array( $capability['targets'] ) ) {
					continue;
				}

				$property = sanitize_text_field( $capability['property'] );
				$targets  = array_values( array_unique( array_filter( array_map( 'sanitize_key', $capability['targets'] ) ) ) );

				if ( '' === $property || empty( $targets ) ) {
					continue;
				}

				$rules[ $module_id ][ $property ] = $targets;
			}
		}

		return $rules;
	}

	public static function organization( Config $config ) {

		$organization = Organization_Resolver::resolve( $config );

		if ( empty( $organization ) || ! is_array( $organization ) || empty( $organization['url'] ) ) {
			return array();
		}

		return array(
			'@id' => Organization_Resolver::get_id( $config ),
		);
	}

	public static function apply( $schema, $source_id, $page, $manager, $nodes = array() ) {

		$page_context = $page instanceof \SchemaFrameworks\Page_Schema_Context ? $page : new \SchemaFrameworks\Page_Schema_Context( is_array( $page ) ? $page : array() );
		$page = $page_context->to_legacy_array();
		$relationships = $page_context->relationships();
		if ( empty( $relationships ) ) {
			return $schema;
		}

		$rules = self::rules( $manager->registry() );
		if ( empty( $rules[ $source_id ] ) ) {
			return $schema;
		}

		foreach ( $relationships as $relationship ) {
			if ( ! is_array( $relationship ) ) {
				continue;
			}

			if ( isset( $relationship['source'] ) && $source_id !== sanitize_key( $relationship['source'] ) ) {
				continue;
			}

			$property = isset( $relationship['property'] ) ? (string) $relationship['property'] : '';
			$target   = isset( $relationship['target'] ) ? sanitize_key( $relationship['target'] ) : '';

			if ( ! $property || ! $target || empty( $rules[ $source_id ][ $property ] ) ) {
				continue;
			}

			if ( ! in_array( $target, $rules[ $source_id ][ $property ], true ) ) {
				continue;
			}

			$entity_reference = Entity_Reference::resolve(
				$source_id,
				$property,
				$target,
				$page,
				$manager
			);

			if ( ! $entity_reference ) {
				continue;
			}

			$reference = $entity_reference->to_schema_value();
			$target_id = $reference['@id'];

			if ( isset( $schema[ $property ] ) ) {
				if ( is_array( $schema[ $property ] ) && isset( $schema[ $property ]['@id'] ) ) {
					if ( $schema[ $property ]['@id'] === $target_id ) {
						continue;
					}
					$schema[ $property ] = array( $schema[ $property ], $reference );
					continue;
				}

				if ( ! is_array( $schema[ $property ] ) || ! self::is_list( $schema[ $property ] ) ) {
					$schema[ $property ] = array( $schema[ $property ], $reference );
					continue;
				}

				$existing_ids = array();
				foreach ( $schema[ $property ] as $existing ) {
					if ( is_array( $existing ) && ! empty( $existing['@id'] ) ) {
						$existing_ids[] = $existing['@id'];
					}
				}
				if ( ! in_array( $target_id, $existing_ids, true ) ) {
					$schema[ $property ][] = $reference;
				}
			} else {
				$schema[ $property ] = $reference;
			}
		}

		return $schema;
	}

	private static function is_list( $value ) {
		return is_array( $value ) && array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	public static function validate( $relationship, Registry $registry ) {
		if ( ! is_array( $relationship ) ) {
			return false;
		}

		$source   = isset( $relationship['source'] ) ? sanitize_key( $relationship['source'] ) : '';
		$property = isset( $relationship['property'] ) ? (string) $relationship['property'] : '';
		$target   = isset( $relationship['target'] ) ? sanitize_key( $relationship['target'] ) : '';
		$rules    = self::rules( $registry );

		return ! empty( $rules[ $source ][ $property ] ) && in_array( $target, $rules[ $source ][ $property ], true );
	}
}