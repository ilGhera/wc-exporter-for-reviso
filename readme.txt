=== WooCommerce Exporter for Reviso ===
Contributors: ghera74
Tags: Reviso, Contabilità in Cloud, Team System, Danea Easyfatt, Fatturazione, Invoice
Version: 0.9.3
Requires at least: 4.0
Tested up to: 5.6
License: GPLv2

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

== Description ==

Export suppliers, products, customers and orders from your Woocommerce store to Reviso. Export new orders and create invoices in real time.

**AVAILABLE FEATURES**

* Export WordPress users to Reviso.
* Select one or more WordPress user level to export.
* Export products to Reviso (Premium).
* Export orders to Reviso (Premium).
* Export new orders to Reviso in real time (Premium).
* Delete all data in Reviso with a click.

= Try Reviso for free! =
[https://www.reviso.com/trial?src=hero](https://www.reviso.com/trial?src=hero)

**ITALIANO**
Esporta fornitori, prodotti, clienti e ordini dal tuo store WooCommerce a TeamSystem Reviso, ora rinominato per l'Italia in *Contabilità in Cloud*.
Esporta nuovi ordini e crea fatture in tempo reale.

**FUNZIONALITÀ DISPONIBILI**

* Esporta utenti WordPress come clienti o fornitori verso Reviso
* Seleziona uno o più livelli utente da esportare
* Esporta prodotti WooCommerce in Reviso (Premium)
* Esporta ordini WooCommerce in Reviso (Premium)
* Esporta nuovi ordini in tempo reale (Premium)
* Cancella dati in Reviso con un click

= Prova Reviso gratuitamente! =
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

* Upload the 'wc-exporter-for-reviso’ directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
* Activate Reviso for WooCommerce from your Plugins page.
* Once Activated, go to WooComerce/ WC Exporter for Reviso menu and set you preferences.

**From your WordPress dashboard**

* Visit *Plugins > Add New*
* Search for *WC Exporter for Reviso* and download it.
* Activate Reviso from your Plugins page.
* Once Activated, go to WooComerce/ WC Exporter for Reviso menu and set you preferences.


== Screenshots ==
1. WordPress users as clients in TeamSystem Reviso
2. WordPress users as suppliers in Reviso
3. Export all your WooCommerce products to Reviso
4. Select one or more WooCommerce orders statuses and export them
5. Create invoices in real time (1) 
6. Create invoices in real time (2)
7. View PDF Invoices directly in WordPress
8. Check the activities in progress
9. Invoice checkout fields


== Changelog ==


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
Release Date: 21 May 2020

* First release
