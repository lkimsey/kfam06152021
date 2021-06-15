<?php

class BrizyPro_Content_Placeholders_SimpleProductAware extends Brizy_Content_Placeholders_Simple {

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		global $product;

		if ( ! $product ) {
			return '';
		}

        ob_start(); ob_clean();
		parent::getValue( $context, $contentPlaceholder );
		return ob_get_clean();
	}
}