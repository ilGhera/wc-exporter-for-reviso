<?php
/**
 * Single product options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCEFR_Single_Product
 */
class WCEFR_Single_Product {

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$class = new WCEFR_Products();

		/* Only if dimension module was activated in Reviso */
		if ( $class->dimension_module() ) {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		}
	}

	/**
	 * Add the new metabox to the product page
	 *
	 * @param string $post_type the post type.
	 *
	 * @return void
	 */
	public function add_meta_box( $post_type ) {

		if ( 'product' === $post_type ) {

			add_meta_box(
				'wcefr-meta-box',
				__( 'Reviso', 'wc-exporter-for-reviso' ),
				array( $this, 'render_meta_box_content' ),
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param object $post The post object.
	 *
	 * @return void
	 */
	public function render_meta_box_content( $post ) {

		wp_nonce_field( 'wcefr-meta-box', 'wcefr-meta-box-nonce' );
        $product = wc_get_product( $post->ID );
		?>
		<p>
			<label for="wcefr-departmental-distribution">
				<?php echo wp_kses_post( __( '<i>Departmental distribution</i>', 'wc-exporter-for-reviso' ) ); ?>
			</label>
		</p>
		<select class="wcefr-departmental-distribution" name="wcefr-departmental-distribution">
		<option value="0"><?php esc_html_e( 'Select', 'wc-exporter-for-reviso' ); ?></option>
			<?php
			$class         = new WCEFR_Products();
			$distributions = $class->get_remote_departmental_distributions();

			/*Get the value from the db*/
			$general_dist = get_option( 'wcefr-departmental-distribution' );
            $dist         = $product->get_meta( 'wcefr-departmental-distribution' );
			$saved_dist   = '' !== $dist ? $dist : $general_dist;

			if ( is_array( $distributions ) ) {

				foreach ( $distributions as $dist ) {

					$selected = intval( $dist->departmentalDistributionNumber ) === intval( $saved_dist ) ? ' selected="selected"' : '';

					echo '<option value="' . esc_attr( $dist->departmentalDistributionNumber ) . '"' . esc_html( $selected ) . '>' . esc_html( $dist->name ) . '</option>';
				}
			}
			?>
		</select>
		<p class="howto"><?php esc_html_e( 'Set a specific departmental distribution for this product', 'wc-exporter-for-reviso' ); ?></p>
		<?php
		wcefr_go_premium();
	}
}

new WCEFR_Single_Product();

