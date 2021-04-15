/**
 * JS
 * 
 * @author ilGhera
 * @package wc-exporter-for-reviso/js
 * @since 0.9.8
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
		self.book_invoice();
		self.wcefr_check_connection();
	}


	/**
	 * Delete the admin messages
	 */
	self.delete_messages = function() {

		jQuery(function($){

			$('.yes, .not', '.wcefr-message ').html('');

		})

	}


	/**
	 * Tab navigation
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

		        /*Delete the admin messages*/
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
	 * Plugin tools available only if connected to Reviso
	 */
	self.wcefr_tools_control = function(deactivate = false) {

		jQuery(function($){

			if(deactivate) {

				$('.wcefr-form').addClass('disconnected');
				$('.wcefr-form.connection').removeClass('disconnected');

				$('.wcefr-form input').attr('disabled','disabled');
				$('.wcefr-form select').attr('disabled','disabled');

				$('.wcefr-suppliers-groups, .wcefr-customers-groups').addClass('wcefr-select');
		        self.chosen(true);

			} else {

				$('.wcefr-form').removeClass('disconnected');
				$('.wcefr-form input').removeAttr('disabled');
				$('.wcefr-form select').removeAttr('disabled');

			}


		})

	}
		

	/**
	 * Check the connection to Reviso
	 */
	self.wcefr_check_connection = function() {

		jQuery(function($){

			var data = {
				'action': 'wcefr-check-connection'
			}

			$.post(ajaxurl, data, function(response){

				if(response) {

					/*Activate plugin tools*/
					self.wcefr_tools_control();
			
					$('.check-connection').html(response);
					$('.wcefr-connect').hide();
					$('.wcefr-disconnect').css('display', 'inline-block');
					$('.wcefr-disconnect').animate({
						opacity: 1
					}, 500);

				} else {

					/*Deactivate plugin tools*/
					self.wcefr_tools_control(true);

				}

			})

		})

	}


	/**
	 * Disconnect from Reviso deleting the Agreement Grant Tocken from the db
	 */
	self.wcefr_disconnect = function() {

		jQuery(function($){

			$(document).on('click', '.wcefr-disconnect', function(){

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
	 * Adds a spinning gif to the message box waiting for the response
	 */
	self.wcefr_response_loading = function() {

		jQuery(function($){

			var container = $('.wcefr-message .yes');

			$(container).html('<div class="wcefr-loading"><img></div>');
			$('img', container).attr('src', wcefrSettings.responseLoading);

		})

	}


	/**
	 * Scroll page to the response message
	 */
	self.wcefr_response_scroll = function() {

		jQuery(function($){
	        
	        $('html, body').animate({
	        	scrollTop: $('.wcefr-message').offset().top
	        }, 'slow');

		})

	}


	/**
	 * Show a message to the admin
	 * @param  {string} message the text
	 * @param  {bool}   error   different style with true
	 */
	self.wcefr_response_message = function(message, error = false, update = false) {

		jQuery(function($){

			/*Remove the loading gif*/
			$('.wcefr-message .yes').html('');

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


	/**
	 * Export WP users to Reviso
	 */
	self.wcefr_export_users = function() {

		jQuery(function($){

			$('.button-primary.wcefr.export-users').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
				self.wcefr_response_loading();
				self.wcefr_response_scroll();

				var type  = $(this).hasClass('customers') ? 'customers' : 'suppliers';
				var role  = $('.wcefr-' + type + '-role').val();
				var group = $('.wcefr-' + type + '-groups').val();

				var data = {
					'action': 'wcefr-export-users',
					'wcefr-export-users-nonce': wcefrUsers.exportNonce,
					'type': type,
					'role': role,
					'group': group
				}

				$.post(ajaxurl, data, function(response){

					if (response) {

						var result = JSON.parse(response);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.wcefr_response_message( result[i][1], error, false );

						}

					}

				})
			
			})

		})

	}


	/**
	 * Delete all the users from Reviso
	 */
	self.wcefr_delete_remote_users = function() {

		jQuery(function($){

			$('.button-primary.wcefr.red.users').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var type   = $(this).hasClass('customers') ? 'customers' : 'suppliers';
				var answer = confirm( 'Vuoi cancellare tutti i ' + type + ' da Reviso?' ); //temp.

				if ( answer ) {

					self.wcefr_response_loading();
					self.wcefr_response_scroll();

					var data = {
						'action': 'wcefr-delete-remote-users',
						'wcefr-delete-users-nonce': wcefrUsers.deleteNonce,
						'type': type
					}


					$.post(ajaxurl, data, function(response){

						if (response) {

							var result = JSON.parse(response);

							for (var i = 0; i < result.length; i++) {

								var error = 'error' === result[i][0] ? true : false;
								var update = 0 !== i ? true : false; 

								self.wcefr_response_message( result[i][1], error, false );
		
							}

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
				self.wcefr_response_loading();
				self.wcefr_response_scroll();

				var terms = $('.wcefr-products-categories').val();

				var data = {
					'action': 'wcefr-export-products',
					'wcefr-export-products-nonce': wcefrProducts.exportNonce,
					'terms': terms
				}

				$.post(ajaxurl, data, function(response){

					if (response) {

						var result = JSON.parse(response);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.wcefr_response_message( result[i][1], error, false );

						}

					}

				})

			})

		})

	}


	/**
	 * Delete all the products from Reviso
	 */
	self.wcefr_delete_remote_products = function() {

		jQuery(function($){

			$('.button-primary.wcefr.red.products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
							
				var answer = confirm( 'Vuoi cancellare tutti i prodotti da Reviso?' );

				if ( answer ) {

					self.wcefr_response_loading();
					self.wcefr_response_scroll();

					var data = {
						'action': 'wcefr-delete-remote-products',
						'wcefr-delete-products-nonce': wcefrProducts.deleteNonce,
					}

					$.post(ajaxurl, data, function(response){


						if (response) {

							var result = JSON.parse(response);

							for (var i = 0; i < result.length; i++) {

								var error = 'error' === result[i][0] ? true : false;
								var update = 0 !== i ? true : false; 

								self.wcefr_response_message( result[i][1], error, false );
		
							}

						}

					})

				}

			})

		})

	}


	/**
	 * Show customers and suppliers groups in the plugin options page
	 * @param {string} type customer or supplier
	 */
	self.get_user_groups = function(type) {

		jQuery(function($){

            var optionSelected;
            var isTabOrders;
            var selectClass;
            var selected;
			var groups;

			var data = {
				'action': 'wcefr-get-' + type + '-groups',
				'confirm': 'yes' 
			}

			$.post(ajaxurl, data, function(response){

				if (response) {

					groups = JSON.parse(response);

                    $('.wcefr-' + type + '-groups').each(function(){

                        isTabOrders    = $(this).hasClass('wcefr-orders-customers-group') ? true : false;
                        selectClass    = isTabOrders ? 'wcefr-select-large' : 'wcefr-select';
                        optionSelected = $(this).attr('data-group-selected');

                        if (typeof groups === 'object') {

                            for (key in groups) {

                                selected = key == optionSelected ? ' selected="selected"' : false;
                                $(this).append('<option value="' + key + '"' + selected + '>' + groups[key] + '</option>');

                            }

                        } else {

                            $(this).append('<option>' + groups + '</option>');

                        }

                        $(this).addClass(selectClass);
                        self.chosen(true);

                    })

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

				self.delete_messages();
				self.wcefr_response_loading();
				self.wcefr_response_scroll();

				var statuses = $('.wcefr-orders-statuses').val();

				var data = {
					'action': 'wcefr-export-orders',
					'wcefr-export-orders-nonce': wcefrOrders.exportNonce,
					'statuses': statuses
				}

				$.post(ajaxurl, data, function(response){

					if (response) {

						var result = JSON.parse(response);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.wcefr_response_message( result[i][1], error, false );

						}

					}

				})

			})

		})

	}


	/**
	 * Delete all orders from Reviso
	 */
	self.wcefr_delete_remote_orders = function() {

		jQuery(function($){

			$('.button-primary.wcefr.red.orders').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
								
				var answer = confirm( 'Vuoi cancellare tutti gli ordini da Reviso?' );

				if ( answer ) {

					self.wcefr_response_loading();
					self.wcefr_response_scroll();

					var data = {
						'action': 'wcefr-delete-remote-orders',
						'wcefr-delete-orders-nonce': wcefrOrders.deleteNonce,
					}

					$.post(ajaxurl, data, function(response){

						if (response) {

							var result = JSON.parse(response);

							for (var i = 0; i < result.length; i++) {

								var error = 'error' === result[i][0] ? true : false;
								var update = 0 !== i ? true : false; 

								self.wcefr_response_message( result[i][1], error, false );

							}

						}

					})

				}

			})

		})

	}


	/**
	 * Show the book invoices option only with issue invoices option activated
	 */
	self.book_invoice = function() {

		jQuery(function($){

			var create_invoice_button = $('.wcefr-create-invoices-field td span.tzCheckBox');
			var issue_invoice_button  = $('.wcefr-issue-invoices-field td span.tzCheckBox');
			var	invoices_field        = $('.wcefr-invoices-field');
			var	book_invoices_field   = $('.wcefr-book-invoices-field');
			var	send_invoices_field   = $('.wcefr-send-invoices-field');
			
			if ( $(create_invoice_button).hasClass('checked') ) {
				
				invoices_field.show();

				if ( $(issue_invoice_button).hasClass('checked') ) {

					book_invoices_field.show();
					send_invoices_field.show();

				} else {
				
					book_invoices_field.hide();			
					send_invoices_field.hide();			
		
				}

			}

			/*Hide all if invoices creation is disabled*/
			$(create_invoice_button).on( 'click', function(){

				if ( $(this).hasClass('checked') ) {
				
					invoices_field.show();

					if ( $(issue_invoice_button).hasClass('checked') ) {

					book_invoices_field.show();
					send_invoices_field.show();

				} else {
				
					book_invoices_field.hide();			
					send_invoices_field.hide();			
		
				}
				
				} else {
				
					invoices_field.hide('slow');
	
				}

			})

			/*Hide options related to the issue of invoices*/
			$(issue_invoice_button).on( 'click', function(){
				
				if ( $(this).hasClass('checked') ) {
				
					book_invoices_field.show();
					send_invoices_field.show();
				
				} else {
				
					book_invoices_field.hide('slow');			
					send_invoices_field.hide('slow');			
		
				}

			})
		})

	}


	/**
	 * Fires Chosen
	 * @param  {bool} destroy method distroy
	 */
	self.chosen = function(destroy = false) {

		jQuery(function($){

			$('.wcefr-select').chosen({
		
				disable_search_threshold: 10,
				width: '200px'
			
			});

			$('.wcefr-select-large').chosen({
		
				disable_search_threshold: 10,
				width: '290px'
			
			});

		})

	}


}


/**
 * Class starter with onLoad method
 */
jQuery(document).ready(function($) {
	
	var Controller = new wcefrController;
	Controller.onLoad();

});
