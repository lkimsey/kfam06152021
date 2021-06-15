<?php

class BrizyPro_Content_Providers_Wp extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'wp';


	/**
	 * @return array|mixed
	 */
	public function getGroupedPlaceholders() {
		$placeholders = array(
			self::CONFIG_KEY_TEXT  => $this->getTextPlaceholders(),
			self::CONFIG_KEY_IMAGE => $this->getMediaPlaceholders(),
			self::CONFIG_KEY_LINK  => $this->getLinkPlaceholders()
		);

		//return array( self::PROVIDER_CONFIG_NAME => $placeholders );
		return array( self::PROVIDER_CONFIG_NAME => array_map( 'array_filter', $placeholders ) );
	}

	/**
	 * @return array|int
	 * @throws Exception
	 */
	public function getAllPlaceholders() {
		$placeholders = parent::getAllPlaceholders();

		array_unshift( $placeholders,
			new BrizyPro_Content_Placeholders_PostLoop( 'Post Loop', 'brizy_dc_post_loop' ),
			new BrizyPro_Content_Placeholders_PostLoopPagination(),
			new BrizyPro_Content_Placeholders_PostLoopTags(),
			new BrizyPro_Content_Placeholders_PostTags(),
			new BrizyPro_Content_Placeholders_Breadcrumbs( 'Breadcrumbs', 'editor_breadcrumbs' ),
			new BrizyPro_Content_Placeholders_Comments( 'Comments', 'editor_comments' ),
			$this->postNavigation(),
			new Brizy_Content_Placeholders_Simple( '', 'editor_is_user_logged_in', function () {
				return is_user_logged_in() ? 'true' : 'false';
			} ),
			new BrizyPro_Content_Placeholders_MenuItemActive( '', 'editor_menu_active_item' )
		);

		return $placeholders;
	}

	/**
	 *
	 * @return array
	 */
	private function getTextPlaceholders() {

		$holders = array_merge(
			array(
				new BrizyPro_Content_Placeholders_SimplePostAware( 'Post Title', 'brizy_dc_post_title', function ( $context ) {
					return apply_filters( 'the_title', $context->getWpPost()->post_title, $context->getWpPost()->ID );
				} ),

				new BrizyPro_Content_Placeholders_PostContent( 'Post Content', 'brizy_dc_post_content', Brizy_Content_Placeholders_Abstract::DISPLAY_BLOCK ),

				new BrizyPro_Content_Placeholders_Excerpt( 'Post Excerpt', 'brizy_dc_post_excerpt' ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Post Date', 'brizy_dc_post_date', function ( $context ) {
					return get_the_date( '', $context->getWpPost() );
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Post Time', 'brizy_dc_post_time', function ( $context ) {
					return get_the_time( '', $context->getWpPost() );
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Post ID', 'brizy_dc_post_id', function ( $context ) {
					return $context->getWpPost()->ID;
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Post Comments Count', 'brizy_dc_comments_count', function ( $context ) {
					return get_comments_number( $context->getWpPost() );
				} ),
			),

			$this->getTextPlaceholderTerms(),

			array(
				new BrizyPro_Content_Placeholders_SimplePostAware( 'Author Name', 'brizy_dc_post_author_name', function ( $context ) {

					if ( get_the_author_meta( 'user_firstname', $context->getAuthor() ) || get_the_author_meta( 'user_lastname', $context->getAuthor() ) ) {
						return trim( get_the_author_meta( 'user_firstname', $context->getAuthor() ) . ' ' . get_the_author_meta( 'user_lastname', $context->getAuthor() ) );
					} else {
						return trim( get_the_author_meta( 'display_name', $context->getAuthor() ) );
					}
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Author Bio', 'brizy_dc_post_author_description', function ( $context ) {
					return get_the_author_meta( 'description', $context->getAuthor() );
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Author Email', 'brizy_dc_post_author_email', function ( $context ) {
					return get_the_author_meta( 'email', $context->getAuthor() );
				} ),

				new BrizyPro_Content_Placeholders_SimplePostAware( 'Author Website', 'brizy_dc_post_author_url', function ( $context ) {
					return get_the_author_meta( 'url', $context->getAuthor() );
				} ),

				new Brizy_Content_Placeholders_Simple( 'Site Title', 'brizy_dc_site_title', function () {
					return get_bloginfo();
				} ),

				new Brizy_Content_Placeholders_Simple( 'Site Tagline', 'brizy_dc_site_tagline', function () {
					return get_bloginfo( 'description' );
				} ),

				$this->getArchiveTitle(),

				new Brizy_Content_Placeholders_Simple( 'Archive Description', 'brizy_dc_archive_description', function () {
					return strip_tags( get_the_archive_description() );
				} )
			),
			[
				new Brizy_Content_Placeholders_Simple( 'Username', 'editor_user_username', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					$user = wp_get_current_user();

					return $user->data->user_login;
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Role', 'editor_user_role', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					$user  = wp_get_current_user();
					$roles = (array) $user->roles;

					return implode( ',', $roles );
				} ),

				new Brizy_Content_Placeholders_Simple( 'User First Name', 'editor_user_first_name', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					return get_the_author_meta( 'user_firstname', get_current_user_id() );
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Last Name', 'editor_user_last_name', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					return get_the_author_meta( 'user_lastname', get_current_user_id() );
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Nickname', 'editor_user_nickname', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					return get_the_author_meta( 'nickname', get_current_user_id() );
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Display Name', 'editor_user_display_name', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					$user = wp_get_current_user();

					return $user->data->display_name;
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Email', 'editor_user_email', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					$user = wp_get_current_user();

					return $user->data->user_email;
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Website', 'editor_user_website', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					$user = wp_get_current_user();

					return $user->data->user_url;
				} ),

				new Brizy_Content_Placeholders_Simple( 'User Description', 'editor_user_description', function () {

					if ( ! is_user_logged_in() ) {
						return '';
					}

					return get_the_author_meta( 'description', get_current_user_id() );
				} ),
			]
		);

		return $holders;
	}

	/**
	 * @return array
	 */
	private function getMediaPlaceholders() {
		return array(
			new BrizyPro_Content_Placeholders_FeaturedImg( 'Featured Image', 'brizy_dc_img_featured_image' ),
			new BrizyPro_Content_Placeholders_Logo( 'Site logo', 'brizy_dc_img_site_logo' ),
			new BrizyPro_Content_Placeholders_SimplePostAware( 'Author Profile Picture', 'brizy_dc_img_avatar_url', function ( $context ) {
				return esc_url( get_avatar_url( $context->getAuthor() ) );
			} ),
			new BrizyPro_Content_Placeholders_SimplePostAware( 'User Avatar', 'editor_user_avatar', function () {
				return esc_url( get_avatar_url( get_current_user_id() ) );
			} ),
		);
	}

	/**
	 * @return array
	 */
	private function getLinkPlaceholders() {
		$holders = array(
			new BrizyPro_Content_Placeholders_Link( 'Post URL', 'brizy_dc_url_post', function ( $context ) {
				if ( $context->getWpPost() ) {
					return get_permalink( $context->getWpPost() );
				}

				return '';
			} ),

			new BrizyPro_Content_Placeholders_Link( 'Author URL', 'brizy_dc_url_author', function ( $context ) {
				if ( $context->getWpPost() ) {
					return get_author_posts_url( $context->getAuthor() );
				}

				return '';
			} ),

			new BrizyPro_Content_Placeholders_Link( 'Comments URL', 'brizy_dc_url_comments', function ( $context ) {
				if ( $context->getWpPost() ) {
					return get_comments_link( $context->getWpPost()->ID );
				}

				return '';
			} ),

			new BrizyPro_Content_Placeholders_Link( 'Site URL', 'brizy_dc_url_site', function () {
				return esc_url( home_url( '/' ) );
			} ),

			new Brizy_Content_Placeholders_Simple( 'Login Url', 'editor_login_url', function () {
				return esc_url( site_url( 'wp-login.php', 'login_post' ) );
			} ),

			new Brizy_Content_Placeholders_Simple( 'Logout Url', 'editor_logout_url', function () {
				return wp_logout_url();
			} ),

			new Brizy_Content_Placeholders_Simple( 'Lost Password Url', 'editor_lostpassword_url', function () {
				return wp_lostpassword_url();
			} ),

			new BrizyPro_Content_Placeholders_Link( 'User Profile', 'editor_user_profile_url', function () {
				return get_author_posts_url( get_current_user_id() );
			} ),

			$this->getUrlArchive()
		);

		return $holders;
	}

	/**
	 * @return array
	 */
	private function getTextPlaceholderTerms() {

		$terms = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
		$out   = array();

		foreach ( $terms as $tax ) {

			$out[] = new BrizyPro_Content_Placeholders_SimplePostAware( $tax->label, "brizy_dc_post_tax_{$tax->name}", function ( $context ) use ( $tax ) {

				$terms = get_terms( array(
						'object_ids' => $context->getWpPost()->ID,
						'taxonomy'   => $tax->name
					)
				);

				if ( ! $terms || is_wp_error( $terms ) ) {
					return '';
				}

				$links = array();

				foreach ( $terms as $term ) {

					if ( ! ( $url = get_term_link( $term ) ) || is_wp_error( $url ) ) {
						continue;
					}

					$links[] = '<a href="' . esc_url( $url ) . '">' . $term->name . '</a>';
				}

				return implode( ', ', $links );
			} );
		}

		return $out;
	}

	/**
	 * @return BrizyPro_Content_Placeholders_Link
	 */
	private function getUrlArchive() {

		return new BrizyPro_Content_Placeholders_Link( 'Archive URL', 'brizy_dc_url_archive', function () {

			if ( is_category() || is_tag() || is_tax() ) {
				$url = get_term_link( get_queried_object() );
			} elseif ( is_author() ) {
				$url = get_author_posts_url( get_queried_object_id() );
			} elseif ( is_year() ) {
				$url = get_year_link( get_query_var( 'year' ) );
			} elseif ( is_month() ) {
				$url = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
			} elseif ( is_day() ) {
				$url = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
			} elseif ( is_post_type_archive() ) {
				$url = get_post_type_archive_link( get_post_type() );
			} else {
				$url =  get_post_type_archive_link( 'post' );
			}

			return $url;
		} );
	}

	/**
	 * @return Brizy_Content_Placeholders_Simple
	 */
	private function getArchiveTitle() {

		return new Brizy_Content_Placeholders_Simple( 'Archive Title', 'brizy_dc_archive_title', function () {

			if ( is_category() ) {
				$title = single_cat_title( '', false );
			} elseif ( is_tag() ) {
				$title = single_tag_title( '', false );
			} elseif ( is_author() ) {
				$title = get_the_author();
			} elseif ( is_year() ) {
				$title = get_the_date( _x( 'Y', 'yearly archives date format' ) );
			} elseif ( is_month() ) {
				$title = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
			} elseif ( is_day() ) {
				$title = get_the_date( _x( 'F j, Y', 'daily archives date format' ) );
			} elseif ( is_post_type_archive() ) {
				$title = post_type_archive_title( '', false );
			} elseif ( is_tax() ) {
				$tax   = get_taxonomy( get_queried_object()->taxonomy );
				$title = sprintf( '%1$s: %2$s' , $tax->labels->singular_name, single_term_title( '', false ) );
			} else {
				$title = ''; // __( 'Archives' )
			}

			return apply_filters( 'get_the_archive_title', $title );
		} );
	}

	/**
	 * @return Brizy_Content_Placeholders_Simple
	 */
	private function postNavigation() {
		return new Brizy_Content_Placeholders_Simple( 'Post Navigation', 'editor_post_navigation', function ( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {

			/*
			 * Use array_change_key_case to keep backward compatibility.
			 * When it was as wp shortcode the atts keys come here lowercase
			 */
			$atts = array_change_key_case( $contentPlaceholder->getAttributes(), CASE_LOWER );

			$post_type    = get_post_type( get_queried_object_id() );
			$post_types   = explode( ',', $atts['post_type'] );
			$in_same_term = in_array( $post_type, $post_types );
			$taxonomy     = empty( $atts["{$post_type}_taxonomy"] ) ? 'category' : $atts["{$post_type}_taxonomy"];
			$prev         = get_previous_post_link( '%link', '%title', $in_same_term, '', $taxonomy );
			$next         = get_next_post_link( '%link', '%title', $in_same_term, '', $taxonomy );

			if ( ! empty( $prev ) ) {
				$prev = str_replace( 'href="', 'class="brz-a brz-navigation__prev" href="', $prev );
			}

			if ( ! empty( $next ) ) {
				$next = str_replace( 'href="', 'class="brz-a brz-navigation__next" href="', $next );
			}

			if ( empty( $prev ) && empty( $next ) ) {
				return '';
			}

			$text_nav     = '';

			if ( $atts['showtitle'] == 'on' ) {
				$prevTitle = $prev ? '<span class="brz-span brz-navigation-title__prev">' . $atts['titleprevious'] . '</span>' : '';
				$nextTitle = $next ? '<span class="brz-span brz-navigation-title__next">' . $atts['titlenext'] . '</span>' : '';
				$text_nav  = '<div class="brz-navigation-title">' . $prevTitle . $nextTitle . '</div>';
			}

			return $text_nav . '<div class="brz-navigation">' . $prev . $next . '</div>';
		} );
	}
}
