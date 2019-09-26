<?php
/**
 * Functions
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

/**
 * Update checker
 */
require( WCEFR_DIR . 'plugin-update-checker/plugin-update-checker.php' );

$wcefr_update_checker = Puc_v4_Factory::buildUpdateChecker(
   
    'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-exporter-for-reviso-premium',
    WCEFR_FILE,
    'wc-exporter-for-reviso-premium'

);


/**
 * Secure update check with the Premium Key
 * @param  array $queryArgs the default args
 * @return array            the updated args
 */
function wcefr_secure_update_check( $queryArgs ) {

    $key = base64_encode( get_option( 'wcefr-premium-key' ) );

    if ( $key ) {

        $queryArgs['premium-key'] = $key;

    }

    return $queryArgs;

}
$wcefr_update_checker->addQueryArgFilter( 'wcefr_secure_update_check' );


/**
 * Plugin update message
 * @param  array  $plugin_data plugin information
 * @param  array  $response    available plugin update information
 * @return string              the message shown to the publisher
 */
function wcefr_update_message( $plugin_data, $response ) {
	
	$key = get_option( 'wcefr-premium-key' );
	
	$message = null;

	if ( ! $key ) {

		$message = 'A <b>Premium Key</b> is required for keeping this plugin up to date. Please, add yours in the <a href="' . WCEFR_SETTINGS . '">options page</a> or click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';
	
	} else {
	
		$decoded_key = explode( '|', base64_decode( $key ) );
	    $bought_date = date( 'd-m-Y', strtotime( $decoded_key[1] ) );
	    $limit = strtotime( $bought_date . ' + 365 day' );
	    $now = strtotime( 'today' );

	    if ( $limit < $now ) { 
	       
	        $message = 'It seems like your <strong>Premium Key</strong> is expired. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';
	    
	    } elseif ( $decoded_key[2] !== '7082' ) {
	    	
	    	$message = 'It seems like your <strong>Premium Key</strong> is not valid. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';
	    
	    }

	}

	$allowed_tags = array(
		'strong' => [],
		'a'		 => [
			'href'   => [],
			'target' => []
		]
	);

	echo ( $message ) ? '<br><span class="wcefr-alert">' . wp_kses( $message, $allowed_tags ) . '</span>' : '';

}
add_action( 'in_plugin_update_message-' . WCEFR_DIR_NAME . '/wc-exporter-for-reviso.php', 'wcefr_update_message', 10, 2 );
