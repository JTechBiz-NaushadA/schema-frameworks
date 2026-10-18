<?php

namespace SchemaFrameworks\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Integrations\Adapters\Yoast_Organization;

class Integration_Detector {

	public static function get_available() {

		$integrations = array();

		$yoast = new Yoast_Organization();

		if ( $yoast->is_available() ) {

			$integrations['yoast'] = array(
				'id'    => $yoast->get_id(),
				'name'  => $yoast->get_name(),
				'class' => Yoast_Organization::class,
			);
		}

		return apply_filters(
			'schema_frameworks_available_integrations',
			$integrations
		);
	}

	public static function is_available( $id ) {

		$available = self::get_available();

		return isset( $available[ $id ] );
	}
}