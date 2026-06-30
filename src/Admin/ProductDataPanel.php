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
            'label'       => __('Pre-order', 'plogins-preorder'),
            'description' => __('Sell this product as a pre-order. It stays purchasable even when out of stock.', 'plogins-preorder'),
        ]);

        woocommerce_wp_text_input([
            'id'          => ProductMeta::META_RELEASE_DATE,
            'label'       => __('Expected release date', 'plogins-preorder'),
            'description' => __('Optional. Shown on the product page and used by add-ons for release notifications.', 'plogins-preorder'),
            'type'        => 'date',
            'value'       => $this->releaseDateValue(),
        ]);

        echo '</div>';
    }

    private function releaseDateValue(): string
    {
        global $post;

        if (! $post instanceof \WP_Post) {
            return '';
        }

        $product = wc_get_product($post->ID);

        if (! $product instanceof \WC_Product) {
            return '';
        }

        return (new \Preorder\ProductMeta())->releaseDate($product);
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

        $releaseDate = isset($_POST[ProductMeta::META_RELEASE_DATE])
            ? sanitize_text_field(wp_unslash((string) $_POST[ProductMeta::META_RELEASE_DATE]))
            : '';

        if ('' !== $releaseDate && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
            $releaseDate = '';
        }

        if ('' === $releaseDate) {
            $product->delete_meta_data(ProductMeta::META_RELEASE_DATE);
        } else {
            $product->update_meta_data(ProductMeta::META_RELEASE_DATE, $releaseDate);
        }
    }
}
