<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Config {

	const OPTION_NAME = 'schema_frameworks_config';

	private $config = array();

	public function __construct() {

		$defaults = array();

		$defaults_file = SCHEMA_FRAMEWORKS_PATH . 'config/defaults.php';

		if ( file_exists( $defaults_file ) ) {
			$defaults = require $defaults_file;
		}

		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$this->config = array_replace_recursive(
			$defaults,
			$saved
		);

		$this->config = apply_filters(
			'schema_frameworks_config',
			$this->config
		);
	}

	public function all() {
		return $this->config;
	}

	public function get( $key, $default = null ) {

		$segments = explode( '.', $key );
		$value    = $this->config;

		foreach ( $segments as $segment ) {

			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $default;
			}

			$value = $value[ $segment ];
		}

		return $value;
	}

	public function has( $key ) {

		$segments = explode( '.', $key );
		$value    = $this->config;

		foreach ( $segments as $segment ) {

			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return false;
			}

			$value = $value[ $segment ];
		}

		return true;
	}

	public function set( $key, $value ) {

		$segments = explode( '.', $key );
		$reference =& $this->config;

		foreach ( $segments as $segment ) {

			if ( ! isset( $reference[ $segment ] ) || ! is_array( $reference[ $segment ] ) ) {
				$reference[ $segment ] = array();
			}

			$reference =& $reference[ $segment ];
		}

		$reference = $value;
	}

	public function save() {

		return update_option(
			self::OPTION_NAME,
			$this->config
		);
	}
}