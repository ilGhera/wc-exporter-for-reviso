<?php /**
 * Admin
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

class wcefrAdmin {


	public function __construct() {

		add_action( 'admin_menu', array( $this, 'wcefr_add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this , 'wcefr_register_scripts' ) );

		// $connect = $this->connect();
		// add_action( 'admin_init', array( $this, 'connect' ) );
	}


	/**
	 * Registrazione script necessario al menu di navigazione
	 */
	function wcefr_register_scripts() {

		$screen = get_current_screen();
		if ( $screen->id === 'woocommerce_page_wc-exporter-for-reviso' ) {

			/*js*/
			wp_enqueue_script( 'wcefr-js', WCEFR_URI . 'js/wcefr.js', array( 'jquery' ), '1.0', true );
		    wp_enqueue_script('bootstrap-js', plugin_dir_url(__DIR__) . 'js/bootstrap.min.js');
			// wp_enqueue_script( 'tzcheckbox', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );
			// wp_enqueue_script( 'tzcheckbox-script', WCEFR_URI . 'js/tzCheckbox/js/script.js', array( 'jquery' ) );
		
			/*css*/
			wp_enqueue_style( 'wcefr-style', WCEFR_URI . 'css/wc-exporter-for-reviso.css' );
		    wp_enqueue_style('bootstrap-iso', plugin_dir_url(__DIR__) . 'css/bootstrap-iso.css');
			// wp_enqueue_style( 'tzcheckbox-style', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );

		}

	}


	/**
	 * Voce di menu
	 */
	public function wcefr_add_menu() {
		$wcefr_page = add_submenu_page( 'woocommerce', 'WCEFR Options', 'WC Exporter for Reviso', 'manage_woocommerce', 'wc-exporter-for-reviso', array( $this, 'wcefr_options' ) );

		return $wcefr_page;
	}


	/**
	 * Pagina opzioni
	 */
	public function wcefr_options() {

		/*Controllo se l'utente ha i diritti d'accessso necessari*/
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'It seems like you don\'t have permission to see this page', 'wcefr' ) );
		}


		/*Inizio template di pagina*/
		echo '<div class="wrap">';
			echo '<div class="wrap-left">';

				/*Controllo se woocommerce e' installato*/
				if ( ! class_exists( 'WooCommerce' ) ) {
					echo '<div id="message" class="error">';
						echo '<p>';
							echo '<strong>' . __( 'ATTENTION! It seems like Woocommerce is not installed.', 'wcefr' ) . '</strong>';
						echo '</p>';
					echo '</div>';
					exit;
				}

				echo '<div id="wcefr-generale">';

					/*Header*/
					echo '<h1 class="wcefr main">' . __( 'WooCommerce Exporter for Reviso - Premium', 'wcefr' ) . '</h1>';

						echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>';
						echo '<h2 id="wcefr-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
							echo '<a href="#" data-link="wcefr-settings" class="nav-tab nav-tab-active" onclick="return false;">' . __( 'Settings', 'wcefr' ) . '</a>';
							echo '<a href="#" data-link="wcefr-suppliers" class="nav-tab" onclick="return false;">' . __( 'Suppliers', 'wcefr' ) . '</a>';
							echo '<a href="#" data-link="wcefr-products" class="nav-tab" onclick="return false;">' . __( 'Products', 'wcefr' ) . '</a>';
							echo '<a href="#" data-link="wcefr-customers" class="nav-tab" onclick="return false;">' . __( 'Customers', 'wcefr' ) . '</a>';
							echo '<a href="#" data-link="wcefr-orders" class="nav-tab" onclick="return false;">' . __( 'Orders', 'wcefr' ) . '</a>';
						echo '</h2>';


						/*Settings*/
						echo '<div id="wcefr-settings" class="wcefr-admin" style="display: block;">';

							include( WCEFR_ADMIN . 'wcefr-settings-template.php' );

						echo '</div>';


						/*Cusomers*/
						echo '<div id="wcefr-suppliers" class="wcefr-admin">';

							include( WCEFR_ADMIN . 'wcefr-suppliers-template.php' );

						echo '</div>';


						/*Cusomers*/
						echo '<div id="wcefr-products" class="wcefr-admin">';

							include( WCEFR_ADMIN . 'wcefr-products-template.php' );

						echo '</div>';


						/*Cusomers*/
						echo '<div id="wcefr-customers" class="wcefr-admin">';

							include( WCEFR_ADMIN . 'wcefr-customers-template.php' );

						echo '</div>';

						/*Cusomers*/
						echo '<div id="wcefr-orders" class="wcefr-admin">';

							include( WCEFR_ADMIN . 'wcefr-orders-template.php' );

						echo '</div>';

				echo '</div>';

				/*Utilizzato per mostrare messaggio all'admin*/
				echo '<div class="wcefr-message"></div>';

			echo '</div>';
		
			echo '<div class="wrap-right">';
				echo '<!-- <iframe width="300" height="900" scrolling="no" src="https://www.ilghera.com/images/wcefr-premium-iframe.html"></iframe> -->';
			echo '</div>';
			
			echo '<div class="clear"></div>';

		echo '</div>';

	}


	public function connect() {

		if ( isset( $_POST['api'] ) ) {

			$add_customer = array(
				'name' => 'Franco Bianchi',
				// 'italianCustomerType' => 'Consumer',
				'email' => 'franco@bianchi.it',
				'customerGroup' => array(
					'customerGroupNumber' => 1,
				),
				'currency' => 'EUR',
				'country' => 'Italia',
				'City' => 'Varese',
				'address' => 'via Rossi, 33',
				'zip' => '21100',
				'vatNumber' => '02847000128',
				'vatZone' => array(
					'vatZoneNumber' => 1,
				),
				'paymentTerms' => array(
					'paymentTermsNumber' => 6,
				),
				// 'xxxxx' => 'xxxxxxx',
			);

			$delete_customer = array(
				'customerNumber' => 2,
			);

			$redirect = admin_url( 'page=reviso-for-wc' );

			// $test = wp_remote_get( 'https://rest.reviso.com/customers', array( 'headers' => $headers ) );
			// $test = wp_remote_get( 'https://rest.reviso.com/v2/invoices/', array( 'headers' => $headers ) );
			// $test = wp_remote_get( 'https://rest.reviso.com/orders/', array( 'headers' => $headers ) );
			// $test = wp_remote_post( 
			
			// 	'https://rest.reviso.com/orders/', 
			// 	array( 
			// 		'headers' => $headers ,
			// 		'body'    => array(

			// 		),
			// 	),

			// );
			// 
		


			$test = new wcefrCall( 
				
				'post',
				'customers', 
				$add_customer

			);

			// error_log( 'Response: ' . print_r( $test['body'], true ) );
		
		}


	}

}
new wcefrAdmin;