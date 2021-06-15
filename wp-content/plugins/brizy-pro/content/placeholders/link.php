<?php

class BrizyPro_Content_Placeholders_Link extends Brizy_Content_Placeholders_Simple {

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return false|mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {
		$link = parent::getValue( $context, $contentPlaceholder );

		if ( filter_var( $link, FILTER_VALIDATE_EMAIL ) ) {
			return "mailto:{$link}";
		}

		return $link;
	}

}