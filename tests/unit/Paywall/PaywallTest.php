<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversClass;
use WpX402\WpX402\Paywall\Paywall;
use WpX402\WpX402\Tests\TestCase;

/**
 * Class PaywallTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversClass(Paywall::class)]
class PaywallTest extends TestCase
{

    /**
     * Test isPaywallEnabled() without a post.
     */
    public function testIsPaywallEnabledWithoutPost(): void
    {
        $this->assertFalse(Paywall::isPaywallEnabled(false));
    }

    /**
     * Test areCategoriesExcludedFromPaywall() without categories.
     */
    public function testAreCategoriesExcludedFromPaywallWithoutCategories(): void
    {
        $this->assertFalse(Paywall::areCategoriesExcludedFromPaywall([]));
    }
}
