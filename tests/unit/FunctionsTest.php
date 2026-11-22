<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Middleware\Middleware;
use WpX402\WpX402\Settings\Misc;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Telemetry\EventType;
use function json_decode;
use function WpX402\WpX402\middleware;
use function WpX402\WpX402\telemetry;
use const JSON_THROW_ON_ERROR;

/**
 * Class FunctionsTest
 * @package WpX402\WpX402\Tests
 */
#[CoversFunction('WpX402\WpX402\middleware')]
#[CoversFunction('WpX402\WpX402\telemetry')]
#[CoversFunction('WpX402\WpX402\anonymizer')]
#[UsesClass(Api::class)]
#[UsesClass(Setting::class)]
class FunctionsTest extends TestCase
{

    /**
     * Test middleware() returns a shared instance.
     */
    public function testMiddleware(): void
    {
        $this->assertInstanceOf(Middleware::class, middleware());
        $this->assertSame(middleware(), middleware());
    }

    /**
     * Test telemetry() does nothing when telemetry is off (the default).
     */
    public function testTelemetryDisabled(): void
    {
        $this->fakeHttp(['success' => true]);
        telemetry(EventType::REQUIRED);
        $this->assertCount(0, $this->requests);
    }

    /**
     * Test telemetry() posts the event when telemetry is on.
     */
    public function testTelemetryEnabled(): void
    {
        $this->setMiscSettings([Misc::TELEMETRY => 'on']);
        $this->fakeHttp(['success' => true]);
        telemetry(EventType::SUCCESS, ['signature' => 'abc']);

        $this->assertCount(1, $this->requests);
        $this->assertSame('https://api.wp-x402.com/collect', $this->requests[0]['url']);
        $body = json_decode($this->requests[0]['args']['body'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('collect', $body['wp_x402']);
        $this->assertSame('payment_success', $body['event_type']);
        $this->assertSame('wordpress-plugin', $body['project_type']);
        $this->assertSame(['signature' => 'abc'], $body['meta']);
        $this->assertNotEmpty($body['uuid']);
    }
}
