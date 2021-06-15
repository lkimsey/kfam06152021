<?php

class BrizyPro_Content_Placeholders_FeaturedImg extends BrizyPro_Content_Placeholders_Image {

	/**
	 * BrizyPro_Content_PlaceholderFeaturedImg constructor.
	 *
	 * @param $label
	 * @param $placeholder
	 * @param Brizy_Content_Context $context
	 */
	public function __construct( $label, $placeholder ) {
		$this->label       = $label;
		$this->placeholder = $placeholder;

		/**
		 * @param Brizy_Content_Context $context
		 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
		 *
		 * @return string|void
		 */
		$this->value = function ( $context, $contentPlaceholder ) {

			$noImageUrl = BRIZY_PRO_PLUGIN_URL . "/public/images/no-image.png";

			if ( ! $context->getWpPost() ) {
				return $noImageUrl;
			}

			$attributes = $contentPlaceholder->getAttributes();

			$attachmentId = get_post_thumbnail_id( $context->getWpPost()->ID );

			if ( ! $attachmentId ) {
				return $noImageUrl;
			}

			$thumbnailUid = get_post_meta( $attachmentId, 'brizy_attachment_uid', true );

			if ( ! $thumbnailUid ) {
				$thumbnailUid = $attachmentId;
			}

			$type = get_post_mime_type( $attachmentId );

			if ( $type === 'image/svg+xml' ) {
				return $this->getUrlAsSvg( $attachmentId, $thumbnailUid, $attributes, $context );
			}

			return $this->getUrlAsImage( $attachmentId, $thumbnailUid, $attributes, $context );
		};
	}

	private function getUrlAsImage( $attachmentId, $thumbnailUid, $attributes, $context ) {

		$noImageUrl = BRIZY_PRO_PLUGIN_URL . "/public/images/no-image.png";

		$imageMeta = wp_get_attachment_metadata( $attachmentId );

		if ( ! isset( $imageMeta['height'] ) || $imageMeta['height'] == 0 ) {
			return $noImageUrl;
		}

		$focalPoint = get_post_meta( $context->getWpPost()->ID, 'brizy_attachment_focal_point', true );

		if ( ! $focalPoint ) {
			$focalPoint = array( 'x' => 50, 'y' => 50 );
		}

		list( $ox, $oy, $nW, $nH, $cW, $cH ) = $this->calculateImageOffsetByFocalPoint(
			(int) $imageMeta['width'],
			(int) $imageMeta['height'],
			(int) $attributes['cW'],
			(int) $attributes['cH'],
			$focalPoint['x'],
			$focalPoint['y'] );

		$filterParams = array(
			'iW' => (int) $nW,
			'iH' => (int) $nH,
			'oX' => $ox,
			'oY' => $oy,
			'cW' => (int) $cW,
			'cH' => (int) $cH,
		);
		$params       = array(
			Brizy_Editor::prefix( '_media' ) => $thumbnailUid,
			Brizy_Editor::prefix( '_crop' )  => http_build_query( $filterParams ),
			Brizy_Editor::prefix( '_post' )  => $context->getWpPost()->ID
		);

		return site_url( '?' . http_build_query( $params ) );
	}

	private function getUrlAsSvg( $attachmentId, $thumbnailUid, $attributes, $context ) {

		$params = array(
			Brizy_Editor::prefix( '_attachment' ) => $thumbnailUid,
		);

		return site_url( '?' . http_build_query( $params ) );
	}


	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {
		return call_user_func( $this->value, $context, $contentPlaceholder );
	}

	public function getAttachmentId( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {
		return get_post_thumbnail_id( $context->getWpPost()->ID );
	}
}