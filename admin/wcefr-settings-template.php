<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.1
 */

?>

<!-- Reviso connection -->
<form name="wcefr-settings" class="wcefr-form free connection one-of"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection status', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<div class="bootstrap-iso">
					<div class="check-connection">
						<h4 class="wcefr-connection-status"><span class="wcefr label label-danger"><?php esc_html_e( 'Not connected', 'wc-exporter-for-reviso' ); ?></span></h4>
					</div>
				</div>
				<p class="description"><?php esc_html_e( 'Connect with your Reviso credentials', 'wc-exporter-for-reviso' ); ?></p>				
			</td>
		</tr>
	</table>

	<a class="button-primary wcefr-connect" href="https://app.reviso.com/api1/requestaccess.aspx?appPublicToken=iRxYo7PUDBHSsw6Kd63uLRM86FDx1O0HERqbknB2hhg1&locale=it-IT&redirectUrl=<?php echo esc_url( WCEFR_SETTINGS ); ?>"><?php esc_html_e( 'Connect to Reviso', 'wc-exporter-for-reviso' ); ?></a>
	<a class="button-primary wcefr-disconnect red"><?php esc_html_e( 'Disconnect from Reviso', 'wc-exporter-for-reviso' ); ?></a>

</form>

<!-- Global settings -->
<?php include( WCEFR_INCLUDES . 'wc-checkout-fields/templates/wcefr-checkout-template.php' ); ?>

<?php
/*Pass data to the script file*/
wp_localize_script(
	'wcefr-js',
	'wcefrSettings',
	array(
		'responseLoading' => WCEFR_URI . 'images/loader.gif',
	)
);
