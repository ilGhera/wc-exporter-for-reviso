/**
 * Gestisce la visualizzazione dei campi fiscali in base al tipo di fattura selezionato
 * @author ilGhera
 * @package wc-exporter-for-danea-reviso/js
 * @since 0.9.0
 */
jQuery(document).ready(function($){

	var invoice_type  = $('#billing_wcefr_invoice_type');
	var p_iva 		  = $('#billing_wcefr_piva_field');
	var pec           = $('#billing_wcefr_pec_field');
	var receiver_code = $('#billing_wcefr_pa_code_field');

	if($(invoice_type).val() === 'private-invoice') {
	
		p_iva.hide();
	
	} else if($(invoice_type).val() === 'private') {

		p_iva.hide();
		pec.hide();
		receiver_code.hide();

	}

	$(invoice_type).on('change', function(){
		if($(this).val() === 'private-invoice') {
		
			p_iva.hide();
			pec.show();
			receiver_code.show();
		
		} else if($(this).val() === 'private') {
			
			p_iva.hide();
			pec.hide();
			receiver_code.hide();

		} else {
			
			p_iva.show();
			pec.show();
			receiver_code.show();
		
		}
	})
})