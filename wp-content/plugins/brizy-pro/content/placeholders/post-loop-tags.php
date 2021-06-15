<?php

class BrizyPro_Content_Placeholders_PostLoopTags extends Brizy_Content_Placeholders_Abstract {

	/**
	 * @var
	 */
	private $twig;

	/**
	 * BrizyPro_Content_Placeholders_PostLoopPagination constructor.
	 * @throws Exception
	 */
	public function __construct() {
		$this->placeholder = 'brizy_dc_post_loop_tags';
		$this->label       = 'Post loop tags';
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

		$attributes = $contentPlaceholder->getAttributes();

		$tagsContext              = array();
		$tagsContext['tags']      = $this->getTagList();
		$tagsContext['ulClassName'] = isset( $attributes['ulClassName'] ) ? $attributes['ulClassName'] : '';
		$tagsContext['liClassName'] = isset( $attributes['liClassName'] ) ? $attributes['liClassName'] : '';

		return Brizy_TwigEngine::instance( BRIZY_PRO_PLUGIN_PATH . "/content/views/" )->render( 'tags.html.twig', $tagsContext );
	}

	protected function getTagList() {
		return get_tags( [ 'hide_empty' => true ] );
	}

	/**
	 * @return mixed|string
	 */
	protected function getOptionValue() {
		return null;
	}

}
