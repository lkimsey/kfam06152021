<?php

if ( post_password_required() ) {
	return;
}

$is_woo = false;
if ( is_singular( [ 'product' ] ) && class_exists( 'WooCommerce' ) ) {
    global $product;
    $is_woo = true;
}

if ( ! comments_open() ) {
    return;
}

?>

<div id="comments" class="brz-comments-area">

	<?php

	if ( have_comments() ) :
		?>
		<h2 class="brz-comments-title">
			<?php

                if ( $is_woo ) {

	                $count = $product->get_review_count();
	                if ( $count && wc_review_ratings_enabled() ) {
		                /* translators: 1: reviews count 2: product name */
		                $reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'brizy-pro' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );
		                echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product ); // WPCS: XSS ok.
	                } else {
		                esc_html_e( 'Reviews', 'woocommerce' );
	                }

                } else {

	                $comments_number = get_comments_number();
	                if ( '1' === $comments_number ) {
		                /* translators: %s: post title */
		                printf( _x( 'One Reply to &ldquo;%s&rdquo;', 'comments title', 'brizy-pro' ), get_the_title() );
	                } else {
		                printf(
		                /* translators: 1: number of comments, 2: post title */
			                _nx(
				                '%1$s Reply to &ldquo;%2$s&rdquo;',
				                '%1$s Replies to &ldquo;%2$s&rdquo;',
				                $comments_number,
				                'comments title',
				                'brizy-pro'
			                ),
			                number_format_i18n( $comments_number ),
			                get_the_title()
		                );
	                }
                }
			?>
		</h2>

		<div class="brz-comments">
			<?php
				wp_list_comments([
					'walker'     => new BrizyPro_Templates_CommentsWalker(),
					'short_ping' => true,
					'style'      => 'div',
					'format'     => 'html5'
                ]);
			?>
		</div>

		<?php
			the_comments_pagination(
				array(
					'prev_text' => '<span class="brz-screen-reader-text">' . __( 'Previous', 'brizy-pro' ) . '</span>',
					'next_text' => '<span class="brz-screen-reader-text">' . __( 'Next', 'brizy-pro' ) . '</span>',
				)
			);
	else:
        if ( $is_woo ) {
	        echo '<p class="woocommerce-noreviews">' . __( 'There are no reviews yet.', 'brizy-pro' ) . '</p>';
        }
	endif; // Check for have_comments().


    if ( $is_woo && ! ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) ) :

	    echo '<p class="brz-no-comments">' . __( 'Only logged in customers who have purchased this product may leave a review.', 'brizy-pro' ) . '</p>';

    elseif ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :

		echo '<p class="brz-no-comments">' . __( 'Comments are closed.', 'brizy-pro' ) . '</p>';

	else :

		ob_start(); ob_clean();

		comment_form();

		$form = ob_get_clean();

		echo str_replace( 'class="comment-respond"', 'class="brz-comment-respond"', $form );

	endif;
	?>
</div>
