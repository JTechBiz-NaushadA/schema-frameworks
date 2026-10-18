<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Archive extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'archive'; }
	public function get_label() { return 'Archive'; }
	public function matches( Request_Context $context, $configuration = true ) {
		return $this->boolean_match( $configuration, $context->get( 'is_archive', false ) );
	}
}