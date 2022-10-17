<?php
/**
 * Export customer and suppliers to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.1.0
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
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

            /* Get options */
			$this->suppliers_role        = get_option( 'wcefr-suppliers-role' );
			$this->customers_role        = get_option( 'wcefr-customers-role' );

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
	 * Return the provinceNumer, required by Reviso for adding the province
	 *
	 * @param  string $code the two letters province code coming from WC.
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

        $transient = get_transient( 'wcefr-suppliers-groups' );

        if ( $transient ) {

            $output = $transient;

        } else {

            $output = $this->get_user_groups( 'suppliers' );

            set_transient( 'wcefr-suppliers-groups', $output, DAY_IN_SECONDS );
            
        }

		echo json_encode( $output );

		exit;

	}


	/**
	 * Callback - Get customers groups
	 */
	public function get_customers_groups() {

        $transient = get_transient( 'wcefr-customers-groups' );

        if ( $transient ) {

            $output = $transient;

        } else {

            $output = $this->get_user_groups( 'customers' );

            set_transient( 'wcefr-customers-groups', $output, DAY_IN_SECONDS );
            
        }

		echo json_encode( $output );

		exit;

	}


	/**
	 * Prepare the single user data to export to Reviso
	 *
	 * @param  int    $user_id   the WP user id.
	 * @param  string $type      customers or suppliers.
     * @param  object $order     the WC order to get the customer details.
     * @param  bool   $attention return the contact data with true.
	 * @return array
	 */
	public function prepare_user_data( $user_id, $type, $order = null, $attention = false ) {

		$type_singular = substr( $type, 0, -1 );

		if ( $user_id ) {

			$user_details = get_userdata( $user_id );

			$user_data = array_map(
				function( $a ) {
					return $a[0];
				},
				get_user_meta( $user_id )
			);

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
			$website                 = $user_details->user_url;
			$vat_number              = isset( $user_data['billing_wcefr_piva'] ) ? $user_data['billing_wcefr_piva'] : null;
			$identification_number   = isset( $user_data['billing_wcefr_cf'] ) ? $user_data['billing_wcefr_cf'] : null;
			$italian_certified_email = isset( $user_data['billing_wcefr_pec'] ) ? $user_data['billing_wcefr_pec'] : null;
			$public_entry_number     = isset( $user_data['billing_wcefr_pa_code'] ) ? $user_data['billing_wcefr_pa_code'] : null;

		} elseif ( $order ) {

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
			$vat_number              = $order->get_meta( '_billing_wcefr_piva' ) ? $order->get_meta( '_billing_wcefr_piva' ) : null;
			$identification_number   = $order->get_meta( '_billing_wcefr_cf' ) ? $order->get_meta( '_billing_wcefr_cf' ) : null;
			$italian_certified_email = $order->get_meta( '_billing_wcefr_pec' ) ? $order->get_meta( '_billing_wcefr_pec' ) : null;
			$public_entry_number     = $order->get_meta( '_billing_wcefr_pa_code' ) ? $order->get_meta( '_billing_wcefr_pa_code' ) : null;

		} else {

			return;

        }

        /* Customer contact */
        if ( $attention ) {

            return $company ? $contact : false;

        }

        $base_location = wc_get_base_location();
        $shop_country  = is_array( $base_location ) && isset( $base_location['country'] ) ? $base_location['country'] : null;

		/*Reviso VatZone based on user country */
		$vat_zone = $shop_country === $country ? 1 : 3;

		/*Reviso's group selected by the admin*/
		if ( $order ) {
	
            $get_customers_groups = get_option( 'wcefr-orders-customers-group' );

            if ( 0 === intval( $get_customers_groups ) ) {

                /* By nationality */
                $group = $shop_country === $country ? 1 : 2;

            } else {

                /* Custom group */
                $group = $get_customers_groups;

            }

		} else {

			$group = get_option( 'wcefr-' . $type . '-group' );

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
			'vatZone' => array(
				'vatZoneNumber' => $vat_zone,
			),
			'paymentTerms'           => array(
				'paymentTermsNumber' => 6,
			),
			'countryCode'            => array(
				'code' => $country,
			),
			$type_singular . 'Group' => array(
				$type_singular . 'GroupNumber' => intval( $group ),
			),
		);

		if ( 'IT' === $country ) {
			$args['province'] = array(
				'countryCode' => array(
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
     * Get the customer contact number from Reviso or create it if necessary
     *
     * @param int    $remote_id the customer id in Reviso.
     * @param string $contact_name the customer contact name.
     *
     * @return int  
     */
    public function get_customer_contact_number( $remote_id, $contact_name ) {

        /* Get customer contacts */
        $contacts       = $this->wcefr_call->call( 'get', 'customers/' . $remote_id . '/contacts' );

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
                'name' => $contact_name,
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
	 * @return void
	 */
	public function export_single_user( $user_id, $type, $order = null, $new = false, $remote_id = null ) {

        $args = $this->prepare_user_data( $user_id, $type, $order );

        if ( $new ) {

            $remote_id = false;

        } else {

            /* Check if the remote user exists if $new is not specified */
            $remote_id = $remote_id ? $remote_id : $this->user_exists( $type, $args['email'] );
        
        }

		if ( $args ) {

            $contact_name = null;

            if ( 'customers' === $type ) {

                $contact_name = $this->prepare_user_data( $user_id, $type, $order, true );

            }

			if ( ! $remote_id ) {

				$output = $this->wcefr_call->call( 'post', $type . '/', $args );

                if ( isset( $output->customerNumber ) ) {

                    $remote_id = $output->customerNumber;

                    /* Add the customer contact name in case of company */
                    if ( $contact_name ) {

                        $contact_number = $this->get_customer_contact_number( $remote_id, $contact_name );
                        
                        $args['attention'] = array(
                            'customerContactNumber' => $contact_number,
                        );

                    }

                    /* Update the customer */
                    $output = $this->wcefr_call->call( 'put', $type . '/' . $remote_id, $args );

                }


			} else {

                /* Add the customer contact name in case of company */
                if ( $contact_name ) {

                    $contact_number = $this->get_customer_contact_number( $remote_id, $contact_name );
                    
                    $args['attention'] = array(
                        'customerContactNumber' => $contact_number,
                    );

                }

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

		if ( isset( $_POST['wcefr-users-role-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['wcefr-users-role-nonce'] ), 'wcefr-users-role' ) ) {

			$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';

			/* Save in the DB */
			$output = update_option( 'wcefr-' . $type . '-role', $role );

            if ( $output ) {

                echo __( 'Saved!', 'wc-exporter-for-reviso' );

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

		if ( isset( $_POST['wcefr-export-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['wcefr-export-users-nonce'] ), 'wcefr-export-users' ) ) {

			$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
			$group = isset( $_POST['group'] ) ? sanitize_text_field( wp_unslash( $_POST['group'] ) ) : '';

			/* Save in the DB */
			update_option( 'wcefr-' . $type . '-group', $group );

			$args     = array( 'role' => $role );
			$users    = get_users( $args );
			$response = array();

			if ( $users ) {

				$n = 0;

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
			$response[] = array(
				'ok',
				/* translators: 1: users count 2: user type */
				esc_html( sprintf( __( '%1$d %2$s(s) export process has begun', 'wc-exporter-for-reviso' ), $n, $message_type ) ),
			);

			echo json_encode( $response );

		}

		exit;
	}


	/**
	 * Delete a single customer/ supplier in Reviso
	 *
	 * @param  int    $user_number the user number in Reviso.
	 * @param  string $type        customer or supplier.
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
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['type'], $_POST['wcefr-delete-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['wcefr-delete-users-nonce'] ), 'wcefr-delete-users' ) ) {

			$response = array();
			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
			$users = $this->get_remote_users( $type );

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
				$response[] = array(
					'ok',
					/* translators: 1: users count 2: user type */
					esc_html( sprintf( __( '%1$d %2$s(s) delete process has begun', 'wc-exporter-for-reviso' ), $n, $message_type ) ),
				);

				echo json_encode( $response );

			} else {

				$response[] = array(
					'error',
					/* translators: user type */
					esc_html( sprintf( __( 'ERROR! There are not %s to delete', 'wc-exporter-for-reviso' ), $type ) ),
				);

				echo json_encode( $response );

			}

		}

		exit;

	}
}
new WCEFR_Users( true );
