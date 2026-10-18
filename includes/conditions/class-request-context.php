<?php

namespace SchemaFrameworks\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Request_Context {

	private $data = array();

	public function __construct( array $data = array() ) {
		$this->data = array_merge(
			array(
				'post_id'      => 0,
				'post_type'    => '',
				'url'          => '',
				'path'         => '',
				'is_singular'  => false,
				'is_archive'   => false,
				'is_front_page'=> false,
				'categories'   => array(),
				'tags'         => array(),
			),
			$data
		);

		$this->data['post_id'] = absint( $this->data['post_id'] );
		$this->data['post_type'] = sanitize_key( $this->data['post_type'] );
		$this->data['is_singular'] = (bool) $this->data['is_singular'];
		$this->data['is_archive'] = (bool) $this->data['is_archive'];
		$this->data['is_front_page'] = (bool) $this->data['is_front_page'];
		$this->data['categories'] = $this->normalize_values( $this->data['categories'] );
		$this->data['tags'] = $this->normalize_values( $this->data['tags'] );
	}

	public static function from_current_request() {
		$post_id = is_singular() ? (int) get_queried_object_id() : 0;
		$post_type = $post_id ? get_post_type( $post_id ) : '';
		$url = $post_id ? get_permalink( $post_id ) : home_url( '/' );

		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? wp_unslash( $_SERVER['REQUEST_URI'] )
			: '';

		$path = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			$path = '';
		}

		$categories = array();
		$tags = array();

		if ( $post_id ) {
			$categories = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );
			$tags = wp_get_post_tags( $post_id, array( 'fields' => 'slugs' ) );
		}

		return new self(
			array(
				'post_id'       => $post_id,
				'post_type'     => $post_type,
				'url'           => $url,
				'path'          => $path,
				'is_singular'   => is_singular(),
				'is_archive'    => is_archive(),
				'is_front_page' => is_front_page(),
				'categories'    => $categories,
				'tags'          => $tags,
			)
		);
	}

	public function get( $key, $default = null ) {
		return array_key_exists( $key, $this->data )
			? $this->data[ $key ]
			: $default;
	}

	public function all() {
		return $this->data;
	}

	private function normalize_values( $values ) {
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$result = array();
		foreach ( $values as $value ) {
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				$result[] = sanitize_title( (string) $value );
			}
		}

		return array_values( array_unique( $result ) );
	}
}