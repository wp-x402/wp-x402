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
 * Plugin URI: https://github.com/wp-x402/wp-x402
 * GitHub Plugin URI: https://github.com/wp-x402/wp-x402
 * Primary Branch: main
 * Release Asset: true
 */

namespace WpX402\WpX402;

defined('ABSPATH') || exit;

use Dwnload\EddSoftwareLicenseManager\Edd;
use Dwnload\WpSettingsApi\WpSettingsApi;
use Exception;
use ReflectionClass;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use WpX402\WpX402\Settings\Factory;
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
    ->add(new Edd\LicenseManager($plugin, $container->get(ServiceProvider::LICENSE_DATA)))
    ->add(new Paywall\ForBots($container))
    ->addOnHook(Middleware\Middleware::class, 'rest_api_init');

if (is_admin()) {
    $plugin
        ->add(new Settings\Agents($container))
        ->add(new Settings\Factory($container))
        ->add(new Settings\General($container))
        ->add(new Settings\Misc($container));
}

add_action('init', static function () use ($plugin): void {
    $plugin->addOnHook(WpSettingsApi::class, admin_only: true, args: [Factory::getPluginSettings($plugin)]);
}, 2);

$plugin->initialize();

register_activation_hook(__FILE__, static function () use ($plugin): void {
    $manager = $plugin->getInit()->getWpHookObject(Edd\LicenseManager::class);
    try {
        (new ReflectionClass($manager))->getMethod('scheduleEvents')->invoke($manager);
    } catch (Exception) {
        // Nothing to do.
    }
});
