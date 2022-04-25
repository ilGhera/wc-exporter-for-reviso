<?php
/**
 * Export products to Reviso
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.0.0
 */
class WCEFR_Products {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct() {

		$this->wcefr_call = new WCEFR_Call();

	}


    /**
     * Check if the dimension module is active
     *
     * @return bool
     */
    public function dimension_module() {

        $output    = false;
        $transient = get_transient( 'wcefr-dimension-module' );

        if ( $transient ) {

            $output = $transient;

        } else {

            $response  = $this->wcefr_call->call( 'get', 'self' );

            if ( is_object( $response ) && isset( $response->modules ) ) {

                if ( is_array( $response->modules ) ) {
                    
                    foreach ( $response->modules as $module ) {

                        if ( 0 === strpos( $module->name, 'Dimension' ) ) {

                            $output = true;

                            continue;

                        }

                    } 

                    set_transient( 'wcefr-dimension-module', $output, DAY_IN_SECONDS );

                }

            }

        }

        return $output;

    }


    /**
     * Get alle the remote departmental distributions
     *
     * @return array
     */
    public function get_remote_departmental_distributions() {

        $transient = get_transient( 'wcefr-departmental-distribution' );

        if ( $transient ) {

            $output = $transient;

        } else {

            $response = $this->wcefr_call->call( 'get', 'departmental-distributions' );

            if ( ( isset( $response->collection ) && empty( $response->collection ) ) || isset( $response->errorCode ) ) {

                $output = false;

            } else {

                set_transient( 'wcefr-departmental-distribution', $response->collection, DAY_IN_SECONDS );

                $output = $response->collection;

            }
            
        }

		return $output;

    }

}
new WCEFR_Products( true );

