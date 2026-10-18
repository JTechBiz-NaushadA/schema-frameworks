<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Organization_Resolver {

	public static function resolve( Config $config ) {

		$organization = $config->get(
			'organization',
			array()
		);

		if (
			empty( $organization )
			|| ! is_array( $organization )
		) {
			return array();
		}

		$organization['address'] = $config->get(
			'organization.address',
			array()
		);

		$organization['same_as'] = $config->get(
			'organization.same_as',
			array()
		);

		return apply_filters(
			'schema_frameworks_resolved_organization',
			$organization,
			$config
		);
	}
	
	public static function get_id( Config $config ) {

		$organization = self::resolve( $config );

		$url = '';

		if (
			! empty( $organization['url'] )
		) {
			$url = $organization['url'];
		}

		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}

		return trailingslashit(
			esc_url_raw( $url )
		) . '#organization';
	}
}