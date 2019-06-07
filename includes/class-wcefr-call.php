<?php
/**
 * Gestisce le chiamate alle API Revisio
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrCall {

	private $base_url = 'https://rest.reviso.com/';


	/**
	 * Recupera il tocket dal db
	 */
	private function get_agreement_grant_token() {

		$output = get_option( 'wcefr-agt' ); 
		return $output;

	}


	public function headers() {

		$output = array(
			'X-AppSecretToken' => 'rqxTsPjvhLfKdbw29IOUdxNl1sIrYNsEKZ6RRIXhlyE1',
			'X-AgreementGrantToken' => $this->get_agreement_grant_token(),
		  	'Content-Type' => 'application/json',
		);

		return $output;
	}


	/**
	 * Esegue la chiamata all'endpoint dato
	 * @param string $method   il tipo di chiamata
	 * @param string $endpoint il nome dell'endpoint
	 * @param array  $args     i dati da inviare
	 */
	public function call( $method, $endpoint, $args = null ) {

		$body = $args ? json_encode( $args ) : '';

		$response = wp_remote_request(

			$this->base_url . $endpoint, 
			array( 
				'method' => $method,
				'headers' => $this->headers(),
				'body'    => $body,
			)

		);

		// error_log( $endpoint );


		// if ( $endpoint == 'products' ) {
			if ( $method == 'post' ) {
				error_log( 'Response: ' . print_r( $response['body'], true ) );
				// error_log( 'Response: ' . print_r( $response, true ) );
			}
		// }

		return $response['body'];

	}

}
