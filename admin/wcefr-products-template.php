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
			<th scope="row"><?php esc_html_e( 'Products categories', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-products-categories wcefr-select" name="wcefr-products-categories[]" multiple data-placeholder="<?php esc_html_e( 'All categories', 'wc-exporter-for-reviso' ); ?>">
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
				<p class="description"><?php esc_html_e( 'Select which categories to send to Reviso.', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Departmental distribution', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<select class="wcefr-departmental-distribution" name="wcefr-departmental-distribution">
                    <option>-</option>
					<?php
                    $class = new WCEFR_Products();
                    $distributions = $class->get_remote_departmental_distributions();

					/*Get the value from the db*/
					$saved_distribution = get_option( 'wcefr-departmental-distribution' );
                    error_log( 'DATE SAVED: ' . $saved_distribution );

                    if ( is_array( $distributions ) ) {

                        foreach ( $distributions as $dist ) {

							$selected = intval( $dist->departmentalDistributionNumber ) === intval( $saved_distribution ) ? ' selected="selected"' : '';

							echo '<option value="' . esc_attr( $dist->departmentalDistributionNumber ) . '"' . esc_html( $selected ) . '>' . esc_html( $dist->name ) . '</option>';
                        }
                    }    
                    ?>
				</select>
				<p class="description"><?php esc_html_e( 'Select a generic deparmental distribution to use for the products.', 'wc-exporter-for-reviso' ); ?></p>
				<p class="description"><?php esc_html_e( 'You can specify a different value in every single product page.', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>

	<input type="submit" name="wcefr-products-export" class="button-primary wcefr export products" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>">

</form>


<!-- Delete form -->
<form name="wcefr-delete-products" id="wcefr-delete-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete products', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all products on Reviso. Note that you cannot delete a product that has been used on an Invoice.', 'wc-exporter-for-reviso' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red products" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" />
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
