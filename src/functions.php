<?php

declare(strict_types=1);

namespace TheFrosty\WpX402;

use TheFrosty\WpX402\Middleware\Middleware;
use TheFrosty\WpX402\Middleware\Rejection;

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
