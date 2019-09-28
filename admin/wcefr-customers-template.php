<?php
/**
 * Customers options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

?>

<!-- Export form -->
<form name="wcefr-export-customers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User role', 'wcefr' ); ?></th>
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
				<p class="description"><?php esc_html_e( 'Select your customers user role', 'wcefr' ); ?></p>

			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Group', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-customers-groups" name="wcefr-customers-groups"></select>
				<p class="description"><?php esc_html_e( 'Select a Reviso customer group.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary wcefr export-users customers" value="<?php esc_html_e( 'Export to Reviso', 'wcefr' ); ?>" />
	</p>

</form>


<!-- Delete form -->
<form name="wcefr-delete-customers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete customers', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all customers on Reviso.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary wcefr red users customers" value="<?php esc_html_e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>
