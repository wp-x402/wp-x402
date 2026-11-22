<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use WP_Http;
use WpX402\WpX402\Middleware\Rejection;
use WpX402\WpX402\Tests\TestCase;
use function WpX402\WpX402\reject;

/**
 * Class RejectionTest
 * @package WpX402\WpX402\Tests\Middleware
 */
#[CoversClass(Rejection::class)]
#[CoversFunction('WpX402\WpX402\reject')]
class RejectionTest extends TestCase
{

    /**
     * Test unauthorized().
     */
    public function testUnauthorized(): void
    {
        $rejection = Rejection::unauthorized();
        $this->assertSame('x402_unauthorized', $rejection->getCode());
        $this->assertSame('Unauthorized', $rejection->getMessage());
        // Status defaults to 401 when not set.
        $this->assertSame(WP_Http::UNAUTHORIZED, $rejection->getStatus());
    }

    /**
     * Test the reject() helper function.
     */
    public function testRejectFunction(): void
    {
        $rejection = reject('x402_forbidden', 'Forbidden', WP_Http::FORBIDDEN);
        $this->assertSame('x402_forbidden', $rejection->getCode());
        $this->assertSame('Forbidden', $rejection->getMessage());
        $this->assertSame(WP_Http::FORBIDDEN, $rejection->getStatus());

        $rejection->setStatus(null);
        $this->assertSame(WP_Http::UNAUTHORIZED, $rejection->getStatus());
    }
}
