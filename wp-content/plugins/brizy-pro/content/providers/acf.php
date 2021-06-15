<?php

class BrizyPro_Content_Providers_Acf extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'acf';

	public function getAllPlaceholders() {
		$out = array();

		foreach ( $this->getPlaceholders( true ) as $placeholders ) {
			$out = array_merge( $out, call_user_func_array( 'array_merge', $placeholders ) );
		}

		return $out;
	}

	public function getGroupedPlaceholders() {
		return $this->getPlaceholders( false );
	}

	/**
	 * @param $all
	 *
	 * @return array
	 */
	private function getPlaceholders( $all ) {

		$placeholders   = $this->getDefaultGroupPlaceholders();
		$provider_types = $this->get_provider_types();
		$groups         = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : apply_filters( 'acf/get_field_groups', array() );

		if ( ! is_array( $groups ) ) {
			return array( self::PROVIDER_CONFIG_NAME => $placeholders );
		}

		foreach ( $groups as $group ) {

			$fields = function_exists( 'acf_get_fields' ) ? acf_get_fields( $group['ID'] ) : apply_filters( 'acf/field_group/get_fields', array(), $group['id'] );

			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					$type = $field['type'];

					if ( ! isset( $provider_types[ $type ] ) ) {
						continue;
					}

					foreach ( $provider_types[ $type ] as $config_type ) {
						/**
						 * @uses get_richText_placeholders(), get_image_placeholders(), get_link_placeholders()
						 */
						$placeholder = call_user_func( array(
							$this,
							"get_{$config_type}_placeholders"
						), $field );

						$placeholders[ $config_type ][] = $placeholder;

						if ( $all && $placeholder ) {
							$type_part = $config_type === self::CONFIG_KEY_TEXT ? 'text' : ( $config_type === self::CONFIG_KEY_IMAGE ? 'img' : $config_type );
							$placeholders[ $config_type ][] = new BrizyPro_Content_Placeholders_Proxy( "brizy_dc_acf_{$type_part}_{$field['name']}", $placeholder );
						}
					}
				}
			}
		}

		return array( self::PROVIDER_CONFIG_NAME => array_map( 'array_filter', $placeholders ) );
	}

	private function get_richText_placeholders( $field ) {

		return new BrizyPro_Content_Placeholders_SimplePostAware( $field['label'], "brizy_dc_{$field['parent']}_{$field['name']}", function ( $context ) use ( $field ) {

			$data = $this->get_field( $field['key'], $this->get_queried( $context ) );
			$type = $field['type'];

			if ( empty( $data ) ) {
				return '';
			}

			if ( in_array( $type, array( 'select', 'checkbox', 'radio' ) ) ) {

				$data = (array) $data;

				if ( ! empty( $field['return_format'] ) ) {

					if ( 'label' === $field['return_format'] ) {
						$data = array_intersect( $field['choices'], $data );
					} elseif ( 'array' === $field['return_format'] ) {

						if ( isset( $data['label'] ) ) {
							unset( $data['label'] );
						} else {
							$data = array_intersect_key( $field['choices'], array_fill_keys( wp_list_pluck( $data, 'value' ), '' ) );
						}
					}
				} else {
					$data = array_intersect_key( $field['choices'], array_fill_keys( $data, '' ) );
				}

				$data = implode( ', ', $data );

			} elseif ( 'true_false' === $type ) {
				$data = $data ? 'True' : 'False';
			}

			return $data;
		} );
	}

	private function get_image_placeholders( $field ) {

		return new BrizyPro_Content_Placeholders_Image( $field['label'], "brizy_dc_{$field['parent']}_{$field['name']}", function ( $context ) use ( $field ) {

			$data = $this->get_field( $field['key'], $this->get_queried( $context ) );

			if ( isset( $data['id'] ) ) {
				$data = $data['id'];
			}

			return $data;
		} );
	}

	private function get_link_placeholders( $field ) {

		return new BrizyPro_Content_Placeholders_Link( $field['label'], "brizy_dc_{$field['parent']}_{$field['name']}", function ( $context ) use ( $field ) {

			$data = $this->get_field( $field['key'], $this->get_queried( $context ) );
			$type = $field['type'];

			if ( empty( $data ) ) {
				return '';
			}

			if ( 'link' === $type ) {

				if ( ! empty( $field['return_format'] ) && 'array' === $field['return_format'] ) {
					$data = $data['url'];
				}

			} elseif ( 'email' === $type ) {

				if ( $data ) {
					$data = "mailto:{$data}";
				}
			} elseif ( 'taxonomy' === $type ) {
				if ( $data ) {
					$data = is_array( $data ) && isset( $data[0] ) ? $data[0] : $data;
					$data = ( $data = get_term_link( $data, $field['taxonomy'] ) ) && ! is_wp_error( $data ) && is_string( $data ) ? $data : '';
				}

			} elseif ( in_array( $type, array( 'post_object', 'relationship' ) ) ) {

				$data = is_array( $data ) && isset( $data[0] ) ? $data[0] : $data;
				$data = get_permalink( $data );
			}

			return is_array( $data ) && isset( $data[0] ) ? $data[0] : $data;
		} );
	}

	private function get_field( $selector, $post_id ) {

        add_filter( 'acf/validate_post_id', [ $this, 'get_post_id' ], 11, 2 );

	    $value = get_field( $selector, $post_id );

        remove_filter( 'acf/validate_post_id', [ $this, 'get_post_id' ], 11 );

	    return $value;
    }

    public function get_post_id( $post_id, $_post_id  ) {
        return $_post_id;
    }

	/**
	 * @param $context
	 *
	 * @return string
	 */
	private function get_queried( $context ) {

		$object = $context->getObjectData();

		switch ( $object['object_type'] ) {
			case 'user':
				$queried = "user_{$object['object_id']}";
				break;
			case 'tax':
				$queried = "{$object['tax']}_{$object['object_id']}";
				break;
			default:
				$queried = $object['object_id'];
				break;
		}

		return $queried;
	}

	/**
	 * Get all types which we currently supported. They are specific to this provider only.
	 * @return array
	 */
	private function get_provider_types() {
		return array(
			'text'             => array( self::CONFIG_KEY_TEXT ),
			'textarea'         => array( self::CONFIG_KEY_TEXT ),
			'number'           => array( self::CONFIG_KEY_TEXT ),
			'range'            => array( self::CONFIG_KEY_TEXT ),
			'email'            => array( self::CONFIG_KEY_LINK, self::CONFIG_KEY_TEXT ),
			'url'              => array( self::CONFIG_KEY_LINK ),
			'password'         => array( self::CONFIG_KEY_TEXT ),
			'wysiwyg'          => array( self::CONFIG_KEY_TEXT ),
			'select'           => array( self::CONFIG_KEY_TEXT ),
			'checkbox'         => array( self::CONFIG_KEY_TEXT ),
			'radio'            => array( self::CONFIG_KEY_TEXT ),
			'button_group'     => array( self::CONFIG_KEY_TEXT ),
			'true_false'       => array( self::CONFIG_KEY_TEXT ),
			'date_picker'      => array( self::CONFIG_KEY_TEXT ),
			'date_time_picker' => array( self::CONFIG_KEY_TEXT ),
			'time_picker'      => array( self::CONFIG_KEY_TEXT ),
			'color_picker'     => array( self::CONFIG_KEY_TEXT ),
			'image'            => array( self::CONFIG_KEY_IMAGE ),
			//'file'         => array( self::CONFIG_KEY_LINK ),
			//'oembed'       => array( self::CONFIG_KEY_OEMBED ),
			'link'             => array( self::CONFIG_KEY_LINK ),
			'page_link'        => array( self::CONFIG_KEY_LINK ),
			'post_object'      => array( self::CONFIG_KEY_LINK ),
			'relationship'     => array( self::CONFIG_KEY_LINK ),
			'taxonomy'         => array( self::CONFIG_KEY_LINK ),
			//'user'         => array( self::CONFIG_KEY_TEXT )
		);
	}
}