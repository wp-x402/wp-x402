<?php

declare(strict_types=1);

namespace WpX402\WpX402;

use TheFrosty\WpUtilities\Utils\Anonymizer;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Middleware\Middleware;
use WpX402\WpX402\Middleware\Rejection;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Telemetry\EventType;
use function header;
use function headers_sent;
use function sprintf;

/**
 * Returns the Anonymizer.
 * @return object<Anonymizer>
 */
function anonymizer(): object
{
    static $instance;
    $instance ??= new class {
        use Anonymizer;
    };

    return $instance;
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
 * Set an HTTP header message with the `X-402` key.
 * @param string $message
 */
function setHeader(string $message): void
{
    if (!headers_sent()) {
        header(sprintf('%s: %s', Api::HEADER_X_402, $message));
    }
}

/**
 * Telemetry API.
 * @param EventType $event_type
 * @param array $meta
 * @throws \JsonException
 */
function telemetry(EventType $event_type, array $meta = []): void
{
    if (!Setting::allowTelemetry()) {
        return;
    }

    $data = [
        Api::ACTION => Api::ACTION_COLLECT,
        'uuid' => anonymizer()->uuid(),
        'event_type' => $event_type->value,
        'project_type' => 'wordpress-plugin',
        'wallet' => anonymizer()->anonymize(Setting::getWallet()),
        'amount' => Setting::getPrice(),
        'meta' => $meta,
    ];

    Api::wpRemote(Api::getApiUrl(Api::ACTION_COLLECT), $data, ['timeout' => 2]);
}
