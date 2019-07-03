<?php
/**
 * Esportazione di clienti e fornitori verso Reviso
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrUsers {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'export_customers' ) );
		add_action( 'admin_init', array( $this, 'export_suppliers' ) );
		add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
		add_action( 'wp_ajax_get-customer-groups', array( $this, 'get_customer_groups' ) );
		add_action( 'wp_ajax_get-supplier-groups', array( $this, 'get_supplier_groups' ) );

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

		$provinces = json_decode( $this->wcefrCall->call( 'get', 'provinces/IT?pagesize=1000' ) );

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

		$output = json_decode( $this->wcefrCall->call( 'get', 'customers/' . $customer_number . '/delivery-locations' ) );

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

		$users = json_decode( $this->get_remote_users( $type ) );

		$field_name = 'customer' === $type ? 'customerNumber' : 'supplierNumber';

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
	 * @param  string $type cliente o fornitore
	 * @return array
	 */
	public function get_user_groups( $type ) {

		$output = array();

		$groups = json_decode( $this->wcefrCall->call( 'get', $type . '-groups' ) );

		$field_name = 'customer' === $type ? 'customerGroupNumber' : 'supplierGroupNumber';

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
	public function get_supplier_groups() {

		$output = $this->get_user_groups( 'supplier' );
		echo json_encode( $output );

		exit;

	}


	/**
	 * Callback - Recupero gruppi clienti
	 * @return string json
	 */
	public function get_customer_groups() {

		$output = $this->get_user_groups( 'customer' );
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

		$type = 'customers' === $type ? 'customer' : 'supplier';


		$user_details = get_userdata( $user->ID );
					
		$user_data = array_map( 
			function( $a ) {
				return $a[0];
			},
			get_user_meta( $user->ID )
		);
		
		$user_email = $user_data['billing_email'];

		/*Gruppo Reviso selezionato dall'admnin*/
		$group = isset( $_POST['wcefr-' . $type . '-groups'] ) ? intval( $_POST['wcefr-' . $type . '-groups'] ) : ''; 

		// error_log( 'Group2: ' . $_POST['wcefr-' . $type . '-groups'] );

		/*Salvo le impostazioni nel database*/
		update_option( 'wcefr-' . $type . '-group', $group ); 

		$args = null;

		/*Verifico presenza campi ordine */
		if ( isset( $user_data['billing_postcode'] ) ) {

			$args = array(
				'name' => $user_data['billing_first_name'] . ' ' . $user_data['billing_last_name'],
				'email' => $user_email,
				$type . 'Group' => array(
					$type . 'GroupNumber' => $group,
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
				'vatNumber' => $user_data['billing_wcexd_piva'], //TEMP
				'vatZone' => array(
					'vatZoneNumber' => 1,
				),
				'paymentTerms' => array(
					'paymentTermsNumber' => 6,
				),
				'italianCertifiedEmail' => $user_data['billing_wcexd_pec'],
				'corporateIdentificationNumber' => $user_data['billing_wcexd_cf'],
				'publicEntryNumber' => $user_data['billing_wcexd_pa_code'],

				'telephoneAndFaxNumber' => $user_data['billing_phone'],
				'website' => $user_details->user_url,
				// 'defaultDeliveryLocation' => array(
				// 	'deliveryLocationNumber' => 1	
				// )
				// 'italianCustomerType' => 'Consumer',
				// 'xxxxxxxx' => $user_data['xxxxxxxxxxxxxxxx'],

			);

		}

		return $args;

	}


	/**
	 * Esporta utenti WordPress come clienti in Reviso
	 * @param string $type cliente o fornitore
	 */
	public function export_users( $type ) {

		/*Ruolo utente da esportare*/
		$users_role = sanitize_text_field( $_POST['wcefr-' . $type . '-role'] );

		/*Salvo le impostazioni nel database*/
		update_option( 'wcefr-' . $type . '-role', $users_role ); 
		  
		$args = array( 'role' => $users_role );

		$users = get_users($args);

		if ( $users ) {
			foreach ($users as $user) {	
				
				$args = $this->prepare_user_data( $user, $type );

				if ( $args and ! $this->user_exists( $type, $args['email'] ) ) {

					$this->wcefrCall->call( 'post', $type . '/', $args );
					// $this->add_delivery_location( 1, $user_data );
			
				}
					
			}

		}

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


	/**
	 * Cancella tutti i clienti presenti in Reviso
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['type'] ) ) {

			$type = sanitize_text_field( $_POST['type'] );
			$users = json_decode( $this->get_remote_users( $type ) );

			$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

			if ( isset( $users->collection ) && count( $users->collection ) > 0 ) {
				
				$n = 0;
				foreach ( $users->collection as $user ) {
					
					$n++;

					$output = $this->wcefrCall->call( 'delete', $type . '/' . $user->$field_name );

				}

				$response = array(
					'ok',
					__( 'The delete process is started', 'wcefr' ),
				);

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