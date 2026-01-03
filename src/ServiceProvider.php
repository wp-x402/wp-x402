<?php

declare(strict_types=1);

namespace WpX402\WpX402;

use Dwnload\EddSoftwareLicenseManager\Edd\License;
use Dwnload\WpSettingsApi\WpSettingsApi;
use Multicoin\AddressValidator\CurrencyFactory;
use Multicoin\AddressValidator\WalletAddressValidator;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use WpX402\WpX402\Settings\Factory;
use function apply_filters;
use function is_array;

/**
 * Class ServiceProvider
 * @package WpX402
 */
class ServiceProvider implements ServiceProviderInterface
{

    public const string HTTP_FOUNDATION_REQUEST = 'http.request';
    public const string LICENSE_DATA = 'license_data';
    public const string WALLET_ADDRESS_VALIDATOR = 'address_validator';
    public const string WP_SETTINGS_API = 'wp_settings_api';

    /**
     * Register services.
     * @param PimpleContainer $pimple Container instance.
     */
    public function register(PimpleContainer $pimple): void
    {
        $pimple[self::HTTP_FOUNDATION_REQUEST] = static fn(): Request => Request::createFromGlobals();
        $pimple[self::LICENSE_DATA] = static fn(): array => self::getLicenseData();
        $pimple[self::WALLET_ADDRESS_VALIDATOR] = static fn(): WalletAddressValidator => new WalletAddressValidator(
            CurrencyFactory::createRegistry()
        );
        $pimple[self::WP_SETTINGS_API] = static fn(PimpleContainer $container): WpSettingsApi => new WpSettingsApi(
            Factory::getPluginSettings($container[PLUGIN_SLUG])
        );
    }

    /**
     * Build the EDD License Manager's config data array.
     * @return array
     */
    private static function getLicenseData(): array
    {
        static $data;
        if (is_array($data)) {
            return $data;
        }
        $data = [
            'api_url' => apply_filters('wp_x402_license_api_url', 'https://wp-x402.com/'),
            'license' => License::getLicenseKey(PLUGIN_SLUG),
            'item_name' => 'x402', // Name of this plugin (matching your EDD Download title).
            'author' => 'wp-x402',
            'item_id' => 14,
            'version' => VERSION,
        ];
        return $data;
    }
}
