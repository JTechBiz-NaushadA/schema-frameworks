<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class URL extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'url'; }
	public function get_label() { return 'URL'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$current = untrailingslashit( esc_url_raw( (string) $context->get( 'url', '' ) ) );
		if ( '' === $current ) { return false; }
		foreach ( $this->values( $configuration ) as $url ) {
			if ( $current === untrailingslashit( esc_url_raw( $url ) ) ) { return true; }
		}
		return false;
	}
}