<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use function esc_html__;

enum Entitlement: string
{
    case DIGITAL = 'digital';
    case PAYMENT_REQUIRED = 'payment-required';

    /**
     * Returns array of entitlement choices with labels and values.
     * @return array
     */
    public static function choices(): array
    {
        return array_combine(
            array_map(static fn(Entitlement $entitlement) => $entitlement->value, self::cases()),
            array_map(static fn(Entitlement $entitlement) => $entitlement->label(), self::cases())
        );
    }

    /**
     * Returns label match for value.
     * @return string
     */
    public function label(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
        return match ($this) {
            self::DIGITAL => esc_html__('Digital Access', 'wp-x402'),
            self::PAYMENT_REQUIRED => esc_html__('Payment Required', 'wp-x402'),
        };
    }
}
