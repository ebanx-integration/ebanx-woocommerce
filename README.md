# EBANX WooCommerce Payment Gateway Plugin

This plugin enables you to integrate your WooCommerce store with the EBANX payment gateway.

It includes support to installments and also has bindings for the WooCommerce Extra Checkout Fields for Brazil
plugin (http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

## Requirements
* PHP 5.3+
* Wordpress 3.7+
* WooCommerce 2.1+

## Installation
1. Clone the git repo to your Wordpress /wp-content/plugins folder
```
git clone --recursive https://github.com/ebanx/ebanx-woocommerce.git
```
2. Visit your WooCommerce settings menu:
    WooCommerce > Settings > Payment Gateways > EBANX
3. Enable the EBANX payment gateway, and add your integration key.
4. Go to the EBANX Merchant Area, then to **Integration > Merchant Options**.
  1. Change the _Status Change Notification URL_ to:
```
{YOUR_SITE}/index.php/ebanx/notify/
```
  2. Change the _Response URL_ to:
```
{YOUR_SITE}/index.php/ebanx/return/
```
5. That's all!

## Changelog
* 1.11.4: fixed template issues
* 1.11.3: fixed template issue
* 1.11.2: bump version
* 1.11.1: fixed short tags
* 1.11.0: tested against WP4.0.1
* 1.10.0: PagoEfectivo RUC option
* 1.9.4: fixed return/notification on sandbox
* 1.9.3: fixed URLs for WooCommerce 2.1+
* 1.9.2: fixed business checkout
* 1.9.1: fixed checkout without plugin
* 1.9.0: implemented checkout for businesses
* 1.8.0: different checkout pages for each country, partial localization
* 1.7.0: installments for credit cards, PagoEfectivo, translations
* 1.6.1: updated library version to support new API endpoint.
* 1.6.0: EBANX checkout gets CPF and birthdate from extra parameters plugin
* 1.5.2: fixed checkout parameters usage
* 1.5.1: fixed clean cart code
* 1.5.0: implemented client side form validation, added Hipercard
* 1.4.0: localized error messages
* 1.3.0: updated payment methods image
* 1.2.1: fixed wrong checkout params
* 1.2.0: improved error handling
* 1.1.0: added support for the Direct API
* 1.0.5: updated EBANX library
* 1.0.4: removed installments from checkout mode
* 1.0.3: fixed mode setter
* 1.0.2: bumped version
* 1.0.1: enforced minimum installment value (R$20), fixed order ID/merchant code
* 1.0.0: first release
