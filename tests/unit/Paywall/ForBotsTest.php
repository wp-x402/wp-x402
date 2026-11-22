<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Paywall\AbstractPaywall;
use WpX402\WpX402\Paywall\Entitlement;
use WpX402\WpX402\Paywall\ForBots;
use WpX402\WpX402\Settings\General;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Tests\TestCase;
use function base64_decode;
use function base64_encode;
use function json_decode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * Class ForBotsTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversClass(ForBots::class)]
#[UsesClass(AbstractPaywall::class)]
#[UsesClass(Api::class)]
#[UsesClass(PaymentRequired::class)]
#[UsesClass(Accepts::class)]
#[UsesClass(UrlResource::class)]
#[UsesClass(Mainnet::class)]
#[UsesClass(Testnet::class)]
#[UsesClass(Entitlement::class)]
#[UsesClass(Setting::class)]
class ForBotsTest extends TestCase
{

    private const string EVM_WALLET = '0x209693Bc6afc0C5328bA36FaF03C514EF312287C';

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setGeneralSettings([
            General::ACCOUNT => 'base',
            sprintf(General::WALLET, 'base') => self::EVM_WALLET,
        ]);
    }

    /**
     * Test getFacilitatorData() sends the accepted requirements entry and the payment signature.
     */
    public function testGetFacilitatorData(): void
    {
        $paywall = new ForBots();
        $payment_required = $this->invoke($paywall, 'getPaymentRequired', 'https://example.org/post/');
        $signature = base64_encode('{"x402Version":2}');

        $data = $this->invoke($paywall, 'getFacilitatorData', Api::ACTION_VERIFY, $payment_required, $signature);
        $this->assertSame(Api::ACTION_VERIFY, $data[Api::ACTION]);
        $this->assertSame('testnet', $data[Api::NETWORK]);
        $this->assertSame($signature, $data[Api::PAYMENT_SIGNATURE]);

        $requirements = json_decode(
            (string)base64_decode($data[Api::PAYMENT_REQUIREMENTS]),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertSame($payment_required->getAccepts()[0]->toArray(), $requirements);
        $this->assertSame('eip155:84532', $requirements['network']);
        $this->assertSame('10000', $requirements['amount']);
        $this->assertArrayNotHasKey('accepts', $requirements);
    }

    /**
     * Test facilitate() posts to the API action endpoint.
     */
    public function testFacilitate(): void
    {
        $this->setGeneralSettings([
            General::ACCOUNT => 'base',
            General::NETWORK => Mainnet::class,
            sprintf(General::WALLET, 'base') => self::EVM_WALLET,
        ]);
        $this->fakeHttp(['isValid' => true]);
        $paywall = new ForBots();
        $payment_required = $this->invoke($paywall, 'getPaymentRequired', 'https://example.org/post/');

        $response = $this->invoke($paywall, 'facilitate', Api::ACTION_VERIFY, $payment_required, 'c2ln');
        $this->assertIsArray($response);
        $this->assertCount(1, $this->requests);
        $this->assertSame('https://api.wp-x402.com/verify', $this->requests[0]['url']);
        $body = json_decode($this->requests[0]['args']['body'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('mainnet', $body[Api::NETWORK]);
        $this->assertSame('c2ln', $body[Api::PAYMENT_SIGNATURE]);
    }
}
