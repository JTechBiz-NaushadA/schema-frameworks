<?php

namespace SchemaFrameworks\Integrations\Adapters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Integrations\Integration_Interface;

class Yoast_Organization implements Integration_Interface {

	public function get_id() {

		return 'yoast';
	}

	public function get_name() {

		return 'Yoast SEO';
	}

	public function is_available() {

		return (
			defined( 'WPSEO_VERSION' )
			|| class_exists(
				'\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece'
			)
		);
	}

	public function get_organization_data() {

		if ( ! $this->is_available() ) {
			return array();
		}

		$settings = get_option(
			'wpseo_titles',
			array()
		);

		if ( ! is_array( $settings ) ) {
			return array();
		}

		$organization = array();

		if ( ! empty( $settings['website_name'] ) ) {
			$organization['name'] = sanitize_text_field(
				$settings['website_name']
			);
		}

		if ( ! empty( $settings['alternate_website_name'] ) ) {
			$organization['alternate_name'] = sanitize_text_field(
				$settings['alternate_website_name']
			);
		}

		if ( ! empty( $settings['company_logo_id'] ) ) {

			$logo_url = wp_get_attachment_image_url(
				(int) $settings['company_logo_id'],
				'full'
			);

			if ( $logo_url ) {
				$organization['logo'] = esc_url_raw(
					$logo_url
				);
			}
		}

		if (
			empty( $organization['logo'] ) &&
			! empty( $settings['company_logo'] )
		) {
			$organization['logo'] = esc_url_raw(
				$settings['company_logo']
			);
		}

		$map = array(
			'description'   => 'org-description',
			'email'         => 'org-email',
			'telephone'     => 'org-phone',
			'legal_name'    => 'org-legal-name',
			'founding_date' => 'org-founding-date',
		);

		foreach ( $map as $output_key => $setting_key ) {

			if ( empty( $settings[ $setting_key ] ) ) {
				continue;
			}

			$organization[ $output_key ] = sanitize_text_field(
				$settings[ $setting_key ]
			);
		}

		$organization['url'] = esc_url_raw(
			home_url( '/' )
		);

		return $organization;
	}
}