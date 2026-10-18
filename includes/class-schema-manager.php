<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema_Manager {

	private $config;

	private $registry;

	private $condition_manager = null;

	public function __construct( Config $config, Registry $registry ) {

		$this->config   = $config;
		$this->registry = $registry;
	}

	public function init() {

		$this->registry->register_default_schemas(
			$this->config,
			$this
		);

		do_action(
			'schema_frameworks_register_schemas',
			$this->registry,
			$this->config
		);
	}

	public function config() {
		return $this->config;
	}

	public function registry() {
		return $this->registry;
	}

	public function set_condition_manager( $condition_manager ) {

		if ( $condition_manager instanceof \SchemaFrameworks\Conditions\Condition_Manager ) {
			$this->condition_manager = $condition_manager;
		}
	}

	public function conditions_match( $schema_id, $context = null ) {

		if ( ! $this->condition_manager ) {
			return true;
		}

		$page = $this->get_current_page_configuration();

		if ( empty( $page ) || ! is_array( $page ) ) {
			return true;
		}

		if (
			empty( $page['conditions'] ) ||
			! is_array( $page['conditions'] )
		) {
			return true;
		}

		if ( ! array_key_exists( $schema_id, $page['conditions'] ) ) {
			return true;
		}

		$configuration = $page['conditions'][ $schema_id ];

		$request_context = \SchemaFrameworks\Conditions\Request_Context::from_current_request();

		return $this->condition_manager->matches(
			$configuration,
			$request_context
		);
	}

	public function is_enabled() {

		return (bool) $this->config->get(
			'general.enabled',
			true
		);
	}

	public function get_current_page_configuration() {

		$pages = $this->config->get( 'pages', array() );

		if ( empty( $pages ) || ! is_array( $pages ) ) {
			return array();
		}

		$current_path = $this->get_current_page_path();

		foreach ( $pages as $page ) {

			if ( ! is_array( $page ) ) {
				continue;
			}

			if (
				! empty( $page['page_id'] ) &&
				is_singular() &&
				(int) $page['page_id'] === (int) get_queried_object_id()
			) {
				return $page;
			}

			if ( ! empty( $page['url'] ) ) {

				$configured_url = wp_parse_url(
					$page['url'],
					PHP_URL_PATH
				);

				$configured_path = $this->normalize_page_path(
					$configured_url
				);

				if ( $current_path === $configured_path ) {
					return $page;
				}
			}

			if ( isset( $page['path'] ) ) {

				$configured_path = $this->normalize_page_path(
					$page['path']
				);

				if ( $current_path === $configured_path ) {
					return $page;
				}
			}
		}

		return array();
	}

	public function get_current_page_context() {
		return new Page_Schema_Context( $this->get_current_page_configuration() );
	}

	public function get_content_context( $configuration = array() ) {
		return new Content_Schema_Context( is_array( $configuration ) ? $configuration : array() );
	}

	public function get_current_page_url() {

		$url = get_permalink();

		if ( $url ) {
			return $url;
		}

		return home_url( '/' );
	}

	private function get_current_page_path() {

		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? wp_unslash( $_SERVER['REQUEST_URI'] )
			: '';

		$request_path = wp_parse_url(
			$request_uri,
			PHP_URL_PATH
		);

		if ( ! $request_path ) {
			return '';
		}

		$home_path = wp_parse_url(
			home_url( '/' ),
			PHP_URL_PATH
		);

		if ( $home_path ) {

			$home_path = trim(
				$home_path,
				'/'
			);

			if ( $home_path ) {

				$request_path = preg_replace(
					'#^/' . preg_quote( $home_path, '#' ) . '/?#',
					'',
					$request_path
				);
			}
		}

		$request_path = preg_replace(
			'#^/?index\.php/?#',
			'',
			$request_path
		);

		return $this->normalize_page_path(
			$request_path
		);
	}

	private function normalize_page_path( $path ) {

		$path = trim(
			(string) $path,
			'/'
		);

		$home_path = wp_parse_url(
			home_url( '/' ),
			PHP_URL_PATH
		);

		if ( $home_path ) {

			$home_path = trim(
				$home_path,
				'/'
			);

			if ( $home_path ) {

				$path = preg_replace(
					'#^' . preg_quote( $home_path, '#' ) . '/?#',
					'',
					$path
				);
			}
		}

		$path = preg_replace(
			'#^/?index\.php/?#',
			'',
			$path
		);

		return trim(
			$path,
			'/'
		);
	}
}