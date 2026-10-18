<?php

namespace SchemaFrameworks\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Condition_Interface {

	public function get_id();
	public function get_label();
	public function matches( Request_Context $context, $configuration = true );
}