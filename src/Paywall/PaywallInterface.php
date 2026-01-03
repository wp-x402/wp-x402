<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

/**
 * Interface PaywallInterface
 * @package WpX402\WpX402\Paywall
 */
interface PaywallInterface
{
    public const float DEFAULT_PRICE = 0.01;
    public const string PAYWALL_ENABLED = 'paywall_enabled';
}
