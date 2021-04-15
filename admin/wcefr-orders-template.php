<?php
/**
 * Orders options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.4
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
					$statuses = wc_get_order_statuses();
					foreach ( $statuses as $key => $value ) {
						echo '<option name="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '">';
						echo esc_html( __( $value, 'wc-exporter-for-reviso' ) ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select which orders to export', 'wc-exporter-for-reviso' ); ?></p>

				<?php wcefr_go_premium(); ?>

			</td>
		</tr>
	</table>

	<input type="submit" class="button-primary wcefr export orders" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>" disabled>

</form>


<!-- Delete form -->
<form name="wcefr-delete-orders" id="wcefr-delete-orders" class="wcefr-form one-of"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete orders', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all orders on Reviso', 'wc-exporter-for-reviso' ); ?></p>

				<?php wcefr_go_premium(); ?>

			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red orders" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" disabled />
	</p>

</form>

<!-- Settings form -->
<form name="wcefr-orders-settings" class="wcefr-form"  method="post" action="">

	<h2><?php esc_html_e( 'Orders settings', 'wc-exporter-for-reviso' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Export orders', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-export-orders" value="1">
				<p class="description"><?php esc_html_e( 'Export orders to Reviso automatically', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-create-invoices-field">
			<th scope="row"><?php esc_html_e( 'Create invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-create-invoices" value="1" ?>
				<p class="description"><?php esc_html_e( 'Create invoices in Reviso for completed orders', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-issue-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Issue invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" class="wcefr-issue-invoices" name="wcefr-issue-invoices" value="1"?>
				<p class="description"><?php esc_html_e( 'Issue invoices created in Reviso directly ', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-send-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Send invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" class="wcefr-send-invoices" name="wcefr-send-invoices" value="1">
				<p class="description"><?php esc_html_e( 'Attach invoices to completed order notifications', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr class="wcefr-invoices-field wcefr-book-invoices-field" style="display: none;">
			<th scope="row"><?php esc_html_e( 'Book invoices', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-book-invoices" value="1">
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

						echo '<option value="' . esc_attr( $single->prefix ) . '">' . esc_html( $single->prefix ) . ' - ' . esc_html( $single->name ) . '</option>';

                        }

                        echo '</select>';

                    }
                    ?>
                    <p class="description"><?php echo wp_kses_post( __( 'Choose the series of numbers to use for <b>Invoices</b>', 'wc-exporter-for-reviso' ) ); ?></p>

                    <?php wcefr_go_premium(); ?>

                </div>
                <div class="wcefr-number-series-receipts">
                    <?php
                    if ( is_array( $get_remote_series ) && ! empty( $get_remote_series ) ) {

                        echo '<select name="wcefr-number-series-receipts" class="wcefr-select-large">';

                        foreach ( $get_remote_series as $single ) {

                            echo '<option value="' . esc_attr( $single->prefix ) . '">' . esc_html( $single->prefix ) . ' - ' . esc_html( $single->name ) . '</option>';

                        }

                        echo '</select>';

                    }
                    ?>
                    <p class="description"><?php echo wp_kses_post( __( 'Choose the series of numbers to use for <b>Receipts</b>', 'wc-exporter-for-reviso' ) ); ?></p>

				<?php wcefr_go_premium(); ?>

                </div>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Customers group', 'wc-exporter-for-reviso' ); ?></th>
			<td>
            <select class="wcefr-customers-groups wcefr-orders-customers-group" name="wcefr-orders-customers-group">
                    <option value="0"><?php esc_html_e( 'Auto', 'wc-exporter-for-reviso' ); ?></option>
                </select>
				<p class="description"><?php echo wp_kses_post( __( 'Select a specific group of Reviso customers or use <i>Auto</i> for national and foreign groups', 'wc-exporter-for-reviso' ) ); ?></p>

                <?php wcefr_go_premium(); ?>

			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" name="wcefr-orders-settings-sent" class="button-primary wcefr orders-settings" value="<?php esc_html_e( 'Save options', 'wc-exporter-for-reviso' ); ?>" disabled />
	</p>

</form>
