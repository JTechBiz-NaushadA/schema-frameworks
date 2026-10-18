<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Tag extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'tag'; }
	public function get_label() { return 'Tag'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$actual = $context->get( 'tags', array() );
		$expected = array_map( 'sanitize_title', $this->values( $configuration ) );
		return ! empty( array_intersect( $actual, $expected ) );
	}
}