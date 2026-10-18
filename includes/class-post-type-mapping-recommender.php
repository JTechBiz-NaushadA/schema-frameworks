<?php

namespace SchemaFrameworks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Type_Mapping_Recommender {

	public function recommend( $post_type, Registry $registry ) {
		if ( ! is_object( $post_type ) || empty( $post_type->name ) ) {
			return $this->empty_recommendation( 'No valid post type was supplied.' );
		}

		$name = sanitize_key( $post_type->name );
		$label = isset( $post_type->label ) ? sanitize_text_field( $post_type->label ) : $name;
		$description = isset( $post_type->description ) ? sanitize_text_field( $post_type->description ) : '';
		$haystack = strtolower( $name . ' ' . $label . ' ' . $description );
		$rules = array(
			'product' => array( 'pattern' => '/\b(product|products|catalog item|merchandise)\b/', 'module' => 'product', 'type' => 'Product', 'reason' => 'The post type metadata contains product-related terms.' ),
			'service' => array( 'pattern' => '/\b(service|services|offering)\b/', 'module' => 'service', 'type' => 'Service', 'reason' => 'The post type metadata contains service-related terms.' ),
			'faq' => array( 'pattern' => '/\b(faq|frequently asked questions|question and answer)\b/', 'module' => 'faq', 'type' => 'FAQPage', 'reason' => 'The post type metadata describes FAQ content.' ),
			'howto' => array( 'pattern' => '/\b(howto|how-to|tutorial|step by step)\b/', 'module' => 'howto', 'type' => 'HowTo', 'reason' => 'The post type metadata suggests instructional content.' ),
			'tech_article' => array( 'pattern' => '/\b(technical article|tech article|developer documentation|technical guide)\b/', 'module' => 'tech_article', 'type' => 'TechArticle', 'reason' => 'The post type metadata suggests technical editorial content.' ),
			'digital_document' => array( 'pattern' => '/\b(document|documents|download|whitepaper|white paper|ebook|e-book)\b/', 'module' => 'digital_document', 'type' => 'DigitalDocument', 'reason' => 'The post type metadata suggests a digital document or downloadable resource.' ),
			'image_object' => array( 'pattern' => '/\b(attachment|media library|image gallery)\b/', 'module' => 'image_object', 'type' => 'ImageObject', 'reason' => 'The post type metadata suggests media or image content.' ),
		);

		if ( 'post' === $name ) {
			return $this->build_recommendation( $name, 'BlogPosting', 'blog_post', 'high', array( 'WordPress standard post type is intended for editorial entries.' ), $registry );
		}

		foreach ( $rules as $rule ) {
			if ( preg_match( $rule['pattern'], $haystack ) ) {
				return $this->build_recommendation( $name, $rule['type'], $rule['module'], 'medium', array( $rule['reason'], 'Recommendation is based on registered post type name, label, and description only.' ), $registry );
			}
		}

		if ( preg_match( '/\b(article|blog|news|editorial|story|stories)\b/', $haystack ) ) {
			return $this->build_recommendation( $name, 'Article', 'article', 'medium', array( 'The post type metadata suggests editorial content.', 'The exact editorial subtype cannot be determined from metadata alone.' ), $registry );
		}

		return $this->empty_recommendation( 'Available post type metadata does not support a reliable mapping. Leave this type unmapped until an administrator selects an appropriate schema type.' );
	}

	private function build_recommendation( $post_type, $schema_type, $module_id, $confidence, $evidence, Registry $registry ) {
		$available = $registry->has( $module_id );
		return array(
			'post_type' => sanitize_key( $post_type ),
			'suggested_schema_type' => sanitize_text_field( $schema_type ),
			'module_id' => sanitize_key( $module_id ),
			'confidence' => $confidence,
			'evidence' => array_values( $evidence ),
			'module_available' => $available,
			'status' => 'pending',
			'note' => $available ? 'Suggestion requires administrator confirmation.' : 'A compatible schema generator is not registered yet. This suggestion cannot generate public JSON-LD until support is available.',
		);
	}

	private function empty_recommendation( $reason ) {
		return array(
			'post_type' => '', 'suggested_schema_type' => '', 'module_id' => '',
			'confidence' => 'low', 'evidence' => array( sanitize_text_field( $reason ) ),
			'module_available' => false, 'status' => 'unmapped', 'note' => 'No mapping has been activated.',
		);
	}
}