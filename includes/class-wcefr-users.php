<?php
/**
 * Export customer and suppliers to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCEFR_Users
 */
class WCEFR_Users {

	/**
	 * WP user role for suppliers
	 *
	 * @var string
	 */
	private $suppliers_role;

	/**
	 * WP user role for customer
	 *
	 * @var string
	 */
	private $customers_role;

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

			/* Get options */
			$this->suppliers_role = get_option( 'wcefr-suppliers-role' );
			$this->customers_role = get_option( 'wcefr-customers-role' );

			add_action( 'wp_ajax_wcefr-update-users-role', array( $this, 'update_users_role' ) );
			add_action( 'wp_ajax_wcefr-export-users', array( $this, 'export_users' ) );
			add_action( 'wp_ajax_wcefr-delete-remote-users', array( $this, 'delete_remote_users' ) );
			add_action( 'wp_ajax_wcefr-get-customers-groups', array( $this, 'get_customers_groups' ) );
			add_action( 'wp_ajax_wcefr-get-suppliers-groups', array( $this, 'get_suppliers_groups' ) );
			add_action( 'wcefr_export_single_user_event', array( $this, 'export_single_user' ), 10, 3 );
			add_action( 'wcefr_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 4 );
		}

		$this->wcefr_call = new WCEFR_Call();
	}

	/**
	 * Get the WP user data
	 *
	 * @param int  $user_id  the WP user ID.
	 * @param bool $user_url return only the user site URL with true.
	 *
	 * @return mixed
	 */
	private function get_user_data( $user_id, $user_url = false ) {

		$output = null;

		if ( $user_id ) {

			$user_details = get_userdata( $user_id );

			if ( $user_url ) {

				return $user_details->user_url;
			}

			$output = array_map(
				function( $a ) {
					return $a[0];
				},
				get_user_meta( $user_id )
			);

			$output['user_url'] = $user_details->user_url;
		}

		return $output;
	}

	/**
	 * Return the provinceNumer, required by Reviso for adding the province
	 *
	 * @param  string $code the two letters province code coming from WC.
	 *
	 * @return int
	 */
	private function get_province_number( $code ) {

		$transient = get_transient( 'wcefr-provinces' );

		if ( $transient ) {

			$provinces = $transient;

		} else {

			$provinces = $this->wcefr_call->call( 'get', 'provinces/IT?pagesize=1000' );
		}

		if ( isset( $provinces->collection ) ) {

			if ( ! $transient ) {

				set_transient( 'wcefr-provinces', $provinces, DAY_IN_SECONDS );
			}

			foreach ( $provinces->collection as $prov ) {

				if ( isset( $prov->code ) && $code === $prov->code ) {

					return $prov->provinceNumber;
				}
			}
		}
	}

	/**
	 * Get the delivery locations of a specific user in Reviso
	 *
	 * @param  int $customer_number the customer number in Reviso.
	 *
	 * @return array
	 */
	private function get_delivery_locations( $customer_number ) {

		$output = $this->wcefr_call->call( 'get', 'customers/' . $customer_number . '/delivery-locations' );

		if ( isset( $output->collection ) ) {

			return $output->collection;
		}
	}

	/**
	 * Add a new delivery location for a specific user in Reviso
	 *
	 * @param  int   $customer_number the customer number in Reviso.
	 * @param  array $args            the delivery location details.
	 *
	 * @return array
	 */
	private function add_delivery_location( $customer_number, $args ) {

		$output = $this->wcefr_call->call( 'post', 'customers/' . $customer_number . '/delivery-locations', $args );

		/*Log the error*/
		if ( ( isset( $output->errorCode ) || isset( $output->developerHint ) ) && isset( $output->message ) ) {

			error_log( 'WCEFR ERROR | User ID ' . $user_id . ' | ' . $output->message );

		} else {

			return $output->deliveryLocationNumber;
		}
	}

	/**
	 * Get the delivery location to the specified user
	 *
	 * @param int    $user_id         the WP user.
	 * @param int    $customer_number the customer number in Reviso.
	 * @param object $order           the WC order.
	 *
	 * @return int the delivery location number
	 */
	private function get_delivery_location( $user_id, $customer_number, $order = null ) {

		$output             = null;
		$delivery_locations = $this->get_delivery_locations( $customer_number );

		/* Get user data */
		$user_data = $this->get_user_data( $user_id );

		if ( is_array( $user_data ) && ! empty( $user_data ) ) {

			$shipping_country  = isset( $user_data['shipping_country'] ) ? $user_data['shipping_country'] : '';
			$shipping_city     = isset( $user_data['shipping_city'] ) ? $user_data['shipping_city'] : '';
			$shipping_address  = isset( $user_data['shipping_address_1'] ) ? $user_data['shipping_address_1'] : '';
			$shipping_postcode = isset( $user_data['shipping_postcode'] ) ? $user_data['shipping_postcode'] : '';
			$shipping_country  = $shipping_country ? $shipping_country : $user_data['billing_country'];
			$shipping_city     = $shipping_city ? $shipping_city : $user_data['billing_city'];
			$shipping_address  = $shipping_address ? $shipping_address : $user_data['billing_address_1'];
			$shipping_postcode = $shipping_postcode ? $shipping_postcode : $user_data['billing_postcode'];

		} elseif ( is_object( $order ) ) {

			$shipping_country  = $order->get_shipping_country() ? $order->get_shipping_country() : '';
			$shipping_city     = $order->get_shipping_city() ? $order->get_shipping_city() : '';
			$shipping_address  = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : '';
			$shipping_postcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '';
			$shipping_country  = $shipping_country ? $shipping_country : $order->get_billing_country();
			$shipping_city     = $shipping_city ? $shipping_city : $order->get_billing_city();
			$shipping_address  = $shipping_address ? $shipping_address : $order->get_billing_address_1();
			$shipping_postcode = $shipping_postcode ? $shipping_postcode : $order->get_billing_postcode();
		}

		if ( is_array( $delivery_locations ) ) {

			foreach ( $delivery_locations as $location ) {

				if ( $shipping_address === $location->address ) {

					$output = $location->deliveryLocationNumber;

					break;
				}
			}
		}

		if ( ! $output ) {

			/* Add a new delivery location */
			$args = array(
				'address'    => $shipping_address,
				'city'       => $shipping_city,
				'country'    => $shipping_country,
				'postalCode' => $shipping_postcode,
			);

			$output = $this->add_delivery_location( $customer_number, $args );
		}

		return $output;
	}

	/**
	 * Get customers and suppliers from Reviso
	 *
	 * @param string $type the type of user.
	 * @param int    $customer_number the specific customer to get.
	 *
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
	 *
	 * @return bool
	 */
	private function user_exists( $type, $email ) {

		$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

		$output = $this->wcefr_call->call( 'get', $type . '?filter=email$eq:' . $email );

		if ( ! $output ) {

			return false;

		} else {

			if ( isset( $output->collection[0]->$field_name ) ) {

				return $output->collection[0]->$field_name;
			}
		}
	}

	/**
	 * Get the customers/ suppliers groups from Reviso
	 *
	 * @param  string $type customer or supplier.
	 *
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
	 *
	 * @return void
	 */
	public function get_suppliers_groups() {

		$transient = get_transient( 'wcefr-suppliers-groups' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$output = $this->get_user_groups( 'suppliers' );

			set_transient( 'wcefr-suppliers-groups', $output, DAY_IN_SECONDS );
		}

		echo wp_json_encode( $output );

		exit;
	}

	/**
	 * Callback - Get customers groups
	 *
	 * @return void
	 */
	public function get_customers_groups() {

		$transient = get_transient( 'wcefr-customers-groups' );

		if ( $transient ) {

			$output = $transient;

		} else {

			$output = $this->get_user_groups( 'customers' );

			set_transient( 'wcefr-customers-groups', $output, DAY_IN_SECONDS );
		}

		echo wp_json_encode( $output );

		exit;
	}

	/**
	 * Prepare the single user data to export to Reviso
	 *
	 * @param  int    $user_id   the WP user id.
	 * @param  string $type      customers or suppliers.
	 * @param  object $order     the WC order to get the customer details.
	 * @param  bool   $attention return the contact data with true.
	 *
	 * @return array
	 */
	public function prepare_user_data( $user_id, $type, $order = null, $attention = false ) {

		$type_singular = substr( $type, 0, -1 );

		if ( $order ) {

			$company                 = $order->get_billing_company();
			$contact                 = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$name                    = $company ? $company : $contact;
			$user_email              = $order->get_billing_email();
			$country                 = $order->get_billing_country();
			$city                    = $order->get_billing_city();
			$state                   = $order->get_billing_state();
			$address                 = $order->get_billing_address_1();
			$postcode                = $order->get_billing_postcode();
			$phone                   = $order->get_billing_phone();
			$website                 = $this->get_user_data( $user_id, true );
			$vat_number              = $order->get_meta( '_billing_wcefr_piva' ) ? $order->get_meta( '_billing_wcefr_piva' ) : null;
			$identification_number   = $order->get_meta( '_billing_wcefr_cf' ) ? $order->get_meta( '_billing_wcefr_cf' ) : null;
			$italian_certified_email = $order->get_meta( '_billing_wcefr_pec' ) ? $order->get_meta( '_billing_wcefr_pec' ) : null;
			$public_entry_number     = $order->get_meta( '_billing_wcefr_pa_code' ) ? $order->get_meta( '_billing_wcefr_pa_code' ) : null;
			$italian_castomer_type   = $vat_number ? 'B2B' : 'Consumer';

		} elseif ( $user_id ) {

			/* Get user data */
			$user_data = $this->get_user_data( $user_id );

			if ( is_array( $user_data ) ) {

				$company                 = isset( $user_data['billing_company'] ) ? $user_data['billing_company'] : null;
				$contact                 = isset( $user_data['billing_first_name'], $user_data['billing_last_name'] ) ? $user_data['billing_first_name'] . ' ' . $user_data['billing_last_name'] : '';
				$name                    = $company ? $company : $contact;
				$user_email              = isset( $user_data['billing_email'] ) ? $user_data['billing_email'] : '';
				$country                 = isset( $user_data['billing_country'] ) ? $user_data['billing_country'] : '';
				$city                    = isset( $user_data['billing_city'] ) ? $user_data['billing_city'] : '';
				$state                   = isset( $user_data['billing_state'] ) ? $user_data['billing_state'] : '';
				$address                 = isset( $user_data['billing_address_1'] ) ? $user_data['billing_address_1'] : '';
				$postcode                = isset( $user_data['billing_postcode'] ) ? $user_data['billing_postcode'] : '';
				$phone                   = isset( $user_data['billing_phone'] ) ? $user_data['billing_phone'] : '';
				$website                 = isset( $user_data['user_url'] ) ? $user_data['user_url'] : null;
				$vat_number              = isset( $user_data['billing_wcefr_piva'] ) ? $user_data['billing_wcefr_piva'] : null;
				$identification_number   = isset( $user_data['billing_wcefr_cf'] ) ? $user_data['billing_wcefr_cf'] : null;
				$italian_certified_email = isset( $user_data['billing_wcefr_pec'] ) ? $user_data['billing_wcefr_pec'] : null;
				$public_entry_number     = isset( $user_data['billing_wcefr_pa_code'] ) ? $user_data['billing_wcefr_pa_code'] : null;
				$italian_castomer_type   = $vat_number ? 'B2B' : 'Consumer';
			}
		} else {

			return;
		}

		/* Generic pa code */
		if ( ! $public_entry_number ) {

			$public_entry_number = 'IT' === $country ? '0000000' : 'XXXXXXX';
		}

		/* Customer contact */
		if ( $attention ) {

			return $company ? $contact : false;
		}

		$base_location = wc_get_base_location();
		$shop_country  = is_array( $base_location ) && isset( $base_location['country'] ) ? $base_location['country'] : null;

		/*Reviso VatZone based on user country */
		$vat_zone = $shop_country === $country ? 1 : 3;

		/* Payment term */
		$class        = new WCEFR_Orders();
		$payment_term = $class->get_remote_payment_term();

		if ( $order ) {

			/*Reviso's group selected by the admin*/
			$get_customers_groups = get_option( 'wcefr-orders-customers-group' );

			if ( 0 === intval( $get_customers_groups ) ) {

				/* By nationality */
				$group = $shop_country === $country ? 1 : 2;

			} else {

				/* Custom group */
				$group = $get_customers_groups;
			}

			/* Payment method */
			$payment_method_title = $order->get_payment_method() ? $order->get_payment_method() : '';
			$payment_method       = $class->get_remote_payment_method( $payment_method_title );

		} else {

			/*Reviso's group saved by the admin*/
			$group = get_option( 'wcefr-' . $type . '-group' );

			/* Payment method and term */
			$payment_method = get_user_meta( $user_id, 'wcefr-payment-method', true );
		}

		$args = array(
			'name'                   => $name,
			'email'                  => $user_email,
			'currency'               => 'EUR', // temp.
			'country'                => $country,
			'city'                   => $city,
			'address'                => $address,
			'zip'                    => $postcode,
			'telephoneAndFaxNumber'  => $phone,
			'vatZone'                => array(
				'vatZoneNumber' => $vat_zone,
			),
			'countryCode'            => array(
				'code' => $country,
			),
			$type_singular . 'Group' => array(
				$type_singular . 'GroupNumber' => intval( $group ),
			),
			'italianCustomerType'    => $italian_castomer_type,
		);

		if ( 'IT' === $country ) {

			$args['province'] = array(
				'countryCode'    => array(
					'code' => $country,
				),
				'provinceNumber' => $this->get_province_number( $state ),
			);
		}

		if ( isset( $website ) ) {

			$args['website'] = $website;
		}

		if ( $vat_number ) {

			$args['vatNumber'] = $vat_number;
		}

		if ( $identification_number ) {

			$args['corporateIdentificationNumber'] = strtoupper( $identification_number );

		} elseif ( $vat_number ) {

			$args['corporateIdentificationNumber'] = strtoupper( $vat_number );
		}

		if ( $italian_certified_email ) {

			$args['italianCertifiedEmail'] = $italian_certified_email;
		}

		if ( $public_entry_number ) {

			$args['publicEntryNumber'] = $public_entry_number;
		}

		if ( $payment_method ) {

			$args['paymentType'] = $payment_method;
		}

		if ( $payment_term ) {

			$args['paymentTerms'] = $payment_term;
		}

		return $args;
	}

	/**
	 * Get the customer contact number from Reviso or create it if necessary
	 *
	 * @param int    $remote_id the customer id in Reviso.
	 * @param string $contact_name the customer contact name.
	 *
	 * @return int
	 */
	public function get_customer_contact_number( $remote_id, $contact_name ) {

		/* Get customer contacts */
		$contacts = $this->wcefr_call->call( 'get', 'customers/' . $remote_id . '/contacts' );

		if ( isset( $contacts->collection ) && is_array( $contacts->collection ) ) {

			foreach ( $contacts->collection as $contact ) {

				if ( $contact_name === $contact->name ) {

					return $contact->customerContactNumber;
				}
			}

			$args = array(
				'customer' => array(
					'customerNumber' => $remote_id,
				),
				'name'     => $contact_name,
			);

			$contact = $this->wcefr_call->call( 'post', 'customers/' . $remote_id . '/contacts', $args );

			if ( isset( $contact->customerContactNumber ) ) {

				return $contact->customerContactNumber;
			}
		}
	}

	/**
	 * Export single WP user to Reviso
	 *
	 * @param  int    $user_id   the WP user.
	 * @param  string $type      customer or supplier.
	 * @param  object $order     the WC order to get the customer details.
	 * @param  bool   $new       with true the remote user doesn't exist.
	 * @param  bool   $remote_id the remote id of the Reviso customer.
	 *
	 * @return int the remote user ID
	 */
	public function export_single_user( $user_id, $type, $order = null, $new = false, $remote_id = null ) {

		$output       = null;
		$contact_name = null;
		$args         = $this->prepare_user_data( $user_id, $type, $order );

		if ( $new ) {

			$remote_id = false;

		} else {

			/* Check if the remote user exists if $new is not specified */
			$remote_id = $remote_id ? $remote_id : $this->user_exists( $type, $args['email'] );
		}

		if ( $args ) {

			/* Add the new customer in Reviso */
			if ( ! $remote_id ) {

				$output = $this->wcefr_call->call( 'post', $type . '/', $args );

				if ( isset( $output->customerNumber ) ) {

					$remote_id = $output->customerNumber;
				}
			}

			if ( $remote_id && 'customers' === $type ) {

				$contact_name = $this->prepare_user_data( $user_id, $type, $order, true );

				/* Add the customer contact name in case of company */
				if ( $contact_name ) {

					$contact_number = $this->get_customer_contact_number( $remote_id, $contact_name );

					$args['attention'] = array(
						'customerContactNumber' => $contact_number,
					);
				}

				/* Add the delivery location */
				$args['defaultDeliveryLocation'] = array(
					'deliveryLocationNumber' => $this->get_delivery_location( $user_id, $remote_id, $order ),
				);

				$output = $this->wcefr_call->call( 'put', $type . '/' . $remote_id, $args );
			}

			/*Log the error*/
			if ( ( isset( $output->errorCode ) || isset( $output->developerHint ) ) && isset( $output->message ) ) {

				error_log( 'WCEFR ERROR | User ID ' . $user_id . ' | ' . $output->message );

			} else {

				return $output;
			}
		}
	}

	/**
	 * Update users role for suppliers and customers with Ajax
	 *
	 * @return void
	 */
	public function update_users_role() {

		if ( isset( $_POST['wcefr-users-role-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-users-role-nonce'] ) ), 'wcefr-users-role' ) ) {

			$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$role = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';

			/* Save in the DB */
			$output = update_option( 'wcefr-' . $type . '-role', $role );

			if ( $output ) {

				echo esc_html__( 'Saved!', 'wc-exporter-for-reviso' );
			}
		}

		exit;
	}

	/**
	 * Export WP users as customers/ suppliers in Reviso
	 *
	 * @return void
	 */
	public function export_users() {

		if ( isset( $_POST['wcefr-export-users-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-export-users-nonce'] ) ), 'wcefr-export-users' ) ) {

			$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
			$group = isset( $_POST['group'] ) ? sanitize_text_field( wp_unslash( $_POST['group'] ) ) : '';

			/* Save in the DB */
			update_option( 'wcefr-' . $type . '-group', $group );

			$args     = array( 'role' => $role );
			$users    = get_users( $args );
			$response = array();
			$n        = 0;

			if ( $users ) {

				foreach ( $users as $user ) {

					$n++;

					/*Schedule single event*/
					as_enqueue_async_action(
						'wcefr_export_single_user_event',
						array(
							'user_id'   => $user->ID,
							'user_type' => $type,
						),
						'wcefr_export_single_user'
					);
				}
			}

			$message_type = substr( $type, 0, -1 );
			$response[]   = array(
				'ok',
				/* translators: 1: users count 2: user type */
				esc_html( sprintf( __( '%1$d %2$s(s) export process has begun', 'wc-exporter-for-reviso' ), $n, $message_type ) ),
			);

			echo wp_json_encode( $response );
		}

		exit;
	}

	/**
	 * Delete a single customer/ supplier in Reviso
	 *
	 * @param  int    $user_number the user number in Reviso.
	 * @param  string $type        customer or supplier.
	 *
	 * @return void
	 */
	public function delete_remote_single_user( $user_number, $type ) {

		$output = $this->wcefr_call->call( 'delete', $type . '/' . $user_number );

		/*Log the error*/
		if ( ( isset( $output->errorCode ) || isset( $output->developerHint ) ) && isset( $output->message ) ) {

			error_log( 'WCEFR ERROR | Reviso user ' . $user_number . ' | ' . $output->message );
		}
	}

	/**
	 * Delete all customers/ suppliers in Reviso
	 *
	 * @return void
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['type'], $_POST['wcefr-delete-users-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcefr-delete-users-nonce'] ) ), 'wcefr-delete-users' ) ) {

			$response = array();
			$type     = sanitize_text_field( wp_unslash( $_POST['type'] ) );
			$users    = $this->get_remote_users( $type );

			$field_name = 'customers' === $type ? 'customerNumber' : 'supplierNumber';

			if ( isset( $users->collection ) && count( $users->collection ) > 0 ) {

				$n = 0;

				foreach ( $users->collection as $user ) {

					$n++;

					/*Cron event*/
					as_enqueue_async_action(
						'wcefr_delete_remote_single_user_event',
						array(
							'remote_user' => $user->$field_name,
							'user_type'   => $type,
						),
						'wcefr_delete_remote_single_user'
					);
				}

				$message_type = substr( $type, 0, -1 );
				$response[]   = array(
					'ok',
					/* translators: 1: users count 2: user type */
					esc_html( sprintf( __( '%1$d %2$s(s) delete process has begun', 'wc-exporter-for-reviso' ), $n, $message_type ) ),
				);

				echo wp_json_encode( $response );

			} else {

				$response[] = array(
					'error',
					/* translators: user type */
					esc_html( sprintf( __( 'ERROR! There are not %s to delete', 'wc-exporter-for-reviso' ), $type ) ),
				);

				echo wp_json_encode( $response );
			}
		}

		exit;
	}
}

new WCEFR_Users( true );

