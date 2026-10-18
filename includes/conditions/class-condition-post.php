<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Post extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'post'; }
	public function get_label() { return 'Post'; }
	public function matches( Request_Context $context, $configuration = true ) {
		return $this->boolean_match( $configuration, 'post' === $context->get( 'post_type', '' ) && $context->get( 'is_singular', false ) );
	}
}