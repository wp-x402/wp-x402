<?php

declare(strict_types=1);

namespace WpX402\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\Options;
use NumberFormatter;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Paywall\PaywallInterface;
use function sprintf;

/**
 * Class Setting
 * @package WpX402\WpX402\Settings
 */
class Setting
{

    public const int USDC_DECIMALS = 6;

    /**
     * Is telemetry allowed?
     * @return bool
     */
    public static function allowTelemetry(): bool
    {
        return self::getMiscSetting(Misc::TELEMETRY, 'off') === 'on';
    }

    /**
     * Get allowed accounts.
     * @return array
     */
    public static function getAccounts(): array
    {
        return [
            'base' => esc_html__('EVM', 'wp-x402'),
            'solana' => esc_html__('Solana', 'wp-x402'),
        ];
    }

    /**
     * Return the account setting.
     * @return string
     */
    public static function getAccount(): string
    {
        return sanitize_text_field(self::getGeneralSetting(General::ACCOUNT, array_key_first(self::getAccounts())));
    }

    /**
     * Return the wallet setting.
     * @return string
     */
    public static function getWallet(): string
    {
        return sanitize_text_field(self::getGeneralSetting(sprintf(General::WALLET, self::getAccount()), ''));
    }

    /**
     * Return the network setting.
     * @return string
     */
    public static function getNetwork(): string
    {
        return sanitize_text_field(self::getGeneralSetting(General::NETWORK, Testnet::class));
    }

    /**
     * Return the price setting.
     * @return string
     */
    public static function getPrice(): string
    {
        $price = (float)self::getGeneralSetting(General::PRICE, PaywallInterface::DEFAULT_PRICE);
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return sanitize_text_field($formatter->formatCurrency($price, 'USD'));
    }

    /**
     * Return the price in the asset's atomic units, as the x402 `amount` field requires.
     * USDC uses 6 decimals, so a price of 0.01 is "10000".
     * @param int $decimals The asset's decimals.
     * @return string
     */
    public static function getAmount(int $decimals = self::USDC_DECIMALS): string
    {
        $price = (float)self::getGeneralSetting(General::PRICE, PaywallInterface::DEFAULT_PRICE);

        return (string)max(0, (int)round($price * (10 ** $decimals)));
    }

    /**
     * Is the Mainnet selected?
     * @return bool
     */
    public static function isMainnet(): bool
    {
        return self::getNetwork() === Mainnet::class;
    }

    /**
     * Get our option key in our general setting index.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    protected static function getGeneralSetting(string $key, mixed $default = null): mixed
    {
        return Options::getOption($key, General::SECTION_ID, $default);
    }

    /**
     * Get our option key in our misc setting index.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    protected static function getMiscSetting(string $key, mixed $default = null): mixed
    {
        return Options::getOption($key, Misc::SECTION_ID, $default);
    }
}
