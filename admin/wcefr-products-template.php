<?php
/**
 * Prodotti
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<form name="wcefr-export-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'xxxxxx', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php _e( 'xxxxxxxxxxxxxxxxxxxxxx', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" name="wcefr-products-export" class="button-primary" value="<?php _e( 'Export to Reviso', 'wcefr' ); ?>">

</form>


<form name="wcefr-delete-products" id="wcefr-delete-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Delete products', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php _e( 'Delete all products on Reviso.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-products-submit', 'wcefr-export-products-nonce' ); ?>
	<p class="submit">
		<input type="submit" class="button-primary wcefr red products" value="<?php _e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>