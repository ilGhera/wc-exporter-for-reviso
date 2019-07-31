<?php
/**
 * Ordini
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<form name="wcefr-orders" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Order status', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-orders-statuses wcefr-select" name="wcefr-orders-statuses[]" multiple data-placeholder="<?php _e( 'All orders types', 'wcefr' ); ?>">
					<?php
					$saved_statuses = get_option( 'wcefr-orders-statuses' ) ? get_option( 'wcefr-orders-statuses' ) : array();
					$statuses = wc_get_order_statuses();
					foreach ( $statuses as $key => $value ) {
						echo '<option name="' . $key . '" value="' . $key . '"';
						echo ( in_array( $key, $saved_statuses ) ) ? ' selected="selected">' : '>';
						echo __( $value, 'wcefr' ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php _e( 'Select which orders to export ', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" class="button-primary wcefr export orders" value="<?php _e( 'Export to Reviso', 'wcefr' ); ?>">

</form>


<form name="wcefr-delete-orders" id="wcefr-delete-orders" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Delete orders', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php _e( 'Delete all orders on Reviso', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-products-submit', 'wcefr-export-products-nonce' ); ?>
	<p class="submit">
		<input type="submit" class="button-primary wcefr red orders" value="<?php _e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>