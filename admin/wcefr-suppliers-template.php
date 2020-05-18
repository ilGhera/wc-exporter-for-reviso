<?php
/**
 * Suppliers options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

?>

<!-- Export form -->
<form name="wcefr-export-suppliers" class="wcefr-form free"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'User role', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-suppliers-role wcefr-select" name="wcefr-suppliers-role">
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();

					/*Get the value from the db*/
					$suppliers_role = get_option( 'wcefr-suppliers-role' );

					foreach ( $roles as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . ( $key === $suppliers_role ? ' selected="selected"' : '' ) . '> ' . esc_html( __( $value, 'woocommerce' ) ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select your suppliers user role', 'wc-exporter-for-reviso' ); ?></p>

			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Group', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-suppliers-groups" name="wcefr-suppliers-groups"></select>
				<p class="description"><?php esc_html_e( 'Select a Reviso suppliers group', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary wcefr export-users suppliers" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>


<!-- Delete form -->
<form name="wcefr-delete-suppliers" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete suppliers', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all suppliers on Reviso', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red users suppliers" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" />
	</p>

</form>

<?php
/*Nonce*/
$export_users_nonce = wp_create_nonce( 'wcefr-export-users' );
$delete_users_nonce = wp_create_nonce( 'wcefr-delete-users' );

wp_localize_script(
	'wcefr-js',
	'wcefrUsers',
	array(
		'exportNonce' => $export_users_nonce,
		'deleteNonce' => $delete_users_nonce,
	)
);
