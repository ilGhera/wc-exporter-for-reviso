<?php
/**
 * Single product options
 *
 * @author ilGhera
 * @package wc-exporter-for-reviso/includes
 * @since 1.0.0
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
            add_action( 'save_post',      array( $this, 'save' ) );

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
 
        ?>
        <p>
            <label for="wcefr-departmental-distribution">
                <?php echo wp_kses_post( __( "<i>Departmental distribution</i>", 'wc-exporter-for-reviso' ) ); ?>
            </label>
        </p>
        <select class="wcefr-departmental-distribution" name="wcefr-departmental-distribution">
        <option value="0"><?php esc_html_e( 'Select', 'wc-exporter-for-reviso' ); ?></option>
            <?php
            $class = new WCEFR_Products();
            $distributions = $class->get_remote_departmental_distributions();

            /*Get the value from the db*/
            $general_dist = get_option( 'wcefr-departmental-distribution' );
            $dist         = get_post_meta( $post->ID, 'wcefr-departmental-distribution', true );
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
    }


    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     *
     * return mixed
     */
    public function save( $post_id ) {
 
        if ( ! isset( $_POST['wcefr-meta-box-nonce'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['wcefr-meta-box-nonce'];
 
        if ( ! wp_verify_nonce( $nonce, 'wcefr-meta-box' ) ) {
            return $post_id;
        }
 
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
 
        $dist = sanitize_text_field( $_POST['wcefr-departmental-distribution'] );
 
        update_post_meta( $post_id, 'wcefr-departmental-distribution', $dist );
    }


}
new WCEFR_Single_Product();

