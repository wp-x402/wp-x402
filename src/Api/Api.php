<?php

declare(strict_types=1);

namespace WpX402\WpX402\Api;

use Multicoin\AddressValidator\WalletAddressValidator;
use TheFrosty\WpUtilities\Api\WpRemote;
use WP_Error;
use function apply_filters;
use function array_merge;
use function esc_url;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const WpX402\WpX402\VERSION;

/**
 * Class Api
 * @package WpX402\WpX402\Api
 */
class Api
{

    use WpRemote;

    public const string ACTION = 'wp_x402';
    public const string ACTION_COLLECT = 'collect';
    public const string ACTION_VERIFY = 'verify';
    public const string HEADER_X_402 = 'X-402';
    public const string HEADER_PAYMENT_RESPONSE = 'PAYMENT-RESPONSE';
    public const string HEADER_PAYMENT_REQUIRED = 'PAYMENT-REQUIRED';
    public const string HEADER_PAYMENT_SIGNATURE = 'PAYMENT-SIGNATURE';
    public const string HEADER_X_PAYMENT = 'X-PAYMENT';
    public const string NETWORK = 'network';
    public const string PAYMENT_REQUIREMENTS = 'paymentRequirements';
    public const string PAYMENT_SIGNATURE = 'paymentSignature';
    final public const string URL = 'https://api.wp-x402.com';

    final public const string USER_AGENT = 'WpX402';

    /**
     * Validate the wallet address based on supported networks.
     * @param WalletAddressValidator $validator
     * @param string $value The wallet address to validate
     * @return bool
     */
    public static function isValidWallet(WalletAddressValidator $validator, string $value): bool
    {
        return $validator->validate($value, 'eth') || $validator->validate($value, 'sol');
    }

    /**
     * Get the API URL.
     * @param string|null $action
     * @return string
     */
    public static function getApiUrl(?string $action = ''): string
    {
        return sprintf('%s/%s', apply_filters('wp_x402_api_url', self::URL), $action ?? '');
    }

    /**
     * Return a remote POST request.
     * @param string $url URL.
     * @param array $data POST body data.
     * @param array $args Additional POST request args.
     * @return array|WP_Error
     * @throws \JsonException
     */
    public static function wpRemote(string $url, array $data, array $args = []): array|WP_Error
    {
        $defaults = [
            'body' => json_encode($data, JSON_THROW_ON_ERROR),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 7,
            'user-agent' => sprintf('%s/%s; %s', self::USER_AGENT, VERSION, esc_url(get_bloginfo('url'))),
        ];

        return (new self())->wpRemotePost($url, array_merge($defaults, $args));
    }
}
