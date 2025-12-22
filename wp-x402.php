<?php
/**
 * Plugin Name: x402
 * Description: A payments middleware for WordPress.
 * Author: Austin Passy
 * Author URI: https://austin.passy.co/
 * Version: 0.1.0
 * Requires at least: 6.7
 * Tested up to: 6.9.0
 * Requires PHP: 8.3
 * Plugin URI: https://github.com/thefrosty/wp-x402
 * GitHub Plugin URI: https://github.com/thefrosty/wp-x402
 * Primary Branch: main
 * Release Asset: true
 */

namespace TheFrosty\WpX402;

defined('ABSPATH') || exit;

use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use TheFrosty\WpX402\Settings\Settings;
use function defined;
use function is_readable;

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

const VERSION = '0.1.0';

$plugin = PluginFactory::create('wp-x402');
$container = $plugin->getContainer();
$container->register(new ServiceProvider());

$plugin
    ->add(new DisablePluginUpdateCheck())
    ->add(new Paywall\ForBots($container))
    ->add(new Settings($container))
    ->add(new WpSettingsApi(Settings::factory(VERSION)))
    ->addOnHook(Middleware\Middleware::class, 'rest_api_init')
    ->initialize();
