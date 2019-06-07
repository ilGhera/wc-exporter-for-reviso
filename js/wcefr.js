/**
 * JS
 * @author ilGhera
 * @package wc-exporter-for-reviso/js
 * @since 0.9.0
 */


/**
 * Gestisce la navigazione tra i tab della pagina opzioni
 */
var wcefr_pagination = function() {

	jQuery(function($){

		var $contents = $('.wcefr-admin')
		var url = window.location.href.split("#")[0];
		var hash = window.location.href.split("#")[1];

		if(hash) {
	        $contents.hide();		    
		    $('#' + hash).fadeIn(200);		
	        $('h2#wcefr-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $('h2#wcefr-admin-menu a').each(function(){
	        	if($(this).data('link') == hash) {
	        		$(this).addClass('nav-tab-active');
	        	}
	        })
	        
	        $('html, body').animate({
	        	scrollTop: 0
	        }, 'slow');
		}

		$("h2#wcefr-admin-menu a").click(function () {
	        var $this = $(this);
	        
	        $contents.hide();
	        $("#" + $this.data("link")).fadeIn(200);
	        $('h2#wcefr-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $this.addClass('nav-tab-active');

	        window.location = url + '#' + $this.data('link');

	        $('html, body').scrollTop(0);

	    })

	})
        	
}


/**
 * Cancellazione di tutti gli utenti da Reviso
 */
var wcefr_delete_remote_users = function() {

	jQuery(function($){

		$('.button-primary.wcefr.red.users').on('click', function(e){

			e.preventDefault();

			var type = $(this).hasClass('customers') ? 'customers' : 'suppliers';
			
			console.log('Type: ' + type);
			
			var answer = confirm( 'Vuoi cancellare tutti i ' + type + ' da Reviso?' );

			if ( answer ) {

				var data = {
					'action': 'delete-remote-users',
					'type': type
				}

				console.log('Data: ' + JSON.stringify(data));
				$.post(ajaxurl, data, function(response){
					console.log(response);
				})

			}

		})

	})

}


/**
 * Cancellazione di tutti i prodotti da Reviso
 */
var wcefr_delete_remote_products = function() {

	jQuery(function($){

		$('.button-primary.wcefr.red.products').on('click', function(e){

			e.preventDefault();
						
			var answer = confirm( 'Vuoi cancellare tutti i prodotti da Reviso?' );

			if ( answer ) {

				var data = {
					'action': 'delete-remote-products',
				}

				console.log('Data: ' + JSON.stringify(data));
				$.post(ajaxurl, data, function(response){
					console.log(response);
				})

			}

		})

	})

}


/**
 * Mostra i gruppi di clienti presenti nella pagina opzioni del plugin
 */
var get_customer_groups = function() {

	jQuery(function($){

		var groups;
		var data = {
			'action': 'get-customer-groups',
			'confirm': 'yes' 
		}

		$.post(ajaxurl, data, function(response){
			groups = JSON.parse(response);
			for (key in groups) {
				$('.wcefr-customer-groups').append('<option value="' + key + '">' + groups[key] + '</option>');
			}
		})

	})

}


/**
 * Mostra i gruppi di fornitori presenti nella pagina opzioni del plugin
 */
var get_supplier_groups = function() {

	jQuery(function($){

		var groups;
		var data = {
			'action': 'get-supplier-groups',
			'confirm': 'yes' 
		}

		$.post(ajaxurl, data, function(response){
			groups = JSON.parse(response);
			for (key in groups) {
				$('.wcefr-supplier-groups').append('<option value="' + key + '">' + groups[key] + '</option>');
			}
		})

	})

}


jQuery(document).ready(function ($) {
	
    /*Navigazione tabs*/
    wcefr_pagination();

    wcefr_delete_remote_users();

	get_customer_groups();

	get_supplier_groups();

	wcefr_delete_remote_products();

});
