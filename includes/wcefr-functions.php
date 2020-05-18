<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 0.9.0
 */

/**
 * Go premium button
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
