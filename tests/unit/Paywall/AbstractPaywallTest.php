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
use function base64_encode;

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
}
