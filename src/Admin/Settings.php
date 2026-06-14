<?php

declare(strict_types=1);

namespace Preorder\Admin;

defined('ABSPATH') || exit;

use Preorder\Contract\HasHooks;
use Preorder\Settings as SettingsStore;

/**
 * Settings screen registered under WooCommerce → Pre-orders.
 *
 * Stores two values in the `preorder_settings` option: the global enable toggle
 * and the default add-to-cart button label. All output is escaped, all input
 * sanitised, the form is nonce-protected and gated on the manage_woocommerce
 * capability.
 */
final class Settings implements HasHooks
{
    private const PAGE   = 'preorder-settings';
    private const NONCE  = 'preorder_save_settings';
    private const ACTION = 'preorder_settings';

    public function __construct(private readonly SettingsStore $store)
    {
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter(
            'plugin_action_links_' . plugin_basename(\Preorder\PLUGIN_FILE),
            [$this, 'addSettingsLink'],
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Pre-orders', 'preorder'),
            __('Pre-orders', 'preorder'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    /**
     * @param array<int, string> $links Existing action links.
     * @return array<int, string>
     */
    public function addSettingsLink(array $links): array
    {
        $url = add_query_arg('page', self::PAGE, admin_url('admin.php'));

        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Settings', 'preorder'),
        );

        array_unshift($links, $settingsLink);

        return $links;
    }

    public function enqueueAssets(string $hook): void
    {
        if ('woocommerce_page_' . self::PAGE !== $hook) {
            return;
        }

        wp_enqueue_style(
            'preorder-admin',
            PREORDER_URL . 'assets/css/admin.css',
            [],
            \Preorder\VERSION,
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings    = $this->store->all();
        $enabled     = (bool) ($settings['enabled'] ?? true);
        $buttonText  = (string) ($settings['default_button_text'] ?? '');
        $saved       = isset($_GET['preorder-saved']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI flag.

        ?>
        <div class="wrap preorder-settings">
            <h1><?php echo esc_html__('Pre-orders', 'preorder'); ?></h1>

            <?php if ($saved) : ?>
                <div class="notice notice-success is-dismissible" role="status">
                    <p><?php echo esc_html__('Settings saved.', 'preorder'); ?></p>
                </div>
            <?php endif; ?>

            <p class="preorder-intro">
                <?php echo esc_html__('Mark individual products as pre-orders from the product editor (Product data → General). These options control the storefront defaults.', 'preorder'); ?>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
                <?php wp_nonce_field(self::NONCE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="preorder-enabled"><?php echo esc_html__('Enable pre-orders', 'preorder'); ?></label>
                            </th>
                            <td>
                                <label class="preorder-toggle">
                                    <input
                                        type="checkbox"
                                        id="preorder-enabled"
                                        name="enabled"
                                        value="1"
                                        <?php checked($enabled); ?>
                                        aria-describedby="preorder-enabled-help"
                                    />
                                    <?php echo esc_html__('Apply pre-order behaviour on the storefront.', 'preorder'); ?>
                                </label>
                                <p class="description" id="preorder-enabled-help">
                                    <?php echo esc_html__('When off, products flagged as pre-orders behave like normal products. Turn this off to pause pre-orders store-wide without editing each product.', 'preorder'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="preorder-button-text"><?php echo esc_html__('Default button text', 'preorder'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="preorder-button-text"
                                    name="default_button_text"
                                    class="regular-text"
                                    value="<?php echo esc_attr($buttonText); ?>"
                                    placeholder="<?php echo esc_attr__('Pre-order now', 'preorder'); ?>"
                                    aria-describedby="preorder-button-text-help"
                                />
                                <p class="description" id="preorder-button-text-help">
                                    <?php echo esc_html__('Add-to-cart label shown for pre-order products. Individual products can override this in the product editor.', 'preorder'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save changes', 'preorder')); ?>
            </form>
        </div>
        <?php
    }

    public function handleSave(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'preorder'));
        }

        check_admin_referer(self::NONCE);

        $buttonText = isset($_POST['default_button_text'])
            ? sanitize_text_field(wp_unslash((string) $_POST['default_button_text']))
            : '';

        $settings = [
            'enabled'             => isset($_POST['enabled']),
            'default_button_text' => $buttonText,
        ];

        update_option(SettingsStore::OPTION, $settings);
        $this->store->flush();

        $redirect = add_query_arg(
            [
                'page'           => self::PAGE,
                'preorder-saved' => '1',
            ],
            admin_url('admin.php'),
        );

        wp_safe_redirect($redirect);
        exit;
    }
}
