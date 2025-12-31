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

use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use function defined;
use function is_readable;

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

const PLUGIN_SLUG = 'wp-x402';
const VERSION = '0.1.0';

$plugin = PluginFactory::create(PLUGIN_SLUG);
$container = $plugin->getContainer();
$container->register(new ServiceProvider());

$plugin
    ->add(new DisablePluginUpdateCheck())
    ->add(new Paywall\ForBots($container))
    ->add(new Paywall\ForHumans($container))
    ->addOnHook(Middleware\Middleware::class, 'rest_api_init');

if (is_admin()) {
    $plugin
        ->add(new Settings\General($container))
        ->add($container->get(ServiceProvider::WP_SETTINGS_API));
}

$plugin->initialize();
