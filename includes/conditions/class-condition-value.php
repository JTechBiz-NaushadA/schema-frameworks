<?php

namespace SchemaFrameworks\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Condition_Value {

	protected function values( $value ) {
		if ( is_array( $value ) ) {
			$values = $value;
		} else {
			$values = array( $value );
		}

		$result = array();
		foreach ( $values as $item ) {
			if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
				$result[] = (string) $item;
			}
		}

		return $result;
	}

	protected function boolean_match( $value, $actual ) {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return (bool) $value === (bool) $actual;
	}
}