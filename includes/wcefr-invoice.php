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
	$invoice_number = $class->document_exists( $order_id, true );

	error_log( 'INVOICE: ' . $invoice_number );


	if ( $invoice_number ) {
	
		$file = $class->wcefrCall->call( 'get', '/v2/invoices/drafts/' . $invoice_number . '/pdf', null, false ); 

		error_log( 'PDF: ' . print_r( $file, true ) );

		$filename = 'Invoice-' . $invoice_number . '.pdf'; 
		  
		header('Content-type: application/pdf'); 
		header('Content-Disposition: inline; filename="' . $filename . '"'); 
		echo $file;		

	}

	exit;

}
