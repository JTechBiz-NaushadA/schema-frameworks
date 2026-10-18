<?php

namespace SchemaFrameworks\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Condition_Manager {

	private $conditions = array();

	public function init() {
		$defaults = array(
			'singular'   => Singular::class,
			'archive'    => Archive::class,
			'front_page' => Front_Page::class,
			'page'       => Page::class,
			'post'       => Post::class,
			'post_type'  => Post_Type::class,
			'page_id'    => Page_ID::class,
			'url'        => URL::class,
			'path'       => Path::class,
			'category'   => Category::class,
			'tag'        => Tag::class,
		);

		foreach ( $defaults as $id => $class ) {
			$this->register( $id, $class );
		}

		do_action(
			'schema_frameworks_register_conditions',
			$this
		);
	}

	public function register( $id, $class ) {
		if ( empty( $id ) || empty( $class ) || ! class_exists( $class ) ) {
			return false;
		}

		$condition = new $class();
		if ( ! $condition instanceof Condition_Interface ) {
			return false;
		}

		$this->conditions[ $id ] = $condition;
		return true;
	}

	public function has( $id ) {
		return isset( $this->conditions[ $id ] );
	}

	public function get( $id ) {
		return $this->conditions[ $id ] ?? null;
	}

	public function all() {
		return $this->conditions;
	}

	public function matches( $configuration, Request_Context $context ) {
		if ( empty( $configuration ) ) {
			return true;
		}

		if ( ! is_array( $configuration ) ) {
			return false;
		}

		$mode = 'all';
		$conditions = $configuration;

		if ( isset( $configuration['conditions'] ) && is_array( $configuration['conditions'] ) ) {
			$conditions = $configuration['conditions'];
			$mode = isset( $configuration['match'] )
				? strtolower( (string) $configuration['match'] )
				: 'all';
		}

		$mode = in_array( $mode, array( 'all', 'any' ), true ) ? $mode : 'all';
		$results = array();

		foreach ( $conditions as $id => $value ) {
			if ( ! $this->has( $id ) ) {
				return false;
			}

			$results[] = (bool) $this->get( $id )->matches( $context, $value );
		}

		if ( empty( $results ) ) {
			return true;
		}

		return 'any' === $mode
			? in_array( true, $results, true )
			: ! in_array( false, $results, true );
	}
}