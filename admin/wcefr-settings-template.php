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
			<th scope="row"><?php _e( 'Connetti Reviso', 'wcefr' ); ?></th>
			<td>
				<div class="bootstrap-iso">
					<a class="btn btn-primary" href="https://app.reviso.com/api1/requestaccess.aspx?appPublicToken=iRxYo7PUDBHSsw6Kd63uLRM86FDx1O0HERqbknB2hhg1&locale=it-IT&redirectUrl=http://localhost/wp-dev/wp-admin/admin.php?page=wc-exporter-for-reviso">Connetti Reviso</a>
				</div>
				<p class="description"><?php _e( 'Connect with your Reviso credentials', 'wcefr' ); ?></p>				
			</td>
		</tr>
		<tr> 
			<th scope="row"><?php _e( 'Test the API', 'wcefr' ); ?></th>
			<td>
				<form method="post" action="">
					<input type="hidden" name="api">
					<input type="submit" name="Via">
				</form>
				<p class="description"><?php _e( 'test', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" class="button-primary" value="<?php _e( 'Save', 'wcifd' ); ?>">

</form>
