<?php

class BrizyPro_Content_Providers_Woocommerce extends Brizy_Content_Providers_AbstractProvider {

	const PROVIDER_CONFIG_NAME = 'woocommerce';

	public function getGroupedPlaceholders() {
		$placeholders = array(
			self::CONFIG_KEY_TEXT  => $this->getTextPlaceholders(),
			self::CONFIG_KEY_IMAGE => $this->getMediaPlaceholders(),
			self::CONFIG_KEY_LINK  => $this->getLinkPlaceholders()
		);

		return array( self::PROVIDER_CONFIG_NAME => array_map( 'array_filter', $placeholders ) );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAllPlaceholders() {
		$placeholders = parent::getAllPlaceholders();

		array_unshift( $placeholders,
			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_stock', function () {
				global $product;
				echo wc_get_stock_html( $product );
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_add_to_cart_btn', function () {
				woocommerce_template_single_add_to_cart();
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_additional_info', function () {
				wc_get_template( 'single-product/tabs/additional-information.php' );
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_price', function ($post) {
				wc_get_template( '/single-product/price.php' );
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_sku', function () {
				global $product;

				$sku = wc_product_sku_enabled() && ( $sku = $product->get_sku() ) ? $sku : '';

				//echo( wc_product_sku_enabled() && ( $sku || $product->is_type( 'variable' ) ) ? $sku : '' );
				echo $sku;
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_rating', function () {

				if ( ! post_type_supports( 'product', 'comments' ) ) {
					return;
				}

				wc_get_template( 'single-product/rating.php' );
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_metas', function () {
				$this->get_metas();
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_gallery', function () {
				$this->get_gallery();
			} ),
			/*
			 * Do not use BrizyPro_Content_Placeholders_SimpleProductAware because it depends on global $product and if the global doesn't exist the cart won't be rendered.
			 */
			new Brizy_Content_Placeholders_Simple( '', 'editor_product_cart', function () {
				ob_start(); ob_clean();
				$this->get_cart();
				return ob_get_clean();
			} ),

			new BrizyPro_Content_Placeholders_SimpleProductAware( '', 'editor_product_short_description', function () {
				wc_get_template( 'single-product/short-description.php' );
			} ),

			new BrizyPro_Content_Placeholders_ProductUpsellsLoop( 'Upsells', 'editor_product_upsells' )
		);

		return $placeholders;
	}

	/**
	 * @return array
	 */
	public function getTextPlaceholders() {
		return [];
	}

	/**
	 * @return array
	 */
	public function getMediaPlaceholders() {
		return [];
	}

	/**
	 * @return array
	 */
	public function getLinkPlaceholders() {
		$holders = array(

			new BrizyPro_Content_Placeholders_Link( 'Review link', 'editor_product_review_url', function ( $context ) {
				$link = '#reviews';

				if ( ! $context->getProduct() ) {
					return;
				}

				if ( 'no' === get_option( 'woocommerce_enable_review_rating' ) || ! comments_open( $context->getProduct() ) ) {
					$link = '';
				}

				return $link;
			} )
		);

		return $holders;
	}

	private function get_reviews_title_text() {
		// sale-flash.php

		return new BrizyPro_Content_Placeholders_SimplePostAware( 'Reviews Title', 'editor_product_reviews_title', function ( $context ) {
			if ( ! comments_open( $context->getProduct() ) ) {
				return '';
			}

			if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' && ( $count = $context->getProduct()->get_review_count() ) ) {
				/* translators: 1: reviews count 2: product name */
				return sprintf(
					esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'brizy-pro' ) ),
					esc_html( $count ),
					'<span>' . get_the_title( $context->getWpPost() ) . '</span>'
				);
			}

			return __( 'Reviews', 'brizy-pro' );
		} );
	}

	private function get_upsells_title_text() {
		// up-sells.php
		return new BrizyPro_Content_Placeholders_SimplePostAware( 'Upsells Title', 'editor_product_upsells_title', function ( $context ) {
			$limit   = '-1';
			$columns = 4;
			$orderby = 'rand';
			$order   = 'desc';

			// Handle the legacy filter which controlled posts per page etc.
			$args = apply_filters( 'woocommerce_upsell_display_args', array(
				'posts_per_page' => $limit,
				'orderby'        => $orderby,
				'columns'        => $columns,
			) );

			$orderby = apply_filters( 'woocommerce_upsells_orderby', isset( $args['orderby'] ) ? $args['orderby'] : $orderby );
			$limit   = apply_filters( 'woocommerce_upsells_total', isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : $limit );

			// Get visible upsells then sort them at random, then limit result set.
			$upsells = wc_products_array_orderby( array_filter( array_map( 'wc_get_product', $context->getProduct()->get_upsell_ids() ), 'wc_products_array_filter_visible' ), $orderby, $order );
			$upsells = $limit > 0 ? array_slice( $upsells, 0, $limit ) : $upsells;

			$title = '';

			if ( $upsells ) {
				$title = esc_html__( 'You may also like&hellip;', 'brizy-pro' );
			}

			return $title;
		} );
	}

	private function get_related_title_text() {
		// single-product/related.php
		return new BrizyPro_Content_Placeholders_SimplePostAware( 'Related Title', 'editor_product_related_title', function ( $context ) {
			$posts_per_page = 2;
			// Get visible related products then sort them at random.
			$related_products = array_filter( array_map( 'wc_get_product', wc_get_related_products( $context->getProduct()->get_id(), $posts_per_page, $context->getProduct()->get_upsell_ids() ) ), 'wc_products_array_filter_visible' );
			$related_products = wc_products_array_orderby( $related_products, 'rand', 'desc' );

			$title = '';

			if ( $related_products ) {
				$title = esc_html__( 'Related products', 'brizy-pro' );
			}

			return $title;
		} );
	}

	private function get_metas() {
        global $product;

        echo '<div class="brz-metas">';

        do_action( 'woocommerce_product_meta_start' );

        $items = [];
        $sku   = $product->get_sku();

        if ( wc_product_sku_enabled() && ( $sku || $product->is_type( 'variable' ) ) ) {
            $items[] = [
                'title' => __( 'SKU', 'brizy-pro' ),
                'value' => $sku,
            ];
        }

        $items[] = [
            'title' => _nx( 'Category', 'Categories', count( $product->get_category_ids() ), 'Woocommerce Product Meta Category', 'brizy-pro' ),
            'value' => $this->get_the_term_list( 'product_cat' ),
        ];

        $items[] = [
            'title' => _nx( 'Tag', 'Tags', count( $product->get_tag_ids() ), 'Woocommerce Product Meta Tag', 'brizy-pro' ),
            'value' => $this->get_the_term_list( 'product_tag' ),
        ];

        foreach ( $items as $item ) {
            if ( empty( $item['value'] ) ) {
                continue;
            }

            echo '<span class="brz-wooproductmeta__container">' . $this->meta_title( $item['title'] ) . $this->meta_value( $item['value'] ) . '</span>';
        }

        do_action( 'woocommerce_product_meta_end' );

        echo '</div>';
	}

    private function get_the_term_list( $taxonomy, $sep = ', ' ) {

        global $product;

        $terms = get_the_terms( $product->get_id(), $taxonomy );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        $links = array();

        foreach ( $terms as $term ) {
            $link = get_term_link( $term, $taxonomy );
            if ( is_wp_error( $link ) ) {
                continue;
            }
            $links[] = '<a href="' . esc_url( $link ) . '" rel="tag" class="brz-a">' . $term->name . '</a>';
        }

        return join( $sep, $links );
    }

    private function meta_title( $title ) {
        return '<span class="brz-wooproductmeta__item brz-wooproductmeta__item-category">' . $title . '</span>';
    }

    private function meta_value( $content ) {
        return '<span class="brz-wooproductmeta__item brz-wooproductmeta__item-value">' . $content . '</span>';
    }

    private function get_gallery() {

	    $this->setScriptDependency( 'brizy-preview', [ 'zoom', 'photoswipe', 'flexslider', 'wc-single-product' ] );
	    
        wc_get_template( 'single-product/product-image.php' );
    }

    private function get_cart() {

	    if ( null === WC()->cart ) {
	        return;
        }

        if ( apply_filters( 'woocommerce_widget_cart_is_hidden', false ) ) {
            return;
        }

        ?>
        <div class="brz-cart">
            <a class="brz-a brz-woocart">
                <span class="brz-woocart__parent">
                    <span class="brz-woocart__price">
                        <span class="brz-woocart__price-currency"><?php echo get_woocommerce_currency_symbol(); ?></span><?php echo WC()->cart->get_cart_contents_total(); ?></span>
                </span>
                <span class="brz-woocart__icon" data-counter="<?php echo WC()->cart->get_cart_contents_count(); ?>">
                    <svg class="brz-icon-svg"><svg id="nc_icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve"><g class="nc-icon-wrapper" fill="currentColor"><rect y="15" fill="currentColor" width="24" height="2"></rect> <rect data-color="color-2" x="2" y="11" fill="currentColor" width="6" height="2"></rect> <path fill="currentColor" d="M4,4c0,0.552,0.448,1,1,1h14.719l-1.932,7.728l1.94,0.485l2.243-8.97c0.075-0.299,0.007-0.615-0.182-0.858 S21.308,3,21,3H6V1c0-0.552-0.448-1-1-1H0v2h4V4z"></path> <rect data-color="color-2" x="4" y="7" fill="currentColor" width="6" height="2"></rect> <circle data-color="color-2" fill="currentColor" cx="5.5" cy="21.5" r="2.5"></circle> <circle data-color="color-2" fill="currentColor" cx="20.5" cy="21.5" r="2.5"></circle></g></svg></svg>
                </span>
            </a>

            <form class="brz-woocart__sidebar" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
                <div class="brz-woocart__sidebar-close"></div>
                <?php $this->get_cart_content(); ?>
            </form>
        </div>
        <?php
    }

    private function get_cart_content() {

        $items = WC()->cart->get_cart();

        if ( empty( $items ) ) {
            $this->get_empty_cart();
            return;
        }

        ?>
        <div class="brz-woocart__sidebar-contents">
            <?php
            do_action( 'woocommerce_before_mini_cart_contents' );

            foreach ( $items as $i => $item ) {
                self::get_product( $i, $item );
            }

            do_action( 'woocommerce_mini_cart_contents' );
            ?>
        </div>
        <div class="brz-woocart__sidebar-subtotal">
            <strong class="brz-strong"><?php _e( 'Subtotal', 'brizy-pro' ); ?>:</strong>
            <span class="brz-woocart__sidebar-price">
                <span class="brz-woocart__sidebar-price-currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                <?php echo WC()->cart->get_cart_contents_total(); ?>
            </span>
        </div>
        <div class="brz-woocart__sidebar-buttons">
            <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="brz-woocart__sidebar-button">
                <span class="brz-woocart__sidebar-button-text">
                    <?php _e( 'View cart', 'brizy-pro' ); ?>
                </span>
            </a>
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="brz-woocart__sidebar-button">
                <span class="brz-woocart__sidebar-button-text"><?php _e( 'Checkout', 'brizy-pro' ); ?></span>
            </a>
        </div>

        <?php
    }

    private function get_product( $key, $item ) {

        $_product           = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
        $is_product_visible = ( $_product && $_product->exists() && $item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) );

        if ( ! $is_product_visible ) {
            return;
        }

        $product_id        = apply_filters( 'woocommerce_cart_item_product_id', $item['product_id'], $item, $key );
        //$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $item, $key );
        $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $item ) : '', $item, $key );
        ?>
        <div class="brz-woocart__sidebar-item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $item, $key ) ); ?>">

            <div class="brz-woocart__sidebar-image__block product-thumbnail">
                <?php
                $thumbnail = str_replace( 'class="', 'class="brz-woocart__sidebar-image ', apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $item, $key ) );

                if ( ! $product_permalink ) :
                    echo $thumbnail;
                else :
                    printf( '<a href="%s" class="brz-a">%s</a>', esc_url( $product_permalink ), $thumbnail );
                endif;
                ?>
            </div>
            <div class="brz-woocart__sidebar__product-info">
                <div class="brz-woocart__sidebar__product-name" data-title="<?php esc_attr_e( 'Product', 'brizy-pro' ); ?>">
                    <?php
                    if ( ! $product_permalink ) {
                        echo apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $item, $key );
                    } else {
                        echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a class="brz-a" href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $item, $key );
                    }

                    do_action( 'woocommerce_after_cart_item_name', $item, $key );

                    //echo wc_get_formatted_cart_item_data( $item );
                    ?>
                </div>
                <div class="brz-woocart__sidebar__product-price__container product-price" data-title="<?php esc_attr_e( 'Price', 'brizy-pro' ); ?>">
                    <?php
                    $price_html =
                        '<span class="brz-woocart__sidebar__product-price-parent quantity">' .
                        $item['quantity'] . ' x  
		                    <span class="brz-woocart__sidebar__product-price">
                                <span class="brz-woocart__sidebar__product-price__currency">' .
                        get_woocommerce_currency_symbol() .
                        '</span>' .
                        $this->get_price( $_product ) .
                        '</span>
                        </span>';
                    ?>
                    <?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', $price_html, $item, $key ); ?>
                </div>
            </div>

            <div class="brz-woocart__sidebar-remove product-remove">
                <?php
                echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                    '<a href="%s" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><svg class="brz-icon-svg"><svg id="nc_icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve"><g class="nc-icon-wrapper" fill="currentColor"><path data-color="color-2" fill="currentColor" d="M23,4h-6V1c0-0.552-0.447-1-1-1H8C7.447,0,7,0.448,7,1v3H1C0.447,4,0,4.448,0,5v2c0,0.552,0.447,1,1,1h22 c0.553,0,1-0.448,1-1V5C24,4.448,23.553,4,23,4z M9,2h6v2H9V2z"></path> <path fill="currentColor" d="M21,10H3v13c0,0.552,0.448,1,1,1h16c0.552,0,1-0.448,1-1V10z M9,20H7v-6h2V20z M13,20h-2v-6h2V20z M17,20 h-2v-6h2V20z"></path></g></svg></svg></a>',
                    esc_url( wc_get_cart_remove_url( $key ) ),
                    __( 'Remove this item', 'brizy-pro' ),
                    esc_attr( $product_id ),
                    esc_attr( $key ),
                    esc_attr( $_product->get_sku() )
                ), $key );
                ?>
            </div>
        </div>
        <?php
    }

    private function get_price( $product ) {
        return WC()->cart->display_prices_including_tax() ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product );
    }

    private function get_empty_cart() {
        ?>
        <div class="woocommerce-mini-cart__empty-message">
            <?php _e( 'No products in the cart.', 'brizy-pro' ); ?>
        </div>
        <?php
    }
}