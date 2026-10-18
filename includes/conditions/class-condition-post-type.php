<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Post_Type extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'post_type'; }
	public function get_label() { return 'Post Type'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$expected = $this->values( $configuration );
		if ( empty( $expected ) ) { return false; }
		return in_array( $context->get( 'post_type', '' ), array_map( 'sanitize_key', $expected ), true );
	}
}