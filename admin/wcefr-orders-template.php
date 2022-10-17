<?php
/**
 * Orders options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 1.1.0
 */

?>

<!-- Export form -->
<form name="wcefr-orders" class="wcefr-form wcefr-orders-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Order status', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-orders-statuses wcefr-select" name="wcefr-orders-statuses[]" multiple data-placeholder="<?php esc_html_e( 'All orders types', 'wc-exporter-for-reviso' ); ?>">
					<?php
					$saved_statuses = get_option( 'wcefr-orders-statuses' ) ? get_option( 'wcefr-orders-statuses' ) : array();
					$statuses = wc_get_order_statuses();
					foreach ( $statuses as $key => $value ) {
						echo '<option name="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"';
						echo ( in_array( $key, $saved_statuses ) ) ? ' selected="selected">' : '>';
						echo esc_html( __( $value, 'wc-exporter-for-reviso' ) ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select which orders to export', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" class="button-primary wcefr export orders" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>">

</form>


<!-- Delete form -->
<form name="wcefr-delete-orders" id="wcefr-delete-orders" class="wcefr-form one-of"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete orders', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all orders on Reviso', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red orders" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>

<?php
/*Nonce*/
$export_orders_nonce = wp_create_nonce( 'wcefr-export-orders' );
$delete_orders_nonce = wp_create_nonce( 'wcefr-delete-orders' );

wp_localize_script(
	'wcefr-js',
	'wcefrOrders',
	array(
		'exportNonce' => $export_orders_nonce,
		'deleteNonce' => $delete_orders_nonce,
	)
);
?>

<!-- Settings form -->
<form name="wcefr-orders-settings" class="wcefr-form"  method="post" action="">

    <h2><?php esc_html_e( 'Synchronization options', 'wc-exporter-for-reviso' ); ?></h2>

	<?php
	$wcefr_export_orders          = get_option( 'wcefr-export-orders' );
	$wcefr_create_invoices        = get_option( 'wcefr-create-invoices' );
	$wcefr_issue_invoices         = get_option( 'wcefr-issue-invoices' );
	$wcefr_send_invoices          = get_option( 'wcefr-send-invoices' );
	$wcefr_book_invoices          = get_option( 'wcefr-book-invoices' );
	$wcefr_number_series          = get_option( 'wcefr-number-series-prefix' );
	$wcefr_number_series_receipts = get_option( 'wcefr-number-series-receipts-prefix' );
	$wcefr_orders_customers_group = get_option( 'wcefr-orders-customers-group' );

	if ( isset( $_POST['wcefr-orders-settings-sent'], $_POST['wcefr-orders-settings-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['wcefr-orders-settings-nonce'] ), 'wcefr-orders-settings' ) ) {

		$wcefr_export_orders = isset( $_POST['wcefr-export-orders'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-export-orders'] ) ) : 0;
		update_option( 'wcefr-export-orders', $wcefr_export_orders );

		$wcefr_create_invoices = isset( $_POST['wcefr-create-invoices'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-create-invoices'] ) ) : 0;
		update_option( 'wcefr-create-invoices', $wcefr_create_invoices );

		$wcefr_issue_invoices = isset( $_POST['wcefr-issue-invoices'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-issue-invoices'] ) ) : 0;
		update_option( 'wcefr-issue-invoices', $wcefr_issue_invoices );

		$wcefr_send_invoices = isset( $_POST['wcefr-send-invoices'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-send-invoices'] ) ) : 0;
		update_option( 'wcefr-send-invoices', $wcefr_send_invoices );

		$wcefr_book_invoices = isset( $_POST['wcefr-book-invoices'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-book-invoices'] ) ) : 0;
		update_option( 'wcefr-book-invoices', $wcefr_book_invoices );

		$wcefr_number_series = isset( $_POST['wcefr-number-series'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-number-series'] ) ) : $wcefr_number_series;
		update_option( 'wcefr-number-series-prefix', $wcefr_number_series );
    
        $wcefr_number_series_receipts = isset( $_POST['wcefr-number-series-receipts'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-number-series-receipts'] ) ) : $wcefr_number_series_receipts;
		update_option( 'wcefr-number-series-receipts-prefix', $wcefr_number_series_receipts );

        $wcefr_orders_customers_group = isset( $_POST['wcefr-orders-customers-group'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr-orders-customers-group'] ) ) : $wcefr_orders_customers_group;
		update_option( 'wcefr-orders-customers-group', $wcefr_orders_customers_group );
	}
	?>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Export orders', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-export-orders" value="1"<?php echo 1 == $wcefr_export_orders ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export orders to Reviso automatically', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-create-invoices-field">
			<th scope="row"><?php esc_html_e( 'Create invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-create-invoices" value="1"<?php echo 1 == $wcefr_create_invoices ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Create invoices in Reviso for completed orders', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-issue-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Issue invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" class="wcefr-issue-invoices" name="wcefr-issue-invoices" value="1"<?php echo 1 == $wcefr_issue_invoices ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Issue invoices created in Reviso directly ', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-send-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Send invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" class="wcefr-send-invoices" name="wcefr-send-invoices" value="1"<?php echo 1 == $wcefr_send_invoices ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Attach invoices to completed order notifications', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-book-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Book invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-book-invoices" value="1"<?php echo 1 == $wcefr_book_invoices ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Book invoices created in Reviso directly ', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-number-series-field">
			<th scope="row"><?php esc_html_e( 'Number series', 'wc-exporter-for-reviso' ); ?></th>
            <td>
                <div class="wcefr-number-series">
                    <?php
                    $class = new WCEFR_Orders();
                    $get_remote_series = $class->get_remote_number_series( null, 'customerInvoice' );

                    if ( is_array( $get_remote_series ) && ! empty( $get_remote_series ) ) {

                        echo '<select name="wcefr-number-series" class="wcefr-select-large">';

                        foreach ( $get_remote_series as $single ) {

                            $checked = $single->prefix === $wcefr_number_series ? ' selected="selected"' : '';

                            echo '<option value="' . esc_attr( $single->prefix ) . '"' . esc_attr( $checked ) . '>' . esc_html( $single->prefix ) . ' - ' . esc_html( $single->name ) . '</option>';

                        }

                        echo '</select>';

                    }
                    ?>
                    <p class="description"><?php echo wp_kses_post( __( 'Choose the series of numbers to use for <b>Invoices</b>', 'wc-exporter-for-reviso' ) ); ?></p>
                </div>
                <div class="wcefr-number-series-receipts">
                    <?php
                    if ( is_array( $get_remote_series ) && ! empty( $get_remote_series ) ) {

                        echo '<select name="wcefr-number-series-receipts" class="wcefr-select-large">';

                        foreach ( $get_remote_series as $single ) {

                            $checked = $single->prefix === $wcefr_number_series_receipts ? ' selected="selected"' : '';

                            echo '<option value="' . esc_attr( $single->prefix ) . '"' . esc_attr( $checked ) . '>' . esc_html( $single->prefix ) . ' - ' . esc_html( $single->name ) . '</option>';

                        }

                        echo '</select>';

                    }
                    ?>
                    <p class="description"><?php echo wp_kses_post( __( 'Choose the series of numbers to use for <b>Receipts</b>', 'wc-exporter-for-reviso' ) ); ?></p>
                </div>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Customers group', 'wc-exporter-for-reviso' ); ?></th>
			<td>
            <select data-group-selected="<?php echo esc_attr( $wcefr_orders_customers_group ); ?>" class="wcefr-customers-groups wcefr-orders-customers-group" name="wcefr-orders-customers-group">
                    <option value="0"><?php esc_html_e( 'Auto', 'wc-exporter-for-reviso' ); ?></option>
                </select>
				<p class="description"><?php echo wp_kses_post( __( 'Select a specific group of Reviso customers or use <i>Auto</i> for national and foreign groups', 'wc-exporter-for-reviso' ) ); ?></p>
			</td>
		</tr>
	</table>

	<?php wp_nonce_field( 'wcefr-orders-settings', 'wcefr-orders-settings-nonce' ); ?>
	
	<p class="submit">
		<input type="submit" name="wcefr-orders-settings-sent" class="button-primary wcefr orders-settings" value="<?php esc_html_e( 'Save options', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>
