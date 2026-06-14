<?php
/**
 * Uninstall cleanup for Preorder.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the option Preorder
 * creates. Per-product pre-order meta (_preorder_enabled) is intentionally left
 * in place: it is user content that may be shared with other tools and is cheap
 * to leave.
 *
 * @package Preorder
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('preorder_settings');
