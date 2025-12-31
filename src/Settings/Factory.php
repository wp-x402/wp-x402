<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\PluginSettings;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use TheFrosty\WpUtilities\Plugin\Plugin;
use function esc_html__;
use const TheFrosty\WpX402\VERSION;

/**
 * Class Factory
 * @package TheFrosty\WpX402\Settings
 */
class Factory
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
}
