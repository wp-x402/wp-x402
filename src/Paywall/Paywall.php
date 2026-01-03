<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use function filter_var;
use function get_post_meta;
use const FILTER_VALIDATE_BOOL;

/**
 * Class Paywall
 * @package CrainsGrandRapids\Meta
 */
class Paywall
{

    /**
     * Check if paywall is enabled for given post.
     * @param int|false $post_id
     * @return bool
     */
    public static function isPaywallEnabled(int|false $post_id): bool
    {
        if ($post_id === false) {
            return false;
        }

        return self::isPaywallEnabledForObject($post_id);
    }

    /**
     * Check if paywall is enabled for given object id.
     * @param int $post_id
     * @return bool
     */
    private static function isPaywallEnabledForObject(int $post_id): bool
    {
        $paywall_enabled = get_post_meta($post_id, PaywallInterface::PAYWALL_ENABLED, true);
        if (empty($paywall_enabled) && $paywall_enabled !== '0') {
            return true;
        }
        return filter_var($paywall_enabled, FILTER_VALIDATE_BOOL);
    }
}
