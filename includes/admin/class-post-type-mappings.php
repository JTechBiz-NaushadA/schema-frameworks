<?php

namespace SchemaFrameworks\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Config;
use SchemaFrameworks\Registry;
use SchemaFrameworks\Post_Type_Mapping_Recommender;
use SchemaFrameworks\Post_Schema_Module;

class Post_Type_Mappings {

	private $config;
	private $registry;
	private $recommender;
	private $post_module;

	public function __construct( Config $config, Registry $registry ) {
		$this->config = $config;
		$this->registry = $registry;
		$this->recommender = new Post_Type_Mapping_Recommender();
		$this->post_module = new Post_Schema_Module( $config );
	}

	public function register_menu() {
		add_submenu_page(
			'schema-frameworks',
			'Post Type Mappings',
			'Post Type Mappings',
			'manage_options',
			'schema-frameworks-post-mappings',
			array( $this, 'render_page' )
		);
	}

	public function handle_save() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change Schema Frameworks mappings.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'schema_frameworks_save_post_mappings', 'schema_frameworks_post_mappings_nonce' );

		$enabled = isset( $_POST['post_module_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['post_module_enabled'] ) );

		$comment_enabled = isset( $_POST['comment_schema_enabled'] )
			&& '1' === sanitize_text_field( wp_unslash( $_POST['comment_schema_enabled'] ) );
		$comment_limit = isset( $_POST['comment_max_per_post'] )
			? absint( wp_unslash( $_POST['comment_max_per_post'] ) )
			: absint( $this->config->get( 'comments.max_per_post', 10 ) );

		$this->config->set( 'modules.comment', (bool) $comment_enabled );
		$this->config->set( 'comments.max_per_post', max( 1, min( 100, $comment_limit ) ) );
		$current = $this->post_module->get_mappings();
		$current = is_array( $current ) ? $current : array();
		$submitted = isset( $_POST['post_type_mappings'] ) && is_array( $_POST['post_type_mappings'] )
			? wp_unslash( $_POST['post_type_mappings'] ) : array();

		$public_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $public_types as $type_name => $type_object ) {
			$type_name = sanitize_key( $type_name );
			if ( in_array( $type_name, array( 'page', 'revision', 'nav_menu_item' ), true ) || ! isset( $submitted[ $type_name ] ) || ! is_array( $submitted[ $type_name ] ) ) {
				continue;
			}
			$row = $submitted[ $type_name ];
			$action = isset( $row['action'] ) ? sanitize_key( $row['action'] ) : 'pending';
			if ( 'unmapped' === $action ) {
				$current[ $type_name ] = array( 'status' => 'unmapped', 'schema_type' => '', 'module_id' => '', 'updated_at' => current_time( 'mysql' ) );
				continue;
			}
			if ( 'confirmed' !== $action ) {
				continue;
			}

			$schema_type = isset( $row['schema_type'] ) ? sanitize_text_field( $row['schema_type'] ) : '';
			if ( '' === $schema_type ) {
				$current[ $type_name ] = array( 'status' => 'unmapped', 'schema_type' => '', 'module_id' => '', 'updated_at' => current_time( 'mysql' ) );
				continue;
			}
			$recommendation = $this->recommender->recommend( $type_object, $this->registry );
			$allowed_types = array_keys( $this->available_schema_types() );
			if ( ! in_array( $schema_type, $allowed_types, true ) ) {
				continue;
			}
			$module_id = '';
			$module_available = in_array( $schema_type, array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true );
			foreach ( $this->registry->get_all_metadata() as $registered_id => $metadata ) {
				if ( isset( $metadata['schema_type'] ) && $metadata['schema_type'] === $schema_type ) {
					$module_id = sanitize_key( $registered_id );
					$module_available = true;
					break;
				}
			}
			$current[ $type_name ] = array(
				'status' => 'confirmed',
				'schema_type' => $schema_type,
				'module_id' => $module_id,
				'generation_ready' => (bool) $module_available,
				'updated_at' => current_time( 'mysql' ),
			);
		}

		$this->post_module->save_foundation_settings( $enabled, $current );
		wp_safe_redirect( add_query_arg( array( 'page' => 'schema-frameworks-post-mappings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view post type mappings.', 'schema-frameworks' ), '', array( 'response' => 403 ) );
		}
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$stored = $this->post_module->get_mappings();
		$stored = is_array( $stored ) ? $stored : array();
		$available = $this->available_schema_types();
		?>
		<div class="wrap schema-frameworks-admin-page schema-frameworks-post-mappings">
			<h1><?php echo esc_html__( 'Post Type Schema Mapping', 'schema-frameworks' ); ?></h1>
			<p><?php echo esc_html__( 'Review deterministic suggestions for public WordPress post types. Suggestions never activate themselves; an administrator must confirm or override each mapping.', 'schema-frameworks' ); ?></p>
			<?php if ( isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Post type mapping settings saved.', 'schema-frameworks' ); ?></p></div>
			<?php endif; ?>
			<div class="notice notice-info"><p><strong><?php echo esc_html__( 'Choose a content type:', 'schema-frameworks' ); ?></strong> <?php echo esc_html__( 'Only content-appropriate types supported by this generator are offered below. Site and entity types such as WebSite, WebPage, Organization, and Person are intentionally excluded because they describe the site, page, publisher, or author rather than the post content.', 'schema-frameworks' ); ?></p></div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="schema_frameworks_save_post_mappings" />
				<?php wp_nonce_field( 'schema_frameworks_save_post_mappings', 'schema_frameworks_post_mappings_nonce' ); ?>
				<table class="form-table" role="presentation"><tbody><tr>
					<th scope="row"><label for="schema-frameworks-post-module-enabled"><?php echo esc_html__( 'Post Schema Module', 'schema-frameworks' ); ?></label></th>
					<td><label><input id="schema-frameworks-post-module-enabled" type="checkbox" name="post_module_enabled" value="1" <?php checked( $this->post_module->is_enabled() ); ?> /> <?php echo esc_html__( 'Enable Post schema generation', 'schema-frameworks' ); ?></label><p class="description"><?php echo esc_html__( 'When enabled, published content with a confirmed supported mapping can emit schema in the Yoast graph. Individual content items can be disabled in their editor panel.', 'schema-frameworks' ); ?></p></td>
				</tr></tbody></table>
				<h2><?php echo esc_html__( 'Comment Schema', 'schema-frameworks' ); ?></h2>
				<p><?php echo esc_html__( 'Comment schema is added to eligible mapped post content. Only administrator-approved comments are emitted; pending, spam, and trashed comments are excluded.', 'schema-frameworks' ); ?></p>
				<table class="form-table" role="presentation"><tbody>
				<tr>
					<th scope="row"><label for="sf-comment-schema-enabled"><?php echo esc_html__( 'Approved Comment Schema', 'schema-frameworks' ); ?></label></th>
					<td><label><input id="sf-comment-schema-enabled" type="checkbox" name="comment_schema_enabled" value="1" <?php checked( $this->config->get( 'modules.comment', false ) ); ?> /> <?php echo esc_html__( 'Enable Comment schema', 'schema-frameworks' ); ?></label><p class="description"><?php echo esc_html__( 'Optional and disabled by default. Requires Post Schema generation and a confirmed supported post-type mapping.', 'schema-frameworks' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="sf-comment-max-per-post"><?php echo esc_html__( 'Maximum comments per post', 'schema-frameworks' ); ?></label></th>
					<td><input id="sf-comment-max-per-post" type="number" min="1" max="100" step="1" name="comment_max_per_post" value="<?php echo esc_attr( absint( $this->config->get( 'comments.max_per_post', 10 ) ) ); ?>" class="small-text" /><p class="description"><?php echo esc_html__( 'Maximum approved Comment entities emitted for each post (1–100). Default: 10.', 'schema-frameworks' ); ?></p></td>
				</tr>
				</tbody></table>
				<h2><?php echo esc_html__( 'Public Post Types', 'schema-frameworks' ); ?></h2>
				<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Post type', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Recommendation', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Mapping decision', 'schema-frameworks' ); ?></th><th><?php echo esc_html__( 'Status', 'schema-frameworks' ); ?></th></tr></thead><tbody>
				<?php foreach ( $post_types as $type_name => $type_object ) : ?>
					<?php
					$type_name = sanitize_key( $type_name );
					if ( in_array( $type_name, array( 'page', 'revision', 'nav_menu_item' ), true ) ) { continue; }
					$recommendation = $this->recommender->recommend( $type_object, $this->registry );
					$saved = isset( $stored[ $type_name ] ) && is_array( $stored[ $type_name ] ) ? $stored[ $type_name ] : array();
					$saved_type = isset( $saved['schema_type'] ) ? $saved['schema_type'] : '';
					$saved_module = isset( $saved['module_id'] ) ? $saved['module_id'] : '';
					$current_type = 'confirmed' === ( isset( $saved['status'] ) ? $saved['status'] : '' ) ? $saved_type : $recommendation['suggested_schema_type'];
					if ( ! isset( $available[ $current_type ] ) ) { $current_type = ''; }
					$current_module = $saved_module ? $saved_module : $recommendation['module_id'];
					$confidence = isset( $recommendation['confidence'] ) ? $recommendation['confidence'] : 'low';
					?>
					<tr>
						<th scope="row"><strong><?php echo esc_html( $type_object->label ); ?></strong><br /><code><?php echo esc_html( $type_name ); ?></code><p class="description"><?php echo esc_html( isset( $type_object->description ) ? $type_object->description : '' ); ?></p></th>
						<td><?php if ( $recommendation['suggested_schema_type'] ) : ?><strong><?php echo esc_html( $recommendation['suggested_schema_type'] ); ?></strong><br /><span><?php echo esc_html( ucfirst( $confidence ) ); ?> confidence</span><?php else : ?><em><?php echo esc_html__( 'No reliable suggestion', 'schema-frameworks' ); ?></em><?php endif; ?><ul><?php foreach ( $recommendation['evidence'] as $evidence ) : ?><li><?php echo esc_html( $evidence ); ?></li><?php endforeach; ?></ul><?php if ( ! empty( $recommendation['module_available'] ) ) : ?><p class="description"><?php echo esc_html__( 'A matching registered module is available.', 'schema-frameworks' ); ?></p><?php else : ?><p class="description"><?php echo esc_html__( 'This recommendation is not currently supported by the post-content generator. Choose a supported content type only if it accurately describes this content, or leave it unmapped.', 'schema-frameworks' ); ?></p><?php endif; ?></td>
						<td>
							<label class="screen-reader-text" for="sf-map-type-<?php echo esc_attr( $type_name ); ?>"><?php echo esc_html__( 'Schema type', 'schema-frameworks' ); ?></label>
							<select id="sf-map-type-<?php echo esc_attr( $type_name ); ?>" name="post_type_mappings[<?php echo esc_attr( $type_name ); ?>][schema_type]">
								<option value=""><?php echo esc_html__( 'Leave unmapped', 'schema-frameworks' ); ?></option>
								<?php $options = $available; foreach ( $options as $schema_type => $label ) : ?><option value="<?php echo esc_attr( $schema_type ); ?>" <?php selected( $current_type, $schema_type ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
							</select>
							<input type="hidden" name="post_type_mappings[<?php echo esc_attr( $type_name ); ?>][module_id]" value="<?php echo esc_attr( $current_module ); ?>" />
							<p><label><input type="radio" name="post_type_mappings[<?php echo esc_attr( $type_name ); ?>][action]" value="pending" <?php checked( ! isset( $saved['status'] ) || 'pending' === $saved['status'] ); ?> /> <?php echo esc_html__( 'Keep suggestion pending', 'schema-frameworks' ); ?></label></p>
							<p><label><input type="radio" name="post_type_mappings[<?php echo esc_attr( $type_name ); ?>][action]" value="confirmed" <?php checked( isset( $saved['status'] ) && 'confirmed' === $saved['status'] ); ?> /> <?php echo esc_html__( 'Confirm / save mapping', 'schema-frameworks' ); ?></label></p>
							<p><label><input type="radio" name="post_type_mappings[<?php echo esc_attr( $type_name ); ?>][action]" value="unmapped" <?php checked( isset( $saved['status'] ) && 'unmapped' === $saved['status'] ); ?> /> <?php echo esc_html__( 'Explicitly leave unmapped', 'schema-frameworks' ); ?></label></p>
						</td>
						<td><?php echo esc_html( isset( $saved['status'] ) ? ucfirst( $saved['status'] ) : 'Pending review' ); ?><?php if ( isset( $saved['generation_ready'] ) && ! $saved['generation_ready'] ) : ?><br /><span class="description"><?php echo esc_html__( 'Waiting for generator', 'schema-frameworks' ); ?></span><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody></table>
				<?php submit_button( __( 'Save Mapping Decisions', 'schema-frameworks' ) ); ?>
			</form>
		</div>
		<?php
	}

	private function available_schema_types() {
		return array(
			'BlogPosting' => 'BlogPosting — ordinary blog posts (recommended for Posts)',
			'Article' => 'Article — general editorial articles',
			'TechArticle' => 'TechArticle — technical articles and technical guidance',
			'DigitalDocument' => 'DigitalDocument — digital documents and downloadable resources',
		);
	}
}