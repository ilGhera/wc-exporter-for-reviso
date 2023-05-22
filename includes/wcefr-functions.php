<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.1
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
 * Go Premium button
 *
 * @return void 
 */
function wcefr_go_premium() {

	$title = __( 'This is a premium functionality, click here for more information', 'wc-exporter-for-reviso' );
	$output = '<span class="wcefr label label-warning premium">';
		$output .= '<a href="https://www.ilghera.com/product/woocommerce-exporter-for-reviso-premium" target="_blank" title="' . esc_attr( $title ) . '">Premium</a>';
	$output .= '</span>';

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

