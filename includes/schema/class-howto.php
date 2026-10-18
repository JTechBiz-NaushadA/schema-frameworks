<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HowTo implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'howto';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.howto',
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
			'howto',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['howto'] ) ||
			! is_array( $page['howto'] )
		) {
			return array();
		}

		$data = $page['howto'];

		$schema = array(
			'@type' => 'HowTo',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
		);

		$allowed_properties = array(
			'name',
			'description',
			'image',
			'totalTime',
			'estimatedCost',
			'tool',
			'supply',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		if (
			! empty( $data['steps'] ) &&
			is_array( $data['steps'] )
		) {
			$steps = array();

			foreach ( $data['steps'] as $step ) {

				if ( ! is_array( $step ) ) {
					continue;
				}

				if ( empty( $step['name'] ) ) {
					continue;
				}

				$step_schema = array(
					'@type' => 'HowToStep',
					'name'  => $step['name'],
				);

				if ( ! empty( $step['text'] ) ) {
					$step_schema['text'] = $step['text'];
				}

				if ( ! empty( $step['url'] ) ) {
					$step_schema['url'] = $step['url'];
				}

				if ( ! empty( $step['image'] ) ) {
					$step_schema['image'] = $step['image'];
				}

				$steps[] = $step_schema;
			}

			if ( ! empty( $steps ) ) {
				$schema['step'] = $steps;
			}
		}

		return apply_filters(
			'schema_frameworks_howto_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}

	private function get_schema_id() {

		$page = $this->schema_manager->get_current_page_configuration();

		if ( ! empty( $page['url'] ) ) {
			return trailingslashit( $page['url'] ) . '#howto';
		}

		if ( ! empty( $page['path'] ) ) {
			return trailingslashit(
				home_url(
					'/' . trim( $page['path'], '/' ) . '/'
				)
			) . '#howto';
		}

		$url = $this->schema_manager->get_current_page_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return trailingslashit( $url ) . '#howto';
	}
}