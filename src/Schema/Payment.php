<?php

declare(strict_types=1);

namespace WpX402\WpX402\Schema;

/**
 * Payment Schemes allowed for x402.
 */
enum Payment: string
{

    case EXACT = 'exact';
    case UPTO = 'upto';
}
