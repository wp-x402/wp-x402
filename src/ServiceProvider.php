<?php

declare(strict_types=1);

namespace TheFrosty\WpX402;

use Dwnload\WpSettingsApi\WpSettingsApi;
use Multicoin\AddressValidator\CurrencyFactory;
use Multicoin\AddressValidator\WalletAddressValidator;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpX402\Settings\Factory;

/**
 * Class ServiceProvider
 * @package TheFrosty
 */
class ServiceProvider implements ServiceProviderInterface
{

    public const string HTTP_FOUNDATION_REQUEST = 'http.request';
    public const string WALLET_ADDRESS_VALIDATOR = 'address_validator';
    public const string WP_SETTINGS_API = 'wp_settings_api';

    /**
     * Register services.
     * @param PimpleContainer $pimple Container instance.
     */
    public function register(PimpleContainer $pimple): void
    {
        $pimple[self::HTTP_FOUNDATION_REQUEST] = static fn(): Request => Request::createFromGlobals();
        $pimple[self::WALLET_ADDRESS_VALIDATOR] = static fn(): WalletAddressValidator => new WalletAddressValidator(
            CurrencyFactory::createRegistry()
        );
        $pimple[self::WP_SETTINGS_API] = static fn(PimpleContainer $container): WpSettingsApi => new WpSettingsApi(
            Factory::getPluginSettings($container[PLUGIN_SLUG])
        );
    }
}
