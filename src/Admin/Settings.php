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
            __('Pre-orders', 'plogins-preorder'),
            __('Pre-orders', 'plogins-preorder'),
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
            esc_html__('Settings', 'plogins-preorder'),
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

        // Tiny vanilla mirror so merchants see the storefront button label as they
        // type. No framework, no jQuery — registered on its own handle, deferred.
        wp_register_script('preorder-admin', '', [], \Preorder\VERSION, true);
        wp_enqueue_script('preorder-admin');

        $preview = sprintf(
            'document.addEventListener("DOMContentLoaded",function(){'
            . 'var i=document.getElementById("preorder-button-text"),'
            . 'p=document.getElementById("preorder-button-preview");'
            . 'if(!i||!p)return;'
            . 'var d=%s;'
            . 'var sync=function(){p.textContent=(i.value.trim()||d);};'
            . 'i.addEventListener("input",sync);sync();});',
            wp_json_encode($this->store->defaultButtonText()),
        );

        wp_add_inline_script('preorder-admin', $preview);
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings       = $this->store->all();
        $enabled        = (bool) ($settings['enabled'] ?? true);
        $buttonText     = (string) ($settings['default_button_text'] ?? '');
        $defaultButton  = $this->store->defaultButtonText();
        $previewLabel   = '' !== trim($buttonText) ? $buttonText : $defaultButton;
        $saved          = isset($_GET['preorder-saved']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI flag.

        ?>
        <div class="wrap preorder-settings">
            <h1><?php echo esc_html__('Pre-orders', 'plogins-preorder'); ?></h1>

            <?php if ($saved) : ?>
                <div class="notice notice-success is-dismissible" role="status">
                    <p><?php echo esc_html__('Settings saved.', 'plogins-preorder'); ?></p>
                </div>
            <?php endif; ?>

            <p class="preorder-intro">
                <?php echo esc_html__('Flag any product as a pre-order from the product editor (Product data → General). The options here set the store-wide defaults that those products inherit.', 'plogins-preorder'); ?>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
                <?php wp_nonce_field(self::NONCE); ?>

                <div class="preorder-section">
                    <h2 class="preorder-section__title"><?php echo esc_html__('Storefront behaviour', 'plogins-preorder'); ?></h2>
                    <p class="preorder-section__lead">
                        <?php echo esc_html__('Controls whether the pre-order rules run on your live store.', 'plogins-preorder'); ?>
                    </p>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="preorder-enabled"><?php echo esc_html__('Enable pre-orders', 'plogins-preorder'); ?></label>
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
                                        <?php echo esc_html__('Apply pre-order behaviour on the storefront.', 'plogins-preorder'); ?>
                                    </label>
                                    <p class="description" id="preorder-enabled-help">
                                        <?php echo esc_html__('Lets flagged products stay purchasable while out of stock and shows the pre-order button. Turn this off to pause every pre-order store-wide in one click, without un-flagging each product. Default: on.', 'plogins-preorder'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="preorder-section">
                    <h2 class="preorder-section__title"><?php echo esc_html__('Pre-order button', 'plogins-preorder'); ?></h2>
                    <p class="preorder-section__lead">
                        <?php echo esc_html__('The label shoppers see in place of the usual add-to-cart text.', 'plogins-preorder'); ?>
                    </p>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="preorder-button-text"><?php echo esc_html__('Default button text', 'plogins-preorder'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="preorder-button-text"
                                        name="default_button_text"
                                        class="regular-text"
                                        value="<?php echo esc_attr($buttonText); ?>"
                                        placeholder="<?php echo esc_attr($defaultButton); ?>"
                                        aria-describedby="preorder-button-text-help"
                                    />
                                    <p class="preorder-preview" aria-hidden="true">
                                        <span class="preorder-preview__label"><?php echo esc_html__('Shoppers see:', 'plogins-preorder'); ?></span>
                                        <span class="preorder-preview__btn" id="preorder-button-preview"><?php echo esc_html($previewLabel); ?></span>
                                    </p>
                                    <p class="description" id="preorder-button-text-help">
                                        <?php
                                        printf(
                                            /* translators: %s: default button label, e.g. "Pre-order now". */
                                            esc_html__('Replaces the add-to-cart label on pre-order products. Leave blank to use %s. Any single product can override this from its own editor.', 'plogins-preorder'),
                                            '<code>' . esc_html($defaultButton) . '</code>',
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(__('Save changes', 'plogins-preorder')); ?>
            </form>
        </div>
        <?php
    }

    public function handleSave(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'plogins-preorder'));
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
