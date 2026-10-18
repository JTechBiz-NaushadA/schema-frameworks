<?php
namespace SchemaFrameworks\Conditions;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Path extends Condition_Value implements Condition_Interface {
	public function get_id() { return 'path'; }
	public function get_label() { return 'Path'; }
	public function matches( Request_Context $context, $configuration = true ) {
		$current = trim( (string) $context->get( 'path', '' ), '/' );
		foreach ( $this->values( $configuration ) as $path ) {
			if ( $current === trim( wp_parse_url( $path, PHP_URL_PATH ) ?: $path, '/' ) ) { return true; }
		}
		return false;
	}
}