<?php
/**
 * Export orders to reviso
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrOrders {

	public function __construct() {

		$this->init();
		
		add_action( 'wp_ajax_export-orders', array( $this, 'export_orders' ) );
		add_action( 'wp_ajax_delete-remote-orders', array( $this, 'delete_remote_orders' ) );

		add_action( 'wcefr_export_single_order_event', array( $this, 'export_single_order' ), 10, 1 );
		add_action( 'wcefr_delete_remote_single_order_event', array( $this, 'delete_remote_single_order' ), 10, 2 );
		// add_action( 'admin_footer', array( $this, 'get_remote_invoices' ) );

		$this->wcefrCall = new wcefrCall();

	}


    /**
     * Check the administrator settings to automatically export orders to Reviso
     * @return void
     */
    public function init() {

    	/*Export orders automatically to Reviso*/
    	$export_orders = get_option( 'wcefr-export-orders' );

    	if ( $export_orders ) {
    		
			// add_action( 'woocommerce_new_order', array( $this, 'export_single_order' ) );
			add_action( 'woocommerce_thankyou', array( $this, 'export_single_order' ) );

    	}

    	/*Create invoices in Reviso with WC completed orders */
    	$create_invoices = get_option( 'wcefr-create-invoices' );

    	if ( $create_invoices ) {
	
			add_action( 'woocommerce_order_status_completed', array( $this, 'create_single_invoice' ) );
    	
    	}
    }

	
	/**
	 * Get all the orders from Reviso
	 * @return array
	 */
	public function get_remote_orders() {

		$output = $this->wcefrCall->call( 'get', 'orders?pagesize=10000'  );

		return $output;

	}


	/**
	 * Check if a wc order is already on Reviso
	 * @param  int  $order_id the wc order id
	 * @param  bool $invoice  search in invoices instead of orders
	 * @return bool
	 */
	public function document_exists( $order_id, $invoice = false )  {

		$output = null;
		$endpoint = $invoice ? '/v2/invoices/drafts' : 'orders';

		$response = $this->wcefrCall->call( 'get', $endpoint . '?filter=notes.text1$eq:WC-Order-' . $order_id );

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {
			
			$output = $response->collection[0]->id;
			
		}

		return $output;

	}


	/**
	 * Get all invoices from Reviso
	 * @return array
	 */
	public function get_remote_invoices() {

		$output = $this->wcefrCall->call( 'get', 'v2/invoices/drafts?pagesize=10000'  );

		return $output;

	}


	/**
	 * TEMP
	 * Get the wc payment gatewauys available
	 * @return array
	 */
	public function get_available_methods() {

		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$enabled_gateways = [];

		if( $gateways ) {
		    foreach( $gateways as $gateway ) {

		        if( $gateway->enabled == 'yes' ) {

		            $enabled_gateways[] = $gateway;

		        }
		    }
		}

	}


	/**
	 * Check if a specific payment method exists in Reviso
	 * @param  string $payment_gateway the wc payment gateway
	 * @return int the Reviso payment method number              
	 */
	private function payment_metod_exists( $payment_gateway ) {

		$response = $this->wcefrCall->call( 'get', 'payment-terms?filter=name$eq:' . $payment_gateway );

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

			return $response->collection[0];
		
		}

	}


	/**
	 * Add a specific payment method in reviso
	 * @param string $payment_gateway the wc payment gateway
	 */
	public function add_remote_payment_method( $payment_gateway ) {

		$output = $this->payment_metod_exists( $payment_gateway ); 

		if ( ! $output ) {

			$args = array(
				'name' 			   => $payment_gateway,
				'paymentTermsType' => 'net', //temp
				'daysOfCredit' 	   => 0,
			);

			$response = $this->wcefrCall->call( 'post', 'payment-terms', $args );

			if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

				$output = $response->collection[0];
			
			}

		}

		return $output;

	}


	/**
	 * Prepare the data of all the items of the order
	 * @param  object $order the wc order
	 * @return array
	 */
	private function order_items_data( $order ) {

		$output = array();

		if ( $order->get_items() ) {
	
			$n = 0;
			foreach ( $order->get_items() as $item_id => $item ) {

				$n++;
				$product = $item->get_product();

				if ( $product ) {

					$output[] = array(

						'lineNumber' => $n,
						'quantity' => wc_stock_amount( $item['qty'] ),
						'product' => array(
							'id' => $product->get_sku(),
							'productNumber' => $product->get_sku(),
							'name' => $item['name'],
						),
						
						'description' => $item['name'],
						'discountPercentage' => 0.00, //temp
						// 'marginInBaseCurrency' => 6.00,
						// 'marginPercentage' => 100.00,
						'quantity' => wc_stock_amount( $item['qty'] ),
						// 'sortKey' => 1,
						'totalNetAmount' => floatval( wc_format_decimal( $order->get_line_subtotal($item, false, false), 2 ) ),
						'totalGrossAmount' => floatval( wc_format_decimal( $order->get_line_total($item, false, false ), 2 ) ) + floatval( wc_format_decimal( $item['line_tax'], 2 ) ),
						'totalVatAmount' => floatval( wc_format_decimal( $item['line_tax'], 2 ) ),
						'unit' => array(
							'name' => 'Pezzi',
							'unitNumber' => 1,
						),
						// 'unitCostPrice' => 0.0000000000,
						'unitNetPrice' => floatval( wc_format_decimal( $order->get_item_total($item, false, false), 2 ) ),
						// 'deliveredQuantity' => 0.0000000000,
						// 'manuallyEditedSalesPrice' => false,
					);

				}

			}
		}

		return $output;

	}


	/**
	 * TEMP
	 * Get additional expenses from Reviso
	 * @return object
	 */
	private function get_additional_expenses() {

		$output = null;


		$response = $this->wcefrCall->call( 'get', 'additional-expenses' );

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {
			
			$output = $response->collection[0];
			error_log( 'additional-expenses: ' . print_r( $output, true ) );

		}

		return $output;

	}


	/**
	 * Prepare order data to export to Reviso
	 * @param  object $order the WC order
	 * @return array
	 */
	private function prepare_order_data( $order ) {

		$company_name  = $order->get_billing_company();
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$client_name   = $company_name ? $company_name : $customer_name;
		$pa_code	   = get_post_meta( $order->get_id(), '_billing_wcefr_pa_code', true );

		// Add the payment method if not already on Reviso
		$payment_method = $this->add_remote_payment_method( $order->get_payment_method_title() );

		$output = array(
			'currency' => $order->get_currency(),
			'customer'  => array(
				'splitPayment'	 => false,
				'customerNumber' => 1, //temp
			),
			'date' => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'delivery' => array(
				'address' => $order->get_shipping_address_1(),
				'city' => $order->get_shipping_city(),
				'country' => $order->get_shipping_country(),
				'zip' => $order->get_shipping_postcode(),
			),
			'dueDate' => $order->get_date_created()->date( 'Y-m-d H:i:s' ), //temp
			'exchangeRate' => 100.00,
			'grossAmount' => floatval( wc_format_decimal( $order->get_total(), 2 ) ),
			'isArchived' => false,
			'isSent' => false,
			'layout' => array( //temp
				'isDefault' => false,
				'layoutNumber' => 9,
			),
			'paymentTerms' => $payment_method,
			'recipient' => array(
				'address' => $order->get_billing_address_1(),
				'city' => $order->get_billing_city(),
				'country' => $order->get_billing_country(),
				'name' =>  $client_name,
				'publicEntryNumber' => $pa_code,
				'vatZone' => array(
					'vatZoneNumber' => 1, //temp
				),
				'zip' => $order->get_billing_postcode(),
				// 'attention' => array(
				// 	'name' => $customer_name,
				// 	// 'emailNotifications' => $order->get_billing_email(),
				// ),
			),
			'roundingAmount' => 0.00,
			'vatAmount' =>floatval( wc_format_decimal( $order->get_total_tax(), 2 ) ),
			'vatIncluded' => true, //temp
 			'notes' => array(
 				'text1' => 'WC-Order-' . $order->get_id(),
 			),
 			'lines' => $this->order_items_data( $order ),
 			'additionalExpenseLines' => array( //temp
 				array(
	 				// 'additionalExpense' => $this->get_additional_expenses(),
	 				'additionalExpense' => array(
	 					'additionalExpenseNumber' => 1, //temp
	 				),
 					'lineNumber' => 1,
 					'additionalExpenseType' => 'Transport',
 					'amount' => floatval( wc_format_decimal( $order->get_total_shipping(), 2 ) ),
 					// 'grossAmount' => floatval( wc_format_decimal( $order->get_total_tax(), 2 ) ),
 					'isExcluded' => false,
 					'vatAmount' => floatval( wc_format_decimal( $order->get_shipping_tax(), 2 ) ),
 					// 'vatRate' => xxx,
 				),
 			),

		);

		return $output;

	}


	/**
	 * Export the single WC order to Reviso
	 * @param  int  $order_id the order id
	 * @param  bool $invoice export to Reviso as an invoice
	 */
	public function export_single_order( $order_id, $invoice = false ) {

		$order = new WC_Order( $order_id );					
		$args = $this->prepare_order_data( $order );

		if ( $args ) {

			$endpoint = $invoice ? '/v2/invoices/drafts/' : 'orders';

			$output = $this->wcefrCall->call( 'post', $endpoint, $args );

		}

	}


	/**
	 * Export WC orders to Reviso
	 */
	public function export_orders() {

		$statuses = isset( $_POST['statuses'] ) ? $_POST['statuses'] : array( 'any' );

		$response = array();
		
		$args = array(
			'post_type' => 'shop_order',  
			'posts_per_page' => -1
		);

		/*Modify the query with the orders statuses choosed by the admin*/
		if ( is_array( $statuses ) && ! empty( $statuses ) ) {
			
			$args['post_status'] = $statuses;

			/*Update the db*/
			update_option( 'wcefr-orders-statuses', $statuses );

		}

		$posts = get_posts( $args );
		
		if( $posts ) {

			$n = 0;
			
			foreach ( $posts as $post ) {

				$n++;

				if ( ! $this->document_exists( $post->ID ) && ! $this->document_exists( $post->ID, true ) ) {
	
						/*Cron event*/
						wp_schedule_single_event(

							time() + 1,
							'wcefr_export_single_order_event',
							array(
								$post->ID
							)
							
						);													
	
				} else {

					/*The order is already in Reviso*/
					$response[] = array(
						'error',
						__( 'The order <span>' . $post->ID . '</span> has already been exported', 'wcefr' ),			
					);

				}
				
			}

		}

		$response[] = array(
			'ok',
			__( 'Orders to export: <span>' . $n . '</span>', 'wcefr' ),			
		);

		echo json_encode( $response );

		exit;

	}


	/**
	 * Delete the single order from Reviso
	 * @param  int $n        the count of orders to delete
	 * @param  int $order_id the order id to delete
	 */
	public function delete_remote_single_order( $n, $order_id ) {

		$output = $this->wcefrCall->call( 'delete', 'orders/' . $order_id );
	
		if ( 1 === $output->deletedCount ) {
			
			$response = array(
				'ok',
				__( 'Deleted order: <span>' . $n . '</span>', 'wcefr' ),			
			);

		} else {

			$response = array(
				'error',
				__( 'ERROR! An error occurred with the order #' . $product->order->id . '<br>', 'wcefr' ),
			);

		}

	}


	/**
	 * Delete orders in reviso
	 * @param  int $id the order id for a specific order
	 */
	public function delete_remote_orders( $id = null ) {

		if ( $id ) {

			$this->wcefrCall->call( 'delete', 'orders/' . $id );

		} else {

			$orders = $this->get_remote_orders();

			if ( isset( $orders->collection ) && count( $orders->collection ) > 0 ) {

				$n = 0;
				$response = array();

				foreach ( $orders->collection as $order ) {

					$n++;
					

					/*Cron event*/
					wp_schedule_single_event(

						time() + 1,
						'wcefr_delete_remote_single_order_event',
						array(
							$n,
							$order->id,
						)
						
					);													

					echo json_encode( $response );

				}

				$response[] = array(
					'ok',
					__( 'The delete process is started', 'wcefr' ),
				);

				echo json_encode( $response );

			} else {
				
				$response[] = array(
					'error',
					__( 'ERROR! There are not orders to delete', 'wcefr' ),
				);

				echo json_encode( $response );

			}

			exit;

		}

	} 


	/**
	 * Create a new Reviso invoice and delete the relative remote order if exists
	 * @param  int $order_id the wc order id
	 */
	public function create_single_invoice( $order_id ) {

		// Delete order
		if ( $id = $this->document_exists( $order_id ) ) {
			
			$this->delete_remote_orders( $id );
			
		}

		// Create invoice
		if ( ! $this->document_exists( $order_id, true ) ) {

			$this->export_single_order( $order_id, true );

		}
		
	}

}
new wcefrOrders;
