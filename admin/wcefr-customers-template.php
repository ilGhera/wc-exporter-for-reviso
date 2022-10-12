<?php
/**
 * Customers options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.8
 */

$synchronize_customers  = get_option( 'wcefr-synchronize-customers' ) ? get_option( 'wcefr-synchronize-customers' ) : 0;
?>

<!-- Export form -->
<form name="wcefr-export-customers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User role', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-customers-role wcefr-select" name="wcefr-customers-role">
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();

					/*Get value from the db*/
					$customers_role = get_option( 'wcefr-customers-role' );

					foreach ( $roles as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . ( $key === $customers_role ? ' selected="selected"' : '' ) . '> ' . esc_html( __( $value, 'woocommerce' ) ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select your customers user role', 'wc-exporter-for-reviso' ); ?></p>

			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Group', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-customers-groups" name="wcefr-customers-groups"></select>
				<p class="description"><?php esc_html_e( 'Select a Reviso customers group', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary wcefr export-users customers" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>


<!-- Delete form -->
<form name="wcefr-delete-customers" class="wcefr-form one-of"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete customers', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all customers on Reviso', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary wcefr red users customers" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>

<!-- Settings form -->
<form name="wcefr-customers-settings" class="wcefr-form"  method="post" action="">

    <h2><?php esc_html_e( 'Synchronization options', 'wc-exporter-for-reviso' ); ?></h2>

	<table class="form-table">
		<tr class="synchronize-customers">
			<th scope="row"><?php esc_html_e( 'Customers', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<input type="checkbox" name="wcefr-synchronize-customers" value="1"<?php echo 1 == $synchronize_customers ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Update customers in Reviso in real time', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
        <?php wp_nonce_field( 'wcefr-customers-settings', 'wcefr-customers-settings-nonce' ); ?>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary wcefr customers-settings" value="<?php esc_html_e( 'Save settings', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>

