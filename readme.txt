=== Preorder - Pre-Orders for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, preorder, pre-order, backorder, out of stock
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers pre-order upcoming or out-of-stock WooCommerce products with a custom add-to-cart button.

== Description ==

Preorder lets you sell WooCommerce products before they are in stock. Tick a box
on the product and it stays purchasable even when its stock status is out of
stock, so a customer can reserve an upcoming release or a restock instead of
landing on a dead "out of stock" page.

On the storefront, pre-order products get a custom add-to-cart label (for example
"Pre-order now"), and each pre-order line is flagged in the cart and copied onto
the order, so you can tell pre-orders apart when you pack and ship.

The plugin is developed in the open. Source and bug reports:
https://github.com/wppoland/preorder

= Features =

* A **Pre-order** checkbox on every product, under **Product data → General**.
* A custom add-to-cart label for pre-order products, set store-wide.
* Pre-order products stay purchasable while their stock status is out of stock.
* The cart and checkout show a "Pre-order: Yes" row on each pre-order line.
* That flag is copied onto the order line item, so it shows on the order screen and packing slips.
* A **WooCommerce → Pre-orders** screen with a store-wide on/off switch and the default button text.
* Pausing the on/off switch makes flagged products behave like normal products again, without editing each one.
* Forms are nonce-checked and limited to users who can manage WooCommerce; output is escaped and input sanitised.
* Ships with a translation template (preorder.pot) and a Polish translation; removing the plugin deletes its setting.
* Works with WooCommerce HPOS and the cart and checkout blocks.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/preorder`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Edit a product, open **Product data → General**, and tick **Pre-order**.
4. Adjust store-wide defaults under **WooCommerce → Pre-orders**.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. WooCommerce must be installed and active.

= What happens when a product is marked as a pre-order? =

It becomes purchasable even when out of stock, its add-to-cart button label
changes, and the cart and order lines are flagged as pre-orders.

= Can I pause pre-orders without editing every product? =

Yes. Turn off the global toggle under **WooCommerce → Pre-orders** and flagged
products behave like normal products until you turn it back on.

== Screenshots ==

1. The pre-order field in the WooCommerce product editor.
2. The WooCommerce → Pre-orders settings screen.

== Changelog ==

= 0.1.0 =
* Initial release: per-product pre-order flag, custom button text, out-of-stock purchasability, and cart and order flagging, with a settings screen.
