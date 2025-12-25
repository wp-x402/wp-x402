<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

/**
 * Interface PaywallInterface
 * @package TheFrosty\WpX402\Paywall
 */
interface PaywallInterface
{
    public const float DEFAULT_PRICE = 0.01;
    public const string PAYWALL_ENABLED = 'paywall_enabled';
}
