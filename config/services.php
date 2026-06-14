<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Services are thin: storefront behaviour lives in PreorderService
 * and the admin UI in the Admin\* classes.
 *
 * @package Preorder
 */

declare(strict_types=1);

use Preorder\Admin\ProductDataPanel;
use Preorder\Admin\Settings as SettingsPage;
use Preorder\Container;
use Preorder\ProductMeta;
use Preorder\Service\PreorderService;
use Preorder\Settings;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Settings::class, static fn (): Settings => new Settings());
    $c->singleton(ProductMeta::class, static fn (): ProductMeta => new ProductMeta());

    $c->singleton(PreorderService::class, static fn (): PreorderService => new PreorderService(
        $c->get(Settings::class),
        $c->get(ProductMeta::class),
    ));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(SettingsPage::class, static fn (): SettingsPage => new SettingsPage(
            $c->get(Settings::class),
        ));
        $c->singleton(ProductDataPanel::class, static fn (): ProductDataPanel => new ProductDataPanel());
    }
};
