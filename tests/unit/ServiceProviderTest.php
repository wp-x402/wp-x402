<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests;

use Multicoin\AddressValidator\WalletAddressValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\Container;
use WpX402\WpX402\ServiceProvider;
use const WpX402\WpX402\VERSION;

/**
 * Class ServiceProviderTest
 * @package WpX402\WpX402\Tests
 */
#[CoversClass(ServiceProvider::class)]
class ServiceProviderTest extends TestCase
{

    /**
     * Test register().
     */
    public function testRegister(): void
    {
        $container = new Container();
        (new ServiceProvider())->register($container);

        $this->assertInstanceOf(Request::class, $container->get(ServiceProvider::HTTP_FOUNDATION_REQUEST));
        $this->assertInstanceOf(
            WalletAddressValidator::class,
            $container->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR)
        );

        $license = $container->get(ServiceProvider::LICENSE_DATA);
        $this->assertIsArray($license);
        $this->assertSame('https://wp-x402.com/', $license['api_url']);
        $this->assertSame(14, $license['item_id']);
        $this->assertSame(VERSION, $license['version']);
    }
}
