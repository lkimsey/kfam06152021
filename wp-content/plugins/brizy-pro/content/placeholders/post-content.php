<?php

class BrizyPro_Content_Placeholders_PostContent extends BrizyPro_Content_Placeholders_SimplePostAware {

	/**
	 * @return string|callable
	 */
	protected $value;

	/**
	 * BrizyPro_Content_Placeholders_PostContent constructor.
	 *
	 * @param $label
	 * @param $placeholder
	 * @param string $display
	 */
	public function __construct( $label, $placeholder, $display = Brizy_Content_Placeholders_Abstract::DISPLAY_INLINE ) {
		parent::__construct( $label, $placeholder, $this->getTheContentCallback(), $display );
	}

	private function getTheContentCallback() {
		return function ( $context ) {

			$usesEditor = false;

			try {

				if ( Brizy_Editor_Entity::isBrizyEnabled( $context->getWpPost() ) ) {
					$usesEditor = true;
				}

				$post = Brizy_Editor_Post::get( $context->getWpPost() );

			} catch ( Exception $e ) {
			}

			$content = $usesEditor ? $post->get_compiled_page()->get_body() : $context->getWpPost()->post_content;

			if ( ! has_blocks( $content ) ) {
				$content = wpautop( $content );
			}

			return wp_doing_ajax() ? apply_filters( 'the_content', $content ) : $content;
		};
	}
}
