<?php

class BrizyPro_Content_Placeholders_Logo extends Brizy_Content_Placeholders_Simple {


	/**
	 * Brizy_Editor_Content_GenericPlaceHolder constructor.
	 *
	 * @param string $label
	 * @param string $placeholder
	 * @param string|array $value
	 */
	public function __construct( $label, $placeholder ) {
		parent::__construct( $label, $placeholder, function () {
			return get_theme_mod( 'custom_logo' );
		} );
	}


	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return false|mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		$att_id = parent::getValue( $context, $contentPlaceholder );

		if ( filter_var( $att_id, FILTER_VALIDATE_URL ) ) {
			$att_id = $this->get_attachment_id( $att_id );
		}

		if ( ! $att_id || ! wp_attachment_is_image( $att_id ) ) {
			return BRIZY_PRO_PLUGIN_URL."/public/images/no-image.png";
		}

		$img_data = wp_get_attachment_image_src( $att_id, 'full' );

		return $img_data[0];
	}

	/**
	 * Get an attachment ID given a URL.
	 *
	 * @param string $url
	 *
	 * @return int Attachment ID on success, 0 on failure
	 */
	function get_attachment_id( $url ) {

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
}