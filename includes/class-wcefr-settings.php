<?php
/**
 * Impostazioni generali
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrSettings {

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_check-connection', array( $this, 'check_connection_callback') );
		add_action( 'wp_ajax_wcefr-disconnect', array( $this, 'disconnect_callback') );
		add_action( 'admin_footer', array( $this, 'check_connection') );
		add_action( 'admin_footer', array( $this, 'save_agt' ) );

		$this->wcefrCall = new wcefrCall();

	}

	/**
	 * Script e fogli di stile
	 */
	public function enqueue() {

		wp_enqueue_script( 'chosen', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.jquery.min.js' );
		wp_enqueue_script( 'tzcheckbox', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );

		wp_enqueue_style( 'chosen-style', WCEFR_URI . '/vendor/harvesthq/chosen/chosen.min.css' );
	    wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.8.1/css/all.css' );
		wp_enqueue_style( 'tzcheckbox-style', WCEFR_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );

	}


	/**
	 * Verifica che la pagina corrente sia quella delle opzioni del plugin
	 * @return boolean
	 */
	public function is_wcefr_admin() {

		$screen = get_current_screen();

		if ( isset( $screen->id ) && $screen->id === 'woocommerce_page_wc-exporter-for-reviso' ) {
			return true;
		}

	}


	/**
	 * Salva l'Agreement Grant Tocken dell'admin nel db
	 */
	public function save_agt() {

		if ( $this->is_wcefr_admin() && isset( $_GET['token'] ) ) {
			$token = sanitize_text_field( $_GET['token'] );

			update_option( 'wcefr-agt', $token );
		}

	}


	public function check_connection() {

		if ( $this->is_wcefr_admin() ) {
			?>	
			<script>
				jQuery(document).ready(function($){
					var Controller = new wcefrController;
					Controller.wcefr_check_connection();
					// wcefr_check_connection();
				})
			</script>
			<?php
		}
	}


	public function disconnect_callback() {

		delete_option( 'wcefr-agt', $token );

		exit;

	}


	public function check_connection_callback() {
			
		$response = json_decode( $this->wcefrCall->call( 'get', 'self' ) );
		
		if ( isset( $response->application->appNumber ) && 2891 ===  $response->application->appNumber ) {

			echo '<h4 class="wcefr-connection-status"><span class="label label-success">' . __( 'Connected', 'wcefr' ) . '</span></h4>'; 
		
		}		

		exit;
	}

}
new wcefrSettings;
