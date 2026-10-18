<?php

namespace SchemaFrameworks\Yoast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

class Schema_Piece extends Abstract_Schema_Piece {

	public $identifier;

	private $schema;

	private $graph_assembler;

	public function __construct( $schema, $graph_assembler = null ) {

		$this->schema     = $schema;
		$this->identifier = $schema->get_id();
		$this->graph_assembler = $graph_assembler;
	}

	public function is_needed() {

		return $this->schema->is_needed(
			$this->context
		);
	}

	public function generate() {

		$schema = $this->schema->generate(
			$this->context
		);

		if ( $this->graph_assembler ) {
			$schema = $this->graph_assembler->assemble(
				$schema,
				$this->schema->get_id(),
				$this->context
			);
		}

		return $schema;
	}
}