<?php
/**
 * Add the new fields to the checkout form
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */
class WCEFR_Checkout_Fields {

	/**
	 * The checkout fields added by the plugin
	 *
	 * @var array
	 */
	public $custom_fields;


	/**
	 * The constructor
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'add_checkout_script' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'set_custom_fields' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fields' ), 10, 2 );
		add_action( 'woocommerce_thankyou', array( $this, 'display_custom_data' ) );
		add_action( 'woocommerce_view_order', array( $this, 'display_custom_data' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_custom_data_in_admin' ) );
		add_filter( 'woocommerce_email_customer_details', array( $this, 'display_custom_data_in_email' ), 10, 4 );
		add_action( 'woocommerce_checkout_process', array( $this, 'checkout_fields_check' ) );
		add_action( 'show_user_profile', array( $this, 'extra_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'extra_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_extra_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_profile_fields' ) );

		$this->custom_fields = $this->get_active_custom_fields();
		$this->cf_mandatory  = get_option( 'wcefr_cf_mandatory' );
		$this->only_italy    = get_option( 'wcefr_only_italy' );
		$this->cf_only_italy = get_option( 'wcefr_cf_only_italy' );

	}


	/**
	 * Loading scripts
	 *
	 * @return void
	 */
	public function add_checkout_script() {
		wp_enqueue_script( 'wcefr-checkout-script', WCEFR_URI . 'js/wcefr-checkout.js' );
		wp_localize_script(
			'jwppp-select',
			'jwppp_select',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);

	}


	/**
	 * Checkout fields based on the options selected by the admin
	 *
	 * @return array
	 */
	public function get_active_custom_fields() {

		$output = array();

		$custom_fields = array(
			'billing_wcefr_piva'    => __( 'VAT number', 'wcefr' ),
			'billing_wcefr_cf'      => __( 'Fiscal code', 'wcefr' ),
			'billing_wcefr_pec'     => __( 'PEC', 'wcefr' ),
			'billing_wcefr_pa_code' => __( 'Receiver code', 'wcefr' ),
		);

		foreach ( $custom_fields as $key => $value ) {
			if ( get_option( $key . '_active' ) === '1' ) {
				$output[ $key ] = $value;
			}
		}

		return $output;
	}


	/**
	 * Add the custom fields to the WC index
	 *
	 * @param  array $fields the WC checkout fields.
	 * @return array
	 */
	public function set_custom_fields( $fields ) {

		$select = array(
			'private' => array(
				'active' => get_option( 'wcefr_private' ),
				'field'  => array( 'private' => __( 'Private (Receipt)', 'wcefr' ) ),
			),
			'private_invoice' => array(
				'active' => get_option( 'wcefr_private_invoice' ),
				'field' => array( 'private-invoice' => __( 'Private (Invoice)', 'wcefr' ) ),
			),
			'company_invoice' => array(
				'active' => get_option( 'wcefr_company_invoice' ),
				'field' => array( 'company-invoice' => __( 'Company (Invoice)', 'wcefr' ) ),
			),
		);

		/*The sum of documents activated by the admin*/
		$sum = ( $select['private']['active'] + $select['private_invoice']['active'] + $select['company_invoice']['active'] );

		if ( $sum > 1 ) {
			$fields['billing']['billing_wcefr_invoice_type'] = array(
				'type'    => 'select',
				'options' => array(),
				'label'   => __( 'Fiscal document', 'wcefr' ),
				'required'    => true,
				'class'   => array(
					'field-name form-row-wide',
				),
			);

			foreach ( $select as $key => $value ) {
				if ( '1' === $value['active'] ) {
					$label = key( $value['field'] );
					$fields['billing']['billing_wcefr_invoice_type']['options'][ $label ] = $value['field'][ $label ];
				}
			}
		}

		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $value ) {
				$fields['billing'][ $key ] = array(
					'type' => 'text',
					'label' => $value,
					'class' => array(
						'field-name form-row-wide',
					),
				);
			}

			if ( isset( $this->custom_fields['billing_wcefr_piva'] ) ) {
				$fields['billing']['billing_wcefr_piva']['required'] = true;
			}

			/*CF mandatory on page loading*/
			if ( isset( $this->custom_fields['billing_wcefr_cf'] ) ) {
				if ( ( 1 === $sum && ! isset( $select['private']['active'] ) || $sum > 1 ) ) {

					$fields['billing']['billing_wcefr_cf']['required'] = true;

				} elseif ( 1 === $sum && isset( $select['private']['active'] ) ) {
					if ( get_option( 'wcefr_cf_mandatory' ) ) {

						$fields['billing']['billing_wcefr_cf']['required'] = true;

					}
				}
			}

			/*CF and P.IVA mandatory if required*/
			if ( isset( $_POST['billing_wcefr_invoice_type'] ) ) {

				if ( 'private-invoice' === $_POST['billing_wcefr_invoice_type'] ) {

					$fields['billing']['billing_wcefr_piva']['required'] = false;

				} elseif ( 'private' === $_POST['billing_wcefr_invoice_type'] ) {

					$fields['billing']['billing_wcefr_piva']['required'] = false;

					if ( ! get_option( 'wcefr_cf_mandatory' ) ) {

						$fields['billing']['billing_wcefr_cf']['required'] = false;

					}
				}
			}

			/*Rendo obbligatorio cf e p. iva ed azienda solo quando richiesto*/
			if ( isset( $_POST['billing_wcefr_invoice_type'] ) ) {

				if ( 'private' !== $_POST['billing_wcefr_invoice_type'] ) {

					if ( ! isset( $this->custom_fields['billing_wcefr_pec'] ) && isset( $this->custom_fields['billing_wcefr_pa_code'] ) ) {

						$fields['billing']['billing_wcefr_pa_code']['required'] = true;

					} elseif ( isset( $this->custom_fields['billing_wcefr_pec'] ) && ! isset( $this->custom_fields['billing_wcefr_pa_code'] ) ) {

						$fields['billing']['billing_wcefr_pec']['required'] = true;

					}
				}
			}
		}

		return $fields;
	}


	/**
	 * Check if the CF/ P.Iva is valid or not
	 *
	 * @param  string $valore P.IVA or CF.
	 * @return bool
	 */
	public function fiscal_field_checker( $valore ) {
		$expression = '^[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]$';
		if ( is_numeric( $valore ) ) {
			$expression = '^[0-9]{11}$';
		}
		if ( preg_match( '/' . $expression . '/', $valore ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Check the fields values creating the order
	 *
	 * @return mixed WC notices in case of errors
	 */
	public function checkout_fields_check() {

		/*PEC or PA Code*/
		if ( isset( $_POST['billing_wcefr_invoice_type'] ) && 'private' !== $_POST['billing_wcefr_invoice_type'] ) {
			if ( isset( $this->custom_fields['billing_wcefr_pec'] ) && isset( $this->custom_fields['billing_wcefr_pa_code'] ) ) {
				$pec     = isset( $_POST['billing_wcefr_pec'] ) ? sanitize_email( wp_unslash( $_POST['billing_wcefr_pec'] ) ) : '';
				$pa_code = isset( $_POST['billing_wcefr_pa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_wcefr_pa_code'] ) ) : '';

				if ( ! $pec && ! $pa_code ) {
					wc_add_notice( __( 'The <strong> PEC </strong> field or the <strong> Receiver Code </strong> field must be completed.', 'wcefr' ), 'error' );
				}
			}
		}

		/*Fiscal fields check*/
		if ( get_option( 'wcefr_fields_check' ) ) {

			/*CF*/
			if ( isset( $_POST['billing_wcefr_cf'] ) && '' !== $_POST['billing_wcefr_cf'] && false === $this->fiscal_field_checker( sanitize_text_field( wp_unslash( $_POST['billing_wcefr_cf'] ) ) ) ) {
				wc_add_notice( 'WARNING! The <strong> Tax Code </strong> entered is incorrect.', 'error' );
			}

			/*P.IVA*/
			if ( isset( $_POST['billing_wcefr_invoice_type'] ) && 'company-invoice' === $_POST['billing_wcefr_invoice_type'] ) {
				if ( isset( $_POST['billing_wcefr_piva'] ) && '' !== $_POST['billing_wcefr_piva'] && false === $this->fiscal_field_checker( sanitize_text_field( wp_unslash( $_POST['billing_wcefr_piva'] ) ) ) ) {
					wc_add_notice( 'WARNING! The <strong> VAT number </strong> entered is incorrect.', 'error' );
				}
			}
		}
	}


	/**
	 * Save the custom fields values
	 *
	 * @param  object $order the current WC order.
	 * @param  array  $data  the WC order data.
	 */
	public function save_fields( $order, $data ) {

		if ( $this->custom_fields ) {
			foreach ( $this->custom_fields as $key => $value ) {
				if ( isset( $data[ $key ] ) ) {
					$order->update_meta_data( '_' . $key, sanitize_text_field( $data[ $key ] ) );
				}
			}
		}

	}


	/**
	 * Show the custom fields values in the WC thank you page and in the profile user page
	 *
	 * @param  int $order_id   the WC order id.
	 * @return mixed
	 */
	public function display_custom_data( $order_id ) {

		$order = wc_get_order( $order_id );

		echo '<h2>' . esc_html( __( 'Electronic invoicing', 'wcefr' ) ) . '</h2>';

		echo '<table class="shop_table shop_table_responsive">';
			echo '<tbody>';
		if ( $this->custom_fields ) {
			foreach ( $this->custom_fields as $key => $value ) {
				if ( $order->get_meta( '_' . $key ) ) {
					echo '<tr>';
						echo '<th width="40%">' . esc_html( $value ) . ':</th>';
						echo '<td>' . esc_html( $order->get_meta( '_' . $key ) ) . '</td>';
					echo '</tr>';
				}
			}
		}
			echo '</tbody>';
		echo '</table>';
	}


	/**
	 * Show the custom fields values in the back-end WC order
	 *
	 * @param  object $order the WC order.
	 */
	public function display_custom_data_in_admin( $order ) {

		if ( $this->custom_fields ) {
			foreach ( $this->custom_fields as $key => $value ) {
				if ( $order->get_meta( '_' . $key ) ) {
					echo '<p><strong>' . esc_html( $value ) . ': </strong><br>' . esc_html( $order->get_meta( '_' . $key ) ) . '</p>';
				}
			}
		}
	}


	/**
	 * Show the custom fields values in the confirmation email
	 *
	 * @param  object $order         the WC order.
	 * @param  bool   $sent_to_admin to be included also in the admin email, default is true.
	 * @param  boool  $plain_text    define if the mail must be sent with plain text.
	 * @param  string $email         the recipient's email.
	 * @return mixed
	 */
	public function display_custom_data_in_email( $order, $sent_to_admin, $plain_text, $email ) {

		if ( $this->custom_fields ) {

			echo '<h2>' . esc_html( __( 'Electronic invoicing', 'wcefr' ) ) . '</h2>';
			foreach ( $this->custom_fields as $key => $value ) {
				if ( $order->get_meta( '_' . $key ) ) {
					echo '<p style="margin: 0 0 8px;">' . esc_html( $value ) . ': <span style="font-weight: normal;">' . esc_html( $order->get_meta( '_' . $key ) ) . '</span></p>';
				}
			}
			echo '<div style="display: block; padding-bottom: 25px;"></div>';
		}

	}

	/**
	 * Add custom fields to user profile page
	 *
	 * @param  object $user user WP.
	 * @return mixed
	 */
	public function extra_user_profile_fields( $user ) {

		if ( $this->custom_fields ) {

			echo '<h3>' . esc_html__( 'Electronic invoicing', 'wcefr' ) . '</h3>';

			echo '<table class="form-table">';

				foreach ( $this->custom_fields as $key => $value ) {

					echo '<tr>';
						echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</label></th>';
						echo '<td>';
							echo '<input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( get_the_author_meta( $key, $user->ID ) ) . '" class="regular-text" />';
						echo '</td>';
					echo '</tr>';

				}

			echo '</table>';
		}

	}

	/**
	 * Edit custom fields in user profile page
	 *
	 * @param  int $user_id l'id dell'utente WP.
	 * @return mixed
	 */
	public function save_extra_user_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {

			return false;

		} else {

			if ( $this->custom_fields ) {

				foreach ( $this->custom_fields as $key => $value ) {

					$new_value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';

					if ( $new_value ) {

						update_user_meta( $user_id, $key, $new_value );

					}

				}

			}

		}

	}

}
new WCEFR_Checkout_Fields();
