<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\HttpFoundation\Request;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Api\Bots;
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
use function add_filter;
use function base64_decode;
use function base64_encode;
use function json_decode;
use function remove_all_filters;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * Class ForBotsTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversClass(ForBots::class)]
#[UsesClass(AbstractPaywall::class)]
#[UsesClass(Api::class)]
#[UsesClass(Bots::class)]
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
     * Tear down.
     */
    public function tearDown(): void
    {
        $this->setStaticRequest(AbstractPaywall::class, null);
        remove_all_filters('wp_x402_debug');
        parent::tearDown();
    }

    /**
     * Request headers, query, user agent, debug, is an x402 client.
     * @return array<string, array{array<string, string>, array<string, string>, string, bool, bool}>
     */
    public static function clientProvider(): array
    {
        $signature = [Api::HEADER_PAYMENT_SIGNATURE => base64_encode('{"x402Version":2}')];
        $browser = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 Safari/605.1.15';

        return [
            'payment from any user agent' => [$signature, [], $browser, false, true],
            'known AI agent' => [[], [], 'Mozilla/5.0 (compatible; GPTBot/1.2; +https://openai.com/gptbot)', false, true],
            'browser' => [[], [], $browser, false, false],
            'no user agent' => [[], [], '', false, false],
            'fakeBot when debugging' => [[], ['fakeBot' => '1'], $browser, true, true],
            'fakeBot ignored without debug' => [[], ['fakeBot' => '1'], $browser, false, false],
        ];
    }

    /**
     * Test isX402Client().
     * @param array<string, string> $headers
     * @param array<string, string> $query
     */
    #[DataProvider('clientProvider')]
    public function testIsX402Client(array $headers, array $query, string $agent, bool $debug, bool $expected): void
    {
        $this->fakeHttp(['GPTBot' => ['operator' => 'OpenAI'], 'ClaudeBot' => ['operator' => 'Anthropic']]);
        add_filter('wp_x402_debug', $debug ? '__return_true' : '__return_false');
        $request = new Request($query, server: $agent === '' ? [] : ['HTTP_USER_AGENT' => $agent]);
        $request->headers->add($headers);
        $this->setStaticRequest(AbstractPaywall::class, $request);

        $this->assertSame($expected, $this->invoke(new ForBots(), 'isX402Client'));
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

    /**
     * Response code, response body, is valid.
     * @return array<string, array{int, string, bool}>
     */
    public static function verificationProvider(): array
    {
        return [
            'valid' => [200, '{"isValid":true,"payer":"0x857b06519E91e3A54538791bDbb0E22373e36b66"}', true],
            'invalid' => [200, '{"isValid":false,"invalidReason":"insufficient_funds"}', false],
            'truthy is not true' => [200, '{"isValid":"true"}', false],
            'valid body, error status' => [202, '{"isValid":true}', false],
            'api error' => [401, '{"error":"`PAYMENT-SIGNATURE` header is required"}', false],
            'not json' => [200, '<html></html>', false],
        ];
    }

    /**
     * Test isPaymentValid() only accepts a 200 with `isValid: true`.
     */
    #[DataProvider('verificationProvider')]
    public function testIsPaymentValid(int $code, string $body, bool $expected): void
    {
        $response = ['response' => ['code' => $code, 'message' => ''], 'body' => $body, 'headers' => []];
        $this->assertSame($expected, $this->invoke(ForBots::class, 'isPaymentValid', $response));
    }

    /**
     * Test getJsonBody().
     */
    public function testGetJsonBody(): void
    {
        $this->assertSame(
            ['isValid' => false, 'invalidReason' => 'insufficient_funds'],
            $this->invoke(ForBots::class, 'getJsonBody', ['body' => '{"isValid":false,"invalidReason":"insufficient_funds"}'])
        );
        $this->assertSame([], $this->invoke(ForBots::class, 'getJsonBody', ['body' => '"a string"']));
        $this->assertSame([], $this->invoke(ForBots::class, 'getJsonBody', ['body' => '']));
    }

    /**
     * Response code, response body, is settled.
     * @return array<string, array{int, string, bool}>
     */
    public static function settlementProvider(): array
    {
        return [
            'settled' => [200, '{"success":true,"transaction":"0x1234","network":"eip155:84532"}', true],
            'failed' => [200, '{"success":false,"errorReason":"insufficient_funds","transaction":""}', false],
            'error status' => [500, '{"success":true}', false],
            'not json' => [200, 'oops', false],
        ];
    }

    /**
     * Test isSettled() only accepts a 200 with `success: true`.
     */
    #[DataProvider('settlementProvider')]
    public function testIsSettled(int $code, string $body, bool $expected): void
    {
        $response = ['response' => ['code' => $code, 'message' => ''], 'body' => $body, 'headers' => []];
        $this->assertSame($expected, $this->invoke(ForBots::class, 'isSettled', $response));
    }

    /**
     * Test facilitate() posts settle requests to the settle endpoint.
     */
    public function testFacilitateSettle(): void
    {
        $this->fakeHttp(['success' => true]);
        $paywall = new ForBots();
        $payment_required = $this->invoke($paywall, 'getPaymentRequired', 'https://example.org/post/');

        $this->invoke($paywall, 'facilitate', Api::ACTION_SETTLE, $payment_required, 'c2ln');
        $this->assertSame('https://api.wp-x402.com/settle', $this->requests[0]['url']);
        $body = json_decode($this->requests[0]['args']['body'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(Api::ACTION_SETTLE, $body[Api::ACTION]);
    }
}
