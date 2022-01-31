<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.0.0
 */
class WCEFR_Settings {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_ajax_wcefr-check-connection', array( $this, 'check_connection_callback' ) );
			add_action( 'wp_ajax_wcefr-disconnect', array( $this, 'disconnect_callback' ) );
			add_action( 'wp_ajax_wcefr-clear-cache', array( $this, 'clear_cache' ) );
			add_action( 'admin_footer', array( $this, 'save_agt' ) );

		}

		$this->wcefr_call = new WCEFR_Call();

	}

	/**
	 * Scripts and style sheets
	 *
	 * @return void
	 */
	public function enqueue() {

		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-exporter-for-reviso' === $screen->id ) {
            wp_enqueue_script( 'chosen', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.jquery.min.js' );
            wp_enqueue_script( 'tzcheckbox', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );

            wp_enqueue_style( 'chosen-style', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.min.css' );
            wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.8.1/css/all.css' );
            wp_enqueue_style( 'tzcheckbox-style', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );
        }

	}


	/**
	 * Check if the current page is the plugin options page
	 *
	 * @return boolean
	 */
	public function is_wcefr_admin() {

		$screen = get_current_screen();

		if ( isset( $screen->id ) && 'woocommerce_page_wc-exporter-for-reviso' === $screen->id ) {
			return true;
		}

	}


	/**
	 * Save the Agreement Grant Token in the db
	 *
	 * @return void
	 */
	public function save_agt() {

		if ( $this->is_wcefr_admin() && isset( $_GET['token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );

			update_option( 'wcefr-agt', $token );
		}

	}


	/**
	 * Deletes the Agreement Grant Token from the db
	 *
	 * @return void
	 */
	public function disconnect_callback() {

		delete_option( 'wcefr-agt' );

		exit;

	}


	/**
	 * Display the status of the connection to Reviso
	 *
	 * @param bool $return if true the method returns only if the connection is set.
	 * @return mixed
	 */
	public function check_connection_callback( $return = false ) {

		$response = $this->wcefr_call->call( 'get', 'self' );
        
		if ( isset( $response->httpStatusCode ) && isset( $response->message ) ) {

			echo false;

		} elseif ( isset( $response->application->appNumber ) ) {

			if ( $return ) {

				return true;

			} else {

				echo '<h4 class="wcefr-connection-status"><span class="label label-success">' . esc_html( __( 'Connected', 'wc-exporter-for-reviso' ) ) . '</span></h4>';

			}

		}

		exit;

	}

    /**
     * Clear the temporary date saved in the db
     *
     * @return void
     */
    public function clear_cache() {

        $transients = array( 
            'wcefr-suppliers-groups',
            'wcefr-customers-groups',
            'wcefr-payment-methods',
            'wcefr-additional-expenses',
            'wcefr-inventory-module',
            'wcefr-departmental-distribution',
            'wcefr-vat-code',
            'wcefr-vat-rate',
        );

        foreach ( $transients as $transient ) {

            delete_transient( $transient );

        }

        /* Response message */
        $response[] = array(
            'ok',
            /* translators: Transients deleted */
            esc_html__( 'Temporary data were deleted' ),
        );

        echo json_encode( $response );

        exit;
    } 

}
new WCEFR_Settings( true );

