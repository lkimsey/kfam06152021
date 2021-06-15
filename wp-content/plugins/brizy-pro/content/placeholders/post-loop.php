<?php

class BrizyPro_Content_Placeholders_PostLoop extends Brizy_Content_Placeholders_Abstract {

	/**
	 * BrizyPro_Content_Placeholders_PostLoop constructor.
	 *
	 * @param string $label
	 * @param string $placeholder
	 *
	 * @throws Exception
	 */
	public function __construct( $label, $placeholder ) {
		$this->setLabel( $label );
		$this->setPlaceholder( $placeholder );
		$this->setDisplay( self::DISPLAY_BLOCK );
	}

	/**
	 * @param Brizy_Content_ContentPlaceholder $contentPlaceholder
	 * @param Brizy_Content_Context $context
	 *
	 * @return false|mixed|string
	 */
	public function getValue( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

		$attributes    = $contentPlaceholder->getAttributes();
		$posts         = $this->getPosts( $attributes );
		$globalProduct = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
		$content       = '';

		$placeholderProvider = new Brizy_Content_PlaceholderProvider();
		$extractor           = new Brizy_Content_PlaceholderExtractor( $placeholderProvider );

		list( $placeholders, $newContent ) = $extractor->extract( $contentPlaceholder->getContent() );
		$replacer = new Brizy_Content_PlaceholderReplacer($context, $placeholderProvider );


		foreach ( (array) $posts as $postId ) {

			// this method will initialize the WP_Post instance avoiding the adding it to cache
			// this way we avoid huge memory usage..
			$post = $this->getWpPostInstance($postId);

			if(!$post) continue;

			if ( 'product' === $post->post_type ) {
				$GLOBALS['product'] = wc_get_product( $post );
			}

			$newContext = Brizy_Content_ContextFactory::createContext( $context->getProject(), null, $post, null, true );
			$newContext->setProvider($context->getProvider());
			Brizy_Content_ContextFactory::makeContextGlobal( $newContext );

			$content .= $replacer->getContent( $placeholders, $newContent, $newContext );

			Brizy_Content_ContextFactory::clearGlobalContext();
		}

		if ( isset( $GLOBALS['product'] ) ) {

			unset( $GLOBALS['product'] );

			if ( $globalProduct ) {
				$GLOBALS['product'] = $globalProduct;
			}
		}

		$content = do_shortcode( $content );

		return $content;
	}

	protected function getWpPostInstance($id) {
		global $wpdb;
		$_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $id ) );

		if ( ! $_post ) {
			return false;
		}

		$_post = sanitize_post( $_post, 'raw' );

		return new WP_Post( $_post );
	}


	/**
	 * @return mixed|string
	 */
	protected function getOptionValue() {
		return $this->getReplacePlaceholder();
	}

	/**
	 * @param $attributes
	 *
	 * @return array
	 */
	protected function getPosts( $attributes ) {
		$paged = $this->getPageVar();
		// avoid flooding the cache

		$query = null;
		if ( isset( $attributes['query'] ) && ! empty( $attributes['query'] ) ) {
			$params = array_merge(
				array(
					'fields'         => 'ids',
					'posts_per_page' => isset( $attributes['count'] ) ? $attributes['count'] : 3,
					'orderby'        => isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'none',
					'order'          => isset( $attributes['order'] ) ? $attributes['order'] : 'ASC',
					'post_type'      => isset( $attributes['post_type'] ) ? $attributes['post_type'] : array_keys( get_post_types( [ 'public' => true ] ) ),
					'paged'          => $paged,
				),
				wp_parse_args( $attributes['query'] )
			);

			$query = new WP_Query( apply_filters( 'brizy_post_loop_args', $params ) );

		} else {
			global $wp_query;
			$params                   = $wp_query->query_vars;
			$params['fields']         = 'ids';
			$params['orderby']        = isset( $attributes['orderby'] ) ? $attributes['orderby'] : ( isset( $params['orderby'] ) ? $params['orderby'] : null );
			$params['order']          = isset( $attributes['order'] ) ? $attributes['order'] : ( isset( $params['order'] ) ? $params['order'] : null );
			$params['posts_per_page'] = isset( $attributes['count'] ) ? (int) $attributes['count'] : ( isset( $params['posts_per_page'] ) ? $params['posts_per_page'] : null );
			$params['post_type']      = isset( $attributes['post_type'] ) ? $attributes['post_type'] : ( isset( $params['post_type'] ) ? $params['post_type'] : null );
			$params['paged']          = (int) $paged;

			$query = new WP_Query( $params );
		}

		$posts = $query->posts;

		wp_reset_postdata();
		unset( $query );

		return $posts;
	}

	/**
	 * @return int|mixed
	 */
	private function getPageVar() {
		if ( $paged = get_query_var( self::getPaginationKey() ) ) {
			return (int) $paged;
		}

		return 1;
	}

	/**
	 * Return the pagination key. bpage is the default value.
	 *
	 * @return mixed|void
	 */
	public static function getPaginationKey() {
		return apply_filters( 'brizy_postloop_pagination_key', 'bpage' );
	}
}

