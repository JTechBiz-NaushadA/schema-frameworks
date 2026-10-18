<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Page_ID extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'page_id'; }
	public function get_label() { return 'Page ID'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$expected = $this->values( $configuration );
		if ( empty( $expected ) ) { return false; }
		foreach ( $expected as $id ) {
			if ( absint( $id ) === (int) $context->get( 'post_id', 0 ) ) { return true; }
		}
		return false;
	}
}