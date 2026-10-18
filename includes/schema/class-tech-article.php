<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tech_Article implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'tech_article';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get(
			'modules.tech_article',
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
			'tech_article',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();

		if (
			empty( $page['tech_article'] ) ||
			! is_array( $page['tech_article'] )
		) {
			return array();
		}

		$data = $page['tech_article'];

		$schema = array(
			'@type' => 'TechArticle',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
		);

		$allowed_properties = array(
			'headline',
			'name',
			'description',
			'url',
			'image',
			'datePublished',
			'dateModified',
			'author',
			'publisher',
			'about',
			'proficiencyLevel',
			'dependencies',
			'programmingLanguage',
			'keywords',
			'articleSection',
			'wordCount',
			'inLanguage',
		);

		foreach ( $allowed_properties as $property ) {

			if ( array_key_exists( $property, $data ) ) {
				$schema[ $property ] = $data[ $property ];
			}
		}

		return apply_filters(
			'schema_frameworks_tech_article_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}

	private function get_schema_id() {

		$page = $this->schema_manager->get_current_page_configuration();

		if ( ! empty( $page['url'] ) ) {
			return trailingslashit( $page['url'] ) . '#techarticle';
		}

		if ( ! empty( $page['path'] ) ) {
			return trailingslashit(
				home_url(
					'/' . trim( $page['path'], '/' ) . '/'
				)
			) . '#techarticle';
		}

		$url = $this->schema_manager->get_current_page_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return trailingslashit( $url ) . '#techarticle';
	}
}