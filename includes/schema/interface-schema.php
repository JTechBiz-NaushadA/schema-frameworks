<?php

namespace SchemaFrameworks\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Schema_Interface {

	public function get_id();

	public function is_needed( $context = null );

	public function generate( $context = null );
}