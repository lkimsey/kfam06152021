<?php

class BrizyPro_Content_Providers_Toolset extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'toolset';

	public function getGroupedPlaceholders() {

		$placeholders   = $this->getDefaultGroupPlaceholders();
		$provider_types = $this->get_provider_types();
		$groups         = array();
		$group_types    = array(
			TYPES_CUSTOM_FIELD_GROUP_CPT_NAME    => 'wpcf-fields',
			TYPES_USER_META_FIELD_GROUP_CPT_NAME => 'wpcf-usermeta',
			TYPES_TERM_META_FIELD_GROUP_CPT_NAME => 'wpcf-termmeta'
		);

		foreach ( $group_types as $cpt => $option_name ) {

			$item = array_map( function( $val ) use ( $cpt, $option_name ) {
					return array_merge( $val, array( 'post_type' => $cpt, 'option_name' => $option_name ) );
				},
				wpcf_admin_fields_get_groups( $cpt )
			);

			$groups = array_merge( $groups, $item );
		}

		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return array( self::PROVIDER_CONFIG_NAME => $placeholders );
		}

		foreach ( $groups as $group ) {

			$group_id = $group['id'];
			$fields   = wpcf_admin_fields_get_fields_by_group( $group_id, 'slug', false, false, false, $group['post_type'], $group['option_name'], true );

			if ( ! is_array( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {

			    if ( ! is_array( $field ) || empty( $field['type'] ) ) {
			        continue;
                }

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
		return new Brizy_Content_Placeholders_Simple( $field['name'], "brizy_dc_{$group_id}_{$field['meta_key']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context, array( 'suppress_filters'  => 'true' ) );
		} );
	}

	private function get_image_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Image( $field['name'], "brizy_dc_{$group_id}_{$field['meta_key']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context, array( 'output'  => 'raw' ) );
		} );
	}

	private function get_link_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Link( $field['name'], "brizy_dc_{$group_id}_{$field['meta_key']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context, array( 'output'  => 'raw' ) );
		} );
	}

	private function get_oembed_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_Oembed( $field['name'], "brizy_dc_{$group_id}_{$field['meta_key']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context, array() );
		} );
	}

	private function get_video_placeholder( $field, $group_id ) {
		return new BrizyPro_Content_Placeholders_SimplePostAware( $field['name'], "brizy_dc_{$group_id}_{$field['meta_key']}", function ( $context ) use ( $field ) {
			return $this->render( $field, $context, array( 'output'  => 'raw' ) );
		} );
	}

	/**
	 * @param $field
	 * @param $context
	 * @param $args
	 *
	 * @return string
	 */
	private function render( $field, $context, $args ) {

		switch ( $field['meta_type'] ) {
			case 'postmeta':
				return types_render_field( $field['id'], array_merge( array( 'post_id' => $context->getWpPost()->ID ), $args ) );
				break;
			case 'usermeta':
				return types_render_usermeta( $field['id'], array_merge( array( 'user_id' => $context->getAuthor() ), $args ) );
				break;
			case 'termmeta':
				return types_render_termmeta( $field['id'], array_merge( array( 'term_id' => $context->getTerm() ), $args ) );
				break;
		}

		return '';
	}

	/**
	 * Get all types which we currently supported. They are specific to this provider only.
	 * @return array
	 */
	private function get_provider_types() {
		return array(
			//'audio'       => array( self::CONFIG_KEY_TEXT ),
			'checkboxes'  => array( self::CONFIG_KEY_TEXT ),
			'checkbox'    => array( self::CONFIG_KEY_TEXT ),
			//'colorpicker' => array( self::CONFIG_KEY_TEXT ),
			'date'        => array( self::CONFIG_KEY_TEXT ),
			'email'       => array( self::CONFIG_KEY_TEXT, self::CONFIG_KEY_LINK ),
			//'embed'       => array( self::CONFIG_KEY_OEMBED ),
			//'file'        => array( self::CONFIG_KEY_IMAGE ),
			'image'       => array( self::CONFIG_KEY_IMAGE ),
			'numeric'     => array( self::CONFIG_KEY_TEXT ),
			'phone'       => array( self::CONFIG_KEY_TEXT ),
			'radio'       => array( self::CONFIG_KEY_TEXT ),
			'select'      => array( self::CONFIG_KEY_TEXT ),
			'skype'       => array( self::CONFIG_KEY_TEXT ),
			'textarea'    => array( self::CONFIG_KEY_TEXT ),
			'textfield'   => array( self::CONFIG_KEY_TEXT ),
			'url'         => array( self::CONFIG_KEY_LINK ),
			'video'       => array( self::CONFIG_KEY_VIDEO ),
			'wysiwyg'     => array( self::CONFIG_KEY_TEXT ),
		);
	}
}