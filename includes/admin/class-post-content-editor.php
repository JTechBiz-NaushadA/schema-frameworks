<?php
namespace SchemaFrameworks\Admin;
if ( ! defined( 'ABSPATH' ) ) { exit; }

use SchemaFrameworks\Post_Schema_Module;

class Post_Content_Editor {
	private $module;
	public function __construct( $config ) { $this->module = new Post_Schema_Module( $config ); }
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_box' ), 10, 2 );
		add_action( 'add_meta_boxes_post', array( $this, 'register_post_box' ), 10, 1 );
		add_action( 'save_post', array( $this, 'save' ), 10, 3 );
	}
	public function register_box( $post_type, $post = null ) {
		if ( ! is_object( $post ) && is_object( $post_type ) ) {
			$post = $post_type;
		}
		if ( ! is_object( $post ) || ! isset( $post->post_type, $post->ID ) || 'page' === $post->post_type || in_array( $post->post_type, array( 'revision', 'attachment', 'nav_menu_item' ), true ) ) { return; }
		if ( ! is_post_type_viewable( $post->post_type ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
		add_meta_box( 'schema-frameworks-post-content', __( 'Schema Frameworks — Content Schema', 'schema-frameworks' ), array( $this, 'render' ), $post->post_type, 'normal', 'default' );
	}
	public function register_post_box( $post ) {
		if ( ! is_object( $post ) || ! isset( $post->post_type, $post->ID ) ) { return; }
		$this->register_box( $post->post_type, $post );
	}
	public function render( $post ) {
		$mapping = $this->module->get_mapping( $post->post_type );
		$enabled = $this->module->is_enabled() && is_array( $mapping ) && 'confirmed' === ( $mapping['status'] ?? '' ) && in_array( $mapping['schema_type'] ?? '', array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' ), true );
		$override = get_post_meta( $post->ID, '_schema_frameworks_post_schema', true );
		$override = is_array( $override ) ? $override : array();
		$active = array_key_exists( 'enabled', $override ) ? (bool) $override['enabled'] : $enabled;
		$type = isset( $override['schema_type'] ) ? sanitize_text_field( $override['schema_type'] ) : ''; 
		wp_nonce_field( 'schema_frameworks_post_content_' . $post->ID, 'schema_frameworks_post_content_nonce' );
		echo '<p>' . esc_html__( 'This panel applies only to this content item. The global Post Schema Module must be enabled and this post type must have a confirmed mapping.', 'schema-frameworks' ) . '</p>';
		echo '<p><label><input type="checkbox" name="schema_frameworks_post_content[enabled]" value="1" ' . checked( $active, true, false ) . ' /> ' . esc_html__( 'Enable schema for this content item', 'schema-frameworks' ) . '</label></p>';
		echo '<p><label for="schema-frameworks-post-schema-type">' . esc_html__( 'Schema.org content type override (optional)', 'schema-frameworks' ) . '</label><br />';
		echo '<select id="schema-frameworks-post-schema-type" name="schema_frameworks_post_content[schema_type]">';
		echo '<option value="" ' . selected( $type, '', false ) . '>' . esc_html__( 'Use confirmed post-type mapping', 'schema-frameworks' ) . '</option>';
		$options = array(
			'BlogPosting' => 'BlogPosting — ordinary blog posts',
			'Article' => 'Article — general editorial articles',
			'TechArticle' => 'TechArticle — technical articles and technical guidance',
			'DigitalDocument' => 'DigitalDocument — digital documents and downloadable resources',
		);
		foreach ( $options as $schema_type => $label ) { echo '<option value="' . esc_attr( $schema_type ) . '" ' . selected( $type, $schema_type, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></p>';
		echo '<p class="description">' . esc_html__( 'Leave this set to Use confirmed post-type mapping unless this specific item needs a different content type. WebSite, WebPage, Organization, and Person are intentionally not offered here because they describe other graph entities, not the post content.', 'schema-frameworks' ) . '</p>';

		if ( 'post' === $post->post_type ) {
			$faq_items = get_post_meta( $post->ID, '_schema_frameworks_post_faq', true );
			$faq_items = is_array( $faq_items ) ? $faq_items : array();
			echo '<hr /><h3>' . esc_html__( 'Custom FAQ (optional)', 'schema-frameworks' ) . '</h3>';
			echo '<p>' . esc_html__( 'Add questions and answers that are actually visible in this blog post. FAQPage schema is generated only when at least one complete question and answer is saved and the global FAQ module is enabled.', 'schema-frameworks' ) . '</p>';
			echo '<div id="schema-frameworks-post-faq-items">';
			$faq_items = array_values( $faq_items );
			if ( empty( $faq_items ) ) { $faq_items = array( array( 'question' => '', 'answer' => '' ) ); }
			foreach ( $faq_items as $index => $item ) {
				$this->render_faq_row( $index, is_array( $item ) ? $item : array() );
			}
			echo '</div><p><button type="button" class="button" id="schema-frameworks-add-post-faq">' . esc_html__( 'Add question', 'schema-frameworks' ) . '</button></p>';
			echo '<script type="text/html" id="schema-frameworks-post-faq-template">';
			$this->render_faq_row( '__FAQ_INDEX__', array() );
			echo '</script>';
		}
	}
	private function render_faq_row( $index, $item ) {
		$question = isset( $item['question'] ) ? (string) $item['question'] : '';
		$answer = isset( $item['answer'] ) ? (string) $item['answer'] : '';
		echo '<div class="schema-frameworks-post-faq-row" style="border:1px solid #dcdcde;padding:12px;margin:12px 0;background:#fff">';
		echo '<p><label><strong>' . esc_html__( 'Question', 'schema-frameworks' ) . '</strong><br /><input type="text" class="widefat" name="schema_frameworks_post_faq[' . esc_attr( (string) $index ) . '][question]" value="' . esc_attr( $question ) . '" /></label></p>';
		echo '<p><label><strong>' . esc_html__( 'Answer', 'schema-frameworks' ) . '</strong><br /><textarea class="widefat" rows="3" name="schema_frameworks_post_faq[' . esc_attr( (string) $index ) . '][answer]">' . esc_textarea( $answer ) . '</textarea></label></p>';
		echo '<p><button type="button" class="button-link-delete schema-frameworks-remove-post-faq">' . esc_html__( 'Remove question', 'schema-frameworks' ) . '</button></p></div>';
	}
	public function save( $post_id, $post, $update ) {
		if ( ! isset( $_POST['schema_frameworks_post_content_nonce'] ) || ! is_object( $post ) ) { return; }
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['schema_frameworks_post_content_nonce'] ) ), 'schema_frameworks_post_content_' . $post_id ) ) { return; }
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) || 'page' === $post->post_type ) { return; }
		$input = isset( $_POST['schema_frameworks_post_content'] ) && is_array( $_POST['schema_frameworks_post_content'] ) ? wp_unslash( $_POST['schema_frameworks_post_content'] ) : array();
		$type = isset( $input['schema_type'] ) ? sanitize_text_field( $input['schema_type'] ) : '';
		$mapping = $this->module->get_mapping( $post->post_type );
		$allowed = array( 'BlogPosting', 'Article', 'TechArticle', 'DigitalDocument' );
		$mapped_type = is_array( $mapping ) && isset( $mapping['schema_type'] ) ? $mapping['schema_type'] : '';
		if ( '' !== $type && ! in_array( $type, $allowed, true ) ) { $type = ''; }
		$value = array( 'enabled' => ! empty( $input['enabled'] ), 'schema_type' => $type );
		update_post_meta( $post_id, '_schema_frameworks_post_schema', $value );

		if ( 'post' === $post->post_type ) {
			$submitted_faq = isset( $_POST['schema_frameworks_post_faq'] ) && is_array( $_POST['schema_frameworks_post_faq'] ) ? wp_unslash( $_POST['schema_frameworks_post_faq'] ) : array();
			$faq_items = array();
			foreach ( $submitted_faq as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
				$answer = isset( $item['answer'] ) ? sanitize_textarea_field( $item['answer'] ) : '';
				if ( '' === $question || '' === $answer ) { continue; }
				$faq_items[] = array( 'question' => $question, 'answer' => $answer );
				if ( count( $faq_items ) >= 20 ) { break; }
			}
			if ( empty( $faq_items ) ) { delete_post_meta( $post_id, '_schema_frameworks_post_faq' ); }
			else { update_post_meta( $post_id, '_schema_frameworks_post_faq', $faq_items ); }
		}
	}
}