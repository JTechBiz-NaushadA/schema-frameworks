<?php

namespace SchemaFrameworks\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SchemaFrameworks\Registry;

class Schema_Configuration {

	private $registry;

	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	public function get_fields( $module_id ) {
		$definition = $this->registry->get( $module_id );

		if ( empty( $definition['options'] ) || ! is_array( $definition['options'] ) ) {
			return array();
		}

		$fields = isset( $definition['options']['config_fields'] ) && is_array( $definition['options']['config_fields'] )
			? $definition['options']['config_fields']
			: array();

		return $this->normalize_fields( $fields );
	}

	public function has_fields( $module_id ) {
		return ! empty( $this->get_fields( $module_id ) );
	}

	public function sanitize( $module_id, $value ) {
		$fields = $this->get_fields( $module_id );

		if ( ! is_array( $value ) ) {
			return array();
		}

		return $this->sanitize_fields( $fields, $value );
	}

	public function render( $module_id, $value = array(), $name_prefix = '', $id_prefix = '', $template = false ) {
		$fields = $this->get_fields( $module_id );

		if ( empty( $fields ) ) {
			return '';
		}

		$value = is_array( $value ) ? $value : array();

		$html  = '<div class="schema-frameworks-schema-config" data-schema-config="' . esc_attr( $module_id ) . '">';
		$html .= '<h4>' . esc_html( $this->get_label( $module_id ) ) . ' Configuration</h4>';
		$html .= '<table class="form-table" role="presentation">';
		$html .= $this->render_fields( $fields, $value, $name_prefix, $id_prefix, $template );
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	private function get_label( $module_id ) {
		$definition = $this->registry->get( $module_id );
		$options = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();
		return ! empty( $options['label'] ) ? $options['label'] : $module_id;
	}

	private function normalize_fields( $fields ) {
		$normalized = array();

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_key = isset( $field['key'] ) ? (string) $field['key'] : (string) $key;
			$field['key'] = preg_replace( '/[^A-Za-z0-9_@-]/', '', $field_key );
			if ( empty( $field['key'] ) ) {
				continue;
			}

			$field['type'] = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$field['label'] = isset( $field['label'] ) ? (string) $field['label'] : $field['key'];
			$field['description'] = isset( $field['description'] ) ? (string) $field['description'] : '';

			if ( in_array( $field['type'], array( 'group', 'repeater' ), true ) ) {
				$field['fields'] = isset( $field['fields'] ) && is_array( $field['fields'] )
					? $this->normalize_fields( $field['fields'] )
					: array();
			}

			$normalized[] = $field;
		}

		return $normalized;
	}

	private function sanitize_fields( $fields, $value ) {
		$output = array();

		foreach ( $fields as $field ) {
			$key = $field['key'];
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}

			$raw = $value[ $key ];

			if ( 'group' === $field['type'] ) {
				$group = is_array( $raw ) ? $raw : array();
				$clean_group = $this->sanitize_fields( $field['fields'], $group );
				if ( ! empty( $clean_group ) ) {
					$output[ $key ] = $clean_group;
				}
				continue;
			}

			if ( 'repeater' === $field['type'] ) {
				$rows = is_array( $raw ) ? $raw : array();
				$clean_rows = array();
				foreach ( $rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$clean_row = $this->sanitize_fields( $field['fields'], $row );
					if ( ! empty( $clean_row ) ) {
						$clean_rows[] = $clean_row;
					}
				}
				if ( ! empty( $clean_rows ) ) {
					$output[ $key ] = $clean_rows;
				}
				continue;
			}

			if ( 'url_list' === $field['type'] || 'text_list' === $field['type'] ) {
				$lines = is_array( $raw ) ? $raw : preg_split( '/\r\n|\r|\n/', (string) $raw );
				$clean_lines = array();
				foreach ( $lines as $line ) {
					$line = trim( (string) $line );
					if ( '' === $line ) {
						continue;
					}
					$clean = 'url_list' === $field['type'] ? esc_url_raw( $line ) : sanitize_text_field( $line );
					if ( '' !== $clean && ! in_array( $clean, $clean_lines, true ) ) {
						$clean_lines[] = $clean;
					}
				}
				if ( ! empty( $clean_lines ) ) {
					$output[ $key ] = $clean_lines;
				}
				continue;
			}

			$clean = $this->sanitize_value( $field['type'], $raw );
			if ( '' === $clean || null === $clean ) {
				continue;
			}
			$output[ $key ] = $clean;
		}

		return $output;
	}

	private function sanitize_value( $type, $value ) {
		if ( is_array( $value ) ) {
			return null;
		}

		$value = is_string( $value ) ? trim( wp_unslash( $value ) ) : $value;

		switch ( $type ) {
			case 'url':
				return esc_url_raw( (string) $value );
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'number':
				return is_numeric( $value ) ? (string) $value : '';
			case 'date':
				return sanitize_text_field( (string) $value );
			case 'boolean':
				return ! empty( $value );
			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	private function render_fields( $fields, $value, $name_prefix, $id_prefix, $template ) {
		$html = '';

		foreach ( $fields as $field ) {
			$key = $field['key'];
			$current = isset( $value[ $key ] ) ? $value[ $key ] : '';
			$field_name = $name_prefix . '[' . $key . ']';
			$field_id = $id_prefix . '-' . sanitize_html_class( $key );

			if ( 'group' === $field['type'] ) {
				$html .= '<tr><th scope="row">' . esc_html( $field['label'] ) . '</th><td>';
				$html .= '<div class="schema-frameworks-field-group">';
				$html .= $this->render_group_fields( $field['fields'], is_array( $current ) ? $current : array(), $field_name, $field_id, $template );
				$html .= '</div>';
				if ( ! empty( $field['description'] ) ) {
					$html .= '<p class="description">' . esc_html( $field['description'] ) . '</p>';
				}
				$html .= '</td></tr>';
				continue;
			}

			if ( 'repeater' === $field['type'] ) {
				$html .= '<tr><th scope="row">' . esc_html( $field['label'] ) . '</th><td>';
				$html .= '<div class="schema-frameworks-repeater" data-repeater="' . esc_attr( $field['key'] ) . '">';
				$rows = is_array( $current ) ? $current : array();
				if ( empty( $rows ) ) {
					$rows = array( array() );
				}
				foreach ( $rows as $row_index => $row ) {
					$html .= $this->render_repeater_row( $field['fields'], is_array( $row ) ? $row : array(), $field_name . '[' . $row_index . ']', $field_id . '-' . $row_index, $template );
				}
				$html .= '<button type="button" class="button schema-frameworks-repeater-add">Add ' . esc_html( rtrim( $field['label'], 's' ) ) . '</button>';
				$html .= '<template class="schema-frameworks-repeater-template">';
				$html .= $this->render_repeater_row( $field['fields'], array(), $field_name . '[__ROW_INDEX__]', $field_id . '-__ROW_INDEX__', true );
				$html .= '</template>';
				$html .= '</div>';
				if ( ! empty( $field['description'] ) ) {
					$html .= '<p class="description">' . esc_html( $field['description'] ) . '</p>';
				}
				$html .= '</td></tr>';
				continue;
			}

			$html .= '<tr><th scope="row"><label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			$html .= $this->render_control( $field, $current, $field_name, $field_id, $template );
			if ( ! empty( $field['description'] ) ) {
				$html .= '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}
			$html .= '</td></tr>';
		}

		return $html;
	}

	private function render_group_fields( $fields, $value, $name_prefix, $id_prefix, $template ) {
		$html = '<div class="schema-frameworks-group-fields">';
		foreach ( $fields as $field ) {
			$key = $field['key'];
			$current = isset( $value[ $key ] ) ? $value[ $key ] : '';
			$field_name = $name_prefix . '[' . $key . ']';
			$field_id = $id_prefix . '-' . sanitize_html_class( $key );
			$html .= '<div class="schema-frameworks-group-field"><label for="' . esc_attr( $field_id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label>';
			$html .= $this->render_control( $field, $current, $field_name, $field_id, $template );
			if ( ! empty( $field['description'] ) ) {
				$html .= '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	private function render_repeater_row( $fields, $value, $name_prefix, $id_prefix, $template ) {
		$html = '<div class="schema-frameworks-repeater-row">';
		$html .= '<div class="schema-frameworks-repeater-row-fields">';
		foreach ( $fields as $field ) {
			$key = $field['key'];
			$current = isset( $value[ $key ] ) ? $value[ $key ] : '';
			$field_name = $name_prefix . '[' . $key . ']';
			$field_id = $id_prefix . '-' . sanitize_html_class( $key );
			$html .= '<div class="schema-frameworks-repeater-field"><label for="' . esc_attr( $field_id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label>';
			$html .= $this->render_control( $field, $current, $field_name, $field_id, $template );
			$html .= '</div>';
		}
		$html .= '</div><button type="button" class="button-link-delete schema-frameworks-repeater-remove">Remove</button></div>';
		return $html;
	}

	private function render_control( $field, $value, $name, $id, $template ) {
		$type = $field['type'];
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		if ( 'boolean' === $type ) {
			return '<input type="hidden" name="' . esc_attr( $name ) . '" value="0" /><label class="schema-frameworks-checkbox-control"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . ' /> <span>Yes</span></label>';
		}

		if ( 'url_list' === $type || 'text_list' === $type ) {
			$lines = is_array( $value ) ? implode( "\n", $value ) : (string) $value;
			return '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $lines ) . '</textarea>';
		}

		if ( 'textarea' === $type ) {
			return '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
		}

		$input_type = in_array( $type, array( 'url', 'number', 'date' ), true ) ? $type : 'text';
		return '<input class="regular-text" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
	}
}