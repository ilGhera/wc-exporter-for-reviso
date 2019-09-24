<?php
/**
 * Reviso Invoice preview
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

if ( isset( $_GET['preview'] ) ) {

	$order_id = isset( $_GET['order-id'] ) ? $_GET['order-id'] : '';

	$class = new wcefrOrders();
	$invoice = $class->document_exists( $order_id, true, true );

	if ( $invoice['id'] && $invoice['status'] ) {
	
		$file = $class->wcefrCall->call( 'get', '/v2/invoices/' . $invoice['status'] . '/' . $invoice['id'] . '/pdf', null, false ); 

		$filename = 'Invoice-' . $invoice['id'] . '.pdf'; 
		  
		header('Content-type: application/pdf'); 
		header('Content-Disposition: inline; filename="' . $filename . '"'); 
		
		echo $file;		

	}

	exit;

}
