<?php
/**
 * Export products to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCEFR_Products
 */
class WCEFR_Products {

	/**
	 * WCEFR_Call
	 *
	 * @var WCEFR_Call
	 */
	private $wcefr_call;

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			/* Hooks */
			add_action( 'wp_ajax_wcefr-export-products', array( $this, 'export_products' ) );
			add_action( 'wp_ajax_wcefr-delete-remote-products', array( $this, 'delete_remote_products' ) );
			add_action( 'wcefr_export_single_product_event', array( $this, 'export_single_product' ) );
			add_action( 'wcefr_delete_remote_single_product_event', array( $this, 'delete_remote_single_product' ) );
		}

		$this->wcefr_call = new WCEFR_Call();
	}

	/**
	 * Check if the inventory module is active
	 *
	 * @return bool
	 */
	private function inventory_module() {

		$output    = 'no';
		$transient = get_transient( 'wcefr-inventory-module' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'self' );

			if ( is_object( $response ) && isset( $response->modules ) ) {

				if ( is_array( $response->modules ) ) {

					foreach ( $response->modules as $module ) {

						if ( 'Lager' === $module->name ) {

							$output = 'yes';

							continue;
						}
					}

					set_transient( 'wcefr-inventory-module', $output, DAY_IN_SECONDS );
				}
			}
		}

		return 'yes' === $output ? true : false;
	}

	/**
	 * Check if the dimension module is active
	 *
	 * @return bool
	 */
	public function dimension_module() {

		$output    = 'no';
		$transient = get_transient( 'wcefr-dimension-module' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'self' );

			if ( is_object( $response ) && isset( $response->modules ) ) {

				if ( is_array( $response->modules ) ) {

					foreach ( $response->modules as $module ) {

						if ( 0 === strpos( $module->name, 'Dimension' ) ) {

							$output = 'yes';

							continue;
						}
					}

					set_transient( 'wcefr-dimension-module', $output, DAY_IN_SECONDS );
				}
			}
		}

		return 'yes' === $output ? true : false;
	}

	/**
	 * Get all the products from Reviso
	 *
	 * @return array
	 */
	private function get_remote_products() {

		$output = $this->wcefr_call->call( 'get', 'products?pagesize=1000' );

		$results = isset( $output->pagination->results ) ? $output->pagination->results : '';

		if ( 1000 < $results ) {

			$limit = $results / 1000;

			for ( $i = 1; $i < $limit; $i++ ) {

				$get_products = $this->wcefr_call->call( 'get', 'products?skippages=' . $i . '&pagesize=1000' );

				if ( isset( $get_products->collection ) && ! empty( $get_products->collection ) ) {

					$output->collection = array_merge( $output->collection, $get_products->collection );

				} else {

					continue;
				}
			}
		}

		return $output;
	}

	/**
	 * Check if a specific product exists in Reviso
	 *
	 * @param  string $sku_ready the WC product sku already formatted for Reviso endpoint.
	 *
	 * @return bool
	 */
	private function product_exists( $sku_ready ) {

		$output = true;

		$response = $this->wcefr_call->call( 'get', 'products/' . $sku_ready );

		if ( ( isset( $response->collection ) && empty( $response->collection ) ) || isset( $response->errorCode ) ) {

			$output = false;
		}

		return $output;
	}

	/**
	 * Get alle the remote departmental distributions
	 *
	 * @return array
	 */
	public function get_remote_departmental_distributions() {

		$transient = get_transient( 'wcefr-departmental-distribution' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'departmental-distributions' );

			if ( ( isset( $response->collection ) && empty( $response->collection ) ) || isset( $response->errorCode ) ) {

				$output = false;

			} else {

				set_transient( 'wcefr-departmental-distribution', $response->collection, DAY_IN_SECONDS );

				$output = $response->collection;
			}
		}

		return $output;
	}

	/**
	 * Get WC tax class details
	 *
	 * @param  string $tax_rate_class set for a specific tax rate class.
	 * @param  string $tax_rate       set for a specific tax rate class.
	 *
	 * @return object
	 */
	private function get_wc_tax_class( $tax_rate_class = 'all', $tax_rate = null ) {

		global $wpdb;

		$tax_rate_class = 'standard' === $tax_rate_class ? null : $tax_rate_class;

		if ( null === $tax_rate_class && $tax_rate ) {

			$where = ' WHERE tax_rate = ' . number_format( $tax_rate, 4 );

		} else {

			$where = 'all' !== $tax_rate_class ? " WHERE tax_rate_class = '$tax_rate_class'" : '';
		}

		$query = 'SELECT * FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . $where;

		$results = $wpdb->get_results( $query );

		if ( $results && isset( $results[0] ) ) {

			return $results[0];
		}
	}

	/**
	 * Get the standard tax rate
	 *
	 * @return int
	 */
	private function get_standard_rate() {

		$result = $this->get_wc_tax_class( 'standard' );

		if ( isset( $result->tax_rate ) ) {

			return intval( $result->tax_rate );

		} else {

			return 99;
		}
	}

	/**
	 * Get details for the new vat account
	 *
	 * @param string $data the vat account data to return.
	 *
	 * @return int
	 */
	public function get_remote_vat_account_info( $data ) {

		$response = $this->wcefr_call->call( 'get', 'vat-accounts?filter=vatType.vatTypeNumber$eq:1' );

		if ( isset( $response->collection[0] ) && ! empty( $response->collection[0] ) ) {

			if ( 'account' === $data && isset( $response->collection[0]->account->accountNumber ) ) {

				return $response->collection[0]->account->accountNumber;

			} elseif ( 'vatReportSetup' === $data && isset( $response->collection[0]->vatReportSetup->vatReportSetupNumber ) ) {

				return $response->collection[0]->vatReportSetup->vatReportSetupNumber;
			}
		}
	}

	/**
	 * Add a new vat account to Reviso
	 *
	 * @param  int $vat_rate the vat rate.
	 *
	 * @return int the vatCode
	 */
	public function add_remote_vat_account( $vat_rate ) {

		$args = array(
			'account'        => array(
				'accountNumber' => $this->get_remote_vat_account_info( 'account' ),
			),
			'vatType'        => array(
				'name'          => __( 'Sales VAT', 'wc-exporter-for-reviso' ),
				'vatTypeNumber' => 1,
			),
			/* Translators: the VAT rate */
			'name'           => sprintf( __( '%d%% VAT purchases', 'wc-exporter-for-reviso' ), $vat_rate ),
			'ratePercentage' => $vat_rate,
			'vatReportSetup' => array(
				'vatReportSetupNumber' => $this->get_remote_vat_account_info( 'vatReportSetup' ), // For reduced rates.
			),
			'vatCode'        => 'VAT' . $vat_rate,
		);

		$vat_account = $this->wcefr_call->call( 'post', 'vat-accounts', $args );

		if ( isset( $vat_account->vatCode ) ) {

			return $vat_account->vatCode;
		}
	}

	/**
	 * Get a specific vat account from Reviso or create it if necessary
	 *
	 * @param  int    $vat_rate the vat rate.
	 * @param  string $vat_code the vat code.
	 *
	 * @return array  vat accounts available in Reviso.
	 */
	public function get_remote_vat_code( $vat_rate, $vat_code = null ) {

		$output = null;

		if ( $vat_code ) {

			$transient_name = 'wcefr-vat-code-' . $vat_code;
			$end            = sprintf( '?filter=vatType.vatTypeNumber$eq:1$and:vatCode$eq:%s', $vat_code );

		} else {

			$transient_name = 'wcefr-vat-rate-' . $vat_rate;
			$end            = sprintf( '?filter=vatType.vatTypeNumber$eq:1$and:ratePercentage$eq:%s', ceil( $vat_rate ) );

		}

		/* Get transient */
		$transient = get_transient( $transient_name );

		if ( $transient ) {

			$response = $transient;

		} else {

			$response = $this->wcefr_call->call( 'get', 'vat-accounts' . $end );
		}

		if ( isset( $response->collection ) && ! empty( $response->collection ) ) {

			if ( ! $transient ) {

				/* Set the transient */
				set_transient( $transient_name, $response, DAY_IN_SECONDS );
			}

			$output = $response->collection[0]->vatCode;

		} else {

			/* Delete transient */
			delete_transient( $transient_name );

			$output = $this->add_remote_vat_account( $vat_rate );
		}

		return $output;
	}

	/**
	 * Add an account in Reviso
	 *
	 * @param  int $vat_rate used to create the account number.
	 *
	 * @return int the number of the account created
	 */
	private function add_remote_account( $vat_rate ) {

		/*Get the remote vat code*/
		$vat_code = $this->get_remote_vat_code( $vat_rate ); // temp.

		$account_number = $vat_rate < 10 ? 580550 . $vat_rate : 58055 . $vat_rate;

		$args = array(
			'accountCategory' => array(
				'description'           => 'Sales of products',
				'accountCategoryNumber' => 48,
			),
			'accountType'     => 'profitAndLoss',
			'balance'         => 0,
			'debitCredit'     => 'credit',
			'accountNumber'   => $account_number,
			/* Translators: the vat rate */
			'name'            => sprintf( __( 'Sale of goods VAT %d', 'wc-exporter-for-reviso' ), $vat_rate ),
			'vatAccount'      => array(
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
	 *
	 * @return bool
	 */
	private function get_remote_account_number( $vat_rate ) {

		$account_number = $vat_rate < 10 ? 580550 . $vat_rate : 58055 . $vat_rate;

		$account = $this->wcefr_call->call( 'get', 'accounts/' . $account_number );

		if ( ! isset( $account->accountNumber ) || intval( $account->accountNumber ) !== $account_number ) {

			$this->add_remote_account( $vat_rate );
		}

		return $account_number;
	}

	/**
	 * Add a product group in Reviso
	 *
	 * @param  int    $vat_rate           the product group number to search.
	 * @param  string $product_group_name the remote product group name.
	 *
	 * @return object the remote product group
	 */
	private function add_remote_product_group( $vat_rate, $product_group_name ) {

		$product_group_number = 99 !== intval( $vat_rate ) ? ( intval( 100 + $vat_rate ) ) : $vat_rate;
		$account_number       = $this->get_remote_account_number( $vat_rate );

		/* Translators: the vat rate */
		$name = $vat_rate == $product_group_name ? sprintf( __( '%d%% VAT', 'wc-exporter-for-reviso' ), $vat_rate ) : $product_group_name;

		$args = array(
			'productGroupNumber' => $product_group_number,
			'name'               => $name,
			'salesAccountsList'  => array(

				0 => array(
					'salesAccount' => array(
						'accountNumber' => $account_number,
					),
					'vatZone'      => array(
						'vatZoneNumber' => 1,
					),
				),
			),
		);

		/* Only with inventory module enabled */
		if ( $this->inventory_module() ) {

			$args['inventory'] = array(
				'purchaseAccount' => array(
					'accountNumber' => 6625005,
				),
			);
		}

		$output = $this->wcefr_call->call( 'post', 'product-groups/', $args );

		return $output;
	}

	/**
	 * Get the number of a specific product group from Reviso
	 *
	 * @param  int  $vat_rate the product group number to search.
	 * @param  bool $standard true for standard VAT rate product group.
	 *
	 * @return int
	 */
	private function get_remote_product_group( $vat_rate, $standard = false ) {

		$output               = null;
		$product_group_number = 99 !== intval( $vat_rate ) ? ( intval( 100 + $vat_rate ) ) : $vat_rate;
		$remote_product_group = $this->wcefr_call->call( 'get', 'product-groups/' . $product_group_number );

		if ( isset( $remote_product_group->productGroupNumber ) ) {

			$output = $remote_product_group->productGroupNumber;

		} else {

			if ( 99 === intval( $product_group_number ) ) {

				$wc_tax        = null;
				$tax_rate_name = __( 'No VAT', 'wc-exporter-for-reviso' );

			} else {

				$wc_tax        = $standard ? $this->get_wc_tax_class( 'standard' ) : $this->get_wc_tax_class( null, $vat_rate );
				$tax_rate_name = isset( $wc_tax->tax_rate_name ) ? $wc_tax->tax_rate_name : '';
			}

			$remote_product_group = $this->add_remote_product_group( $vat_rate, $tax_rate_name );

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
	 * @param  bool   $taxable   if not the Reviso product group 99 will be used.
	 * @param  string $tax_class the WC tax class assigned to the product.
	 *
	 * @return int             the product group number
	 */
	private function get_product_group( $taxable, $tax_class = null ) {

		$standard = false;
		$output   = null;

		if ( ! $taxable ) {

			$tax_class = 99; // 5;
		}

		if ( null === $tax_class ) {

			$tax_class = $this->get_standard_rate();
			$standard  = true;

		} elseif ( ! is_numeric( $tax_class ) ) {

			$tax_class_info = $this->get_wc_tax_class( $tax_class );

			if ( isset( $tax_class_info->tax_rate ) ) {

				$tax_class = intval( $tax_class_info->tax_rate );

			} else {

				$tax_class = 99;
			}
		}

		$output = $this->get_remote_product_group( $tax_class, $standard );

		return $output;
	}

	/**
	 * Prepare the sku to get the right product endpoint
	 *
	 * @param  string $sku the product sku.
	 * @return string
	 */
	private function format_sku( $sku ) {

		$output = str_replace( '_', '_8_', $sku );
		$output = str_replace( '<', '_0_', $output );
		$output = str_replace( '>', '_1_', $output );
		$output = str_replace( '*', '_2_', $output );
		$output = str_replace( '%', '_3_', $output );
		$output = str_replace( ':', '_4_', $output );
		$output = str_replace( '&', '_5_', $output );
		$output = str_replace( '/', '_6_', $output );
		$output = str_replace( '\\', '_7_', $output );
		$output = str_replace( ' ', '_9_', $output );
		$output = str_replace( '?', '_10_', $output );
		$output = str_replace( '.', '_11_', $output );
		$output = str_replace( '#', '_12_', $output );
		$output = str_replace( '+', '_13_', $output );

		return $output;
	}

	/**
	 * Prepare the single product data for Reviso
	 *
	 * @param  object $product the WC product.
	 *
	 * @return array
	 */
	private function prepare_product_data( $product ) {

		$sku = $product->get_sku() ? $product->get_sku() : ( 'wc-' . $product->get_id() );

		/*Departmental distribution*/
        $specific_dist = $product->get_meta( 'wcefr-departmental-distribution', true );
		$generic_dist  = get_option( 'wcefr-departmental-distribution' );
		$dist          = 0 !== intval( $specific_dist ) ? $specific_dist : $generic_dist;

		/*Sale price*/
		$sale_price  = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
        $description = mb_convert_encoding( $product->get_description(), 'UTF-8', 'ISO-8859-1' );

		/*Get the product volume if available*/
		$volume = 0;
		$width  = $product->get_width();

		if ( $width ) {

			$height = $product->get_height();
			$length = $product->get_length();

			if ( $width && $height && $length ) {

				$volume = $width * $height * $length;
			}
		}

		$output = array(
			'productNumber'    => avoid_length_exceed( $sku, 25 ),
			'barred'           => false,
			'name'             => avoid_length_exceed( $product->get_name(), 300 ),
			'description'      => avoid_length_exceed( $description, 500 ),
			'salesPrice'       => floatval( wc_format_decimal( $sale_price, 2 ) ),
			'productGroup'     => array(
				'productGroupNumber' => $this->get_product_group( $product->is_taxable(), $product->get_tax_class() ),
			),
			'recommendedPrice' => floatval( wc_format_decimal( $product->get_regular_price(), 2 ) ),
			'unit'             => array(
				'unitNumber' => 1,
			),
		);

		/* Departmental distribution */
		if ( $this->dimension_module() && $dist ) {
			$output['departmentalDistribution'] = array(
				'departmentalDistributionNumber' => $dist,
			);
		}

		/* Only with inventory module enabled */
		if ( $this->inventory_module() ) {

			$output['inventory'] = array(
				'packageVolume' => $volume,
			);
		}

		return $output;
	}

	/**
	 * Export single product to Reviso
	 *
	 * @param  int $post_id the post ID.
	 *
	 * @return void
	 */
	public function export_single_product( $post_id ) {

		if ( wp_is_post_autosave( $post_id ) ) {

			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! is_object( $product ) ) {

			return;

		} elseif ( $product ) {

			$sku    = $product->get_sku() ? $product->get_sku() : ( 'wc-' . $product->get_id() );
			$end    = $this->format_sku( $sku );
			$exists = $this->product_exists( $end );

			if ( $exists && 'trash' === $product->get_status() ) {

				/* Delete remote product */
				$this->delete_remote_single_product( $end );

			} else {

				$args = $this->prepare_product_data( $product );

				if ( $args ) {

					if ( $exists ) {

						$output = $this->wcefr_call->call( 'put', 'products/' . $end, $args ); // temp.

					} else {

						$output = $this->wcefr_call->call( 'post', 'products', $args );
					}

					/*Log the error*/
					if ( ( isset( $output->errorCode ) || isset( $output->developerHint ) ) && isset( $output->message ) ) {

						error_log( 'WCEFR ERROR | Reviso product ' . $post_id . ' | ' . $output->message );
					}
				}
			}
		}
	}

	/**
	 * Export WC product to Reviso
	 *
	 * @return void
	 */
	public function export_products() {

		if ( isset( $_POST['wcefr-export-products-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-export-products-nonce'] ) ), 'wcefr-export-products' ) ) {

			$class    = new WCEFR_Orders();
			$terms    = isset( $_POST['terms'] ) ? $class->sanitize_array( $_POST['terms'] ) : '';
			$dist     = isset( $_POST['dist'] ) ? sanitize_text_field( wp_unslash( $_POST['dist'] ) ) : '';
			$response = array();

			$args = array(
				'status' => 'publish',
				'limit'  => -1,
			);

			/*Modify the query based on the admin categories selection */
			if ( is_array( $terms ) && ! empty( $terms ) ) {

                $args['product_cat_id'] = $terms;
			}

            $products = wc_get_products( $args );

			/*Update the db*/
			update_option( 'wcefr-products-categories', $terms );
			update_option( 'wcefr-departmental-distribution', $dist );

			if ( $products ) {

				$n = 0;

				foreach ( $products as $product ) {

					$n++;

					if ( $product->is_type( 'variable' ) ) {

						$variations = $product->get_children();

						if ( is_array( $variations ) ) {

							foreach ( $variations as $var_id ) {

								/*Schedule single event*/
								as_enqueue_async_action(
									'wcefr_export_single_product_event',
									array(
										'product_id' => $var_id,
									),
									'wcefr_export_single_product'
								);
							}
						}

					} else {

						/*Schedule single event*/
						as_enqueue_async_action(
							'wcefr_export_single_product_event',
							array(
								'product_id' => $product->get_id(),
							),
							'wcefr_export_single_product'
						);
					}
				}

				$response[] = array(
					'ok',
					/* translators: the products count */
					esc_html( sprintf( __( '%d product(s) export process has begun', 'wc-exporter-for-reviso' ), $n ) ),
				);

			} else {

				$response[] = array(
					'error',
					esc_html( __( 'ERROR! There are not products to export', 'wc-exporter-for-reviso' ) ),
				);
			}

			echo wp_json_encode( $response );
		}

		exit;
	}

	/**
	 * Delete a single product on Reviso
	 *
	 * @param  int $product_number the product to delete in Reviso.
	 *
	 * @return void
	 */
	public function delete_remote_single_product( $product_number ) {

		$end    = $this->format_sku( $product_number );
		$output = $this->wcefr_call->call( 'delete', 'products/' . $end );

		/*Log the error*/
		if ( ( isset( $output->errorCode ) || isset( $output->developerHint ) ) && isset( $output->message ) ) {

			error_log( 'WCEFR ERROR | Reviso delete product ' . $product_number . ' | ' . $output->message );
		}
	}

	/**
	 * Delete all the products in Reviso
	 *
	 * @return void
	 */
	public function delete_remote_products() {

		$products = $this->get_remote_products();

		if ( isset( $products->collection ) && count( $products->collection ) > 0 ) {

			$n        = 0;
			$response = array();

			foreach ( $products->collection as $product ) {

				$n++;

				/*Schedule single event*/
				as_enqueue_async_action(
					'wcefr_delete_remote_single_product_event',
					array(
						'product_number' => $product->productNumber,
					),
					'wcefr_delete_remote_single_product'
				);
			}

			$response[] = array(
				'ok',
				/* translators: the products count */
				esc_html( sprintf( __( '%d product(s) delete process has begun', 'wc-exporter-for-reviso' ), $n ) ),
			);

		} else {

			$response[] = array(
				'error',
				esc_html( __( 'ERROR! There are not products to delete', 'wc-exporter-for-reviso' ) ),
			);
		}

		echo wp_json_encode( $response );

		exit;
	}
}

new WCEFR_Products( true );

