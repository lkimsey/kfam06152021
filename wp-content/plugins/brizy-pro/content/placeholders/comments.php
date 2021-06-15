<?php

class BrizyPro_Content_Placeholders_Comments extends Brizy_Content_Placeholders_Simple {

	private $atts = [];

	/**
	 * Brizy_Editor_Content_GenericPlaceHolder constructor.
	 *
	 * @param string $label
	 * @param string $placeholder
	 */
	public function __construct( $label, $placeholder ) {
		parent::__construct( $label, $placeholder, function ( Brizy_Content_Context $context, Brizy_Content_ContentPlaceholder $contentPlaceholder ) {
			return $this->comments_template( $contentPlaceholder->getAttributes() );
		} );
	}

	public function comments_template( $atts ) {

		$this->atts        = $atts;
		
		$this->atts['woo'] = is_singular( [ 'product' ] );

		add_action( 'wp_list_comments_args', [ $this, '_action_wp_list_comments_args' ] );
		add_action( 'comments_template', [ $this, '_action_comments_template' ] );
		add_action( 'comments_template_query_args', [ $this, '_action_comments_template_query_args' ] );
		add_action( 'comment_form_default_fields', [ $this, '_action_comment_form_default_fields' ] );
		add_action( 'comment_form_defaults', [ $this, '_action_comment_form_defaults' ] );

		ob_start(); ob_clean();

		comments_template();

		$ob_get_clean = ob_get_clean();

		return $ob_get_clean;
	}

	public function _action_wp_list_comments_args( $args ) {
		return array_merge( $args, $this->atts );
	}

	public function _action_comments_template() {
		return implode( DIRECTORY_SEPARATOR, [ BRIZY_PRO_PLUGIN_PATH, 'templates', 'comments.php' ] );
	}

	public function _action_comments_template_query_args( $comment_args ) {

		$comment_args['number'] = $this->atts['limit'];
		$cpage                  = get_query_var( 'cpage' );
		$get_query_var          = ( empty( $cpage ) ? 1 : $cpage ) - 1;
		$comment_args['offset'] = $comment_args['number'] * $get_query_var;

		return $comment_args;
	}

	public function _action_comment_form_default_fields( $fields ) {

		$commenter     = wp_get_current_commenter();
		$req           = get_option( 'require_name_email' );
		$aria_req      = ( $req ? " aria-required='true'" : '' );

		$fields['author'] =
			'<p class="brz-comment-form-author">
				<label for="author">' . __( 'Name', 'brizy' ) .
					( $req ? '<span class="required">*</span>' : '' ) .
				'</label>' .
				'<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' />
			</p>';

		$fields['email'] =
			'<p class="brz-comment-form-email">
				<label for="email">' . __( 'Email', 'brizy' ) .
				( $req ? '<span class="required">*</span>' : '' ) .
				'</label>' .
				'<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' />
			</p>';

		$fields['url'] =
			'<p class="brz-comment-form-url">
				<label for="url">' . __( 'Website', 'brizy' ) . '</label>' .
				'<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" />
			</p>';

		return $fields;
	}

	public function _action_comment_form_defaults( $args ) {

		$req           = get_option( 'require_name_email' );
		$user          = wp_get_current_user();
		$user_identity = $user->exists() ? $user->display_name : '';
		$required_text = sprintf( ' ' . __( 'Required fields are marked %s', 'brizy' ), '<span class="required">*</span>' );
		$form_title    = $this->atts['woo'] ? _x( 'Your review', 'noun', 'brizy' ) : _x( 'Comment', 'noun', 'brizy' );

		$args['id_form']            = 'brz-comment-form';
		$args['class_form']         = 'brz-form brz--comment__form-reply-body';
		$args['id_submit']          = 'brz-submit';
		$args['class_submit']       = 'brz-submit';
		$args['submit_field']       = '<p class="brz-form-submit">%1$s %2$s</p>';
		$args['title_reply_before'] = '<h3 id="reply-title" class="brz-comment-reply-title">';

		$args['comment_field'] =
			'<p class="brz-comment-form-comment">
				<label for="comment">' . $form_title . '</label>
				<textarea name="comment" cols="45" rows="8" aria-required="true"></textarea>
			</p>';

		$args['must_log_in'] =
			'<p class="brz-must-log-in">' .
				sprintf(
					__( 'You must be <a href="%s">logged in</a> to post a comment.', 'brizy' ),
					wp_login_url( apply_filters( 'the_permalink', get_permalink() ) )
				) .
			'</p>';

		$args['logged_in_as'] =
			'<p class="brz-logged-in-as">' .
				sprintf(
					__( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', 'brizy' ),
					admin_url( 'profile.php' ),
					$user_identity,
					wp_logout_url( apply_filters( 'the_permalink', get_permalink() ) )
				) .
			'</p>';

		$args['comment_notes_before'] =
			'<p class="comment-notes">' .
				__( 'Your email address will not be published.', 'brizy' ) . ( $req ? $required_text : '' ) .
			'</p>';

		if ( $this->atts['woo'] ) {

			$args['title_reply'] = have_comments() ? esc_html__( 'Add a review', 'woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title() );

			if ( wc_review_ratings_enabled() ) {
				$args['comment_field'] =
					'<div class="comment-form-rating">
						<label for="rating">' . esc_html__( 'Your rating', 'brizy' ) . '</label>
						<select name="rating" id="rating" required>
							<option value="">' . esc_html__( 'Rate&hellip;', 'brizy' ) . '</option>
							<option value="5">' . esc_html__( 'Perfect', 'brizy' ) . '</option>
							<option value="4">' . esc_html__( 'Good', 'brizy' ) . '</option>
							<option value="3">' . esc_html__( 'Average', 'brizy' ) . '</option>
							<option value="2">' . esc_html__( 'Not that bad', 'brizy' ) . '</option>
							<option value="1">' . esc_html__( 'Very poor', 'brizy' ) . '</option>
						</select>
					</div>' .
					$args['comment_field'];
			}
		}

		return $args;
	}

	public function _action_comments_per_page() {
		return $this->atts['limit'];
	}

	public function _action_thread_comments_depth() {
		return 5; //$this->atts['thread'];
	}
}