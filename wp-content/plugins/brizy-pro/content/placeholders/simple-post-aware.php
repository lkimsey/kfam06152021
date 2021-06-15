<?php

class BrizyPro_Content_Placeholders_SimplePostAware extends Brizy_Content_Placeholders_Simple {

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		if ( ! $context->getWpPost() ) {
			return;
		}

		return parent::getValue( $context, $contentPlaceholder );
	}

	/**
	 * @return mixed|string
	 */
	protected function getOptionValue() {

		return $this->getReplacePlaceholder();
	}
}