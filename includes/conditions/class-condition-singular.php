<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Singular extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'singular'; }
	public function get_label() { return 'Singular'; }
	public function matches( Request_Context $context, $configuration = true ) {
		return $this->boolean_match( $configuration, $context->get( 'is_singular', false ) );
	}
}