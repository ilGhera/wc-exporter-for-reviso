<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wcefr_ilghera_url' ) ) {

	/**
	 * Indirizzo di una pagina prodotto su ilghera.com: lingua dell'utente e tracciamento.
	 *
	 * @param string $slug     lo slug del prodotto, uguale nelle due lingue.
	 * @param string $campaign utm_campaign: settings (upgrade) o license (rinnovo).
	 *
	 * @return string
	 */
	function wcefr_ilghera_url( $slug, $campaign = 'settings' ) {

		$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$path   = ( 0 === strpos( $locale, 'it' ) ) ? 'it/prodotto' : 'product';

		return add_query_arg(
			array(
				'utm_source'   => 'wp-plugin',
				'utm_medium'   => 'wc-exporter-for-reviso',
				'utm_campaign' => $campaign,
			),
			'https://www.ilghera.com/' . $path . '/' . $slug . '/'
		);
	}
}

/**
 * Returns the string passed less long than the limit specified
 *
 * @param  string $text  the full text.
 * @param  int    $limit the string length limit.
 *
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
 * Go Premium button
 *
 * @return void
 */
function wcefr_go_premium() {

	$title       = __( 'This is a premium functionality, click here for more information', 'wc-exporter-for-reviso' );
	$output      = '<span class="wcefr label label-warning premium">';
		$output .= '<a href="' . esc_url( wcefr_ilghera_url( 'woocommerce-exporter-for-reviso-premium', 'settings' ) ) . '" target="_blank" title="' . esc_attr( $title ) . '">Premium</a>';
	$output     .= '</span>';

	$allowed = array(
		'span' => array(
			'class' => array(),
		),
		'a'    => array(
			'target' => array(),
			'title'  => array(),
			'href'   => array(),
		),
	);

	echo wp_kses( $output, $allowed );
}

