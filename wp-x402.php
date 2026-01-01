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

use Dwnload\EddSoftwareLicenseManager\Edd;
use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Settings\Factory;
use function defined;
use function is_readable;
use function sprintf;

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
    ->add(new Edd\PluginUpdater('https://wp-x402.com/', __FILE__, $container->get(ServiceProvider::LICENSE_DATA)))
    ->add(new Paywall\ForBots($container))
    ->add(new Paywall\ForHumans($container))
    ->addOnHook(Middleware\Middleware::class, 'rest_api_init');

if (is_admin()) {
    $plugin
        ->add(new Settings\Factory($container))
        ->add(new Settings\General($container));
}

add_action('init', static function () use ($plugin): void {
    $plugin->addOnHook(WpSettingsApi::class, admin_only: true, args: [Factory::getPluginSettings($plugin)]);
}, 2);

add_filter('dwnload_api_remote_post_args', static function (array $args): array {
    $args['user-agent'] = sprintf('%s/%s; %s', Api::USER_AGENT, VERSION, esc_url(get_bloginfo('url')));
    return $args;
});

$plugin->initialize();
