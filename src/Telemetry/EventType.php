<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Telemetry;

enum EventType: string
{
    case HEARTBEAT = 'heartbeat';
    case REQUIRED = 'payment_required';
    case SUCCESS = 'payment_success';
    case FAILED = 'payment_failed';
}
