<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversClass;
use WpX402\WpX402\Paywall\Entitlement;
use WpX402\WpX402\Tests\TestCase;

/**
 * Class EntitlementTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversClass(Entitlement::class)]
class EntitlementTest extends TestCase
{

    /**
     * Test choices().
     */
    public function testChoices(): void
    {
        $this->assertSame(
            [
                'digital' => 'Digital Access',
                'payment-required' => 'Payment Required',
            ],
            Entitlement::choices()
        );
    }

    /**
     * Test label().
     */
    public function testLabel(): void
    {
        $this->assertSame('Payment Required', Entitlement::PAYMENT_REQUIRED->label());
        $this->assertSame('Digital Access', Entitlement::DIGITAL->label());
    }
}
