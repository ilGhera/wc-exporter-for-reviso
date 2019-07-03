<?php
/**
 * Gestisce gli ordini
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrOrders {

	public function __construct() {

		add_action( 'woocommerce_new_order', array( $this, 'new_order' ) );

	}


	public function new_order( $order_id ) {

		$order = new WC_Order( $order_id );

		error_log( 'Order: ' . print_r( $order, true ) );

	} 

}
new wcefrOrders;
