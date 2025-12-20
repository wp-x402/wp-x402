<?php

declare(strict_types=1);

namespace TheFrosty\WpX402;

use Multicoin\AddressValidator\CurrencyFactory;
use Multicoin\AddressValidator\WalletAddressValidator;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ServiceProvider
 * @package TheFrosty
 */
class ServiceProvider implements ServiceProviderInterface
{

    public const string HTTP_FOUNDATION_REQUEST = 'http.request';
    public const string WALLET_ADDRESS_VALIDATOR = 'address_validator';

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
    }
}
