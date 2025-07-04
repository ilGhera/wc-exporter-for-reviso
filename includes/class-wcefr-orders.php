<?php
/**
 * Export orders to reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 *
 * @since 1.2.0
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * WCEFR_Orders
 */
class WCEFR_Orders {

	/**
	 * Export orders to reviso
	 *
	 * @var boolean
	 */
	public $export_orders = false;

	/**
	 * Create invoices
	 *
	 * @var boolean
	 */
	public $create_invoices = false;

	/**
	 * Issue invoices
	 *
	 * @var boolean
	 */
	public $issue_invoices = false;

	/**
	 * Send invoices
	 *
	 * @var boolean
	 */
	public $send_invoices = false;

	/**
	 * Book invoices
	 *
	 * @var boolean
	 */
	public $book_invoices = false;

	/**
	 * Number series prefix
	 *
	 * @var string
	 */
	public $number_series_prefix = '';

	/**
	 * Number series prefix receipts
	 *
	 * @var string
	 */
	public $number_series_prefix_receipts = '';

	/**
	 * WCEFR_Call
	 *
	 * @var WCEFR_Call
	 */
	public $wcefr_call;

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$this->number_series_prefix          = get_option( 'wcefr-number-series-prefix' );
			$this->number_series_prefix_receipts = get_option( 'wcefr-number-series-receipts-prefix' );
            $this->init();

			/* Actions */
			add_action( 'wp_ajax_wcefr-export-orders', array( $this, 'export_orders' ) );
			add_action( 'wp_ajax_wcefr-delete-remote-orders', array( $this, 'delete_remote_orders' ) );
			add_action( 'wcefr_export_single_order_event', array( $this, 'export_single_order' ), 10, 1 );
			add_action( 'wcefr_delete_remote_single_order_event', array( $this, 'delete_remote_single_order' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'invoice_column_style' ) );
		}

		$this->wcefr_call = new WCEFR_Call();
	}

	/**
	 * Executed in init
	 *
	 * @return void
	 */
	public function init() {

         /**
         * Check if the OrderUtil class exists for compatibility with older WC versions.
         * If OrderUtil does not exist, HPOS is not supported or very old, so legacy hooks are used.
         */
        if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

            /* HPOS is active */
            add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'wc_columns_head' ) );
            add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'wc_columns_content' ), 10, 2 );

        } else {

            /* HPOS is not active */
            add_filter( 'manage_edit-shop_order_columns', array( $this, 'wc_columns_head' ) );
            add_action( 'manage_shop_order_posts_custom_column', array( $this, 'wc_columns_content_legacy' ), 10, 2 );
        }
	}

    /**
     * Add style to the Invoice column in the orders index.
     * Adapts CSS selector for HPOS compatibility.
     *
     * @return void
     */
    public function invoice_column_style() {

        /* Initialize CSS variable. */
        $css = '';

        /* Check if OrderUtil class exists and HPOS is enabled. */
        if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

            /* CSS for HPOS enabled environment. */
            $css  = '.wc-orders-list-table-shop_order #order_invoice.column-order_invoice, '; /* Header cell */
            $css .= '.wc-orders-list-table-shop_order .type-shop_order .column-order_invoice {'; /* Body cell */
            $css .= 'width: 5%;';
            $css .= 'text-align: center;';
            $css .= '}';

        } else {

            /* CSS for legacy (non-HPOS) environment. */
            $css = '.post-type-shop_order .wp-list-table .column-order_invoice { width: 5%; text-align: center; }';
        }

        /* Enqueue the inline style. */
        if ( ! empty( $css ) ) {

            /* wp_add_inline_style( 'woocommerce_admin_styles', $css ); */
            wp_add_inline_style( 'wcefr-style', $css );
        }
    }

	/**
	 * Add the title to the Invoice column
	 *
	 * @param  array $defaults the wc column heads.
	 *
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
	 * @param  int    $order_id the WC order.
	 *
	 * @return mixed
	 */
	public function wc_columns_content( $column, $order ) {

		if ( 'order_invoice' === $column ) {

            $invoice_number = $order->get_meta( 'wcefr-invoice', true );

			if ( $invoice_number ) {

				$icon = WCEFR_URI . 'images/pdf.png';

				echo '<a href="?wcefr-preview=true&order-id=' . esc_attr( $order->get_id() ) . '" target="_blank" title="' . esc_attr( $invoice_number ) . '"><img src="' . esc_url( $icon ) . '"></a>';

			} else {

				$icon      = WCEFR_URI . 'images/pdf-black.png';
				$scheduled = as_has_scheduled_action(
					'wcefr_export_single_order_event',
					array(
						'order_id' => $order->get_id(),
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
	 * Set the preview invoice button in legacy mode
	 *
	 * @param  string $column   the WC order index column.
	 * @param  int    $order_id the WC order id.
	 *
	 * @return mixed
	 */
	public function wc_columns_content_legacy( $column, $order_id ) {

        $order = wc_get_order( $order_id );

		if ( 'order_invoice' === $column ) {

            $invoice_number = $order->get_meta( 'wcefr-invoice', true );

			if ( $invoice_number ) {

				$icon = WCEFR_URI . 'images/pdf.png';

				echo '<a href="?wcefr-preview=true&order-id=' . esc_attr( $order_id ) . '" target="_blank" title="' . esc_attr( $invoice_number ) . '"><img src="' . esc_url( $icon ) . '"></a>';

			} else {

				$icon      = WCEFR_URI . 'images/pdf-black.png';
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
	 *
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

				$enabled_gateways[] = $gateway->id;
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
	 *
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
	 *
	 * @return float the discount percentage
	 */
	private function get_order_discount_percentage( $order ) {

		$net_total = number_format(
			(float) $order->get_total() -
			$order->get_total_tax() -
			$order->get_total_shipping() +
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
                $vat_rate  = 0.0;

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

                        $specific_dist = $order->get_meta( 'wcefr-departmental-distribution', true );
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
	 *
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
	 *
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
	 *
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
	 *
	 * @return int the vatZoneNumber
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
	 *
	 * @return mixed
	 */
	public function document_exists( $order_id, $invoice = false, $invoice_details = false ) {

		$filter    = '?filter=notes.text1$eq:WC-Order-' . $order_id;
		$responses = array();

		if ( $invoice ) {

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
		$pa_code                = $order->get_meta( '_billing_wcefr_pa_code', true ); 
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

		return $output;
	}

	/**
	 * Export the single WC order to Reviso
	 *
	 * @param  int  $order_id the order id.
	 * @param  bool $invoice export to Reviso as an invoice.
	 *
	 * @return void
	 */
	public function export_single_order( $order_id, $invoice = false ) {

		$order          = new WC_Order( $order_id );
		$order_exists   = $this->document_exists( $order_id );
		$invoice_exists = $this->document_exists( $order_id, true, true );

		if ( ! $order_exists && ! isset( $invoice_exists['id'] ) ) {

			$args            = $this->prepare_order_data( $order );
			$order_completed = 'completed' === $order->get_status() ? true : false;
			$invoice         = $order_completed ? $order_completed : $invoice;

			if ( $args ) {
				$endpoint = $invoice ? '/v2/invoices/drafts/' : 'orders';

				$output = $this->wcefr_call->call( 'post', $endpoint, $args );

				/*An invoice for this order is ready on Reviso*/
				if ( $invoice && isset( $output->id ) ) {

                    $order->update_meta_data( 'wcefr-invoice', $output->id );
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

                $order->update_meta_data( 'wcefr-invoice', $invoice_exists['number'] );
			}
		}
	}

	/**
	 * Sanitize every single array element
	 *
	 * @param  array $array the array to sanitize.
	 *
	 * @return array the sanitized array.
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
     *
     * @return void
     */
    public function export_orders() {

        if ( isset( $_POST['wcefr-export-orders-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-export-orders-nonce'] ) ), 'wcefr-export-orders' ) ) {

            $statuses = isset( $_POST['statuses'] ) ? $this->sanitize_array( $_POST['statuses'] ) : array( 'any' );
            $response = array();
            $order_ids = array(); /* Initialize an array to store order IDs. */

            /* Update the database option. */
            update_option( 'wcefr-orders-statuses', $statuses );

            /* Conditionally retrieve orders based on HPOS status. */
            if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

                /* HPOS is enabled. Use wc_get_orders(). */
                $args = array(
                    'limit'   => -1, /* Retrieve all matching orders. */
                    'orderby' => 'id',
                    'order'   => 'ASC',
                );

                /* Filter orders by status. */
                if ( ! empty( $statuses ) && ! in_array( 'any', $statuses, true ) ) {

                    $args['status'] = $statuses;
                }

                $orders_query = new WC_Order_Query( $args ); /* Use WC_Order_Query for better control. */
                $orders = $orders_query->get_orders();

                foreach ( $orders as $order ) {

                    if ( $order instanceof WC_Order ) {

                        $order_ids[] = $order->get_id(); /* Store the order ID. */
                    }
                }

            } else {

                /* HPOS is not enabled or not available. Fallback to get_posts(). */
                $args = array(
                    'post_type'      => 'shop_order',
                    'posts_per_page' => -1,
                );

                /* Filter orders by status. */
                if ( empty( $statuses ) || in_array( 'any', $statuses, true ) ) {

                    $args['post_status'] = 'any'; /* 'any' for get_posts() means all statuses. */

                } else {

                    $args['post_status'] = $statuses; /* Specific statuses for get_posts(). */
                }

                $posts = get_posts( $args );

                foreach ( $posts as $post ) {

                    $order_ids[] = $post->ID; /* Store the post ID (which is the order ID in legacy mode). */
                }
            }

            $n = 0; /* Counter for processed orders. */

            if ( ! empty( $order_ids ) ) {

                foreach ( $order_ids as $order_id ) {

                    $n++;
                    /* Cron event for single order export. */
                    $this->single_order_async_action( $order_id );
                }
            }

            $response[] = array(
                'ok',
                /* translators: %d: number of orders */
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
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function delete_remote_orders( $id = null ) {

		if ( $id ) {

			$this->wcefr_call->call( 'delete', 'orders/' . $id );

		} else {

			$response = array();
			$orders   = $this->get_remote_orders();

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
	 *
	 * @return void
	 */
	public function create_single_invoice( $order_id ) {

		/*Create invoice*/
		$this->export_single_order( $order_id, true );
	}
}

new WCEFR_Orders( true );

