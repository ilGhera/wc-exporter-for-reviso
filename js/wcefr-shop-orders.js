/**
 * JS - Shop Orders
 * @author ilGhera
 * @package wc-exporter-for-reviso/js
 * @since 0.9.0
 */

jQuery(document).ready(function($) {

	$('.wcefr-pdf-download').each(function(){
		
		$(this).on('click', function(){

			var order_id = $(this).data('order-id');

			var data = {
				'action': 'wcefr-download-pdf',
				'order-id': order_id
			}

			$.post(ajaxurl, data, function(response){

				console.log(response);
				window.open('http://localhost/wp-dev/wp-content/plugins/wc-exporter-for-reviso/includes/wcefr-invoice-preview.php?preview=true', 'Invoice preview', 'width=800, height=600');
			
			})

		})
	
	})

})