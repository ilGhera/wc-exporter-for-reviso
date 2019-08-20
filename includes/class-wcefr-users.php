<?php
/**
 * Esportazione di clienti e fornitori verso Reviso
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrUsers {

	public function __construct() {

		// add_action( 'admin_init', array( $this, 'export_customers' ) );
		// add_action( 'admin_init', array( $this, 'export_suppliers' ) );
		add_action( 'wp_ajax_export-users', array( $this, 'export_users' ) );
		add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
		add_action( 'wp_ajax_get-customers-groups', array( $this, 'get_customers_groups' ) );
		add_action( 'wp_ajax_get-suppliers-groups', array( $this, 'get_suppliers_groups' ) );
		add_action( 'wcefr_export_single_user_event', array( $this, 'export_single_user' ), 10, 3 );
		add_action( 'wcefr_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 4 );

		$this->wcefrCall = new wcefrCall();

	}


	/**
	 * TEMP
	 */
	public function test_call() {

		$output = $this->wcefrCall->call( 'get', 'provinces/IT' );
		
		return $output;

	}


	/**
	 * Resituisce il provinceNumer, necessario all'aggiunta della provincia in Reviso
	 * @param  string $code la sigla della provincia proveniente da WooCommerce
	 * @return int
	 */
	private function get_province_number( $code ) {

		$provinces = $this->wcefrCall->call( 'get', 'provinces/IT?pagesize=1000' );

		if ( isset( $provinces->collection ) ) {

			foreach ( $provinces->collection as $prov ) {
				if ( isset( $prov->code ) && $code == $prov->code ) {
					return $prov->provinceNumber;
				}
			}
		}

	}


	/**
	 * Restituisce gli indirizzi di spedizione legati all'utente
	 * @param  int $customer_number il numero utente Reviso
	 * @return [type] 				TEMP
	 */
	private function get_delivery_locations( $customer_number ) {

		$output = $this->wcefrCall->call( 'get', 'customers/' . $customer_number . '/delivery-locations' );

		return $output;
		
	}


	/**
	 * Aggiunge un indirizzo di spedizione all'utente dato
	 * @param int $customer_number il numero utente Reviso
	 * @param array $userdata 	   wp user data
	 */
	private function add_delivery_location( $customer_number, $userdata ) {

		$delivery_locations = $this->get_delivery_locations( $customer_number );

		$count = 0;
		if ( isset( $delivery_locations->collection ) && is_array( $delivery_locations->collection ) ) {
			$count = count( $delivery_locations->collection );
		}

		if ( isset( $userdata->shipping_address_1 ) ) {

			$args = array(
				'address' 				 => $userdata['shipping_address_1'],
	            'city' 					 => $userdata['shipping_city'],
	            'country' 				 => $userdata['shipping_country'],
	            'postalCode' 			 => $userdata['shipping_postcode'],
	            'barred' 				 => null,
	            'deliveryLocationNumber' => $count + 1,
			);

		} else {

			$args = array(	
				'address' 				 => $userdata['billing_address_1'],
	            'city' 					 => $userdata['billing_city'],
	            'country' 				 => $userdata['billing_country'],
	            'postalCode' 			 => $userdata['billing_postcode'],
	            'barred' 				 => null,
	            'deliveryLocationNumber' => $count + 1,

	        );

		}

		$this->wcefrCall->call( 'post', 'customers/' . $customer_number . '/delivery-locations', $args );

	}


	/**
	 * Restituisce i clienti/ fornitori presenti in Reviso
	 * @return string risposta in json della chiamata all'endpoint
	 */
	private function get_remote_users( $type, $customer_number = null ) {

		$output = $this->wcefrCall->call( 'get', $type . '/' . $customer_number  );
		
		return $output;

	}


	/**
	 * Verifica la presenza di un cliente/ fornitore in Reviso
	 * @param  string $email la mail del cliente
	 * @return bool
	 */
	private function user_exists( $type, $email ) {

		$output = false;
		$mails = array();

		$users = $this->get_remote_users( $type );

		$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

		if ( isset( $users->collection ) ) {
			foreach ($users->collection as $customer) {
				$mails[ $customer->$field_name ] = $customer->email;
			}
		}

		if ( in_array( $email, $mails ) ) {
			$output = true;
		}

		return $output;
	}


	/**
	 * Restituisce i gruppi presenti in Reviso per il tipo di utente dato
	 * @param  string $type clienti o fornitori
	 * @return array
	 */
	public function get_user_groups( $type ) {

		$output = array();

		// From plural to singular as required by the endpoint
		$endpoint = substr( $type, 0, -1 );

		$groups = $this->wcefrCall->call( 'get', $endpoint . '-groups' );

		$field_name = 'customers' === $type ? 'customerGroupNumber' : 'supplierGroupNumber';

		if ( isset( $groups->collection ) ) {
			
			foreach ($groups->collection as $group) {

			 	$output[ $group->$field_name ] = $group->name; 

			 } 
			
		} else {

			$output = __( 'No groups available', 'wcefr' );
		
		}

		return $output;

	}


	/**
	 * Callback - Recupero gruppi fornitori
	 * @return string json
	 */
	public function get_suppliers_groups() {

		$output = $this->get_user_groups( 'suppliers' );
		echo json_encode( $output );

		exit;

	}


	/**
	 * Callback - Recupero gruppi clienti
	 * @return string json
	 */
	public function get_customers_groups() {

		$output = $this->get_user_groups( 'customers' );
		echo json_encode( $output );

		exit;

	}


	/**
	 * Prepara i dati del singolo utente da esporare verso Reviso
	 * @param  int $user il numero utente Reviso
	 * @param  string $type cliente o fornitore
	 * @return array
	 */
	public function prepare_user_data( $user, $type ) {

		$type_singular = substr( $type , 0, -1 );

		$user_details = get_userdata( $user->ID );
					
		$user_data = array_map( 
			function( $a ) {
				return $a[0];
			},
			get_user_meta( $user->ID )
		);
		
		$user_email = $user_data['billing_email'];

		/*Gruppo Reviso selezionato dall'admnin*/
		$group = get_option( 'wcefr-' . $type . '-group' );

		// error_log( 'Group2: ' . $_POST['wcefr-' . $type . '-groups'] );

		/*Salvo le impostazioni nel database*/
		update_option( 'wcefr-' . $type . '-group', $group ); 

		$args = null;

		/*Verifico presenza campi ordine */
		if ( isset( $user_data['billing_postcode'] ) ) {

			$args = array(
				'name' => $user_data['billing_first_name'] . ' ' . $user_data['billing_last_name'],
				'email' => $user_email,
				$type_singular . 'Group' => array(
					$type_singular . 'GroupNumber' => intval( $group ),
				),
				'currency' => 'EUR',
				'country' => $user_data['billing_country'],
				'city' => $user_data['billing_city'],
				'countryCode' => array(
					'code' => $user_data['billing_country'],
				),
				'province' => array(
					'countryCode' => array(
						'code' => $user_data['billing_country'],
					),
					'ProvinceNumber' => $this->get_province_number( $user_data['billing_state'] ),
				),
				'address' => $user_data['billing_address_1'],
				'zip' => $user_data['billing_postcode'],
				// 'vatNumber' => $user_data['billing_wcexd_piva'], //TEMP
				'vatZone' => array(
					'vatZoneNumber' => 1,
				),
				'paymentTerms' => array(
					'paymentTermsNumber' => 6,
				),
				// 'italianCertifiedEmail' => $user_data['billing_wcexd_pec'],
				// 'corporateIdentificationNumber' => $user_data['billing_wcexd_cf'],
				// 'publicEntryNumber' => $user_data['billing_wcexd_pa_code'],

				'telephoneAndFaxNumber' => $user_data['billing_phone'],
				'website' => $user_details->user_url,
				// 'defaultDeliveryLocation' => array(
				// 	'deliveryLocationNumber' => 1	
				// )
				// 'italianCustomerType' => 'Consumer',
				// 'xxxxxxxx' => $user_data['xxxxxxxxxxxxxxxx'],

			);

			if ( isset( $user_data['billing_wcexd_piva'] ) ) {
				$args['vatNumber'] = $user_data['billing_wcexd_piva'];
			}

			if ( isset( $user_data['billing_wcexd_cf'] ) ) {
				$args['corporateIdentificationNumber'] = $user_data['billing_wcexd_cf'];
			}

			if ( isset( $user_data['billing_wcexd_pec'] ) ) {
				$args['italianCertifiedEmail'] = $user_data['billing_wcexd_pec'];
			}

			if ( isset( $user_data['billing_wcexd_pa_code'] ) ) {
				$args['publicEntryNumber'] = $user_data['billing_wcexd_pa_code'];
			}

		}

		return $args;

	}


	public function export_single_user( $n, $user, $type ) {

		$args = $this->prepare_user_data( $user, $type );

		if ( $args and ! $this->user_exists( $type, $args['email'] ) ) {

			$output = $this->wcefrCall->call( 'post', $type . '/', $args );
			
			if ( isset( $output->errorCode ) || isset( $output->developerHint )) {
			
				error_log( 'ATTENZIONE:' . print_r( $output, true ) );

				$response[] = array(
					'error',
					__( 'ERROR! ' . $output->message . '<br>', 'wcefr' ),
				);

			} else {

				$n++;

				$response[] = array(
					'ok',
					// __( 'The product #' . $product->productNumber . ' was deleted', 'wcefr' ),			
					__( 'Exported ' . $type . ': <span>' . $n . '</span>', 'wcefr' ),			
				);

			}
						
		}

	}


	/**
	 * Esporta utenti WordPress come clienti in Reviso
	 * @param string $type cliente o fornitore
	 */
	public function export_users( $type ) {

		$type  = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$role  = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		$group = isset( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : '';

		/*Salvo le impostazioni nel database*/
		update_option( 'wcefr-' . $type . '-role', $role ); 
		update_option( 'wcefr-' . $type . '-group', $group ); 
		  
		$args = array( 'role' => $role );

		$users = get_users($args);

		$response = array();

		if ( $users ) {

			$n = 0;

			foreach ($users as $user) {	

				$n++;

				/*Cron event*/
				wp_schedule_single_event(

					time() + 1,
					'wcefr_export_single_user_event',
					array(
						$n,
						$user,
						$type,
					)
					
				);								

			}

		}

		$response[] = array(
			'ok',
			// __( 'The product #' . $product->productNumber . ' was deleted', 'wcefr' ),			
			__( 'Exported ' . $type . ': <span>' . $n . '</span>', 'wcefr' ),			
		);


		echo json_encode( $response );

		exit;
	}


	/**
	 * Esporta utenti WordPress come fornitori in Reviso
	 */
	public function export_suppliers() {

		if( isset( $_POST['wcefr-suppliers-role'] ) ) {

			$response = $this->export_users( 'suppliers' );

		}

	}


	/**
	 * Esporta utenti WordPress come clienti in Reviso
	 */
	public function export_customers() {

		if( isset( $_POST['wcefr-customers-role'] ) ) {

			$response = $this->export_users( 'customers' );

		}

	}


	public function delete_remote_single_user( $n, $user, $type, $field_name ) {

		$output = $this->wcefrCall->call( 'delete', $type . '/' . $user->$field_name );
		error_log( 'CANCELLAZIONE UTENTI: ' . print_r( $output, true ) );
		if ( isset( $output->errorCode ) || isset( $output->developerHint )) {

			$response = array(
				'error',
				__( 'ERROR! ' . $output->message . '<br>', 'wcefr' ),
			);

		} else {

			$response = array(
				'ok',
				__( 'Deleted ' . $type . ': <span>' . $n . '</span>', 'wcefr' ),			
			);

		}

	}


	/**
	 * Cancella tutti i clienti presenti in Reviso
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['type'] ) ) {

			$type = sanitize_text_field( $_POST['type'] );
			$users = $this->get_remote_users( $type );

			$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

			if ( isset( $users->collection ) && count( $users->collection ) > 0 ) {
				
				$n = 0;
				$response = array();

				foreach ( $users->collection as $user ) {
					
					$n++;

					/*Cron event*/
					wp_schedule_single_event(

						time() + 1,
						'wcefr_delete_remote_single_user_event',
						array(
							$n,
							$user,
							$type,
							$field_name,
						)
						
					);													
 
				}

				echo json_encode( $response );

			} else {
				
				$response = array(
					'error',
					__( 'ERROR! There are not ' . $type . ' to delete', 'wcefr' ),
				);

				echo json_encode( $response );
			
			}

		}

		exit;

	}
}
new wcefrUsers;