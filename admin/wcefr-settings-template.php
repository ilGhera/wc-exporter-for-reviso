<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

?>

<!-- Reviso connection -->
<form name="wcefr-settings" class="wcefr-form connection one-of"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection status', 'wcefr' ); ?></th>
			<td>
				<div class="bootstrap-iso">
					<div class="check-connection">
						<h4 class="wcefr-connection-status"><span class="wcefr label label-danger"><?php esc_html_e( 'Not connected', 'wcefr' ); ?></span></h4>
					</div>
				</div>
				<p class="description"><?php esc_html_e( 'Connect with your Reviso credentials', 'wcefr' ); ?></p>				
			</td>
		</tr>
	</table>

	<a class="button-primary wcefr-connect" href="https://app.reviso.com/api1/requestaccess.aspx?appPublicToken=iRxYo7PUDBHSsw6Kd63uLRM86FDx1O0HERqbknB2hhg1&locale=it-IT&redirectUrl=<?php echo esc_url( WCEFR_SETTINGS ); ?>"><?php esc_html_e( 'Connect to Reviso', 'wcefr' ); ?></a>
	<a class="button-primary wcefr-disconnect red"><?php esc_html_e( 'Disconnect from Reviso', 'wcefr' ); ?></a>

</form>


<!-- Global settings -->
<?php
$wcefr_company_invoice = get_option( 'wcefr_company_invoice' );
$wcefr_private_invoice = get_option( 'wcefr_private_invoice' );
$wcefr_private         = get_option( 'wcefr_private' );
$wcefr_cf_mandatory    = get_option( 'wcefr_cf_mandatory' );
$wcefr_fields_check    = get_option( 'wcefr_fields_check' );
$wcefr_pec_active      = get_option( 'billing_wcefr_pec_active' );
$wcefr_pa_code_active  = get_option( 'billing_wcefr_pa_code_active' );

if ( isset( $_POST['wcefr-options-nonce'] ) && wp_verify_nonce( $_POST['wcefr-options-nonce'], 'wcefr-options' ) ) {

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_company_invoice = isset( $_POST['wcefr_company_invoice'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_company_invoice'] ) ) : 0;
		update_option( 'wcefr_company_invoice', $wcefr_company_invoice );
		update_option( 'billing_wcefr_piva_active', $wcefr_company_invoice );
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_private_invoice = isset( $_POST['wcefr_private_invoice'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_private_invoice'] ) ) : 0;
		update_option( 'wcefr_private_invoice', $wcefr_private_invoice );
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_private = isset( $_POST['wcefr_private'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_private'] ) ) : 0;
		update_option( 'wcefr_private', $wcefr_private );
	}

	/*Update the CF filed value based on the previous selected options*/
	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		if ( 0 === $wcefr_company_invoice && 0 === $wcefr_private_invoice && 0 === $wcefr_private ) {
			update_option( 'billing_wcefr_cf_active', 0 );
		} else {
			update_option( 'billing_wcefr_cf_active', 1 );
		}
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_cf_mandatory = isset( $_POST['wcefr_cf_mandatory'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_cf_mandatory'] ) ) : 0;
		update_option( 'wcefr_cf_mandatory', $wcefr_cf_mandatory );
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_fields_check = isset( $_POST['wcefr_fields_check'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_fields_check'] ) ) : 0;
		update_option( 'wcefr_fields_check', $wcefr_fields_check );
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_pec_active = isset( $_POST['wcefr_pec_active'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_pec_active'] ) ) : 0;
		update_option( 'billing_wcefr_pec_active', $wcefr_pec_active );
	}

	if ( isset( $_POST['wcefr-options-sent'] ) ) {
		$wcefr_pa_code_active = isset( $_POST['wcefr_pa_code_active'] ) ? sanitize_text_field( wp_unslash( $_POST['wcefr_pa_code_active'] ) ) : 0;
		update_option( 'billing_wcefr_pa_code_active', $wcefr_pa_code_active );
	}

}
?>

<!--Start form-->
<form name="wcefr-options" id="wcefr-options" class="wcefr-form"  method="post" action="">

	<h3 class="wcefr"><?php esc_html_e( 'Checkout page', 'wcefr' ); ?></h3>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Tax documents', 'wcefr' ); ?></th>
			<td>
				<p style="margin-bottom: 10px;">
					<label for="wcefr_company_invoice">
						<input type="checkbox" name="wcefr_company_invoice" value="1"<?php echo 1 == $wcefr_company_invoice ? ' checked="checked"' : ''; ?>>
						<?php echo '<span class="tax-document">' . esc_html( __( 'Company (Invoice)', 'wcefr' ) ) . '</span>'; ?>
					</label>							
				</p>
				<p style="margin-bottom: 10px;">
					<label for="wcefr_private_invoice">
						<input type="checkbox" name="wcefr_private_invoice" value="1"<?php echo 1 == $wcefr_private_invoice ? ' checked="checked"' : ''; ?>>
						<?php echo '<span class="tax-document">' . esc_html( __( 'Private (Invoice)', 'wcefr' ) ) . '</span>'; ?>
					</label>
				</p>
				<p>
					<label for="wcefr_private">
						<input type="checkbox" name="wcefr_private" value="1"<?php echo 1 == $wcefr_private ? ' checked="checked"' : ''; ?>>
						<?php echo '<span class="tax-document">' . esc_html( __( 'Private (Receipt)', 'wcefr' ) ) . '</span>'; ?>
					</label>
				</p>
				<p class="description"><?php esc_html_e( 'By activating one or more types of invoice, the fields VAT and Tax Code will be displayed when needed.', 'wcefr' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'CF required', 'wcefr' ); ?></th>
			<td>
				<label for="wcefr_cf_mandatory">
					<input type="checkbox" name="wcefr_cf_mandatory" value="1"<?php echo 1 == $wcefr_cf_mandatory ? ' checked="checked"' : ''; ?>>
				</label>
				<p class="description"><?php esc_html_e( 'Make the Tax Code field mandatory for receipts to individuals.', 'wcefr' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Check fields', 'wcefr' ); ?></th>
			<td>
				<label for="wcefr_fields_check">
					<input type="checkbox" name="wcefr_fields_check" value="1"<?php echo 1 == $wcefr_fields_check ? ' checked="checked"' : ''; ?>>
				</label>
				<p class="description"><?php esc_html_e( 'Activate the control of the VAT and Tax Code fields.', 'wcefr' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'PEC', 'wcefr' ); ?></th>
			<td>
				<label for="wcefr_pec_active">
					<input type="checkbox" name="wcefr_pec_active" value="1"<?php echo 1 == $wcefr_pec_active ? ' checked="checked"' : ''; ?>>
				</label>
				<p class="description"><?php esc_html_e( 'Activate the PEC field for electronic invoicing', 'wcefr' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Receiving code', 'wcefr' ); ?></th>
			<td>
				<label for="wcefr-pa-code">
					<input type="checkbox" name="wcefr_pa_code_active" value="1"<?php echo 1 == $wcefr_pa_code_active ? ' checked="checked"' : ''; ?>>
				</label>
				<p class="description"><?php esc_html_e( 'Activate the Receiving Code field for electronic invoicing', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<?php wp_nonce_field( 'wcefr-options', 'wcefr-options-nonce' ); ?>
	
	<p class="submit">
		<input type="submit" name="wcefr-options-sent" class="button-primary" value="<?php esc_attr_e( 'Save', 'wcefr' ); ?>" />
	</p>
</form>

<?php
/*Pass data to the script file*/
wp_localize_script(
	'wcefr-js',
	'wcefrSettings',
	array(
		'responseLoading' => WCEFR_URI . 'images/loader.gif',
	)
);
