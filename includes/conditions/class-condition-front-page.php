<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Front_Page extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'front_page'; }
	public function get_label() { return 'Front Page'; }
	public function matches( Request_Context $context, $configuration = true ) {
		return $this->boolean_match( $configuration, $context->get( 'is_front_page', false ) );
	}
}