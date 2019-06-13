/**
 * JS
 * @author ilGhera
 * @package wc-exporter-for-reviso/js
 * @since 0.9.0
 */


var wcefrController = function() {

	var self = this;

	self.onLoad = function() {
	    self.wcefr_pagination();
	    self.wcefr_delete_remote_users();
		self.get_user_groups('customer');
		self.get_user_groups('supplier');
		self.wcefr_delete_remote_products();
		self.wcefr_disconnect();
	}

	/**
	 * Gestisce la navigazione tra i tab della pagina opzioni
	 */
	self.wcefr_pagination = function() {

		jQuery(function($){

			var contents = $('.wcefr-admin')
			var url = window.location.href.split("#")[0];
			var hash = window.location.href.split("#")[1];

			if(hash) {
		        contents.hide();		    
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
		        
		        contents.hide();
		        $("#" + $this.data("link")).fadeIn(200);
		        $('h2#wcefr-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
		        $this.addClass('nav-tab-active');

		        window.location = url + '#' + $this.data('link');

		        $('html, body').scrollTop(0);

		    })

		})
	        	
	}


	/**
	 * Verifica connessione Reviso
	 */
	self.wcefr_check_connection = function() {

		jQuery(function($){

			var data = {
				'action': 'check-connection'
			}

			$.post(ajaxurl, data, function(response){
				console.log(response);

				if(response) {
			
					$('.check-connection').html(response);
					$('.wcefr-connect').hide();
					$('.wcefr-disconnect').show('slow');
				}

			})

		})

	}


	/**
	 * Disconnette il plugin dalla piattaforma Reviso, 
	 * cancellando l'Agreement Grant Tocken dal db
	 */
	self.wcefr_disconnect = function() {

		jQuery(function($){

			$(document).on('click', '.wcefr-disconnect', function(){

				console.log('Vai!');

				var data = {
					'action': 'wcefr-disconnect'
				}

				$.post(ajaxurl, data, function(response){
					location.reload();
				})

			})

		})

	}


	/**
	 * Cancellazione di tutti gli utenti da Reviso
	 */
	self.wcefr_delete_remote_users = function() {

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
	self.wcefr_delete_remote_products = function() {

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
	 * Mostra i gruppi di clienti e fornitori nella pagina opzioni del plugin
	 * @param {string} type cliente o fornitore
	 */
	self.get_user_groups = function(type) {

		jQuery(function($){

			var groups;
			var data = {
				'action': 'get-' + type + '-groups',
				'confirm': 'yes' 
			}

			$.post(ajaxurl, data, function(response){
				groups = JSON.parse(response);

				if (typeof groups === 'object') {

					for (key in groups) {
						$('.wcefr-' + type + '-groups').append('<option value="' + key + '">' + groups[key] + '</option>');
					}

				} else {

					$('.wcefr-' + type + '-groups').append('<option>' + groups + '</option>');

				}
			})

		})

	}


	/**
	 * Mostra i gruppi di fornitori presenti nella pagina opzioni del plugin
	 */
	self.get_supplier_groups = function() {

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


}


jQuery(document).ready(function ($) {
	
	var Controller = new wcefrController;
	Controller.onLoad();

});
