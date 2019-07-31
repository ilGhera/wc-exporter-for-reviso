<?php
/**
 * Esportazione prodotti verso Reviso
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrProducts {

	public function __construct() {

		// add_action( 'admin_init', array( $this, 'export_products' ) );
		add_action( 'wp_ajax_export-products', array( $this, 'export_products' ) );
		add_action( 'wp_ajax_delete-remote-products', array( $this, 'delete_remote_products' ) );

		$this->wcefrCall = new wcefrCall();
		// $this->get_remote_products();
		// $this->product_exists( 399 );
	}

	
	/**
	 * TEMP
	 * Restituisce i prodotti presenti in Reviso
	 * @return string risposta in json della chiamata all'endpoint
	 */
	private function get_remote_products() {

		$output = $this->wcefrCall->call( 'get', 'products?pagesize=10000'  );
		// error_log( 'Prodotti: ' . print_r( $output, true  ) );

		return $output;
	
	}


	/**
	 * Verifica la presenza di un prodotto in Reviso
	 * @param  string $sku lo sku del prodotto WooCommerce
	 * @return bool
	 */
	private function product_exists( $sku ) {
		
		$output = true;
		
		// error_log( 'EXISTS: ' . print_r( $this->wcefrCall->call( 'get', 'products/' . $sku ), true ) );

		$response = $this->wcefrCall->call( 'get', 'products/' . $sku );
		// $result   = isset( $response['body'] ) ? $response['body'] : '';

		// error_log( 'Errore: ' . print_r( $response, true ) );
		// 
		if ( isset( $response->errorCode ) ) {
			$output = false;
		}

		return $output;

	}


	/**
	 * Restituisce il testo dato con lunghezza massima 500 caratteri, come previsto da Reviso
	 * nela campo di descizione prodotto
	 * @param  string $text la descrizione completa del prodotto WooCommerce
	 * @return string
	 */
	private function prepare_product_description( $text ) {

		$output = $text;

		if( strlen( $text ) > 500 ) {
			
			$output = substr( $text, 0, 496 ) . ' ...';

		}

		return $output;

	}


	/**
	 * Prepara i dati del singolo prodotto da esporare verso Reviso
	 * @param  object $product il prodotto WooCommerce
	 * @return array
	 */
	private function prepare_product_data( $product ) {

		$sale_price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
		$output = array(
			'productNumber' => $product->get_sku(),
			// 'barCode'  	    => $product->get_sku(),
			'barred' 	    => false,
			//'costPrice'   => xxxxxxx,
			'description'   => $this->prepare_product_description( $product->get_description() ),
			// Al momento non supportato dalle API
			// 'inventory'     => array(
			// 	'available' 		   => ( float ) $product->get_stock_quantity(),
		        // 'inStock'  			   => ( float ) $product->get_stock_quantity(),
		        // 'orderedByCustomers'   => xxxxxxx,
		        // 'orderedFromSuppliers' => xxxxxxx,
		        // 'packageVolume' 	   => xxxxxxx,
			// ),
	        'unit' 		   => array(
		        'unitNumber' => 1,
			),
			// 'lastUpdated'  => $product->get_date_modified()->date,
			'name' 		   => $product->get_title(),
			'productGroup' => array(
				'productGroupNumber' => 22,
				// 'self' 			     => $product->xxxx,
			),
			'recommendedPrice' 	   => ( float ) number_format( $product->get_price(), 2 ),
			// 'recommendedCostPrice' => $product->xxxx,
			'salesPrice' 		   => ( float ) number_format( $sale_price, 2 ),

			// 'xxxxxxx' => xxxxxxx,
		);

		// error_log('Product data: ' . print_r( $output, true ) );
		// error_log('Product data: ' . json_encode( $output ) );
		return $output;

	}


	/**
	 * Prepare the sku for getting the right product endpoint
	 * @param  string $sku the product sku
	 * @return string
	 */
	private function format_sku( $sku ) {

		$output = str_replace('/', '_6_', $sku );

		return $output;

	}


	/**
	 * Esporta prodotti WooCommerce verso Reviso
	 */
	public function export_products() {

		$terms = isset( $_POST['terms'] ) ? $_POST['terms'] : '';
		
		$args = array(
			'post_type' => array(
				'product', 
				// 'product_variation',
			), 
			'post_status'=>'publish',
			'posts_per_page' => -1
		);

		/*Modifico la query con  le categorie prodotto selezionate dall'admin*/
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $terms,
				),
			);

			/*Aggiorno il dato nel db*/
			update_option( 'wcefr-products-categories', $terms );

		}

		$posts = get_posts( $args );
		
		if( $posts ) {

			$n = 0;
			$response = array();

			foreach ( $posts as $post ) {

				$n++;
				
				$product = wc_get_product( $post->ID );
				$sku = $product->get_sku();

				// error_log('Prodotto: ' . print_r( $product, true ) );

				$args = $this->prepare_product_data( $product );

				// error_log('ARGS: ' . print_r( $args, true ) );


				if ( $args ) {

					$end = $this->format_sku( $sku );

					if ( $this->product_exists( $end ) ) {

						$output = $this->wcefrCall->call( 'put', 'products/' . $end, $args );

					} else {

						$output = $this->wcefrCall->call( 'post', 'products', $args );

					}

					if ( isset( $output->errorCode ) ) {
					
						error_log( 'ATTENZIONE:' . print_r( $output, true ) );

						$response[] = array(
							'error',
							__( 'ERROR! ' . $output->message . ' #' . $product->get_sku() . '<br>', 'wcefr' ),
							// __( 'ERROR! An error occurred with the product #' . $product->get_sku() . '<br>', 'wcefr' ),
						);

					} else {

						$response[] = array(
							'ok',
							// __( 'The product #' . $product->productNumber . ' was deleted', 'wcefr' ),			
							__( 'Exported products: <span>' . $n . '</span>', 'wcefr' ),			
						);

					}
					
					echo json_encode( $response );

				}

			}
		}

		exit;

	}


	/**
	 * Cancella tutti i prodotti presenti in Reviso
	 */
	public function delete_remote_products() {

		$products = $this->get_remote_products(); 	
		if ( isset( $products->collection ) && count( $products->collection ) > 0 ) {

			$n = 0;
			$response = array();

			foreach ( $products->collection as $product ) {

				$end = $this->format_sku( $product->productNumber );
				
				$output = $this->wcefrCall->call( 'delete', 'products/' . $end );
			
				if ( isset( $output->errorCode ) || isset( $output->developerHint )) {
					
					error_log( 'ATTENZIONE:' . print_r( $output, true ) );

					$response[] = array(
						'error',
						__( 'ERROR! ' . $output->message . ' #' . $product->productNumber . '<br>', 'wcefr' ),
						// __( 'ERROR! An error occurred with the product #' . $product->productNumber . '<br>', 'wcefr' ),
					);


				} else {

					$n++;

					$response[] = array(
						'ok',
						// __( 'The product #' . $product->productNumber . ' was deleted', 'wcefr' ),			
						__( 'Deleted products: <span>' . $n . '</span>', 'wcefr' ),			
					);

				}

				// echo json_encode( $response );

			}

			// $response = array(
			// 	'ok',
			// 	__( 'The delete process is started', 'wcefr' ),
			// );

			echo json_encode( $response );

		} else {
			
			$response[] = array(
				'error',
				__( 'ERROR! There are not products to delete', 'wcefr' ),
			);

			echo json_encode( $response );

		}

		exit;

	}

}
new wcefrProducts;
