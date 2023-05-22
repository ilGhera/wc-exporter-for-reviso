<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 1.0.0
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
		<tr>
			<th></th>
			<td>
				<a class="button-primary wcefr-connect wcefr-button" href="https://app.reviso.com/api1/requestaccess.aspx?appPublicToken=iRxYo7PUDBHSsw6Kd63uLRM86FDx1O0HERqbknB2hhg1&locale=it-IT&redirectUrl=<?php echo esc_url( WCEFR_SETTINGS ); ?>"><?php esc_html_e( 'Connect to Reviso', 'wc-exporter-for-reviso' ); ?></a>
				<a class="button-primary wcefr-button wcefr-disconnect red"><?php esc_html_e( 'Disconnect from Reviso', 'wc-exporter-for-reviso' ); ?></a>
			</td>
		</tr>
	</table>


</form>

<!-- Tools -->
<form name="wcefr-tools" class="wcefr-form"  method="post" action="">
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Temporary data', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="hidden" name="wcefr-clear-cache" value="1">
				<input type="submit" class="button-primary wcefr-button wcefr-clear-cache" value="<?php esc_html_e( 'Clear the cache', 'wc-exporter-for-reviso' ); ?>">
				<p class="description"><?php esc_html_e( 'Remove all the data saved in cache', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>
</form>

<?php
/*Nonce*/
$clear_cache_nonce = wp_create_nonce( 'wcefr-clear-cache' );

/*Pass data to the script file*/
wp_localize_script(
	'wcefr-js',
	'wcefrSettings',
	array(
		'responseLoading' => WCEFR_URI . 'images/loader.gif',
		'clearCacheNonce' => $clear_cache_nonce,
	)
);
