<?php
/**
 * Reviso Invoice preview
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

if ( isset( $_GET['preview'] ) ) {

	$order_id = isset( $_GET['order-id'] ) ? $_GET['order-id'] : '';

	error_log( 'ORDER: ' . $order_id );

	$class = new wcefrOrders();
	$invoice = $class->document_exists( $order_id, true, true );

	error_log( 'INVOICE: ' . $invoice['id'] );


	if ( $invoice['id'] && $invoice['status'] ) {
	
		$file = $class->wcefrCall->call( 'get', '/v2/invoices/' . $invoice['status'] . '/' . $invoice['id'] . '/pdf', null, false ); 

		error_log( 'PDF: ' . print_r( $file, true ) );

		$filename = 'Invoice-' . $invoice['id'] . '.pdf'; 
		  
		header('Content-type: application/pdf'); 
		header('Content-Disposition: inline; filename="' . $filename . '"'); 
		echo $file;		

	}

	exit;

}
