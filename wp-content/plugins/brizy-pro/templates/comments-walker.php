<?php
/**
 * This class outputs custom comment walker for HTML5 friendly WordPress comment and threaded replies.
 */
class BrizyPro_Templates_CommentsWalker extends Walker_Comment {

	/**
	 * Starts the list before the elements are added.
	 *
	 * @since 2.7.0
	 *
	 * @see Walker::start_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Uses 'style' argument for type of HTML list. Default empty array.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				$output .= '<ol class="brz-comments-children">' . "\n";
				break;
			case 'ul':
			default:
				$output .= '<ul class="brz-comments-children">' . "\n";
				break;
		}
	}

	/**
	 * Ends the list of items after the elements are added.
	 *
	 * @since 2.7.0
	 *
	 * @see Walker::end_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Will only append content if style argument value is 'ol' or 'ul'.
	 *                       Default empty array.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				$output .= "</ol><!-- .children -->\n";
				break;
			case 'ul':
			default:
				$output .= "</ul><!-- .children -->\n";
				break;
		}
	}

	/**
	 * Outputs a comment in the HTML5 format.
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {

		$skin           = $args['skin'];
		$tag            = ( 'div' === $args['style'] ) ? 'div' : 'li';
		$parent_classes = 'brz-comments brz-comments__skin-' . $skin . ( $this->has_children ? ' brz-parent' : '' );
		?>
        <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $parent_classes, $comment ); ?>>
		<?php

		call_user_func( [ $this, $skin ], $comment, $depth, $args );

	}

	protected function skin1( $comment, $depth, $args ) {

		$this->get_avatar( $comment, $args );
        ?>

        <div class="brz-ul brz-comments__right-date">
            <div class="brz-comments__name">
                <?php comment_author_link( $comment ) ?>
			</div>
			<div class="brz-comments__right-side">
				<?php $this->woo_stars( $comment, $args ); ?>
				<div class="brz-comments__date">
					<?php $this->comment_date( $comment, $args ); ?>
				</div>
			</div>
			<?php $this->replay_link( $args, $depth ); ?>
			
	        <?php $this->comment_text( $comment, $depth, $args ); ?>
        </div>
        <?php
	}

	protected function skin2( $comment, $depth, $args ) {

        $this->get_avatar( $comment, $args );
        ?>

        <div class="brz-ul brz-comments__right-date">
            <div class="brz-comments__name-date">
                <span class="brz-span brz-comments__name">
                    <?php comment_author_link( $comment ) ?>
                </span>
                <span class="brz-span brz-comments__date">
                   <?php $this->comment_date( $comment, $args ); ?>
                </span>
	            <?php $this->woo_stars( $comment, $args ); ?>
            </div>
	        <?php
                $this->comment_text( $comment, $depth, $args );
                $this->replay_link( $args, $depth );
	        ?>
        </div>
        <?php
	}

	protected function skin3( $comment, $depth, $args ) {

		$this->get_avatar( $comment, $args );
		?>

        <div class="brz-ul brz-comments__right-date">
            <div class="brz-comments__name">
                <?php comment_author_link( $comment ); ?>
            </div>
            <div class="brz-comments__date">
				<?php $this->comment_date( $comment, $args ); ?>
            </div>
			<?php $this->woo_stars( $comment, $args ); ?>
			<?php $this->comment_text( $comment, $depth, $args, true ); ?>
        </div>
		<?php
	}

	protected function skin4( $comment, $depth, $args ) { ?>

        <div class="brz-ul brz-comments__right-date">
            <div class="brz-comments__name-date">
                <span class="brz-span brz-comments__name">
                    <?php comment_author_link( $comment ); ?>
                </span>
				<div class="brz-comments__left-side">
					<span class="brz-span brz-comments__date">
						<?php $this->comment_date( $comment, $args ); ?>
					</span>
					<?php $this->woo_stars( $comment, $args ); ?>
				</div>
            </div>

			<?php
                $this->get_avatar( $comment, $args );
                $this->comment_text( $comment, $depth, $args, true );
			?>
        </div>

		<?php
	}

	protected function comment_date( $comment, $args ) {
	    ?>
        <a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
            <span class="brz-span">
                <?php echo get_comment_date( '', $comment ); ?>,&nbsp;
            </span>
	        <?php echo get_comment_time(); ?>
        </a>
        <?php
    }

    protected function comment_text( $comment, $depth, $args, $replay = false ) {
	    if ( '0' == $comment->comment_approved ) : ?>
            <div class="brz-comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'brizy' ); ?></div>
        <?php else: ?>
            <div class="brz-comments__text">
                <?php comment_text(); ?>
                <?php if ( $replay ) : ?>
                    <?php $this->replay_link( $args, $depth, '', '' ) ?>
                <?php endif; ?>
            </div>
        <?php endif;
    }

	private function get_avatar( $comment, $args ) {

		$comment_author_url  = get_comment_author_url( $comment );
		$avatar              = get_avatar( $comment, $args['avatar_size'], '', '', array( 'class' => 'brz-img brz-comments__logo-img' ) );

		if ( ! empty( $comment_author_url ) ) {
			$avatar = '<a href="' . $comment_author_url . '" rel="external nofollow">' . $avatar . '</a>';
		}

		echo '<div class="brz-comments__logo">' . $avatar . '</div>';
    }

    private function woo_stars( $comment, $args ) {

	    if ( ! $args['woo'] ) {
	        return;
		}
		
		echo '<div class="brz-comments__rating">';
			do_action( 'woocommerce_review_before_comment_meta', $comment );
		echo '</div>';
	    
    }

    private function replay_link( $args, $depth, $before = '<div class="brz-comments__reply">', $after = '</div>' ) {

	    if ( $args['woo'] ) {
		    return;
	    }

	    $link = get_comment_reply_link(
		    array_merge(
			    $args,
			    array(
				    'depth'     => $depth,
				    'max_depth' => $args['max_depth'],
				    'before'    => $before,
				    'after'     => $after
			    )
		    ),
            null,
            null
	    );

	    echo str_replace( 'comment-reply-link', 'comment-reply-link brz-a', $link );
    }
}
