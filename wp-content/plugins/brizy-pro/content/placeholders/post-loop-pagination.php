<?php

class BrizyPro_Content_Placeholders_PostLoopPagination extends Brizy_Content_Placeholders_Abstract {

	/**
	 * BrizyPro_Content_Placeholders_PostLoopPagination constructor.
	 * @throws Exception
	 */
	public function __construct() {
		$this->placeholder = 'brizy_dc_post_loop_pagination';
		$this->label       = 'Post loop pagination';
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

		global $wp_rewrite;
		$old_pagination_base         = $wp_rewrite->pagination_base;
		$wp_rewrite->pagination_base = BrizyPro_Content_Placeholders_PostLoop::getPaginationKey();

		// URL base depends on permalink settings.
		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );
		$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';
		$format       = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format       .= $wp_rewrite->using_permalinks() ? user_trailingslashit( BrizyPro_Content_Placeholders_PostLoop::getPaginationKey() . '/%#%', 'paged' ) : '?' . BrizyPro_Content_Placeholders_PostLoop::getPaginationKey() . '=%#%';

		$attributes                      = $contentPlaceholder->getAttributes();

		$queryVars = wp_parse_args( isset($attributes['query'])?$attributes['query']:'' );
		$paginationContext               = array();

		$paginationContext['totalCount'] = $this->getPostCount( $attributes );
		$attributes['count']             = isset( $attributes['count'] ) ? (int) $attributes['count'] : ( isset( $queryVars['posts_per_page'] ) ? $queryVars['posts_per_page'] : '3' );
		$paginationContext['pages']      = ceil( $paginationContext['totalCount'] / $attributes['count'] );
		$paginationContext['page']       = $this->getPageVar();
		$paginationContext['pagination'] = paginate_links( array(
			'prev_next' => false,
			'type'      => 'list',
			'format'    => $format,
			'current'   => $this->getPageVar(),
			'total'     => $paginationContext['pages']
		) );
		$wp_rewrite->pagination_base     = $old_pagination_base;

		return Brizy_TwigEngine::instance( BRIZY_PRO_PLUGIN_PATH . "/content/views/" )->render( 'pagination.html.twig', $paginationContext );
	}

	/**
	 * @return mixed|string
	 */
	protected function getOptionValue() {
		return null;
	}

	/**
	 * @param $attributes
	 *
	 * @return int
	 */
	private function getPostCount( $attributes ) {

		$query = null;

		if ( isset( $attributes['query'] ) && !empty($attributes['query'] )) {
			$params = array_merge( array(
				'posts_per_page' => isset( $attributes['count'] ) ? $attributes['count'] : 3,
				'orderby'        => isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'none',
				'order'          => isset( $attributes['order'] ) ? $attributes['order'] : 'ASC',
				'post_type'      => isset( $attributes['post_type'] ) ? $attributes['post_type'] : null,
			), wp_parse_args( $attributes['query'] ) );
			$query  = new WP_Query( $params );
		} else {
			global $wp_query;
			$queryVars                   = $wp_query->query_vars;
			$queryVars['orderby']        = isset( $attributes['orderby'] ) ? $attributes['orderby'] : ( isset( $queryVars['orderby'] ) ? $queryVars['orderby'] : null );
			$queryVars['order']          = isset( $attributes['order'] ) ? $attributes['order'] : ( isset( $queryVars['order'] ) ? $queryVars['order'] : null );
			$queryVars['posts_per_page'] = isset( $attributes['count'] ) ? (int) $attributes['count'] : ( isset( $queryVars['posts_per_page'] ) ? $queryVars['posts_per_page'] : null );
			$queryVars['post_type']      = isset( $attributes['post_type'] ) ? $attributes['post_type'] : ( isset( $queryVars['post_type'] ) ? $queryVars['post_type'] : null );
			$query                       = new WP_Query( $queryVars );
		}

		$count = $query->found_posts;
		wp_reset_postdata();

		return $count;
	}

	/**
	 * @return int|mixed
	 */
	private function getPageVar() {
		if ( $paged = get_query_var( BrizyPro_Content_Placeholders_PostLoop::getPaginationKey() ) ) {
			return (int) $paged;
		}

		return 1;
	}


}
