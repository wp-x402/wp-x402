<?php

declare(strict_types=1);

namespace TheFrosty\WpX402;

use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Content\Payment;
use TheFrosty\WpX402\Middleware\Middleware;
use TheFrosty\WpX402\Middleware\Rejection;
use TheFrosty\WpX402\Telemetry\EventType;

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
    $wallet = get_option('x402_wallet', Payment::TESTNET_WALLET);
    $price = get_option('x402_price', Payment::DEFAULT_PRICE);

    $data = [
        Api::ACTION => Api::ACTION_COLLECT,
        'event_type' => $event_type->value,
        'project_type' => 'wordpress-plugin',
        'wallet' => $wallet,
        'amount' => $price,
        'meta' => $meta,
    ];

    Api::wpRemote(Api::getApiUrl(), $data);
}
