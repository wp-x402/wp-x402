<?php

declare(strict_types=1);

namespace TheFrosty\WpX402;

use Dwnload\WpSettingsApi\Api\Options;
use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Middleware\Middleware;
use TheFrosty\WpX402\Middleware\Rejection;
use TheFrosty\WpX402\Paywall\PaywallInterface;
use TheFrosty\WpX402\Settings\Settings;
use TheFrosty\WpX402\Telemetry\EventType;
use function sanitize_text_field;

/**
 * Return the price setting.
 * @return string
 */
function getPrice(): string
{
    return sanitize_text_field(getSetting(Settings::PRICE, PaywallInterface::DEFAULT_PRICE));
}

/**
 * Return the wallet setting.
 * @return string
 */
function getWallet(): string
{
    return sanitize_text_field(getSetting(Settings::WALLET, PaywallInterface::TESTNET_WALLET));
}

/**
 * Get our general setting by key.
 * @param string $key
 * @param mixed|null $default
 * @return mixed
 */
function getSetting(string $key, mixed $default = null): mixed
{
    return Options::getOption($key, Settings::GENERAL_SETTINGS, $default);
}

/**
 * Returns the Middleware.
 * @return Middleware
 */
function middleware(): Middleware
{
    static $instance;
    $instance ??= new Middleware();

    return $instance;
}

/**
 * Returns a Rejection.
 * @param string $code
 * @param string $message
 * @param int|null $status
 * @return Rejection
 */
function reject(string $code, string $message, int|null $status): Rejection
{
    return new Rejection([Rejection::CODE => $code, Rejection::MESSAGE => $message, Rejection::STATUS => $status]);
}

/**
 * Telemetry API.
 * @param EventType $event_type
 * @param array $meta
 * @throws \JsonException
 */
function telemetry(EventType $event_type, array $meta = []): void
{
    $data = [
        Api::ACTION => Api::ACTION_COLLECT,
        'event_type' => $event_type->value,
        'project_type' => 'wordpress-plugin',
        'wallet' => getWallet(),
        'amount' => getPrice(),
        'meta' => $meta,
    ];

    Api::wpRemote(Api::getApiUrl(), $data);
}
