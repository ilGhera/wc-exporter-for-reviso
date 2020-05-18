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

					if ( $terms ) {
						foreach ( $terms as $single_term ) {

							echo '<option value="' . esc_attr( $single_term->term_id ) . '">' . esc_html( $single_term->name ) . '</option>';

						}
					}
					?>

				</select>
				<p class="description"><?php esc_html_e( 'Select which categories to send to Reviso', 'wc-exporter-for-reviso' ); ?></p>
				
				<?php wcefr_go_premium(); ?>
			
			</td>
		</tr>
	</table>

	<input type="submit" name="wcefr-products-export" class="button-primary wcefr export products" value="<?php esc_html_e( 'Export to Reviso', 'wc-exporter-for-reviso' ); ?>" disabled>

</form>


<!-- Delete form -->
<form name="wcefr-delete-products" id="wcefr-delete-products" class="wcefr-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete products', 'wc-exporter-for-reviso' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all products on Reviso. Note that you cannot delete a product that has been used on an Invoice.', 'wc-exporter-for-reviso' ); ?></p>
				
				<?php wcefr_go_premium(); ?>
			
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary wcefr red products" value="<?php esc_html_e( 'Delete from Reviso', 'wc-exporter-for-reviso' ); ?>" disabled />
	</p>

</form>

