<?php
/**
 * Export orders to reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.1.0
 */
class WCEFR_Orders {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			$this->number_series_prefix          = get_option( 'wcefr-number-series-prefix' );
			$this->number_series_prefix_receipts = get_option( 'wcefr-number-series-receipts-prefix' ); 

		}

		$this->wcefr_call = new WCEFR_Call();

	}


	/**
	 * Get a specific number serie from Reviso
	 *
	 * @param  string $prefix     examples are FVE, FVL, ecc.
	 * @param  string $entry_type used to filter the number series.
	 * @param  bool   $first      if true returns the numberSeriesNumber of the first result, otherwise all the array.
	 * @return mixed
	 */
	public function get_remote_number_series( $prefix = null, $entry_type = null, $first = false ) {

		if ( $prefix ) {

            $transient_name = 'wcefr-number-series-prefix';
			$args           = '?filter=prefix$eq:' . $prefix;

		} elseif ( $entry_type ) {

            $transient_name = 'wcefr-number-series-type';
			$args           = '?filter=entryType$eq:' . $entry_type;

		} else {

            $transient_name = 'wcefr-number-series';
			$args           = null;

		}

        /* Get the transient */
        $transient = get_transient( $transient_name );

        if ( $transient ) {

            $response = $transient;

        } else {

            $response  = $this->wcefr_call->call( 'get', 'number-series' . $args );

        }
            
        if ( isset( $response->collection ) ) {

            if ( ! $transient ) {

                /* Set the transient */
                set_transient( $transient_name, $response, DAY_IN_SECONDS );
                
            }

            if ( $first && isset( $response->collection[0]->numberSeriesNumber ) ) {

                return $response->collection[0]->numberSeriesNumber;

            } else {

                return $response->collection;

            }

        }

	}

}
new WCEFR_Orders( true );

