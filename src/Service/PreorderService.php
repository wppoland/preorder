<?php

declare(strict_types=1);

namespace Preorder\Service;

defined('ABSPATH') || exit;

use Preorder\Contract\HasHooks;
use Preorder\ProductMeta;
use Preorder\Settings;

/**
 * Storefront behaviour for pre-order products.
 *
 * When a product is flagged as a pre-order it becomes purchasable even while out
 * of stock, the add-to-cart button label changes, and the cart line + order line
 * are flagged as pre-orders for fulfilment.
 *
 * Everything degrades gracefully: if the plugin is globally disabled, the product
 * is not a pre-order, or WooCommerce data is missing, the methods short-circuit
 * and leave the default storefront behaviour untouched.
 */
final class PreorderService implements HasHooks
{
    private const CART_FLAG = 'preorder_is_preorder';

    public function __construct(
        private readonly Settings $settings,
        private readonly ProductMeta $meta,
    ) {
    }

    public function registerHooks(): void
    {
        if (! $this->settings->isEnabled()) {
            return;
        }

        // Make pre-order products purchasable while out of stock.
        add_filter('woocommerce_is_purchasable', [$this, 'filterPurchasable'], 10, 2);
        add_filter('woocommerce_product_is_in_stock', [$this, 'filterInStock'], 10, 2);
        add_filter('woocommerce_product_backorders_allowed', [$this, 'filterBackordersAllowed'], 10, 2);

        // Change the add-to-cart button label.
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'filterButtonText'], 10, 2);
        add_filter('woocommerce_product_add_to_cart_text', [$this, 'filterButtonText'], 10, 2);

        // Flag the cart item, surface it in the cart/checkout, and copy to the order.
        add_filter('woocommerce_add_cart_item_data', [$this, 'addCartItemData'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'displayCartItemData'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'addOrderItemMeta'], 10, 4);
    }

    private function applies(mixed $product): bool
    {
        return $product instanceof \WC_Product && $this->meta->isPreorder($product);
    }

    /**
     * @param bool $purchasable Current purchasability.
     */
    public function filterPurchasable(bool $purchasable, \WC_Product $product): bool
    {
        if ($purchasable || ! $this->applies($product)) {
            return $purchasable;
        }

        // A pre-order must still have a price to be purchasable.
        return '' !== (string) $product->get_price();
    }

    /**
     * @param bool $inStock Current stock status.
     */
    public function filterInStock(bool $inStock, \WC_Product $product): bool
    {
        if ($inStock || ! $this->applies($product)) {
            return $inStock;
        }

        return true;
    }

    /**
     * @param bool $allowed Current backorder permission.
     */
    public function filterBackordersAllowed(bool $allowed, int $productId): bool
    {
        if ($allowed) {
            return $allowed;
        }

        $product = wc_get_product($productId);
        if (! $this->applies($product)) {
            return $allowed;
        }

        return true;
    }

    /**
     * @param string $text Current button label.
     */
    public function filterButtonText(string $text, \WC_Product $product): string
    {
        if (! $this->applies($product)) {
            return $text;
        }

        return $this->settings->defaultButtonText();
    }

    /**
     * Flag the cart item when a pre-order product is added.
     *
     * @param array<string, mixed> $cartItemData Existing cart item data.
     * @return array<string, mixed>
     */
    public function addCartItemData(array $cartItemData, int $productId, int $variationId): array
    {
        $targetId = $variationId > 0 ? $variationId : $productId;
        $product  = wc_get_product($targetId);

        if (! $this->applies($product)) {
            return $cartItemData;
        }

        $cartItemData[self::CART_FLAG] = true;

        return $cartItemData;
    }

    /**
     * Surface the pre-order flag in the cart and checkout line item.
     *
     * @param array<int, array{key: string, value: string, display?: string}> $itemData Existing display rows.
     * @param array<string, mixed>                                             $cartItem The cart item.
     * @return array<int, array{key: string, value: string, display?: string}>
     */
    public function displayCartItemData(array $itemData, array $cartItem): array
    {
        if (empty($cartItem[self::CART_FLAG])) {
            return $itemData;
        }

        $itemData[] = [
            'key'   => __('Pre-order', 'preorder'),
            'value' => __('Yes', 'preorder'),
        ];

        return $itemData;
    }

    /**
     * Copy the pre-order flag onto the order line item for fulfilment.
     *
     * @param array<string, mixed> $values The cart item values.
     */
    public function addOrderItemMeta(
        \WC_Order_Item_Product $item,
        string $cartItemKey,
        array $values,
        \WC_Order $order
    ): void {
        if (empty($values[self::CART_FLAG])) {
            return;
        }

        $item->add_meta_data(__('Pre-order', 'preorder'), __('Yes', 'preorder'), true);
    }
}
