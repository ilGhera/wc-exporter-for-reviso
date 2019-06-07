<?php
/**
 * Esportazione prodotti verso Reviso
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrProducts {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'export_products' ) );
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

		$output = $this->wcefrCall->call( 'get', 'products'  );
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

		$response = json_decode( $this->wcefrCall->call( 'get', 'products/' . $sku ), true );

		// error_log( 'Errore: ' . print_r( $response, true ) );
		// 
		if ( isset( $response['errorCode'] ) ) {
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
	public function prepare_product_desciption( $text ) {

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
			'description'   => $this->prepare_product_desciption( $product->get_description() ),
			'inventory'     => array(
				'available' 		   => ( float ) $product->get_stock_quantity(),
		        'inStock'  			   => ( float ) $product->get_stock_quantity(),
		        // 'orderedByCustomers'   => xxxxxxx,
		        // 'orderedFromSuppliers' => xxxxxxx,
		        // 'packageVolume' 	   => xxxxxxx,
			),
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
	 * Esporta prodotti WooCommerce verso Reviso
	 */
	public function export_products() {

		if ( isset( $_POST['wcefr-products-export'] ) ) {
			$args = array(
				'post_type' => array(
					'product', 
					// 'product_variation',
				), 
				'post_status'=>'publish',
				'posts_per_page' => -1
			);

			$posts = get_posts( $args );
			
			if( $posts ) {
				foreach ( $posts as $post ) {
					
					$product = wc_get_product( $post->ID );
					$sku = $product->get_sku();

					// error_log('Prodotto: ' . print_r( $product, true ) );

					$args = $this->prepare_product_data( $product );

					if ( $args ) {

						if ( $this->product_exists( $sku ) ) {

							$this->wcefrCall->call( 'put', 'products/' . $sku, $args );

						} else {

							$this->wcefrCall->call( 'post', 'products', $args );

						}
						
					
					}

				}
			}

		}

	}


	/**
	 * Cancella tutti i prodotti presenti in Reviso
	 */
	public function delete_remote_products() {

		$products = json_decode( $this->get_remote_products() );

		// error_log( 'Products: ' . print_r( $products, true ) );
		
		if ( isset( $products->collection ) ) {
			$n = 0;
			foreach ( $products->collection as $product ) {

				$n++;
				
				$output = $this->wcefrCall->call( 'delete', 'products/' . $product->productNumber . '?pagesize=1000' );

				error_log( 'Delete: ' . print_r( $output, true ) );

			}

			echo 'Eliminati ' . $n . ' prodotti'; //TEMP

		} else {
			
			echo 'Error'; //TEMP
		
		}

		exit;

	}

}
new wcefrProducts;
