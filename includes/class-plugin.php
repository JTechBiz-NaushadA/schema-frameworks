<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static $instance = null;

	private $config;

	private $registry;

	private $schema_manager;

	private $condition_manager;

	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {

		$this->config = new Config();
		$this->registry = new Registry();
		$this->schema_manager = new Schema_Manager(
			$this->config,
			$this->registry
		);

		$this->condition_manager = new Conditions\Condition_Manager();
		$this->schema_manager->set_condition_manager( $this->condition_manager );

		$this->init();
	}

	private function init() {

		do_action( 'schema_frameworks_before_init', $this );

		$yoast = new Integrations\Yoast(
			$this->config,
			$this->registry,
			$this->schema_manager
		);

		$yoast->init();

		$this->schema_manager->init();

		$this->condition_manager->init();
		
		$admin = new \SchemaFrameworks\Admin\Admin(
			$this->config,
			$this->registry
		);

		$admin->init();

		do_action( 'schema_frameworks_init', $this );

	}

	public function config() {
		return $this->config;
	}

	public function registry() {
		return $this->registry;
	}

	public function schema_manager() {
		return $this->schema_manager;
	}

	public function condition_manager() {
		return $this->condition_manager;
	}

	public function validate_graph( $nodes ) {
		return Graph_Validator::validate_graph( $nodes );
	}
}