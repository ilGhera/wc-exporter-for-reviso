=== WooCommerce Exporter for Reviso - Premium ===
Contributors: ghera74
Tags: Reviso, Contabilità in Cloud, Team System, Danea Easyfatt, Fatturazione, Invoice
Version: 1.1.1
Requires at least: 4.0
Tested up to: 6.1
License: GPLv2

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

== Description ==

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

**AVAILABLE FEATURES**

* Export WordPress users to Reviso.
* Select one or more WordPress user level to export.
* Update customers and suppliers in Reviso in real time.
* Export products to Reviso.
* Select one or more product categories to export.
* Update products in Reviso in real time.
* Export orders to Reviso.
* Export new orders to Reviso in real time.
* Add specific fields for electronic invoicing to the checkout form.
* Delete all data in Reviso with a click.

= Try Reviso for free! =
[https://www.reviso.com/trial?src=hero](https://www.reviso.com/trial?src=hero)


**IMPORTANT NOTES**

This plugin sends data to an external service, like the products bought by the user and profile informations.

= Service informations: =
[https://www.reviso.com/](https://www.reviso.com/)

= Service endpoint: =
[https://rest.reviso.com/](https://rest.reviso.com/)

= Service privacy policy: =
[https://www.reviso.com/privacy-policy](https://www.reviso.com/privacy-policy)


== Installation ==

* Upload the 'wc-exporter-for-reviso-premium’ directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
* Activate Reviso for WooCommerce from your Plugins page.
* Once Activated, go to WooComerce/ WC Exporter for Reviso menu and set you preferences.


== Screenshots ==


== Changelog ==


= 1.1.1
Release Date: 7 November 2022

* Enhancement: WordPress 6.1 support 
* Enhancement: PUC v5 support
* Update: Plugin Update Checker
* Bug fix: Failure to create invoice with massive export orders 


= 1.1.0
Release Date: 15 October 2022

* Enhancement: Export and update products in real time 
* Enhancement: Export and update suppliers in real time 
* Enhancement: Export and update clients in real time 
* Enhancement: Save users role for clients and suppliers with Ajax 
* Upadate: Plugin Update Checker
* Update: Action Scheduler
* Bug fix: Departmental distribution missed in new orders exported


= 1.0.2
Release Date: 21 April 2022

* Bug fix: Impossible delete Number Series transient
* Bug fix: Possible error getting the remote VAT code
* Bug fix: Departmental distribution options available even with Dimension module disabled
* Bug fix: Bad discount percentage because decimal numbers were missed
* Bug fix: VatIncluded value not dynamic
* Bug fix: Product volume not exported to Reviso


= 1.0.1
Release Date: 3 February 2022

* Bug fix: Page and post preview not working 


= 1.0.0
Release Date: 31 January 2022

* Enhancement: New tab checkout in pluign settings page 
* Enhancement: More speed and efficiency thanks to the use of transients 
* Enhancement: New tool to delete temporary data
* Enhancement: Now is possible set a generic Departmental distribution for all the products
* Enhancement: Set a specific Departmental distribution in every single product page
* Enhancement: Plugin Update checker library updated 
* Enhancement: Action Scheduler library updated 
* Bug fix: Bug creating a new vat account 
* Bug fix: Bug creating a new additional expense 


= 0.9.13
Release Date: 14 October 2021

* Enhancement: Customer name is now used as contact in Reviso when the company name is present
* Bug fix: Product variations not exported while using category filter tool


= 0.9.12
Release Date: 30 July 2021

* Bug fix: Product group not created in some cases by exporting products 


= 0.9.11
Release Date: 15 July 2021

* Bug fix: Wrong VAT code assigned in Reviso exporting orders


= 0.9.10
Release Date: 28 April 2021 

* Bug fix: Empty PEC field error
* Bug fix: Translation missed for bad PEC error message  


= 0.9.9
Release Date: 27 April 2021 

* Enhancement: PDF icon displayed even for draft invoices
* Enhancement: Default receiver code added automatically when necessary
* Bug fix: Cusomer data already present in revision not modified while exporting orders
* Bug fix: Bad email address allowed in PEC field


= 0.9.8 
Release Date: 15 April 2021 

* Enhancement: Choose the Reviso user group to use for auto exported WooCommerce orders 
* Bug fix: Backend scripts loaded even where not necessary
* Bug fix: Customer phone number not exported 
* Bug Fix: Shop Country statically set to Italy


= 0.9.7 =
Release Date: 16 February 2021 

* Bug fix: Billing data entered not recognized 


= 0.9.6 =
Release Date: 8 February 2021 

* Bug fix: VIES check not working in some cases with italian VAT number
* Bug fix: Billing information not required by the selected document type


= 0.9.5 = 
Release Date: 25 January 2021

* Enhancement: Choose a specific number series for receipts
* Bug fix: wrong discount percentage exporting orders


= 0.9.4 = 
Release Date: 18 January 2021

* Enhancement: VIES VAT number validation
* Enhancement: New option for mandatory fiscal code
* Enhancement: New option for mandatory VAT code only in European Union countries
* Bug fix: Company name field hidden with private invoice 
* Bug fix: Impossible exporting payment methods with name longer than 50 


= 0.9.3 = 
Release Date: 15 January 2021

* Bug fix: Orders with products without sku not exported 
* Bug fix: Wrong number of decimals in serialized data exporting orders  


= 0.9.2 = 
Release Date: 11 January 2021

* Enhancement: Now working even if Inventory module is not activated in Reviso
* Bug fix: Product without sku non exporterd 
* Bug fix: Get province called even if country is not Italy
* Bug fix: Get percentage error in case of value zero
* Bug fix: Get document number from Reviso 
* Bug fix: Layout value removed exporting orders
* Bug fix: Create new payment term in Reviso
* Bug fix: Invoice preview link not working in Multisite


= 0.9.1 = 
Release Date: 20 June 2020

* Bug fix: Error exporting orders made by guest users
* Bug fix: Wrong customers group assigned to foreign users
* Bug fix: Wrong IVA Zone assigned to foreign users


= 0.9.0 = 
Release Date: 18 May 2020

* First release
