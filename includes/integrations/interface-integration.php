<?php

namespace SchemaFrameworks\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Integration_Interface {

	public function get_id();

	public function get_name();

	public function is_available();

	public function get_organization_data();
}