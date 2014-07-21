=== Plugin Name ===
Contributors: ebanx
Donate link: http://ebanx.com/
Tags: ebanx, woocommerce, payment
Requires at least: 3.7.0
Tested up to: 3.9.1
Stable tag: 1.2.0
License: BSD
License URI: http://opensource.org/licenses/BSD-3-Clause

EBANX is the market leader in e-commerce payment solutions for International Merchants selling online to Brazil.
This plugin enables you to integrate your WooCommerce store with the EBANX payment gateway.

== Description ==

This plugin enables you to integrate your WooCommerce store with the EBANX payment gateway.

It includes support to installments and also has bindings for the WooCommerce Extra Checkout Fields for Brazil
plugin (http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

== Installation ==

How to install the plugin:

1. Upload the ZIP file to your Wordpress installation directory
2. Extract the ZIP file contents to the /wp-content/plugins directory.
3. Visit your WooCommerce settings menu:
    WooCommerce > Settings > Payment Gateways > EBANX
4. Enable the EBANX payment gateway, and add your integration key.
5.1. Change the _Status Change Notification URL_ to:
```
{YOUR_SITE}/index.php/ebanx/notify/
```
5.2. Change the _Response URL_ to:
```
{YOUR_SITE}/index.php/ebanx/return/
```
6. That's all!

== Changelog ==
= 1.2.0=
* Improved error handling
= 1.1.0 =
* Added support to the Direct API
= 1.0.5 =
* Updated EBANX library
= 1.0.4 =
* Removed installments from checkout mode
= 1.0.3 =
* Fixed mode setter
= 1.0.2 =
* Bumped version
= 1.0.1 =
* Enforced minimum installment value (R$20).
* Fixed order ID/merchant code.
= 1.0.0 =
* First release.
