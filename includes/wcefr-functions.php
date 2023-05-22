<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.3.0
 */

/**
 * Returns the string passed less long than the limit specified
 *
 * @param  string $text  the full text.
 * @param  int    $limit the string length limit.
 * @return string
 */
function avoid_length_exceed( $text, $limit ) {

	$output = $text;

	if ( strlen( $text ) > $limit ) {

		if ( 25 === intval( $limit ) ) {

			/*Product number (sku)*/
			$output = substr( $text, 0, $limit );

		} else {

			/*Product name and description*/
			$output = substr( $text, 0, ( $limit - 4 ) ) . ' ...';

		}
	}

	return $output;

}


/**
 * Update checker
 */
require WCEFR_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wcefr_update_checker = PucFactory::buildUpdateChecker(
	'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=wc-exporter-for-reviso-premium',
	WCEFR_FILE,
	'wc-exporter-for-reviso-premium'
);


/**
 * Secure update check with the Premium Key
 *
 * @param  array $query_args the default args.
 * @return array            the updated args
 */
function wcefr_secure_update_check( $query_args ) {

	$key = base64_encode( get_option( 'wcefr-premium-key' ) );

	if ( $key ) {

		$query_args['premium-key'] = $key;

	}

	return $query_args;

}
$wcefr_update_checker->addQueryArgFilter( 'wcefr_secure_update_check' );


/**
 * Plugin update message
 *
 * @param  array $plugin_data plugin information.
 * @param  array $response    available plugin update information.
 */
function wcefr_update_message( $plugin_data, $response ) {

	$key = get_option( 'wcefr-premium-key' );

	$message = null;

	if ( ! $key ) {

		$message = 'A <b>Premium Key</b> is required for keeping this plugin up to date. Please, add yours in the <a href="' . WCEFR_SETTINGS . '">options page</a> or click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

	} else {

		$decoded_key = explode( '|', base64_decode( $key ) );
		$bought_date = date( 'd-m-Y', strtotime( $decoded_key[1] ) );
		$limit       = strtotime( $bought_date . ' + 365 day' );
		$now         = strtotime( 'today' );

		if ( $limit < $now ) {

			$message = 'It seems like your <strong>Premium Key</strong> is expired. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

		} elseif ( '7082' !== $decoded_key[2] ) {

			$message = 'It seems like your <strong>Premium Key</strong> is not valid. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

		}
	}

	$allowed_tags = array(
		'strong' => array(),
		'a'      => array(
			'href'   => array(),
			'target' => array(),
		),
	);

	echo ( $message ) ? '<br><span class="wcefr-alert">' . wp_kses( $message, $allowed_tags ) . '</span>' : '';

}
add_action( 'in_plugin_update_message-' . WCEFR_DIR_NAME . '/wc-exporter-for-reviso.php', 'wcefr_update_message', 10, 2 );
