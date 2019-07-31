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
		self.tzCheckbox();
	    self.wcefr_export_users();
	    self.wcefr_delete_remote_users();
		self.get_user_groups('customers');
		self.get_user_groups('suppliers');
		self.wcefr_export_products();
		self.wcefr_export_orders();
		self.wcefr_delete_remote_products();
		self.wcefr_delete_remote_orders();
		self.wcefr_disconnect();
	}


	/**
	 * Cancella i messaggi dell'admin
	 */
	self.delete_messages = function() {

		jQuery(function($){

			$('.yes, .not', '.wcefr-message ').html('');

		})

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

		        self.chosen(true);
		        self.chosen();

		        $('h2#wcefr-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
		        $this.addClass('nav-tab-active');

		        window.location = url + '#' + $this.data('link');

		        $('html, body').scrollTop(0);

		        /*Cancella messaggio admin*/
		        self.delete_messages();

		    })

		})
	        	
	}


	/**
	 * Checkboxes
	 */
	self.tzCheckbox = function() {

		jQuery(function($){
			$('input[type=checkbox]').tzCheckbox({labels:['On','Off']});
		});

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
				// console.log(response);

				if(response) {
			
					$('.check-connection').html(response);
					$('.wcefr-connect').hide();
					$('.wcefr-disconnect').animate({
						opacity: 1
					}, 500);
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
	 * Mostra un messaggio all'admin
	 * @param  {string} message il testo del messaggio
	 * @param  {bool}   error   in caso di errore stile differente 
	 */
	self.wcefr_response_message = function(message, error = false, update = false) {

		jQuery(function($){

			var container	  = error ? $('.wcefr-message .not') : $('.wcefr-message .yes');
			var message_class = error ? 'alert-danger' : 'alert-info';
			var icon		  = error ? 'fa-exclamation-triangle' : 'fa-info-circle';
			
			if ( update ) {

				$(container).append( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for Reviso </b> - ' + message + '</div>' );

			} else {

				$(container).html( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for Reviso </b> - ' + message + '</div>' );

			}

		})

	}


	self.wcefr_export_users = function() {

		jQuery(function($){

			$('.button-primary.wcefr.export-users').on('click', function(e){

				e.preventDefault();

				var type  = $(this).hasClass('customers') ? 'customers' : 'suppliers';
				var role  = $('.wcefr-' + type + '-role').val();
				var group = $('.wcefr-' + type + '-groups').val();

				var data = {
					'action': 'export-users',
					'type': type,
					'role': role,
					'group': group
				}

				$.post(ajaxurl, data, function(response){

					console.log(response);
					
					var result = JSON.parse(response);

					for (var i = 0; i < result.length; i++) {

						var error = 'error' === result[i][0] ? true : false;
						var update = 0 !== i ? true : false; 

						self.wcefr_response_message( result[i][1], error, false );

					}

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

					$.post(ajaxurl, data, function(response){

						var result = JSON.parse(response);

						console.log(result);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.wcefr_response_message( result[i][1], error, true );
	
						}

					})

				}

			})

		})

	}


	/**
	 * Export products to Reviso
	 */
	self.wcefr_export_products = function() {

		jQuery(function($){

			$('.button-primary.wcefr.export.products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var terms = $('.wcefr-products-categories').val();

				var data = {
					'action': 'export-products',
					'terms': terms
				}

				$.post(ajaxurl, data, function(response){
					
					console.log(response);
					
					var result = JSON.parse(response);

					for (var i = 0; i < result.length; i++) {

						var error = 'error' === result[i][0] ? true : false;
						var update = 0 !== i ? true : false; 

						self.wcefr_response_message( result[i][1], error, false );

					}

				})

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

				self.delete_messages();
							
				var answer = confirm( 'Vuoi cancellare tutti i prodotti da Reviso?' );

				if ( answer ) {

					var data = {
						'action': 'delete-remote-products',
					}

					$.post(ajaxurl, data, function(response){

						var result = JSON.parse(response);

						console.log(result);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.wcefr_response_message( result[i][1], error, true );
	
						}

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

				console.log(groups);

				if (typeof groups === 'object') {

					for (key in groups) {
						$('.wcefr-' + type + '-groups').append('<option value="' + key + '">' + groups[key] + '</option>');
					}

				} else {

					$('.wcefr-' + type + '-groups').append('<option>' + groups + '</option>');

				}

				$('.wcefr-' + type + '-groups').addClass('wcefr-select');
		        self.chosen(true);

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


	/**
	 * Export orders to Reviso
	 */
	self.wcefr_export_orders = function() {

		jQuery(function($){

			$('.button-primary.wcefr.export.orders').on('click', function(e){

				e.preventDefault();

				var statuses = $('.wcefr-orders-statuses').val();

				console.log(statuses);

				var data = {
					'action': 'export-orders',
					'statuses': statuses
				}

				$.post(ajaxurl, data, function(response){
					
					console.log(response);
					
					// var result = JSON.parse(response);

					// for (var i = 0; i < result.length; i++) {

					// 	var error = 'error' === result[i][0] ? true : false;
					// 	var update = 0 !== i ? true : false; 

					// 	self.wcefr_response_message( result[i][1], error, false );

					// }

				})

			})

		})

	}


	/**
	 * Cancella tutti gli ordini ra Reviso
	 */
	self.wcefr_delete_remote_orders = function() {

		jQuery(function($){

			$('.button-primary.wcefr.red.orders').on('click', function(e){

				e.preventDefault();
								
				var answer = confirm( 'Vuoi cancellare tutti gli ordini da Reviso?' );

				if ( answer ) {

					var data = {
						'action': 'delete-remote-orders',
					}

					$.post(ajaxurl, data, function(response){

						console.log(response);

					})

				}

			})

		})

	}


	/**
	 * Invoca chosen con tutte le opzioni definite	
	 * @param  {bool} destroy metodo distroy
	 */
	self.chosen = function(destroy = false) {

		jQuery(function($){

			$('.wcefr-select').chosen({
		
				disable_search_threshold: 10,
				width: '200px'
			
			});

		})

	}


}


jQuery(document).ready(function ($) {
	
	var Controller = new wcefrController;
	Controller.onLoad();

});
