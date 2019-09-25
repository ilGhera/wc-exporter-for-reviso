<?php
/**
 * Suppliers options
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */
?>

<!-- Export form -->
<form name="wcefr-export-suppliers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'User role', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-suppliers-role wcefr-select" name="wcefr-suppliers-role">
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();

					/*Get the value from the db*/
					$suppliers_role = get_option( 'wcefr-suppliers-role' );

					foreach ( $roles as $key => $value ) {
						echo '<option value="' . $key . '"' . ( $key === $suppliers_role ? ' selected="selected"' : '' ) . '> ' . __( $value, 'woocommerce' ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php echo __( 'Select your suppliers user role', 'wcefr' ); ?></p>

			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Group', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-suppliers-groups wcefr-select" name="wcefr-suppliers-groups">
					<option><?php _e( 'No groups available', 'wcefr' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Select a Reviso suppliers group.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-suppliers-submit', 'wcefr-export-suppliers-nonce' ); ?>

	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary wcefr export-users suppliers" value="<?php _e( 'Export to Reviso', 'wcefr' ); ?>" />
	</p>

</form>


<!-- Delete form -->
<form name="wcefr-delete-suppliers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Delete suppliers', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php _e( 'Delete all suppliers on Reviso.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php //wp_nonce_field( 'wcefr-export-suppliers-submit', 'wcefr-export-suppliers-nonce' ); ?>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red users suppliers" value="<?php _e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>