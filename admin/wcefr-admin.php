<?php /**
 * Admin
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

class wcefrAdmin {


	public function __construct() {

		add_action( 'admin_menu', array( $this, 'wcefr_add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this , 'wcefr_register_scripts' ) );

	}


	/**
	 * Scripts and style sheets
	 * @return void
	 */
	function wcefr_register_scripts() {

		$screen = get_current_screen();
		if ( $screen->id === 'woocommerce_page_wc-exporter-for-reviso' ) {

			/*js*/
			wp_enqueue_script( 'wcefr-js', WCEFR_URI . 'js/wcefr.js', array( 'jquery' ), '1.0', true );
		    wp_enqueue_script( 'bootstrap-js', plugin_dir_url(__DIR__) . 'js/bootstrap.min.js' );
		
			/*css*/
		    wp_enqueue_style( 'bootstrap-iso', plugin_dir_url(__DIR__) . 'css/bootstrap-iso.css' );

		} elseif ( $screen->id === 'edit-shop_order' ) {

			wp_enqueue_script( 'wcefr-js', WCEFR_URI . 'js/wcefr-shop-orders.js', array( 'jquery' ), '1.0', true );

		}

		wp_enqueue_style( 'wcefr-style', WCEFR_URI . 'css/wc-exporter-for-reviso.css' );

	}


	/**
	 * Menu page
	 * @return string
	 */
	public function wcefr_add_menu() {

		$wcefr_page = add_submenu_page( 'woocommerce', 'WCEFR Options', 'WC Exporter for Reviso', 'manage_woocommerce', 'wc-exporter-for-reviso', array( $this, 'wcefr_options' ) );

		return $wcefr_page;

	}


	/**
	 * Options page
	 * @return mixed
	 */
	public function wcefr_options() {

		/*Right of access*/
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'It seems like you don\'t have permission to see this page', 'wcefr' ) );
		}


		/*Page template start*/
		echo '<div class="wrap">';
			echo '<div class="wrap-left">';

				/*Check if WooCommerce is installed ancd activated*/
				if ( ! class_exists( 'WooCommerce' ) ) {
					echo '<div id="message" class="error">';
						echo '<p>';
							echo '<strong>' . __( 'ATTENTION! It seems like Woocommerce is not installed.', 'wcefr' ) . '</strong>';
						echo '</p>';
					echo '</div>';
					exit;
				}

				echo '<div id="wcefr-generale">';

					/*Header*/
					echo '<h1 class="wcefr main">' . __( 'WooCommerce Exporter for Reviso - Premium', 'wcefr' ) . '</h1>';
			
					/*Plugin premium key*/
					$key = sanitize_text_field( get_option( 'wcefr-premium-key' ) );

					if ( isset( $_POST['wcefr-premium-key'] ) ) {
					
						$key = sanitize_text_field( $_POST['wcefr-premium-key'] );
					
						update_option( 'wcefr-premium-key', $key );
					
					}

					/*Premium Key Form*/
					echo '<form id="wcefr-premium-key" method="post" action="">';
					echo '<label>' . __( 'Premium Key', 'wcefr' ) . '</label>';
					echo '<input type="text" class="regular-text code" name="wcefr-premium-key" id="wcefr-premium-key" placeholder="' . __( 'Add your Premium Key', 'wcefr' ) . '" value="' . $key . '" />';
					echo '<p class="description">' . __( 'Add your Premium Key and keep update your copy of <strong>Woocommerce Exporter for Reviso - Premium</strong>.', 'wcefr' ) . '</p>';
					echo '<input type="submit" class="button button-primary" value="' . __( 'Save ', 'wcefr' ) . '" />';
					echo '</form>';

					/*Plugin options menu*/
					echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>';
					echo '<h2 id="wcefr-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
						echo '<a href="#" data-link="wcefr-settings" class="nav-tab nav-tab-active" onclick="return false;">' . __( 'Settings', 'wcefr' ) . '</a>';
						echo '<a href="#" data-link="wcefr-suppliers" class="nav-tab" onclick="return false;">' . __( 'Suppliers', 'wcefr' ) . '</a>';
						echo '<a href="#" data-link="wcefr-products" class="nav-tab" onclick="return false;">' . __( 'Products', 'wcefr' ) . '</a>';
						echo '<a href="#" data-link="wcefr-customers" class="nav-tab" onclick="return false;">' . __( 'Customers', 'wcefr' ) . '</a>';
						echo '<a href="#" data-link="wcefr-orders" class="nav-tab" onclick="return false;">' . __( 'Orders', 'wcefr' ) . '</a>';
					echo '</h2>';

					/*Settings*/
					echo '<div id="wcefr-settings" class="wcefr-admin" style="display: block;">';

						include( WCEFR_ADMIN . 'wcefr-settings-template.php' );

					echo '</div>';


					/*Suppliers*/
					echo '<div id="wcefr-suppliers" class="wcefr-admin">';

						include( WCEFR_ADMIN . 'wcefr-suppliers-template.php' );

					echo '</div>';


					/*Products*/
					echo '<div id="wcefr-products" class="wcefr-admin">';

						include( WCEFR_ADMIN . 'wcefr-products-template.php' );

					echo '</div>';


					/*Customers*/
					echo '<div id="wcefr-customers" class="wcefr-admin">';

						include( WCEFR_ADMIN . 'wcefr-customers-template.php' );

					echo '</div>';

					/*Orders*/
					echo '<div id="wcefr-orders" class="wcefr-admin">';

						include( WCEFR_ADMIN . 'wcefr-orders-template.php' );

					echo '</div>';

				echo '</div>';

				/*Admin message*/
				echo '<div class="wcefr-message">';
					echo '<div class="yes"></div>';
					echo '<div class="not"></div>';
				echo '</div>';

			echo '</div>';
		
			echo '<div class="wrap-right">';
				echo '<!-- <iframe width="300" height="900" scrolling="no" src="https://www.ilghera.com/images/wcefr-premium-iframe.html"></iframe> -->';
			echo '</div>';
			
			echo '<div class="clear"></div>';

		echo '</div>';

	}

}
new wcefrAdmin;
