<?php

namespace SchemaFrameworks\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Config;
use SchemaFrameworks\Registry;

class Settings {

	private $config;

	private $registry;

	private $schema_configuration;

	public function __construct( Config $config, Registry $registry, Schema_Configuration $schema_configuration ) {

		$this->config              = $config;
		$this->registry            = $registry;
		$this->schema_configuration = $schema_configuration;
	}

	public function init() {

		add_action(
			'admin_init',
			array( $this, 'register_settings' )
		);
	}

	public function register_settings() {

		register_setting(
			'schema_frameworks_settings',
			'schema_frameworks_config',
			array(
				'type'              => 'array',
				'sanitize_callback' => array(
					$this,
					'sanitize',
				),
				'default'           => array(),
			)
		);
	}

	public function sanitize( $input ) {

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$current = $this->config->all();

		$output = array_replace_recursive(
			$current,
			$input
		);

		if ( isset( $input['general']['enabled'] ) ) {

			$output['general']['enabled'] =
				! empty( $input['general']['enabled'] );
		}

		if ( ! isset( $output['modules'] ) || ! is_array( $output['modules'] ) ) {
			$output['modules'] = array();
		}

		foreach ( $this->registry->all() as $id => $definition ) {

			$options = isset( $definition['options'] )
				&& is_array( $definition['options'] )
				? $definition['options']
				: array();

			if ( empty( $options['admin_managed'] ) ) {
				continue;
			}

			if ( isset( $input['modules'][ $id ] ) ) {
				$output['modules'][ $id ] = ! empty( $input['modules'][ $id ] );
			} elseif ( isset( $input['modules'] ) ) {
				$output['modules'][ $id ] = false;
			}
		}

		if ( isset( $input['comments']['max_per_post'] ) ) {
			$output['comments']['max_per_post'] = max( 1, min( 100, absint( $input['comments']['max_per_post'] ) ) );
		}

		if ( isset( $input['modules']['organization'] ) ) {

			$output['modules']['organization'] =
				! empty( $input['modules']['organization'] );
		}

		if ( isset( $input['pages'] ) && is_array( $input['pages'] ) ) {

			$existing_pages = isset( $current['pages'] )
				&& is_array( $current['pages'] )
				? $current['pages']
				: array();

			$existing_by_id = array();

			foreach ( $existing_pages as $existing_page ) {

				if ( ! is_array( $existing_page ) ) {
					continue;
				}

				if ( ! empty( $existing_page['page_id'] ) ) {
					$existing_by_id[ (int) $existing_page['page_id'] ] = $existing_page;
				}
			}

			$normalized_pages = array();

			foreach ( $input['pages'] as $page ) {

				if ( ! is_array( $page ) ) {
					continue;
				}

				$page_id = isset( $page['page_id'] )
					? absint( $page['page_id'] )
					: 0;

				if ( ! $page_id ) {
					continue;
				}

				$post = get_post( $page_id );

				if ( ! $post || 'page' !== $post->post_type ) {
					continue;
				}

				$schemas = isset( $page['schemas'] ) && is_array( $page['schemas'] )
					? $page['schemas']
					: array();

				$schemas = array_values(
					array_unique(
						array_filter(
							array_map( 'sanitize_key', $schemas )
						)
					)
				);

				$page_config = isset( $existing_by_id[ $page_id ] )
					? $existing_by_id[ $page_id ]
					: array();

				$page_config['page_id'] = $page_id;
				$page_config['url'] = esc_url_raw( get_permalink( $page_id ) );
				$page_config['path'] = wp_parse_url( $page_config['url'], PHP_URL_PATH );
				$page_config['schemas'] = $schemas;

				foreach ( $schemas as $schema_id ) {

					if ( ! $this->registry->has( $schema_id ) ) {
						continue;
					}

					if ( isset( $page[ $schema_id ] ) && is_array( $page[ $schema_id ] ) ) {
						$page_config[ $schema_id ] = $this->schema_configuration->sanitize(
							$schema_id,
							$page[ $schema_id ]
						);
					}
				}

				$normalized_pages[] = $page_config;
			}

			foreach ( $existing_pages as $existing_page ) {

				if ( ! is_array( $existing_page ) ) {
					continue;
				}

				if ( ! empty( $existing_page['page_id'] ) ) {
					continue;
				}

				if ( ! empty( $existing_page['url'] ) || ! empty( $existing_page['path'] ) ) {
					$normalized_pages[] = $existing_page;
				}
			}

			$output['pages'] = $normalized_pages;
		}

		if ( isset( $input['organization'] )
			&& is_array( $input['organization'] ) ) {

			$organization = $input['organization'];

			$output['organization'] = array_replace_recursive(
				$current['organization'] ?? array(),
				$organization
			);

			if ( isset( $organization['same_as'] ) ) {

				$same_as = is_array( $organization['same_as'] )
					? $organization['same_as']
					: array();

				$normalized_same_as = array();

				foreach ( $same_as as $url ) {

					$url = esc_url_raw( trim( (string) $url ) );

					if ( empty( $url ) ) {
						continue;
					}

					if ( ! in_array( $url, $normalized_same_as, true ) ) {
						$normalized_same_as[] = $url;
					}
				}

				$output['organization']['same_as'] = $normalized_same_as;
			}
		}

		return $output;
	}
}