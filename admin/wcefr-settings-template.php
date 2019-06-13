<?php
/**
 * Impostazioni generali
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<form name="wcefr-settings" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Connection status', 'wcefr' ); ?></th>
			<td>
				<div class="bootstrap-iso">
					<div class="check-connection">
						<h4 class="wcefr-connection-status"><span class="wcefr label label-danger"><?php _e( 'Not connected', 'wcefr' ); ?></span></h4>
					</div>
				</div>
				<p class="description"><?php _e( 'Connect with your Reviso credentials', 'wcefr' ); ?></p>				
			</td>
		</tr>
	</table>

	<!-- <input type="submit" class="button-primary" value="<?php _e( 'Save', 'wcifd' ); ?>"> -->
	<a class="button-primary wcefr-connect" href="https://app.reviso.com/api1/requestaccess.aspx?appPublicToken=iRxYo7PUDBHSsw6Kd63uLRM86FDx1O0HERqbknB2hhg1&locale=it-IT&redirectUrl=<?php echo WCEFR_SETTINGS; ?>"><?php _e( 'Connect to Reviso', 'wcefr' ); ?></a>
	<a class="button-primary wcefr-disconnect red"><?php _e( 'Disconnect from Reviso', 'wcefr' ); ?></a>
</form>
