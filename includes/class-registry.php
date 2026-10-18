<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	private $schemas = array();

	public function register( $id, $class, $options = array() ) {

		if ( empty( $id ) || empty( $class ) ) {
			return false;
		}

		$options = $this->normalize_module_metadata( $id, $class, $options );

		$this->schemas[ $id ] = array(
			'class'    => $class,
			'options'  => $options,
			'metadata' => $this->extract_module_metadata( $id, $class, $options ),
		);

		return true;
	}

	private function normalize_module_metadata( $id, $class, $options ) {
		$type_map = array(
			'software_application' => 'SoftwareApplication',
			'product'              => 'Product',
			'service'              => 'Service',
			'faq'                  => 'FAQPage',
			'howto'                => 'HowTo',
			'tech_article'         => 'TechArticle',
			'digital_document'     => 'DigitalDocument',
			'comment'              => 'Comment',
			'defined_term'         => 'DefinedTerm',
			'web_application'      => 'WebApplication',
			'webpage'              => 'WebPage',
			'breadcrumb'           => 'BreadcrumbList',
			'image_object'         => 'ImageObject',
			'organization_logo'    => 'ImageObject',
			'person'               => 'Person',
			'organization'         => 'Organization',
		);

		$defaults = array(
			'id'           => (string) $id,
			'schema_type'  => isset( $type_map[ $id ] ) ? $type_map[ $id ] : '',
			'label'        => isset( $options['label'] ) ? (string) $options['label'] : ucwords( str_replace( array( '_', '-' ), ' ', $id ) ),
			'description'  => isset( $options['description'] ) ? (string) $options['description'] : '',
			'category'     => isset( $options['category'] ) ? (string) $options['category'] : 'content',
			'capabilities' => isset( $options['capabilities'] ) && is_array( $options['capabilities'] ) ? $options['capabilities'] : array(),
			'config_fields'=> isset( $options['config_fields'] ) && is_array( $options['config_fields'] ) ? $options['config_fields'] : array(),
			'admin_managed' => ! empty( $options['admin_managed'] ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! array_key_exists( $key, $options ) || null === $options[ $key ] ) {
				$options[ $key ] = $value;
			}
		}

		return $options;
	}
	
	private function extract_module_metadata( $id, $class, $options ) {
		return array(
			'id'            => (string) $id,
			'class'         => (string) $class,
			'schema_type'   => isset( $options['schema_type'] ) ? (string) $options['schema_type'] : '',
			'label'         => isset( $options['label'] ) ? (string) $options['label'] : (string) $id,
			'description'   => isset( $options['description'] ) ? (string) $options['description'] : '',
			'category'      => isset( $options['category'] ) ? (string) $options['category'] : 'content',
			'capabilities'  => isset( $options['capabilities'] ) && is_array( $options['capabilities'] ) ? $options['capabilities'] : array(),
			'config_fields' => isset( $options['config_fields'] ) && is_array( $options['config_fields'] ) ? $options['config_fields'] : array(),
			'admin_managed' => ! empty( $options['admin_managed'] ),
		);
	}

	public function get_metadata( $id ) {
		return isset( $this->schemas[ $id ]['metadata'] ) ? $this->schemas[ $id ]['metadata'] : null;
	}

	public function get_all_metadata() {
		$metadata = array();
		foreach ( $this->schemas as $id => $schema ) {
			if ( isset( $schema['metadata'] ) ) {
				$metadata[ $id ] = $schema['metadata'];
			}
		}
		return $metadata;
	}

	public function get_schema_type( $id ) {
		$metadata = $this->get_metadata( $id );
		return is_array( $metadata ) && '' !== $metadata['schema_type'] ? $metadata['schema_type'] : null;
	}

	public function has( $id ) {
		return isset( $this->schemas[ $id ] );
	}

	public function get( $id ) {
		return $this->schemas[ $id ] ?? null;
	}

	public function register_default_schemas( $config, $schema_manager ) {

		$base = array(
			'config'         => $config,
			'schema_manager' => $schema_manager,
			'admin_managed'  => true,
		);

		$definition = $base;
		$definition['label'] = 'SoftwareApplication';
		$definition['description'] = 'Describe software applications and software products.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'applicationCategory', 'type' => 'text', 'label' => 'Application Category' ),
			array( 'key' => 'applicationSubCategory', 'type' => 'text', 'label' => 'Application Subcategory' ),
			array( 'key' => 'operatingSystem', 'type' => 'text', 'label' => 'Operating System' ),
			array( 'key' => 'softwareVersion', 'type' => 'text', 'label' => 'Software Version' ),
			array( 'key' => 'releaseNotes', 'type' => 'url', 'label' => 'Release Notes URL' ),
			array( 'key' => 'featureList', 'type' => 'text_list', 'label' => 'Feature List', 'description' => 'One feature per line.' ),
			array( 'key' => 'keywords', 'type' => 'text_list', 'label' => 'Keywords', 'description' => 'One keyword per line.' ),
			array( 'key' => 'isAccessibleForFree', 'type' => 'boolean', 'label' => 'Accessible for Free' ),
			array( 'key' => 'author', 'type' => 'text', 'label' => 'Author' ),
			array( 'key' => 'publisher', 'type' => 'text', 'label' => 'Publisher' ),
			array( 'key' => 'offers', 'type' => 'group', 'label' => 'Offer', 'fields' => array(
				array( 'key' => 'price', 'type' => 'number', 'label' => 'Price' ),
				array( 'key' => 'priceCurrency', 'type' => 'text', 'label' => 'Price Currency' ),
				array( 'key' => 'availability', 'type' => 'url', 'label' => 'Availability URL' ),
				array( 'key' => 'url', 'type' => 'url', 'label' => 'Offer URL' ),
			) ),
		);
		$this->register( 'software_application', \SchemaFrameworks\Schema\Software_Application::class, $definition );

		$definition = $base;
		$definition['label'] = 'Product';
		$definition['description'] = 'Describe products and their structured product information.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'sku', 'type' => 'text', 'label' => 'SKU' ),
			array( 'key' => 'mpn', 'type' => 'text', 'label' => 'MPN' ),
			array( 'key' => 'brand', 'type' => 'text', 'label' => 'Brand' ),
			array( 'key' => 'category', 'type' => 'text', 'label' => 'Category' ),
			array( 'key' => 'model', 'type' => 'text', 'label' => 'Model' ),
			array( 'key' => 'manufacturer', 'type' => 'text', 'label' => 'Manufacturer', 'description' => 'The framework manages the Organization relationship for this property.' ),
			array( 'key' => 'offers', 'type' => 'group', 'label' => 'Offer', 'fields' => array(
				array( 'key' => 'price', 'type' => 'number', 'label' => 'Price' ),
				array( 'key' => 'priceCurrency', 'type' => 'text', 'label' => 'Price Currency' ),
				array( 'key' => 'availability', 'type' => 'url', 'label' => 'Availability URL' ),
				array( 'key' => 'url', 'type' => 'url', 'label' => 'Offer URL' ),
			) ),
			array( 'key' => 'aggregateRating', 'type' => 'group', 'label' => 'Aggregate Rating', 'fields' => array(
				array( 'key' => 'ratingValue', 'type' => 'number', 'label' => 'Rating Value' ),
				array( 'key' => 'reviewCount', 'type' => 'number', 'label' => 'Review Count' ),
			) ),
			array( 'key' => 'review', 'type' => 'group', 'label' => 'Review', 'fields' => array(
				array( 'key' => 'author', 'type' => 'text', 'label' => 'Author' ),
				array( 'key' => 'ratingValue', 'type' => 'number', 'label' => 'Rating Value' ),
				array( 'key' => 'body', 'type' => 'textarea', 'label' => 'Review Body' ),
				array( 'key' => 'datePublished', 'type' => 'date', 'label' => 'Date Published' ),
			) ),
		);
		$this->register( 'product', \SchemaFrameworks\Schema\Product::class, $definition );

		$definition = $base;
		$definition['label'] = 'Service';
		$definition['description'] = 'Describe services offered by an organization or provider.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'serviceType', 'type' => 'text', 'label' => 'Service Type' ),
			array( 'key' => 'category', 'type' => 'text', 'label' => 'Category' ),
			array( 'key' => 'areaServed', 'type' => 'text', 'label' => 'Area Served' ),
			array( 'key' => 'brand', 'type' => 'text', 'label' => 'Brand' ),
			array( 'key' => 'termsOfService', 'type' => 'url', 'label' => 'Terms of Service URL' ),
			array( 'key' => 'offers', 'type' => 'group', 'label' => 'Offer', 'fields' => array(
				array( 'key' => 'price', 'type' => 'number', 'label' => 'Price' ),
				array( 'key' => 'priceCurrency', 'type' => 'text', 'label' => 'Price Currency' ),
				array( 'key' => 'url', 'type' => 'url', 'label' => 'Offer URL' ),
			) ),
		);
		$this->register( 'service', \SchemaFrameworks\Schema\Service::class, $definition );

		$definition = $base;
		$definition['label'] = 'FAQ';
		$definition['description'] = 'Add FAQPage structured data to eligible pages.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'questions', 'type' => 'repeater', 'label' => 'Questions', 'description' => 'Add each FAQ question and its accepted answer.', 'fields' => array(
				array( 'key' => 'question', 'type' => 'text', 'label' => 'Question' ),
				array( 'key' => 'answer', 'type' => 'textarea', 'label' => 'Answer' ),
			) ),
		);
		$this->register( 'faq', \SchemaFrameworks\Schema\FAQ::class, $definition );

		$definition = $base;
		$definition['label'] = 'HowTo';
		$definition['description'] = 'Describe instructional how-to content.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'totalTime', 'type' => 'text', 'label' => 'Total Time' ),
			array( 'key' => 'estimatedCost', 'type' => 'text', 'label' => 'Estimated Cost' ),
			array( 'key' => 'tool', 'type' => 'text_list', 'label' => 'Tools', 'description' => 'One tool per line.' ),
			array( 'key' => 'supply', 'type' => 'text_list', 'label' => 'Supplies', 'description' => 'One supply per line.' ),
			array( 'key' => 'steps', 'type' => 'repeater', 'label' => 'Steps', 'description' => 'Add each step in order.', 'fields' => array(
				array( 'key' => 'name', 'type' => 'text', 'label' => 'Step Name' ),
				array( 'key' => 'text', 'type' => 'textarea', 'label' => 'Step Text' ),
				array( 'key' => 'url', 'type' => 'url', 'label' => 'Step URL' ),
				array( 'key' => 'image', 'type' => 'url', 'label' => 'Step Image URL' ),
			) ),
		);
		$this->register( 'howto', \SchemaFrameworks\Schema\HowTo::class, $definition );

		$definition = $base;
		$definition['label'] = 'TechArticle';
		$definition['description'] = 'Describe technical article content.';
		$definition['config_fields'] = array(
			array( 'key' => 'headline', 'type' => 'text', 'label' => 'Headline' ),
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'datePublished', 'type' => 'date', 'label' => 'Date Published' ),
			array( 'key' => 'dateModified', 'type' => 'date', 'label' => 'Date Modified' ),
			array( 'key' => 'author', 'type' => 'text', 'label' => 'Author' ),
			array( 'key' => 'publisher', 'type' => 'text', 'label' => 'Publisher' ),
			array( 'key' => 'about', 'type' => 'text', 'label' => 'About' ),
			array( 'key' => 'proficiencyLevel', 'type' => 'text', 'label' => 'Proficiency Level' ),
			array( 'key' => 'dependencies', 'type' => 'text', 'label' => 'Dependencies' ),
			array( 'key' => 'programmingLanguage', 'type' => 'text_list', 'label' => 'Programming Languages', 'description' => 'One language per line.' ),
			array( 'key' => 'keywords', 'type' => 'text_list', 'label' => 'Keywords', 'description' => 'One keyword per line.' ),
			array( 'key' => 'articleSection', 'type' => 'text', 'label' => 'Article Section' ),
			array( 'key' => 'wordCount', 'type' => 'number', 'label' => 'Word Count' ),
			array( 'key' => 'inLanguage', 'type' => 'text', 'label' => 'Language' ),
		);
		$this->register( 'tech_article', \SchemaFrameworks\Schema\Tech_Article::class, $definition );

		$definition = $base;
		$definition['label'] = 'DigitalDocument';
		$definition['description'] = 'Describe digital documents and downloadable resources.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'contentUrl', 'type' => 'url', 'label' => 'Content URL' ),
			array( 'key' => 'encodingFormat', 'type' => 'text', 'label' => 'Encoding Format' ),
			array( 'key' => 'datePublished', 'type' => 'date', 'label' => 'Date Published' ),
			array( 'key' => 'dateModified', 'type' => 'date', 'label' => 'Date Modified' ),
			array( 'key' => 'inLanguage', 'type' => 'text', 'label' => 'Language' ),
			array( 'key' => 'author', 'type' => 'text', 'label' => 'Author' ),
			array( 'key' => 'publisher', 'type' => 'text', 'label' => 'Publisher' ),
			array( 'key' => 'about', 'type' => 'text', 'label' => 'About' ),
			array( 'key' => 'mentions', 'type' => 'text_list', 'label' => 'Mentions', 'description' => 'One mention per line.' ),
			array( 'key' => 'keywords', 'type' => 'text_list', 'label' => 'Keywords', 'description' => 'One keyword per line.' ),
			array( 'key' => 'license', 'type' => 'url', 'label' => 'License URL' ),
			array( 'key' => 'version', 'type' => 'text', 'label' => 'Version' ),
			array( 'key' => 'fileSize', 'type' => 'text', 'label' => 'File Size' ),
		);
		$this->register( 'digital_document', \SchemaFrameworks\Schema\Digital_Document::class, $definition );

		$definition = $base;
		$definition['label'] = 'DefinedTerm';
		$definition['description'] = 'Describe glossary and terminology definitions.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'inDefinedTermSet', 'type' => 'url', 'label' => 'Defined Term Set URL' ),
			array( 'key' => 'termCode', 'type' => 'text', 'label' => 'Term Code' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'sameAs', 'type' => 'url_list', 'label' => 'SameAs URLs', 'description' => 'One URL per line.' ),
		);
		$this->register( 'defined_term', \SchemaFrameworks\Schema\Defined_Term::class, $definition );

		$definition = $base;
		$definition['label'] = 'WebApplication';
		$definition['description'] = 'Describe browser-based web applications.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'applicationCategory', 'type' => 'text', 'label' => 'Application Category' ),
			array( 'key' => 'applicationSubCategory', 'type' => 'text', 'label' => 'Application Subcategory' ),
			array( 'key' => 'operatingSystem', 'type' => 'text', 'label' => 'Operating System' ),
			array( 'key' => 'browserRequirements', 'type' => 'text', 'label' => 'Browser Requirements' ),
			array( 'key' => 'softwareVersion', 'type' => 'text', 'label' => 'Software Version' ),
			array( 'key' => 'releaseNotes', 'type' => 'url', 'label' => 'Release Notes URL' ),
			array( 'key' => 'featureList', 'type' => 'text_list', 'label' => 'Feature List', 'description' => 'One feature per line.' ),
			array( 'key' => 'permissions', 'type' => 'text_list', 'label' => 'Permissions', 'description' => 'One permission per line.' ),
			array( 'key' => 'storageRequirements', 'type' => 'text', 'label' => 'Storage Requirements' ),
			array( 'key' => 'memoryRequirements', 'type' => 'text', 'label' => 'Memory Requirements' ),
			array( 'key' => 'isAccessibleForFree', 'type' => 'boolean', 'label' => 'Accessible for Free' ),
			array( 'key' => 'offers', 'type' => 'group', 'label' => 'Offer', 'fields' => array(
				array( 'key' => 'price', 'type' => 'number', 'label' => 'Price' ),
				array( 'key' => 'priceCurrency', 'type' => 'text', 'label' => 'Price Currency' ),
				array( 'key' => 'url', 'type' => 'url', 'label' => 'Offer URL' ),
			) ),
			array( 'key' => 'provider', 'type' => 'text', 'label' => 'Provider', 'description' => 'The framework may replace this with the configured Organization relationship.' ),
			array( 'key' => 'author', 'type' => 'text', 'label' => 'Author' ),
			array( 'key' => 'publisher', 'type' => 'text', 'label' => 'Publisher' ),
			array( 'key' => 'inLanguage', 'type' => 'text', 'label' => 'Language' ),
		);
		$this->register( 'web_application', \SchemaFrameworks\Schema\Web_Application::class, $definition );

		$definition = $base;
		$definition['label'] = 'ImageObject';
		$definition['description'] = 'Add standalone ImageObject structured data.';
		$definition['config_fields'] = array(
			array( 'key' => 'url', 'type' => 'url', 'label' => 'Image URL', 'description' => 'Required to generate the ImageObject.' ),
			array( 'key' => 'caption', 'type' => 'text', 'label' => 'Caption' ),
			array( 'key' => 'inLanguage', 'type' => 'text', 'label' => 'Language' ),
			array( 'key' => 'width', 'type' => 'number', 'label' => 'Width' ),
			array( 'key' => 'height', 'type' => 'number', 'label' => 'Height' ),
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'alternateName', 'type' => 'text', 'label' => 'Alternate Name' ),
			array( 'key' => 'encodingFormat', 'type' => 'text', 'label' => 'Encoding Format' ),
			array( 'key' => 'contentSize', 'type' => 'text', 'label' => 'Content Size' ),
			array( 'key' => 'uploadDate', 'type' => 'date', 'label' => 'Upload Date' ),
		);
		$this->register( 'image_object', \SchemaFrameworks\Schema\Image_Object::class, $definition );

		$definition = $base;
		$definition['label'] = 'Person';
		$definition['description'] = 'Describe people represented by the site or its content.';
		$definition['config_fields'] = array(
			array( 'key' => 'name', 'type' => 'text', 'label' => 'Name' ),
			array( 'key' => 'url', 'type' => 'url', 'label' => 'URL' ),
			array( 'key' => 'image', 'type' => 'url', 'label' => 'Image URL' ),
			array( 'key' => 'description', 'type' => 'textarea', 'label' => 'Description' ),
			array( 'key' => 'jobTitle', 'type' => 'text', 'label' => 'Job Title' ),
			array( 'key' => 'givenName', 'type' => 'text', 'label' => 'Given Name' ),
			array( 'key' => 'familyName', 'type' => 'text', 'label' => 'Family Name' ),
			array( 'key' => 'additionalName', 'type' => 'text', 'label' => 'Additional Name' ),
			array( 'key' => 'alternateName', 'type' => 'text', 'label' => 'Alternate Name' ),
			array( 'key' => 'email', 'type' => 'text', 'label' => 'Email' ),
			array( 'key' => 'telephone', 'type' => 'text', 'label' => 'Telephone' ),
			array( 'key' => 'worksFor', 'type' => 'text', 'label' => 'Works For' ),
			array( 'key' => 'knowsAbout', 'type' => 'text_list', 'label' => 'Knows About', 'description' => 'One subject per line.' ),
			array( 'key' => 'sameAs', 'type' => 'url_list', 'label' => 'SameAs URLs', 'description' => 'One URL per line.' ),
			array( 'key' => 'birthDate', 'type' => 'date', 'label' => 'Birth Date' ),
			array( 'key' => 'gender', 'type' => 'text', 'label' => 'Gender' ),
			array( 'key' => 'nationality', 'type' => 'text', 'label' => 'Nationality' ),
		);
		$this->register( 'person', \SchemaFrameworks\Schema\Person::class, $definition );

		$this->register(
			'webpage',
			\SchemaFrameworks\Schema\WebPage::class,
			array( 'config' => $config, 'schema_manager' => $schema_manager, 'admin_managed' => false, 'label' => 'WebPage', 'description' => 'Internal Yoast WebPage integration; managed automatically.' )
		);

		$this->register(
			'breadcrumb',
			\SchemaFrameworks\Schema\Breadcrumb::class,
			array( 'config' => $config, 'schema_manager' => $schema_manager, 'admin_managed' => false, 'label' => 'Breadcrumb', 'description' => 'Internal Yoast Breadcrumb integration; managed automatically.' )
		);

		$this->register(
			'comment',
			\SchemaFrameworks\Schema\Comment_Schema::class,
			array( 'config' => $config, 'schema_manager' => $schema_manager, 'admin_managed' => true, 'label' => 'Approved Post Comments', 'description' => 'Optionally include approved public comments on supported Posts and custom post types.' )
		);

		$this->register(
			'organization_logo',
			\SchemaFrameworks\Schema\Organization_Logo::class,
			array( 'config' => $config, 'schema_manager' => $schema_manager, 'admin_managed' => false, 'label' => 'Organization Logo', 'description' => 'Internal dependency for the Organization logo ImageObject.' )
		);

		$this->register(
			'organization',
			\SchemaFrameworks\Schema\Organization::class,
			array( 'config' => $config, 'schema_manager' => $schema_manager, 'admin_managed' => false, 'label' => 'Organization', 'description' => 'Managed through the dedicated Organization settings.' )
		);

		$this->set_relationship_capabilities( 'product', array(
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'service', array(
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'provider', 'targets' => array( 'organization', 'person' ) ),
		) );
		$this->set_relationship_capabilities( 'software_application', array(
			array( 'property' => 'provider', 'targets' => array( 'organization' ) ),
			array( 'property' => 'author', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'publisher', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'web_application', array(
			array( 'property' => 'provider', 'targets' => array( 'organization' ) ),
			array( 'property' => 'author', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'publisher', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'tech_article', array(
			array( 'property' => 'author', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'publisher', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'about', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'digital_document', array(
			array( 'property' => 'author', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'publisher', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'about', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'person', array(
			array( 'property' => 'worksFor', 'targets' => array( 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'faq', array(
			array( 'property' => 'about', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'howto', array(
			array( 'property' => 'about', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'defined_term', array(
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'tech_article', array(
			array( 'property' => 'author', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'publisher', 'targets' => array( 'organization', 'person' ) ),
			array( 'property' => 'about', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'defined_term', 'person', 'organization' ) ),
			array( 'property' => 'isRelatedTo', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'tech_article', 'digital_document', 'faq', 'howto', 'defined_term', 'person', 'organization' ) ),
		) );
		$this->set_relationship_capabilities( 'webpage', array(
			array( 'property' => 'mainEntity', 'targets' => array( 'product', 'service', 'software_application', 'web_application', 'faq', 'howto', 'tech_article', 'digital_document', 'defined_term', 'person' ) ),
		) );
	}

	public function set_relationship_capabilities( $id, $capabilities ) {
		if ( ! isset( $this->schemas[ $id ] ) || ! is_array( $capabilities ) ) {
			return false;
		}

		$this->schemas[ $id ]['options']['relationships'] = $capabilities;
		return true;
	}

	public function all() {
		return $this->schemas;
	}

	public function get_relationship_capabilities( $id ) {
		$definition = $this->get( $id );
		$options = isset( $definition['options'] ) && is_array( $definition['options'] )
			? $definition['options']
			: array();

		return isset( $options['relationships'] ) && is_array( $options['relationships'] )
			? $options['relationships']
			: array();
	}

	public function unregister( $id ) {
		unset( $this->schemas[ $id ] );
	}
}