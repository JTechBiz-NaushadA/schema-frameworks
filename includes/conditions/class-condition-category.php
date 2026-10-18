<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Category extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'category'; }
	public function get_label() { return 'Category'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$actual = $context->get( 'categories', array() );
		$expected = array_map( 'sanitize_title', $this->values( $configuration ) );
		return ! empty( array_intersect( $actual, $expected ) );
	}
}