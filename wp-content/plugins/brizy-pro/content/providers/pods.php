<?php

class BrizyPro_Content_Providers_Pods extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'pods';

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
	public function getPlaceholders( $all ) {

		$placeholders   = $this->getDefaultGroupPlaceholders();
		$provider_types = $this->get_provider_types();
		$groups         = pods_api()->load_pods( array( 'table_info' => true, 'fields' => true ) );

		if ( ! is_array( $groups ) ) {
			return array( self::PROVIDER_CONFIG_NAME => $placeholders );
		}

		foreach ( $groups as $group ) {

			if ( empty( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field ) {

				$type = $field['type'];

				if ( ! isset( $provider_types[ $type ] ) ) {
					continue;
				}

				foreach ( $provider_types[ $type ] as $config_type ) {
					/**
					 * @uses get_richText_placeholders(), get_image_placeholders(), get_link_placeholders(), get_oembed_placeholders()
					 */
					$placeholder = call_user_func( array(
						$this,
						"get_{$config_type}_placeholders"
					), $field, $group );

					$placeholders[ $config_type ][] = $placeholder;

					if ( $all && $placeholder instanceof Brizy_Content_Placeholders_Abstract) {
						$placeholders[ $config_type ][] = new BrizyPro_Content_Placeholders_Proxy( "brizy_dc_pod_{$group['type']}_{$field['name']}", $placeholder );
					}
				}
			}
		}

		return array( self::PROVIDER_CONFIG_NAME => array_map( 'array_filter', $placeholders ) );
	}

	/**
	 * @param $field
	 * @param $group
	 *
	 * @return array|Brizy_Content_Placeholders_Simple
	 */
	private function get_richText_placeholders( $field, $group ) {

		return new Brizy_Content_Placeholders_Simple( $field['label'], "brizy_dc_{$group['id']}_{$field['name']}", function ( $context ) use ( $field, $group ) {

			$pod = pods( $field['pod'], $this->get_queried( $context, $group ) );

			return $pod->display( $field['name'] );
		} );
	}

	/**
	 * @param $field
	 * @param $group
	 *
	 * @return array|BrizyPro_Content_Placeholders_Image
	 */
	private function get_image_placeholders( $field, $group ) {

		if ( 'file' === $field['type'] && 'single' !== $field['options']['file_format_type'] ) {
			return array();
		}

		return new BrizyPro_Content_Placeholders_Image( $field['label'], "brizy_dc_{$group['id']}_{$field['name']}", function ( $context ) use ( $field, $group ) {
			$pod  = pods( $field['pod'], $this->get_queried( $context, $group ) );
			$data = $pod->field( $field['name'] );

			return isset( $data['ID'] ) ? $data['ID'] : '';
		} );
	}

	/**
	 * @param $field
	 * @param $group
	 *
	 * @return array|BrizyPro_Content_Placeholders_Link
	 */
	private function get_link_placeholders( $field, $group ) {
		return new BrizyPro_Content_Placeholders_Link( $field['label'], "brizy_dc_{$group['id']}_{$field['name']}", function ( $context ) use ( $field, $group ) {

			$pod  = pods( $field['pod'], $this->get_queried( $context, $group ) );
			$data = $pod->field( $field['name'] );

			return isset( $data['ID'] ) && ( $data = wp_get_attachment_url( $data['ID'] ) ) ? $data : $data;
		} );
	}

	/**
	 * @param $field
	 * @param $group
	 *
	 * @return Brizy_Content_Placeholders_Simple
	 */
	private function get_oembed_placeholders( $field, $group ) {
		return new Brizy_Content_Placeholders_Simple( $field['label'], "brizy_dc_{$group['id']}_{$field['name']}", function ( $context ) use ( $field, $group ) {

			$pod = pods( $field['pod'], $this->get_queried( $context, $group ) );

			return $pod->display( $field['name'] );
		} );
	}

	/**
	 * @param $context
	 * @param $group
	 *
	 * @return null
	 */
	private function get_queried( $context, $group ) {

		$queried = null;

		switch ( $group['type'] ) {
			case 'post_type':
				$queried = $context->getWpPost()->ID;
				break;
			case 'user':
				$queried = $context->getAuthor();
				break;
			case 'taxonomy':
				$queried = $context->getTerm();
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
			'text'      => array( self::CONFIG_KEY_TEXT ),
			'website'   => array( self::CONFIG_KEY_LINK ),
			'phone'     => array( self::CONFIG_KEY_TEXT ),
			'email'     => array( self::CONFIG_KEY_TEXT, self::CONFIG_KEY_LINK ),
			'password'  => array( self::CONFIG_KEY_TEXT ),
			'paragraph' => array( self::CONFIG_KEY_TEXT ),
			'wysiwyg'   => array( self::CONFIG_KEY_TEXT ),
			//'code'      => array( self::CONFIG_KEY_TEXT ),
			'datetime'  => array( self::CONFIG_KEY_TEXT ),
			'date'      => array( self::CONFIG_KEY_TEXT ),
			'time'      => array( self::CONFIG_KEY_TEXT ),
			'boolean'   => array( self::CONFIG_KEY_TEXT ),
			//'color'     => array( self::CONFIG_KEY_TEXT ),
			'number'    => array( self::CONFIG_KEY_TEXT ),
			'currency'  => array( self::CONFIG_KEY_TEXT ),
			//'oembed'    => array( self::CONFIG_KEY_OEMBED ),
			//'pick'      => array( self::CONFIG_KEY_TEXT ),
			'file'      => array( self::CONFIG_KEY_IMAGE ),
			'avatar'    => array( self::CONFIG_KEY_IMAGE ),
		);
	}
}