<?php
/**
 * Plugin Name: WooCommerce Exporter for Reviso
 * Plugin URI: https://www.ilghera.com/product/woocommerce-exporter-for-reviso
 * Description: xxxxxxxx
 * Author: ilGhera
 * Version: 0.0.9
 * Author URI: https://ilghera.com
 * Requires at least: 4.0
 * Tested up to: 5
 * WC tested up to: 3
 * Text Domain: wcefr
 */


function load_wc_exporter_for_reviso() {

	/*Function check */
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	/*Internationalization*/
	load_plugin_textdomain( 'wcefr', false, basename( dirname( __FILE__ ) ) . '/languages' );

	/*Dichiarazioni costanti*/
	define( 'WCEFR_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WCEFR_URI', plugin_dir_url( __FILE__ ) );
	define( 'WCEFR_INCLUDES', WCEFR_DIR . 'includes/' );
	define( 'WCEFR_ADMIN', WCEFR_DIR . 'admin/' );
	define( 'WCEFR_SETTINGS', admin_url( 'admin.php?page=wc-exporter-for-reviso' ) );

	/*Richiamo file necessari*/
	require( WCEFR_ADMIN . 'wcefr-admin.php' );
	require( WCEFR_INCLUDES . 'wcefr-functions.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-call.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-users.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-products.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-settings.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-checkout-fields.php' );
	require( WCEFR_INCLUDES . 'class-wcefr-orders.php' );

}
add_action( 'plugins_loaded', 'load_wc_exporter_for_reviso', 10 );