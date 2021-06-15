<?php

class BrizyPro_Content_Placeholders_Oembed extends Brizy_Content_Placeholders_Simple {

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return false|mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		$value = parent::getValue( $context, $contentPlaceholder );

		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return wp_oembed_get( $value );
		}

		return $value;
	}
}