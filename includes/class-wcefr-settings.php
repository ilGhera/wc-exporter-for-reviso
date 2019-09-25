<?php
/**
 * General settings
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrSettings {

	/**
	 * Class constructor
	 * @param boolean $init fire hooks if true
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_ajax_check-connection', array( $this, 'check_connection_callback') );
			add_action( 'wp_ajax_wcefr-disconnect', array( $this, 'disconnect_callback') );
			add_action( 'admin_footer', array( $this, 'check_connection') );
			add_action( 'admin_footer', array( $this, 'save_agt' ) );

		}

		$this->wcefrCall = new wcefrCall();
		// $this->connected = $this->check_connection_callback( true );
	}

	/**
	 * Scripts and style sheets
	 * @return void
	 */
	public function enqueue() {

		wp_enqueue_script( 'chosen', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.jquery.min.js' );
		wp_enqueue_script( 'tzcheckbox', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );

		wp_enqueue_style( 'chosen-style', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.min.css' );
	    wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.8.1/css/all.css' );
		wp_enqueue_style( 'tzcheckbox-style', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );

	}


	/**
	 * Check if the current page is the plugin options page
	 * @return boolean
	 */
	public function is_wcefr_admin() {

		$screen = get_current_screen();

		if ( isset( $screen->id ) && $screen->id === 'woocommerce_page_wc-exporter-for-reviso' ) {
			return true;
		}

	}


	/**
	 * Save the Agreement Grant Token in the db
	 * @return void
	 */
	public function save_agt() {

		if ( $this->is_wcefr_admin() && isset( $_GET['token'] ) ) {
			$token = sanitize_text_field( $_GET['token'] );

			update_option( 'wcefr-agt', $token );
		}

	}


	/**
	 * Check the connection to Reviso
	 * @return void
	 */
	public function check_connection() {

		if ( $this->is_wcefr_admin() ) {
			?>	
			<script>
				jQuery(document).ready(function($){
					var Controller = new wcefrController;
					Controller.wcefr_check_connection();
				})
			</script>
			<?php
		}
	}


	/**
	 * Deletes the Agreement Grant Token from the db
	 * @return void
	 */
	public function disconnect_callback() {


		$output = delete_option( 'wcefr-agt' );
		error_log( 'DEL: ' . $output );

		exit;

	}


	/**
	 * Display the status of the connection to Reviso
	 * @return mixed
	 */
	public function check_connection_callback( $return = false ) {
			
		$response = $this->wcefrCall->call( 'get', 'self' );

		error_log( 'CONNECT: ' . print_r( $response, true ) );
		
		if ( isset( $response->application->appNumber ) && 2891 ===  $response->application->appNumber ) {

			if ( $return ) {

				return true;
			
			} else {
				
				echo '<h4 class="wcefr-connection-status"><span class="label label-success">' . __( 'Connected', 'wcefr' ) . '</span></h4>'; 

			}
		
		} elseif ( $return ) {

			return false;

		}

		exit;
	}

}
new wcefrSettings( true );
