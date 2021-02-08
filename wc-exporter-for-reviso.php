<?php
/**
 * Plugin Name: WC Exporter for Reviso - Premium
 * Plugin URI: https://www.ilghera.com/product/woocommerce-exporter-for-reviso-premium
 * Description: Connect your store to Reviso and export orders, products, customers and suppliers.
 * Author: ilGhera
 * Version: 0.9.6
 * 
 * Author URI: https://ilghera.com
 * Requires at least: 4.0
 * Tested up to: 5.6
 * WC tested up to: 4
 * Text Domain: wc-exporter-for-reviso
 */

/**
 * Handles the plugin activation
 *
 * @return void
 */
function load_wc_exporter_for_reviso() {

	/*Function check */
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	/*Internationalization*/
	load_plugin_textdomain( 'wc-exporter-for-reviso', false, basename( dirname( __FILE__ ) ) . '/languages' );

	/*Constants declaration*/
	define( 'WCEFR_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WCEFR_URI', plugin_dir_url( __FILE__ ) );
	define( 'WCEFR_FILE', __FILE__ );
	define( 'WCEFR_ADMIN', WCEFR_DIR . 'admin/' );
	define( 'WCEFR_DIR_NAME', basename( dirname( __FILE__ ) ) );
	define( 'WCEFR_INCLUDES', WCEFR_DIR . 'includes/' );
	define( 'WCEFR_SETTINGS', admin_url( 'admin.php?page=wc-exporter-for-reviso' ) );

	/*Files required*/
	require_once( WCEFR_DIR . 'libraries/action-scheduler/action-scheduler.php' );
	require_once( WCEFR_ADMIN . 'class-wcefr-admin.php' );
	require_once( WCEFR_INCLUDES . 'wcefr-functions.php' );
	require_once( WCEFR_INCLUDES . 'class-wcefr-call.php' );
	require_once( WCEFR_INCLUDES . 'class-wcefr-settings.php' );
	require_once( WCEFR_INCLUDES . 'class-wcefr-users.php' );
	require_once( WCEFR_INCLUDES . 'class-wcefr-products.php' );
	require_once( WCEFR_INCLUDES . 'class-wcefr-orders.php' );
	require_once( WCEFR_INCLUDES . 'wcefr-invoice.php' );
	require_once( WCEFR_INCLUDES . 'wc-checkout-fields/class-wcefr-checkout-fields.php' );
}
add_action( 'after_setup_theme', 'load_wc_exporter_for_reviso', 1 );
