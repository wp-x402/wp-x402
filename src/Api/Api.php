<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Api;

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

    public const string ACTION = 'x402-action';
    public const string ACTION_COLLECT = 'collect';
    public const string ACTION_VERIFY = 'verify';

    final public const string USER_AGENT = 'WpX402';

    /**
     * Get the API URL.
     * @return string
     */
    public static function getApiUrl(): string
    {
        return home_url('/api/x402');
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
            'headers' => [
                'user-agent' => sprintf('%s/%s; %s', self::USER_AGENT, VERSION, esc_url(get_bloginfo('url'))),
            ],
            'body' => json_encode($data, JSON_THROW_ON_ERROR),
            'timeout' => 7,
        ];

        return (new self())->wpRemotePost($url, array_merge($defaults, $args));
    }
}
