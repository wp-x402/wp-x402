<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Api;

use Multicoin\AddressValidator\CurrencyFactory;
use Multicoin\AddressValidator\WalletAddressValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Tests\TestCase;
use function add_filter;
use function json_decode;
use function remove_all_filters;
use const JSON_THROW_ON_ERROR;
use const WpX402\WpX402\VERSION;

/**
 * Class ApiTest
 * @package WpX402\WpX402\Tests\Api
 */
#[CoversClass(Api::class)]
class ApiTest extends TestCase
{

    /**
     * Wallet address, is valid.
     * @return array<string, array{string, bool}>
     */
    public static function walletProvider(): array
    {
        return [
            'evm' => ['0x209693Bc6afc0C5328bA36FaF03C514EF312287C', true],
            'solana' => ['CKPKJWNdJEqa81x7CkZ14BVPiY6y16Sxs7owznqtWYp5', true],
            'empty' => ['', false],
            'garbage' => ['not-a-wallet', false],
        ];
    }

    /**
     * Test isValidWallet().
     */
    #[DataProvider('walletProvider')]
    public function testIsValidWallet(string $wallet, bool $expected): void
    {
        $validator = new WalletAddressValidator(CurrencyFactory::createRegistry());
        $this->assertSame($expected, Api::isValidWallet($validator, $wallet));
    }

    /**
     * Test getApiUrl().
     */
    public function testGetApiUrl(): void
    {
        $this->assertSame('https://api.wp-x402.com/verify', Api::getApiUrl(Api::ACTION_VERIFY));
        $this->assertSame('https://api.wp-x402.com/', Api::getApiUrl(null));

        add_filter('wp_x402_api_url', static fn(): string => 'https://api.example.test');
        $this->assertSame('https://api.example.test/collect', Api::getApiUrl(Api::ACTION_COLLECT));
        remove_all_filters('wp_x402_api_url');
    }

    /**
     * Test wpRemote() sends a JSON POST with our user agent.
     */
    public function testWpRemote(): void
    {
        $this->fakeHttp(['success' => true]);
        $response = Api::wpRemote(Api::getApiUrl(Api::ACTION_VERIFY), ['foo' => 'bar']);

        $this->assertIsArray($response);
        $this->assertCount(1, $this->requests);
        $request = $this->requests[0];
        $this->assertSame('https://api.wp-x402.com/verify', $request['url']);
        $this->assertSame('POST', $request['args']['method']);
        $this->assertSame(['foo' => 'bar'], json_decode($request['args']['body'], true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame('application/json', $request['args']['headers']['Content-Type']);
        $this->assertStringStartsWith(Api::USER_AGENT . '/' . VERSION . '; ', $request['args']['user-agent']);
    }
}
