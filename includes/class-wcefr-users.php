<?php
/**
 * Export customer and suppliers to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */
class WCEFR_Users {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$settings = new WCEFR_Settings();

			if ( $settings->connected ) {

				add_action( 'wp_ajax_wcefr-export-users', array( $this, 'export_users' ) );
				add_action( 'wp_ajax_wcefr-delete-remote-users', array( $this, 'delete_remote_users' ) );
				add_action( 'wp_ajax_wcefr-get-customers-groups', array( $this, 'get_customers_groups' ) );
				add_action( 'wp_ajax_wcefr-get-suppliers-groups', array( $this, 'get_suppliers_groups' ) );
				add_action( 'wcefr_export_single_user_event', array( $this, 'export_single_user' ), 10, 3 );
				add_action( 'wcefr_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 4 );

			}

		}

		$this->wcefr_call = new WCEFR_Call();

	}


	/**
	 * Return the provinceNumer, required by Reviso for adding the province
	 *
	 * @param  string $code the two letters province code coming from WC.
	 * @return int
	 */
	private function get_province_number( $code ) {

		$provinces = $this->wcefr_call->call( 'get', 'provinces/IT?pagesize=1000' );

		if ( isset( $provinces->collection ) ) {

			foreach ( $provinces->collection as $prov ) {

				if ( isset( $prov->code ) && $code == $prov->code ) {

					return $prov->provinceNumber;

				}
			}
		}

	}


	/**
	 * Get the delivery locations of a specific user in Reviso
	 *
	 * @param  int $customer_number the customer number in Reviso.
	 * @return array
	 */
	private function get_delivery_locations( $customer_number ) {

		$output = $this->wcefr_call->call( 'get', 'customers/' . $customer_number . '/delivery-locations' );

		return $output;

	}


	/**
	 * Add a delivery location to the specified user
	 *
	 * @param int   $customer_number the customer number in Reviso.
	 * @param array $userdata        wp user data.
	 * @return void
	 */
	private function add_delivery_location( $customer_number, $userdata ) {

		$delivery_locations = $this->get_delivery_locations( $customer_number );

		$count = 0;
		if ( isset( $delivery_locations->collection ) && is_array( $delivery_locations->collection ) ) {
			$count = count( $delivery_locations->collection );
		}

		if ( isset( $userdata->shipping_address_1 ) ) {

			$args = array(
				'address'                => $userdata['shipping_address_1'],
				'city'                   => $userdata['shipping_city'],
				'country'                => $userdata['shipping_country'],
				'postalCode'             => $userdata['shipping_postcode'],
				'barred'                 => null,
				'deliveryLocationNumber' => $count + 1,
			);

		} else {

			$args = array(
				'address'                => $userdata['billing_address_1'],
				'city'                   => $userdata['billing_city'],
				'country'                => $userdata['billing_country'],
				'postalCode'             => $userdata['billing_postcode'],
				'barred'                 => null,
				'deliveryLocationNumber' => $count + 1,
			);
		}

		$this->wcefr_call->call( 'post', 'customers/' . $customer_number . '/delivery-locations', $args );

	}


	/**
	 * Get customers and suppliers from Reviso
	 *
	 * @param string $type the type of user.
	 * @param int    $customer_number the specific customer to get.
	 * @return array
	 */
	private function get_remote_users( $type, $customer_number = null ) {

		$output = $this->wcefr_call->call( 'get', $type . '/' . $customer_number );

		return $output;

	}


	/**
	 * Check if a customer/ supplier exists in Reviso
	 *
	 * @param  string $type the type of user.
	 * @param  string $email the user email.
	 * @return bool
	 */
	private function user_exists( $type, $email ) {

		$output = false;
		$mails = array();

		$users = $this->get_remote_users( $type );

		$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

		if ( isset( $users->collection ) ) {

			foreach ( $users->collection as $customer ) {

				$mails[ $customer->$field_name ] = $customer->email;

			}

		}

		if ( in_array( $email, $mails ) ) {

			$output = true;

		}

		return $output;
	}


	/**
	 * Get the customers/ suppliers groups from Reviso
	 *
	 * @param  string $type customer or supplier.
	 * @return array
	 */
	public function get_user_groups( $type ) {

		$output = array();

		/*From plural to singular as required by the endpoint*/
		$endpoint = substr( $type, 0, -1 );

		$groups = $this->wcefr_call->call( 'get', $endpoint . '-groups' );

		$field_name = 'customers' === $type ? 'customerGroupNumber' : 'supplierGroupNumber';

		if ( isset( $groups->collection ) ) {

			foreach ( $groups->collection as $group ) {

				$output[ $group->$field_name ] = $group->name;

			}

		}

		return $output;

	}


	/**
	 * Callback - Get suppliers groups
	 */
	public function get_suppliers_groups() {

		$output = $this->get_user_groups( 'suppliers' );
		echo json_encode( $output );

		exit;

	}


	/**
	 * Callback - Get customers groups
	 */
	public function get_customers_groups() {

		$output = $this->get_user_groups( 'customers' );
		echo json_encode( $output );

		exit;

	}


	/**
	 * Prepare the single user data to export to Reviso
	 *
	 * @param  array  $user  the WP user if exists.
	 * @param  string $type  customers or suppliers.
	 * @param  object $order the WC order to get the customer details.
	 * @return array
	 */
	public function prepare_user_data( $user = null, $type, $order = null ) {

		$type_singular = substr( $type, 0, -1 );

		if ( $user ) {

			$user_details = get_userdata( $user['ID'] );

			$user_data = array_map(
				function( $a ) {
					return $a[0];
				},
				get_user_meta( $user['ID'] )
			);

			$name                    = $user_data['billing_first_name'] . ' ' . $user_data['billing_last_name'];
			$user_email              = $user_data['billing_email'];
			$country                 = $user_data['billing_country'];
			$city                    = $user_data['billing_city'];
			$state                   = $user_data['billing_state'];
			$address                 = $user_data['billing_address_1'];
			$postcode                = $user_data['billing_postcode'];
			$phone                   = $user_data['billing_phone'];
			$website                 = $user_details->user_url;
			$vat_number              = isset( $user_data['billing_wcefr_piva'] ) ? $user_data['billing_wcefr_piva'] : null;
			$identification_number   = isset( $user_data['billing_wcefr_cf'] ) ? $user_data['billing_wcefr_cf'] : null;
			$italian_certified_email = isset( $user_data['billing_wcefr_pec'] ) ? $user_data['billing_wcefr_pec'] : null;
			$public_entry_number     = isset( $user_data['billing_wcefr_pa_code'] ) ? $user_data['billing_wcefr_pa_code'] : null;

		} elseif ( $order ) {

			$name                    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$user_email              = $order->get_billing_email();
			$country                 = $order->get_billing_country();
			$city                    = $order->get_billing_city();
			$state                   = $order->get_billing_state();
			$address                 = $order->get_billing_address_1();
			$postcode                = $order->get_billing_postcode();
			$phone                   = $order->get_billing_phone();
			$vat_number              = $order->get_meta( '_billing_wcefr_piva' ) ? $order->get_meta( '_billing_wcefr_piva' ) : null;
			$identification_number   = $order->get_meta( '_billing_wcefr_cf' ) ? $order->get_meta( '_billing_wcefr_cf' ) : null;
			$italian_certified_email = $order->get_meta( '_billing_wcefr_pec' ) ? $order->get_meta( '_billing_wcefr_pec' ) : null;
			$public_entry_number     = $order->get_meta( '_billing_wcefr_pa_code' ) ? $order->get_meta( '_billing_wcefr_pa_code' ) : null;

		} else {

			return;

		}

		/*Reviso's group selected by the admin*/
		$group = get_option( 'wcefr-' . $type . '-group' );

		$args = array(
			'name'                   => $name,
			'email'                  => $user_email,
			'currency'               => 'EUR', //temp.
			'country'                => $country,
			'city'                   => $city,
			'address'                => $address,
			'zip'                    => $postcode,
			'telephoneAndFaxNumber'  => $phone,
			'vatZone' => array(
				'vatZoneNumber' => 1,
			),
			'paymentTerms'           => array(
				'paymentTermsNumber' => 6,
			),
			'countryCode'            => array(
				'code' => $country,
			),
			'province'               => array(
				'countryCode' => array(
					'code' => $country,
				),
				'ProvinceNumber' => $this->get_province_number( $state ),
			),
			$type_singular . 'Group' => array(
				$type_singular . 'GroupNumber' => intval( $group ),
			),
		);

		if ( isset( $website ) ) {
			$args['website'] = $website;
		}

		if ( $vat_number ) {
			$args['vatNumber'] = $vat_number;
		}

		if ( $identification_number ) {
			$args['corporateIdentificationNumber'] = $identification_number;
		}

		if ( $italian_certified_email ) {
			$args['italianCertifiedEmail'] = $italian_certified_email;
		}

		if ( $public_entry_number ) {
			$args['publicEntryNumber'] = $public_entry_number;
		}

		return $args;

	}


	/**
	 * Export single WP user to Reviso
	 *
	 * @param  int    $n the count number.
	 * @param  object $user the WP user.
	 * @param  string $type customer or supplier.
	 * @param  object $order the WC order to get the customer details.
	 * @return string admin message for Ajax response //temp
	 */
	public function export_single_user( $n, $user = null, $type, $order = null ) {

		$args = $this->prepare_user_data( $user, $type, $order );

		if ( $args && ! $this->user_exists( $type, $args['email'] ) ) {

			$output = $this->wcefr_call->call( 'post', $type . '/', $args );

			return $output;

		}

	}


	/**
	 * Export WP users as customers/ suppliers in Reviso
	 *
	 * @param  string $type customers or suppliers.
	 */
	public function export_users() {

		if ( isset( $_POST['wcefr-export-users-nonce'] ) && wp_verify_nonce( $_POST['wcefr-export-users-nonce'], 'wcefr-export-users' ) ) {

			$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
			$group = isset( $_POST['group'] ) ? sanitize_text_field( wp_unslash( $_POST['group'] ) ) : '';

			/*Salvo le impostazioni nel database*/
			update_option( 'wcefr-' . $type . '-role', $role );
			update_option( 'wcefr-' . $type . '-group', $group );

			$args     = array( 'role' => $role );
			$users    = get_users( $args );
			$response = array();

			if ( $users ) {

				$n = 0;

				foreach ( $users as $user ) {

					$n++;

					/*Cron event*/
					as_enqueue_async_action(
						'wcefr_export_single_user_event',
						array(
							$n,
							$user,
							$type,
						),
						'wcefr_export_single_user'
					);

				}

			}

			$message_type = substr( $type, 0, -1 );
			$response[] = array(
				'ok',
				/* translators: 1: users count 2: user type */
				esc_html( sprintf( __( '%1$d %2$s(s) export process has begun', 'wcefr' ), $n, $message_type ) ),
			);


			echo json_encode( $response );

		}

		exit;
	}


	/**
	 * Delete a single customer/ supplier in Reviso
	 *
	 * @param  int    $n          the count number.
	 * @param  object $user       the user from reviso.
	 * @param  string $type       customer or supplier.
	 * @param  string $field_name based on the type and useful to compose the endpoint.
	 */
	public function delete_remote_single_user( $n, $user, $type, $field_name ) {

		$output = $this->wcefr_call->call( 'delete', $type . '/' . $user->$field_name );

		/*temp*/
		if ( isset( $output->errorCode ) || isset( $output->developerHint ) ) {

			$response = array(
				'error',
				/* translators: error message */
				esc_html( sprintf( __( 'ERROR! %s<br>', 'wcefr' ), $output->message ) ),
			);

		} else {

			$response = array(
				'ok',
				/* translators: 1: users count 2: user type */
				esc_html( sprintf( __( 'Deleted %1$s: %2$d', 'wcefr' ), $type, $n ) ),
			);

		}

	}


	/**
	 * Delete all customers/ suppliers in Reviso
	 */
	public function delete_remote_users() {
		
		error_log('delete users');

		if ( isset( $_POST['type'], $_POST['wcefr-delete-users-nonce'] ) && wp_verify_nonce( $_POST['wcefr-delete-users-nonce'], 'wcefr-delete-users' ) ) {


			$response = array();
			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
			$users = $this->get_remote_users( $type );

			$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

			if ( isset( $users->collection ) && count( $users->collection ) > 0 ) {

				$n = 0;

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

				$message_type = substr( $type, 0, -1 );
				$response[] = array(
					'ok',
					/* translators: 1: users count 2: user type */
					esc_html( sprintf( __( '%1$d %2$s(s) delete process has begun', 'wcefr' ), $n, $message_type ) ),
				);

				echo json_encode( $response );

			} else {

				$response[] = array(
					'error',
					/* translators: user type */
					esc_html( sprintf( __( 'ERROR! There are not %s to delete', 'wcefr' ), $type ) ),
				);

				echo json_encode( $response );

			}

		}

		exit;

	}
}
new WCEFR_Users( true );
