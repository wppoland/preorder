<?php

declare(strict_types=1);

namespace Preorder\Admin;

defined('ABSPATH') || exit;

use Preorder\Contract\HasHooks;
use Preorder\ProductMeta;

/**
 * Adds the "Pre-order" control to the WooCommerce Product data box (General tab):
 * an enable checkbox. The value is sanitised on save; the nonce WooCommerce
 * already verifies for the product editor (`woocommerce_meta_nonce`) is
 * re-checked here.
 */
final class ProductDataPanel implements HasHooks
{
    public function registerHooks(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderFields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'saveFields']);
    }

    public function renderFields(): void
    {
        echo '<div class="options_group preorder-product-fields">';

        woocommerce_wp_checkbox([
            'id'          => ProductMeta::META_ENABLED,
            'label'       => __('Pre-order', 'preorder'),
            'description' => __('Sell this product as a pre-order. It stays purchasable even when out of stock.', 'preorder'),
        ]);

        echo '</div>';
    }

    public function saveFields(\WC_Product $product): void
    {
        // WooCommerce verifies woocommerce_meta_nonce before this hook fires; re-check defensively.
        $nonce = isset($_POST['woocommerce_meta_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['woocommerce_meta_nonce']))
            : '';

        if ('' === $nonce || ! wp_verify_nonce($nonce, 'woocommerce_save_data')) {
            return;
        }

        $enabled = isset($_POST[ProductMeta::META_ENABLED]) ? 'yes' : '';
        $product->update_meta_data(ProductMeta::META_ENABLED, $enabled);
    }
}
