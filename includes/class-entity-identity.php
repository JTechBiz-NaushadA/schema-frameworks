<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Entity_Identity {

	private static $fragments = array(
		'software_application' => 'software',
		'product'              => 'product',
		'service'              => 'service',
		'faq'                  => 'faq',
		'howto'                => 'howto',
		'tech_article'         => 'techarticle',
		'digital_document'     => 'digitaldocument',
		'defined_term'         => 'definedterm',
		'web_application'      => 'webapplication',
		'webpage'              => 'webpage',
		'breadcrumb'           => 'breadcrumb',
		'person'               => 'person',
	);

	private static $types = array(
		'software_application' => 'SoftwareApplication',
		'product'              => 'Product',
		'service'              => 'Service',
		'faq'                  => 'FAQPage',
		'howto'                => 'HowTo',
		'tech_article'         => 'TechArticle',
		'digital_document'     => 'DigitalDocument',
		'defined_term'         => 'DefinedTerm',
		'web_application'      => 'WebApplication',
		'webpage'              => 'WebPage',
		'breadcrumb'           => 'BreadcrumbList',
		'image_object'         => 'ImageObject',
		'person'               => 'Person',
		'organization'         => 'Organization',
		'organization_logo'    => 'ImageObject',
	);

	public static function for_module( $module_id, $page = array(), $manager = null ) {

		$module_id = sanitize_key( $module_id );
		$page_context = $page instanceof Page_Schema_Context ? $page : new Page_Schema_Context( is_array( $page ) ? $page : array() );

		if ( 'person' === $module_id && $page_context->explicit_entity_id( 'person' ) ) {
			return trim( sanitize_text_field( $page_context->explicit_entity_id( 'person' ) ) );
		}

		$page = $page_context->to_legacy_array();

		if ( 'organization' === $module_id && $manager && method_exists( $manager, 'config' ) ) {
			return Organization_Resolver::get_id( $manager->config() );
		}

		if ( 'organization_logo' === $module_id && $manager && method_exists( $manager, 'config' ) ) {
			return preg_replace(
				'/#organization$/',
				'#organization-logo',
				Organization_Resolver::get_id( $manager->config() )
			);
		}

		if ( 'image_object' === $module_id ) {
			if ( ! empty( $page['image_object']['url'] ) ) {
				return esc_url_raw( $page['image_object']['url'] );
			}
			return '';
		}

		if ( ! isset( self::$fragments[ $module_id ] ) ) {
			return '';
		}

		$base = self::page_base_url( $page, $manager );
		return trailingslashit( $base ) . '#' . self::$fragments[ $module_id ];
	}

	public static function page_base_url( $page = array(), $manager = null ) {

		$url = '';

		if ( is_array( $page ) && ! empty( $page['url'] ) ) {
			$url = esc_url_raw( $page['url'] );
		}

		if ( ! $url && is_array( $page ) && ! empty( $page['path'] ) ) {
			$url = home_url( '/' . trim( $page['path'], '/' ) . '/' );
		}

		if ( ! $url && $manager && method_exists( $manager, 'get_current_page_url' ) ) {
			$url = $manager->get_current_page_url();
		}

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return esc_url_raw( $url );
	}

	public static function type_for_module( $module_id ) {
		return isset( self::$types[ $module_id ] ) ? self::$types[ $module_id ] : '';
	}
}