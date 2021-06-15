<?php

class BrizyPro_Content_Placeholders_Breadcrumbs extends Brizy_Content_Placeholders_Simple {

	private $position = 0;

	/**
	 * Brizy_Editor_Content_GenericPlaceHolder constructor.
	 *
	 * @param string $label
	 * @param string $placeholder
	 */
	public function __construct( $label, $placeholder ) {
		parent::__construct( $label, $placeholder, function () {
			return $this->get_breadcrumbs();
		} );
	}

	public function get_breadcrumbs() {
		if ( is_home() || is_front_page() ) {
			return '';
		}

		$set = array(
			'home'     => esc_html__( 'Home', 'brizy-pro' ), // text for the 'Home' link
			'category' => esc_html__( 'Archive by Category "%s"', 'brizy-pro' ), // text for a category page
			'search'   => esc_html__( 'Search Results for "%s" Query', 'brizy-pro' ), // text for a search results page
			'tag'      => esc_html__( 'Posts Tagged "%s"', 'brizy-pro' ), // text for a tag page
			'author'   => esc_html__( 'Articles Posted by %s', 'brizy-pro' ), // text for an author page
			'404'      => esc_html__( 'Error 404', 'brizy-pro' ), // text for the 404 page
			'page'     => esc_html__( 'Page %s', 'brizy-pro' ), // text 'Page N'
			'cpage'    => esc_html__( 'Comment Page %s', 'brizy-pro' ) // text 'Comment Page N'
		);

		global $post;

		$parent_id = ( $post ) ? $post->post_parent : '';

		$out = $this->get_item( $set['home'], home_url( '/' ) );

		if ( is_search() ) {

			$out .= $this->get_item( sprintf( $set['search'], get_search_query() ), '', '' );

		} elseif ( is_year() ) {

			$out .= $this->get_item( get_the_time( 'Y' ), '', '' );

		} elseif ( is_month() ) {

			$out .= $this->get_item( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ) );
			$out .= $this->get_item( get_the_time( 'F' ), '', '' );

		} elseif ( is_day() ) {

			$out .= $this->get_item( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ) );
			$out .= $this->get_item( get_the_time( 'F' ), get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) );
			$out .= $this->get_item( get_the_time( 'd' ), get_day_link( get_the_time( 'Y' ), get_the_time( 'm' ), get_the_time( 'd' ) ), '' );

		} elseif ( is_single() && ! is_attachment() ) {

			if ( get_post_type() == 'product' && class_exists( 'WooCommerce' ) ) {

				$terms = wc_get_product_terms(
					get_the_ID(), 'product_cat', apply_filters(
						'woocommerce_breadcrumb_product_terms_args', array(
							'orderby' => 'parent',
							'order'   => 'DESC',
						)
					)
				);

				if ( $terms ) {

					$main_term = $terms[0];
					$ancestors = get_ancestors( $main_term->term_id, 'product_cat' );
					$ancestors = array_reverse( $ancestors );

					foreach ( $ancestors as $ancestor ) {
						$ancestor = get_term( $ancestor, 'product_cat' );

						if ( ! is_wp_error( $ancestor ) && $ancestor ) {
							$out .= $this->get_item( $ancestor->name, get_term_link( $ancestor ) );
						}
					}

					$out .= $this->get_item( $main_term->name, get_term_link( $main_term ) );
				}

				if ( get_query_var( 'cpage' ) ) {
					$out .= $this->get_item( get_the_title(), get_permalink() );
					$out .= $this->get_item( sprintf( $set['cpage'], get_query_var( 'cpage' ) ), '', '' );
				} else {
					$out .= $this->get_item( get_the_title(), '', '' );
				}

			} elseif ( get_post_type() != 'post' ) {

				$post_type = get_post_type_object( get_post_type() );

				$out .= $this->get_item( $post_type->labels->name, get_post_type_archive_link( $post_type->name ) );
				$out .= $this->get_item( get_the_title(), '', '' );

			} else {

				$cat       = get_the_category();
				$catID     = $cat[0]->cat_ID;
				$parents   = get_ancestors( $catID, 'category' );
				$parents   = array_reverse( $parents );
				$parents[] = $catID;

				foreach ( $parents as $cat ) {
					$out .= $this->get_item( get_cat_name( $cat ), get_category_link( $cat ) );
				}

				if ( get_query_var( 'cpage' ) ) {
					$out .= $this->get_item( get_the_title(), get_permalink() );
					$out .= $this->get_item( sprintf( $set['cpage'], get_query_var( 'cpage' ) ), '', '' );
				} else {
					$out .= $this->get_item( get_the_title(), '', '' );
				}
			}

		} elseif ( is_category() || is_tag() || is_tax() ) {

			$wp_the_query   = $GLOBALS['wp_the_query'];
			$queried_object = $wp_the_query->get_queried_object();
			$term_object    = get_term( $queried_object );
			$taxonomy       = $term_object->taxonomy;
			$term_parent    = $term_object->parent;

			if ( 0 !== $term_parent ) {

				// Get all the current term ancestors
				$parent_term_links = [];

				while ( $term_parent ) {
					$term                = get_term( $term_parent, $taxonomy );
					$parent_term_links[] = $this->get_item( $term->name, get_term_link( $term ) );
					$term_parent         = $term->parent;
				}

				$out .= implode( '', array_reverse( $parent_term_links ) );
			}

			$out .= $this->get_item( $term_object->name, get_term_link( $term_object ), '' );

		} elseif ( is_post_type_archive() ) {

			$post_type = get_post_type_object( get_post_type() );

			if ( get_query_var( 'paged' ) ) {

				$out .= $this->get_item( $post_type->label, get_post_type_archive_link( $post_type->name ) );
				$out .= $this->get_item( sprintf( $set['page'], get_query_var( 'paged' ) ), '', '' );

			} else {
				$out .= $this->get_item( $post_type->label, '', '' );
			}

		} elseif ( is_attachment() ) {

			$parent    = get_post( $parent_id );
			$cat       = get_the_category( $parent->ID );
			$catID     = $cat[0]->cat_ID;
			$parents   = get_ancestors( $catID, 'category' );
			$parents   = array_reverse( $parents );
			$parents[] = $catID;

			foreach ( $parents as $cat ) {
				$out .= $this->get_item( get_cat_name( $cat ), get_category_link( $cat ) );
			}

			$out .= $this->get_item( $parent->post_title, get_permalink( $parent ) );
			$out .= $this->get_item( get_the_title(), '', '' );

		} elseif ( is_page() && ! $parent_id ) {

			$out .= $this->get_item( get_the_title(), get_permalink(), '' );

		} elseif ( is_page() && $parent_id ) {

			$parents = get_post_ancestors( get_the_ID() );

			foreach ( array_reverse( $parents ) as $pageID ) {
				$out .= $this->get_item( get_the_title( $pageID ), get_page_link( $pageID ) );
			}

			$out .= $this->get_item( get_the_title(), get_permalink(), '' );

		} elseif ( is_author() ) {

			$author = get_userdata( get_query_var( 'author' ) );

			if ( get_query_var( 'paged' ) ) {

				$out .= $this->get_item( sprintf( $set['author'], $author->display_name ), get_author_posts_url( $author->ID ) );
				$out .= $this->get_item( sprintf( $set['page'], get_query_var( 'paged' ) ), '', '' );

			} else {
				$out .= $this->get_item( sprintf( $set['author'], $author->display_name ), '', '' );
			}

		} elseif ( is_404() ) {

			$out .= $this->get_item( $set['404'], '', '' );

		} elseif ( has_post_format() && ! is_singular() ) {
			$out .= $this->get_item( get_post_format_string( get_post_format() ), '', '' );
		}

		return '<ul class="brz-breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">' . $out . '</ul>';
	}

	private function get_item( $title, $url = '', $show_separator = true ) {

		$separator = '';
		$url       = $url ? $url : $this->get_current_url();

		if ( $show_separator ) {
			$separator = '<svg id="nc-right-arrow-heavy" class="brz-icon-svg" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"><g class="nc-icon-wrapper" fill="currentColor"><path d="M5.204 16L3 13.91 9.236 8 3 2.09 5.204 0l7.339 6.955c.61.578.61 1.512 0 2.09L5.204 16z" fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" class="nc-icon-wrapper"/></g></svg>';
		}

		$this->position += 1;

		$li =
			'<li class="brz-li" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
			    <a class="brz-a" itemprop="item" href="' . esc_url( $url ) . '">
			        <span itemprop="name">' . $title . '</span>
			    </a>
			    <meta itemprop="position" content="' . $this->position . '" />' .
				$separator .
			'</li>';

		return $li;
	}

	private function get_current_url() {

		global $wp;

		return rtrim( home_url( add_query_arg( [ $_GET ], $wp->request ) ), '/' ) . '/';
	}
}