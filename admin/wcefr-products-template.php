<?php
/**
 * Products options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/admin
 * @since 0.9.0
 */

?>

<!-- Export form -->
<form name="wcefr-export-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Products categories', 'wcefr' ); ?></th>
			<td>
				<select class="wcefr-products-categories wcefr-select" name="wcefr-products-categories[]" multiple data-placeholder="<?php esc_html_e( 'All categories', 'wcefr' ); ?>">
					<?php
					$terms = get_terms( 'product_cat' );

					/*Get the value from the db*/
					$products_categories = get_option( 'wcefr-products-categories' );

					if ( $terms ) {
						foreach ( $terms as $single_term ) {

							$selected = is_array( $products_categories ) && in_array( $single_term->term_id, $products_categories ) ? ' selected="selected"' : '';

							echo '<option value="' . esc_attr( $single_term->term_id ) . '"' . esc_html( $selected ) . '>' . esc_html( $single_term->name ) . '</option>';
						}
					}
					?>

				</select>
				<p class="description"><?php esc_html_e( 'Select which categories to send to Reviso', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" name="wcefr-products-export" class="button-primary wcefr export products" value="<?php esc_html_e( 'Export to Reviso', 'wcefr' ); ?>">

</form>


<!-- Delete form -->
<form name="wcefr-delete-products" id="wcefr-delete-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete products', 'wcefr' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all products on Reviso.<br>Note that you cannot delete a product that has been used on an Invoice.', 'wcefr' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red products" value="<?php esc_html_e( 'Delete from Reviso', 'wcefr' ); ?>" />
	</p>

</form>

<?php
/*Nonce*/
$export_products_nonce = wp_create_nonce( 'wcefr-export-products' );
$delete_products_nonce = wp_create_nonce( 'wcefr-delete-products' );

wp_localize_script(
	'wcefr-js',
	'wcefrProducts',
	array(
		'exportNonce' => $export_products_nonce,
		'deleteNonce' => $delete_products_nonce,
	)
);
