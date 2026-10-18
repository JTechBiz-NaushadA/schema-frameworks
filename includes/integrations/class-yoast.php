<?php

namespace SchemaFrameworks\Integrations;

use SchemaFrameworks\Config;
use SchemaFrameworks\Registry;
use SchemaFrameworks\Schema_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Yoast {

	private $config;

	private $registry;

	private $schema_manager;

	public function __construct(
		Config $config,
		Registry $registry,
		Schema_Manager $schema_manager
	) {
		$this->config         = $config;
		$this->registry       = $registry;
		$this->schema_manager = $schema_manager;
	}

	public function init() {

		add_action(
			'plugins_loaded',
			array( $this, 'initialize' ),
			20
		);
	}

	public function initialize() {

		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		if (
			! class_exists(
				'\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece'
			)
		) {
			return;
		}

		require_once SCHEMA_FRAMEWORKS_PATH
			. 'includes/integrations/class-schema-piece.php';

		add_filter(
			'wpseo_schema_graph_pieces',
			array( $this, 'register_graph_pieces' ),
			10,
			2
		);
		
		add_filter(
			'wpseo_schema_organization',
			array( $this, 'modify_organization' ),
			20,
			2
		);
		
		add_filter(
			'wpseo_schema_website',
			array( $this, 'modify_website' ),
			20,
			2
		);

		add_filter(
			'wpseo_schema_webpage',
			array( $this, 'modify_webpage' ),
			10,
			2
		);

		add_filter(
			'wpseo_schema_article',
			array( $this, 'modify_article' ),
			20,
			2
		);

		add_filter(
			'wpseo_schema_breadcrumb',
			array( $this, 'modify_breadcrumb' ),
			10,
			2
		);
	}

	public function register_graph_pieces( $pieces, $context ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return $pieces;
		}

		$graph_assembler = new \SchemaFrameworks\Graph_Assembler(
			$this->config,
			$this->schema_manager
		);

		foreach ( $this->registry->all() as $id => $definition ) {

			if (
				in_array(
					$id,
					array(
						'webpage',
						'breadcrumb',
					),
					true
				)
			) {
				continue;
			}

			if ( empty( $definition['class'] ) ) {
				continue;
			}

			$class = $definition['class'];

			if ( ! class_exists( $class ) ) {
				continue;
			}

			try {

				$schema = new $class(
					$this->config,
					$this->schema_manager
				);

				if (
					! $schema instanceof
					\SchemaFrameworks\Schema\Schema_Interface
				) {
					continue;
				}

				if ( ! $this->schema_manager->conditions_match( $id, $context ) ) {
					continue;
				}

				if ( ! $schema->is_needed( $context ) ) {
					continue;
				}

				$pieces[] =
					new \SchemaFrameworks\Yoast\Schema_Piece(
						$schema,
						$graph_assembler
					);

			} catch ( \Throwable $e ) {

				do_action(
					'schema_frameworks_schema_error',
					$e,
					$id,
					$definition
				);
			}
		}

		$post_schema = new \SchemaFrameworks\Schema\Post_Content_Schema( $this->config, $this->schema_manager );
		if ( $post_schema->is_needed( $context ) ) {
			$post_data = $post_schema->generate( $context );
			$post_type = isset( $post_data['@type'] ) ? $post_data['@type'] : '';
			if ( ! in_array( $post_type, array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true ) ) {
				$pieces[] = new \SchemaFrameworks\Yoast\Schema_Piece( $post_schema );
			}
		}

		return $pieces;
	}

	public function modify_article( $data, $context ) {
		if ( ! is_array( $data ) || ! $this->schema_manager->is_enabled() || ! is_singular() ) {
			return $data;
		}

		$post_schema = new \SchemaFrameworks\Schema\Post_Content_Schema( $this->config, $this->schema_manager );
		if ( ! $post_schema->is_needed( $context ) ) {
			return $data;
		}

		$custom = $post_schema->generate( $context );
		$type = isset( $custom['@type'] ) ? $custom['@type'] : '';
		if ( ! in_array( $type, array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true ) ) {
			return $data;
		}

		$data['@type'] = $type;
		$preserve = array( '@id', 'mainEntityOfPage', 'isPartOf', 'author', 'publisher', 'breadcrumb', 'potentialAction' );
		foreach ( $custom as $key => $value ) {
			if ( '@type' === $key || in_array( $key, $preserve, true ) ) {
				continue;
			}
			$data[ $key ] = $value;
		}

		if ( empty( $data['publisher'] ) ) {
			$publisher_id = '';

			if ( ! empty( $custom['publisher']['@id'] ) ) {
				$publisher_id = $custom['publisher']['@id'];
			}

			if ( '' === $publisher_id && $this->config->get( 'modules.organization', false ) ) {
				$organization = \SchemaFrameworks\Organization_Resolver::resolve( $this->config );
				if ( ! empty( $organization ) && is_array( $organization ) ) {
					$publisher_id = \SchemaFrameworks\Organization_Resolver::get_id( $this->config );
				}
			}

			if ( '' === $publisher_id ) {
				$publisher_id = trailingslashit( esc_url_raw( home_url( '/' ) ) ) . '#organization';
			}

			$data['publisher'] = array( '@id' => $publisher_id );
		}

		if ( $this->config->get( 'modules.comment', false ) ) {
			$post = get_queried_object();
			if ( is_object( $post ) && ! empty( $post->ID ) && 'publish' === get_post_status( $post ) ) {
				$max_comments = absint( $this->config->get( 'comments.max_per_post', 10 ) );
				$max_comments = max( 1, min( 100, $max_comments ) );
				$approved_count = (int) get_comments( array(
					'post_id' => (int) $post->ID,
					'status'  => 'approve',
					'type'    => 'comment',
					'count'   => true,
				) );
				$comments = get_comments( array(
					'post_id' => (int) $post->ID,
					'status'  => 'approve',
					'type'    => 'comment',
					'number'  => $max_comments,
					'orderby' => 'comment_date_gmt',
					'order'   => 'ASC',
				) );
				$comment_nodes = array();
				foreach ( $comments as $comment ) {
					if ( ! $comment instanceof \WP_Comment || ! in_array( (string) $comment->comment_approved, array( '1', 'approve' ), true ) ) {
						continue;
					}
					$comment_url = get_comment_link( $comment );
					$comment_node = array(
						'@type' => 'Comment',
						'@id' => ( $comment_url ? esc_url_raw( $comment_url ) : esc_url_raw( get_permalink( $post ) ) . '#comment-' . (int) $comment->comment_ID ),
						'datePublished' => get_comment_date( 'c', $comment ),
						'text' => wp_strip_all_tags( strip_shortcodes( $comment->comment_content ) ),
						'url' => $comment_url ? esc_url_raw( $comment_url ) : '',
						'author' => array( '@type' => 'Person', 'name' => sanitize_text_field( $comment->comment_author ) ),
					);
					if ( ! empty( $comment->comment_author_url ) && wp_http_validate_url( $comment->comment_author_url ) ) {
						$comment_node['author']['url'] = esc_url_raw( $comment->comment_author_url );
					}
					$comment_nodes[] = $comment_node;
				}
				if ( $approved_count > 0 ) {
					$data['commentCount'] = $approved_count;
				}
				if ( ! empty( $comment_nodes ) ) {
					$data['comment'] = $comment_nodes;
				}
			}
		}

		return $data;
	}

	public function modify_organization( $data, $context ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return $data;
		}

		if (
			! $this->config->get(
				'modules.organization',
				false
			)
		) {
			return $data;
		}

		$organization = \SchemaFrameworks\Organization_Resolver::resolve(
			$this->config
		);

		if (
			empty( $organization ) ||
			! is_array( $organization )
		) {
			return $data;
		}

		if ( ! empty( $organization['name'] ) ) {
			$data['name'] = $organization['name'];
		}

		if ( ! empty( $organization['alternate_name'] ) ) {
			$data['alternateName'] =
				$organization['alternate_name'];
		}

		if ( ! empty( $organization['legal_name'] ) ) {
			$data['legalName'] =
				$organization['legal_name'];
		}

		if ( ! empty( $organization['url'] ) ) {
			$data['url'] =
				esc_url_raw( $organization['url'] );
		}

		if ( ! empty( $organization['logo'] ) ) {

			$data['logo'] = array(
				'@id' => preg_replace(
					'/#organization$/',
					'#organization-logo',
					\SchemaFrameworks\Organization_Resolver::get_id(
						$this->config
					)
				),
			);
		}

		if ( ! empty( $organization['description'] ) ) {
			$data['description'] =
				$organization['description'];
		}

		if ( ! empty( $organization['founding_date'] ) ) {
			$data['foundingDate'] =
				$organization['founding_date'];
		}

		if ( ! empty( $organization['telephone'] ) ) {
			$data['telephone'] =
				$organization['telephone'];
		}

		if ( ! empty( $organization['email'] ) ) {
			$data['email'] =
				$organization['email'];
		}

		if (
			! empty( $organization['address'] ) &&
			is_array( $organization['address'] )
		) {

			$address = $organization['address'];

			$postal_address = array(
				'@type' => 'PostalAddress',
			);

			$map = array(
				'street'      => 'streetAddress',
				'locality'    => 'addressLocality',
				'region'      => 'addressRegion',
				'postal_code' => 'postalCode',
				'country'     => 'addressCountry',
			);

			foreach ( $map as $source => $target ) {

				if (
					isset( $address[ $source ] ) &&
					'' !== $address[ $source ]
				) {
					$postal_address[ $target ] =
						$address[ $source ];
				}
			}

			if ( count( $postal_address ) > 1 ) {
				$data['address'] = $postal_address;
			}
		}

		if (
			! empty( $organization['same_as'] ) &&
			is_array( $organization['same_as'] )
		) {

			$same_as = array();

			foreach ( $organization['same_as'] as $url ) {

				$url = esc_url_raw( $url );

				if ( $url ) {
					$same_as[] = $url;
				}
			}

			if ( ! empty( $same_as ) ) {
				$data['sameAs'] = $same_as;
			}
		}

		return $data;
	}

	public function modify_website( $data, $context ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return $data;
		}

		if (
			! $this->config->get(
				'modules.organization',
				false
			)
		) {
			return $data;
		}

		$organization = \SchemaFrameworks\Organization_Resolver::resolve(
			$this->config
		);

		if (
			empty( $organization ) ||
			! is_array( $organization )
		) {
			return $data;
		}

		$data['publisher'] = array(
			'@id' => \SchemaFrameworks\Organization_Resolver::get_id(
				$this->config
			),
		);

		return $data;
	}

	public function modify_webpage( $data, $context ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return $data;
		}

		$definition = $this->registry->get(
			'webpage'
		);

		if ( empty( $definition['class'] ) ) {
			return $data;
		}

		$class = $definition['class'];

		if ( ! class_exists( $class ) ) {
			return $data;
		}

		try {

			$schema = new $class(
				$this->config,
				$this->schema_manager
			);

			if (
				! $schema instanceof
				\SchemaFrameworks\Schema\Schema_Interface
			) {
				return $data;
			}

			if ( ! $schema->is_needed( $context ) ) {
				return $data;
			}

			$custom_data = $schema->generate(
				$context
			);

			if (
				empty( $custom_data ) ||
				! is_array( $custom_data )
			) {
				return $data;
			}

			foreach (
				$custom_data as $property => $value
			) {
				$data[ $property ] = $value;
			}

		} catch ( \Throwable $e ) {

			do_action(
				'schema_frameworks_schema_error',
				$e,
				'webpage',
				$definition
			);
		}

		return $data;
	}

	public function modify_breadcrumb( $data, $context ) {

		if ( ! $this->schema_manager->is_enabled() ) {
			return $data;
		}

		$definition = $this->registry->get(
			'breadcrumb'
		);

		if ( empty( $definition['class'] ) ) {
			return $data;
		}

		$class = $definition['class'];

		if ( ! class_exists( $class ) ) {
			return $data;
		}

		try {

			$schema = new $class(
				$this->config,
				$this->schema_manager
			);

			if (
				! $schema instanceof
				\SchemaFrameworks\Schema\Schema_Interface
			) {
				return $data;
			}

			if ( ! $schema->is_needed( $context ) ) {
				return $data;
			}

			$custom_data = $schema->generate(
				$context
			);

			if (
				empty( $custom_data ) ||
				! is_array( $custom_data )
			) {
				return $data;
			}

			foreach (
				$custom_data as $property => $value
			) {
				$data[ $property ] = $value;
			}

		} catch ( \Throwable $e ) {

			do_action(
				'schema_frameworks_schema_error',
				$e,
				'breadcrumb',
				$definition
			);
		}

		return $data;
	}
}