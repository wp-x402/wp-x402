<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Paywall\AbstractPaywall;
use WpX402\WpX402\Paywall\ForBots;
use WpX402\WpX402\Tests\TestCase;
use function add_filter;
use function base64_encode;
use function remove_all_filters;

/**
 * Class AbstractPaywallTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversClass(AbstractPaywall::class)]
class AbstractPaywallTest extends TestCase
{

    /**
     * Tear down.
     */
    public function tearDown(): void
    {
        $this->setStaticRequest(AbstractPaywall::class, null);
        parent::tearDown();
    }

    /**
     * Request headers, expected signature.
     * @return array<string, array{array<string, string>, string|null}>
     */
    public static function signatureProvider(): array
    {
        $signature = base64_encode('{"x402Version":2}');

        return [
            'v2 header' => [[Api::HEADER_PAYMENT_SIGNATURE => $signature], $signature],
            'v1 header' => [[Api::HEADER_X_PAYMENT => $signature], $signature],
            'no header' => [[], null],
            'not base64' => [[Api::HEADER_PAYMENT_SIGNATURE => 'not base64!'], null],
        ];
    }

    /**
     * Test getPaymentSignature().
     * @param array<string, string> $headers
     */
    #[DataProvider('signatureProvider')]
    public function testGetPaymentSignature(array $headers, ?string $expected): void
    {
        $request = new Request();
        $request->headers->add($headers);
        $this->setStaticRequest(AbstractPaywall::class, $request);

        $this->assertSame($expected, $this->invoke(new ForBots(), 'getPaymentSignature'));
    }

    /**
     * Test buildJsonResponse() marks paywall responses as uncacheable and varying on the payment headers.
     */
    public function testBuildJsonResponse(): void
    {
        $response = $this->invoke(new ForBots(), 'buildJsonResponse', ['error' => 'Payment Required'], 402, ['X-Test' => 'yes']);

        $this->assertSame(402, $response->getStatusCode());
        $this->assertSame('{"error":"Payment Required"}', $response->getContent());
        $this->assertSame('yes', $response->headers->get('X-Test'));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('PAYMENT-SIGNATURE, X-PAYMENT', $response->headers->get('Vary'));
    }

    /**
     * Test isDebug() follows WP_DEBUG (on in the test config) and the `wp_x402_debug` filter.
     */
    public function testIsDebug(): void
    {
        $paywall = new ForBots();
        $this->assertSame(WP_DEBUG, $this->invoke($paywall, 'isDebug'));

        add_filter('wp_x402_debug', '__return_false');
        $this->assertFalse($this->invoke($paywall, 'isDebug'));
        remove_all_filters('wp_x402_debug');
    }
}
