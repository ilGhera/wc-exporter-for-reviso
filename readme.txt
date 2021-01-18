=== WooCommerce Exporter for Reviso - Premium ===
Contributors: ghera74
Tags: Reviso, Contabilità in Cloud, Team System, Danea Easyfatt, Fatturazione, Invoice
Version: 0.9.4
Requires at least: 4.0
Tested up to: 5.6
License: GPLv2

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

== Description ==

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

**AVAILABLE FEATURES**

* Export WordPress users to Reviso.
* Select one or more WordPress user level to export.
* Export orders to Reviso.
* Export new orders to Reviso in real time.
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


= 0.9.4 = 
Release Date: 18 January 2021

* Bug fix: Impossible export payment method with name longer than 50 


= 0.9.3 = 
Release Date: 15 January 2021

* Bug fix: Order with product without sku not exported 
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
