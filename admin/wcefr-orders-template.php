<?php
/**
 * Orders options
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<!-- Export form -->
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


<!-- Delete form -->
<form name="wcefr-delete-orders" id="wcefr-delete-orders" class="wcefr-form one-of"  method="post" action="">

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


<!-- Settings form -->
<form name="wcefr-orders-settings" class="wcefr-form"  method="post" action="">

	<h2>Orders settings</h2>

	<?php
	$wcefr_export_orders = get_option( 'wcefr-export-orders' );
	$wcefr_create_invoices = get_option( 'wcefr-create-invoices' );

	if ( isset( $_POST['wcefr-orders-options-sent'] ) ) {
		$wcefr_export_orders = isset( $_POST['wcefr-export-orders'] ) ? $_POST['wcefr-export-orders'] : 0;
		update_option( 'wcefr-export-orders', $wcefr_export_orders );

		$wcefr_create_invoices = isset( $_POST['wcefr-create-invoices'] ) ? $_POST['wcefr-create-invoices'] : 0;
		update_option( 'wcefr-create-invoices', $wcefr_create_invoices );
	}
	?>

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Export orders', 'wcefr' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-export-orders" value="1"<?php echo $wcefr_export_orders == 1 ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php _e( 'Export orders to Reviso automatically', 'wcefr' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Create invoices', 'wcefr' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-create-invoices" value="1"<?php echo $wcefr_create_invoices == 1 ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php _e( 'Create invoices in Reviso for completed orders ', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-products-submit', 'wcefr-export-products-nonce' ); ?>
	
	<p class="submit">
		<input type="submit" name="wcefr-orders-options-sent" class="button-primary wcefr orders-settings" value="<?php _e( 'Save options', 'wcefr' ); ?>" />
	</p>

</form>
