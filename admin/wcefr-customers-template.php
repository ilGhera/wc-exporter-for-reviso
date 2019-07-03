<?php
/**
 * Gestisce clienti
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<form name="wcefr-export-customers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'User role', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-customers-role wcefr-select" name="wcefr-customers-role">
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();

					/*Leggo il dato se già esistente nel database*/
					$customers_role = get_option( 'wcefr-customers-role' );

					foreach ( $roles as $key => $value ) {
						echo '<option value="' . $key . '"' . ( $key === $customers_role ? ' selected="selected"' : '' ) . '> ' . __( $value, 'woocommerce' ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php echo __( 'Select your customers user role', 'wcefr' ); ?></p>

			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Group', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-customer-groups" name="wcefr-customer-groups"></select>
				<p class="description"><?php _e( 'Select a Reviso customer group or create a new one.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php wp_nonce_field( 'wcefr-export-customers-submit', 'wcefr-export-customers-nonce' ); ?>
	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary" value="<?php _e( 'Export to Reviso', 'wcefr' ); ?>" />
	</p>

</form>


<form name="wcefr-delete-customers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Delete customers', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php _e( 'Delete all customers on Reviso.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-customers-submit', 'wcefr-export-customers-nonce' ); ?>
	<p class="submit">
		<input type="submit" class="button-primary wcefr red users customers" value="<?php _e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>