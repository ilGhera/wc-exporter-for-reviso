<?php
/**
 * Handles the API calls
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

class wcefrCall {

	private $base_url = 'https://rest.reviso.com/';


	/**
	 * Get the Agreement Token from the db
	 * @return string
	 */
	private function get_agreement_grant_token() {

		$output = get_option( 'wcefr-agt' ); 
		return $output;

	}


	/**
	 * Define the headers to use in every API call
	 * @return array
	 */
	public function headers() {

		$output = array(
			'X-AppSecretToken' => 'rqxTsPjvhLfKdbw29IOUdxNl1sIrYNsEKZ6RRIXhlyE1', //temp
			'X-AgreementGrantToken' => $this->get_agreement_grant_token(),
		  	'Content-Type' => 'application/json',
		);

		return $output;
	}


	/**
	 * The call
	 * @param  string $method   could be GET, POST, DELETE or PUT
	 * @param  string $endpoint the endpoint's name
	 * @param  array  $args     the data
	 * @param  bool   $decode   json_dedcode if true
	 * @return mixed  the response
	 */
	public function call( $method, $endpoint = '', $args = null, $decode = true ) {

		// error_log( 'ARGS: ' .  print_r( $args, true ) );

		$body = $args ? json_encode( $args ) : '';

		// error_log( 'ARGS: ' .  print_r( $body, true ) );

		$response = wp_remote_request(

			$this->base_url . $endpoint, 
			array( 
				'method' => $method,
				'headers' => $this->headers(),
				'timeout' => 20,
				'body'    => $body,
			)

		);

		if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {

			// error_log( 'WCEFR | PDF: ' . print_r( $response, true ) );

			$output = $decode ? json_decode( $response['body'] ) : $response['body'];

			if ( isset( $output->errors ) || isset( $output->errorCode ) ) {
				
				error_log( 'WCEFR | ERROR: ' . print_r( $output, true ) );

			}
			
			return $output;

		} else {

			/*Print the error to the log*/
			error_log( 'WCEFR | WP ERROR: ' . print_r( $response, true ) );

		}

	}

}
