<?php

namespace SchemaFrameworks\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Config;
use SchemaFrameworks\Registry;
use SchemaFrameworks\Helpers\Relationships;

class Page_Editor {

	private $config;
	private $registry;
	private $schema_configuration;

	public function __construct( Config $config, Registry $registry, Schema_Configuration $schema_configuration ) {
		$this->config               = $config;
		$this->registry             = $registry;
		$this->schema_configuration = $schema_configuration;
	}

	public function init() {
		add_action( 'add_meta_boxes_page', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save' ), 10, 3 );
	}

	public function register_meta_box( $post ) {
		if ( ! current_user_can( 'edit_page', $post->ID ) ) {
			return;
		}

		add_meta_box(
			'schema-frameworks-page-schema',
			'Schema Frameworks',
			array( $this, 'render' ),
			'page',
			'normal',
			'high'
		);
	}

	public function render( $post ) {
		$config = $this->get_page_configuration( $post->ID );
		$enabled_modules = $this->get_enabled_modules();
		$selected = isset( $config['schemas'] ) && is_array( $config['schemas'] )
			? $config['schemas']
			: array();

		wp_nonce_field( 'schema_frameworks_page_schema', 'schema_frameworks_page_schema_nonce' );
		?>
		<div class="schema-frameworks-page-editor">
			<div class="schema-frameworks-page-editor-intro">
				<p><strong>Configure structured data for this page.</strong></p>
				<p class="description">Enable the schema types that apply to this page, then complete their configuration below. The settings are saved with this page's Schema Frameworks configuration.</p>
			</div>

			<section class="schema-frameworks-editor-section">
				<div class="schema-frameworks-editor-section-heading">
					<div>
						<span class="schema-frameworks-section-kicker">Step 1</span>
						<h3>Enabled Schemas</h3>
						<p class="description">Only modules enabled globally under Schema Frameworks → Modules are available here.</p>
					</div>
				</div>

				<div class="schema-frameworks-editor-module-grid">
					<?php foreach ( $enabled_modules as $module_id => $module ) : ?>
						<?php
						$is_selected = in_array( $module_id, $selected, true );
						$has_config  = $this->schema_configuration->has_fields( $module_id );
						?>
						<label class="schema-frameworks-editor-module-card<?php echo $is_selected ? ' is-selected' : ''; ?>">
							<input
								type="checkbox"
								name="schema_frameworks_page[schemas][]"
								value="<?php echo esc_attr( $module_id ); ?>"
								data-config-target="schema-frameworks-editor-config-<?php echo esc_attr( $module_id ); ?>"
								<?php checked( $is_selected ); ?>
							/>
							<span class="schema-frameworks-editor-module-card-content">
								<strong><?php echo esc_html( $module['label'] ); ?></strong>
								<span><?php echo esc_html( $module['description'] ); ?></span>
								<?php if ( ! $has_config ) : ?>
									<em>Configuration fields not yet defined.</em>
								<?php endif; ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="schema-frameworks-editor-section schema-frameworks-editor-configuration">
				<div class="schema-frameworks-editor-section-heading">
					<div>
						<span class="schema-frameworks-section-kicker">Step 2</span>
						<h3>Schema Configuration</h3>
						<p class="description">Select a schema tab below to complete its configuration. Only schemas enabled for this page are shown.</p>
					</div>
				</div>

				<?php
				$active_tab = '';
				foreach ( $enabled_modules as $module_id => $module ) {
					if ( $this->schema_configuration->has_fields( $module_id ) && in_array( $module_id, $selected, true ) ) {
						$active_tab = $module_id;
						break;
					}
				}
				?>

				<div class="schema-frameworks-editor-tabs" role="tablist" aria-label="Schema configuration">
					<?php foreach ( $enabled_modules as $module_id => $module ) : ?>
						<?php if ( ! $this->schema_configuration->has_fields( $module_id ) ) : continue; endif; ?>
						<?php $is_active = $module_id === $active_tab; ?>
						<button
							type="button"
							class="schema-frameworks-editor-tab<?php echo $is_active ? ' is-active' : ''; ?>"
							data-schema-tab="<?php echo esc_attr( $module_id ); ?>"
							role="tab"
							aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
							aria-controls="schema-frameworks-editor-config-<?php echo esc_attr( $module_id ); ?>"
							<?php echo in_array( $module_id, $selected, true ) ? '' : ' style="display:none;"'; ?>
						>
							<?php echo esc_html( $module['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="schema-frameworks-editor-config-panels">
					<?php foreach ( $enabled_modules as $module_id => $module ) : ?>
						<?php if ( ! $this->schema_configuration->has_fields( $module_id ) ) : continue; endif; ?>
						<?php $is_active = $module_id === $active_tab; ?>
						<div
							id="schema-frameworks-editor-config-<?php echo esc_attr( $module_id ); ?>"
							class="schema-frameworks-editor-config-panel"
							role="tabpanel"
							aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
							<?php echo $is_active ? '' : ' style="display:none;"'; ?>
						>
							<?php
							$value = isset( $config[ $module_id ] ) && is_array( $config[ $module_id ] )
								? $config[ $module_id ]
								: array();

							echo $this->schema_configuration->render(
								$module_id,
								$value,
								'schema_frameworks_page[' . $module_id . ']',
								'schema-frameworks-editor-field-' . $module_id
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="schema-frameworks-editor-no-selection"<?php echo $active_tab ? ' style="display:none;"' : ''; ?>>
					<p>No schema is enabled for this page yet. Select one or more schemas in Step 1 to configure them here.</p>
				</div>
			</section>

			<section class="schema-frameworks-editor-section schema-frameworks-editor-relationships">
				<div class="schema-frameworks-editor-section-heading">
					<div>
						<span class="schema-frameworks-section-kicker">Step 3</span>
						<h3>Entity Relationships</h3>
						<p class="description">Connect schema entities using only Schema Frameworks relationships that are valid for the selected source and target types. Invalid combinations are ignored when saved.</p>
					</div>
				</div>
				<?php echo $this->render_relationship_editor( $config, $enabled_modules ); ?>
			</section>
		</div>
		<?php
	}

	public function save( $post_id, $post, $update ) {
		if ( ! isset( $_POST['schema_frameworks_page_schema_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['schema_frameworks_page_schema_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'schema_frameworks_page_schema' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['schema_frameworks_page'] ) && is_array( $_POST['schema_frameworks_page'] )
			? wp_unslash( $_POST['schema_frameworks_page'] )
			: array();

		$this->save_page_configuration( $post_id, $raw );
	}

	private function save_page_configuration( $page_id, $input ) {
		$current = $this->config->all();
		$pages = isset( $current['pages'] ) && is_array( $current['pages'] )
			? $current['pages']
			: array();

		$existing_index = null;
		$page_config = array();

		foreach ( $pages as $index => $existing ) {
			if ( is_array( $existing ) && ! empty( $existing['page_id'] ) && (int) $existing['page_id'] === (int) $page_id ) {
				$existing_index = $index;
				$page_config = $existing;
				break;
			}
		}

		$schemas = isset( $input['schemas'] ) && is_array( $input['schemas'] )
			? $input['schemas']
			: array();

		$schemas = array_values( array_unique( array_filter( array_map( 'sanitize_key', $schemas ) ) ) );

		$allowed = array_keys( $this->get_enabled_modules() );
		$schemas = array_values( array_intersect( $schemas, $allowed ) );

		$page_url = get_permalink( $page_id );
		$page_config['page_id'] = (int) $page_id;
		$page_config['url']     = $page_url ? esc_url_raw( $page_url ) : '';
		$page_config['path']    = $page_url ? wp_parse_url( $page_url, PHP_URL_PATH ) : '';
		$page_config['schemas'] = $schemas;

		foreach ( $schemas as $schema_id ) {
			if ( ! $this->schema_configuration->has_fields( $schema_id ) ) {
				continue;
			}

			$value = isset( $input[ $schema_id ] ) && is_array( $input[ $schema_id ] )
				? $input[ $schema_id ]
				: array();

			$page_config[ $schema_id ] = $this->schema_configuration->sanitize( $schema_id, $value );
		}

		$page_config['relationships'] = $this->sanitize_relationships(
			isset( $input['relationships'] ) && is_array( $input['relationships'] )
				? $input['relationships']
				: array()
		);

		if ( null !== $existing_index ) {
			$pages[ $existing_index ] = $page_config;
		} else {
			$pages[] = $page_config;
		}

		$current['pages'] = $pages;
		$this->config->set( 'pages', $pages );
		$this->config->save();
	}


	private function sanitize_relationships( $relationships ) {
		$clean = array();
		foreach ( $relationships as $relationship ) {
			if ( ! is_array( $relationship ) ) {
				continue;
			}
			$row = array(
				'source'   => isset( $relationship['source'] ) ? sanitize_key( $relationship['source'] ) : '',
				'property' => isset( $relationship['property'] ) ? sanitize_text_field( $relationship['property'] ) : '',
				'target'   => isset( $relationship['target'] ) ? sanitize_key( $relationship['target'] ) : '',
			);
			if ( Relationships::validate( $row, $this->registry ) ) {
				$clean[] = $row;
			}
		}
		return $clean;
	}

	private function render_relationship_editor( $config, $enabled_modules ) {
		$page_relationships = isset( $config['relationships'] ) && is_array( $config['relationships'] )
			? $config['relationships']
			: array();

		$rules = Relationships::rules( $this->registry );
		$module_options = array();
		foreach ( $enabled_modules as $id => $module ) {
			$module_options[ $id ] = $module['label'];
		}

		if ( $this->config->get( 'modules.organization', false ) ) {
			$module_options['organization'] = 'Organization';
		}
		
		$source_options = array();
		foreach ( $module_options as $id => $label ) {
			if ( ! empty( $rules[ $id ] ) ) {
				$source_options[ $id ] = $label;
			}
		}

		$html  = '<div class="schema-frameworks-relationship-editor">';
		$html .= '<div class="schema-frameworks-repeater schema-frameworks-relationship-repeater">';

		$rows = ! empty( $page_relationships ) ? $page_relationships : array( array() );
		foreach ( $rows as $index => $row ) {
			$html .= $this->render_relationship_row( $row, $index, $source_options, $module_options, $rules );
		}

		if ( empty( $source_options ) ) {
			$html .= '<p class="description">No enabled schema module currently declares a configurable relationship capability.</p>';
		} else {
			$html .= '<button type="button" class="button schema-frameworks-repeater-add">Add Relationship</button>';
			$html .= '<template class="schema-frameworks-repeater-template">';
			$html .= $this->render_relationship_row( array(), '__ROW_INDEX__', $source_options, $module_options, $rules, true );
			$html .= '</template>';
		}

		$html .= '</div></div>';
		return $html;
	}

	private function render_relationship_row( $row, $index, $source_options, $module_options, $rules, $template = false ) {
		$source   = isset( $row['source'] ) ? $row['source'] : '';
		$property = isset( $row['property'] ) ? $row['property'] : '';
		$target   = isset( $row['target'] ) ? $row['target'] : '';
		$prefix   = 'schema_frameworks_page[relationships][' . $index . ']';


		$html = '<div class="schema-frameworks-repeater-row schema-frameworks-relationship-row">';
		$html .= '<div class="schema-frameworks-repeater-row-fields">';

		$html .= '<div class="schema-frameworks-repeater-field"><label><strong>Source</strong></label><select class="regular-text" name="' . esc_attr( $prefix . '[source]' ) . '">';
		$html .= '<option value="">Select source</option>';
		foreach ( $source_options as $id => $label ) {
			$html .= '<option value="' . esc_attr( $id ) . '" ' . selected( $source, $id, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$html .= '</select></div>';

		$html .= '<div class="schema-frameworks-repeater-field"><label><strong>Property</strong></label><select class="regular-text" name="' . esc_attr( $prefix . '[property]' ) . '">';
		$html .= '<option value="">Select property</option>';
		foreach ( $rules as $source_id => $properties ) {
			foreach ( $properties as $property_id => $targets ) {
				$is_source = $source_id === $source;
				$html .= '<option value="' . esc_attr( $property_id ) . '" data-source="' . esc_attr( $source_id ) . '" data-targets="' . esc_attr( implode( ',', $targets ) ) . '" ' . selected( $is_source ? $property : '', $property_id, false ) . ( $is_source ? '' : ' hidden' ) . '>' . esc_html( $property_id ) . '</option>';
			}
		}
		$html .= '</select></div>';

		$html .= '<div class="schema-frameworks-repeater-field"><label><strong>Target</strong></label><select class="regular-text" name="' . esc_attr( $prefix . '[target]' ) . '">';
		$html .= '<option value="">Select target</option>';
		foreach ( $module_options as $id => $label ) {
			$allowed = in_array( $id, $rules[ $source ][ $property ] ?? array(), true );
			$html .= '<option value="' . esc_attr( $id ) . '" data-target-id="' . esc_attr( $id ) . '" ' . selected( $allowed ? $target : '', $id, false ) . ( $allowed ? '' : ' hidden' ) . '>' . esc_html( $label ) . '</option>'; 
		}
		$html .= '</select></div>';

		$html .= '</div><button type="button" class="button-link-delete schema-frameworks-repeater-remove">Remove</button></div>';
		return $html;
	}

	private function get_page_configuration( $page_id ) {
		$pages = $this->config->get( 'pages', array() );

		if ( ! is_array( $pages ) ) {
			return array();
		}

		foreach ( $pages as $page ) {
			if ( is_array( $page ) && ! empty( $page['page_id'] ) && (int) $page['page_id'] === (int) $page_id ) {
				return $page;
			}
		}

		return array();
	}

	private function get_enabled_modules() {
		$modules = array();

		foreach ( $this->registry->all() as $id => $definition ) {
			if ( 'comment' === $id ) {
				continue;
			}

			$options = isset( $definition['options'] ) && is_array( $definition['options'] )
				? $definition['options']
				: array();

			if ( empty( $options['admin_managed'] ) ) {
				continue;
			}

			if ( ! $this->config->get( 'modules.' . $id, false ) ) {
				continue;
			}

			$modules[ $id ] = array(
				'label'       => ! empty( $options['label'] ) ? $options['label'] : $id,
				'description' => ! empty( $options['description'] ) ? $options['description'] : '',
			);
		}

		return $modules;
	}
}
