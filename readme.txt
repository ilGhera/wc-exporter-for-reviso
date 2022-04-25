=== WooCommerce Exporter for Reviso ===
Contributors: ghera74
Tags: Reviso, Contabilità in Cloud, Team System, Danea Easyfatt, Fatturazione, Invoice
Version: 1.0.0
Requires at least: 4.0
Tested up to: 5.9
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

https://youtu.be/gtyhllUEqN4

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

= 1.0.0
Release Date: 25 April 2022

* Bug fix: Impossible delete Number Series transient
* Bug fix: Possible error getting the remote VAT code
* Bug fix: Departmental distribution options available even with Dimension module disabled
* Bug fix: Bad discount percentage because decimal numbers were missed
* Bug fix: VatIncluded value not dynamic
* Bug fix: Product volume not exported to Reviso


= 0.9.6
Release Date: 14 October 2021

* Enhancement: Customer name is now used as contact in Reviso when the company name is present
* Bug fix: Product variations not exported while using category filter tool


= 0.9.5
Release Date: 28 April 2021 

* Enhancement: (Premium) PDF icon displayed even for draft invoices
* Enhancement: Default receiver code added automatically when necessary
* Bug fix: (Premium) Customer data already present in revision not modified while exporting orders
* Bug fix: Bad email address allowed in PEC field


= 0.9.4 =
Release Date: 15 April 2021 

* Enhancement: (Premium) Choose the Reviso user group to use for auto exported WooCommerce orders 
* Bug fix: Backend scripts loaded even where not necessary
* Bug fix: Customer phone number not exported 
* Bug Fix: Shop Country statically set to Italy


= 0.9.3 =
Release Date: 16 February 2021 

* Bug fix: Billing data entered not recognized 


= 0.9.2 =
Release Date: 8 February 2021 

* Enhancement: (Premium) Choose a specific number series for receipts
* Bug fix: VIES check not working in some cases with italian VAT number
* Bug fix: Billing information not required by the selected document type


= 0.9.1 = 
Release Date: 20 June 2020

* Enhancement: Now working even if Inventory module is not activated in Reviso
* Enhancement: VIES VAT number validation
* Enhancement: New option for mandatory fiscal code
* Enhancement: New option for mandatory VAT code only in European Union countries
* Bug fix: Company name field hidden with private invoice 
* Bug fix: Impossible exporting payment methods with name longer than 50 
* Bug fix: Wrong customers group assigned to foreign users
* Bug fix: Wrong IVA Zone assigned to foreign users
* Bug fix: Get province called even if country is not Italy
* Bug fix: Product without sku non exporterd 


= 0.9.0 = 
Release Date: 21 May 2020

* First release
