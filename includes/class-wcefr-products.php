<?php
/**
 * Export products to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */
class WCEFR_Products {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$settings = new WCEFR_Settings();

			if ( $settings->connected ) {

				add_action( 'wp_ajax_export-products', array( $this, 'export_products' ) );
				add_action( 'wp_ajax_delete-remote-products', array( $this, 'delete_remote_products' ) );
				add_action( 'wcefr_export_single_product_event', array( $this, 'export_single_product' ), 10, 2 );
				add_action( 'wcefr_delete_remote_single_product_event', array( $this, 'delete_remote_single_product' ), 10, 2 );

			}

		}

		$this->wcefr_call = new WCEFR_Call();

	}


	/**
	 * Get all the products from Reviso
	 *
	 * @return array
	 */
	private function get_remote_products() {

		$output = null;

		for ($i=0; $i < 5 ; $i++) { 
			
			error_log( 'NUMERO: ' . $i );

			$get_products = $this->wcefr_call->call( 'get', 'products?skippages=' . $i . '&pagesize=10' );
			
			if ( isset( $get_products->collection ) && ! empty( $get_products->collection ) ) {

				if ( isset( $output->collection ) ) {
	
					$output->collection = array_merge( $output->collection, $get_products->collection );
	
				} else {

					$output = $get_products;

					error_log( 'OUTPUT: ' . print_r( $output, true ) );

				}

			} else {

				continue;

			}
		
		}

		error_log( 'PRODOTTI: ' . print_r( $output, true ) );

		return $output;
	}


	/**
	 * Check if a specific product exists in Reviso
	 *
	 * @param  string $sku the WC product sku.
	 * @return bool
	 */
	private function product_exists( $sku ) {

		$output = true;

		$response = $this->wcefr_call->call( 'get', 'products/' . $sku );

		if ( isset( $response->errorCode ) ) {
			$output = false;
		}

		return $output;

	}


	/**
	 * Returns the product description less than 500 characters long
	 *
	 * @param  string $text the full WC product description.
	 * @return string
	 */
	private function prepare_product_description( $text ) {

		$output = $text;

		if ( strlen( $text ) > 500 ) {

			$output = substr( $text, 0, 496 ) . ' ...';

		}

		return $output;

	}


	/**
	 * Get WC tax class details
	 *
	 * @param  string $tax_rate_class set for a specific tax rate class.
	 * @return object
	 */
	private function get_wc_tax_class( $tax_rate_class = 'all' ) {

		global $wpdb;

		$where = 'all' !== $tax_rate_class ? " WHERE tax_rate_class = '$tax_rate_class'" : '';

		$query = "
			SELECT * FROM " . $wpdb->prefix . "woocommerce_tax_rates $where 
		";

		$results = $wpdb->get_results( $query );

		if ( $results && isset( $results[0] ) ) {
			return $results[0];
		}

	}


	/**
	 * Add a new vat account to Reviso
	 *
	 * @param  int $vat_rate the vat rate.
	 * @return int the vatCode
	 */
	public function add_remote_vat_account( $vat_rate ) {

		$args = array(
			'account' => array(
				'accountNumber' => 2201,
			),
			'vatType' => array(
				'name'          => 'Sales VAT',
				'vatTypeNumber' => 1,
			),
			'name' => 'Acquisti con IVA al ' . $vat_rate . '%',
			'ratePercentage' => $vat_rate,
			'vatReportSetup' => array(
				'vatReportSetupNumber' => 24, // For reduced rates.
			),
		);

		$vat_account = $this->wcefr_call->call( 'post', 'vat-accounts', $args );

		if ( isset( $vat_account->vatCode ) ) {

			return $vat_account->vatCode;

		}

	}


	/**
	 * Get a specific vat account from Reviso or create it necessary
	 *
	 * @param  int $vat_rate the vat rate.
	 * @return array  vat accounts available in Reviso.
	 */
	public function get_remote_vat_code( $vat_rate ) {

		$output = null;

		$end = '?filter=vatType.vatTypeNumber$eq:1$and:ratePercentage$eq:' . $vat_rate;

		$response = $this->wcefr_call->call( 'get', 'vat-accounts' . $end );

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

			$output = $response->collection[0]->vatCode;

		} else {

			$output = $this->add_remote_vat_account( $vat_rate );

		}

		return $output;
	}


	/**
	 * Add an account in Reviso
	 *
	 * @param  int $vat_rate used for create the account number.
	 * @return int the number of the account created
	 */
	private function add_remote_account( $vat_rate ) {

		/*Get the remote vat code*/
		$vat_code = $this->get_remote_vat_code( $vat_rate ); // temp.

		$account_number = $vat_rate < 10 ? 580550 . $vat_rate : 58055 . $vat_rate;

		$args = array(
			'accountCategory' => array(
				'description' => 'Sales of products',
				'accountCategoryNumber' => 48,
			),
			'accountType' => 'profitAndLoss',
			'balance' => 0,
			'debitCredit' => 'credit',
			'accountNumber' => $account_number,
			'name' => 'Merci c/vendite iva al ' . $vat_rate . '%',
			'vatAccount' => array(
				'vatCode' => $vat_code,
			),
		);

		$account = $this->wcefr_call->call( 'post', 'accounts/', $args );

		if ( isset( $account->accountNumber ) && $account_number === $account->accountNumber ) {

			return $account->accountNumber;

		}

	}


	/**
	 * Get a specific account in Reviso and create it if it does not exist
	 *
	 * @param  int $vat_rate used for create the account number.
	 * @return bool
	 */
	private function get_remote_account_number( $vat_rate ) {

		$account_number = $vat_rate < 10 ? 580550 . $vat_rate : 58055 . $vat_rate;

		$account = $this->wcefr_call->call( 'get', 'accounts/' . $account_number );

		if ( ! isset( $account->accountNumber ) || $account_number != $account->accountNumber ) {

			$this->add_remote_account( $vat_rate );

		}

		return $account_number;

	}


	/**
	 * Add a product group in Reviso
	 *
	 * @param  int    $product_group_number the remote product goup.
	 * @param  string $product_group_name   the remote product group name.
	 * @return object the remote product group
	 */
	private function add_remote_product_group( $product_group_number, $product_group_name ) {

		$account_number = $this->get_remote_account_number( $product_group_number );

		$name = $product_group_number === $product_group_name ? 'IVA al ' . $product_group_number . '%' : $product_group_name;

		$args = array(
			'productGroupNumber' => $product_group_number,
			'name'               => $name,
			'inventoryEnabled'   => true,
			'inventory' => array(
				'purchaseAccount' => array(
					'accountNumber' => 6625005,
				),
			),
			'salesAccountsList'  => array(

				0 => array(
					'salesAccount' => array(
						'accountNumber' => $account_number,
					),
					'vatZone' => array(
						'vatZoneNumber' => 1,
					),
				),

				1 => array(
					'salesAccount' => array(
						'accountNumber' => '5805556',
					),
					'vatZone' => array(
						'vatZoneNumber' => 2,
					),
				),

				2 => array(
					'salesAccount' => array(
						'accountNumber' => '5805550',
					),
					'vatZone' => array(
						'vatZoneNumber' => 3,
					),
				),

				3 => array(
					'salesAccount' => array(
						'accountNumber' => '5805599',
					),
					'vatZone' => array(
						'vatZoneNumber' => 4,
					),
				),

			),

		);

		$output = $this->wcefr_call->call( 'post', 'product-groups/', $args );

		return $output;

	}


	/**
	 * Get the number of a specific product group from Reviso
	 *
	 * @param  int $product_group_number the product group number to search.
	 * @return int
	 */
	private function get_remote_product_group( $product_group_number ) {

		$output = null;

		$remote_product_group = $this->wcefr_call->call( 'get', 'product-groups/' . $product_group_number );

		if ( isset( $remote_product_group->productGroupNumber ) ) {

			$output = $remote_product_group->productGroupNumber;

		} else {

			$wc_tax = $this->get_wc_tax_class( $product_group_number );

			$tax_rate_name = isset( $wc_tax->tax_rate_name ) ? $wc_tax->tax_rate_name : '';

			$remote_product_group = $this->add_remote_product_group( $product_group_number, $tax_rate_name );

			if ( isset( $remote_product_group->productGroupNumber ) ) {

				$output = $remote_product_group->productGroupNumber;

			}

		}

		return $output;

	}


	/**
	 * The Reviso product group is based on the vat class applied to the product
	 * This method get the WC tax class and passes the value to another function for searching the Reviso product group
	 * The product group will be created if necessary
	 *
	 * @param  bool $taxable   if not the Reviso product group 99 will be used.
	 * @param  int  $tax_class the WC tax class assigned to the product.
	 * @return int             the product group number
	 */
	private function get_product_group( $taxable, $tax_class ) {

		$output = null;

		if ( ! $taxable ) {

			$output = 99;

		} else {

			$tax_class = '' == $tax_class ? 22 : $tax_class;

			$output = $this->get_remote_product_group( $tax_class );

		}

		return $output;

	}


	/**
	 * Prepare the single product data for Reviso
	 *
	 * @param  object $product the WC product.
	 * @return array
	 */
	private function prepare_product_data( $product ) {

		$sale_price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
		$output = array(
			'productNumber'    => $product->get_sku(),
			'barred'           => false,
			'name'             => $product->get_name(),
			'description'      => $this->prepare_product_description( $product->get_description() ),
			'salesPrice'       => (float) number_format( $sale_price, 2 ),
			'productGroup'     => array(
				'productGroupNumber' => $this->get_product_group( $product->is_taxable(), $product->get_tax_class() ),
			),
			'recommendedPrice' => (float) number_format( $product->get_regular_price(), 2 ),
			'unit'             => array(
				'unitNumber' => 1,
			),
		);

		return $output;

	}


	/**
	 * Prepare the sku for getting the right product endpoint
	 *
	 * @param  string $sku the product sku.
	 * @return string
	 */
	private function format_sku( $sku ) {

		$output = str_replace( '/', '_6_', $sku );

		return $output;

	}


	/**
	 * Export single product to Reviso
	 *
	 * @param  int $n       the count of products exported.
	 * @param  int $post_id the product id.
	 */
	public function export_single_product( $n, $post_id ) {

		$product = wc_get_product( $post_id );
		$sku = $product->get_sku();

		/*Avoid parent product export*/
		if ( ! $product->is_type( 'variable' ) ) {

			$args = $this->prepare_product_data( $product );

			if ( $args ) {

				$end = $this->format_sku( $sku );

				if ( $this->product_exists( $end ) ) {

					$output = $this->wcefr_call->call( 'put', 'products/' . $end, $args );

				} else {

					$output = $this->wcefr_call->call( 'post', 'products', $args );

				}

				error_log( 'PRODOTTO: ' . print_r( $output, true ) ); // temp.

				/*temp*/
				if ( isset( $output->errorCode ) ) { // temp.

					$response[] = array(
						'error',
						/* translators: 1: the error message 2: the product sku */
						esc_html( sprintf( __( 'ERROR! %1$s #%2$s<br>', 'wcefr' ), $output->message, $product->get_sku() ) ),
					);

				} else {

					$response[] = array(
						'ok',
						/* translators: the products count */
						esc_html( sprintf( __( 'Exported products: %d', 'wcefr' ), $n ) ),
					);

				}

				echo json_encode( $response );

			}

		}

		exit;

	}


	/**
	 * Export WC product to Reviso
	 */
	public function export_products() {

		if ( isset( $_POST['wcefr-export-products-nonce'] ) && wp_verify_nonce( $_POST['wcefr-export-products-nonce'], 'wcefr-export-products' ) ) {

			$class = new WCEFR_Orders();

			$terms = isset( $_POST['terms'] ) ? $class->sanitize_array( $_POST['terms'] ) : '';

			$response = array();

			$args = array(
				'post_type' => array(
					'product',
					'product_variation',
				),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			/*Modify the query based on the admin categories selection */
			if ( is_array( $terms ) && ! empty( $terms ) ) {

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $terms,
					),
				);

			}

			/*Update the db*/
			update_option( 'wcefr-products-categories', $terms );

			$response = array();

			$posts = get_posts( $args );

			if ( $posts ) {

				$n = 0;

				foreach ( $posts as $post ) {

					$n++;

					/*Cron event*/
					wp_schedule_single_event(
						time() + 1,
						'wcefr_export_single_product_event',
						array(
							$n,
							$post->ID,
						)
					);

				}

				$response[] = array(
					'ok',
					/* translators: the products count */
					esc_html( sprintf( __( '%n product(s) export process has begun', 'wcefr' ), $n ) ),
				);

				echo json_encode( $response );

			}

		}

		exit;

	}


	/**
	 * Delete a single product on Reviso
	 *
	 * @param  int $n              the count of products exported.
	 * @param  int $product_number the product to delete in Reviso.
	 */
	public function delete_remote_single_product( $n, $product_number ) {

		$end = $this->format_sku( $product_number );

		$output = $this->wcefr_call->call( 'delete', 'products/' . $end );

		/*temp*/
		if ( isset( $output->errorCode ) || isset( $output->developerHint ) ) {

			$response = array(
				'error',
				/* translators: 1: the error message 2: the product number */
				esc_html( sprintf( __( 'ERROR! %1$s #%2$n<br>', 'wcefr' ), $output->message, $product_number ) ),
			);

		} else {

			$response = array(
				'ok',
				/* translators: the product count */
				esc_html( sprintf( __( 'Deleted products: %d', 'wcefr' ), $n ) ),
			);

		}

	}


	/**
	 * Delete all the products in Reviso
	 */
	public function delete_remote_products() {

		$products = $this->get_remote_products();

		if ( isset( $products->collection ) && count( $products->collection ) > 0 ) {

			$n = 0;
			$response = array();

			foreach ( $products->collection as $product ) {

				$n++;

				/*Cron event*/
				wp_schedule_single_event(
					time() + 1,
					'wcefr_delete_remote_single_product_event',
					array(
						$n,
						$product->productNumber,
					)
				);

			}

			$response[] = array(
				'ok',
				/* translators: the products count */
				esc_html( sprintf( __( '%d product(s) delete process has begun', 'wcefr' ), $n ) ),
			);

		} else {

			$response[] = array(
				'error',
				esc_html( __( 'ERROR! There are not products to delete', 'wcefr' ) ),
			);

		}

		echo json_encode( $response );

		exit;

	}

}
new WCEFR_Products( true );
