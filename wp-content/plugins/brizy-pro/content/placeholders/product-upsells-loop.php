<?php

class BrizyPro_Content_Placeholders_ProductUpsellsLoop extends BrizyPro_Content_Placeholders_PostLoop {

	/**
	 * @param $atts
	 *
	 * @return array
	 */
	protected function getPosts( $atts ) {

		global $product;

		if ( ! $product ) {
			return [];
		}

		$limit = empty( $atts['count'] ) ? '-1' : $atts['count'];
		$order = 'desc';

		$orderby = apply_filters( 'woocommerce_upsells_orderby', 'rand' );
		$order   = apply_filters( 'woocommerce_upsells_order', $order );
		$limit   = apply_filters( 'woocommerce_upsells_total', $limit );

		// Get visible upsells then sort them at random, then limit result set.
		$upsells = wc_products_array_orderby( array_filter( array_map( 'wc_get_product', $product->get_upsell_ids() ), 'wc_products_array_filter_visible' ), $orderby, $order );
		$upsells = $limit > 0 ? array_slice( $upsells, 0, $limit ) : $upsells;

		if ( ! $upsells ) {
			return [];
		}

		return array_map( function( $product ) {
			/* @var $product WC_Product */
			return $product->get_id();
		}, $upsells );
	}
}

