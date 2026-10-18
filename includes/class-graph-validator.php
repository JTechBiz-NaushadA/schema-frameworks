<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Helpers\Relationships;

class Graph_Validator {

	public static function validate_node( $schema, $module_id = '' ) {
		$errors = array();
		$warnings = array();

		if ( ! is_array( $schema ) ) {
			$errors[] = 'Schema node is not an array.';
			return compact( 'errors', 'warnings' );
		}

		if ( empty( $schema['@type'] ) ) {
			$errors[] = 'Schema node is missing @type.';
		}

		if ( ! empty( $module_id ) && empty( $schema['@id'] ) && 'image_object' !== $module_id ) {
			$warnings[] = 'Schema node is missing @id.';
		}

		if ( ! empty( $schema['offers'] ) ) {
			$offers = isset( $schema['offers'][0] ) ? $schema['offers'] : array( $schema['offers'] );
			foreach ( $offers as $offer ) {
				if ( is_array( $offer ) && empty( $offer['@type'] ) ) {
					$warnings[] = 'Offer is missing @type Offer.';
				}
			}
		}

		return compact( 'errors', 'warnings' );
	}

	public static function validate_relationships( $page, Registry $registry ) {
		$errors = array();
		if ( empty( $page['relationships'] ) || ! is_array( $page['relationships'] ) ) {
			return $errors;
		}
		foreach ( $page['relationships'] as $index => $relationship ) {
			if ( ! Relationships::validate( $relationship, $registry ) ) {
				$errors[] = 'Invalid relationship at index ' . (int) $index . '.';
			}
		}
		return $errors;
	}

	public static function validate_graph( $nodes ) {
		$errors = array();
		$warnings = array();
		$ids = array();

		foreach ( (array) $nodes as $key => $node ) {
			$result = self::validate_node( $node, is_string( $key ) ? $key : '' );
			$errors = array_merge( $errors, $result['errors'] );
			$warnings = array_merge( $warnings, $result['warnings'] );

			if ( ! empty( $node['@id'] ) ) {
				if ( isset( $ids[ $node['@id'] ] ) ) {
					$errors[] = 'Duplicate @id detected: ' . $node['@id'];
				} else {
					$ids[ $node['@id'] ] = true;
				}
			}
		}

		return compact( 'errors', 'warnings' );
	}
}