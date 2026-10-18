<?php
namespace SchemaFrameworks\Schema;
if ( ! defined( 'ABSPATH' ) ) { exit; }

use SchemaFrameworks\Config;
use SchemaFrameworks\Post_Schema_Module;
use SchemaFrameworks\Schema_Manager;

class Post_Content_Schema implements Schema_Interface {
	private $config;
	private $manager;
	private $module;
	private $post = null;
	private $mapping = array();
	private $override = array();

	public function __construct( Config $config, Schema_Manager $manager ) {
		$this->config = $config;
		$this->manager = $manager;
		$this->module = new Post_Schema_Module( $config );
	}
	public function get_id() { return 'post_content'; }
	public function is_needed( $context = null ) {
		$this->post = null;
		$this->mapping = array();
		$this->override = array();
		if ( ! $this->manager->is_enabled() || ! $this->module->is_enabled() || ! is_singular() ) { return false; }
		$post = get_queried_object();
		if ( ! is_object( $post ) || empty( $post->ID ) || 'publish' !== get_post_status( $post ) || 'page' === $post->post_type ) { return false; }
		$mapping = $this->module->get_mapping( $post->post_type );
		if ( ! is_array( $mapping ) || 'confirmed' !== ( $mapping['status'] ?? '' ) ) { return false; }
		$override = get_post_meta( $post->ID, '_schema_frameworks_post_schema', true );
		$override = is_array( $override ) ? $override : array();
		if ( array_key_exists( 'enabled', $override ) && ! $override['enabled'] ) { return false; }
		$type = ! empty( $override['schema_type'] ) ? $override['schema_type'] : ( $mapping['schema_type'] ?? '' );
		if ( ! in_array( $type, array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true ) ) { return false; }
		$this->post = $post;
		$this->mapping = $mapping;
		$this->override = $override;
		return true;
	}
	public function generate( $context = null ) {
		if ( ! $this->post && ! $this->is_needed( $context ) ) { return array(); }
		$post = $this->post;
		$type = ! empty( $this->override['schema_type'] ) ? $this->override['schema_type'] : ( $this->mapping['schema_type'] ?? 'Article' );
		$url = get_permalink( $post );
		if ( ! $url ) { return array(); }
		$title = get_the_title( $post );
		$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 45, '…' );
		$schema = array(
			'@type' => $type,
			'@id' => untrailingslashit( $url ) . '#schema-' . strtolower( preg_replace( '/[^A-Za-z0-9]+/', '-', $type ) ),
			'url' => esc_url_raw( $url ),
			'mainEntityOfPage' => array( '@id' => untrailingslashit( $url ) . '#webpage' ),
			'headline' => wp_strip_all_tags( $title ),
			'name' => wp_strip_all_tags( $title ),
			'datePublished' => get_post_time( 'c', true, $post ),
			'dateModified' => get_post_modified_time( 'c', true, $post ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
		if ( $description ) { $schema['description'] = wp_strip_all_tags( $description ); }
		$image = get_the_post_thumbnail_url( $post, 'full' );
		if ( $image ) { $schema['image'] = array( esc_url_raw( $image ) ); }
		$author_id = (int) $post->post_author;
		if ( $author_id ) {
			$author_url = get_author_posts_url( $author_id );
			$schema['author'] = array( '@type' => 'Person', '@id' => trailingslashit( $author_url ) . '#person', 'name' => get_the_author_meta( 'display_name', $author_id ), 'url' => $author_url );
		}
		$publisher_name = get_bloginfo( 'name' );
		$publisher_url = home_url( '/' );
		$schema['publisher'] = array( '@type' => 'Organization', '@id' => trailingslashit( $publisher_url ) . '#organization', 'name' => $publisher_name, 'url' => $publisher_url );
		$terms = wp_get_post_terms( $post->ID, array( 'category', 'post_tag' ), array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) { $schema['keywords'] = implode( ', ', array_map( 'sanitize_text_field', $terms ) ); }
		if ( 'DigitalDocument' === $type ) { $schema['encodingFormat'] = get_post_mime_type( $post ) ? get_post_mime_type( $post ) : 'text/html'; }
		return apply_filters( 'schema_frameworks_post_content_schema', $schema, $post, $this->mapping, $context );
	}
}