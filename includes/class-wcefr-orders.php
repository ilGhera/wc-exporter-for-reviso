<?php
/**
 * Export orders to reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.3
 */
class WCEFR_Orders {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$this->number_series_prefix = get_option( 'wcefr-number-series-prefix' );

		}

		$this->wcefr_call = new WCEFR_Call();

	}


	/**
	 * Get a specific number sirie from Reviso
	 *
	 * @param  string $prefix     example are FVE, FVL, ecc.
	 * @param  string $entry_type used to filter the number series.
	 * @param  bool   $first      if true returns the numberSeriesNumber of the first result, otherwise all the array.
	 * @return mixed
	 */
	public function get_remote_number_series( $prefix = null, $entry_type = null, $first = false ) {

		if ( $prefix ) {

			/*Used for invoices*/
			$response = $this->wcefr_call->call( 'get', 'number-series?filter=prefix$eq:' . $prefix );

		} elseif ( $entry_type ) {

			$response = $this->wcefr_call->call( 'get', 'number-series?filter=entryType$eq:' . $entry_type );

		} else {

			$response = $this->wcefr_call->call( 'get', 'number-series' );

		}

		if ( isset( $response->collection ) ) {

			if ( $first && isset( $response->collection[0]->numberSeriesNumber ) ) {

				return $response->collection[0]->numberSeriesNumber;

			} else {

				return $response->collection;

			}

		}

	}

}
new WCEFR_Orders( true );
