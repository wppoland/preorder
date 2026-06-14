<?php

declare(strict_types=1);

namespace Preorder;

defined('ABSPATH') || exit;

/**
 * Reads the per-product pre-order configuration stored as product meta.
 *
 * Meta keys:
 *  - _preorder_enabled "yes" | "" (checkbox)
 *
 * The accessor is defensive: a missing product or meta value yields a sane
 * default rather than an error.
 */
final class ProductMeta
{
    public const META_ENABLED = '_preorder_enabled';

    public function isPreorder(\WC_Product $product): bool
    {
        return 'yes' === $product->get_meta(self::META_ENABLED);
    }
}
