<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FAQ implements Schema_Interface {

	private $config;
	private $schema_manager;

	public function __construct( $config, $schema_manager ) {
		$this->config         = $config;
		$this->schema_manager = $schema_manager;
	}

	public function get_id() {
		return 'faq';
	}

	public function is_needed( $context = null ) {

		if ( ! $this->config->get( 'modules.faq', false ) ) {
			return false;
		}

		if ( is_singular( 'post' ) ) {
			$post_faq = get_post_meta( get_queried_object_id(), '_schema_frameworks_post_faq', true );
			if ( $this->has_valid_questions( $post_faq ) ) {
				return true;
			}
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
			'faq',
			$page['schemas'],
			true
		);
	}

	public function generate( $context = null ) {

		$page = $this->schema_manager->get_current_page_configuration();
		$data = array();
		if ( is_singular( 'post' ) ) {
			$post_id = get_queried_object_id();
			$post_faq = get_post_meta( $post_id, '_schema_frameworks_post_faq', true );
			if ( $this->has_valid_questions( $post_faq ) ) {
				$data = array( 'questions' => $post_faq, 'name' => get_the_title( $post_id ) );
				if ( empty( $page ) ) {
					$page = array( 'page_id' => $post_id, 'url' => get_permalink( $post_id ), 'path' => wp_parse_url( get_permalink( $post_id ), PHP_URL_PATH ), 'schemas' => array( 'faq' ) );
				}
			}
		}
		if ( empty( $data ) ) {
			if ( empty( $page['faq'] ) || ! is_array( $page['faq'] ) ) { return array(); }
			$data = $page['faq'];
		}

		$schema = array(
			'@type' => 'FAQPage',
			'@id'   => \SchemaFrameworks\Entity_Identity::for_module( $this->get_id(), $page, $this->schema_manager ),
		);

		if ( ! empty( $data['name'] ) ) {
			$schema['name'] = $data['name'];
		}

		if ( ! empty( $data['description'] ) ) {
			$schema['description'] = $data['description'];
		}

		if (
			! empty( $data['questions'] ) &&
			is_array( $data['questions'] )
		) {
			$main_entity = array();

			foreach ( $data['questions'] as $question ) {

				if ( ! is_array( $question ) ) {
					continue;
				}

				if (
					empty( $question['question'] ) ||
					! isset( $question['answer'] )
				) {
					continue;
				}

				$main_entity[] = array(
					'@type'          => 'Question',
					'name'           => $question['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'   => $question['answer'],
					),
				);
			}

			if ( ! empty( $main_entity ) ) {
				$schema['mainEntity'] = $main_entity;
			}
		}

		if ( empty( $schema['mainEntity'] ) ) {
			return array();
		}

		return apply_filters(
			'schema_frameworks_faq_schema',
			$schema,
			$data,
			$page,
			$context
		);
	}

	private function has_valid_questions( $items ) {
		if ( ! is_array( $items ) ) { return false; }
		foreach ( $items as $item ) {
			if ( is_array( $item ) && ! empty( $item['question'] ) && ! empty( $item['answer'] ) ) { return true; }
		}
		return false;
	}

	private function get_schema_id() {

		$page = $this->schema_manager->get_current_page_configuration();

		if ( ! empty( $page['url'] ) ) {
			return trailingslashit( $page['url'] ) . '#faq';
		}

		if ( ! empty( $page['path'] ) ) {
			return trailingslashit(
				home_url(
					'/' . trim( $page['path'], '/' ) . '/'
				)
			) . '#faq';
		}

		$url = $this->schema_manager->get_current_page_url();

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return trailingslashit( $url ) . '#faq';
	}
}