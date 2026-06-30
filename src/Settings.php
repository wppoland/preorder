<?php

declare(strict_types=1);

namespace Preorder;

defined('ABSPATH') || exit;

/**
 * Typed accessor over the `preorder_settings` option. Reads are merged over the
 * shipped defaults so a partial or missing option never yields a broken state.
 */
final class Settings
{
    public const OPTION = 'preorder_settings';

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require PREORDER_DIR . 'config/defaults.php';

        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return $this->cache = array_merge($defaults, $stored);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->all()['enabled'] ?? true);
    }

    public function defaultButtonText(): string
    {
        $text = (string) ($this->all()['default_button_text'] ?? '');
        $text = trim($text);

        return '' !== $text ? $text : __('Pre-order now', 'plogins-preorder');
    }

    /**
     * Forget the cached option (used after a save in the same request).
     */
    public function flush(): void
    {
        $this->cache = null;
    }
}
