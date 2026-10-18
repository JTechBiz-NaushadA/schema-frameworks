<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Page extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'page'; }
	public function get_label() { return 'Page'; }
	public function matches( Request_Context $context, $configuration = true ) {
		return $this->boolean_match( $configuration, 'page' === $context->get( 'post_type', '' ) && $context->get( 'is_singular', false ) );
	}
}