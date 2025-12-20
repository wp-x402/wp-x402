<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Api;

use TheFrosty\WpUtilities\Api\WpRemote;
use WP_Error;
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

    public static function getApiUrl(): string
    {
        return home_url('/api/x402');
    }

    public static function wpRemote(string $url, array $data): array|WP_Error
    {
        return (new self())->wpRemotePost($url, [
            'headers' => [
                'user-agent' => sprintf('WpX402/%s; %s', VERSION, esc_url(get_bloginfo('url'))),
            ],
            'body' => json_encode($data, JSON_THROW_ON_ERROR),
            'sslverify' => false,
            'timeout' => 15,
        ]);
    }
}
