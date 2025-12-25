<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Api;

use Multicoin\AddressValidator\WalletAddressValidator;
use TheFrosty\WpUtilities\Api\WpRemote;
use WP_Error;
use function array_merge;
use function esc_url;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const TheFrosty\WpX402\VERSION;

/**
 * Class Api
 * @package TheFrosty\WpX402\Api
 */
class Api
{

    use WpRemote;

    public const string ACTION = 'wp_x402';
    public const string ACTION_COLLECT = 'collect';
    public const string ACTION_VERIFY = 'verify';
    public const string HEADER_PAYMENT_RESPONSE = 'PAYMENT-RESPONSE';
    public const string HEADER_PAYMENT_REQUIRED = 'PAYMENT-REQUIRED';
    public const string HEADER_PAYMENT_SIGNATURE = 'HTTP_PAYMENT-SIGNATURE';
    public const string PAYMENT_SIGNATURE = 'paymentSignature';

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
     * @return string
     */
    public static function getApiUrl(): string
    {
        return home_url('/x402/api');
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
            'timeout' => 7,
            'user-agent' => sprintf('%s/%s; %s', self::USER_AGENT, VERSION, esc_url(get_bloginfo('url'))),
        ];

        return (new self())->wpRemotePost($url, array_merge($defaults, $args));
    }
}
