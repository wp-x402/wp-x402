<?php

declare(strict_types=1);

namespace WpX402\WpX402\Telemetry;

enum EventType: string
{
    case HEARTBEAT = 'heartbeat';
    case REQUIRED = 'payment_required';
    case SUCCESS = 'payment_success';
    case FAILED = 'payment_failed';
}
