<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\PluginSettings;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use TheFrosty\WpUtilities\Plugin\Plugin;
use function esc_html__;

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
            'menu-title' => esc_html__('x402 Settings', 'crain'),
            'page-title' => esc_html__('x402 Settings', 'crain'),
            'prefix' => self::PREFIX,
            'version' => '1.0.0-20230221',
        ]);
    }
}
