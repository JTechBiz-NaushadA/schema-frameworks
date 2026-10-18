<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/**
	 * Configuration format version.
	 */
	'config_version' => 1,

	/**
	 * General plugin settings.
	 */
	'general' => array(
		'enabled' => true,
	),

	/**
	 * Schema modules.
	 */
	'modules' => array(
		'software_application' => false,
		'product'              => false,
		'service'              => false,
		'faq'                  => false,
		'howto'                => false,
		'tech_article'         => false,
		'digital_document'     => false,
		'comment'              => false,
		'defined_term'         => false,
		'web_application'      => false,
		'webpage'              => false,
		'breadcrumb'           => false,
		'image_object'         => false,
		'person'               => false,
		'organization'         => false,
	),

	/**
	 * Organization configuration.
	 */
	'organization' => array(
		'name'           => '',
		'alternate_name' => '',
		'legal_name'     => '',
		'url'            => '',
		'logo'           => '',
		'description'    => '',
		'founding_date'  => '',
		'telephone'      => '',
		'email'          => '',
		'address'        => array(
			'street'      => '',
			'locality'    => '',
			'region'      => '',
			'postal_code' => '',
			'country'     => '',
		),
		'same_as'        => array(),
	),
	
	/**
	 * Page-specific schema configuration.
	 */
	'pages' => array(),

	'content_support' => array(
		'post_module_enabled' => false,
	),

	'post_type_mappings' => array(),

	'comments' => array(
		'max_per_post' => 10,
	),

);