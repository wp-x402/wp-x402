<?php

declare(strict_types=1);

namespace WpX402\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\PluginSettings;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\Plugin;
use function array_unshift;
use function esc_html__;
use function sprintf;
use const WpX402\WpX402\VERSION;

/**
 * Class Factory
 * @package WpX402\WpX402\Settings
 */
class Factory extends AbstractContainerProvider
{
    public const string PREFIX = 'wp_x402_';

    /**
     * Helper to get the App object.
     * @param Plugin $plugin The plugin slug
     * @return PluginSettings
     */
    public static function getPluginSettings(Plugin $plugin): PluginSettings
    {
        return SettingsApiFactory::create([
            'domain' => $plugin->getSlug(),
            'file' => $plugin->getFile(),
            'menu-slug' => $plugin->getSlug(),
            'menu-title' => esc_html__('x402 Settings', 'wp-x402'),
            'page-title' => esc_html__('x402 Settings', 'wp-x402'),
            'prefix' => self::PREFIX,
            'version' => VERSION,
        ]);
    }

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('admin_enqueue_scripts', [$this, 'adminScripts']);
        $this->addFilter('plugin_action_links_' . $this->getPlugin()->getBasename(), [$this, 'addSettingsLink']);
    }

    /**
     * Enqueue our settings scripts.
     * @param string $hook
     */
    protected function adminScripts(string $hook): void
    {
        if ($hook !== 'settings_page_' . $this->getPlugin()->getSlug()) {
            return;
        }

        wp_register_script($this->getPlugin()->getSlug(), '');
        wp_enqueue_script($this->getPlugin()->getSlug());
        $data = <<<'SCRIPT'
            document.addEventListener('DOMContentLoaded', function () {
              const select = document.querySelector('select[name*="account"]')
              if (select) {
                const wallets = document.querySelectorAll('input[name*="_wallet"]')
                wallets.forEach((wallet) => {
                  if (!wallet.id.includes(select.value)) {
                    wallet.closest('tr').style.display = 'none'
                  }
                })
              }
              select.addEventListener('change', function () {
                const wallets = document.querySelectorAll('input[name*="_wallet"]')
                wallets.forEach((wallet) => {
                  if (!wallet.id.includes(this.value)) {
                    wallet.closest('tr').style.display = 'none'
                    return
                  }
                  wallet.closest('tr').style.display = ''
                })
              })
            })
            SCRIPT;
        wp_add_inline_script($this->getPlugin()->getSlug(), $data);
    }

    /**
     * Add settings page link to the plugins page.
     * @param array $actions
     * @return array
     */
    protected function addSettingsLink(array $actions): array
    {
        array_unshift(
            $actions,
            sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                menu_page_url($this->getPlugin()->getSlug(), false),
                esc_attr__('Settings for x402', 'wp-x402'),
                esc_html__('Settings', 'wp-x402')
            ),
        );

        return $actions;
    }
}
