<?php

class BrizyPro_Content_Providers_Metabox extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'metabox';

	public function getGroupedPlaceholders() {

		$placeholders   = $this->getDefaultGroupPlaceholders();
		$provider_types = $this->get_provider_types();
		$groups         = rwmb_get_registry( 'meta_box' )->all();

		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return array( self::PROVIDER_CONFIG_NAME => $placeholders );
		}

		foreach ( $groups as $group_id => $group ) {

			if ( empty( $group->meta_box ) || empty( $group->meta_box['fields'] ) || ! is_array( $group->meta_box['fields'] ) ) {
				continue;
			}

			$fields = $group->meta_box['fields'];

			foreach ( $fields as $field ) {

				$type = $field['type'];

				if ( ! isset( $provider_types[ $type ] ) ) {
					continue;
				}

				foreach ( $provider_types[ $type ] as $config_type ) {
					/**
					 * @uses get_richText_placeholder(), get_image_placeholder(), get_link_placeholder(), get_oembed_placeholder(), get_video_placeholder()
					 */
					$placeholders[ $config_type ][] = call_user_func( array(
						$this,
						"get_{$config_type}_placeholder"
					), $field, $group_id );
				}
			}
		}

		return array( self::PROVIDER_CONFIG_NAME => array_map( 'array_filter', $placeholders ) );
	}

	private function get_richText_placeholder( $field, $group_id ) {
		return new Brizy_Content_Placeholders_Simple( $field['name'], "brizy_dc_{$group_id}_{$field['id']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context );
		} );
	}

	private function get_image_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Image( $field['name'], "brizy_dc_{$group_id}_{$field['id']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context );
		} );
	}

	private function get_link_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Link( $field['name'], "brizy_dc_{$group_id}_{$field['id']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context );
		} );
	}

	private function get_oembed_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Oembed( $field['name'], "brizy_dc_{$group_id}_{$field['id']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context );
		} );
	}

	private function get_video_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_SimplePostAware( $field['name'], "brizy_dc_{$group_id}_{$field['id']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context );
		} );
	}

	/**
	 * @param $field
	 * @param $context
	 *
	 * @return string
	 */
	private function render( $field, $context ) {

		$object = $context->getObjectData();
		$value  = rwmb_meta( $field['id'], array( 'object_type' => $object['object_type'] ), $object['object_id'] );

		switch ( $field['type'] ) {
			case 'button_group':
			case 'autocomplete':
			case 'checkbox_list':
				if ( ! empty( $field['clone'] ) ) {
					$value = $value ? implode( ', ', call_user_func_array( 'array_merge', $value ) ) : '';
				} else {
					$value = is_array( $value ) ? implode( ', ', $value ) : $value;
				}
				break;
			case 'select_advanced':
			case 'select':
				if ( ! empty( $field['clone'] ) && ! empty( $field['multiple'] ) ) {
					$value = $value ? implode( ', ', call_user_func_array( 'array_merge', $value ) ) : '';
				} else {
					$value = is_array( $value ) ? implode( ', ', $value ) : $value;
				}
				break;
			case 'color':
			case 'date':
			case 'image_select':
			case 'number':
			case 'radio':
			case 'range':
			case 'slider':
			case 'text':
			case 'textarea':
			case 'time':
			case 'wysiwyg':
				$value = is_array( $value ) ? implode( ', ', $value ) : $value;
				break;
			case 'checkbox':
				if ( is_array( $value ) ) {
					$value = empty( $value ) ? 0 : 1;
				}
				break;
			case 'image':
			case 'image_advanced':
			case 'image_upload':
				if ( empty( $field['clone'] ) ) {
					foreach ( $value as $img ) {
						$value = $img['ID'];
						break;
					}
				} else {
					$value = '';
				}
				break;
			case 'single_image':
				$value = isset( $value['ID'] ) ? $value['ID'] : '';
				break;
			case 'switch':
				$value = rwmb_the_value( $field['id'], array( 'object_type' => $object['object_type'] ), $object['object_id'], false );
				break;
			case 'taxonomy_advanced':
				if ( $value && ! is_wp_error( $value ) ) {
					if ( ! empty( $field['clone'] ) ) {
						$value = $value ? implode( ', ', wp_list_pluck( call_user_func_array( 'array_merge', $value ), 'name' ) ) : '';
					} else {
						$value = isset( $value->name ) ? $value->name : implode( ', ', wp_list_pluck( $value, 'name' ) );
					}
				}
				break;
		}

		return $value;
	}

	/**
	 * Get all types which we currently supported. They are specific to this provider only.
	 * @return array
	 */
	private function get_provider_types() {

		// TODO - We have no url/link type here

		return array(
			'autocomplete'      => array( self::CONFIG_KEY_TEXT ),
			//'background'  => array( self::CONFIG_KEY_IMAGE ),
			'button_group'      => array( self::CONFIG_KEY_TEXT ),
			'checkbox'          => array( self::CONFIG_KEY_TEXT ),
			'checkbox_list'     => array( self::CONFIG_KEY_TEXT ),
			'color'             => array( self::CONFIG_KEY_TEXT ),
			//'custom_html' => array( self::CONFIG_KEY_TEXT ),
			'date'              => array( self::CONFIG_KEY_TEXT ),
			//'file'        => array( self::CONFIG_KEY_IMAGE ),
			//'file_advanced'        => array( self::CONFIG_KEY_IMAGE ),
			//'file_upload'        => array( self::CONFIG_KEY_IMAGE ),
			//'hidden'        => array( self::CONFIG_KEY_IMAGE ),
			'image'             => array( self::CONFIG_KEY_IMAGE ),
			'image_advanced'    => array( self::CONFIG_KEY_IMAGE ),
			'image_select'      => array( self::CONFIG_KEY_TEXT ),
			'image_upload'      => array( self::CONFIG_KEY_IMAGE ),
			//'key_value'      => array( self::CONFIG_KEY_TEXT ),
			'number'            => array( self::CONFIG_KEY_TEXT ),
			//'oembed'     => array( self::CONFIG_KEY_OEMBED ),
			'radio'             => array( self::CONFIG_KEY_TEXT ),
			'range'             => array( self::CONFIG_KEY_TEXT ),
			'select'            => array( self::CONFIG_KEY_TEXT ),
			'select_advanced'   => array( self::CONFIG_KEY_TEXT ),
			//'sidebar'     => array( self::CONFIG_KEY_TEXT ), // a new type of field
			'single_image'      => array( self::CONFIG_KEY_IMAGE ),
			'slider'            => array( self::CONFIG_KEY_TEXT ),
			'switch'            => array( self::CONFIG_KEY_TEXT ),
			'taxonomy_advanced' => array( self::CONFIG_KEY_TEXT ),
			'text'              => array( self::CONFIG_KEY_TEXT, self::CONFIG_KEY_LINK ),
			'textarea'          => array( self::CONFIG_KEY_TEXT ),
			'time'              => array( self::CONFIG_KEY_TEXT ),
			'video'             => array( self::CONFIG_KEY_VIDEO ),
			'wysiwyg'           => array( self::CONFIG_KEY_TEXT ),
		);
	}
}