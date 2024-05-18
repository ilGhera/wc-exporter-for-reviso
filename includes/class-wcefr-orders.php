<?php
/**
 * Export orders to reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.3.0
 */

/**
 * WCEFR_Orders
 */
class WCEFR_Orders {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$this->export_orders                 = get_option( 'wcefr-export-orders' );
			$this->create_invoices               = get_option( 'wcefr-create-invoices' );
			$this->issue_invoices                = get_option( 'wcefr-issue-invoices' );
			$this->send_invoices                 = get_option( 'wcefr-send-invoices' );
			$this->book_invoices                 = get_option( 'wcefr-book-invoices' );
			$this->number_series_prefix          = get_option( 'wcefr-number-series-prefix' );
			$this->number_series_prefix_receipts = get_option( 'wcefr-number-series-receipts-prefix' );
			$this->init();

			add_action( 'wp_ajax_wcefr-export-orders', array( $this, 'export_orders' ) );
			add_action( 'wp_ajax_wcefr-delete-remote-orders', array( $this, 'delete_remote_orders' ) );
			add_action( 'wcefr_export_single_order_event', array( $this, 'export_single_order' ), 10, 1 );
			add_action( 'wcefr_delete_remote_single_order_event', array( $this, 'delete_remote_single_order' ), 10, 2 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'wc_columns_content' ), 10, 2 );
			add_action( 'admin_print_styles', array( $this, 'invoice_column_style' ) );

			add_filter( 'manage_edit-shop_order_columns', array( $this, 'wc_columns_head' ) );
			add_filter( 'woocommerce_email_attachments', array( $this, 'email_attachments' ), 10, 3 );

		}

		$this->wcefr_call = new WCEFR_Call();

	}


	/**
	 * Check the administrator settings to automatically export orders to Reviso
	 *
	 * @return void
	 */
	public function init() {

		/*Export orders automatically to Reviso*/
		if ( $this->export_orders ) {

			add_action( 'woocommerce_thankyou', array( $this, 'single_order_async_action' ) );

		}

		/*Create invoices in Reviso with WC completed orders */
		if ( $this->create_invoices ) {

			add_action( 'woocommerce_order_status_completed', array( $this, 'single_order_async_action' ) );

			if ( $this->issue_invoices && $this->send_invoices ) {

				/* Remove order completed notification */
				add_action(
					'woocommerce_email',
					function( $email_class ) {

						remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );

					}
				);

			}
		}

	}


	/**
	 * Add style to the Invoice column in the orders index
	 */
	public function invoice_column_style() {

		$css = '.post-type-shop_order .wp-list-table .column-order_invoice { width: 5%; text-align: center; }';
		wp_add_inline_style( 'woocommerce_admin_styles', $css );

	}


	/**
	 * Add the title to the Invoice column
	 *
	 * @param  array $defaults the wc column heads.
	 * @return array
	 */
	public function wc_columns_head( $defaults ) {

		$defaults['order_invoice'] = __( 'Invoice', 'wc-exporter-for-reviso' );

		return $defaults;

	}

	/**
	 * Set the preview invoice button
	 *
	 * @param  string $column   the WC order index column.
	 * @param  int    $order_id the WC order id.
	 * @return mixed
	 */
	public function wc_columns_content( $column, $order_id ) {

		if ( 'order_invoice' === $column ) {

			$invoice_number = get_post_meta( $order_id, 'wcefr-invoice', true );

			if ( $invoice_number ) {

				$icon = WCEFR_URI . 'images/pdf.png';

				echo '<a href="?wcefr-preview=true&order-id=' . esc_attr( $order_id ) . '" target="_blank" title="' . esc_attr( $invoice_number ) . '"><img src="' . esc_url( $icon ) . '"></a>';

			} else {

				$icon      = WCEFR_URI . 'images/pdf-black.png';
				$order     = wc_get_order( $order_id );
				$scheduled = as_has_scheduled_action(
					'wcefr_export_single_order_event',
					array(
						'order_id' => $order_id,
					),
					'wcefr_export_single_order',
				);

				if ( 'completed' === $order->get_status() && $scheduled ) {

					echo '<a class="not-available" title="' . esc_attr__( 'Not available yet', 'wc-exporter-for-reviso' ) . '"><img src="' . esc_url( $icon ) . '"></a>';

				}
			}
		}

	}


	/**
	 * Get all the orders from Reviso
	 *
	 * @return array
	 */
	public function get_remote_orders() {

		$output  = $this->wcefr_call->call( 'get', 'orders?pagesize=1000' );
		$results = isset( $output->pagination->results ) ? $output->pagination->results : '';

		if ( 1000 < $results ) {

			$limit = $results / 1000;

			for ( $i = 1; $i < $limit; $i++ ) {

				$get_orders = $this->wcefr_call->call( 'get', 'orders?skippages=' . $i . '&pagesize=1000' );

				if ( isset( $get_orders->collection ) && ! empty( $get_orders->collection ) ) {

					$output->collection = array_merge( $output->collection, $get_orders->collection );

				} else {

					continue;

				}
			}
		}

		return $output;

	}


	/**
	 * Get all invoices from Reviso
	 *
	 * @param bool   $booked search for booke invoices if true.
	 * @param string $filter to get a specific invoice.
	 * @return array
	 */
	public function get_remote_invoices( $booked = false, $filter = false ) {

		$status  = $booked ? 'booked' : 'drafts';
		$filter  = $filter ? $filter : '?pagesize=1000';
		$output  = $this->wcefr_call->call( 'get', 'v2/invoices/' . $status . $filter );
		$results = isset( $output->pagination->results ) ? $output->pagination->results : '';

		if ( 1000 < $results ) {

			$limit = $results / 1000;

			for ( $i = 1; $i < $limit; $i++ ) {

				$get_invoices = $this->wcefr_call->call( 'get', 'v2/invoices/' . $status . '?skippages=' . $i . '&pagesize=1000' );

				if ( isset( $get_invoices->collection ) && ! empty( $get_invoices->collection ) ) {

					$output->collection = array_merge( $output->collection, $get_invoices->collection );

				} else {

					continue;

				}
			}
		}

		return $output;

	}


	/**
	 * Check if a specific payment term exists in Reviso
	 *
	 * @param string $term_name the payment term name to search in Reviso.
	 *
	 * @return int the Reviso payment term number.
	 */
	private function payment_term_exists( $term_name ) {

		$output    = null;
		$transient = get_transient( 'wcefr-payment-term' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'payment-terms?filter=name$eq:' . $term_name );

			if ( isset( $response->collection[0] ) && ! empty( $response->collection[0] ) ) {

				set_transient( 'wcefr-payment-term', $response->collection[0], DAY_IN_SECONDS );

				$output = $response->collection[0];

			}
		}

		return $output;

	}


	/**
	 * Add a specific payment term in Reviso
	 *
	 * @return object
	 */
	public function get_remote_payment_term() {

		$term_name = __( 'Order date', 'wc-exporter-for-reviso' );
		$output    = $this->payment_term_exists( $term_name );

		if ( ! $output ) {

			delete_transient( 'wcefr-payment-term' );

			$args = array(
				'name'             => $term_name,
				'paymentTermsType' => 'net', // temp.
				'daysOfCredit'     => 0,
			);

			$response = $this->wcefr_call->call( 'post', 'payment-terms', $args );

			if ( isset( $response->name ) ) {

				$output = $response;

			}
		}

		return $output;

	}


	/**
	 *
	 * Get the wc payment gateways available
	 *
	 * @return array
	 */
	public function get_wc_available_methods() {

		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array();

		if ( $gateways ) {

			foreach ( $gateways as $gateway ) {

				/* if ( 'yes' == $gateway->enabled ) { */

					$enabled_gateways[] = $gateway->id;

				/* } */
			}
		}

		return $enabled_gateways;

	}


	/**
	 * Get the payment methods from Reviso
	 *
	 * @return array the Reviso payment methods.
	 */
	private function get_remote_payment_metods() {

		$output    = null;
		$transient = get_transient( 'wcefr-payment-methods' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'payment-types' );

			if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

				set_transient( 'wcefr-payment-methods', $response->collection, DAY_IN_SECONDS );

				$output = $response->collection;

			}
		}

		return $output;

	}


	/**
	 * Get the specific payment method in reviso
	 *
	 * @param string $payment_gateway the wc payment gateway ID.
	 *
	 * @return object the payment method
	 */
	public function get_remote_payment_method( $payment_gateway = null ) {

		$remote_methods = $this->get_remote_payment_metods();
		$method_name    = null;

		switch ( $payment_gateway ) {
			case 'bacs':
				$method_name = 'Bank transfer';
				break;
			case 'cheque':
				$method_name = 'Check';
				break;
			case 'cod':
				$method_name = 'Cash';
				break;
			case 'findomestic':
				$method_name = 'RID';
				break;
			default:
				$method_name = 'Payment card';
				break;
		}

		foreach ( $remote_methods as $method ) {

			if ( strtolower( $method_name ) === strtolower( $method->name ) ) {

				return $method;

			}
		}

	}


	/**
	 * Calculate the percentage between two numbers
	 *
	 * @param  float $value the result of the percentage.
	 * @param  float $total the total number.
	 * @return float        the percentage
	 */
	private function get_percentage( $value, $total ) {

		if ( 0 !== intval( $total ) ) {

			return floatval( wc_format_decimal( ( $value / $total * 100 ), 2 ) );

		}

	}


	/**
	 * Get the total order discount
	 *
	 * @param object $order the order.
	 * @return float the discount percentage
	 */
	private function get_order_discount_percentage( $order ) {

		$net_total = number_format(
			(float) $order->get_total() -
			$order->get_total_tax() -
			$order->get_total_shipping() +
			/* $order->get_shipping_tax()   + */
			$order->get_total_discount(),
			10,
			'.',
			''
		);

		return $this->get_percentage( $order->get_total_discount(), $net_total );

	}


	/**
	 * Get a specific vat account from Reviso or create it necessary
	 *
	 * @param  int    $vat_rate the vat rate.
	 * @param  string $vat_code the vat code.
	 *
	 * @return array  vat accounts available in Reviso
	 */
	private function get_remote_vat_code( $vat_rate, $vat_code = null ) {

		$class = new WCEFR_Products();

		return $class->get_remote_vat_code( $vat_rate, $vat_code );

	}


	/**
	 * Prepare the data of all the items of the order
	 *
	 * @param  object $order the wc order.
	 * @return array
	 */
	private function order_items_data( $order ) {

		$output = array();
		$class  = new WCEFR_Products();

		/* Get order tax labels */
		$tax_labels = array();

		foreach ( $order->get_items( 'tax' ) as $item ) {

			$tax_labels[ $item->get_rate_id() ] = $item->get_label();

		}

		/* Order items */
		if ( $order->get_items() ) {

			$n = -1;
			foreach ( $order->get_items() as $item_id => $item ) {

				$n++;
				$item_data = $item->get_data();
				$product   = $item->get_product();

				if ( $product ) {

					$sku                = $product->get_sku() ? $product->get_sku() : ( 'wc-' . $product->get_id() );
					$qty                = wc_stock_amount( $item['qty'] );
					$total_net_amount   = floatval( wc_format_decimal( $order->get_line_subtotal( $item, false, false ), 10 ) );
					$total_gross_amount = floatval( wc_format_decimal( $order->get_line_total( $item, false, false ), 10 ) ) + floatval( wc_format_decimal( $item['line_tax'], 10 ) );
					$total_vat_amount   = floatval( wc_format_decimal( $item['line_tax'], 10 ) );
					$vat_rate           = $this->get_percentage( $total_vat_amount, $total_net_amount );

					$output[ $n ] = array(

						'lineNumber'         => $n + 1,
						'quantity'           => $qty,
						'description'        => $item['name'],
						'discountPercentage' => $this->get_order_discount_percentage( $order ),
						'quantity'           => wc_stock_amount( $item['qty'] ),
						'totalNetAmount'     => $total_net_amount,
						'totalGrossAmount'   => $total_gross_amount,
						'unitNetPrice'       => floatval( wc_format_decimal( $total_net_amount / $qty, 10 ) ),
						'totalVatAmount'     => $total_vat_amount,
						'product'            => array(
							'id'            => $sku,
							'productNumber' => $sku,
							'name'          => $item['name'],
						),
						'unit'               => array(
							'name'       => 'Pezzi',
							'unitNumber' => 1,
						),

					);

					/*Departmental distribution*/
                    if ( $class->dimension_module() ) {

						$specific_dist = get_post_meta( $product->get_id(), 'wcefr-departmental-distribution', true );
						$generic_dist  = get_option( 'wcefr-departmental-distribution' );
						$dist          = 0 !== intval( $specific_dist ) ? $specific_dist : $generic_dist;
                        $dist          = apply_filters( 'wcefr-product-dep-distribution', $dist, $order->get_id() );

						if ( $dist ) {

							$output[ $n ]['departmentalDistribution'] = array(
								'departmentalDistributionNumber' => $dist,
							);

						}
					}
				}

				/* Get the label tax of the specific order item */
				$taxes = $item->get_taxes();

				foreach ( $taxes['subtotal'] as $rate_id => $tax ) {

					$tax_label = $tax_labels[ $rate_id ];

					/* Add vatInfo to the item data */
					$output[ $n ]['vatInfo'] = array(
						'vatAccount' => array(
							'vatCode' => $this->get_remote_vat_code( $vat_rate, $tax_label ),
						),
					);

				}
			}
		}

		return $output;

	}


	/**
	 * Get additional expenses from Reviso
	 *
	 * @param  int $additional_expense_number the id of the specific addition expenses to get.
	 * @return mixed
	 */
	public function get_additional_expenses( $additional_expense_number = null ) {

		$output   = null;
		$endpoint = $additional_expense_number ? '/' . $additional_expense_number : '';

		/* Get transient */
		$transient = get_transient( 'wcefr-additional-expenses' );

		if ( $transient ) {

			$response = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'additional-expenses' . $endpoint );

		}

		if ( $endpoint ) {

			$output = $response;

		} elseif ( isset( $response->collection ) && ! empty( $response->collection ) ) {

			$output = $response->collection;

			/* Add transient */
			set_transient( 'wcefr-additional-expenses', $response, DAY_IN_SECONDS );

		}

		return $output;

	}


	/**
	 * Add a new additional expenses to Reviso
	 *
	 * @param boolean $transport with true create the additional expenses to use with WC Shipping.
	 * @param mixed   $args      null or an array of arguments for the new additional expenses.
	 * @param int     $vat_rate  the vat rate.
	 *
	 * @return init
	 */
	public function add_additional_expenses( $transport = true, $args = null, $vat_rate = null ) {

		if ( $transport ) {

			$args = array(
				'name'                  => __( 'Transportation fee', 'wc-exporter-for-reviso' ),
				'account'               => array(
					'accountNumber' => '5805490',
				),
				'additionalExpenseType' => 'transport',
				'vatAccount'            => array(
					'vatCode' => $this->get_remote_vat_code( $vat_rate ),
				),
			);

		}

		$response = $this->wcefr_call->call( 'post', 'additional-expenses', $args );

		if ( isset( $response->additionalExpenseNumber ) ) {

			return $response->additionalExpenseNumber;

		}

	}


	/**
	 * Get additional expenses to use for transport or create it if doesn't exist
	 *
	 * @param int $transport_vat_rate the transport vat rate used in the order.
	 *
	 * @return object
	 */
	public function get_transport_additional_expenses( $transport_vat_rate ) {

		$output = array();

		$additional_expenses = $this->get_additional_expenses();

		if ( $additional_expenses ) {

			foreach ( $additional_expenses as $single ) {

				if ( 'transport' === $single->additionalExpenseType ) {
					$output[] = $single;
				}
			}
		}

		if ( ! empty( $output ) ) {

			$output = array(
				'additionalExpenseNumber' => $output[0]->additionalExpenseNumber,
			);

		} else {

			$output = $this->add_additional_expenses( true, null, $transport_vat_rate );

		}

		return $output;

	}


	/**
	 * Get the user from Reviso by email
	 *
	 * @param  string $email  the user email.
	 * @param  object $order  the WC order to get the customer details.
	 * @param  bool   $update update user with true.
	 *
	 * @return int the Reviso customer number
	 */
	private function get_remote_customer( $email, $order, $update = false ) {

		$response = $this->wcefr_call->call( 'get', 'customers?filter=email$eq:' . $email );

		/* Get the WP user if exists */
		$user_id = $order->get_user_id();

		/*Add the new user in Reviso*/
		$wcefr_users = new WCEFR_Users();

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

			$customer_number = $response->collection[0]->customerNumber;

			if ( ! $update ) {

				return $customer_number;

			} else {

				$user = $wcefr_users->export_single_user( $user_id, 'customers', $order, false, $customer_number );

				if ( isset( $user->customerNumber ) ) {

					return $user->customerNumber;

				}
			}
		} else {

			$new_user = $wcefr_users->export_single_user( $user_id, 'customers', $order, true );

			return $new_user->customerNumber;

		}

	}


	/**
	 * Add current year in Reviso accounting years if it doesn't exists
	 *
	 * @return bool
	 */
	public function check_remote_accounting_years() {

		$output   = false;
		$year     = wp_date( 'Y' );
		$response = $this->wcefr_call->call( 'get', 'accounting-years/' . $year );

		if ( is_array( $response ) && isset( $response['year'] ) && $year === $response['year'] ) {

			return true;

		} else {

			$args = array(
				'fromDate' => wp_date( $year ) . '-01-01',
				'toDate'   => wp_date( $year ) . '-12-31',
				'year'     => $year,
			);

			$add = $this->wcefr_call->call( 'post', 'accounting-years', $args );

			if ( is_array( $add ) && isset( $add['year'] ) && $year === $add['year'] ) {

				return true;

			}
		}

	}


	/**
	 * Get a specific number serie from Reviso
	 *
	 * @param  string $prefix     examples are FVE, FVL, ecc.
	 * @param  string $entry_type used to filter the number series.
	 * @param  bool   $first      if true returns the numberSeriesNumber of the first result, otherwise all the array.
	 * @return mixed
	 */
	public function get_remote_number_series( $prefix = null, $entry_type = null, $first = false ) {

		if ( $prefix ) {

			$transient_name = 'wcefr-number-series-prefix';
			$args           = '?filter=prefix$eq:' . $prefix;

		} elseif ( $entry_type ) {

			$transient_name = 'wcefr-number-series-type';
			$args           = '?filter=entryType$eq:' . $entry_type;

		} else {

			$transient_name = 'wcefr-number-series';
			$args           = null;

		}

		/* Get the transient */
		$transient = get_transient( $transient_name );

		if ( $transient ) {

			$response = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'number-series' . $args );

		}

		if ( isset( $response->collection ) ) {

			if ( ! $transient ) {

				/* Set the transient */
				set_transient( $transient_name, $response, DAY_IN_SECONDS );

			}

			if ( $first && isset( $response->collection[0]->numberSeriesNumber ) ) {

				return $response->collection[0]->numberSeriesNumber;

			} else {

				return $response->collection;

			}
		}

	}


	/**
	 * Used for issuing an invoice
	 *
	 * @param  object $order        the wc order.
	 * @param  int    $customer_number the Reviso customer number.
	 * @return object
	 */
	private function create_remote_voucher( $order, $customer_number = null ) {

		$lines = array();

		if ( ! $customer_number ) {

			$customer_number = $this->get_remote_customer( $order->get_billing_email(), $order );

		}

		if ( $order->get_items() ) {

			foreach ( $order->get_items() as $item_id => $item ) {

				$total_gross_amount = floatval( wc_format_decimal( $order->get_line_total( $item, false, false ), 10 ) ) + floatval( wc_format_decimal( $item['line_tax'], 10 ) );

				$lines[] = array(
					'customer' => array(
						'customerNumber' => $customer_number,
					),
					'amount'   => $total_gross_amount,
					'currency' => $order->get_currency(),
					'text'     => $item['name'],
				);

			}
		}

		$args = array(
			'date'         => wp_date( 'Y-m-d' ),
			'lines'        => $lines,
			'numberSeries' => array(
				'numberSeriesNumber' => $this->get_remote_number_series( $this->get_order_ns_prefix( $order ), null, true ),
			),
		);

		$response = $this->wcefr_call->call( 'post', '/vouchers/drafts/customer-invoices', $args );

		return $response;

	}


	/**
	 * Get the vatZone of the order, based on the customer location
	 *
	 * @param  string $country the two letters country code.
	 * @return int             the vatZoneNumber
	 */
	private function get_vat_zone( $country ) {

		$countries         = new WC_Countries();
		$all_countries     = $countries->get_countries(); // temp.
		$europen_countries = $countries->get_european_union_countries();
		$base_country      = $countries->get_base_country();

		if ( $country === $base_country ) {

			return 1;

		} elseif ( in_array( $country, $europen_countries, true ) ) {

			return 2;

		} else {

			return 3;

		}

	}


	/**
	 * Check if a wc order is already on Reviso
	 *
	 * @param  int  $order_id         the wc order id.
	 * @param  bool $invoice          search in invoices instead of orders.
	 * @param  bool $invoice_details  if set to true the method returns an array with invoice id its status.
	 * @return mixed
	 */
	public function document_exists( $order_id, $invoice = false, $invoice_details = false ) {

		$filter    = '?filter=notes.text1$eq:WC-Order-' . $order_id;
		$responses = array();

		if ( $invoice ) {

			// $responses[] = $this->wcefr_call->call( 'get', '/v2/invoices/drafts' . $filter );
			$responses['drafts'] = $this->get_remote_invoices( false, $filter );

			/*Booked invoices endpoint requires a different filter*/
			$responses['booked'] = $this->get_remote_invoices( true, '?filter=notes.textLine1$eq:WC-Order-' . $order_id );

		} else {

			$responses[] = $this->wcefr_call->call( 'get', 'orders' . $filter );

		}

		foreach ( $responses as $key => $value ) {

			if ( isset( $value->collection ) && ! empty( $value->collection ) ) {

				$result = $value->collection[0];

				$id = isset( $result->id ) ? $result->id : $result->bookedInvoiceNumber;

				if ( $invoice_details ) {

					// $number = 'drafts' === $key ? $result->voucher->voucherNumber->displayVoucherNumber : $result->displayInvoiceNumber;

					return array(
						'id'     => $id,
						'number' => $result->number,
						'status' => $key,
					);

				} else {

					return $id;

				}
			}
		}

	}


	/**
	 * Attach Reviso invoice to the WC completed order and custome invoice email
	 *
	 * @param  array  $attachments the WC mail attachments.
	 * @param  string $status      the order status.
	 * @param  object $order       the WC order.
	 */
	public function email_attachments( $attachments, $status, $order ) {

		if ( $this->issue_invoices && $this->send_invoices ) {

			$allowed_statuses = array( 'customer_invoice', 'customer_completed_order' );

			if ( isset( $status ) && in_array( $status, $allowed_statuses, true ) ) {

				$invoice = $this->document_exists( $order->get_id(), true, true );

				if ( isset( $invoice['id'] ) && isset( $invoice['status'] ) ) {

					$filename = 'Invoice-' . $invoice['number'] . '-';

					$pdf = tempnam( sys_get_temp_dir(), $filename );

					rename( $pdf, $pdf .= '.pdf' );

					$file = $this->wcefr_call->call( 'get', '/v2/invoices/' . $invoice['status'] . '/' . $invoice['id'] . '/pdf', null, false );

					$handle = fopen( $pdf, 'w' );

					fwrite( $handle, $file );

					$attachments[] = $pdf;

					fclose( $handle );
					/*unlink( $pdf );*/

				}
			}
		}

		return $attachments;

	}


	/**
	 * Get the number series prefix based on the order type
	 *
	 * @param object $order the order.
	 * @return string
	 */
	private function get_order_ns_prefix( $order ) {

		if ( 'private' === $order->get_meta( '_billing_wcefr_invoice_type' ) ) {

			return $this->number_series_prefix_receipts;

		} else {

			return $this->number_series_prefix;

		}

	}


	/**
	 * Prepare order data to export to Reviso
	 *
	 * @param  object $order the WC order.
	 * @return array
	 */
	private function prepare_order_data( $order ) {

		$company_name           = $order->get_billing_company();
		$customer_name          = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$client_name            = $company_name ? $company_name : $customer_name;
		$pa_code                = get_post_meta( $order->get_id(), '_billing_wcefr_pa_code', true );
		$transport_amount       = floatval( wc_format_decimal( $order->get_total_shipping(), 10 ) );
		$transport_vat_amount   = floatval( wc_format_decimal( $order->get_shipping_tax(), 10 ) );
		$transport_vat_rate     = $this->get_percentage( $transport_vat_amount, $transport_amount );
		$transport_gross_amount = $transport_amount + $transport_vat_amount;
		$order_completed        = 'completed' === $order->get_status() ? true : false;
		$customer_number        = $this->get_remote_customer( $order->get_billing_email(), $order, true );
		$vat_included           = 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? 1 : 0;

		/*Add the payment method if not already on Reviso*/
		$payment_method_title = $order->get_payment_method() ? $order->get_payment_method() : __( 'Direct', 'wc-exporter-for-reviso' );
		$payment_method       = $this->get_remote_payment_method( $payment_method_title );
		$payment_term         = $this->get_remote_payment_term();

		/* Save user metas */
		$user_id = $order->get_user_id();

		if ( 0 !== $user_id ) {
			update_user_meta( $user_id, 'wcefr-payment-method', $payment_method );
		}

		$output = array(
			'currency'       => $order->get_currency(),
			'date'           => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'dueDate'        => $order->get_date_created()->date( 'Y-m-d H:i:s' ), // temp.
			'exchangeRate'   => 100.00,
			'grossAmount'    => floatval( wc_format_decimal( $order->get_total(), 2 ) ),
			'isArchived'     => false,
			'isSent'         => false,
			'paymentTerms'   => $payment_term,
			'paymentType'    => $payment_method,
			'roundingAmount' => 0.00,
			'vatDate'        => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
			'vatAmount'      => floatval( wc_format_decimal( $order->get_total_tax(), 2 ) ),
			'vatIncluded'    => $vat_included,
			'lines'          => $this->order_items_data( $order ),
			'customer'       => array(
				'splitPayment'   => false,
				'customerNumber' => $customer_number,
			),
			'delivery'       => array(
				'address' => $order->get_shipping_address_1(),
				'city'    => $order->get_shipping_city(),
				'country' => $order->get_shipping_country(),
				'zip'     => $order->get_shipping_postcode(),
			),
			'recipient'      => array(
				'address'           => $order->get_billing_address_1(),
				'city'              => $order->get_billing_city(),
				'country'           => $order->get_billing_country(),
				'name'              => $client_name,
				'publicEntryNumber' => $pa_code,
				'zip'               => $order->get_billing_postcode(),
				'vatZone'           => array(
					'vatZoneNumber' => $this->get_vat_zone( $order->get_billing_country() ), // temp.
				),
			),
			'notes'          => array(
				'text1' => 'WC-Order-' . $order->get_id(),
			),
			'numberSeries'   => array(
				'numberSeriesNumber' => $this->get_remote_number_series( $this->get_order_ns_prefix( $order ), null, true ),
			),
		);

		if ( $order_completed && $this->create_invoices ) {

			$output['additionalExpenseLines'] = array( // temp.
				array(
					'additionalExpense'     => $this->get_transport_additional_expenses( $transport_vat_rate ),
					'additionalExpenseType' => 'Transport',
					'lineNumber'            => 1,
					'amount'                => $transport_amount,
					'vatAccount'            => array(
						'vatCode' => $this->get_remote_vat_code( $transport_vat_rate ),
					),
					// 'grossAmount'           => $transport_gross_amount,
					// 'isExcluded'            => false,
					// 'vatAmount'             => $transport_vat_amount,
					// 'vatRate'               => $transport_vat_rate,
				),
			);

			if ( $this->issue_invoices ) {

				$output['voucher'] = $this->create_remote_voucher( $order, $customer_number );

			}
		}

		return $output;

	}


	/**
	 * Export the single WC order to Reviso
	 *
	 * @param  int  $order_id the order id.
	 * @param  bool $invoice export to Reviso as an invoice.
	 */
	public function export_single_order( $order_id, $invoice = false ) {

		$order          = new WC_Order( $order_id );
		$invoice        = 'completed' === $order->get_status() ? true : $invoice;
		$order_exists   = $this->document_exists( $order_id );
		$invoice_exists = $this->document_exists( $order_id, true, true );

		if ( $invoice && $order_exists ) {

			$this->delete_remote_orders( $order_exists );
			$order_exists = false;

		}

		if ( ! $order_exists && ! isset( $invoice_exists['id'] ) ) {

			$args            = $this->prepare_order_data( $order );
			$order_completed = 'completed' === $order->get_status() ? true : false;
			$invoice         = $order_completed ? $order_completed : $invoice;

			if ( $args ) {
				$endpoint = $invoice ? '/v2/invoices/drafts/' : 'orders';

				$output = $this->wcefr_call->call( 'post', $endpoint, $args );

				/*An invoice for this order is ready on Reviso*/
				if ( $invoice && isset( $output->id ) ) {

					update_post_meta( $order_id, 'wcefr-invoice', $output->id );

					if ( $this->issue_invoices ) {

						$data = $output->voucher->voucherNumber->displayVoucherNumber;

						/*Book the invoise if set by the admin*/
						if ( $this->book_invoices ) {

							$booked = $this->wcefr_call->call( 'post', '/v2/invoices/booked', array( 'id' => $output->id ) );

							$data = $booked->displayInvoiceNumber;

						}

						if ( $this->send_invoices ) {

							\WC_Emails::instance();

							/* Send the WC completed order email */
							$email_oc = new WC_Email_Customer_Completed_Order();
							$email_oc->trigger( $order_id );

						}
					}
				}

				/*Log the error*/
				if ( isset( $output->errorCode ) && isset( $output->message ) ) {

					error_log( 'WCEFR ERROR | Order ID ' . $order_id . ' | ' . $output->message );
					error_log( 'ERROR DETAILS: ' . print_r( $output, true ) );

				}
			}
		} else {

			/*If the invoice is on Reviso, update the db (useful for bulk orders export)*/
			if ( isset( $invoice_exists['number'] ) ) {

				update_post_meta( $order_id, 'wcefr-invoice', $invoice_exists['number'] );

			}
		}

	}


	/**
	 * Sanitize every single array element
	 *
	 * @param  array $array the array to sanitize.
	 * @return array        the sanitized array.
	 */
	public function sanitize_array( $array ) {

		$output = array();

		if ( is_array( $array ) && ! empty( $array ) ) {

			foreach ( $array as $key => $value ) {

				$output[ $key ] = sanitize_text_field( wp_unslash( $value ) );

			}
		}

		return $output;

	}


	/**
	 * Enqueue the single async action with Action Scheduler
	 *
	 * @param int $order_id the WC order ID.
	 *
	 * @return void
	 */
	public function single_order_async_action( $order_id ) {

		as_enqueue_async_action(
			'wcefr_export_single_order_event',
			array(
				'order_id' => $order_id,
			),
			'wcefr_export_single_order'
		);

	}


	/**
	 * Export WC orders to Reviso
	 */
	public function export_orders() {

		if ( isset( $_POST['wcefr-export-orders-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-export-orders-nonce'] ) ), 'wcefr-export-orders' ) ) {

			$statuses = isset( $_POST['statuses'] ) ? $this->sanitize_array( $_POST['statuses'] ) : array( 'any' );

			$response = array();

			$args = array(
				'post_type'      => 'shop_order',
				'posts_per_page' => -1,
			);

			/*Modify the query with the orders statuses choosed by the admin*/
			if ( is_array( $statuses ) && ! empty( $statuses ) ) {

				$args['post_status'] = $statuses;

				/*Update the db*/
				update_option( 'wcefr-orders-statuses', $statuses );

			}

			$posts = get_posts( $args );

			$n = 0;

			if ( $posts ) {

				foreach ( $posts as $post ) {

					$n++;

					/*Cron event*/
					$this->single_order_async_action( $post->ID );

				}
			}

			$response[] = array(
				'ok',
				/* translators: users count */
				esc_html( sprintf( __( '%d order(s) export process has begun', 'wc-exporter-for-reviso' ), $n ) ),
			);

			echo wp_json_encode( $response );

		}

		exit;

	}


	/**
	 * Delete the single order from Reviso
	 *
	 * @param  int $order_id the order id to delete.
	 */
	public function delete_remote_single_order( $order_id ) {

		$output = $this->wcefr_call->call( 'delete', 'orders/' . $order_id );

		/*Log the error*/
		if ( isset( $output->errorCode ) && isset( $output->developerHint ) && isset( $output->message ) ) {

			error_log( 'WCEFR ERROR | Order ID ' . $order_id . ' | ' . $output->message . ' | ' . $output->developerHint );

		}

	}


	/**
	 * Delete orders in reviso
	 *
	 * @param  int $id the order id for a specific order.
	 */
	public function delete_remote_orders( $id = null ) {

		if ( $id ) {

			$this->wcefr_call->call( 'delete', 'orders/' . $id );

		} else {

			$response = array();

			$orders = $this->get_remote_orders();

			if ( isset( $orders->collection ) && count( $orders->collection ) > 0 ) {

				$n = 0;

				foreach ( $orders->collection as $order ) {

					$n++;

					/*Cron event*/
					as_enqueue_async_action(
						'wcefr_delete_remote_single_order_event',
						array(
							'order_id' => $order->id,
						),
						'wcefr_delete_remote_single_order'
					);

				}

				$response[] = array(
					'ok',
					/* translators: users count */
					esc_html( sprintf( __( '%d order(s) delete process has begun', 'wc-exporter-for-reviso' ), $n ) ),
				);

				echo wp_json_encode( $response );

			} else {

				$response[] = array(
					'error',
					esc_html( __( 'ERROR! There are not orders to delete', 'wc-exporter-for-reviso' ) ),
				);

				echo wp_json_encode( $response );

			}

			exit;

		}

	}


	/**
	 * Create a new Reviso invoice and delete the relative remote order if exists
	 *
	 * @param  int $order_id the wc order id.
	 */
	public function create_single_invoice( $order_id ) {

		/*Create invoice*/
		$this->export_single_order( $order_id, true );

	}

}
new WCEFR_Orders( true );

