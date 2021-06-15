<?php

class BrizyPro_Content_Placeholders_PostTags extends Brizy_Content_Placeholders_Abstract {

	/**
	 * BrizyPro_Content_Placeholders_PostLoopPagination constructor.
	 * @throws Exception
	 */
	public function __construct() {
		$this->placeholder = 'brizy_dc_post_tags';
		$this->label       = 'Post tags';
		$this->setDisplay( self::DISPLAY_BLOCK );

	}

	/**
	 * @param Brizy_Content_Context $context
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 *
	 * @return mixed|string
	 * @throws Twig_Error_Loader
	 * @throws Twig_Error_Runtime
	 * @throws Twig_Error_Syntax
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		if ( $context->getWpPost() instanceof WP_Post ) {
			$tags = wp_get_post_tags( $context->getWpPost()->ID );

			return implode( ',', array_map( function ( $tag ) {
				return $tag->slug;
			}, $tags ) );
		}
		return '';
	}


	/**
	 * @return mixed|string
	 */
	protected function getOptionValue() {
		return null;
	}

}