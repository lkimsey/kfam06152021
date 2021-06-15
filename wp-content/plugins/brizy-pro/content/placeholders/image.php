<?php

class BrizyPro_Content_Placeholders_Image extends Brizy_Content_Placeholders_Simple {

	use Brizy_Content_Placeholders_ImageAttributesAware;

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return false|mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		$noImageurl = BRIZY_PRO_PLUGIN_URL . "/public/images/no-image.png";

		if ( ! $context->getWpPost() ) {
			return $noImageurl;
		}

		$attributes = $contentPlaceholder->getAttributes();

		$attachmentId = $this->getAttachmentId( $context, $contentPlaceholder );

		if ( ! $attachmentId || ! wp_attachment_is_image( $attachmentId ) ) {
			return $noImageurl;
		}

		$thumbnailUid = get_post_meta( $attachmentId, 'brizy_attachment_uid', true );

		if ( ! $thumbnailUid ) {
			$thumbnailUid = $attachmentId;
		}

		$imageMeta = wp_get_attachment_metadata( $attachmentId );

		if ( ! isset( $imageMeta['height'] ) || $imageMeta['height'] == 0 ) {
			return $noImageurl;
		}

		$focalPoint = get_post_meta( $attachmentId, 'brizy_attachment_focal_point', true );

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

		$params = array(
			Brizy_Editor::prefix( '_media' ) => $thumbnailUid,
			Brizy_Editor::prefix( '_crop' )  => http_build_query( $filterParams ),
			Brizy_Editor::prefix( '_post' )  => $context->getWpPost()->ID
		);

		$site_url = site_url( '?' . http_build_query( $params ) );

		return $site_url;
	}

	/**
	 * @param $iW
	 * @param $iH
	 * @param $cW
	 * @param $cH
	 * @param $ofX
	 * @param $ofY
	 *
	 * @return array
	 */
	protected function calculateImageOffsetByFocalPoint( $iW, $iH, $cW, $cH, $ofX, $ofY ) {

		// check if the container sizes are valid
		if ( $cW == 0 ) {
			$cW = (int) ( ( $iW * $cH ) / $iH );
		}

		if ( $cH == 0 ) {
			$cH = (int) ( ( $iH * $cW ) / $iW );
		}


		$rC    = (int) $cW / $cH;
		$rI    = (int) $iW / $iH;
		$halfW = (int) $cW / 2;
		$halfH = (int) $cH / 2;

		if ( $rI >= $rC ) {
			$nW = $cH / $iH * $iW; // width after adjust to container size
			$nH = $cH; // height after adjust to container size
			$fX = (int) ( $nW * ( $ofX / 100 ) );

			if ( $fX + $halfW > $nW ) {
				$oX = $nW - $cW;
			} elseif ( $fX - $halfW <= 0 ) {
				$oX = 0;
			} else {
				$oX = $fX - $halfW;
			}
			//$oX = $oX * $iH / $cH;
			$oY = 0;
		} else {
			$oX = 0;
			$nW = $cW; // width after adjust to container size
			$nH = $cW / $iW * $iH;
			$fY = (int) ( $nH * ( $ofY / 100 ) );

			if ( $fY + $halfH > $nH ) {
				$oY = $nH - $cH;
			} elseif ( $fY - $halfH <= 0 ) {
				$oY = 0;
			} else {
				$oY = $fY - $halfH;
			}
			//$oY = $oY * $iW / $cW;
		}

		return array( (int) $oX, (int) $oY, $nW, $nH, $cW, $cH );
	}


	/**
	 * Get an attachment ID given a URL.
	 *
	 * @param string $url
	 *
	 * @return int Attachment ID on success, 0 on failure
	 */
	protected function getAttachmentIdByUrl( $url ) {

		$attachment_id = 0;
		$dir           = wp_upload_dir();

		if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?

			$file       = basename( $url );
			$query_args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'value'   => $file,
						'compare' => 'LIKE',
						'key'     => '_wp_attachment_metadata',
					),
				)
			);

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {

				foreach ( $query->posts as $post_id ) {
					$meta                = wp_get_attachment_metadata( $post_id );
					$original_file       = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_id = $post_id;
						break;
					}
				}
			}
		}

		return $attachment_id;
	}

	public function getAttachmentId( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		$attachmentId = parent::getValue( $context, $contentPlaceholder );

		if ( filter_var( $attachmentId, FILTER_VALIDATE_URL ) ) {
			$attachmentId = $this->getAttachmentIdByUrl( $attachmentId );
		}

		if ( ! $attachmentId || ! wp_attachment_is_image( $attachmentId ) ) {
			return null;
		}

		return $attachmentId;
	}
}